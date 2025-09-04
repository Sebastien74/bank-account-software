<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Exception;

/**
 * BaseInterface.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\MappedSuperclass]
#[ORM\HasLifecycleCallbacks]
class BaseInterface extends BaseUserAction
{
    /**
     * Configurations.
     */
    protected static string $masterField = '';
    protected static string $parentMasterField = '';
    protected static array $interface = [];
    protected static array $labels = [];

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $updatedAt = null;

    /**
     * @throws Exception
     */
    #[ORM\PrePersist]
    public function prePersist(): void
    {
        $this->createdAt = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
        parent::prePersist();
    }

    /**
     * @throws Exception
     */
    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
        parent::prePersist();
    }

    public static function getMasterField(): ?string
    {
        return static::$masterField;
    }

    public static function getParentMasterField(): ?string
    {
        return static::$parentMasterField;
    }

    public static function getInterface(): array
    {
        return static::$interface;
    }

    public static function getLabels(): array
    {
        return static::$labels;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt = null): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt = null): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }
}
