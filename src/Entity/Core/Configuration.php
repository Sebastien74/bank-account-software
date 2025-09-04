<?php

declare(strict_types=1);

namespace App\Entity\Core;

use App\Entity\BaseEntity;
use App\Entity\Translation\TranslationDomain;
use App\Repository\Core\ConfigurationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * ConfigurationModel.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'core_configuration')]
#[ORM\Entity(repositoryClass: ConfigurationRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\AssociationOverrides([
    new ORM\AssociationOverride(
        name: 'transDomains',
        joinColumns: [new ORM\JoinColumn(name: 'configuration_id', referencedColumnName: 'id')],
        inverseJoinColumns: [new ORM\InverseJoinColumn(name: 'domain_id', referencedColumnName: 'id')],
        joinTable: new ORM\JoinTable(name: 'core_configuration_translation_domains')
    ),
])]
class Configuration extends BaseEntity
{
    private const array IPS_DEV = ['::1', '127.0.0.1', 'fe80::1', '194.51.155.21', '195.135.16.88', '176.135.112.19', '2a02:8440:5341:81fb:fd04:6bf3:c8c7:1edb', '88.173.106.115', '2001:861:43c3:ce70:bd5f:81d1:7710:888b', '2001:861:43c3:ce70:45e7:2aa7:ab50:c245'];

    /**
     * Configurations.
     */
    protected static array $interface = [
        'name' => 'configuration',
    ];

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    #[Assert\NotBlank]
    private ?string $template = 'default';

