<?php

declare(strict_types=1);

namespace App\Entity\Security;

use App\Entity\BaseSecurity;
use App\Repository\Security\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * User.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'security_user')]
#[ORM\Entity(repositoryClass: UserRepository::class)]
class User extends BaseSecurity
{
    /**
     * Configurations.
     */
    protected static string $masterField = '';
    protected static array $interface = [
        'name' => 'user',
    ];
    protected static array $labels = [];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
    protected ?string $theme = null;

    /**
     * User constructor.
     */
    public function __construct()
    {
        $this->companies = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTheme(): ?string
    {
        return $this->theme;
    }

    public function setTheme(?string $theme): static
    {
        $this->theme = $theme;

        return $this;
    }
}
