<?php

declare(strict_types=1);

namespace App\Entity\Wallet;

use App\Entity\BaseEntity;
use App\Repository\Wallet\WalletRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Wallet.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'wallet')]
#[ORM\Entity(repositoryClass: WalletRepository::class)]
class Wallet extends BaseEntity
{
    /**
     * Configurations.
     */
    protected static array $interface = [
        'name' => 'wallet',
    ];

    #[ORM\Column(type: 'float')]
    private ?float $initialAmount = null;

    #[ORM\OneToMany(targetEntity: Operation::class, mappedBy: 'wallet', cascade: ['persist', 'remove'], fetch: 'EAGER', orphanRemoval: true)]
    #[ORM\OrderBy(['date' => 'DESC'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $operations;

    /**
     * Wallet constructor.
     */
    public function __construct()
    {
        $this->operations = new ArrayCollection();
    }

    public function getInitialAmount(): ?float
    {
        return $this->initialAmount;
    }

    public function setInitialAmount(float $initialAmount): static
    {
        $this->initialAmount = $initialAmount;

        return $this;
    }

    /**
     * @return Collection<int, Operation>
     */
    public function getOperations(): Collection
    {
        return $this->operations;
    }

    public function addOperation(Operation $operation): static
    {
        if (!$this->operations->contains($operation)) {
            $this->operations->add($operation);
            $operation->setWallet($this);
        }

        return $this;
    }

    public function removeOperation(Operation $operation): static
    {
        if ($this->operations->removeElement($operation)) {
            // set the owning side to null (unless already changed)
            if ($operation->getWallet() === $this) {
                $operation->setWallet(null);
            }
        }

        return $this;
    }

    public function getBalance(): float
    {
        $balance = $this->initialAmount;

        foreach ($this->operations as $operation) {
            $amount = $operation->getAmount();
            $operationType = $operation->getOperationType();
            $subCategory = $operation->getSubCategory();

            $isIncome = ($operationType && $operationType->getType() === 'incomes')
                || (!$operationType && $subCategory && $subCategory->getType() === 'incomes');

            if ($isIncome) {
                $balance += $amount;
            } else {
                $balance -= $amount;
            }
        }

        return (float) $balance;
    }

    public function getDailyAverageExpenses(): float
    {
        $expenses = 0;
        $now = new \DateTime();
        $currentMonth = $now->format('m');
        $currentYear = $now->format('Y');

        foreach ($this->operations as $operation) {
            if ($operation->getDate() && $operation->getDate()->format('m') === $currentMonth && $operation->getDate()->format('Y') === $currentYear) {
                $operationType = $operation->getOperationType();
                $subCategory = $operation->getSubCategory();

                $isExpense = ($operationType && $operationType->getType() === 'expenses')
                    || (!$operationType && $subCategory && $subCategory->getType() === 'expenses')
                    || (!$operationType && !$subCategory); // Default to expense if not specified

                if ($isExpense) {
                    $expenses += $operation->getAmount();
                }
            }
        }

        $daysPassed = (int) $now->format('d');

        return $daysPassed > 0 ? $expenses / $daysPassed : 0;
    }

    public function getRemainingDailyBudget(): float
    {
        $balance = $this->getBalance();
        $now = new \DateTime();
        $daysInMonth = (int) $now->format('t');
        $currentDay = (int) $now->format('d');
        $remainingDays = $daysInMonth - $currentDay + 1;

        return $remainingDays > 0 ? $balance / $remainingDays : 0;
    }
}
