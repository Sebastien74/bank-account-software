<?php

declare(strict_types=1);

namespace App\Model\Module;

use App\Entity\Layout\Layout;
use App\Entity\Module\Catalog;
use App\Model\BaseModel;
use App\Model\EntityModel;
use App\Model\ViewModel;
use App\Service\Core\Urlizer;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\QueryException;

/**
 * ProductModel.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final class ProductModel extends BaseModel
{
    private static array $cache = [];

    /**
     * fromEntity.
     *
     * @throws MappingException|NonUniqueResultException|QueryException
     */
    public static function fromEntity(Catalog\Product $product, CoreLocatorInterface $coreLocator, array $options = []): object
    {
        $model = ViewModel::fromEntity($product, $coreLocator, array_merge($options, []));
        $catalogDb = self::getContent('catalog', $product);
        $catalog = ViewModel::fromEntity($catalogDb, $coreLocator, array_merge($options, []));
        $catalogLayout = self::$cache['catalogLayout'][$catalog->id] = !empty(self::$cache['catalogLayout'][$catalog->id])
            ? self::$cache['catalogLayout'][$catalog->id] : self::getContent('layout', $catalog->entity);
        $info = self::information($product);
        if (isset($options['entitiesIds'])) {
            unset($options['entitiesIds']);
        }
        $multiFeaturesValues = [];
        $defaultUniqFeatures = [];
        $values = self::getValues($product, $catalogDb, $multiFeaturesValues, $defaultUniqFeatures);

        $disabledProducts = isset($options['disabledProducts']) && $options['disabledProducts'];
        $products = [];
        if (!$disabledProducts) {
            foreach ($product->getProducts() as $associatedProduct) {
                $products[] = ProductModel::fromEntity($associatedProduct, self::$coreLocator, ['disabledProducts' => true]);
            }
        }

        return (object) array_merge((array) $model, [
            'catalog' => $catalog,
            'catalogSlug' => self::getContent('slug', $catalog),
            'entityForLayout' => $model->layout && $model->layout->getSlug() && !$model->layout->getZones()->isEmpty() && $model->asCustomLayout ? $model->entity : $catalog,
            'info' => $info,
            'values' => $values,
            'products' => $products,
            'template' => self::getTemplate($model, $catalog->entity, $catalogLayout),
            'haveLayout' => $model->haveLayout ?: $catalogLayout && !$catalogLayout->getZones()->isEmpty(),
            'asCustomLayout' => $model->haveLayout ?: $catalogLayout && !$catalogLayout->getZones()->isEmpty(),
            'mainFeature' => self::mainFeature($catalogDb, $values),
            'formPageUrl' => self::getFormPage($model),
        ], $values['defaults']);
    }

    /**
     * To get template.
     */
    private static function getTemplate(ViewModel $model, Catalog\Catalog $catalog, ?Layout $catalogLayout = null): string
    {
        $website = self::$coreLocator->website();
        $websiteTemplate = $website->configuration->template;
        $template = $model->haveLayout || $catalogLayout && !$catalogLayout->getZones()->isEmpty() ? 'front/'.$websiteTemplate.'/actions/catalog/view/layout.html.twig' : null;
        $template = $template && self::$coreLocator->fileExist($template) ? $template : 'front/'.$websiteTemplate.'/actions/catalog/view/default-product.html.twig';
        $templateCatalog = 'front/'.$websiteTemplate.'/actions/catalog/view/'.$catalog->getSlug().'.html.twig';

        return self::$coreLocator->fileExist($templateCatalog) ? $templateCatalog : $template;
    }

    /**
     * To get values.
     *
     * @throws MappingException|NonUniqueResultException
     */
    private static function getValues(Catalog\Product $product, Catalog\Catalog $catalog, array $multiFeaturesValues = [], array $defaultUniqFeatures = []): array
    {
        if (!isset(self::$cache['featuresValues'])) {
            self::$cache['featuresValues'] = [];
            $features = self::$coreLocator->em()->getRepository(Catalog\Feature::class)->findAllByWebsiteIterate(self::$coreLocator->website());
            foreach ($features as $feature) {
                self::$cache['features'][$feature->getId()] = self::$cache['features'][$feature->getId()] ?? EntityModel::fromEntity($feature, self::$coreLocator)->response;
                foreach ($feature->getValues() as $value) {
                    $featureModel = self::$cache['features'][$feature->getId()];
                    $valueModel = EntityModel::fromEntity($value, self::$coreLocator)->response;
                    self::$cache['featuresValues'][$value->getId()]['entity'] = $value;
                    self::$cache['featuresValues'][$value->getId()]['feature'] = $featureModel;
                    self::$cache['featuresValues'][$value->getId()]['featureTitle'] = $featureModel->intl->title;
                    self::$cache['featuresValues'][$value->getId()]['value'] = $valueModel;
                    self::$cache['featuresValues'][$value->getId()]['valueTitle'] = $valueModel->intl->title;
                    self::$cache['featuresValues'][$value->getId()]['slug'] = $value->getSlug();
                }
            }
            ksort(self::$cache['featuresValues']);
        }

        $jsonValues = self::jsonValues($product, $multiFeaturesValues);
        $setJsonValues = false;

        // To add default FeatureValue[]
        self::$cache['valuesCatalog'][$catalog->getId()] = self::$cache['valuesCatalog'][$catalog->getId()] ??
            self::$coreLocator->em()->getRepository(Catalog\FeatureValue::class)->findByCatalog($catalog);
        foreach (self::$cache['valuesCatalog'][$catalog->getId()] as $value) {
            if (empty($jsonValues['byIds'][$value->getId()])) {
                $setJsonValues = true;
                self::addValue($product, $value->getCatalogFeature(), $value);
            }
        }

        // To add default Feature[]
        self::$cache['featuresCatalog'][$catalog->getId()] = self::$cache['featuresCatalog'][$catalog->getId()] ??
            self::$coreLocator->em()->getRepository(Catalog\Feature::class)->findByCatalog($catalog);
        foreach (self::$cache['featuresCatalog'][$catalog->getId()] as $feature) {
            if (empty($jsonValues['featuresByIds'][$feature->getId()])) {
                $setJsonValues = true;
                self::addValue($product, $feature);
            }
        }

        foreach ($product->getValues() as $value) {
            if ($value->getPosition() !== $value->getFeaturePosition()) {
                $setJsonValues = true;
                $value->setPosition($value->getFeaturePosition());
                self::$coreLocator->em()->persist($value);
            }
        }

        if ($setJsonValues) {
            self::$coreLocator->em()->flush();
            $jsonValues = self::jsonValues($product, $multiFeaturesValues);
        }

        $jsonValues['defaultsUniq'] = self::getUniqFeaturesValues($jsonValues, $defaultUniqFeatures);
        $jsonValues['defaultsMulti'] = !empty($jsonValues['defaultsMulti']) ? $jsonValues['defaultsMulti'] : [];
        $jsonValues['defaults'] = array_merge($jsonValues['defaultsUniq'], $jsonValues['defaultsMulti']);

        return array_merge($jsonValues, $jsonValues['defaultsMulti'], $jsonValues['defaultsUniq']);
    }

    /**
     * Get uniq features values.
     */
    private static function getUniqFeaturesValues(array $values = [], $defaultValues = []): array
    {
        $result = [];
        $values = !empty($values['byIds']) ? $values['byIds'] : [];
        foreach ($values as $value) {
            $featureSlug = $value->feature ? $value->feature->slug : '';
            if (in_array($featureSlug, $defaultValues) || isset($defaultValues[$featureSlug])) {
                $slug = $value->feature ? self::stringToCamelCase($featureSlug) : null;
                $result[$slug] = [
                    'title' => $value->value ? $value->value->intl->title : null,
                    'feature' => $value->feature ?: null,
                    'featureTitle' => $value->feature ? $value->feature->intl->title : null,
                    'value' => $value->value ?: null,
                    'valueTitle' => $value->value ? $value->value->intl->title : null,
                ];
            }
        }

        foreach ($defaultValues as $defaultValue) {
            $slug = self::stringToCamelCase($defaultValue);
            if (!isset($result[$slug])) {
                $result[$slug] = false;
            }
        }

        return $result;
    }

    /**
     * Add Value.
     */
    private static function addValue(Catalog\Product $product, Catalog\Feature $feature, ?Catalog\FeatureValue $value = null): void
    {
        $jsonData = $product->getJsonValues();
        $valueProduct = new Catalog\FeatureValueProduct();
        $valueProduct->setFeature($feature);
        $valueProduct->setValue($value);
        $valueProduct->setAsDefault(true);
        $valueProduct->setPosition(count($product->getValues()) + 1);
        $valueProduct->setFeaturePosition(count($product->getValues()) + 1);
        $product->addValue($valueProduct);
        $jsonData[$valueProduct->getPosition()] = [
            'feature' => $valueProduct->getFeature()?->getId(),
            'value' => $valueProduct->getValue()?->getId(),
            'displayInArray' => $valueProduct->isDisplayInArray(),
            'position' => $valueProduct->getPosition(),
        ];
        $product->setJsonValues($jsonData);
        self::$coreLocator->em()->persist($product);
    }

    /**
     * Get jsonValues.
     */
    private static function jsonValues(Catalog\Product $product, array $multiFeaturesValues = []): array
    {
        $jsonValues = $product->getJsonValues();
        self::$cache['featuresValues'] = self::$cache['featuresValues'] ?? [];
        $response = [];
        foreach ($jsonValues as $jsonValue) {
            $jsonValue = (object) $jsonValue;
            $value = !empty(self::$cache['featuresValues'][$jsonValue->value]) ? self::$cache['featuresValues'][$jsonValue->value] : null;
            $feature = !empty(self::$cache['features'][$jsonValue->feature]) ? self::$cache['features'][$jsonValue->feature] : null;
            if ($value) {
                $value = (object) $value;
                $response['byIds'][$value->value->id] = $value;
                $response['byPositions'][$value->value->position.'-'.$value->feature->slug] = $value;
                $response['bySlugs'][$value->value->slug.'-'.$value->feature->slug] = $value;
                $response['byFeaturesSlugs'][$value->feature->slug][$jsonValue->position] = $value;
                $response['byFeaturesIds'][$value->feature->id][$jsonValue->position] = $value;
                ksort($response['byIds']);
                ksort($response['byPositions']);
                ksort($response['bySlugs']);
                ksort($response['byFeaturesSlugs']);
                ksort($response['byFeaturesSlugs'][$value->feature->slug]);
                ksort($response['byFeaturesIds'][$value->feature->id]);
            }
            if ($feature) {
                $response['featuresByIds'][$feature->id] = $feature;
                ksort($response['featuresByIds']);
            }
        }

        foreach ($multiFeaturesValues as $dbSlug => $slug) {
            $dbSlug = is_string($dbSlug) ? $dbSlug : $slug;
            $response = self::values($response, $dbSlug, $slug);
            $response['defaultsMulti'][$slug] = !empty($response[$slug]) ? $response[$slug] : [];
        }

        return $response;
    }

    /**
     * Set values.
     */
    private static function values(array $values, string $dbSlug, string $slug): array
    {
        if (!empty($values['byFeaturesSlugs'][$dbSlug])) {
            foreach ($values['byFeaturesSlugs'][$dbSlug] as $speaker) {
                $slugValue = Urlizer::urlize($speaker->value->intl->title);
                $values[$slug][substr($slugValue, 0,40)] = $speaker;
            }
            ksort($values[$slug]);
        }

        return $values;
    }

    /**
     * Get information.
     */
    private static function information(Catalog\Product $product): ?Catalog\ProductInformation
    {
        self::$cache['infos'][$product->getId()] = self::$cache['infos'][$product->getId()] ?? self::$coreLocator->em()->getRepository(Catalog\ProductInformation::class)->findByProduct($product);

        return !empty(self::$cache['infos'][$product->getId()]) ? self::$cache['infos'][$product->getId()] : null;
    }

    /**
     * Get main Feature.
     *
     * @throws NonUniqueResultException|MappingException
     */
    private static function mainFeature(Catalog\Catalog $catalog, array $values = []): ?object
    {
        $feature = null;
        $catalogSlug = self::getContent('slug', $catalog);

//        if ('my-catalog-name' === $catalogSlug) {
//            $features = !empty($values['byFeaturesSlugs']['my-feature-slug']) ? $values['byFeaturesSlugs']['my-feature-slug'] : [];
//            $firstKey = array_key_first($features);
//            $feature = $firstKey && !empty($features[$firstKey]) ? $features[$firstKey]->value : null;
//        }

        return $feature;
    }
}
