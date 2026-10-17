<?php

declare(strict_types=1);

namespace App\Service\Wallet;

use App\Entity\Wallet\Operation;
use App\Entity\Wallet\OperationType;
use App\Entity\Wallet\SubCategory;
use App\Entity\Wallet\Wallet;
use Doctrine\ORM\EntityManagerInterface;

/**
 * SavingsReconciler.
 *
 * Alimente un compte d'épargne à partir des virements émis depuis le compte
 * courant.
 *
 * Un virement d'épargne est **un seul mouvement vu de deux comptes** : un débit
 * sur le courant, un crédit sur l'épargne. Les deux écritures sont donc liées
 * par `counterpartOf`, avec suppression en cascade — sans ce lien, effacer le
 * débit laisserait un crédit orphelin et le solde de l'épargne mentirait.
 *
 * Le rapprochement est idempotent : il ne crée une contrepartie que là où il
 * n'y en a pas, et peut être rejoué après chaque import sans rien dupliquer.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final readonly class SavingsReconciler
{
    /** Poste des virements qui alimentent l'épargne. */
    public const string TRANSFER_SUB_CATEGORY = 'expenses-savings-transfer';

    /** Poste sous lequel le crédit apparaît sur le compte d'épargne. */
    public const string DEPOSIT_SUB_CATEGORY = 'incomes-other-deposits';

    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    /**
     * Rapproche les virements d'épargne du compte courant.
     *
     * @return array{created: int, existing: int, updated: int, removed: int, missing: int}
     */
    public function reconcile(Wallet $wallet, bool $dryRun = false): array
    {
        $savings = $wallet->getSavingsWallet();
        $report = ['created' => 0, 'existing' => 0, 'updated' => 0, 'removed' => 0, 'missing' => 0];

        if (!$savings instanceof Wallet) {
            return $report;
        }

        $transfers = $this->transfers($wallet);
        $mirrored = $this->mirroredBySource($savings);
        $income = $this->entityManager->getRepository(OperationType::class)->findOneBy(['slug' => 'incomes']);
        $deposit = $this->entityManager->getRepository(SubCategory::class)->findOneBy(['slug' => self::DEPOSIT_SUB_CATEGORY]);

        if (!$income instanceof OperationType) {
            $report['missing'] = count($transfers);

            return $report;
        }

        foreach ($transfers as $transfer) {
            $counterpart = $mirrored[$transfer->getId()] ?? null;

            if ($counterpart instanceof Operation) {
                /*
                 * Le débit a pu être corrigé depuis le rapprochement. Les deux
                 * écritures décrivent un seul mouvement : si elles divergent,
                 * c'est le compte courant qui fait foi.
                 */
                ++$report['existing'];
                if ($this->matches($counterpart, $transfer)) {
                    continue;
                }

                ++$report['updated'];
                if (!$dryRun) {
                    $this->fill($counterpart, $transfer);
                }

                continue;
            }

            ++$report['created'];
            if ($dryRun) {
                continue;
            }

            $counterpart = (new Operation())
                ->setWallet($savings)
                ->setCounterpartOf($transfer)
                ->setOperationType($income);
            if ($deposit instanceof SubCategory) {
                $counterpart->setSubCategory($deposit);
            }
            $this->fill($counterpart, $transfer);

            $this->entityManager->persist($counterpart);
        }

        /*
         * Un debit reclasse hors de l'epargne n'alimente plus rien : sa
         * contrepartie deviendrait un depot sans origine, et gonflerait le
         * solde de l'epargne sans qu'aucune ecriture ne l'explique.
         */
        $sources = [];
        foreach ($transfers as $transfer) {
            $sources[$transfer->getId()] = true;
        }
        foreach ($mirrored as $sourceId => $counterpart) {
            if (isset($sources[$sourceId])) {
                continue;
            }

            ++$report['removed'];
            if (!$dryRun) {
                $this->entityManager->remove($counterpart);
            }
        }

        if (!$dryRun) {
            $this->entityManager->flush();
        }

        return $report;
    }

    /**
     * Virements d'épargne émis depuis le compte.
     *
     * @return Operation[]
     */
    private function transfers(Wallet $wallet): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('o')
            ->from(Operation::class, 'o')
            ->join('o.subCategory', 'sc')
            ->andWhere('o.wallet = :wallet')
            ->andWhere('sc.slug = :slug')
            ->setParameter('wallet', $wallet)
            ->setParameter('slug', self::TRANSFER_SUB_CATEGORY)
            ->orderBy('o.date', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Contreparties déjà présentes sur l'épargne, indexées par virement source.
     *
     * @return array<int, Operation>
     */
    private function mirroredBySource(Wallet $savings): array
    {
        $rows = $this->entityManager->createQueryBuilder()
            ->select('o')
            ->from(Operation::class, 'o')
            ->andWhere('o.wallet = :savings')
            ->andWhere('o.counterpartOf IS NOT NULL')
            ->setParameter('savings', $savings)
            ->getQuery()
            ->getResult();

        $bySource = [];
        foreach ($rows as $row) {
            $bySource[(int) $row->getCounterpartOf()?->getId()] = $row;
        }

        return $bySource;
    }

    /**
     * La contrepartie dit-elle encore la même chose que son virement ?
     */
    private function matches(Operation $counterpart, Operation $transfer): bool
    {
        return abs((float) $counterpart->getAmount() - (float) $transfer->getAmount()) < 0.005
            && $counterpart->getDate()?->format('Y-m-d') === $transfer->getDate()?->format('Y-m-d')
            && $counterpart->isPointed() === $transfer->isPointed();
    }

    /**
     * Recopie le mouvement du compte courant sur sa contrepartie.
     *
     * La date est clonée : partager l'objet ferait suivre l'épargne à chaque
     * retouche de la date du débit, y compris quand elle n'est pas voulue.
     */
    private function fill(Operation $counterpart, Operation $transfer): void
    {
        $date = $transfer->getDate();
        $counterpart
            ->setDate($date instanceof \DateTimeInterface ? new \DateTime($date->format('Y-m-d H:i:s')) : null)
            ->setAmount((float) $transfer->getAmount())
            ->setPointed((bool) $transfer->isPointed())
            ->setOutsider($transfer->getOutsider())
            ->setAdminName($this->label($transfer));
    }

    /**
     * Libellé de la contrepartie.
     *
     * Il nomme la provenance plutôt que de recopier le libellé bancaire du
     * débit, qui parle du compte courant et n'a pas de sens vu de l'épargne.
     */
    private function label(Operation $transfer): string
    {
        return sprintf(
            'Virement reçu du compte %s du %s',
            (string) $transfer->getWallet()?->getAdminName(),
            $transfer->getDate()?->format('d/m/Y') ?? ''
        );
    }
}
