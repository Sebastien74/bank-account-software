<?php

declare(strict_types=1);

namespace App\Controller\Back\Wallet;

use App\Controller\Back\BaseController;
use App\Entity\Wallet\Operation;
use App\Entity\Wallet\Wallet;
use App\Form\Manager\GlobalManagerInterface;
use App\Form\Manager\Wallet\OperationInterface;
use App\Form\Type\Wallet\OperationImportType;
use App\Form\Type\Wallet\OperationType;
use App\Model\Wallet\WalletModel;
use App\Service\AdminLocatorInterface;
use App\Service\CoreLocatorInterface;
use App\Service\Wallet\RecurringChargeForecast;
use App\Service\Wallet\SavingsReconciler;
use Exception;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * WalletController.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_ADMIN')]
#[Route('/back-%security_token%/wallets/operations', schemes: '%protocol%')]
class OperationController extends BaseController
{
    protected int $paginationLimit = -1;
    protected bool $forceEntities = true;

    protected ?string $pageIcon = 'wallet';

    protected ?string $classname = Operation::class;
    protected ?string $formType = OperationType::class;

    /**
     * OperationController constructor.
     */
    public function __construct(
        protected CoreLocatorInterface $coreLocator,
        protected AdminLocatorInterface $adminLocator,
        protected GlobalManagerInterface $globalFormManager,
        protected OperationInterface $operation,
        private readonly RecurringChargeForecast $forecast,
        private readonly SavingsReconciler $savingsReconciler,
    ) {
        parent::__construct($coreLocator, $adminLocator, $globalFormManager, $operation);
    }

    /**
     * Operation index.
     *
     * @throws Exception
     */
    #[Route('/index/{wallet}', name: 'back_operation_index', methods: 'GET|POST')]
    public function index(PaginatorInterface $paginator): Response
    {
        $this->template = 'back/pages/operations.html.twig';
        $wallet = $this->coreLocator->em()->getRepository(Wallet::class)->find($this->coreLocator->request()->attributes->get('wallet'));

        /*
         * Les deux paramètres viennent de la barre d'adresse. Sans validation,
         * ?year=x&month=y faisait lever \DateTime et rendait une 500 avant même
         * que le repository ne puisse les contrôler. Le mois sur un seul
         * chiffre est accepté puis repadé : le repository l'exige à deux
         * chiffres, et ?month=6 produisait jusqu'ici une 500.
         */
        $yearRequest = $this->coreLocator->request()->query->get('year');
        $monthRequest = $this->coreLocator->request()->query->get('month');
        $hasPeriod = is_string($yearRequest) && is_string($monthRequest)
            && 1 === preg_match('/^\d{4}$/', $yearRequest)
            && 1 === preg_match('/^(0?[1-9]|1[0-2])$/', $monthRequest);
        if (!$hasPeriod) {
            $yearRequest = $monthRequest = null;
        }
        $date = $this->arguments['date'] = $hasPeriod
            ? new \DateTime(sprintf('%s/%02d/01', $yearRequest, (int) $monthRequest))
            : new \DateTime('now', new \DateTimeZone('Europe/Paris'));
        $this->arguments['currentYear'] = $year = $date->format('Y');
        $this->arguments['currentMonth'] = $month = $date->format('m');
        $prev = (clone $date)->modify('first day of last month');
        $next = (clone $date)->modify('first day of next month');
        $this->arguments['previousYear']  = $prev->format('Y');
        $this->arguments['previousMonth'] = $prev->format('m');
        $this->arguments['nextYear']  = $next->format('Y');
        $this->arguments['nextMonth'] = $next->format('m');

        $sort = $this->arguments['sort'] = !$this->coreLocator->request()->query->get('sort') ? 'date' : $this->coreLocator->request()->query->get('sort');
        $order = $this->arguments['order'] = !$this->coreLocator->request()->query->get('order') ? 'DESC' : $this->coreLocator->request()->query->get('order');

        if (!$wallet instanceof Wallet) {
            throw $this->createNotFoundException();
        }

        /*
         * Les operations non rattachees sont dispersees sur tout l'historique :
         * les filtrer dans le mois courant n'en montrerait presque aucune. Le
         * filtre sort donc de la periode, et le gabarit le dit.
         */
        $repository = $this->coreLocator->em()->getRepository(Operation::class);
        $unlinkedOnly = $this->arguments['unlinkedOnly'] = $this->coreLocator->request()->query->getBoolean('unlinked');
        $this->arguments['unlinkedCount'] = $repository->countUnlinked($wallet);
        $this->entities = $unlinkedOnly
            ? $repository->findUnlinked($wallet)
            : $repository->findByYearMonth($year, $month, $sort, $order, new \DateTimeZone('Europe/Paris'), $wallet);
        $this->arguments['wallet'] = $walletModel = WalletModel::fromEntity($wallet, $this->coreLocator, ['operations' => $this->entities]);
        /*
         * L'import ne s'offre plus depuis l'ecran : un releve se reprend en
         * entier, avec ses bornes et son controle de solde, ce que la commande
         * `app:wallet:import` fait et qu'un depot de fichier ne permet pas de
         * surveiller. La route POST reste en place pour la commande et les
         * tests ; elle refuse les comptes d'epargne, qui n'ont pas de releve.
         */
        $this->arguments['importForm'] = false;

        /*
         * Sur le mois en cours, et seulement sur lui, on annonce les charges
         * récurrentes qui ne sont pas encore tombées : la question posée
         * devant cet écran est « que me reste-t-il à payer », et la liste des
         * écritures passées n'y répond pas. Sur un mois clos, l'annonce
         * n'aurait aucun sens — ce qui n'est pas tombé ne tombera plus.
         */
        $now = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
        $isCurrentMonth = $now->format('Y-m') === $date->format('Y-m');
        $pending = $isCurrentMonth && !$unlinkedOnly ? $this->forecast->pending($wallet, (int) $year, (int) $month) : [];
        /*
         * Une seule colonne Solde, donc une seule serie.
         *
         * L'echeance a venir et l'ecriture passee se suivent desormais dans le
         * meme tableau : leur donner chacune sa propre suite faisait sauter la
         * colonne d'une ligne a l'autre, le solde reel du 30 voisinant un
         * projete parti du solde de fin de mois. Les deux sont donc rejouees
         * ensemble, dans l'ordre du calendrier : chaque ligne porte le solde
         * reel a sa date, diminue des echeances deja franchies.
         *
         * Une echeance du jour passe apres les ecritures du meme jour : elle
         * n'est justement pas encore tombee.
         */
        [$balances, $pending] = $this->threadBalances($walletModel, $pending, $date);
        $this->arguments['balances'] = $balances;
        $this->arguments['pendingCharges'] = $pending;
        $this->arguments['pendingChargesTotal'] = $this->forecast->total($pending);
        $this->pageTitle = $this->coreLocator->translator()->trans('Mes opérations :', [], 'back').' '.$walletModel->title;

        $response = parent::index($paginator);
        $this->mirrorToSavings($wallet, $response);

        return $response;
    }

