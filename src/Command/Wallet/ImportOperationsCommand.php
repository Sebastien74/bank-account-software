<?php

declare(strict_types=1);

namespace App\Command\Wallet;

use App\Entity\Wallet\Operation;
use App\Entity\Wallet\Wallet;
use App\Form\Manager\Wallet\OperationInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * ImportOperationsCommand.
 *
 * Import d'un relevé de compte XLSX. L'import est une écriture : il ne doit pas
 * être déclenché par l'affichage d'une page, mais explicitement.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[AsCommand(
    name: 'app:wallet:import',
    description: 'Importe les opérations d\'un relevé bancaire XLSX dans un compte.',
)]
class ImportOperationsCommand extends Command
{
    /**
     * ImportOperationsCommand constructor.
     */
    public function __construct(
        private readonly OperationInterface $operationManager,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('file', 'f', InputOption::VALUE_REQUIRED, 'Chemin du fichier XLSX à importer')
            ->addOption('wallet', 'w', InputOption::VALUE_REQUIRED, 'Slug du compte cible', 'main-wallet')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Analyse le fichier sans rien écrire')
            ->addOption('purge', null, InputOption::VALUE_NONE, 'Supprime les opérations du compte avant import');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $walletSlug = (string) $input->getOption('wallet');
        $wallet = $this->entityManager->getRepository(Wallet::class)->findOneBy(['slug' => $walletSlug]);

        if (!$wallet instanceof Wallet) {
            $io->error(sprintf('Compte introuvable pour le slug "%s".', $walletSlug));

            return Command::FAILURE;
        }

        $dryRun = (bool) $input->getOption('dry-run');

        if ($input->getOption('purge') && !$dryRun) {
            $deleted = $this->entityManager->getRepository(Operation::class)
                ->createQueryBuilder('o')
                ->delete()
                ->andWhere('o.wallet = :wallet')
                ->setParameter('wallet', $wallet)
                ->getQuery()
                ->execute();
            $this->entityManager->clear();
            $wallet = $this->entityManager->getRepository(Wallet::class)->findOneBy(['slug' => $walletSlug]);
            $io->warning(sprintf('%d opérations supprimées avant import.', (int) $deleted));
        }

        $report = $this->operationManager->import(
            $input->getOption('file') ? (string) $input->getOption('file') : null,
            $wallet,
            $dryRun
        );

        if (!empty($report['errors'])) {
            foreach ($report['errors'] as $error) {
                $io->warning($error);
            }
        }

        if (null === $report['file']) {
            return Command::FAILURE;
        }

        $io->title($dryRun ? 'Simulation d\'import' : 'Import des opérations');
        $io->definitionList(
            ['Fichier' => $report['file']],
            ['Compte' => (string) $report['wallet']],
            ['Lignes lues' => (string) $report['rows']],
            ['Opérations exploitables' => (string) $report['parsed']],
            ['Importées' => (string) $report['imported']],
            ['Déjà présentes' => (string) $report['skipped']],
            ['Ignorées' => (string) $report['ignored']],
            ['Bénéficiaires créés' => (string) $report['outsiders']],
            ['Catégorisées' => sprintf('%d / %d', $report['categorized'], $report['imported'])],
        );

        if (!empty($report['uncategorized'])) {
            arsort($report['uncategorized']);
            $io->section('Bénéficiaires sans correspondance');
            $io->table(
                ['Bénéficiaire', 'Opérations'],
                array_map(
                    static fn (string $name, int $count): array => [$name, (string) $count],
                    array_keys($report['uncategorized']),
                    $report['uncategorized']
                )
            );
        }

        if (!$dryRun) {
            $io->section('Solde');
            $io->definitionList(
                ['Solde cible du relevé' => null !== $report['targetBalance'] ? number_format($report['targetBalance'], 2, ',', ' ').' €' : 'non trouvé'],
                ['Somme des opérations' => number_format((float) $report['operationsSum'], 2, ',', ' ').' €'],
                ['Solde initial du compte' => number_format((float) $report['initialAmount'], 2, ',', ' ').' €'],
                ['Solde calculé' => number_format((float) $report['balance'], 2, ',', ' ').' €'],
            );

            if (null !== $report['targetBalance'] && abs((float) $report['balance'] - (float) $report['targetBalance']) >= 0.005) {
                $io->error('Le solde calculé ne correspond pas au solde du relevé.');

                return Command::FAILURE;
            }

            $io->success('Import terminé, solde conforme au relevé.');
        }

        return Command::SUCCESS;
    }
}
