<?php

declare(strict_types=1);

namespace App\Form\Manager\Core;

use App\Entity\Core\Website;
use App\Service\Core\InterfaceHelper;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * BaseManager.
 *
 * Manage form
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => BaseManager::class, 'key' => 'core_base_form_manager'],
])]
class BaseManager
{
    /**
     * BaseManager constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly InterfaceHelper $interfaceHelper,
    ) {
    }

    /**
     * @prePersist
     *
     * @throws MappingException
     */
    public function prePersist(mixed $entity, Website $website): void
    {
        $allLocales = $this->getAllLocales($website);
        $intls = $entity->getIntls();
        if (method_exists($entity, 'setWebsite')) {
            $entity->setWebsite($website);
        }
        $this->addIntls($allLocales, $website, $intls, $entity);
        $this->setTitleForce($intls);
    }

    /**
     * Set videos.
     *
     * @throws NonUniqueResultException
     * @throws MappingException
     */
    public function setVideos(Website $website, string $classname, mixed $entity, array $interface): void
    {
        $interfaceVideo = $this->interfaceHelper->generate($classname);
        $masterField = !empty($interfaceVideo['masterField']) ? $interfaceVideo['masterField'] : $interface['name'];
        $position = count($this->coreLocator->em()->getRepository($classname)->findBy([$masterField => $entity])) + 1;
        $allLocales = $this->getAllLocales($website);

        foreach ($allLocales as $locale) {
            foreach ($entity->getVideos() as $video) {
                $existing = false;
                foreach ($video->getIntls() as $intl) {
                    if ($intl->getLocale() === $locale) {
                        $existing = true;
                        break;
                    }
                }
                if (!$existing) {
                    $intlData = $this->coreLocator->metadata($video, 'intls');
                    $intl = new ($intlData->targetEntity)();
                    $intl->setLocale($locale);
                    $intl->setVideo($video->getAdminName());
                    $intl->setWebsite($website);
                    if (method_exists($intl, $intlData->setter)) {
                        $setter = $intlData->setter;
                        $intl->$setter($video);
                    }
                    $video->addIntl($intl);
                }
                if (!$video->getPosition()) {
                    $video->setPosition($position);
                    ++$position;
                }
                $this->coreLocator->em()->persist($video);
            }
        }
    }

    /**
     * Add intls.
     *
     * @throws MappingException
     */
    private function addIntls(array $allLocales, Website $website, Collection $intls, mixed $entity): void
    {
        foreach ($allLocales as $locale) {
            $existing = $this->existingLocale($locale, $intls);
            if (!$existing) {
                $intlData = $this->coreLocator->metadata($entity, 'intls');
                $intl = new ($intlData->targetEntity)();
                $intl->setLocale($locale);
                $intl->setWebsite($website);
                if (method_exists($intl, $intlData->setter)) {
                    $setter = $intlData->setter;
                    $intl->$setter($entity);
                }
                $entity->addIntl($intl);
                $this->coreLocator->em()->persist($entity);
            }
        }
    }

    /**
     * Set title force to H1.
     */
    public function setTitleForce(Collection $intls): void
    {
        foreach ($intls as $intl) {
            $intl->setTitleForce(1);
        }
    }

    /**
     * Check if entity by locale existing.
     */
    private function existingLocale(string $locale, Collection $collection): bool
    {
        foreach ($collection as $entity) {
            if ($entity->getLocale() === $locale) {
                return true;
            }
        }

        return false;
    }

    /**
     * Set adminName.
     */
    private function setAdminName(mixed $entity): void
    {
        $defaultLocale = $entity->getWebsite()->getConfiguration()->getLocale();
        $adminName = null;
        foreach ($entity->getIntls() as $intl) {
            if ($intl->getLocale() === $defaultLocale) {
                $adminName = $intl->getTitle();
                break;
            }
        }
        $entity->setAdminName($adminName);
    }

    /**
     * To set position.
     */
    private function setPosition(mixed $entity): void
    {
        $position = count($this->coreLocator->em()->getRepository(get_class($entity))->findBy(['website' => $entity->getWebsite()])) + 1;
        $entity->setPosition($position);
    }

    /**
     * To get all locales.
     */
    private function getAllLocales(Website $website): ?array
    {
        return $website->getConfiguration()->getAllLocales();
    }
}
