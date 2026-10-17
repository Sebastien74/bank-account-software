<?php

declare(strict_types=1);

namespace App\Controller\Back\Wallet;

use App\Controller\Back\BaseController;
use App\Entity\Wallet\Budget;
use App\Form\Manager\GlobalManagerInterface;
use App\Service\AdminLocatorInterface;
use App\Service\CoreLocatorInterface;
use App\Service\Wallet\BudgetPlanner;
use App\Entity\Wallet\Operation;
use App\Entity\Wallet\Wallet;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * DashboardController.
 *
 * Vue d'ensemble d'un compte, mois par mois. Elle commence par les enveloppes
 * confrontees a la depense reelle ; d'autres blocs viendront s'y ajouter, et
 * c'est pour cela qu'elle ne porte pas le nom des budgets.
 *
 * @author Sebastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_ADMIN')]
#[Route('/back-%security_token%/dashboard', schemes: '%protocol%')]
class DashboardController extends BaseController
{
    protected ?string $pageIcon = 'gauge';

    protected ?string $classname = Budget::class;

    /**
     * DashboardController constructor.
     */
    public function __construct(
        protected CoreLocatorInterface $coreLocator,
        protected AdminLocatorInterface $adminLocator,
        protected GlobalManagerInterface $globalFormManager,
        protected mixed $formManager,
        private readonly BudgetPlanner $planner,
    ) {
        parent::__construct($coreLocator, $adminLocator, $globalFormManager, $formManager);
    }

    /**
     * Tableau de bord des enveloppes, mois par mois.
     *
     * L'ecran confronte chaque enveloppe a la depense reelle du mois. Il
     * affiche aussi les postes depenses sans enveloppe : sans eux, un plan
     * paraitrait tenu alors que l'argent est sorti ailleurs.
     */
    #[Route('/{wallet}/{year}/{month}', name: 'back_dashboard', methods: 'GET', defaults: ['wallet' => null, 'year' => null, 'month' => null])]
    public function dashboard(?Wallet $wallet = null, ?int $year = null, ?int $month = null): Response
    {
        // Depuis le rail, aucun compte n'est nomme : on prend le premier
        // compte courant, celui que l'ecran des comptes montre en tete.
        $wallet ??= $this->coreLocator->em()->getRepository(Wallet::class)
            ->findOneBy(['type' => Wallet::CURRENT], ['position' => 'ASC']);
        if (!$wallet instanceof Wallet) {
            return $this->redirectToRoute('back_wallet_index');
        }

        $now = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris'));
        $year = $year ?: (int) $now->format('Y');
        $month = $month ?: (int) $now->format('n');
        $period = new \DateTimeImmutable(sprintf('%04d-%02d-01 00:00:00', $year, $month), new \DateTimeZone('Europe/Paris'));

        $operations = $this->coreLocator->em()->getRepository(Operation::class);
        $spent = $operations->monthlyTotalsBySubCategory($wallet, $year, $month);
        $budgets = $this->coreLocator->em()->getRepository(Budget::class)->forWallet($wallet);

        $lines = [];
        $totalBudget = 0.0;
        $totalSpent = 0.0;
        foreach ($budgets as $budget) {
            $amount = $budget->getAmount();

            // Une enveloppe peut couvrir plusieurs postes : la depense a
            // comparer est leur somme, pas celle d'un seul.
            $used = 0.0;
            $postes = [];
            foreach ($budget->getSubCategories() as $subCategory) {
                $subCategoryId = (int) $subCategory->getId();
                $used += (float) ($spent[$subCategoryId]['total'] ?? 0.0);
                $postes[] = (string) $subCategory->getAdminName();
                unset($spent[$subCategoryId]);
            }

            if ($budget->isActive()) {
                $totalBudget += $amount;
                $totalSpent += $used;
            }

            $lines[] = [
                'budget' => $budget,
                'name' => (string) $budget->getAdminName(),
                'postes' => $postes,
                'amount' => round($amount, 2),
                'spent' => round($used, 2),
                'remaining' => round($amount - $used, 2),
                // Au-dela de 100 %, la barre reste pleine : c'est le chiffre
                // qui dit le depassement, pas une barre qui deborderait.
                'ratio' => $amount > 0 ? min(100, (int) round($used / $amount * 100)) : ($used > 0 ? 100 : 0),
                'over' => $used > $amount,
                'saving' => $budget->isSaving(),
            ];
        }

        // Ce qui reste dans $spent n'a pas d'enveloppe : on le montre a part.
        $unbudgeted = [];
        foreach ($spent as $row) {
            $unbudgeted[] = ['name' => $row['name'], 'spent' => round($row['total'], 2)];
            $totalSpent += $row['total'];
        }
        usort($unbudgeted, static fn (array $a, array $b): int => $b['spent'] <=> $a['spent']);

        /*
         * Deux revenus, parce qu'ils ne repondent pas a la meme question.
         * Celui du mois est encaisse a ce jour : le 3 du mois, il ne vaut
         * presque rien et comparer les enveloppes a lui ferait croire a un
         * plan intenable. Le revenu de reference, lui, est la mediane des
         * douze derniers mois : c'est sur lui que le plan a ete bati, c'est a
         * lui qu'il doit etre confronte.
         */
        $income = array_sum(array_column($operations->monthlyTotalsBySubCategory($wallet, $year, $month, 'incomes'), 'total'));
        $reference = $this->planner->plan($wallet)['income'];

        $this->pageTitle = $this->coreLocator->translator()->trans('Tableau de bord', [], 'back');
        $this->breadcrumb();
        $this->arguments['pageTitle'] = $this->pageTitle;
        $this->arguments['pageIcon'] = $this->pageIcon;
        $this->arguments['wallet'] = $wallet;
        $this->arguments['period'] = $period;
        $this->arguments['previous'] = $period->modify('-1 month');
        $this->arguments['next'] = $period->modify('+1 month');
        $this->arguments['lines'] = $lines;
        $this->arguments['unbudgeted'] = $unbudgeted;
        $this->arguments['totalBudget'] = round($totalBudget, 2);
        $this->arguments['totalSpent'] = round($totalSpent, 2);
        $this->arguments['income'] = round((float) $income, 2);
        $this->arguments['reference'] = $reference;
        $this->arguments['headroom'] = round($reference - $totalBudget, 2);
        $this->arguments['isCurrentMonth'] = $now->format('Y-n') === $period->format('Y-n');

        return $this->render('back/pages/dashboard.html.twig', $this->coreArguments());
    }

    /**
     * To set breadcrumb.
     */
    protected function breadcrumb(array $items = []): void
    {
        $items[$this->coreLocator->translator()->trans('Tableau de bord', [], 'breadcrumb')] = 'back_dashboard';

        parent::breadcrumb($items);
    }
}
