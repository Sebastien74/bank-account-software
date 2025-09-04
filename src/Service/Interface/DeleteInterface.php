<?php

declare(strict_types=1);

namespace App\Service\Interface;

use App\Service\Admin\DeleteService;

/**
 * DeleteInterface.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
interface DeleteInterface
{
    public function coreService(): DeleteService;
}