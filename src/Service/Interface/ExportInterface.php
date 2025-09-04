<?php

declare(strict_types=1);

namespace App\Service\Interface;

use App\Service\Export;

/**
 * ExportInterface.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
interface ExportInterface
{
    public function coreService(): Export\ExportCsvService;
}