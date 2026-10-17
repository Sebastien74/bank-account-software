<?php

declare(strict_types=1);

namespace App\Controller\Back\Wallet;

use App\Controller\Back\BaseController;
use App\Entity\Wallet\Operation;
use App\Entity\Wallet\OperationType;
use App\Entity\Wallet\Outsider;
use App\Entity\Wallet\RecurringCharge;
use App\Entity\Wallet\SubCategory;
use App\Entity\Wallet\Wallet;
use App\Form\Manager\GlobalManagerInterface;
use App\Repository\Wallet\RecurringChargeRepository;
use App\Service\AdminLocatorInterface;
use App\Service\CoreLocatorInterface;
use App\Service\Urlizer;
use App\Service\Wallet\RecurringChargeForecast;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * RecurringChargeController.
 *
 * Réglage des charges mensuelles détectées dans l'historique.
 *
 * La liste n'est pas saisie : elle est déduite des opérations passées. Cet
 * écran ne sert qu'à corriger ce que l'historique ne peut pas savoir — un
 * abonnement résilié, un tarif qui change, une échéance annuelle.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_ADMIN')]
#[Route('/back-%security_token%/wallets/recurring', schemes: '%protocol%')]
class RecurringChargeController extends BaseController
{
    protected ?string $pageIcon = 'calendar-star';

    public function __construct(
        protected CoreLocatorInterface $coreLocator,
        protected AdminLocatorInterface $adminLocator,
        protected GlobalManagerInterface $globalFormManager,
        protected mixed $formManager,
        private readonly RecurringChargeForecast $forecast,
        private readonly RecurringChargeRepository $chargeRepository,
    ) {
        parent::__construct($coreLocator, $adminLocator, $globalFormManager, $formManager);
    }

    /**
     * Charges programmées d'un compte.
     */
    #[Route('/index/{wallet}', name: 'back_recurring_index', methods: 'GET')]
    public function list(Wallet $wallet): Response
    {
        $now = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
        $this->pageTitle = $this->coreLocator->translator()->trans('Opérations programmées', [], 'back');
        $this->breadcrumb();

        // `coreArguments()` porte le nom de l'application, le point d'entrée
        // Webpack et les drapeaux de contexte : les omettre rendait la page
        // avec les valeurs de repli du gabarit de base.
        $this->arguments['pageTitle'] = $this->pageTitle;
        $this->arguments['pageIcon'] = $this->pageIcon;
        $this->arguments['wallet'] = $wallet;
        $this->arguments['charges'] = $this->forecast->catalogue($wallet, (int) $now->format('Y'), (int) $now->format('n'));
        $this->arguments['currentMonth'] = (int) $now->format('n');
        $this->arguments['currentYear'] = (int) $now->format('Y');

        /*
         * Le tiers et le poste se choisissent comme sur une operation : dans
         * les listes existantes, et le choix du tiers propose le poste que son
         * historique montre. Saisis en texte libre, deux orthographes faisaient
         * deux charges, et rien ne rattachait l'echeance a un poste connu.
         */
        $entityManager = $this->coreLocator->em();
        $this->arguments['outsiders'] = $entityManager->getRepository(Outsider::class)->findBy([], ['adminName' => 'ASC']);
        $this->arguments['subCategories'] = $entityManager->getRepository(SubCategory::class)->findBy([], ['adminName' => 'ASC']);
        $this->arguments['usualSubCategories'] = $entityManager->getRepository(Operation::class)->usualSubCategoryByOutsider();

        return $this->render('back/pages/recurring-charges.html.twig', $this->coreArguments());
    }

