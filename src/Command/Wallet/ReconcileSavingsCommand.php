<?php

declare(strict_types=1);

namespace App\Command\Wallet;

use App\Entity\Wallet\Wallet;
use App\Service\Wallet\SavingsReconciler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * ReconcileSavingsCommand.
 *
 * Crée au besoin le compte d'épargne rattaché à un compte courant, puis y
 * reporte les virements d'épargne émis depuis celui-ci.
 *
 * Le rapprochement est une écriture : il se déclenche explicitement, jamais à
 * l'affichage d'une page. Il est idempotent et peut être rejoué après chaque
 * import.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[AsCommand(
    name: 'app:wallet:savings',
    description: 'Crée le compte d\'épargne d\'un compte courant et y reporte ses virements.',
)]
class ReconcileSavingsCommand extends Command
{
    /**
     * ReconcileSavingsCommand constructor.
     */
    public function __construct(
        private readonly SavingsReconciler $reconciler,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('wallet', 'w', InputOption::VALUE_REQUIRED, 'Slug du compte courant', 'main-wallet')
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'Intitulé du compte d\'épargne', 'Compte épargne')
            ->addOption('slug', null, InputOption::VALUE_REQUIRED, 'Slug du compte d\'épargne', 'savings-wallet')
            ->addOption('initial', null, InputOption::VALUE_REQUIRED, 'Solde du compte d\'épargne avant le premier virement repris', '0')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Annonce ce qui serait écrit sans rien écrire');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = (bool) $input->getOption('dry-run');
        $repository = $this->entityManager->getRepository(Wallet::class);
        $slug = (string) $input->getOption('wallet');
        $wallet = $repository->findOneBy(['slug' => $slug]);

        if (!$wallet instanceof Wallet) {
            $io->error(sprintf('Compte introuvable pour le slug "%s".', $slug));

            return Command::FAILURE;
        }

        if ($wallet->isSavings()) {
            $io->error('Ce compte est déjà un compte d\'épargne : le rapprochement part du compte courant.');

            return Command::FAILURE;
        }

        $savings = $wallet->getSavingsWallet();
        if (!$savings instanceof Wallet) {
            $savingsSlug = (string) $input->getOption('slug');
            $savings = $repository->findOneBy(['slug' => $savingsSlug]);

            if (!$savings instanceof Wallet) {
                $savings = (new Wallet())
                    ->setType(Wallet::SAVINGS)
                    ->setInitialAmount((float) str_replace(',', '.', (string) $input->getOption('initial')));
                $savings->setAdminName((string) $input->getOption('name'));
                $savings->setSlug($savingsSlug);
                $savings->setPosition($this->nextPosition());
                $io->text(sprintf('Compte d\'épargne « %s » à créer.', (string) $input->getOption('name')));

                if (!$dryRun) {
                    $this->entityManager->persist($savings);
                }
            }

            $wallet->setSavingsWallet($savings);
            if (!$dryRun) {
                $this->entityManager->flush();
            }
        }

        if ($dryRun && null === $savings->getId()) {
            $io->warning('Le compte d\'épargne n\'existe pas encore : relancez sans --dry-run pour le créer et compter les virements.');

            return Command::SUCCESS;
        }

        $report = $this->reconciler->reconcile($wallet, $dryRun);

        $io->definitionList(
            ['Compte courant' => sprintf('%s (%s €)', (string) $wallet->getAdminName(), number_format($wallet->getBalance(), 2, ',', ' '))],
            ['Compte d\'épargne' => (string) $savings->getAdminName()],
            ['Virements reportés' => (string) $report['created']],
            ['Déjà rapprochés' => (string) $report['existing']],
            ['Contreparties remises à jour' => (string) $report['updated']],
            ['Contreparties retirées' => (string) $report['removed']],
        );

        if ($report['missing'] > 0) {
            $io->error(sprintf('%d virements non reportés : le type d\'opération « incomes » est introuvable.', $report['missing']));

            return Command::FAILURE;
        }

        if ($dryRun) {
            $io->note('Lecture seule : rien n\'a été écrit.');

            return Command::SUCCESS;
        }

        $this->entityManager->refresh($savings);
        $io->success(sprintf('Solde du compte d\'épargne : %s €', number_format($savings->getBalance(), 2, ',', ' ')));

        return Command::SUCCESS;
    }

    /**
     * Position du nouveau compte, à la suite des comptes existants.
     */
    private function nextPosition(): int
    {
        $max = $this->entityManager->createQueryBuilder()
            ->select('MAX(w.position)')
            ->from(Wallet::class, 'w')
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $max + 1;
    }
}
