<?php

declare(strict_types=1);

namespace App\Entity\Wallet;

use App\Entity\BaseEntity;
use App\Repository\Wallet\BudgetRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Budget.
 *
 * Enveloppe mensuelle pour un poste de dépenses.
 *
 * Le budget porte sur la catégorie, pas sur la sous-catégorie : à trente-deux
 * postes, un tableau de bord ne se lit plus, et une enveloppe de deux euros ne
 * dit rien. La catégorie est le niveau où une décision se prend.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'wallet_budget')]
#[ORM\Entity(repositoryClass: BudgetRepository::class)]
class Budget extends BaseEntity
{
    /**
     * Configurations.
     */
    protected static array $interface = [
        'name' => 'budget',
        'columns' => [
            'amount' => ['label' => 'Montant mensuel', 'format' => 'amount'],
        ],
    ];

    #[ORM\ManyToOne(targetEntity: Wallet::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Wallet $wallet = null;

    /**
     * Postes couverts par l'enveloppe.
     *
     * Au niveau de la sous-categorie, pas de la categorie. « Vie courante et
     * sante » melange 310 EUR d'alimentaire et 13 EUR de coiffeur : une
     * enveloppe commune ne dit rien de l'un ni de l'autre. Le regroupement se
     * decide ici, enveloppe par enveloppe, au lieu d'etre impose par
     * l'arborescence des categories.
     *
     * Une sous-categorie ne doit appartenir qu'a une enveloppe, sans quoi sa
     * depense serait comptee deux fois dans le total.
     *
     * @var Collection<int, SubCategory>
     */
    #[ORM\ManyToMany(targetEntity: SubCategory::class, inversedBy: 'budgets')]
    #[ORM\JoinTable(name: 'wallet_budget_sub_category')]
    #[ORM\OrderBy(['adminName' => 'ASC'])]
    private Collection $subCategories;

    #[ORM\Column(type: Types::FLOAT, options: ['default' => 0])]
    private float $amount = 0.0;

    /**
     * Une enveloppe mise de côté reste visible mais sort des totaux : la
     * supprimer effacerait l'arbitrage qui l'a fixée.
     */
    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    private bool $active = true;

    /**
     * Enveloppe de mise de cote.
     *
     * L'argent en sort du compte comme une depense, mais il n'est pas depense :
     * il est garde. La lecture s'inverse donc — remplir l'enveloppe est le
     * but, et rester en dessous est le manquement.
     */
    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $saving = false;

    public function getWallet(): ?Wallet
    {
        return $this->wallet;
    }

    public function setWallet(?Wallet $wallet): static
    {
        $this->wallet = $wallet;

        return $this;
    }

    /**
     * Budget constructor.
     */
    public function __construct()
    {
        $this->subCategories = new ArrayCollection();
    }

    /**
     * @return Collection<int, SubCategory>
     */
    public function getSubCategories(): Collection
    {
        return $this->subCategories;
    }

    public function addSubCategory(SubCategory $subCategory): static
    {
        if (!$this->subCategories->contains($subCategory)) {
            $this->subCategories->add($subCategory);
        }

        return $this;
    }

    public function removeSubCategory(SubCategory $subCategory): static
    {
        $this->subCategories->removeElement($subCategory);

        return $this;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    public function isSaving(): bool
    {
        return $this->saving;
    }

    public function setSaving(bool $saving): static
    {
        $this->saving = $saving;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;

        return $this;
    }
}
