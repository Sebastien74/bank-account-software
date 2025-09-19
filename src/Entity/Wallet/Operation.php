<?php

declare(strict_types=1);

namespace App\Entity\Wallet;

use App\Entity\BaseEntity;
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
    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(type: 'boolean')]
    private bool $pointed = false;

    #[ORM\ManyToOne(targetEntity: Wallet::class, cascade: ['persist'], inversedBy: 'operations')]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    private ?Wallet $wallet = null;

    #[ORM\ManyToOne(targetEntity: Category::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Category $category = null;

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

    public function getWallet(): ?Wallet
    {
        return $this->wallet;
    }

    public function setWallet(?Wallet $wallet): static
    {
        $this->wallet = $wallet;

        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;

        return $this;
    }
}
