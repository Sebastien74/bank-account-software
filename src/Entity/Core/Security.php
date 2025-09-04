<?php

declare(strict_types=1);

namespace App\Entity\Core;

use App\Entity\BaseInterface;
use App\Entity\Layout\Page;
use App\Repository\Core\SecurityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Security.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'core_security')]
#[ORM\Entity(repositoryClass: SecurityRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Security extends BaseInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 500)]
    private ?string $securityKey = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    protected array $headerData = [
        'strict-transport-security',
        'permissions-policy',
        'content-security-policy',
        'referrer-policy',
        'cross-origin-embedder-policy',
        'cross-origin-resource-policy',
        'x-xss-protection',
        'x-ua-compatible',
        'content-type-options-nosniff',
        'x-frame-options-sameorigin',
        'x-permitted-cross-domain-policies',
        'cross-origin-opener-policy',
        'access-control-allow-origin',
    ];

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $secureWebsite = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $resetPasswordsByGroup = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $adminRegistration = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $adminRegistrationValidation = true;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $adminPasswordSecurity = false;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Assert\NotBlank]
    private int $adminPasswordDelay = 365;

    #[ORM\OneToOne(mappedBy: 'security', targetEntity: Website::class)]
    private ?Website $website = null;

    /**
     * @throws \Exception
     */
    #[ORM\PrePersist]
    public function prePersist(): void
    {
        $this->securityKey = str_replace(['/', '.'], '', crypt(random_bytes(30), 'rl'));
        parent::prePersist();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSecurityKey(): ?string
    {
        return $this->securityKey;
    }

    public function setSecurityKey(string $securityKey): static
    {
        $this->securityKey = $securityKey;

        return $this;
    }

    public function getHeaderData(): ?array
    {
        return $this->headerData;
    }

    public function setHeaderData(?array $headerData): static
    {
        $this->headerData = $headerData;

        return $this;
    }

    public function isSecureWebsite(): ?bool
    {
        return $this->secureWebsite;
    }

    public function setSecureWebsite(bool $secureWebsite): static
    {
        $this->secureWebsite = $secureWebsite;

        return $this;
    }

    public function isResetPasswordsByGroup(): ?bool
    {
        return $this->resetPasswordsByGroup;
    }

    public function setResetPasswordsByGroup(bool $resetPasswordsByGroup): static
    {
        $this->resetPasswordsByGroup = $resetPasswordsByGroup;

        return $this;
    }

    public function isAdminRegistration(): ?bool
    {
        return $this->adminRegistration;
    }

    public function setAdminRegistration(bool $adminRegistration): static
    {
        $this->adminRegistration = $adminRegistration;

        return $this;
    }

    public function isAdminRegistrationValidation(): ?bool
    {
        return $this->adminRegistrationValidation;
    }

    public function setAdminRegistrationValidation(bool $adminRegistrationValidation): static
    {
        $this->adminRegistrationValidation = $adminRegistrationValidation;

        return $this;
    }

    public function isAdminPasswordSecurity(): ?bool
    {
        return $this->adminPasswordSecurity;
    }

    public function setAdminPasswordSecurity(bool $adminPasswordSecurity): static
    {
        $this->adminPasswordSecurity = $adminPasswordSecurity;

        return $this;
    }

    public function getAdminPasswordDelay(): ?int
    {
        return $this->adminPasswordDelay;
    }

    public function setAdminPasswordDelay(?int $adminPasswordDelay): static
    {
        $this->adminPasswordDelay = $adminPasswordDelay;

        return $this;
    }

    public function getWebsite(): ?Website
    {
        return $this->website;
    }

    public function setWebsite(?Website $website): static
    {
        // unset the owning side of the relation if necessary
        if ($website === null && $this->website !== null) {
            $this->website->setSecurity(null);
        }

        // set the owning side of the relation if necessary
        if ($website !== null && $website->getSecurity() !== $this) {
            $website->setSecurity($this);
        }

        $this->website = $website;

        return $this;
    }
}
