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

        $yearRequest = $this->coreLocator->request()->query->get('year');
        $monthRequest = $this->coreLocator->request()->query->get('month');
        $date = $this->arguments['date'] = $yearRequest && $monthRequest ? new \DateTime($yearRequest.'/'.$monthRequest.'/01')
            : new \DateTime('now', new \DateTimeZone('Europe/Paris'));
        $this->arguments['currentYear'] = $year = !$this->coreLocator->request()->query->get('year') ? $date->format('Y') : $this->coreLocator->request()->query->get('year');
        $this->arguments['currentMonth'] = $month = !$this->coreLocator->request()->query->get('month') ? $date->format('m') : $this->coreLocator->request()->query->get('month');
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

        $this->entities = $this->coreLocator->em()->getRepository(Operation::class)
            ->findByYearMonth($year, $month, $sort, $order, new \DateTimeZone('Europe/Paris'), $wallet);
        $this->arguments['wallet'] = $walletModel = WalletModel::fromEntity($wallet, $this->coreLocator, ['operations' => $this->entities]);
        $this->arguments['importForm'] = $this->createForm(OperationImportType::class, null, [
            'action' => $this->generateUrl('back_operation_import', ['wallet' => $wallet->getId()]),
        ])->createView();
        $this->pageTitle = $this->coreLocator->translator()->trans('Mes opérations :', [], 'back').' '.$walletModel->title;

        return parent::index($paginator);
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

        return parent::edit();
    }

    /**
     * Operation pointed.
     */
    #[Route('/pointed/{operation}', name: 'back_operation_pointed', methods: 'POST')]
    public function pointed(Request $request, Operation $operation): JsonResponse
    {
        $operation->setPointed((bool) $request->request->get('status'));
        $this->coreLocator->em()->persist($operation);
        $this->coreLocator->em()->flush();

        return new JsonResponse(['success' => true]);
    }

    /**
     * Operation delete.
     */
    #[Route('/delete/{operation}', name: 'back_operation_delete', methods: 'GET')]
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
        if ($this->coreLocator->request()->attributes->get('objective')) {
            $items[$this->coreLocator->translator()->trans('Édition', [], 'back_breadcrumb')] = 'back_operation_edit';
        }

        parent::breadcrumb($items);
    }
}