    #[ORM\Column(type: Types::STRING, length: 10, nullable: true)]
    #[Assert\NotBlank]
    private string $locale = 'fr';

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private array $locales = [];

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private array $onlineLocales = ['fr'];

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $asDefault = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $fullWidth = true;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $onlineStatus = true;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $seoStatus = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $mediasCategoriesStatus = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $mediasSecondary = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $duplicateMediasStatus = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $preloader = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $progressiveWebApp = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $scrollTopBtn = true;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $breadcrumb = true;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $subNavigation = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $collapsedAdminTrees = true;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $adminAdvertising = true;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Assert\NotBlank]
    private int $cacheExpiration = 120; // minutes

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Assert\NotBlank]
    private int $gdprFrequency = 1095;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $charset = 'UTF-8';

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $backgroundColor = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private array $emailsDev = ['dev@agence-felix.fr'];

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private array $emailsSupport = ['dev@agence-felix.fr', 'support@agence-felix.fr'];

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private array $ipsDev = self::IPS_DEV;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private array $ipsCustomer = [];

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private array $ipsBan = [];

    #[ORM\Column(type: Types::STRING, length: 30, nullable: true)]
    private ?string $adminTheme = 'default';

    #[ORM\Column(type: Types::STRING, length: 30, nullable: true)]
    private ?string $buildTheme = 'diagonals';

    #[ORM\OneToOne(mappedBy: 'configuration', targetEntity: Website::class, fetch: 'EAGER')]
    private ?Website $website = null;

    #[ORM\OneToMany(mappedBy: 'configuration', targetEntity: Domain::class, cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['locale' => 'ASC'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $domains;

    #[ORM\ManyToMany(targetEntity: TranslationDomain::class, cascade: ['persist'])]
    #[ORM\OrderBy(['adminName' => 'ASC'])]
    private ArrayCollection|PersistentCollection $transDomains;

    /**
     * ConfigurationModel constructor.
     */
    public function __construct()
    {
        $this->domains = new ArrayCollection();
    }

    /**
     * Get all IPS.
     */
    public function getAllIPS(array $ipsDev = [], array $ipsCustomer = []): ?array
    {
        $this->ipsDev = !empty($ipsDev) ? $ipsDev : $this->ipsDev;
        $this->ipsCustomer = !empty($ipsCustomer) ? $ipsCustomer : $this->ipsCustomer;

        $ipsDev = [];
        foreach ($this->ipsDev as $ip) {
            $matches = explode(',', $ip);
            foreach ($matches as $match) {
                $ipsDev[] = $match;
            }
        }

        $ipsCustomer = [];
        foreach ($this->ipsCustomer as $ip) {
            $matches = explode(',', $ip);
            foreach ($matches as $match) {
                $ipsCustomer[] = $match;
            }
        }

        $result = array_unique(array_merge($ipsDev, $ipsCustomer));

        return $result ?: self::IPS_DEV;
    }

    /**
     * Get all Locales.
     */
    public function getAllLocales(): ?array
    {
        $allLocales = [$this->locale];
        if (!empty($this->locales)) {
            $allLocales = array_merge($allLocales, $this->locales);
        }
        sort($allLocales);

        return array_unique($allLocales);
    }

    public function getTemplate(): ?string
    {
        return $this->template;
    }

    public function setTemplate(?string $template): static
    {
        $this->template = $template;

        return $this;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function setLocale(?string $locale): static
    {
        $this->locale = $locale;

        return $this;
    }

    public function getLocales(): ?array
    {
        return $this->locales;
    }

    public function setLocales(?array $locales): static
    {
        $this->locales = $locales;

        return $this;
    }

    public function getOnlineLocales(): ?array
    {
        return $this->onlineLocales;
    }

    public function setOnlineLocales(?array $onlineLocales): static
    {
        $this->onlineLocales = $onlineLocales;

        return $this;
    }

    public function isAsDefault(): ?bool
    {
        return $this->asDefault;
    }

    public function setAsDefault(bool $asDefault): static
    {
        $this->asDefault = $asDefault;

        return $this;
    }

    public function isFullWidth(): ?bool
    {
        return $this->fullWidth;
    }

    public function setFullWidth(bool $fullWidth): static
    {
        $this->fullWidth = $fullWidth;

        return $this;
    }

    public function isOnlineStatus(): ?bool
    {
        return $this->onlineStatus;
    }

    public function setOnlineStatus(bool $onlineStatus): static
    {
        $this->onlineStatus = $onlineStatus;

        return $this;
    }

    public function isSeoStatus(): ?bool
    {
        return $this->seoStatus;
    }

    public function setSeoStatus(bool $seoStatus): static
    {
        $this->seoStatus = $seoStatus;

        return $this;
    }

    public function isMediasCategoriesStatus(): ?bool
    {
        return $this->mediasCategoriesStatus;
    }

    public function setMediasCategoriesStatus(bool $mediasCategoriesStatus): static
    {
        $this->mediasCategoriesStatus = $mediasCategoriesStatus;

        return $this;
    }

    public function isMediasSecondary(): ?bool
    {
        return $this->mediasSecondary;
    }

    public function setMediasSecondary(bool $mediasSecondary): static
    {
        $this->mediasSecondary = $mediasSecondary;

        return $this;
    }

    public function isDuplicateMediasStatus(): ?bool
    {
        return $this->duplicateMediasStatus;
    }

    public function setDuplicateMediasStatus(bool $duplicateMediasStatus): static
    {
        $this->duplicateMediasStatus = $duplicateMediasStatus;

        return $this;
    }

    public function isPreloader(): ?bool
    {
        return $this->preloader;
    }

    public function setPreloader(bool $preloader): static
    {
        $this->preloader = $preloader;

        return $this;
    }

    public function isProgressiveWebApp(): ?bool
    {
        return $this->progressiveWebApp;
    }

    public function setProgressiveWebApp(bool $progressiveWebApp): static
    {
        $this->progressiveWebApp = $progressiveWebApp;

        return $this;
    }

    public function isScrollTopBtn(): ?bool
    {
        return $this->scrollTopBtn;
    }

    public function setScrollTopBtn(bool $scrollTopBtn): static
    {
        $this->scrollTopBtn = $scrollTopBtn;

        return $this;
    }

    public function isBreadcrumb(): ?bool
    {
        return $this->breadcrumb;
    }

    public function setBreadcrumb(bool $breadcrumb): static
    {
        $this->breadcrumb = $breadcrumb;

        return $this;
    }

    public function isSubNavigation(): ?bool
    {
        return $this->subNavigation;
    }

    public function setSubNavigation(bool $subNavigation): static
    {
        $this->subNavigation = $subNavigation;

        return $this;
    }

    public function isCollapsedAdminTrees(): ?bool
    {
        return $this->collapsedAdminTrees;
    }

    public function setCollapsedAdminTrees(bool $collapsedAdminTrees): static
    {
        $this->collapsedAdminTrees = $collapsedAdminTrees;

        return $this;
    }

    public function isAdminAdvertising(): ?bool
    {
        return $this->adminAdvertising;
    }

    public function setAdminAdvertising(bool $adminAdvertising): static
    {
        $this->adminAdvertising = $adminAdvertising;

        return $this;
    }

    public function getCacheExpiration(): ?int
    {
        return $this->cacheExpiration;
    }

    public function setCacheExpiration(?int $cacheExpiration): static
    {
        $this->cacheExpiration = $cacheExpiration;

        return $this;
    }

    public function getGdprFrequency(): ?int
    {
        return $this->gdprFrequency;
    }

    public function setGdprFrequency(?int $gdprFrequency): static
    {
        $this->gdprFrequency = $gdprFrequency;

        return $this;
    }

    public function getCharset(): ?string
    {
        return $this->charset;
    }

    public function setCharset(?string $charset): static
    {
        $this->charset = $charset;

        return $this;
    }

    public function getBackgroundColor(): ?string
    {
        return $this->backgroundColor;
    }

    public function setBackgroundColor(?string $backgroundColor): static
    {
        $this->backgroundColor = $backgroundColor;

        return $this;
    }

    public function getEmailsDev(): ?array
    {
        return $this->emailsDev;
    }

    public function setEmailsDev(?array $emailsDev): static
    {
        $this->emailsDev = $emailsDev;

        return $this;
    }

    public function getEmailsSupport(): ?array
    {
        return $this->emailsSupport;
    }

    public function setEmailsSupport(?array $emailsSupport): static
    {
        $this->emailsSupport = $emailsSupport;

        return $this;
    }

    public function getIpsDev(): ?array
    {
        if (empty($this->ipsDev)) {
            $this->ipsDev = self::IPS_DEV;
        }

        return $this->ipsDev;
    }

    public function setIpsDev(?array $ipsDev): static
    {
        $this->ipsDev = $ipsDev;

        return $this;
    }

    public function getIpsCustomer(): ?array
    {
        return $this->ipsCustomer;
    }

    public function setIpsCustomer(?array $ipsCustomer): static
    {
        $this->ipsCustomer = $ipsCustomer;

        return $this;
    }

    public function getIpsBan(): ?array
    {
        return $this->ipsBan;
    }

    public function setIpsBan(?array $ipsBan): static
    {
        $this->ipsBan = $ipsBan;

        return $this;
    }

    public function getAdminTheme(): ?string
    {
        return $this->adminTheme;
    }

    public function setAdminTheme(?string $adminTheme): static
    {
        $this->adminTheme = $adminTheme;

        return $this;
    }

    public function getBuildTheme(): ?string
    {
        return $this->buildTheme;
    }

    public function setBuildTheme(?string $buildTheme): static
    {
        $this->buildTheme = $buildTheme;

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
            $this->website->setConfiguration(null);
        }

        // set the owning side of the relation if necessary
        if ($website !== null && $website->getConfiguration() !== $this) {
            $website->setConfiguration($this);
        }

        $this->website = $website;

        return $this;
    }

    /**
     * @return Collection<int, Domain>
     */
    public function getDomains(): Collection
    {
        return $this->domains;
    }

    public function addDomain(Domain $domain): static
    {
        if (!$this->domains->contains($domain)) {
            $this->domains->add($domain);
            $domain->setConfiguration($this);
        }

        return $this;
    }

    public function removeDomain(Domain $domain): static
    {
        if ($this->domains->removeElement($domain)) {
            // set the owning side to null (unless already changed)
            if ($domain->getConfiguration() === $this) {
                $domain->setConfiguration(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, TranslationDomain>
     */
    public function getTransDomains(): Collection
    {
        return $this->transDomains;
    }

    public function addTransDomain(TranslationDomain $transDomain): static
    {
        if (!$this->transDomains->contains($transDomain)) {
            $this->transDomains->add($transDomain);
        }

        return $this;
    }

    public function removeTransDomain(TranslationDomain $transDomain): static
    {
        $this->transDomains->removeElement($transDomain);

        return $this;
    }
}
