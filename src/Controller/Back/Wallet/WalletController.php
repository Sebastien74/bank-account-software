<?php

declare(strict_types=1);

namespace App\Controller\Back\Wallet;

use App\Controller\Back\BaseController;
use App\Entity\Wallet\Operation;
use App\Entity\Wallet\SubCategory;
use App\Entity\Wallet\Wallet;
use App\Form\Manager\GlobalManagerInterface;
use App\Form\Type\Wallet\WalletType;
use App\Service\AdminLocatorInterface;
use App\Service\CoreLocatorInterface;
use App\Service\Wallet\Statistics\StatisticsBuilder;
use App\Service\Wallet\Statistics\SubCategoryDetailBuilder;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * WalletController.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_ADMIN')]
#[Route('/back-%security_token%/wallets', schemes: '%protocol%')]
class WalletController extends BaseController
{
    protected ?string $pageIcon = 'wallet';

    protected ?string $classname = Wallet::class;
    protected ?string $formType = WalletType::class;

    /**
     * WalletController constructor.
     */
    public function __construct(
        protected CoreLocatorInterface $coreLocator,
        protected AdminLocatorInterface $adminLocator,
        protected GlobalManagerInterface $globalFormManager,
        protected mixed $formManager,
        private readonly StatisticsBuilder $statisticsBuilder,
        private readonly SubCategoryDetailBuilder $subCategoryDetailBuilder,
    ) {
        parent::__construct($coreLocator, $adminLocator, $globalFormManager, $formManager);
    }

    /**
     * Wallet index.
     */
    #[Route('/index', name: 'back_wallet_index', methods: 'GET|POST')]
    public function index(PaginatorInterface $paginator): Response
    {
        $this->pageTitle = $this->coreLocator->translator()->trans('Gestion des comptes', [], 'back');
        $this->addBtnLabel = $this->coreLocator->translator()->trans('Ajouter un compte', [], 'back');
        $this->template = 'back/pages/wallets.html.twig';

        $this->entities = $this->coreLocator->em()->getRepository(Wallet::class)->findBy([], ['position' => 'ASC']);
        $this->forceEntities = true;
        $this->arguments['walletsStats'] = $this->walletsStats($this->entities);

        return parent::index($paginator);
    }

    /**
     * Agrégats affichés sur les cartes de compte.
     *
     * Calculés en base plutôt qu'en parcourant la collection d'opérations, qui
     * compte plusieurs milliers de lignes par compte.
     *
     * @param Wallet[] $wallets
     */
    private function walletsStats(array $wallets): array
    {
        $repository = $this->coreLocator->em()->getRepository(\App\Entity\Wallet\Operation::class);
        $now = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
        $currentDay = (int) $now->format('d');
        $remainingDays = (int) $now->format('t') - $currentDay + 1;
        $stats = [];

        foreach ($wallets as $wallet) {
            $balance = $repository->sumBalance($wallet);
            $totals = $repository->currentMonthTotals($wallet);
            $stats[$wallet->getId()] = [
                'balance' => round($balance, 2),
                'dailyAverageExpenses' => $currentDay > 0 ? round($totals['expenses'] / $currentDay, 2) : 0.0,
                'remainingDailyBudget' => $remainingDays > 0 ? round($balance / $remainingDays, 2) : 0.0,
            ];
        }

        return $stats;
    }

    /**
     * Wallet edit.
     */
    #[Route('/edit/{wallet}', name: 'back_wallet_edit', methods: 'GET|POST')]
    public function edit(): Response
    {
        $this->pageTitle = $this->coreLocator->translator()->trans('Gestion des comptes', [], 'back');

        return parent::edit();
    }

