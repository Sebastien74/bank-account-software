<?php

declare(strict_types=1);

namespace App\Entity\Wallet;

use App\Entity\BaseEntity;
use App\Repository\Wallet\WalletRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Wallet.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'wallet')]
#[ORM\Entity(repositoryClass: WalletRepository::class)]
class Wallet extends BaseEntity
{
    /**
     * Configurations.
     */
    protected static array $interface = [
        'name' => 'wallet',
    ];

    #[ORM\OneToMany(mappedBy: 'wallet', targetEntity: Operation::class, cascade: ['persist', 'remove'], fetch: 'EAGER', orphanRemoval: true)]
    #[ORM\OrderBy(['date' => 'DESC'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $operations;

    public function __construct()
    {
        $this->operations = new ArrayCollection();
    }

    /**
     * @return Collection<int, Operation>
     */
    public function getOperations(): Collection
    {
        return $this->operations;
    }

    public function addOperation(Operation $operation): static
    {
        if (!$this->operations->contains($operation)) {
            $this->operations->add($operation);
            $operation->setWallet($this);
        }

        return $this;
    }

    public function removeOperation(Operation $operation): static
    {
        if ($this->operations->removeElement($operation)) {
            // set the owning side to null (unless already changed)
            if ($operation->getWallet() === $this) {
                $operation->setWallet(null);
            }
        }

        return $this;
    }
}
