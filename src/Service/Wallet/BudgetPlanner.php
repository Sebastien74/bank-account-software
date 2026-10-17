<?php

declare(strict_types=1);

namespace App\Service\Wallet;

use App\Entity\Wallet\Budget;
use App\Entity\Wallet\Category;
use App\Entity\Wallet\Operation;
use App\Entity\Wallet\SubCategory;
use App\Entity\Wallet\Wallet;
use Doctrine\ORM\EntityManagerInterface;

/**
 * BudgetPlanner.
 *
 * Propose une enveloppe mensuelle par poste de dépenses, tenue dans les
 * revenus du compte.
 *
 * La méthode est volontairement explicite, parce qu'un budget est un arbitrage
 * et qu'un arbitrage doit pouvoir se discuter :
 *
 * 1. La référence de revenu est la **médiane** des douze derniers mois, pas la
 *    moyenne : deux mois exceptionnels suffiraient à bâtir un plan qui ne tient
 *    qu'en comptant sur eux.
 * 2. Une marge est retenue sur cette référence, pour que le plan laisse de quoi
 *    absorber un imprévu sans le défaire.
 * 3. Les postes contraints — logement, téléphonie, abonnements, frais
 *    bancaires, alimentation, véhicule — gardent leur dépense observée : les
 *    rogner sur le papier ne les rogne pas en vrai.
 * 4. L'épargne est posée comme une ligne régulière, pas comme un reliquat.
 * 5. Ce qui manque est pris sur les seuls postes où une décision est possible.
 *    Le rapport le dit : l'effort est là, pas dilué partout.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final readonly class BudgetPlanner
{
    /** Fenêtre d'observation, en mois. */
    public const int OBSERVED_MONTHS = 12;

    /**
     * Part du revenu de référence laissée hors enveloppes.
     *
     * Elle est faible, et c'est voulu : la vraie réserve est ailleurs. Le plan
     * est bâti sur le revenu **médian**, qui ignore les mois exceptionnels ;
     * tout ce que ces mois-là apportent au-delà de la médiane est déjà du
     * surplus. Reprendre 5 % par-dessus reviendrait à se serrer deux fois.
     */
    private const float MARGIN_RATE = 0.02;

    /**
     * Mois actifs minimum pour qu'un sous-poste compte comme mensuel.
     *
     * Deux tiers de la fenêtre. En dessous, la dépense n'est pas un rythme :
     * c'est une échéance, qui se provisionne au lieu de se budgéter.
     */
    private const int RECURRING_MONTHS = 8;

    /** Marge plancher, quand 2 % du revenu ne couvrirait rien. */
    private const float MINIMUM_MARGIN = 50.0;

    /** Postes dont la dépense observée est reprise telle quelle. */
    private const array CONSTRAINED = [
        'Frais de logement',
        'Téléphonie',
        'Abonnements et services',
        'Banque et impôts',
        'Vie courante et santé',
        'Transports et déplacements',
    ];

    /** Poste des échéances qui ne tombent pas tous les mois. */
    private const string PROVISION = 'Provisions, échéances annuelles';

    /** Poste d'épargne, posé comme une ligne régulière. */
    private const string SAVINGS = 'Épargne';

    /**
     * Postes écartés du plan, des deux côtés.
     *
     * Les remboursements entre proches ne sont pas une consommation : ils
     * sortent puis reviennent. Les compter crée deux erreurs qui se cumulent
     * au lieu de se compenser — une enveloppe qui mobilise de la capacité
     * pour un flux qui revient, et un revenu de référence gonflé par de
     * l'argent qui n'est pas à nous.
     */
    /**
     * Sous-poste de revenu qui sert de reference.
     *
     * Le salaire seul. Les autres entrees existent, mais on ne peut pas
     * s'engager dessus : les apports personnels vont de 100 a 1 100 EUR selon
     * les mois, et les remboursements dependent d'un tiers. Batir le plan sur
     * un agregat qui les contient, c'est promettre des depenses que seule une
     * bonne annee financerait.
     */
    private const array REFERENCE_INCOME = [
        'incomes-income-remuneration',
    ];

    private const array EXCLUDED = [
        'Remboursements',
    ];

    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    /**
     * Plan proposé pour un compte.
     *
     * Un plafond force un poste a un montant decide, hors calcul : certaines
     * depenses ne se rognent pas au prorata, elles se tranchent. Ce qu'il
     * libere profite aux autres postes arbitrables.
     *
     * @param array<string, float> $caps    intitule de poste => montant impose
     * @param float|null           $reserve ce qu'on veut avoir en poche en fin de mois
     * @param float|null           $savingsFloor ce qu'on veut mettre de cote chaque mois
     *
     * @return array{income: float, margin: float, target: float, observed: float, planned: float, ratio: float, lines: array, irregular: array}
     */
    public function plan(Wallet $wallet, array $caps = [], ?float $reserve = null, ?float $savingsFloor = null): array
    {
        $income = $this->medianMonthlyIncome($wallet);
        /*
         * Le reste a vivre est une decision, pas un residu. Demande, il devient
         * la contrainte : les enveloppes sont calculees pour le laisser
         * intact, et non pour consommer tout le revenu en esperant qu'il en
         * reste. Sans consigne, on garde la marge de precaution habituelle.
         */
        $margin = null !== $reserve
            ? round($reserve, 2)
            : max(self::MINIMUM_MARGIN, round($income * self::MARGIN_RATE, 2));
        $target = round($income - $margin, 2);
        $recurring = $this->recurringByCategory($wallet);
        $observed = $recurring['lines'];

        /*
         * Les échéances qui ne tombent pas tous les mois forment une enveloppe
         * à part. Les laisser hors du plan le ferait paraître tenu alors que
         * l'argent sortira quand même : 410 € par mois d'assurance annuelle,
         * de révision et de vacances ne disparaissent pas parce qu'on ne les
         * budgète pas.
         */
        $provision = $this->roundToTen(array_sum(array_column($recurring['irregular'], 'monthly')));

        /*
         * Les postes contraints sont arrondis avant de calculer ce qui reste,
         * pas après : sur onze lignes, les arrondis cumulés pesaient plus que
         * la marge, et le total manquait la cible qu'il était censé tenir.
         */
        $constrained = 0.0;
        $adjustable = 0.0;
        $capped = 0.0;
        foreach ($observed as $row) {
            if (self::SAVINGS === $row['name']) {
                continue;
            }
            if (isset($caps[$row['name']])) {
                $capped += $caps[$row['name']];
            } elseif (in_array($row['name'], self::CONSTRAINED, true)) {
                $constrained += $this->roundToTen($row['average']);
            } else {
                $adjustable += $row['average'];
            }
        }

        /*
         * Ce qui reste pour les postes arbitrables. Si les contraintes et les
         * provisions dépassent déjà la cible, le ratio tombe à zéro et le
         * rapport le dit : aucun arbitrage sur les loisirs ne sauverait un
         * plan dont le loyer seul excède les revenus.
         */
        /*
         * L'epargne est reservee avant les postes arbitrables, pas apres.
         *
         * Posee en reliquat, elle tombait a dix euros : tout ce qui restait
         * avait deja ete consomme par les loisirs et le tabac. Mettre de cote
         * est une decision, pas un reste ; c'est donc aux postes ou un
         * arbitrage est possible de s'ajuster.
         */
        $savingsPlanned = $this->roundToTen(max(0.0, (float) ($savingsFloor ?? 0.0)));
        $available = max(0.0, $target - $constrained - $capped - $provision - $savingsPlanned);
        $ratio = $adjustable > 0 ? min(1.0, $available / $adjustable) : 1.0;

        $lines = [];
        $planned = 0.0;
        foreach ($observed as $row) {
            if (self::SAVINGS === $row['name']) {
                continue;
            }

            if (isset($caps[$row['name']])) {
                $kind = 'capped';
                $amount = $caps[$row['name']];
            } elseif (in_array($row['name'], self::CONSTRAINED, true)) {
                $kind = 'constrained';
                $amount = $this->roundToTen($row['average']);
            } else {
                $kind = 'adjustable';
                $amount = $this->roundToTen($row['average'] * $ratio);
            }

            $planned += $amount;
            $lines[] = [
                'category' => $row['category'],
                'name' => $row['name'],
                'observed' => round($row['average'], 2),
                'amount' => $amount,
                'kind' => $kind,
            ];
        }

        if ($provision > 0) {
            $planned += $provision;
            $lines[] = [
                'category' => null,
                'subCategoryIds' => array_column($recurring['irregular'], 'subCategoryId'),
                'name' => self::PROVISION,
                'observed' => round(array_sum(array_column($recurring['irregular'], 'monthly')), 2),
                'amount' => $provision,
                'kind' => 'provision',
            ];
        }

        /*
         * Posée comme une charge libre, l'épargne poussait le plan au-delà des
         * revenus : on se serait mis à découvert pour alimenter un livret, ce
         * qui revient à emprunter pour épargner. Elle est donc soit le montant
         * décidé, réservé d'avance, soit ce qui reste une fois tout couvert.
         */
        // Sans consigne, l'epargne reste ce qui reste une fois tout couvert.
        $savings = $savingsPlanned > 0 ? $savingsPlanned : $this->roundToTen(max(0.0, $target - $planned));
        if ($savings > 0) {
            $planned += $savings;
        }
        $savingsCategory = null;
        foreach ($observed as $row) {
            if (self::SAVINGS === $row['name']) {
                $savingsCategory = $row['category'];
                break;
            }
        }
        if ($savingsCategory) {
            $lines[] = [
                'category' => $savingsCategory,
                'name' => self::SAVINGS,
                'observed' => round($this->savingsObserved($observed), 2),
                'amount' => $savings,
                'kind' => 'savings',
            ];
        }

        return [
            'income' => round($income, 2),
            'margin' => $margin,
            'target' => $target,
            'observed' => round(array_sum(array_column($observed, 'average')), 2),
            'planned' => round($planned, 2),
            'ratio' => round($ratio, 4),
            'lines' => $lines,
            // Ce qui ne revient pas tous les mois n'entre pas dans une
            // enveloppe mensuelle, mais doit rester sous les yeux.
            'irregular' => $recurring['irregular'],
        ];
    }

    /**
     * Écrit le plan : une enveloppe par poste, mise à jour si elle existe.
     *
     * Un poste dont les sous-postes ont ete repartis sur plusieurs enveloppes
     * est laisse tel quel : la scission est un arbitrage de l'utilisateur, et
     * le calcul, qui raisonne par categorie, ne saurait pas la refaire.
     *
     * @return array{created: int, updated: int, skipped: list<string>}
     */
    public function apply(Wallet $wallet, array $lines): array
    {
        // Une enveloppe peut couvrir plusieurs postes : elle est retrouvee par
        // chacun d'eux, pour ne pas en creer une seconde sur le meme poste.
        // Une enveloppe est retrouvee par chacun de ses sous-postes : sans cela
        // une seconde serait creee sur un poste deja couvert, et la depense
        // compterait double.
        $existing = [];
        foreach ($this->entityManager->getRepository(Budget::class)->findBy(['wallet' => $wallet]) as $budget) {
            foreach ($budget->getSubCategories() as $subCategory) {
                $existing[(int) $subCategory->getId()] = $budget;
            }
        }

        $report = ['created' => 0, 'updated' => 0, 'skipped' => []];
        $position = count($existing);

        /*
         * La ligne de provision passe en premier, et elle deplace ses
         * sous-postes au lieu de s'ajouter a eux : une revision de vehicule
         * comptee a la fois dans « Transports » et dans les provisions ferait
         * sortir l'argent deux fois du plan.
         */
        usort($lines, static fn (array $a, array $b): int => ('provision' === $b['kind'] ? 1 : 0) <=> ('provision' === $a['kind'] ? 1 : 0));

        foreach ($lines as $line) {
            $subCategories = $this->subCategoriesOf($line);
            if ([] === $subCategories) {
                continue;
            }

            if ('provision' === $line['kind']) {
                $budget = $this->entityManager->getRepository(Budget::class)
                    ->findOneBy(['wallet' => $wallet, 'adminName' => $line['name']]);

                if (!$budget instanceof Budget) {
                    $budget = (new Budget())->setWallet($wallet);
                    $budget->setAdminName($line['name']);
                    $budget->setPosition(++$position);
                    $this->entityManager->persist($budget);
                    ++$report['created'];
                } elseif (abs($budget->getAmount() - $line['amount']) >= 0.005) {
                    ++$report['updated'];
                }

                foreach ($subCategories as $subCategory) {
                    $holder = $existing[(int) $subCategory->getId()] ?? null;
                    if ($holder instanceof Budget && $holder !== $budget) {
                        $holder->removeSubCategory($subCategory);
                    }
                    $budget->addSubCategory($subCategory);
                    $existing[(int) $subCategory->getId()] = $budget;
                }

                $budget->setAmount($line['amount']);
                continue;
            }

            /*
             * L'enveloppe de provisions ne compte pas comme une scission : elle
             * est posee par ce meme calcul, et prend par construction des
             * sous-postes a plusieurs categories. L'y inclure aurait fige tout
             * le plan des le premier passage.
             */
            $matched = [];
            foreach ($subCategories as $subCategory) {
                $found = $existing[(int) $subCategory->getId()] ?? null;
                if ($found instanceof Budget && self::PROVISION !== $found->getAdminName()) {
                    $matched[spl_object_id($found)] = $found;
                }
            }

            if (count($matched) > 1) {
                $report['skipped'][] = $line['name'];
                continue;
            }

            $budget = reset($matched) ?: null;

            if (!$budget instanceof Budget) {
                $budget = (new Budget())->setWallet($wallet);
                $budget->setAdminName($line['name']);
                $budget->setPosition(++$position);
                /*
                 * Une enveloppe proposee couvre tous les sous-postes de sa
                 * categorie. C'est un point de depart : l'ecran permet ensuite
                 * de la scinder, ce que le calcul ne saurait pas decider.
                 */
                foreach ($subCategories as $subCategory) {
                    $budget->addSubCategory($subCategory);
                }
                $this->entityManager->persist($budget);
                ++$report['created'];
            } elseif (abs($budget->getAmount() - $line['amount']) >= 0.005) {
                ++$report['updated'];
            }

            $budget->setAmount($line['amount']);
        }

        $this->entityManager->flush();

        return $report;
    }

    /**
     * Sous-postes couverts par une ligne du plan.
     *
     * Une ligne de catégorie les tire de sa catégorie ; la ligne de provision
     * n'en a pas et porte les siens, puisqu'elle regroupe des sous-postes
     * venus de catégories différentes.
     *
     * @return list<SubCategory>
     */
    private function subCategoriesOf(array $line): array
    {
        if (isset($line['subCategoryIds'])) {
            return $this->entityManager->getRepository(SubCategory::class)
                ->findBy(['id' => $line['subCategoryIds']]);
        }

        $category = $line['category'] ?? null;

        return $category instanceof Category ? $category->getSubCategories()->toArray() : [];
    }

    /**
     * Ce qui a réellement été viré à l'épargne, pour la comparaison.
     *
     * @param list<array{name: string, average: float}> $observed
     */
    private function savingsObserved(array $observed): float
    {
        foreach ($observed as $row) {
            if (self::SAVINGS === $row['name']) {
                return $row['average'];
            }
        }

        return 0.0;
    }

    /**
     * Revenu mensuel médian sur la fenêtre d'observation.
     */
    private function medianMonthlyIncome(Wallet $wallet): float
    {
        $totals = $this->monthlyTotals($wallet, 'incomes');
        if ([] === $totals) {
            return 0.0;
        }

        sort($totals);
        $count = count($totals);
        $middle = intdiv($count, 2);

        return 0 === $count % 2
            ? ($totals[$middle - 1] + $totals[$middle]) / 2
            : $totals[$middle];
    }

    /**
     * Totaux mensuels d'un sens, sur la fenêtre d'observation.
     *
     * @return list<float>
     */
    private function monthlyTotals(Wallet $wallet, string $type): array
    {
        [$start, $end] = $this->window();

        $rows = $this->entityManager->createQueryBuilder()
            ->select('SUBSTRING(o.date, 1, 7) AS period, SUM(o.amount) AS total')
            ->from(Operation::class, 'o')
            ->join('o.operationType', 'ot')
            ->andWhere('o.wallet = :wallet')
            ->join('o.subCategory', 'sc')
            ->join('sc.category', 'c')
            ->andWhere('ot.type = :type')
            ->andWhere('sc.slug IN (:reference)')
            ->setParameter('reference', self::REFERENCE_INCOME)
            ->andWhere('c.adminName NOT IN (:excluded)')
            ->andWhere('o.date >= :start')
            ->andWhere('o.date < :end')
            ->setParameter('wallet', $wallet)
            ->setParameter('type', $type)
            ->setParameter('excluded', self::EXCLUDED)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->groupBy('period')
            ->getQuery()
            ->getArrayResult();

        return array_map(static fn (array $row): float => (float) $row['total'], $rows);
    }

    /**
     * Dépense mensuelle récurrente par catégorie.
     *
     * Une enveloppe mensuelle ne peut se calculer que sur ce qui revient tous
     * les mois. Diviser une charge annuelle par douze produit un chiffre qui
     * n'est juste aucun mois : trop gros onze fois, très insuffisant la
     * douzième. L'exemple du compte est l'entretien du véhicule — une révision
     * de 1 814 €, un jeu de pneus — qui gonflait l'enveloppe « Transports » de
     * 150 € chaque mois pour une dépense qui n'arrive pas.
     *
     * Le tri se fait au niveau du sous-poste, parce que c'est là qu'il a un
     * sens : dans « Transports », l'assurance tombe douze fois sur douze et
     * l'entretien deux fois. Un sous-poste est retenu s'il est présent sur au
     * moins deux tiers de la fenêtre ; l'estimation est alors la médiane de ses
     * douze totaux mensuels, et non leur moyenne, pour qu'un mois exceptionnel
     * ne déplace pas l'enveloppe de tous les autres.
     *
     * Ce qui est écarté n'est pas oublié : le rapport le liste, avec son coût
     * annuel et les mois où il tombe, pour qu'il soit provisionné sciemment.
     *
     * @return array{
     *     lines: list<array{category: Category, name: string, average: float}>,
     *     irregular: list<array{category: string, name: string, yearly: float, monthly: float, months: int}>
     * }
     */
    private function recurringByCategory(Wallet $wallet): array
    {
        [$start, $end] = $this->window();

        $rows = $this->entityManager->createQueryBuilder()
            ->select('c.id AS categoryId, c.adminName AS categoryName, sc.id AS subCategoryId, sc.adminName AS subCategoryName, SUBSTRING(o.date, 1, 7) AS period, SUM(o.amount) AS total')
            ->from(Operation::class, 'o')
            ->join('o.subCategory', 'sc')
            ->join('sc.category', 'c')
            ->join('o.operationType', 'ot')
            ->andWhere('o.wallet = :wallet')
            ->andWhere('ot.type = :expenses')
            ->andWhere('c.adminName NOT IN (:excluded)')
            ->andWhere('o.date >= :start')
            ->andWhere('o.date < :end')
            ->setParameter('wallet', $wallet)
            ->setParameter('expenses', 'expenses')
            ->setParameter('excluded', self::EXCLUDED)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->groupBy('sc.id')
            ->addGroupBy('period')
            ->getQuery()
            ->getArrayResult();

        // Un total par sous-poste et par mois, pour compter les mois actifs.
        $series = [];
        foreach ($rows as $row) {
            $id = (int) $row['subCategoryId'];
            $series[$id] ??= [
                'categoryId' => (int) $row['categoryId'],
                'categoryName' => (string) $row['categoryName'],
                'name' => (string) $row['subCategoryName'],
                'months' => [],
            ];
            $series[$id]['months'][(string) $row['period']] = (float) $row['total'];
        }

        $categories = [];
        foreach ($this->entityManager->getRepository(Category::class)->findBy(['id' => array_column($series, 'categoryId')]) as $category) {
            $categories[(int) $category->getId()] = $category;
        }

        $bases = [];
        $names = [];
        $irregular = [];
        foreach ($series as $id => $row) {
            $active = count($row['months']);
            $yearly = array_sum($row['months']);

            if ($active < self::RECURRING_MONTHS) {
                $irregular[] = [
                    'subCategoryId' => $id,
                    'category' => $row['categoryName'],
                    'name' => $row['name'],
                    'yearly' => round($yearly, 2),
                    'monthly' => round($yearly / self::OBSERVED_MONTHS, 2),
                    'months' => $active,
                ];
                continue;
            }

            // Les mois sans dépense comptent pour zéro dans la médiane : un
            // poste absent deux mois sur douze coûte moins qu'un poste présent
            // partout, et l'enveloppe doit le dire.
            $values = array_values($row['months']);
            for ($i = $active; $i < self::OBSERVED_MONTHS; ++$i) {
                $values[] = 0.0;
            }
            sort($values);
            $count = count($values);
            $middle = intdiv($count, 2);
            $median = 0 === $count % 2
                ? ($values[$middle - 1] + $values[$middle]) / 2
                : $values[$middle];

            $categoryId = $row['categoryId'];
            $bases[$categoryId] = ($bases[$categoryId] ?? 0.0) + $median;
            $names[$categoryId] = $row['categoryName'];
        }

        arsort($bases);

        $lines = [];
        foreach ($bases as $categoryId => $base) {
            $category = $categories[$categoryId] ?? null;
            if (!$category instanceof Category) {
                continue;
            }

            $lines[] = [
                'category' => $category,
                'name' => $names[$categoryId],
                'average' => $base,
            ];
        }

        usort($irregular, static fn (array $a, array $b): int => $b['yearly'] <=> $a['yearly']);

        return ['lines' => $lines, 'irregular' => $irregular];
    }

    /**
     * Fenêtre d'observation : les douze mois pleins qui précèdent le mois en cours.
     *
     * @return array{0: \DateTimeImmutable, 1: \DateTimeImmutable}
     */
    private function window(): array
    {
        $end = new \DateTimeImmutable('first day of this month 00:00:00', new \DateTimeZone('Europe/Paris'));

        return [$end->modify('-'.self::OBSERVED_MONTHS.' months'), $end];
    }

    /**
     * Une enveloppe se retient à la dizaine ; au centime, elle ne se retient pas.
     */
    private function roundToTen(float $amount): float
    {
        return (float) (round($amount / 10) * 10);
    }
}