    /**
     * Soldes de la période, écritures et échéances à venir confondues.
     *
     * @param array<int, array<string, mixed>> $pending
     *
     * @return array{0: array<int, float>, 1: array<int, array<string, mixed>>}
     */
    private function threadBalances(WalletModel $walletModel, array $pending, \DateTimeInterface $period): array
    {
        $operations = $walletModel->currentOperations ?? [];
        $events = [];

        foreach ($operations as $id => $row) {
            $events[] = [
                'day' => $row['date'] instanceof \DateTimeInterface ? $row['date']->format('Y-m-d') : '',
                'order' => 0,
                'rank' => $row['rank'],
                'id' => $id,
                'balance' => $row['balance'],
            ];
        }
        foreach ($pending as $index => $charge) {
            $events[] = [
                'day' => sprintf('%s-%02d', $period->format('Y-m'), $charge['day']),
                'order' => 1,
                'rank' => $index,
                'index' => $index,
                'amount' => (float) $charge['amount'],
            ];
        }

        usort($events, static fn (array $a, array $b): int => [$a['day'], $a['order'], $a['rank']] <=> [$b['day'], $b['order'], $b['rank']]);

        /*
         * Le solde d'avant la premiere ecriture du mois se deduit d'elle : son
         * propre solde, moins ce qu'elle a mis ou retire. Il ne sert que si une
         * echeance tombe avant toute ecriture de la periode.
         */
        $first = null;
        foreach ($events as $event) {
            if (0 === $event['order']) {
                $first = $operations[$event['id']];
                break;
            }
        }
        $running = null === $first
            ? ($walletModel->balance ?? 0.0)
            : round($first['balance'] + ($first['model']->income ? -$first['amount'] : $first['amount']), 2);

        $deduction = 0.0;
        $balances = [];
        foreach ($events as $event) {
            if (0 === $event['order']) {
                $running = $event['balance'];
                $balances[$event['id']] = round($running - $deduction, 2);

                continue;
            }

            $deduction = round($deduction + $event['amount'], 2);
            $pending[$event['index']]['projected'] = round($running - $deduction, 2);
        }

        return [$balances, $pending];
    }

    /**
     * Reporte sur l'épargne les virements que la requête vient d'écrire.
     *
     * La question se pose après chaque écriture, pas seulement après un
     * import : un virement saisi à la main doit alimenter l'épargne comme
     * celui qui vient du relevé. On ne la pose que lorsque le formulaire a
     * effectivement enregistré — une redirection — sinon chaque affichage de
     * page déclencherait deux requêtes pour rien.
     */
    private function mirrorToSavings(Wallet $wallet, Response $response): void
    {
        if (!$response instanceof RedirectResponse || !$wallet->getSavingsWallet() instanceof Wallet) {
            return;
        }

        $mirrored = $this->savingsReconciler->reconcile($wallet);
        if ($mirrored['created'] > 0 || $mirrored['updated'] > 0) {
            $this->addFlash('success', $this->coreLocator->translator()->trans(
                "%created% virement(s) reporté(s) sur le compte d'épargne, %updated% mis à jour.",
                ['%created%' => $mirrored['created'], '%updated%' => $mirrored['updated']],
                'back'
            ));
        }
    }

