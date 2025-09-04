<?php

declare(strict_types=1);

namespace App\Service\Export;

use App\Entity\Module\Form\ContactForm;
use App\Entity\Module\Form\ContactValue;
use App\Entity\Module\Form\Form;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\PersistentCollection;
use ForceUTF8\Encoding;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * ExportContactService.
 *
 * To generate export CSV
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => ExportContactService::class, 'key' => 'contacts_export_service'],
])]
class ExportContactService
{
    private ?Request $request;
    private array $alphas = [];
    private Worksheet $sheet;
    private int $headerAlphaIndex = 0;
    private int $entityAlphaIndex = 0;

    /**
     * ExportContactService constructor.
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly string $projectDir,
        private readonly RequestStack $requestStack,
    ) {
        $this->request = $this->requestStack->getMainRequest();
    }

    /**
     * To execute service.
     */
    public function execute(array $entities, array $interface): array
    {
        $referEntity = new $interface['classname']();
        $configuration = !empty($interface['configuration']) ? $interface['configuration'] : null;
        $exportFields = $configuration ? $configuration->exports : [];
        $spreadsheet = new Spreadsheet();
        $this->alphas = range('A', 'Z');

        try {
            $this->sheet = $spreadsheet->getActiveSheet();
        } catch (\Exception $e) {
        }

        $this->header($referEntity, $exportFields);
        $this->body($referEntity, $entities, $exportFields);

        $filename = $interface['name'].'.xlsx';
        $tempFile = $this->projectDir.'/bin/export/'.$filename;
        $tempFile = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $tempFile);
        $writer = new Xlsx($spreadsheet);

        try {
            $writer->save($tempFile);
        } catch (\PhpOffice\PhpSpreadsheet\Writer\Exception $e) {
        }

        return [
            'tempFile' => $tempFile,
            'fileName' => $filename,
        ];
    }

    /**
     * Set Header.
     */
    private function header($referEntity, array $exportFields = []): void
    {
        foreach ($exportFields as $fieldName) {
            $getter = 'get'.ucfirst($fieldName);
            if (method_exists($referEntity, $getter) && !$referEntity->$getter() instanceof PersistentCollection && !$referEntity->$getter() instanceof ArrayCollection) {
                $this->sheet->setCellValue($this->alphas[$this->headerAlphaIndex]. 1, Encoding::fixUTF8($fieldName));
                $this->sheet->getColumnDimension($this->alphas[$this->headerAlphaIndex])->setAutoSize(true);
                ++$this->headerAlphaIndex;
            } elseif ($referEntity instanceof ContactForm && 'contactValues' === $fieldName) {
                $this->contactFormHeader();
            }
        }
    }

    /**
     * Set ContactForm Header.
     */
    private function contactFormHeader(): void
    {
        $form = $this->entityManager->getRepository(Form::class)->find($this->request->get('form'));
        $zones = $form->getLayout()->getZones();
        $excluded = [SubmitType::class];

        $values = [];
        foreach ($zones as $zone) {
            foreach ($zone->getCols() as $col) {
                foreach ($col->getBlocks() as $block) {
                    if (!in_array($block->getBlockType()->getFieldType(), $excluded)) {
                        $values[$block->getId()] = $this->getIntlEntitled($block);
                    }
                }
            }
        }

        ksort($values);

        foreach ($values as $name) {
            $this->sheet->setCellValue($this->alphas[$this->headerAlphaIndex]. 1, Encoding::fixUTF8($name));
            $this->sheet->getColumnDimension($this->alphas[$this->headerAlphaIndex])->setAutoSize(true);
            ++$this->headerAlphaIndex;
        }
    }

    /**
     * Set Body.
     */
    private function body($referEntity, array $entities = [], array $exportFields = []): void
    {
        $indexEntity = 2;
        foreach ($entities as $entity) {
            $this->entityAlphaIndex = 0;
            foreach ($exportFields as $fieldName) {
                $getter = 'get'.ucfirst($fieldName);
                if (method_exists($entity, $getter) && !$entity->$getter() instanceof PersistentCollection && !$referEntity->$getter() instanceof ArrayCollection) {
                    $this->sheet->setCellValue($this->alphas[$this->entityAlphaIndex].$indexEntity, Encoding::fixUTF8($entity->$getter()));
                    ++$this->entityAlphaIndex;
                } elseif ($referEntity instanceof ContactForm && 'contactValues' === $fieldName) {
                    $this->contactValues($indexEntity, $entity);
                }
            }
            ++$indexEntity;
        }
    }

    /**
     * Set ContactValues.
     */
    private function contactValues(int $indexEntity, mixed $entity): void
    {
        $excluded = [SubmitType::class];
        $values = [];

        foreach ($entity->getContactValues() as $value) {
            /** @var ContactValue $value */
            $block = $value->getConfiguration()->getBlock();
            if (!in_array($block->getBlockType()->getFieldType(), $excluded)) {
                $values[$block->getId()] = $value->getValue();
            }
        }

        ksort($values);

        foreach ($values as $value) {
            $this->sheet->setCellValue($this->alphas[$this->entityAlphaIndex].$indexEntity, Encoding::fixUTF8($value));
            ++$this->entityAlphaIndex;
        }
    }

    /**
     * Get intl entitled.
     */
    private function getIntlEntitled(mixed $entity): ?string
    {
        $getter = 'get'.ucfirst('title');
        $entitled = method_exists($entity, 'getAdminName') && $entity->getAdminName() ? $entity->getAdminName() : null;
        if (method_exists($entity, 'getIntls')) {
            foreach ($entity->getIntls() as $intl) {
                if (method_exists($entity, $getter) && $intl->$getter && $intl->getLocale() === $this->request->getLocale()) {
                    $entitled = $intl->$getter;
                }
            }
        }

        return $entitled;
    }
}
