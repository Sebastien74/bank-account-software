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
        $firstOperationDate = null;
        if (!empty($operations)) {
            $firstOperationDate = $operations[0]->getDate();
        }

        $balanceBefore = $operationRepository->sumBalance($wallet, false, $firstOperationDate);

        return new self(
            id: $wallet->getId(),
            slug : $wallet->getSlug(),
            entity: $wallet,
            title: $wallet->getAdminName(),
            balance: $operationRepository->sumBalance($wallet),
            realBalance: $operationRepository->sumBalance($wallet, true),
            currentBalance: $operationRepository->sumBalance($wallet, false, $date),
            currentRealBalance: $operationRepository->sumBalance($wallet, true, $date),
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
        foreach ($operations as $operation) {
            $model = OperationModel::fromEntity($operation, self::$coreLocator);
            $runningBalance = $model->income ? $runningBalance + $operation->getAmount() : $runningBalance - $operation->getAmount();
            $result[$operation->getId()] = [
                'model' => $model,
                'amount' => $operation->getAmount(),
                'balance' => $runningBalance,
            ];
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
