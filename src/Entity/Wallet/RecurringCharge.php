<?php

declare(strict_types=1);

namespace App\Entity\Wallet;

use App\Entity\BaseEntity;
use App\Repository\Wallet\RecurringChargeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * RecurringCharge.
 *
 * Réglage d'une charge mensuelle détectée dans l'historique.
 *
 * La détection reste automatique : cette table ne liste pas les charges, elle
 * ne porte que ce que l'utilisateur corrige. Une charge sans ligne ici est
 * annoncée telle que l'historique la décrit ; une ligne la désactive ou fixe
 * son montant.
 *
 * C'est ce qui permet de garder une détection simple : elle n'a pas à deviner
 * qu'un abonnement a été résilié ni qu'un tarif a changé, l'utilisateur le
 * sait et le dit une fois.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'wallet_recurring_charge')]
#[ORM\UniqueConstraint(name: 'charge_par_compte', columns: ['wallet_id', 'slug'])]
#[ORM\Entity(repositoryClass: RecurringChargeRepository::class)]
class RecurringCharge extends BaseEntity
{
    /**
     * Configurations.
     */
    protected static array $interface = [
        'name' => 'recurringcharge',
    ];

    #[ORM\ManyToOne(targetEntity: Wallet::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Wallet $wallet = null;

    /** Montant attendu. Nul : l'estimation tirée de l'historique s'applique. */
    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    private ?float $amount = null;

    /** Jour d'échéance. Nul : le jour médian observé s'applique. */
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $dueDay = null;

    /** Fausse : la charge n'est plus annoncée, sans effacer son historique. */
    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    private bool $active = true;

    /**
     * Périodicité.
     *
     * Toutes les échéances ne tombent pas tous les mois : un abonnement annuel
     * passe une fois l'an, au même mois. Annoncé chaque mois il serait faux
     * onze fois sur douze ; jamais annoncé, il tombe sans prévenir.
     */
    public const string MONTHLY = 'monthly';
    public const string YEARLY = 'yearly';

    /**
     * Echeancier a duree determinee : un paiement en quatre fois.
     *
     * Ce n'est pas une charge mensuelle qu'on penserait a desactiver le jour
     * venu : elle s'arrete d'elle-meme au terme annonce. Sans ce cas, un
     * paiement fractionne saisi en mensuel restait annonce indefiniment.
     */
    public const string INSTALMENTS = 'instalments';

    #[ORM\Column(type: Types::STRING, length: 16, options: ['default' => self::MONTHLY])]
    private string $frequency = self::MONTHLY;

    /** Nombre d'echeances d'un echeancier. Nul hors echeancier. */
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $instalments = null;

    /** Mois de la premiere echeance d'un echeancier, au premier du mois. */
    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $startsOn = null;

    /**
     * Tiers et poste, comme sur une operation.
     *
     * Une charge saisie portait son tiers et sa categorie en texte libre :
     * deux orthographes faisaient deux charges, et l'encaisser par avance la
     * rangeait dans les non classees faute de poste identifiable.
     */
    #[ORM\ManyToOne(targetEntity: Outsider::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Outsider $outsider = null;

    #[ORM\ManyToOne(targetEntity: SubCategory::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?SubCategory $subCategory = null;

    /** Mois d'échéance, pour une charge annuelle. Nul si mensuelle. */
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $dueMonth = null;

    /**
     * Vraie : la charge a été saisie à la main, elle n'existe pas dans
     * l'historique. Une charge détectée, elle, revient toute seule si on
     * efface son réglage ; celle-ci disparaîtrait pour de bon.
     */
    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $manual = false;

    /** Libellé du poste, pour une charge saisie : rien ne le déduit. */
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $category = null;

    public function getWallet(): ?Wallet
    {
        return $this->wallet;
    }

    public function setWallet(?Wallet $wallet): static
    {
        $this->wallet = $wallet;

        return $this;
    }

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function setAmount(?float $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    public function getDueDay(): ?int
    {
        return $this->dueDay;
    }

    public function setDueDay(?int $dueDay): static
    {
        $this->dueDay = $dueDay;

        return $this;
    }

    public function getFrequency(): string
    {
        return $this->frequency;
    }

    public function setFrequency(string $frequency): static
    {
        $this->frequency = in_array($frequency, [self::YEARLY, self::INSTALMENTS], true)
            ? $frequency
            : self::MONTHLY;

        return $this;
    }

    public function isYearly(): bool
    {
        return self::YEARLY === $this->frequency;
    }

    public function hasInstalments(): bool
    {
        return self::INSTALMENTS === $this->frequency;
    }

    public function getInstalments(): ?int
    {
        return $this->instalments;
    }

    public function setInstalments(?int $instalments): static
    {
        $this->instalments = null !== $instalments && $instalments > 0 ? $instalments : null;

        return $this;
    }

    public function getStartsOn(): ?\DateTimeInterface
    {
        return $this->startsOn;
    }

    public function setStartsOn(?\DateTimeInterface $startsOn): static
    {
        $this->startsOn = $startsOn;

        return $this;
    }

    public function getOutsider(): ?Outsider
    {
        return $this->outsider;
    }

    public function setOutsider(?Outsider $outsider): static
    {
        $this->outsider = $outsider;

        return $this;
    }

    public function getSubCategory(): ?SubCategory
    {
        return $this->subCategory;
    }

    public function setSubCategory(?SubCategory $subCategory): static
    {
        $this->subCategory = $subCategory;

        return $this;
    }

    /**
     * Rang de l'echeance attendue sur ce mois, a partir de 1, ou null.
     *
     * Sert autant au calcul qu'a l'affichage : « 2 / 4 » dit a l'utilisateur
     * ou il en est, ce qu'un simple « attendue » ne dit pas.
     */
    public function instalmentNumber(int $year, int $month): ?int
    {
        if (!$this->hasInstalments() || !$this->startsOn instanceof \DateTimeInterface || null === $this->instalments) {
            return null;
        }

        $start = (int) $this->startsOn->format('Y') * 12 + (int) $this->startsOn->format('n');
        $rank = $year * 12 + $month - $start + 1;

        return $rank >= 1 && $rank <= $this->instalments ? $rank : null;
    }

    public function getDueMonth(): ?int
    {
        return $this->dueMonth;
    }

    public function setDueMonth(?int $dueMonth): static
    {
        $this->dueMonth = $dueMonth;

        return $this;
    }

    /**
     * La charge est-elle attendue sur ce mois ?
     *
     * L'annee est necessaire : un paiement en quatre fois demarre en novembre
     * court jusqu'en fevrier, et un simple numero de mois ne sait pas dire si
     * ce fevrier-la est le bon.
     */
    public function isDueOn(int $year, int $month): bool
    {
        if (!$this->active) {
            return false;
        }

        if ($this->hasInstalments()) {
            return null !== $this->instalmentNumber($year, $month);
        }

        return !$this->isYearly() || $month === $this->dueMonth;
    }

    public function isManual(): bool
    {
        return $this->manual;
    }

    public function setManual(bool $manual): static
    {
        $this->manual = $manual;

        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;

        return $this;
    }
}
