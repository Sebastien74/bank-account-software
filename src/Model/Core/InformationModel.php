<?php

declare(strict_types=1);

namespace App\Model\Core;

use App\Entity\Core\Website;
use App\Model\BaseModel;
use App\Model\IntlModel;
use App\Model\MediaModel;
use App\Model\MediasModel;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * InformationModel.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final class InformationModel extends BaseModel
{
    private static array $cache = [];

    /**
     * InformationModel constructor.
     */
    public function __construct(
        public readonly ?int $id = null,
        public readonly ?object $entity = null,
        public readonly ?string $companyName = null,
        public readonly ?array $medias = null,
        public readonly ?array $logos = null,
        public readonly ?IntlModel $intl = null,
    ) {
    }

    /**
     * Get model.
     *
     * @throws NonUniqueResultException|MappingException
     */
    public static function fromEntity(Website $website, CoreLocatorInterface $coreLocator, ?string $locale = null): self
    {
        self::setLocators($coreLocator);

        $locale = $locale ?: self::$coreLocator->locale();

        if (isset(self::$cache['response'][$website->getId()][$locale])) {
            return self::$cache['response'][$website->getId()][$locale];
        }

        $medias = MediasModel::fromEntity($website->getConfiguration(), $coreLocator, $locale)->list;
        $logos = self::logos($website, $medias, $locale);

        self::$cache['response'][$website->getId()][$locale] = new self(
            logos: $logos,
        );

        return self::$cache['response'][$website->getId()][$locale];
    }

    /**
     * To get logos.
     */
    private static function logos(Website $website, array $medias, string $locale): array
    {
        if (!empty(self::$cache['logos'][$website->getId()])) {
            return self::$cache['logos'][$website->getId()];
        }

        $logos = [];
        $socialLogos = [];
        $filesystem = new Filesystem();
        $uploadDirname = $website->getUploadDirname();
        $projectDir = self::$coreLocator->projectDir();

        foreach ($medias as $media) {
            /** @var MediaModel $media */
            $entityMedia = $media->media;
            $filename = $entityMedia->getFilename();
            $dirname = $filename ? '/uploads/'.$uploadDirname.'/'.$filename : null;
            $appDirname = $projectDir.'/public'.$dirname;
            $appDirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $appDirname);
            $file = $filename && $filesystem->exists($appDirname) ? $dirname : 'medias/placeholder.jpg';
            $category = $media->mediaRelation->getCategorySlug();
            $logos[$category] = $file;
            $logos['medias'][$category] = $media;
            $logos['mediaRelation'][$category] = $media->mediaRelation;
        }

        ksort($logos);

        self::$cache['logos'][$website->getId()] = $logos;

        return $logos;
    }

    /**
     * Get social network icon by name.
     */
    private static function socialIcon(string $name): ?string
    {
        $icons = [
            'facebook' => 'fab facebook-f',
            'google-plus' => 'fab google',
            'instagram' => 'fab instagram',
            'linkedin' => 'fab linkedin-in',
            'pinterest' => 'fab pinterest-p',
            'tripadvisor' => 'fab tripadvisor',
            'twitter' => 'fab twitter',
            'youtube' => 'fab youtube',
            'tiktok' => 'fab tiktok',
        ];

        return !empty($icons[$name]) ? $icons[$name] : null;
    }

    /**
     * Get contact info sort by zones.
     */
    private static function contactZones(array $addresses, array $phones, array $emails): array
    {
        $contacts = [];

        foreach ($addresses as $address) {
            foreach ($address->getZones() as $zone) {
                $contacts['addresses'][$zone][] = $address;
                $contacts['addresses']['all'][] = $address;
            }
        }

        foreach ($phones as $phone) {
            foreach ($phone->getZones() as $zone) {
                $contacts['phones'][$zone][] = $phone;
                $contacts['phones']['all'][] = $phone;
            }
        }

        foreach ($emails as $email) {
            foreach ($email->getZones() as $zone) {
                $contacts['emails'][$zone][] = $email;
                $contacts['emails']['all'][] = $email;
            }
        }

        return $contacts;
    }

    /**
     * Get alertes.
     *
     * @throws NonUniqueResultException|MappingException
     */
    private static function alerts(IntlModel $intl): array
    {
        $hideAlerts = self::$coreLocator->request() && true === self::$coreLocator->request()->getSession()->get('front_website_alert_hide');
        $message = !$hideAlerts ? self::getContent('placeholder', $intl, true) : [];
        $alerts = [];

        if (!$message) {
            return $alerts;
        }

        preg_match_all('/<li[^>]*>(.*?)<\\/li>/is', $message, $matches);
        if (!empty($matches[1])) {
            foreach ($matches[1] as $text) {
                if ($text && strlen(strip_tags(str_replace('&nbsp;', '', $text))) > 0) {
                    $alerts[] = $text;
                }
            }
        }

        return $alerts;
    }
}
