<?php

declare(strict_types=1);

namespace App\Entity\Core;

use App\Entity\BaseEntity;
use App\Repository\Core\WebsiteRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * WebsiteModel.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'core_website')]
#[ORM\Entity(repositoryClass: WebsiteRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Website extends BaseEntity
{
    /**
     * Configurations.
     */
    protected static array $interface = [
        'name' => 'website',
    ];

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $active = false;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $uploadDirname = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $cacheClearDate = null;

    #[ORM\OneToOne(inversedBy: 'website', targetEntity: Security::class, cascade: ['persist', 'remove'], fetch: 'EAGER')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'cascade')]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ?Security $security = null;

    #[ORM\OneToOne(inversedBy: 'website', targetEntity: Configuration::class, cascade: ['persist', 'remove'], fetch: 'EAGER')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'cascade')]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ?Configuration $configuration = null;

    public function isActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;

        return $this;
    }

    public function getUploadDirname(): ?string
    {
        return $this->uploadDirname;
    }

    public function setUploadDirname(string $uploadDirname): static
    {
        $this->uploadDirname = $uploadDirname;

        return $this;
    }

    public function getCacheClearDate(): ?\DateTimeInterface
    {
        return $this->cacheClearDate;
    }

    public function setCacheClearDate(?\DateTimeInterface $cacheClearDate): static
    {
        $this->cacheClearDate = $cacheClearDate;

        return $this;
    }

    public function getSecurity(): ?Security
    {
        return $this->security;
    }

    public function setSecurity(?Security $security): static
    {
        $this->security = $security;

        return $this;
    }

    public function getConfiguration(): ?Configuration
    {
        return $this->configuration;
    }

    public function setConfiguration(?Configuration $configuration): static
    {
        $this->configuration = $configuration;

        return $this;
    }
}
