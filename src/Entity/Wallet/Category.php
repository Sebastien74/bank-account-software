<?php

declare(strict_types=1);

namespace App\Entity\Wallet;

use App\Entity\BaseEntity;
use App\Repository\Wallet\CategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Category.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'wallet_category')]
#[ORM\Entity(repositoryClass: CategoryRepository::class)]
class Category extends BaseEntity
{
    /**
     * Configurations.
     */
    protected static array $interface = [
        'name' => 'category',
        'masterField' => 'categorytype',
    ];

    protected static array $buttons = [
        'admin_subcategory_index' => 'adminName',
    ];

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $icon = null;

    #[ORM\OneToMany(mappedBy: 'category', targetEntity: SubCategory::class, cascade: ['persist', 'remove'], fetch: 'EAGER', orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $subCategories;

    #[ORM\ManyToOne(targetEntity: CategoryType::class, cascade: ['persist'], inversedBy: 'categories')]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    private ?CategoryType $categorytype = null;

    public function __construct()
    {
        $this->subCategories = new ArrayCollection();
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
            $subCategory->setCategory($this);
        }

        return $this;
    }

    public function removeSubCategory(SubCategory $subCategory): static
    {
        if ($this->subCategories->removeElement($subCategory)) {
            // set the owning side to null (unless already changed)
            if ($subCategory->getCategory() === $this) {
                $subCategory->setCategory(null);
            }
        }

        return $this;
    }

    public function getCategorytype(): ?CategoryType
    {
        return $this->categorytype;
    }

    public function setCategorytype(?CategoryType $categorytype): static
    {
        $this->categorytype = $categorytype;

        return $this;
    }
}
