<?php

declare(strict_types=1);

namespace App\Entity\Wallet;

use App\Entity\BaseEntity;
use App\Entity\Wallet\OperationType as EntityOperationType;
use App\Repository\Wallet\OperationRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Operation.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'wallet_operation')]
#[ORM\Entity(repositoryClass: OperationRepository::class)]
class Operation extends BaseEntity
{
    /**
     * Configurations.
     */
    protected static array $interface = [
        'name' => 'operation',
        'masterField' => 'wallet',
        'orderBy' => 'date',
        'orderSort' => 'ASC',
    ];

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(type: 'boolean')]
    private bool $pointed = false;

    #[ORM\Column(type: 'float')]
    private ?float $amount = null;

    #[ORM\ManyToOne(targetEntity: Wallet::class, cascade: ['persist'], inversedBy: 'operations')]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    private ?Wallet $wallet = null;

    #[ORM\ManyToOne(targetEntity: EntityOperationType::class)]
    #[ORM\JoinColumn(name: 'operation_type_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?EntityOperationType $operationType = null;

    #[ORM\ManyToOne(targetEntity: SubCategory::class)]
    #[ORM\JoinColumn(name: 'sub_category_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?SubCategory $subCategory = null;

    #[ORM\ManyToOne(targetEntity: Outsider::class)]
    #[ORM\JoinColumn(name: 'outsider_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Outsider $outsider = null;

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(?\DateTimeInterface $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function isPointed(): ?bool
    {
        return $this->pointed;
    }

    public function setPointed(bool $pointed): static
    {
        $this->pointed = $pointed;

        return $this;
    }

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    public function getWallet(): ?Wallet
    {
        return $this->wallet;
    }

    public function setWallet(?Wallet $wallet): static
    {
        $this->wallet = $wallet;

        return $this;
    }

    public function getSubCategory(): ?SubCategory
    {
        return $this->subCategory;
    }

    public function setSubCategory(?SubCategory $subCategory): static
    {
        $this->subCategory = $subCategory;

        return $this;
    }

    public function getOperationType(): ?EntityOperationType
    {
        return $this->operationType;
    }

    public function setOperationType(?EntityOperationType $operationType): static
    {
        $this->operationType = $operationType;

        return $this;
    }

    public function getOutsider(): ?Outsider
    {
        return $this->outsider;
    }

    public function setOutsider(?Outsider $outsider): static
    {
        $this->outsider = $outsider;

        return $this;
    }
}
