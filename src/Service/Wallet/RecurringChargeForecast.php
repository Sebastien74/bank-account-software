<?php

declare(strict_types=1);

namespace App\Service\Wallet;

use App\Entity\Wallet\RecurringCharge;
use App\Entity\Wallet\Wallet;
use App\Service\Urlizer;
use App\Repository\Wallet\RecurringChargeRepository;
use App\Repository\Wallet\StatisticsRepository;
use App\Service\Wallet\Statistics\Period;
use App\Service\Wallet\Statistics\PeriodResolver;

/**
 * RecurringChargeForecast.
 *
 * Charges mensuelles attendues qui ne sont pas encore passées sur le mois.
 *
 * Aucun champ ne marque la récurrence : elle est déduite de la régularité
 * d'apparition d'un tiers sur les mois précédents, exactement comme le fait le
 * bloc « Charges récurrentes estimées » du tableau de bord. La détection n'est
 * donc pas dupliquée, seule la question change : non plus « combien me coûtent
 * mes charges », mais « laquelle n'est pas encore tombée ».
 *
 * Le mois en cours est volontairement exclu de la fenêtre d'observation : une
 * charge déjà passée ce mois-ci ne doit pas se compter elle-même parmi les
 * preuves de sa propre régularité.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final readonly class RecurringChargeForecast
{
    /** Mois observés pour juger de la régularité d'un tiers. */
    private const int OBSERVED_MONTHS = 12;

    /**
     * Fenêtre élargie pour les échéances annuelles : sur douze mois, un
     * abonnement payé une fois l'an n'apparaît qu'une seule fois et passe
     * pour un achat isolé. Il faut deux passages pour parler de récurrence.
     */
    private const int YEARLY_OBSERVED_MONTHS = 36;

    /** Part des mois où un tiers doit apparaître pour être tenu pour récurrent. */
    private const float RECURRENCE_RATIO = 0.6;

    /**
     * Postes qui portent un engagement récurrent.
     *
     * C'est la nature du poste qui fait la charge à payer, pas la régularité
     * de la dépense : on fait ses courses tous les mois sans rien devoir à
     * personne, et un retrait au distributeur n'est pas une échéance. Sans ce
     * filtre, la liste annonçait le tabac, la supérette et le bar du coin à
     * côté du loyer — elle ne répondait plus à « que me reste-t-il à payer ».
     *
     * @var array<int, string>
     */
    private const array COMMITMENT_SUB_CATEGORIES = [
        'expenses-various-subscriptions',
        'expenses-various-banking',
        'expenses-housing-rent',
        'expenses-housing-home-insurance',
        'expenses-housing-energy',
        'expenses-housing-heating',
        'expenses-housing-water-consumption',
        'expenses-housing-maintenance-and-services',
        'expenses-phone-phone-internet',
        'expenses-phone-internet',
        'expenses-phone-landline-phone',
        'expenses-phone-cell-phone',
        'expenses-automobile-car-insurance-and-taxes',
        'expenses-taxes-income-taxes',
        'expenses-taxes-license-fee',
        'expenses-taxes-residential-tax',
        'expenses-taxes-property-tax',
        'expenses-savings-transfer',
    ];

    public function __construct(
        private StatisticsRepository $statisticsRepository,
        private RecurringChargeRepository $chargeRepository,
        private PeriodResolver $periodResolver,
    ) {
    }

    /**
     * Charges récurrentes qui n'ont pas encore d'opération sur le mois demandé.
     *
     * @return array<int, array{key: string, name: string, reference: ?string, amount: float, estimated: float, overridden: bool, day: int, months: int, observed: int}>
     */
    public function pending(Wallet $wallet, int $year, int $month): array
    {
        // La fenêtre s'arrête au mois précédent : voir l'en-tête de classe.
        $previous = (new \DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month)))->modify('-1 month');
        $periods = $this->periodResolver->trailingMonths(
            (int) $previous->format('Y'),
            (int) $previous->format('n'),
            self::YEARLY_OBSERVED_MONTHS
        );
        /*
         * Le ratio de récurrence se juge sur les douze *derniers* mois, pas sur
         * douze mois quelconques de la fenêtre élargie : un abonnement résilié
         * il y a un an reste régulier dans son passé, et réapparaîtrait sans
         * cette restriction. Élargir la fenêtre sert à voir les annuelles, pas
         * à ressusciter les mensuelles éteintes.
         */
        $observed = self::OBSERVED_MONTHS;
        $recent = array_map(
            static fn (Period $period): string => $period->start->format('Y-m'),
            array_slice($periods, -self::OBSERVED_MONTHS)
        );
        $recent = array_flip($recent);
        if (0 === $observed) {
            return [];
        }

        // La fenêtre couvre *tous* les mois observés : bornée à $observed, elle
        // ne regardait que les douze plus anciens des trente-six.
        $range = new Period($periods[0]->start, $periods[count($periods) - 1]->end, 'récurrence');
        $rows = $this->statisticsRepository->commitmentRows($wallet, $range, self::COMMITMENT_SUB_CATEGORIES);
        $groups = $this->groupByContract($rows);
        $settings = $this->chargeRepository->settingsFor($wallet);
        $paidThisMonth = $this->paidContractsThisMonth($wallet, $year, $month, $settings);

        $pending = [];
        foreach ($groups as $key => $group) {
            $setting = $settings[$key] ?? null;
            // Une charge réglée en annuelle est jugée sur son mois, pas sur sa
            // fréquence d'apparition : elle n'en a qu'une par an.
            $isYearly = $setting?->isYearly() ?? false;
            $recentMonths = count(array_intersect_key($group['months'], $recent));
            /*
             * Un echeancier n'a pas a prouver sa regularite : il porte son
             * terme. Quatre prelevements ne franchiront jamais le seuil des
             * douze mois, et l'exiger ferait disparaitre la charge que
             * l'utilisateur vient justement de declarer.
             */
            $onSchedule = $isYearly || true === $setting?->hasInstalments();
            if (!$onSchedule && $recentMonths < $observed * self::RECURRENCE_RATIO) {
                continue;
            }
            if (isset($paidThisMonth[$key])) {
                continue;
            }

            /*
             * Le réglage de l'utilisateur prime sur l'historique : il sait
             * qu'un abonnement est résilié ou qu'un tarif a changé, l'historique
             * ne l'apprendra qu'avec plusieurs mois de retard.
             */
            if ($setting && !$setting->isDueOn($year, $month)) {
                continue;
            }

            $amounts = $isYearly
                ? $this->amountsForMonth($group['months'], $month)
                : array_values($group['months']);
            if ([] === $amounts) {
                $amounts = array_values($group['months']);
            }
            sort($amounts);
            // La médiane, pas la moyenne : un mois exceptionnel ne doit pas
            // déplacer l'estimation de tous les autres.
            $median = round($amounts[intdiv(count($amounts), 2)], 2);
            $amount = round($setting?->getAmount() ?? $median, 2);

            $days = $group['days'];
            sort($days);

            $pending[] = [
                'key' => $key,
                'yearly' => $isYearly,
                'instalment' => $setting?->instalmentNumber($year, $month),
                'instalments' => $setting?->getInstalments(),
                'name' => $group['name'],
                'reference' => $group['reference'],
                'category' => $group['category'],
                'categorySlug' => $group['categorySlug'],
                'outsiderId' => $group['outsiderId'],
                'amount' => $amount,
                'estimated' => $median,
                'overridden' => null !== $setting?->getAmount(),
                'day' => $setting?->getDueDay() ?? (int) $days[intdiv(count($days), 2)],
                'months' => $isYearly ? count($group['months']) : $recentMonths,
                'observed' => $observed,
            ];
        }

        // Une charge saisie à la main n'est dans aucun historique : elle ne
        // passerait jamais par la boucle de détection ci-dessus.
        foreach ($settings as $key => $setting) {
            if (!$setting->isManual() || !$setting->isDueOn($year, $month) || isset($paidThisMonth[$key])) {
                continue;
            }
            /*
             * Le poste et le tiers sont choisis a la saisie, comme sur une
             * operation : l'echeance encaissee par avance se range d'elle-meme
             * au bon endroit, au lieu de tomber dans les non classees.
             */
            $subCategory = $setting->getSubCategory();
            $pending[] = [
                'key' => $key,
                'yearly' => $setting->isYearly(),
                'instalment' => $setting->instalmentNumber($year, $month),
                'instalments' => $setting->getInstalments(),
                'name' => (string) $setting->getAdminName(),
                'reference' => null,
                'category' => $subCategory?->getAdminName() ?? (string) $setting->getCategory(),
                'categorySlug' => (string) $subCategory?->getSlug(),
                'outsiderId' => $setting->getOutsider()?->getId(),
                'amount' => round((float) ($setting->getAmount() ?? 0.0), 2),
                'estimated' => round((float) ($setting->getAmount() ?? 0.0), 2),
                'overridden' => true,
                'day' => $setting->getDueDay() ?? 1,
                'months' => 0,
                'observed' => self::OBSERVED_MONTHS,
            ];
        }

        // Par jour d'échéance : la question posée est « que me reste-t-il à
        // payer, et dans quel ordre ».
        usort($pending, static fn (array $a, array $b): int => [$a['day'], $b['amount']] <=> [$b['day'], $a['amount']]);

        return $pending;
    }

    /**
     * Montants observés sur un mois donné, tous exercices confondus.
     *
     * Une échéance annuelle se compare à elle-même : la médiane de ses seuls
     * passages de septembre, pas celle de l'année entière.
     *
     * @param array<string, float> $months
     *
     * @return array<int, float>
     */
    private function amountsForMonth(array $months, int $month): array
    {
        $suffix = sprintf('-%02d', $month);
        $amounts = [];
        foreach ($months as $period => $amount) {
            if (str_ends_with($period, $suffix)) {
                $amounts[] = $amount;
            }
        }

        return $amounts;
    }

    /**
     * Regroupe les lignes par contrat, et non par seul bénéficiaire.
     *
     * Un même fournisseur peut porter plusieurs engagements : Bouygues
     * prélève la ligne mobile et la box le même jour, sous le même nom. Les
     * additionner donnerait une échéance unique qui n'existe pas, et empêcherait
     * d'en corriger une sans l'autre.
     *
     * La référence de contrat est le seul repère stable : le libellé la porte
     * en clair, partiellement masquée (« 06xxxxx891 »), alors que la référence
     * de transaction, elle, change à chaque prélèvement.
     *
     * @param array<int, array{name: string, label: string, category: string, date: \DateTimeInterface, amount: float}> $rows
     *
     * @return array<string, array{name: string, reference: ?string, category: string, months: array<string, float>, days: array<int, int>}>
     */
    private function groupByContract(array $rows): array
    {
        $groups = [];
        foreach ($rows as $row) {
            $reference = $this->contractReference($row['label']);
            $key = Urlizer::urlize($row['name'].($reference ? '-'.$reference : ''));
            $period = $row['date']->format('Y-m');

            $groups[$key] ??= [
                'name' => $row['name'],
                'reference' => $reference,
                'category' => $row['category'] ?? '',
                'categorySlug' => $row['categorySlug'] ?? '',
                'outsiderId' => $row['outsiderId'] ?? null,
                'months' => [],
                'days' => [],
            ];
            $groups[$key]['months'][$period] = round(($groups[$key]['months'][$period] ?? 0.0) + $row['amount'], 2);
            $groups[$key]['days'][] = (int) $row['date']->format('j');
        }

        return $groups;
    }

    /**
     * Référence de contrat masquée, telle que la banque l'écrit.
     *
     * Deux chiffres, une suite de « x » de masquage, puis la fin du numéro.
     * Rien d'autre dans un libellé de prélèvement n'a cette forme, et elle est
     * identique d'un mois à l'autre.
     */
    private function contractReference(string $label): ?string
    {
        return 1 === preg_match('/\b(\d{2}x{3,}\d{2,})\b/i', $label, $matches) ? $matches[1] : null;
    }

    /**
     * Contrats déjà passés sur le mois demandé.
     *
     * Les postes d'engagement ne suffisent pas à poser la question : ils
     * disent ce qui *peut* être une charge récurrente, pas où l'écriture d'une
     * charge déclarée est tombée. Une charge saisie à la main porte le poste
     * que l'utilisateur lui a donné — un échéancier Darty se range dans
     * « Cadeaux » — et la chercher ailleurs la laissait annoncée à venir alors
     * qu'elle venait d'être enregistrée : la ligne se lisait deux fois, une
     * fois réelle et une fois prévue. Les postes des charges réglées sont donc
     * ajoutés à la fenêtre d'observation.
     *
     * @param array<string, RecurringCharge> $settings
     *
     * @return array<string, true>
     */
    private function paidContractsThisMonth(Wallet $wallet, int $year, int $month, array $settings = []): array
    {
        $slugs = self::COMMITMENT_SUB_CATEGORIES;
        foreach ($settings as $setting) {
            $slug = $setting->getSubCategory()?->getSlug();
            if (null !== $slug && '' !== $slug) {
                $slugs[] = $slug;
            }
        }

        $period = $this->periodResolver->month($year, $month);
        $rows = $this->statisticsRepository->commitmentRows($wallet, $period, array_values(array_unique($slugs)));
        $paid = [];
        foreach (array_keys($this->groupByContract($rows)) as $key) {
            $paid[(string) $key] = true;
        }

        return $paid;
    }

    /**
     * Catalogue complet des charges détectées, réglages compris.
     *
     * `pending()` répond à « que reste-t-il à payer » et tait donc ce qui est
     * déjà passé ou désactivé. L'écran de gestion, lui, doit tout montrer :
     * on ne réactive pas une charge qu'on ne voit plus.
     *
     * @return array<int, array{key: string, name: string, reference: ?string, category: string, estimated: float, amount: float, day: int, months: int, observed: int, active: bool, yearly: bool, dueMonth: ?int, overridden: bool, paid: bool}>
     */
    public function catalogue(Wallet $wallet, int $year, int $month): array
    {
        $previous = (new \DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month)))->modify('-1 month');
        $periods = $this->periodResolver->trailingMonths(
            (int) $previous->format('Y'),
            (int) $previous->format('n'),
            self::YEARLY_OBSERVED_MONTHS
        );
        if ([] === $periods) {
            return [];
        }

        $range = new Period($periods[0]->start, $periods[count($periods) - 1]->end, 'récurrence');
        $groups = $this->groupByContract($this->statisticsRepository->commitmentRows($wallet, $range, self::COMMITMENT_SUB_CATEGORIES));
        $settings = $this->chargeRepository->settingsFor($wallet);
        $paid = $this->paidContractsThisMonth($wallet, $year, $month, $settings);
        $recent = array_flip(array_map(
            static fn (Period $period): string => $period->start->format('Y-m'),
            array_slice($periods, -self::OBSERVED_MONTHS)
        ));

        $catalogue = [];
        foreach ($groups as $key => $group) {
            $setting = $settings[$key] ?? null;
            $isYearly = $setting?->isYearly() ?? false;
            $recentMonths = count(array_intersect_key($group['months'], $recent));
            // Une charge réglée à la main reste listée même si l'historique ne
            // la voit plus : c'est la seule façon de revenir sur le réglage.
            if (!$isYearly && null === $setting && $recentMonths < self::OBSERVED_MONTHS * self::RECURRENCE_RATIO) {
                continue;
            }

            $amounts = array_values($group['months']);
            sort($amounts);
            $median = round($amounts[intdiv(count($amounts), 2)], 2);
            $days = $group['days'];
            sort($days);

            $catalogue[] = [
                'key' => $key,
                'name' => $group['name'],
                'reference' => $group['reference'],
                'category' => $group['category'],
                'estimated' => $median,
                'amount' => round($setting?->getAmount() ?? $median, 2),
                'day' => $setting?->getDueDay() ?? (int) $days[intdiv(count($days), 2)],
                'months' => $recentMonths,
                'observed' => self::OBSERVED_MONTHS,
                'active' => $setting?->isActive() ?? true,
                'yearly' => $isYearly,
                'frequency' => $setting?->getFrequency() ?? RecurringCharge::MONTHLY,
                'instalments' => $setting?->getInstalments(),
                'startsOn' => $setting?->getStartsOn(),
                'instalment' => $setting?->instalmentNumber($year, $month),
                'outsiderId' => $group['outsiderId'],
                'subCategoryId' => $setting?->getSubCategory()?->getId(),
                'dueMonth' => $setting?->getDueMonth(),
                'overridden' => null !== $setting?->getAmount(),
                'manual' => false,
                'paid' => isset($paid[$key]),
            ];
        }

        /*
         * Les charges saisies à la main n'apparaissent dans aucun historique :
         * rien ne les ferait remonter par la détection. Elles sont ajoutées
         * ici, après coup, pour que la liste montre tout ce qui est attendu.
         */
        $known = array_column($catalogue, 'key');
        foreach ($settings as $key => $setting) {
            if (!$setting->isManual() || in_array($key, $known, true)) {
                continue;
            }
            $catalogue[] = [
                'key' => $key,
                'name' => (string) $setting->getAdminName(),
                'reference' => null,
                'category' => $setting->getSubCategory()?->getAdminName() ?? (string) $setting->getCategory(),
                'estimated' => (float) ($setting->getAmount() ?? 0.0),
                'amount' => (float) ($setting->getAmount() ?? 0.0),
                'day' => $setting->getDueDay() ?? 1,
                'months' => 0,
                'observed' => self::OBSERVED_MONTHS,
                'active' => $setting->isActive(),
                'yearly' => $setting->isYearly(),
                'frequency' => $setting->getFrequency(),
                'instalments' => $setting->getInstalments(),
                'startsOn' => $setting->getStartsOn(),
                'instalment' => $setting->instalmentNumber($year, $month),
                'outsiderId' => $setting->getOutsider()?->getId(),
                'subCategoryId' => $setting->getSubCategory()?->getId(),
                'dueMonth' => $setting->getDueMonth(),
                'overridden' => true,
                'manual' => true,
                'paid' => isset($paid[$key]),
            ];
        }

        usort($catalogue, static fn (array $a, array $b): int => [$a['day'], $a['name']] <=> [$b['day'], $b['name']]);

        return $catalogue;
    }

    /**
     * Total attendu, pour annoncer l'enjeu sans faire compter l'utilisateur.
     *
     * @param array<int, array{amount: float}> $pending
     */
    public function total(array $pending): float
    {
        return round(array_sum(array_column($pending, 'amount')), 2);
    }
}
