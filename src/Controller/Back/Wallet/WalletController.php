<?php

declare(strict_types=1);

namespace App\Controller\Back\Wallet;

use App\Controller\Back\BaseController;
use App\Entity\Wallet\Wallet;
use App\Form\Type\Wallet\WalletType;
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
     * Wallet statistics.
     */
    #[Route('/statistics/{wallet}', name: 'back_operation_statistics', methods: 'GET')]
    public function statistics(Wallet $wallet): Response
    {
        $this->pageTitle = $wallet->getAdminName().' - '.$this->coreLocator->translator()->trans('Statistiques', [], 'back');

        $operationRepository = $this->coreLocator->em()->getRepository(\App\Entity\Wallet\Operation::class);
        $request = $this->coreLocator->request();

        $now = new \DateTime();
        // Les deux valeurs alimentent des chaînes de date : elles sont validées,
        // sans quoi createFromFormat retourne false et la vue échoue en erreur 500.
        $selectedYear = (string) $request->query->get('year', $now->format('Y'));
        $selectedMonth = (string) $request->query->get('month', $now->format('m'));
        if (!preg_match('/^\d{4}$/', $selectedYear)) {
            $selectedYear = $now->format('Y');
        }
        if (!preg_match('/^(0[1-9]|1[0-2])$/', $selectedMonth)) {
            $selectedMonth = $now->format('m');
        }

        $startYear = \DateTime::createFromFormat('Y-m-d H:i:s', "$selectedYear-01-01 00:00:00");
        $endYear = \DateTime::createFromFormat('Y-m-d H:i:s', "$selectedYear-12-31 23:59:59");

        $startMonth = \DateTime::createFromFormat('Y-m-d H:i:s', "$selectedYear-$selectedMonth-01 00:00:00");
        $endMonth = (clone $startMonth)->modify('last day of this month 23:59:59');

        $yearStatsRaw = $operationRepository->getStats($wallet, $startYear, $endYear);
        $monthStatsRaw = $operationRepository->getStats($wallet, $startMonth, $endMonth);

        // Calculate comparison periods for trends
        $currentDay = (int) $now->format('d');
        $selectedMonthMaxDay = (int) $endMonth->format('d');
        $comparisonDay = min($currentDay, $selectedMonthMaxDay);

        $isCurrentMonthAndYear = $selectedYear === $now->format('Y') && $selectedMonth === $now->format('m');

        $endCurrentPeriod = \DateTime::createFromFormat('Y-m-d H:i:s', "$selectedYear-$selectedMonth-$comparisonDay 23:59:59");
        $startPrevPeriod = \DateTime::createFromFormat('Y-m-d H:i:s', ($selectedYear - 1) . "-$selectedMonth-01 00:00:00");
        $endPrevPeriod = \DateTime::createFromFormat('Y-m-d H:i:s', ($selectedYear - 1) . "-$selectedMonth-$comparisonDay 23:59:59");

        $currentPeriodStatsRaw = $operationRepository->getStats($wallet, $startMonth, $endCurrentPeriod);
        $prevPeriodStatsRaw = $operationRepository->getStats($wallet, $startPrevPeriod, $endPrevPeriod);

        $monthStats = $this->formatStats($monthStatsRaw);
        $hasPrevMonthOperations = $operationRepository->hasOperations($wallet, $startPrevPeriod, $endPrevPeriod);

        if ($hasPrevMonthOperations) {
            $this->computeTrends($monthStats, $currentPeriodStatsRaw, $prevPeriodStatsRaw);
        }

        // Calculate comparison periods for annual trends
        $isCurrentYear = $selectedYear === $now->format('Y');
        $comparisonYearDay = $isCurrentYear ? $currentDay : 31;
        $comparisonYearMonth = $isCurrentYear ? (int) $now->format('m') : 12;

        $endCurrentYearPeriod = \DateTime::createFromFormat('Y-m-d H:i:s', "$selectedYear-$comparisonYearMonth-$comparisonYearDay 23:59:59");
        $startPrevYearPeriod = \DateTime::createFromFormat('Y-m-d H:i:s', ($selectedYear - 1) . "-01-01 00:00:00");
        $endPrevYearPeriod = \DateTime::createFromFormat('Y-m-d H:i:s', ($selectedYear - 1) . "-$comparisonYearMonth-$comparisonYearDay 23:59:59");

        $hasPrevYearOperations = $operationRepository->hasOperations($wallet, $startPrevYearPeriod, $endPrevYearPeriod);

        $comparisonYearStartMonth = 1;
        $comparisonYearStartDay = 1;
        $excludedMonths = [];
        if ($hasPrevYearOperations) {
            $firstOpPrevYear = $operationRepository->getFirstOperationDateInYear($wallet, (int) $selectedYear - 1);
            if ($firstOpPrevYear) {
                $comparisonYearStartMonth = (int) $firstOpPrevYear->format('m');
                $comparisonYearStartDay = (int) $firstOpPrevYear->format('d');
                $startYear = \DateTime::createFromFormat('Y-m-d H:i:s', "$selectedYear-" . $firstOpPrevYear->format('m-d') . " 00:00:00");
                $startPrevYearPeriod = \DateTime::createFromFormat('Y-m-d H:i:s', ($selectedYear - 1) . "-" . $firstOpPrevYear->format('m-d') . " 00:00:00");

                for ($m = 1; $m < $comparisonYearStartMonth; $m++) {
                    $excludedMonths[] = sprintf('%02d', $m);
                }
            }
        }

        // We use the same start date for both main stats and trend comparison to be consistent
        $yearStatsRaw = $operationRepository->getStats($wallet, $startYear, $endYear);
        $yearStats = $this->formatStats($yearStatsRaw);

        if ($hasPrevYearOperations) {
            $currentYearPeriodStatsRaw = $operationRepository->getStats($wallet, $startYear, $endCurrentYearPeriod);
            $prevYearPeriodStatsRaw = $operationRepository->getStats($wallet, $startPrevYearPeriod, $endPrevYearPeriod);
            $this->computeTrends($yearStats, $currentYearPeriodStatsRaw, $prevYearPeriodStatsRaw);
        }

        $availableYears = $operationRepository->getAvailableYears($wallet);
        if (!in_array($now->format('Y'), $availableYears)) {
            $availableYears[] = $now->format('Y');
            rsort($availableYears);
        }

        return $this->render('back/pages/wallet-statistics.html.twig', $this->coreArguments() + [
            'pageTitle' => $this->pageTitle,
            'pageIcon' => 'chart-line',
            'wallet' => $wallet,
            'yearStats' => $yearStats,
            'monthStats' => $monthStats,
            'selectedYear' => $selectedYear,
            'selectedMonth' => $selectedMonth,
            'endMonth' => $endMonth,
            'isCurrentMonthAndYear' => $isCurrentMonthAndYear,
            'comparisonDay' => $comparisonDay,
            'comparisonYearDay' => $comparisonYearDay,
            'comparisonYearMonth' => $comparisonYearMonth,
            'comparisonYearStartDay' => $comparisonYearStartDay ?? 1,
            'comparisonYearStartMonth' => $comparisonYearStartMonth,
            'excludedMonths' => $excludedMonths,
            'years' => $availableYears,
            'months' => [
                '01' => 'Janvier', '02' => 'Février', '03' => 'Mars', '04' => 'Avril',
                '05' => 'Mai', '06' => 'Juin', '07' => 'Juillet', '08' => 'Août',
                '09' => 'Septembre', '10' => 'Octobre', '11' => 'Novembre', '12' => 'Décembre'
            ],
        ]);
    }

    private function computeTrends(array &$mainStats, array $currentPeriodRaw, array $prevPeriodRaw): void
    {
        $currentPeriod = $this->formatStats($currentPeriodRaw);
        $prevPeriod = $this->formatStats($prevPeriodRaw);

        foreach ($mainStats as $catId => &$category) {
            $currCatTotal = $currentPeriod[$catId]['total'] ?? 0;
            $prevCatTotal = $prevPeriod[$catId]['total'] ?? 0;
            $category['prevTotal'] = $prevCatTotal;
            $category['trend'] = $this->calculateTrend($currCatTotal, $prevCatTotal);

            foreach ($category['subCategories'] as $subCatId => &$subCategory) {
                $currSubTotal = $currentPeriod[$catId]['subCategories'][$subCatId]['total'] ?? 0;
                $prevSubTotal = $prevPeriod[$catId]['subCategories'][$subCatId]['total'] ?? 0;
                $subCategory['prevTotal'] = $prevSubTotal;
                $subCategory['trend'] = $this->calculateTrend($currSubTotal, $prevSubTotal);
            }
        }
    }

    private function calculateTrend(float $current, float $previous): ?array
    {
        if ($previous == 0) {
            return $current > 0 ? ['direction' => 'up', 'percentage' => 100, 'color' => 'danger'] : null;
        }

        $diff = round($current, 2) - round($previous, 2);
        $percentage = ($diff / $previous) * 100;

        if (abs($diff) < 0.01) {
            return ['direction' => 'stable', 'percentage' => 0, 'color' => 'secondary'];
        }

        return [
            'direction' => $diff > 0 ? 'up' : 'down',
            'percentage' => abs($percentage),
            'color' => $diff > 0 ? 'danger' : 'success',
        ];
    }

    private function formatStats(array $rawStats): array
    {
        $stats = [];
        foreach ($rawStats as $row) {
            $catId = $row['categoryId'];
            if (!isset($stats[$catId])) {
                $stats[$catId] = [
                    'name' => $row['categoryName'],
                    'total' => 0,
                    'subCategories' => [],
                ];
            }
            $stats[$catId]['total'] += (float) $row['total'];
            $subCatId = $row['subCategoryId'];
            $stats[$catId]['subCategories'][$subCatId] = [
                'name' => $row['subCategoryName'],
                'total' => (float) $row['total'],
            ];
        }

        return $stats;
    }

    /**
     * To set breadcrumb.
     */
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
