<?php

declare(strict_types=1);

namespace App\Entity\Module\Faq;

use App\Entity\BaseEntity;
use App\Entity\Core\Website;
use App\Repository\Module\Faq\FaqRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Faq.
 *
 * @author Sébastien FOURNIER <contact@sebastien-fournier.com>
 */
#[ORM\Table(name: 'module_faq')]
#[ORM\Entity(repositoryClass: FaqRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Faq extends BaseEntity
{
    /**
     * Configurations.
     */
    protected static string $masterField = 'website';
    protected static array $interface = [
        'name' => 'faq',
        'search' => true,
        'prePersistTitle' => false,
        'buttons' => [
            'questions' => 'admin_faqquestion_index',
        ],
    ];
    protected static array $labels = [
        'admin_faqquestion_index' => 'Questions',
    ];

    #[ORM\Column(type: Types::BOOLEAN, length: 255)]
    protected bool $disabledMicrodata = true;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    protected ?string $display = 'all-closed';

    #[ORM\OneToMany(mappedBy: 'faq', targetEntity: Question::class, cascade: ['persist'], orphanRemoval: true)]
    private ArrayCollection|PersistentCollection $questions;

    #[ORM\ManyToOne(targetEntity: Website::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Website $website = null;

    #[ORM\OneToMany(mappedBy: 'faq', targetEntity: FaqIntl::class, cascade: ['persist'], orphanRemoval: true)]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    #[ORM\OrderBy(['locale' => 'ASC'])]
    #[Assert\Valid]
    private ArrayCollection|PersistentCollection $intls;

    /**
     * Faq constructor.
     */
    public function __construct()
    {
        $this->questions = new ArrayCollection();
        $this->intls = new ArrayCollection();
    }

    public function isDisabledMicrodata(): ?bool
    {
        return $this->disabledMicrodata;
    }

    public function setDisabledMicrodata(bool $disabledMicrodata): static
    {
        $this->disabledMicrodata = $disabledMicrodata;

        return $this;
    }

    public function getDisplay(): ?string
    {
        return $this->display;
    }

    public function setDisplay(?string $display): static
    {
        $this->display = $display;

        return $this;
    }

    /**
     * @return Collection<int, Question>
     */
    public function getQuestions(): Collection
    {
        return $this->questions;
    }

    public function addQuestion(Question $question): static
    {
        if (!$this->questions->contains($question)) {
            $this->questions->add($question);
            $question->setFaq($this);
        }

        return $this;
    }

    public function removeQuestion(Question $question): static
    {
        if ($this->questions->removeElement($question)) {
            // set the owning side to null (unless already changed)
            if ($question->getFaq() === $this) {
                $question->setFaq(null);
            }
        }

        return $this;
    }

    public function getWebsite(): ?Website
    {
        return $this->website;
    }

    public function setWebsite(?Website $website): static
    {
        $this->website = $website;

        return $this;
    }

    /**
     * @return Collection<int, FaqIntl>
     */
    public function getIntls(): Collection
    {
        return $this->intls;
    }

    public function addIntl(FaqIntl $intl): static
    {
        if (!$this->intls->contains($intl)) {
            $this->intls->add($intl);
            $intl->setFaq($this);
        }

        return $this;
    }

    public function removeIntl(FaqIntl $intl): static
    {
        if ($this->intls->removeElement($intl)) {
            // set the owning side to null (unless already changed)
            if ($intl->getFaq() === $this) {
                $intl->setFaq(null);
            }
        }

        return $this;
    }
}
