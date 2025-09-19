<?php

declare(strict_types=1);

namespace App\Entity\Wallet;

use App\Entity\BaseEntity;
use App\Repository\Wallet\CategoryTypeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * CategoryType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'wallet_category_type')]
#[ORM\Entity(repositoryClass: CategoryTypeRepository::class)]
class CategoryType extends BaseEntity
{
    /**
     * Configurations.
     */
    protected static array $interface = [
        'name' => 'categorytype',
    ];

    protected static array $buttons = [
        'admin_category_index' => 'adminName',
    ];

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $icon = null;

    #[ORM\OneToMany(mappedBy: 'categorytype', targetEntity: Category::class, cascade: ['persist', 'remove'], fetch: 'EAGER', orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $categories;

    public function __construct()
    {
        $this->categories = new ArrayCollection();
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
     * @return Collection<int, Category>
     */
    public function getCategories(): Collection
    {
        return $this->categories;
    }

    public function addCategory(Category $category): static
    {
        if (!$this->categories->contains($category)) {
            $this->categories->add($category);
            $category->setType($this);
        }

        return $this;
    }

    public function removeCategory(Category $category): static
    {
        if ($this->categories->removeElement($category)) {
            // set the owning side to null (unless already changed)
            if ($category->getType() === $this) {
                $category->setType(null);
            }
        }

        return $this;
    }
}