    /**
     * Enregistrement des réglages.
     *
     * Une seule soumission pour toute la liste : corriger un tarif et
     * désactiver un abonnement sont deux gestes de la même séance.
     */
    #[Route('/save/{wallet}', name: 'back_recurring_save', methods: 'POST')]
    public function save(Wallet $wallet): RedirectResponse
    {
        $request = $this->coreLocator->request();
        if (!$this->isCsrfTokenValid('recurring-'.$wallet->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $entityManager = $this->coreLocator->em();
        $existing = $this->chargeRepository->settingsFor($wallet);
        /** @var array<string, array<string, string>> $rows */
        $rows = $request->request->all('charges');

        foreach ($rows as $key => $row) {
            $charge = $existing[$key] ?? (new RecurringCharge())
                ->setWallet($wallet)
                ->setAdminName((string) ($row['name'] ?? $key))
                ->setSlug($key);

            $amount = $this->decimal($row['amount'] ?? null);
            $day = $this->integer($row['day'] ?? null, 1, 31);
            $frequency = (string) ($row['frequency'] ?? RecurringCharge::MONTHLY);
            $yearly = RecurringCharge::YEARLY === $frequency;

            $charge
                ->setActive(!empty($row['active']))
                ->setAmount($amount)
                ->setDueDay($day)
                ->setFrequency($frequency)
                ->setDueMonth($yearly ? $this->integer($row['dueMonth'] ?? null, 1, 12) : null);
            $this->applySchedule($charge, $row);

            $entityManager->persist($charge);
        }

        $entityManager->flush();
        $request->getSession()->getFlashBag()->add(
            'success',
            $this->coreLocator->translator()->trans('Les opérations programmées sont enregistrées.', [], 'back')
        );

        return $this->redirectToRoute('back_recurring_index', ['wallet' => $wallet->getId()]);
    }

    /**
     * Encaissement d'une echeance avant sa date prevue.
     *
     * Une ligne previsionnelle n'est pas une ecriture : rien ne s'y pointe. La
     * cocher veut dire « c'est passe », et cela ne peut se traduire que d'une
     * facon honnete : l'echeance devient une vraie operation, datee du jour. La
     * prevision disparait alors d'elle-meme, puisque la charge est payee.
     */
    #[Route('/settle/{wallet}/{charge}', name: 'back_recurring_settle', methods: 'POST')]
    public function settle(Wallet $wallet, string $charge): RedirectResponse
    {
        $request = $this->coreLocator->request();
        if (!$this->isCsrfTokenValid('recurring-settle-'.$charge, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $now = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
        $pending = $this->forecast->pending($wallet, (int) $now->format('Y'), (int) $now->format('n'));
        $line = null;
        foreach ($pending as $row) {
            if ($row['key'] === $charge) {
                $line = $row;
                break;
            }
        }

        if (null === $line) {
            $this->flash('error', "Cette echeance n'est plus attendue ce mois-ci.");

            return $this->redirectToRoute('back_operation_index', ['wallet' => $wallet->getId()]);
        }

        $entityManager = $this->coreLocator->em();
        $expenses = $entityManager->getRepository(OperationType::class)->findOneBy(['slug' => 'expenses']);
        if (!$expenses instanceof OperationType) {
            $this->flash('error', "Le type d'operation « depenses » est introuvable.");

            return $this->redirectToRoute('back_operation_index', ['wallet' => $wallet->getId()]);
        }

        $subCategory = $line['categorySlug']
            ? $entityManager->getRepository(SubCategory::class)->findOneBy(['slug' => $line['categorySlug']])
            : null;
        $subCategory ??= $entityManager->getRepository(SubCategory::class)->findOneBy(['slug' => 'expenses-other']);

        $operation = (new Operation())
            ->setWallet($wallet)
            ->setDate(new \DateTime($now->format('Y-m-d').' 00:00:00'))
            ->setAmount((float) $line['amount'])
            ->setPointed(true)
            ->setOperationType($expenses);
        $operation->setAdminName((string) $line['name']);
        if ($subCategory instanceof SubCategory) {
            $operation->setSubCategory($subCategory);
        }
        if (null !== $line['outsiderId']) {
            $outsider = $entityManager->getRepository(Outsider::class)->find($line['outsiderId']);
            if ($outsider instanceof Outsider) {
                $operation->setOutsider($outsider);
            }
        }

        $entityManager->persist($operation);
        $entityManager->flush();

        $this->flash('success', "L'echeance est enregistree comme payee aujourd'hui.");

        return $this->redirectToRoute('back_operation_index', ['wallet' => $wallet->getId()]);
    }

    /**
     * Ajout d'une charge que l'historique ne connaît pas.
     *
     * Un prélèvement qui démarre ce mois-ci n'a pas de passé : il ne sera
     * détecté qu'au bout de sept mois. L'annoncer demande de le saisir.
     */
    #[Route('/add/{wallet}', name: 'back_recurring_add', methods: 'POST')]
    public function add(Wallet $wallet): RedirectResponse
    {
        $request = $this->coreLocator->request();
        if (!$this->isCsrfTokenValid('recurring-add-'.$wallet->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        /*
         * Le tiers se donne comme sur une operation : dans la liste, ou par le
         * champ libre qui en cree un. L'intitule de la charge est celui du
         * tiers — c'est lui qui la nomme, et c'est sur lui que l'historique la
         * reconnaitra le jour ou elle y figurera.
         */
        $outsider = $this->resolveOutsider(
            $request->request->get('outsider'),
            trim((string) $request->request->get('name'))
        );
        if (!$outsider instanceof Outsider) {
            $this->flash('error', 'Sélectionnez un tiers existant ou saisissez un nouveau tiers.');

            return $this->redirectToRoute('back_recurring_index', ['wallet' => $wallet->getId()]);
        }

        $name = (string) $outsider->getAdminName();
        $slug = Urlizer::urlize($name);
        if (isset($this->chargeRepository->settingsFor($wallet)[$slug])) {
            $this->flash('error', 'Une charge porte déjà cet intitulé sur ce compte.');

            return $this->redirectToRoute('back_recurring_index', ['wallet' => $wallet->getId()]);
        }

        $subCategory = $this->coreLocator->em()->getRepository(SubCategory::class)
            ->find((int) $request->request->get('subCategory'));
        $frequency = (string) $request->request->get('frequency');
        $yearly = RecurringCharge::YEARLY === $frequency;

        $charge = (new RecurringCharge())
            ->setWallet($wallet)
            ->setAdminName($name)
            ->setSlug($slug)
            ->setManual(true)
            ->setActive(true)
            ->setOutsider($outsider)
            ->setSubCategory($subCategory instanceof SubCategory ? $subCategory : null)
            ->setCategory($subCategory instanceof SubCategory ? $subCategory->getAdminName() : null)
            ->setAmount($this->decimal($request->request->get('amount')))
            ->setDueDay($this->integer($request->request->get('day'), 1, 31))
            ->setFrequency($frequency)
            ->setDueMonth($yearly ? $this->integer($request->request->get('dueMonth'), 1, 12) : null);
        $this->applySchedule($charge, $request->request->all());

        $this->coreLocator->em()->persist($charge);
        $this->coreLocator->em()->flush();
        $this->flash('success', 'La charge est ajoutée.');

        return $this->redirectToRoute('back_recurring_index', ['wallet' => $wallet->getId()]);
    }

    /**
     * Tiers choisi dans la liste, ou créé depuis le champ libre.
     *
     * Un tiers déjà connu n'est jamais dupliqué : c'est son slug qui en
     * décide, pas son orthographe.
     */
    private function resolveOutsider(mixed $id, string $name): ?Outsider
    {
        $repository = $this->coreLocator->em()->getRepository(Outsider::class);

        if (is_numeric($id)) {
            $outsider = $repository->find((int) $id);
            if ($outsider instanceof Outsider) {
                return $outsider;
            }
        }

        if ('' === $name) {
            return null;
        }

        $slug = (string) Urlizer::urlize($name);
        $outsider = $repository->findOneBy(['slug' => $slug]);
        if ($outsider instanceof Outsider) {
            return $outsider;
        }

        $outsider = (new Outsider())
            ->setAdminName($name)
            ->setSlug($slug)
            ->setPosition(count($repository->findAll()) + 1);
        $outsider->setCreatedBy($this->coreLocator->user());
        $this->coreLocator->em()->persist($outsider);

        return $outsider;
    }

    /**
     * Échéancier à durée déterminée : nombre d'échéances et mois de départ.
     *
     * Hors échéancier, les deux champs sont remis à zéro : une charge repassée
     * en mensuel garderait sinon un terme qui ne la concerne plus, et
     * s'éteindrait toute seule au bout de quatre mois.
     *
     * @param array<string, mixed> $row
     */
    private function applySchedule(RecurringCharge $charge, array $row): void
    {
        if (!$charge->hasInstalments()) {
            $charge->setInstalments(null)->setStartsOn(null);

            return;
        }

        $month = $this->integer($row['startMonth'] ?? null, 1, 12) ?? (int) date('n');
        $year = $this->integer($row['startYear'] ?? null, 2000, 2100) ?? (int) date('Y');

        $charge
            ->setInstalments($this->integer($row['instalments'] ?? null, 2, 60))
            ->setStartsOn(new \DateTime(sprintf('%04d-%02d-01 00:00:00', $year, $month)));
    }

    /**
     * Suppression d'un réglage.
     *
     * Une charge saisie disparaît pour de bon. Une charge détectée, elle,
     * revient à ce que dit l'historique : son réglage est effacé, pas elle.
     * Le message le dit, sans quoi l'utilisateur la croirait supprimée.
     */
    #[Route('/delete/{wallet}/{charge}', name: 'back_recurring_delete', methods: 'POST')]
    public function remove(Wallet $wallet, string $charge): RedirectResponse
    {
        $request = $this->coreLocator->request();
        if (!$this->isCsrfTokenValid('recurring-delete-'.$charge, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $setting = $this->chargeRepository->settingsFor($wallet)[$charge] ?? null;
        if (!$setting instanceof RecurringCharge) {
            $this->flash('error', "Cette charge n'a aucun réglage à effacer.");

            return $this->redirectToRoute('back_recurring_index', ['wallet' => $wallet->getId()]);
        }

        $wasManual = $setting->isManual();
        $this->coreLocator->em()->remove($setting);
        $this->coreLocator->em()->flush();
        $this->flash('success', $wasManual
            ? 'La charge est supprimée.'
            : "Le réglage est effacé : la charge revient à ce que dit l'historique.");

        return $this->redirectToRoute('back_recurring_index', ['wallet' => $wallet->getId()]);
    }

    private function flash(string $type, string $message): void
    {
        $this->coreLocator->request()->getSession()->getFlashBag()->add(
            $type,
            $this->coreLocator->translator()->trans($message, [], 'back')
        );
    }

    /**
     * Montant saisi, ou null pour revenir à l'estimation.
     */
    private function decimal(mixed $value): ?float
    {
        $value = str_replace(',', '.', trim((string) $value));

        return '' === $value || !is_numeric($value) ? null : round((float) $value, 2);
    }

    /**
     * Entier borné, ou null hors bornes : une échéance au 45 du mois n'existe pas.
     */
    private function integer(mixed $value, int $min, int $max): ?int
    {
        $value = trim((string) $value);
        if ('' === $value || !ctype_digit($value)) {
            return null;
        }
        $value = (int) $value;

        return $value >= $min && $value <= $max ? $value : null;
    }

    /**
     * To set breadcrumb.
     */
    protected function breadcrumb(array $items = []): void
    {
        $items[$this->coreLocator->translator()->trans('Mes comptes', [], 'breadcrumb')] = 'back_wallet_index';
        $items[$this->coreLocator->translator()->trans('Opérations programmées', [], 'breadcrumb')] = 'back_recurring_index';

        parent::breadcrumb($items);
    }
}
