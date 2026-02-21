<?php

declare(strict_types=1);

namespace App\Model\Wallet;

use App\Entity\Wallet\Operation;
use App\Entity\Wallet\Wallet;
use App\Model\BaseModel;
use App\Service\CoreLocatorInterface;
use Exception;

/**
 * WalletModel.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final class WalletModel extends BaseModel
{
    /**
     * WalletModel constructor.
     */
    public function __construct(
        public readonly ?int $id = null,
        public readonly ?string $slug = null,
        public readonly ?Wallet $entity = null,
        public readonly ?string $title = null,
        public readonly ?float $balance = null,
        public readonly ?float $realBalance = null,
        public readonly ?float $currentBalance = null,
        public readonly ?float $currentRealBalance = null,
        public readonly ?array $currentOperations = [],
        public readonly ?array $sumPerMonth = [],
    ) {
    }

    /**
     * fromEntity.
     *
     * @throws Exception
     */
    public static function fromEntity(Wallet $wallet, CoreLocatorInterface $coreLocator, array $options = []): object
    {
        self::setLocator($coreLocator);

        $operations = !empty($options['operations']) ? $options['operations'] : [];
        $operationRepository = self::$coreLocator->em()->getRepository(Operation::class);
        $date = (new \DateTime('now', new \DateTimeZone('Europe/Paris')))->modify('first day of next month');

        // Identify the first operation date in the current month's list to calculate the starting balance
        // We sort them by date ASC and ID ASC to find the chronologically first operation of the period
        $sortedOperations = $operations;
        usort($sortedOperations, function($a, $b) {
            $dateDiff = $a->getDate() <=> $b->getDate();
            return 0 !== $dateDiff ? $dateDiff : $a->getId() <=> $b->getId();
        });

        $firstOperationDate = null;
        if (!empty($sortedOperations)) {
            $firstOperationDate = $sortedOperations[0]->getDate();
        }

        $balanceBefore = $operationRepository->sumBalance($wallet, false, $firstOperationDate);

        return new self(
            id: $wallet->getId(),
            slug : $wallet->getSlug(),
            entity: $wallet,
            title: $wallet->getAdminName(),
            balance: round($operationRepository->sumBalance($wallet), 2),
            realBalance: round($operationRepository->sumBalance($wallet, true), 2),
            currentBalance: round($operationRepository->sumBalance($wallet, false, $date), 2),
            currentRealBalance: round($operationRepository->sumBalance($wallet, true, $date), 2),
            currentOperations: self::currentOperations($operations, $balanceBefore),
            sumPerMonth: self::sumPerMonth($wallet, $options),
        );
    }

    /**
     * To set current Operation[].
     */
    private static function currentOperations(array $operations = [], ?float $balance = null): array
    {
        $result = [];
        $runningBalance = $balance;

        // We must calculate balances chronologically regardless of the display order
        $chronologicalOperations = $operations;
        usort($chronologicalOperations, function($a, $b) {
            $dateDiff = $a->getDate() <=> $b->getDate();
            return 0 !== $dateDiff ? $dateDiff : $a->getId() <=> $b->getId();
        });

        $balances = [];
        foreach ($chronologicalOperations as $operation) {
            $model = OperationModel::fromEntity($operation, self::$coreLocator);
            $runningBalance = $model->income ? $runningBalance + $operation->getAmount() : $runningBalance - $operation->getAmount();
            $balances[$operation->getId()] = [
                'model' => $model,
                'amount' => $operation->getAmount(),
                'balance' => round($runningBalance, 2),
            ];
        }

        // Return results in the original order provided (usually the requested display order)
        foreach ($operations as $operation) {
            $result[$operation->getId()] = $balances[$operation->getId()];
        }

        return $result;
    }

    /**
     * To set sumPerMonth.
     *
     * @throws Exception
     */
    private static function sumPerMonth(Wallet $wallet, array $options = []): array
    {
        $date = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
        $parse = isset($options['sumPerMonth']) && true === (bool)$options['sumPerMonth'];

        return $parse ? self::$coreLocator->em()->getRepository(Operation::class)->sumPerMonth($wallet, new \DateTime('2025-01-31 00:00:00'), (clone $date)->modify('first day of next month'), new \DateTimeZone('Europe/Paris')) : [];
    }
}
