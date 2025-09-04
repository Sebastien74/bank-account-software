<?php

declare(strict_types=1);

namespace App\Service\Interface;

use App\Service\Export;
use Psr\Container\ContainerExceptionInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * ExportLocator.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ExportLocator implements ExportInterface
{
    /**
     * ExportLocator constructor.
     */
    public function __construct(
        #[AutowireLocator(Export\ExportCsvService::class, indexAttribute: 'key')] protected ServiceLocator $exportLocator,
    ) {
    }

    /**
     * To get ExportCsvService.
     *
     * @throws ContainerExceptionInterface
     */
    public function coreService(): Export\ExportCsvService
    {
        return $this->exportLocator->get('core_export_service');
    }
}
