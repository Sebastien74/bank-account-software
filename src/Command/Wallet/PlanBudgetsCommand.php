<?php

declare(strict_types=1);

namespace App\Command\Wallet;

use App\Entity\Wallet\Wallet;
use App\Service\Wallet\BudgetPlanner;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * PlanBudgetsCommand.
 *
 * Propose, et sur demande écrit, une enveloppe mensuelle par poste de dépenses.
 *
 * La commande ne touche à rien sans `--apply` : un budget est un arbitrage, il
 * se lit avant d'être adopté.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[AsCommand(
    name: 'app:wallet:budgets',
    description: 'Propose un budget mensuel par poste, tenu dans les revenus du compte.',
)]
class PlanBudgetsCommand extends Command
{
    /**
     * PlanBudgetsCommand constructor.
     */
    public function __construct(
        private readonly BudgetPlanner $planner,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('wallet', 'w', InputOption::VALUE_REQUIRED, 'Slug du compte', 'main-wallet')
            ->addOption('cap', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Plafond impose a un poste, sous la forme "Poste=montant"')
            ->addOption('reste', null, InputOption::VALUE_REQUIRED, "Ce qu'on veut avoir en poche en fin de mois")
            ->addOption('epargne', null, InputOption::VALUE_REQUIRED, "Ce qu'on veut mettre de cote chaque mois")
            ->addOption('apply', null, InputOption::VALUE_NONE, 'Écrit les enveloppes proposées');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $slug = (string) $input->getOption('wallet');
        $wallet = $this->entityManager->getRepository(Wallet::class)->findOneBy(['slug' => $slug]);

        if (!$wallet instanceof Wallet) {
            $io->error(sprintf('Compte introuvable pour le slug "%s".', $slug));

            return Command::FAILURE;
        }

        $reste = $input->getOption('reste');
        $epargne = $input->getOption('epargne');
        $plan = $this->planner->plan(
            $wallet,
            $this->caps($input, $io),
            null !== $reste ? (float) str_replace(',', '.', (string) $reste) : null,
            null !== $epargne ? (float) str_replace(',', '.', (string) $epargne) : null
        );
        if ([] === $plan['lines']) {
            $io->warning("Aucune dépense sur les douze derniers mois : il n'y a rien à budgéter.");

            return Command::SUCCESS;
        }

        $io->definitionList(
            ['Revenu mensuel médian' => $this->euros($plan['income'])],
            ['Reste à vivre visé' => $this->euros($plan['margin'])],
            ['Enveloppe disponible' => $this->euros($plan['target'])],
            ['Dépense récurrente' => $this->euros($plan['observed'])],
            ['Total proposé' => $this->euros($plan['planned'])],
        );

        $io->table(
            ['Poste', 'Nature', 'Récurrent / mois', 'Budget', 'Écart'],
            array_map(fn (array $line): array => [
                $line['name'],
                $this->kind($line['kind']),
                $this->euros($line['observed']),
                $this->euros($line['amount']),
                $this->euros(round($line['amount'] - $line['observed'], 2)),
            ], $plan['lines'])
        );

        if ([] !== $plan['irregular']) {
            /*
             * Ces postes ne reviennent pas tous les mois : les noyer dans une
             * enveloppe mensuelle la rendrait fausse douze mois sur douze. Ils
             * sont montres a part, avec leur cout annuel, pour etre provisionnes
             * sciemment plutot qu'oublies jusqu'au mois ou ils tombent.
             */
            $io->section('Échéances à provisionner, hors enveloppes mensuelles');
            $io->table(
                ['Poste', 'Sous-poste', 'Mois actifs', 'Coût annuel', 'Équivalent / mois'],
                array_map(fn (array $row): array => [
                    $row['category'],
                    $row['name'],
                    $row['months'].'/'.BudgetPlanner::OBSERVED_MONTHS,
                    $this->euros($row['yearly']),
                    $this->euros($row['monthly']),
                ], $plan['irregular'])
            );
            $io->text(sprintf(
                'Total a provisionner : %s par an, soit %s par mois, repris dans la ligne « %s ».',
                $this->euros(round(array_sum(array_column($plan['irregular'], 'yearly')), 2)),
                $this->euros(round(array_sum(array_column($plan['irregular'], 'monthly')), 2)),
                'Provisions, échéances annuelles'
            ));
        }

        $effort = round($plan['observed'] - $plan['planned'], 2);
        if ($effort > 0) {
            $io->note(sprintf(
                "L'effort porte sur les postes arbitrables, ramenés à %s %% de leur dépense récurrente : %s à trouver chaque mois.",
                number_format($plan['ratio'] * 100, 0, ',', ' '),
                $this->euros($effort)
            ));
        }

        if (!$input->getOption('apply')) {
            $io->note('Lecture seule. Relancez avec --apply pour écrire ces enveloppes.');

            return Command::SUCCESS;
        }

        $report = $this->planner->apply($wallet, $plan['lines']);
        if ([] !== $report['skipped']) {
            $io->note(sprintf(
                "Laissé(s) tel(s) quel(s), les sous-postes ayant été répartis à la main sur plusieurs enveloppes : %s.",
                implode(', ', $report['skipped'])
            ));
        }
        $io->success(sprintf('%d enveloppe(s) créée(s), %d mise(s) à jour.', $report['created'], $report['updated']));

        return Command::SUCCESS;
    }

    /**
     * Plafonds passes en ligne de commande.
     *
     * @return array<string, float>
     */
    private function caps(InputInterface $input, SymfonyStyle $io): array
    {
        $caps = [];
        foreach ((array) $input->getOption('cap') as $raw) {
            if (!str_contains((string) $raw, '=')) {
                $io->warning(sprintf('Plafond ignore, forme attendue "Poste=montant" : %s', (string) $raw));
                continue;
            }

            [$name, $amount] = explode('=', (string) $raw, 2);
            $caps[trim($name)] = (float) str_replace(',', '.', trim($amount));
        }

        return $caps;
    }

    private function kind(string $kind): string
    {
        return match ($kind) {
            'constrained' => 'contraint',
            'provision' => 'provision',
            'savings' => 'épargne',
            'capped' => 'plafonné',
            default => 'arbitrable',
        };
    }

    private function euros(float $amount): string
    {
        return number_format($amount, 2, ',', ' ').' €';
    }
}
