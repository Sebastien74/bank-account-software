<?php

declare(strict_types=1);

namespace App\Controller\Back\Wallet;

use App\Controller\BaseController;
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

        return parent::index($paginator);
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
        $this->pageTitle = $this->coreLocator->translator()->trans('Statistiques', [], 'back');
        $this->template = 'back/pages/wallet_statistics.html.twig';

        $operationRepository = $this->coreLocator->em()->getRepository(\App\Entity\Wallet\Operation::class);
        $request = $this->coreLocator->request();

        $now = new \DateTime();
        $selectedYear = $request->query->get('year', $now->format('Y'));
        $selectedMonth = $request->query->get('month', $now->format('m'));

        $startYear = \DateTime::createFromFormat('Y-m-d H:i:s', "$selectedYear-01-01 00:00:00");
        $endYear = \DateTime::createFromFormat('Y-m-d H:i:s', "$selectedYear-12-31 23:59:59");

        $startMonth = \DateTime::createFromFormat('Y-m-d H:i:s', "$selectedYear-$selectedMonth-01 00:00:00");
        $endMonth = (clone $startMonth)->modify('last day of this month 23:59:59');

        $yearStatsRaw = $operationRepository->getStats($wallet, $startYear, $endYear);
        $monthStatsRaw = $operationRepository->getStats($wallet, $startMonth, $endMonth);

        $availableYears = $operationRepository->getAvailableYears($wallet);
        if (!in_array($now->format('Y'), $availableYears)) {
            $availableYears[] = $now->format('Y');
            rsort($availableYears);
        }

        return $this->render($this->template, $this->defaultArguments() + [
            'wallet' => $wallet,
            'yearStats' => $this->formatStats($yearStatsRaw),
            'monthStats' => $this->formatStats($monthStatsRaw),
            'selectedYear' => $selectedYear,
            'selectedMonth' => $selectedMonth,
            'years' => $availableYears,
            'months' => [
                '01' => 'Janvier', '02' => 'Février', '03' => 'Mars', '04' => 'Avril',
                '05' => 'Mai', '06' => 'Juin', '07' => 'Juillet', '08' => 'Août',
                '09' => 'Septembre', '10' => 'Octobre', '11' => 'Novembre', '12' => 'Décembre'
            ],
        ]);
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
            $stats[$catId]['total'] += $row['total'];
            $stats[$catId]['subCategories'][] = [
                'name' => $row['subCategoryName'],
                'total' => $row['total'],
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
