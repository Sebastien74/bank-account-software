<?php

declare(strict_types=1);

namespace App\Form\EventListener\Translation;

use App\Form\EventListener\BaseListener;
use Symfony\Component\Form\FormEvent;

/**
 * IntlsListener.
 *
 * Listen intl Form attribute
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class IntlsListener extends BaseListener
{
    /**
     * preSetData.
     */
    public function preSetData(FormEvent $event): void
    {
        $entity = $event->getData();
        $titleForce = !empty($this->options['fields_data']['titleForce'])
            ? $this->options['fields_data']['titleForce']
            : 2;

        if (!empty($entity)) {
            foreach ($this->locales as $locale) {
                $exist = $this->localeExist($entity, $locale);
                $defaultIntl = $this->getDefault($entity);
                if (!$exist) {
                    $titleForce = $defaultIntl && $defaultIntl->getTitleForce()
                        ? $defaultIntl->getTitleForce() : $titleForce;
                    $this->addIntl($locale, $entity, $titleForce);
                }
            }
        }
    }

    /**
     * Get default locale Media.
     */
    private function getDefault($entity): mixed
    {
        if (is_object($entity) && method_exists($entity, 'getIntls')) {
            foreach ($entity->getIntls() as $existingIntl) {
                if ($existingIntl->getLocale() === $this->defaultLocale) {
                    return $existingIntl;
                }
            }
        }
        return null;
    }

    /**
     * Check if intl locale exist.
     */
    private function localeExist(mixed $entity, string $locale): bool
    {
        if (is_object($entity) && method_exists($entity, 'getIntls')) {
            foreach ($entity->getIntls() as $existingIntl) {
                if ($existingIntl->getLocale() === $locale) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Add intl.
     */
    private function addIntl(string $locale, mixed $entity, int $titleForce): void
    {
        if (is_object($entity) && method_exists($entity, 'addIntl')) {
            $intlData = $this->coreLocator->metadata($entity, 'intls');
            $intl = new ($intlData->targetEntity)();
            $intl->setLocale($locale);
            $intl->setTitleForce($titleForce);
            $intl->setWebsite($this->website->entity);
            if (method_exists($intl, $intlData->setter)) {
                $setter = $intlData->setter;
                $intl->$setter($entity);
            }
            $entity->addIntl($intl);
        }
    }
}
