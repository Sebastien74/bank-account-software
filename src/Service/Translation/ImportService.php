<?php

declare(strict_types=1);

namespace App\Service\Translation;

use App\Command\CacheCommand;
use App\Entity\Translation\Translation;
use App\Service\Core\XlsxFileReader;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\Reader\Exception;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Yaml\Yaml;

/**
 * ImportService.
 *
 * Import translation by Xls files
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ImportService
{
    /**
     * ImportService constructor.
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly XlsxFileReader $fileReader,
        private readonly string $projectDir,
        private readonly CacheCommand $cacheCommand,
    ) {
    }

    /**
     * Execute import.
     *
     * @throws Exception
     */
    public function execute(array $files): void
    {
        $namespaces = $this->getNamespaces();
        foreach ($files as $file) {
            $data = $this->fileReader->read($file);
            $namespace = $this->getRepository($file, $namespaces);
            if (Translation::class === $namespace) {
                $this->addTranslations($data->iterations);
            } elseif ($namespace) {
                $this->addIntls($data->iterations);
            }
        }
        $this->cacheCommand->clear();
    }

    /**
     * Get all namespaces.
     */
    private function getNamespaces(): array
    {
        $namespaces = [];
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        foreach ($metadata as $data) {
            if (0 === $data->getReflectionClass()->getModifiers()) {
                $namespace = $data->getName();
                $tableName = $this->entityManager->getClassMetadata($namespace)->getTableName();
                $namespaces[$tableName] = $data->getName();
            }
        }
        $namespaces['translations'] = Translation::class;

        return $namespaces;
    }

    /**
     * Get entity repository.
     */
    private function getRepository(UploadedFile $file, array $tables): mixed
    {
        $matches = explode('.', str_replace('.xlsx', '', $file->getClientOriginalName()));
        $matches = !empty($matches[0]) ? explode('-', $matches[0]) : null;
        $tableName = !empty($matches[0]) ? $matches[0] : null;

        return !empty($tables[$tableName]) ? $tables[$tableName] : null;
    }

    /**
     * Set Translation[].
     */
    private function addTranslations(array $data): void
    {
        $filesystem = new Filesystem();
        $repository = $this->entityManager->getRepository(Translation::class);
        foreach ($data as $translation) {
            if (!empty($translation['id']) && !empty($translation['content']) && !empty($translation['translation'])) {
                $translationDb = $repository->find($translation['id']);
                $translationDb->setContent($translation['content']);
                $this->entityManager->persist($translationDb);
                $this->entityManager->flush();
                $filePath = $this->projectDir.'/translations/'.$translation['domain'].'.'.$translation['locale'].'.yaml';
                $filePath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $filePath);
                $values = [];
                if ($filesystem->exists($filePath)) {
                    $values = Yaml::parseFile($filePath);
                }
                $values[$translation['content']] = $translation['translation'];
                ksort($values);
                $yaml = Yaml::dump($values);
                file_put_contents($filePath, $yaml);
            }
        }
    }

    /**
     * Set intl[].
     */
    private function addIntls(array $data): void
    {
        $repository = $this->entityManager->getRepository(Intl::class);
        $excludes = ['locale', 'website', 'id'];
        foreach ($data as $translation) {
            if (!empty($translation['id'])) {
                $intl = $repository->find($translation['id']);
                foreach ($translation as $property => $value) {
                    $setter = 'set'.ucfirst($property);
                    if ($intl && !in_array($property, $excludes) && !empty($value) && method_exists($intl, $setter)) {
                        $intl->$setter($value);
                    }
                }
                if ($intl) {
                    $this->entityManager->persist($intl);
                    $this->entityManager->flush();
                }
            }
        }
    }
}