    /**
     * Wallet delete.
     */
    #[Route('/delete/{wallet}', name: 'back_wallet_delete', methods: 'GET')]
    public function delete(Wallet $wallet): RedirectResponse
    {
        return $this->redirect($this->globalFormManager->delete($wallet));
    }
    /**
     * Tableau de bord statistique d'un compte.
     */
    #[Route('/statistics/{wallet}', name: 'back_operation_statistics', methods: 'GET')]
    public function statistics(Wallet $wallet): Response
    {
        $this->pageTitle = $wallet->getAdminName().' - '.$this->coreLocator->translator()->trans('Statistiques', [], 'back');
        $this->pageIcon = 'chart-line';
        $request = $this->coreLocator->request();
        $now = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris'));

        // Les paramètres alimentent des constructions de dates : ils sont bornés
        // ici, une valeur hors plage retombant sur la période courante.
        // getInt() rejetterait « 06 », que porte pourtant tout lien historique.
        // Rien n'est imposé quand la requête ne demande rien : le service se cale
        // alors sur le dernier mois porteur d'écritures.
        $year = $this->positiveInt($request->query->get('year'));
        $month = $this->positiveInt($request->query->get('month'));
        $year = $year >= 1970 && $year <= 2200 ? $year : null;
        $month = $month >= 1 && $month <= 12 ? $month : null;

        $this->breadcrumb();

        return $this->render('back/pages/wallet-statistics.html.twig', $this->coreArguments() + [
            'pageTitle' => $this->pageTitle,
            'pageIcon' => $this->pageIcon,
            'breadcrumb' => $this->breadcrumb,
            'stats' => $this->statisticsBuilder->build($wallet, $year, $month),
        ]);
    }

    /**
     * Détail d'un poste de dépense : opérations et bénéficiaires.
     */
    #[Route('/statistics/{wallet}/poste/{subCategory}', name: 'back_wallet_sub_category_detail', methods: 'GET')]
    public function subCategoryDetail(Wallet $wallet, SubCategory $subCategory): Response
    {
        $request = $this->coreLocator->request();
        $now = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris'));

        $year = $this->positiveInt($request->query->get('year'));
        $month = $this->positiveInt($request->query->get('month'));
        $year = $year >= 1970 && $year <= 2200 ? $year : (int) $now->format('Y');
        $month = $month >= 1 && $month <= 12 ? $month : (int) $now->format('n');
        $scope = 'year' === $request->query->get('scope') ? 'year' : 'month';

        $this->pageTitle = $subCategory->getAdminName();
        $this->pageIcon = 'chart-bar';
        $this->breadcrumb();

        return $this->render('back/pages/wallet-sub-category.html.twig', $this->coreArguments() + [
            'pageTitle' => $this->pageTitle,
            'pageIcon' => $this->pageIcon,
            'breadcrumb' => $this->breadcrumb,
            'detail' => $this->subCategoryDetailBuilder->build($wallet, $subCategory, $year, $month, $scope),
        ]);
    }

    /**
     * Lit un entier positif depuis la requête, sans lever d'exception.
     *
     * Toute valeur non numérique retourne zéro, à charge pour l'appelant de lui
     * substituer sa valeur par défaut.
     */
    private function positiveInt(mixed $value): int
    {
        return is_string($value) && ctype_digit($value) ? (int) $value : 0;
    }

    protected function breadcrumb(array $items = []): void
    {
        $translator = $this->coreLocator->translator();
        $items[$translator->trans('Mes comptes', [], 'breadcrumb')] = 'back_wallet_index';

        $walletId = $this->coreLocator->request()->get('wallet');
        if ($walletId) {
            $routeName = $this->coreLocator->request()->get('_route');
            if ($routeName === 'back_wallet_edit') {
                $items[$translator->trans('Édition', [], 'back_breadcrumb')] = 'back_wallet_edit';
            } elseif ($routeName === 'back_operation_statistics') {
                $items[$translator->trans('Statistiques', [], 'back_breadcrumb')] = 'back_operation_statistics';
            }
        }

        parent::breadcrumb($items);
    }
}
