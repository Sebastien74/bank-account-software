<?php

declare(strict_types=1);

namespace App\Form\Manager\Wallet;

use App\Entity\Wallet\Operation;
use App\Entity\Wallet\OperationType;
use App\Entity\Wallet\Outsider;
use App\Entity\Wallet\Wallet;
use App\Service\CoreLocatorInterface;
use App\Service\Urlizer;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\Form\FormInterface;

/**
 * OperationManager.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
readonly class OperationManager implements OperationInterface
{
    /**
     * OperationManager constructor.
     */
    public function __construct(private CoreLocatorInterface $coreLocator)
    {
    }

    public function import(): void
    {
        $wallet = $this->coreLocator->em()->getRepository(Wallet::class)->findOneBy(['slug' => 'main-wallet']);
        if (!$wallet) {
            return;
        }

        $file = $this->coreLocator->projectDir() . '/bin/data/import/operations.xlsx';
        if (!file_exists($file)) {
            return;
        }

        $spreadsheet = IOFactory::load($file);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray(null, true, true, true);

        $em = $this->coreLocator->em();
        $operationRepository = $em->getRepository(Operation::class);
        $outsiderRepository = $em->getRepository(Outsider::class);
        $operationTypeRepository = $em->getRepository(OperationType::class);
        $user = $this->coreLocator->user();

        $expensesType = $operationTypeRepository->findOneBy(['type' => 'expenses']);
        $incomesType = $operationTypeRepository->findOneBy(['type' => 'incomes']);

        $targetBalance = null;
        if (!empty($rows[7]['C'])) {
            $targetBalanceStr = $rows[7]['C'];
            $targetBalanceStr = str_replace([' ', '€', "\u{00A0}"], '', $targetBalanceStr);
            $targetBalanceStr = str_replace(',', '.', $targetBalanceStr);
            $targetBalance = (float) $targetBalanceStr;
        }

        $outsiders = [];
        foreach ($outsiderRepository->findBy(['createdBy' => $user]) as $outsider) {
            $outsiders[$outsider->getAdminName()] = $outsider;
        }

        $hasChanges = false;
        foreach ($rows as $index => $row) {
            if ($index < 11 || empty($row['A']) || empty($row['B'])) {
                continue;
            }

            $dateStr = $row['A'];
            $adminName = trim($row['B']);
            $cleanAdminName = $this->cleanOutsiderName($adminName);
            $debit = !empty($row['C']) ? (float) str_replace(',', '.', (string) $row['C']) : 0.0;
            $credit = !empty($row['D']) ? (float) str_replace(',', '.', (string) $row['D']) : 0.0;
            $amount = $debit > 0 ? $debit : $credit;
            $operationType = $debit > 0 ? $expensesType : $incomesType;

            try {
                $date = \DateTime::createFromFormat('d/m/Y', $dateStr);
                if (!$date) {
                    continue;
                }
                $date->setTime(0, 0, 0);
            } catch (\Exception) {
                continue;
            }

            $existing = $operationRepository->findOneBy([
                'wallet' => $wallet,
                'date' => $date,
                'amount' => $amount,
                'adminName' => $adminName
            ]);

            if (!$existing) {
                $operation = new Operation();
                $operation->setWallet($wallet);
                $operation->setDate($date);
                $operation->setAmount($amount);
                $operation->setAdminName($adminName);
                $operation->setCreatedBy($user);
                $operation->setOperationType($operationType);

                if (!isset($outsiders[$cleanAdminName])) {
                    $outsider = $outsiderRepository->findOneBy(['adminName' => $cleanAdminName, 'createdBy' => $user]);
                    if (!$outsider) {
                        $outsider = new Outsider();
                        $outsider->setAdminName($cleanAdminName);
                        $outsider->setSlug(Urlizer::urlize($cleanAdminName));
                        $outsider->setCreatedBy($user);
                        $outsider->setPosition(count($outsiders) + 1);
                        $em->persist($outsider);
                    }
                    $outsiders[$cleanAdminName] = $outsider;
                }
                $operation->setOutsider($outsiders[$cleanAdminName]);

                $em->persist($operation);
                $hasChanges = true;
            }
        }

        if ($hasChanges) {
            $em->flush();
        }

        if (null !== $targetBalance) {
            $initial = $wallet->getInitialAmount() ?? 0.0;
            $currentBalance = $operationRepository->sumBalance($wallet);
            if ($currentBalance !== $targetBalance) {
                $diff = $targetBalance - $currentBalance;
                $wallet->setInitialAmount($initial + $diff);
                $em->persist($wallet);
                $em->flush();
            }
        }
    }

    public function execute(Operation $operation, FormInterface $form): void
    {
        $adminName = $form->get('adminName')->getData();
        if ($adminName) {
            $cleanAdminName = $this->cleanOutsiderName($adminName);
            $position = count($this->coreLocator->em()->getRepository(Outsider::class)->findBy([
                'createdBy' => $this->coreLocator->user()
            ])) + 1;
            $outsider = $this->coreLocator->em()->getRepository(Outsider::class)->findOneBy([
                'createdBy' => $this->coreLocator->user(),
                'adminName' => $cleanAdminName,
            ]);
            if (!$outsider) {
                $outsider = new Outsider();
                $outsider->setAdminName($cleanAdminName);
                $outsider->setSlug(Urlizer::urlize($cleanAdminName));
                $outsider->setCreatedBy($this->coreLocator->user());
                $outsider->setPosition($position);
                $this->coreLocator->em()->persist($outsider);
            }
            $operation->setOutsider($outsider);
        }

        if ($operation->getSubCategory() && !$operation->getOperationType()) {
            $operationType = $this->coreLocator->em()->getRepository(OperationType::class)->findOneBy([
                'type' => $operation->getSubCategory()->getType()
            ]);
            if ($operationType) {
                $operation->setOperationType($operationType);
            }
        }
    }

    /**
     * Nettoie le nom du tiers pour éviter les doublons.
     */
    private function cleanOutsiderName(string $adminName): string
    {
        $adminName = mb_strtoupper($adminName, 'UTF-8');

        // Suppression des préfixes de paiement courants
        $adminName = str_replace(['PAIEMENT PAR CARTE', 'PAIEMENT PAR', 'VIREMENT DE', 'VIREMENT POUR'], '', $adminName);

        // Suppression des codes de transaction (ex: X9322)
        $adminName = preg_replace('/X\d{4,}/', '', $adminName);

        // Suppression des dates (ex: 19/02)
        $adminName = preg_replace('/\d{2}\/\d{2}/', '', $adminName);

        // Nettoyage des espaces multiples et trim
        return trim(preg_replace('/\s+/', ' ', $adminName));
    }
}
