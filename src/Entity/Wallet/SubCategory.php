<?php

declare(strict_types=1);

namespace App\Entity\Wallet;

use App\Entity\BaseEntity;
use App\Repository\Wallet\SubCategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * SubCategory.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'wallet_sub_category')]
#[ORM\Entity(repositoryClass: SubCategoryRepository::class)]
class SubCategory extends BaseEntity
{
    /**
     * Configurations.
     */
    protected static array $interface = [
        'name' => 'subcategory',
        'masterField' => 'category',
            'columns' => [
            'budgets' => ['label' => 'Budget', 'format' => 'names'],
        ],
];

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true)]
    private ?string $type = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $icon = null;

    #[ORM\ManyToOne(targetEntity: Category::class, cascade: ['persist'], inversedBy: 'subCategories')]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    private ?Category $category = null;

    /**
     * Enveloppes qui couvrent ce sous-poste.
     *
     * Cote inverse, pour que les index puissent dire a quel budget un poste
     * appartient : sans lui, il faudrait interroger les budgets un par un.
     *
     * @var Collection<int, Budget>
     */
    #[ORM\ManyToMany(targetEntity: Budget::class, mappedBy: 'subCategories')]
    private Collection $budgets;

    #[ORM\PrePersist]
    public function prePersist(): void
    {
        $this->type = $this->category->getType();

        parent::prePersist();
    }

    /**
     * SubCategory constructor.
     */
    public function __construct()
    {
        $this->budgets = new ArrayCollection();
    }

    /**
     * @return Collection<int, Budget>
     */
    public function getBudgets(): Collection
    {
        return $this->budgets;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(?string $icon): static
    {
        $this->icon = $icon;

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
