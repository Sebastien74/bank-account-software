<?php

declare(strict_types=1);

namespace App\Entity\Module\Catalog;

use App\Entity\BaseEntity;
use App\Repository\Module\Catalog\SubCategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * SubCategory.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'module_catalog_sub_category')]
#[ORM\Entity(repositoryClass: SubCategoryRepository::class)]
#[ORM\HasLifecycleCallbacks]
class SubCategory extends BaseEntity
{
    /**
     * Configurations.
     */
    protected static string $masterField = 'catalogcategory';
    protected static array $interface = [
        'name' => 'catalogsubcategory',
    ];

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $promote = false;

    #[ORM\OneToMany(mappedBy: 'subCategory', targetEntity: SubCategoryMediaRelation::class, cascade: ['persist'], orphanRemoval: true)]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    #[ORM\OrderBy(['position' => 'ASC', 'locale' => 'ASC'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $mediaRelations;

    #[ORM\OneToMany(mappedBy: 'subCategory', targetEntity: SubCategoryIntl::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['locale' => 'ASC'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $intls;

    #[ORM\ManyToOne(targetEntity: Category::class, cascade: ['persist'], inversedBy: 'subCategories')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Category $catalogcategory = null;

    /**
     * SubCategory constructor.
     */
    public function __construct()
    {
        $this->mediaRelations = new ArrayCollection();
        $this->intls = new ArrayCollection();
    }

    public function isPromote(): ?bool
    {
        return $this->promote;
    }

    public function setPromote(bool $promote): static
    {
        $this->promote = $promote;

        return $this;
    }

    /**
     * @return Collection<int, SubCategoryMediaRelation>
     */
    public function getMediaRelations(): Collection
    {
        return $this->mediaRelations;
    }

    public function addMediaRelation(SubCategoryMediaRelation $mediaRelation): static
    {
        if (!$this->mediaRelations->contains($mediaRelation)) {
            $this->mediaRelations->add($mediaRelation);
            $mediaRelation->setSubCategory($this);
        }

        return $this;
    }

    public function removeMediaRelation(SubCategoryMediaRelation $mediaRelation): static
    {
        if ($this->mediaRelations->removeElement($mediaRelation)) {
            // set the owning side to null (unless already changed)
            if ($mediaRelation->getSubCategory() === $this) {
                $mediaRelation->setSubCategory(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, SubCategoryIntl>
     */
    public function getIntls(): Collection
    {
        return $this->intls;
    }

    public function addIntl(SubCategoryIntl $intl): static
    {
        if (!$this->intls->contains($intl)) {
            $this->intls->add($intl);
            $intl->setSubCategory($this);
        }

        return $this;
    }

    public function removeIntl(SubCategoryIntl $intl): static
    {
        if ($this->intls->removeElement($intl)) {
            // set the owning side to null (unless already changed)
            if ($intl->getSubCategory() === $this) {
                $intl->setSubCategory(null);
            }
        }

        return $this;
    }

    public function getCatalogcategory(): ?Category
    {
        return $this->catalogcategory;
    }

    public function setCatalogcategory(?Category $catalogcategory): static
    {
        $this->catalogcategory = $catalogcategory;

        return $this;
    }
}
