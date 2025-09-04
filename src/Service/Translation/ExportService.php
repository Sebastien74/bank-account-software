<?php

declare(strict_types=1);

namespace App\Service\Translation;

use App\Entity\Api;
use App\Entity\BaseMediaRelation;
use App\Entity\Core\Website;
use App\Entity\Translation\Translation;
use App\Entity\Translation\TranslationDomain;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\Mapping\MappingException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Exception;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * ExportService.
 *
 * Generate ZipArchive of translations files
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ExportService
{
    private ?string $dirname;
    private Website $website;

    /**
     * ExportService constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $dirname = $this->coreLocator->projectDir().'/bin/export';
        $this->dirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $dirname);
    }

    /**
     * Execute exportation.
     *
     * @throws Exception
     * @throws MappingException
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function execute(Website $website): void
    {
        $this->website = $website;

        $this->removeXlsxFiles();

        $defaultLocale = $website->getConfiguration()->getLocale();
        $locales = $website->getConfiguration()->getLocales();

        $intls = $this->getIntls();
        $intls = $this->generateIntls($intls, $defaultLocale, $locales);
        $this->generateCsvIntls($intls, $defaultLocale);

        $translations = $this->getTranslations($defaultLocale);
        $this->generateCsvTranslations($translations, $defaultLocale);
    }

    /**
     * Generate ZipArchive.
     */
    public function zip(): bool|string
    {
        $finder = Finder::create();
        $finder->files()->in($this->dirname)->name('*.xlsx');
        $zip = new \ZipArchive();
        $zipName = 'translations.zip';
        $zip->open($zipName, \ZipArchive::CREATE);

        foreach ($finder as $file) {
            $zip->addFromString($file->getFilename(), $file->getContents());
        }

        $zip->close();

        return $finder->count() ? $zipName : false;
    }

    /**
     * Remove old Xlsx files.
     */
    private function removeXlsxFiles(): void
    {
        $filesystem = new Filesystem();
        $finder = Finder::create();
        $finder->files()->in($this->dirname)->name('*.xlsx');
        foreach ($finder as $file) {
            $filesystem->remove($file->getRealPath());
        }
    }

    /**
     * Get all intl.
     */
    private function getIntls(): array
    {
        $excluded = [
            BaseMediaRelation::class,
            Api\Facebook::class,
            Api\Instagram::class,
        ];
        $metadata = $this->coreLocator->em()->getMetadataFactory()->getAllMetadata();
        $intls = [];

        foreach ($metadata as $data) {
            $namespace = $data->getName();
            if (0 === $data->getReflectionClass()->getModifiers() && !in_array($namespace, $excluded)) {
                $referEntity = new $namespace();
                $tableName = $this->coreLocator->em()->getClassMetadata($namespace)->getTableName();
                if (method_exists($referEntity, 'getIntls') || method_exists($referEntity, 'getIntl')) {
                    if (method_exists($referEntity, 'getWebsite')) {
                        $entities = $this->coreLocator->em()->getRepository($namespace)->createQueryBuilder('e')
                            ->andWhere('e.website = :website')
                            ->setParameter('website', $this->website);
                    } else {
                        $entities = $this->coreLocator->em()->getRepository($namespace)->findAll();
                        foreach ($entities as $key => $entity) {
                            if (method_exists($entity, 'getMedia') && $entity->getMedia() && $entity->getMedia()->getWebsite()->getId() !== $this->website->getId()) {
                                unset($entities[$key]);
                            }
                        }
                    }
                    $isCollection = method_exists($referEntity, 'getIntls');
                    foreach ($entities as $entity) {
                        if ($isCollection) {
                            foreach ($entity->getIntls() as $intl) {
                                $intls[$tableName][$entity->getId()][$intl->getLocale()] = (object) ['entity' => $entity, 'intl' => $intl, 'isCollection' => true];
                            }
                        } else {
                            $intl = $entity->getIntl() ? $entity->getIntl() : $this->addIntl(false, $tableName, $entity, $entity->getLocale(), null);
                            if ($intl) {
                                $intls[$tableName][$entity->getId()][$intl->getLocale()] = (object) ['entity' => $entity, 'intl' => $intl, 'isCollection' => false];
                            }
                        }
                    }
                }
            }
        }

        return $intls;
    }

    /**
     * Generate non-existent intl.
     */
    private function generateIntls(array $intls, string $defaultLocale, array $websiteLocales): array
    {
        foreach ($intls as $tableName => $entity) {
            $defaultEntity = null;
            $existingLocales = [];
            $intlsLocales = [];
            foreach ($entity as $locales) {
                $defaultEntity = !empty($locales[$defaultLocale]) ? $locales[$defaultLocale] : null;
                $defaultIntl = $defaultEntity ? $defaultEntity->intl : null;
                /* Get default locale entity and check existing locale intl */
                foreach ($locales as $locale => $infos) {
                    if ($locale !== $defaultLocale) {
                        $existingLocales[] = $locale;
                        $intlsLocales[$locale] = $infos->intl;
                    }
                }
                /* Check ans generate non-existent intl */
                foreach ($websiteLocales as $locale) {
                    if ($defaultEntity) {
                        $entity = $defaultEntity->entity;
                        if ($defaultIntl && !in_array($locale, $existingLocales)) {
                            $isCollection = $defaultEntity->isCollection;
                            $intl = $this->addIntl($isCollection, $tableName, $entity, $locale, $defaultIntl);
                            $intls[$tableName][$entity->getId()][$locale] = (object) ['entity' => $entity, 'intl' => $intl, 'isCollection' => false, 'defaultIntl' => $defaultIntl];
                        } else {
                            $intls[$tableName][$entity->getId()][$locale] = (object) ['entity' => $entity, 'intl' => $intlsLocales[$locale], 'isCollection' => false, 'defaultIntl' => $defaultIntl];
                        }
                    }
                }
            }
        }

        return $intls;
    }

    /**
     * Add intl.
     */
    private function addIntl(bool $isCollection, string $tableName, $entity, string $locale, mixed $defaultIntl = null): mixed
    {
        $intlData = method_exists($entity, 'getIntls')
            ? $this->coreLocator->metadata($entity, 'intls')
            : $this->coreLocator->metadata($entity, 'intl');

        $defaultIntl = $defaultIntl ?: new ($intlData->targetEntity)();

        $newIntl = new ($intlData->targetEntity)();
        $newIntl->setLocale($locale);
        $newIntl->setTitleForce($defaultIntl->getTitleForce());
        $newIntl->setTargetStyle($defaultIntl->getTargetStyle());
        $newIntl->setTargetPage($defaultIntl->getTargetPage());
        $newIntl->setWebsite($this->website);

        $setter = $isCollection ? 'addIntl' : 'setIntl';
        $entity->$setter($newIntl);

        $this->coreLocator->em()->persist($entity);
        $this->coreLocator->em()->flush();

        return $newIntl;
    }

    /**
     * Generate intls CSV.
     *
     * @throws MappingException
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws Exception
     */
    private function generateCsvIntls(array $intls, string $defaultLocale): void
    {
        $fileData = $this->getIntlFileData($intls, $defaultLocale);

        foreach ($fileData as $tableName => $locales) {
            foreach ($locales as $locale => $entities) {
                $spreadsheet = new Spreadsheet();
                $sheet = $spreadsheet->getActiveSheet();
                $sheet->setCellValue($this->getCsvIntlsIndex('locale'). 1, 'locale');
                $sheet->getColumnDimension($this->getCsvIntlsIndex('locale'))->setAutoSize(true);
                $sheet->setCellValue($this->getCsvIntlsIndex('website'). 1, 'website');
                $sheet->getColumnDimension($this->getCsvIntlsIndex('locale'))->setAutoSize(true);
                $intlFields = !empty($entities[0]) ? $entities[0]['intlFields'] : [];
                foreach ($intlFields as $field) {
                    if (!empty($this->getCsvIntlsIndex($field->field))) {
                        $sheet->setCellValue($this->getCsvIntlsIndex($field->field). 1, $field->field);
                        $sheet->getColumnDimension($this->getCsvIntlsIndex($field->field))->setAutoSize(true);
                        foreach ($entities as $entityKey => $entity) {
                            $sheet->setCellValue($this->getCsvIntlsIndex('locale').($entityKey + 2), $locale);
                            $sheet->setCellValue($this->getCsvIntlsIndex('website').($entityKey + 2), $this->website->getId());
                            $sheet->setCellValue($this->getCsvIntlsIndex($field->field).($entityKey + 2), $entity[$field->field]);
                        }
                    }
                }
                $filename = $tableName.'-'.$locale.'.xlsx';
                $excelFilepath = $this->dirname.'/'.$filename;
                $writer = new Xlsx($spreadsheet);
                $writer->save($excelFilepath);
            }
        }
    }

    /**
     * Generate intls file data.
     *
     * @throws MappingException
     */
    private function getIntlFileData(array $intls, string $defaultLocale): array
    {
        $fileData = [];

        foreach ($intls as $tableName => $entity) {
            foreach ($entity as $locales) {
                foreach ($locales as $locale => $info) {
                    if ($locale !== $defaultLocale) {
                        if (property_exists($info, 'defaultIntl')) {
                            $defaultIntl = $info->defaultIntl;
                            $localeIntl = $info->intl;
                            $intlFields = $this->getIntlFields($localeIntl);
                            $defaultCount = $this->getIntlContentCount($defaultIntl, $intlFields);
                            $haveContent = $this->getIntlHaveContent($defaultIntl, $localeIntl, $intlFields);
                            if ($defaultCount > 0 && $haveContent) {
                                $entityData = [];
                                $entityData['intlFields'] = $intlFields;
                                foreach ($intlFields as $field) {
                                    $getter = $field->getter;
                                    if ('id' === $field->field) {
                                        $entityData['id'] = $localeIntl->getId();
                                    } else {
                                        $localeContentLength = strlen(strip_tags($localeIntl->$getter()));
                                        $entityData[$field->field] = 0 === $localeContentLength ? $defaultIntl->$getter() : null;
                                    }
                                }
                                $fileData[$tableName][$locale][] = $entityData;
                            }
                        }
                    }
                }
            }
        }

        return $fileData;
    }

    /**
     * Get fields content count.
     */
    private function getIntlContentCount(mixed $intl, array $intlFields): int
    {
        $count = 0;
        foreach ($intlFields as $field) {
            $getter = $field->getter;
            $contentLength = strlen(strip_tags($intl->$getter()));
            if ($contentLength > 0 && 'id' !== $field->field) {
                ++$count;
            }
        }

        return $count;
    }

    /**
     * Check if have content to translate.
     */
    private function getIntlHaveContent(mixed $defaultIntl, mixed $localeIntl, array $intlFields): bool
    {
        foreach ($intlFields as $field) {
            $getter = $field->getter;
            $defaultContentLength = strlen(strip_tags($defaultIntl->$getter()));
            $localeContentLength = strlen(strip_tags($localeIntl->$getter()));
            if ('id' !== $field->field && $defaultContentLength > 0 && 0 === $localeContentLength) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get column index.
     */
    private function getCsvIntlsIndex(string $column): mixed
    {
        $indexes = [
            'locale' => 'A',
            'website' => 'B',
            'id' => 'C',
            'title' => 'D',
            'subTitle' => 'E',
            'introduction' => 'F',
            'body' => 'G',
            'targetLink' => 'H',
            'targetLabel' => 'I',
            'placeholder' => 'J',
            'help' => 'K',
            'error' => 'L',
        ];

        return !empty($indexes[$column]) ? $indexes[$column] : null;
    }

    /**
     * Get Translations.
     */
    private function getTranslations(string $defaultLocale): array
    {
        $translations = [];
        $domains = $this->coreLocator->em()->getRepository(TranslationDomain::class)->findAll();

        foreach ($domains as $domain) {
            if ($domain->isExtract()) {
                foreach ($domain->getUnits() as $unit) {
                    foreach ($unit->getTranslations() as $translation) {
                        if ($translation->getLocale() !== $defaultLocale && !$translation->getContent()) {
                            $translations[$translation->getLocale()][] = $translation;
                        }
                    }
                }
            }
        }

        return $translations;
    }

    /**
     * Generate translations CSV.
     *
     * @throws Exception
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    private function generateCsvTranslations(array $translations, string $defaultLocale): void
    {
        foreach ($translations as $locale => $localeTranslation) {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setCellValue('A1', 'locale');
            $sheet->setCellValue('B1', 'domain');
            $sheet->setCellValue('C1', 'id');
            $sheet->setCellValue('D1', 'content');
            $sheet->setCellValue('E1', 'translation');

            foreach ($localeTranslation as $key => $translation) {
                /** @var Translation $translation */
                $defaultContent = null;
                foreach ($translation->getUnit()->getTranslations() as $unitTranslation) {
                    if ($unitTranslation->getLocale() === $defaultLocale) {
                        $defaultContent = $unitTranslation->getContent();
                        break;
                    }
                }

                if ($defaultContent) {
                    $sheet->setCellValue('A'.($key + 2), $translation->getLocale());
                    $sheet->setCellValue('B'.($key + 2), $translation->getUnit()->getDomain()->getName());
                    $sheet->setCellValue('C'.($key + 2), $translation->getId());
                    $sheet->setCellValue('D'.($key + 2), $defaultContent);
                    $sheet->setCellValue('E'.($key + 2), '');
                }
            }

            $excelFilepath = $this->dirname.'/translations-'.$locale.'.xlsx';
            $writer = new Xlsx($spreadsheet);
            $writer->save($excelFilepath);
        }
    }

    /**
     * Get intl text fields.
     *
     * @throws MappingException
     */
    private function getIntlFields(mixed $entity): array
    {
        $referIntl = new (get_class($entity))();
        $intlMetadata = $this->coreLocator->em()->getClassMetadata(get_class($entity));
        $intlAllFields = $intlMetadata->getFieldNames();
        $allowedFields = ['string', 'text'];
        $disallowedFields = ['subTitlePosition', 'pictogram', 'video', 'associatedWords', 'authorType', 'targetStyle', 'targetLabel', 'slug'];

        $intlFields = [];
        foreach ($intlAllFields as $field) {
            $getter = 'get'.ucfirst($field);
            $mapping = $intlMetadata->getFieldMapping($field);
            $isText = in_array($mapping['type'], $allowedFields) && !str_contains(strtolower($mapping['fieldName']), 'alignment') && 'locale' !== $field;
            if (method_exists($referIntl, $getter) && $isText && !in_array($field, $disallowedFields) || 'id' === $field) {
                $intlFields[] = (object) ['getter' => $getter, 'field' => $field];
            }
        }

        return $intlFields;
    }
}