    /**
     * Operation import from an XLSX bank statement.
     *
     * @throws Exception
     */
    #[Route('/import/{wallet}', name: 'back_operation_import', methods: 'POST')]
    public function import(): RedirectResponse
    {
        $wallet = $this->coreLocator->em()->getRepository(Wallet::class)->find($this->coreLocator->request()->attributes->get('wallet'));
        if (!$wallet instanceof Wallet) {
            throw $this->createNotFoundException();
        }

        if ($wallet->isSavings()) {
            throw $this->createAccessDeniedException("Un compte d'epargne ne s'importe pas : il est alimente par les virements du compte courant.");
        }

        $form = $this->createForm(OperationImportType::class);
        $form->handleRequest($this->coreLocator->request());

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $uploadedFile */
            $uploadedFile = $form->get('file')->getData();
            $importDir = $this->coreLocator->projectDir().'/var/import';
            if (!is_dir($importDir)) {
                mkdir($importDir, 0775, true);
            }
            $filename = uniqid('operations-', true).'.xlsx';
            $uploadedFile->move($importDir, $filename);
            $path = $importDir.\DIRECTORY_SEPARATOR.$filename;

            try {
                $report = $this->operation->import($path, $wallet);
                if ($report['errors']) {
                    $this->addFlash('danger', implode(' ', $report['errors']));
                } else {
                    $this->addFlash('success', $this->coreLocator->translator()->trans(
                        '%imported% opération(s) importée(s), %skipped% déjà présente(s) ignorée(s).',
                        ['%imported%' => $report['imported'], '%skipped%' => $report['skipped']],
                        'back'
                    ));

                    /*
                     * Un relevé apporte de nouveaux virements d'épargne : sans
                     * rapprochement dans la foulée, le compte d'épargne reste
                     * en retard d'un import sur le compte courant.
                     */
                    $mirrored = $this->savingsReconciler->reconcile($wallet);
                    if ($mirrored['created'] > 0 || $mirrored['updated'] > 0) {
                        $this->addFlash('success', $this->coreLocator->translator()->trans(
                            "%created% virement(s) reporté(s) sur le compte d'épargne, %updated% mis à jour.",
                            ['%created%' => $mirrored['created'], '%updated%' => $mirrored['updated']],
                            'back'
                        ));
                    }
                }
            } finally {
                if (is_file($path)) {
                    unlink($path);
                }
            }
        } elseif ($form->isSubmitted()) {
            $errors = [];
            foreach ($form->getErrors(true) as $error) {
                $errors[] = $error->getMessage();
            }
            $this->addFlash('danger', $errors ? implode(' ', $errors) : $this->coreLocator->translator()->trans('Le fichier déposé est invalide.', [], 'back'));
        }

        return $this->redirectToRoute('back_operation_index', ['wallet' => $wallet->getId()]);
    }

    /**
     * Operation edit.
     */
    #[Route('/edit/{operation}', name: 'back_operation_edit', methods: 'GET|POST')]
    public function edit(): Response
    {
        $this->pageTitle = $this->coreLocator->translator()->trans('Mes opérations', [], 'back');

        $response = parent::edit();
        $operation = $this->coreLocator->request()->attributes->get('operation');
        if ($operation instanceof Operation && $operation->getWallet() instanceof Wallet) {
            $this->mirrorToSavings($operation->getWallet(), $response);
        }

        return $response;
    }

    /**
     * Operation pointed.
     */
    #[Route('/pointed/{operation}', name: 'back_operation_pointed', methods: 'POST')]
    public function pointed(Request $request, Operation $operation): JsonResponse
    {
        /*
         * Un POST qui écrit en base se valide par un jeton. SameSite=lax
         * bloque aujourd'hui l'envoi du cookie sur un POST d'origine tierce,
         * mais c'est un réglage de navigateur, pas un contrôle applicatif.
         */
        if (!$this->isCsrfTokenValid('operation-pointed-'.$operation->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $operation->setPointed($request->request->getBoolean('status'));
        $this->coreLocator->em()->persist($operation);
        $this->coreLocator->em()->flush();

        return new JsonResponse(['success' => true]);
    }

    /**
     * Operation delete.
     */
    #[Route('/delete/{operation}', name: 'back_operation_delete', methods: 'POST')]
    public function delete(Operation $operation): RedirectResponse
    {
        return $this->redirect($this->globalFormManager->delete($operation));
    }

    /**
     * To set breadcrumb.
     */
    protected function breadcrumb(array $items = []): void
    {
        $items[$this->coreLocator->translator()->trans('Mes comptes', [], 'breadcrumb')] = 'back_wallet_index';
        $items[$this->coreLocator->translator()->trans('Opérations', [], 'breadcrumb')] = 'back_operation_index';
        if ($this->coreLocator->request()->attributes->get('operation')) {
            $items[$this->coreLocator->translator()->trans('Édition', [], 'back_breadcrumb')] = 'back_operation_edit';
        }

        parent::breadcrumb($items);
    }
}
