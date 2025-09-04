<?php

declare(strict_types=1);

namespace App\Entity\Security;

use App\Entity\BaseEntity;
use App\Repository\Security\ProfileRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Profile.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'security_profile')]
#[ORM\Entity(repositoryClass: ProfileRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Profile extends BaseEntity
{
    /**
     * Configurations.
     */
    protected static array $interface = [
        'name' => 'profile',
    ];

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    protected ?string $gender = null;

    #[ORM\OneToOne(mappedBy: 'profile', targetEntity: User::class, cascade: ['persist', 'remove'])]
    private ?User $user = null;

    public function getGender(): ?string
    {
        return $this->gender;
    }

    public function setGender(?string $gender): static
    {
        $this->gender = $gender;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        // unset the owning side of the relation if necessary
        if ($user === null && $this->user !== null) {
            $this->user->setProfile(null);
        }

        // set the owning side of the relation if necessary
        if ($user !== null && $user->getProfile() !== $this) {
            $user->setProfile($this);
        }

        $this->user = $user;

        return $this;
    }
}
