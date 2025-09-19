<?php

declare(strict_types=1);

namespace App\Entity\Wallet;

use App\Entity\BaseEntity;
use App\Repository\Wallet\SubCategoryRepository;
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
    ];

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $icon = null;

    #[ORM\ManyToOne(targetEntity: Category::class, cascade: ['persist'], inversedBy: 'subCategories')]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    private ?Category $category = null;

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
