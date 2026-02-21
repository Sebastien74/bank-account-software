<?php

declare(strict_types=1);

namespace App\Service;

/**
 * InterfaceHelperInterface.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
interface InterfaceHelperInterface
{
    public function generate(mixed $entity, ?string $field = null): mixed;
}
