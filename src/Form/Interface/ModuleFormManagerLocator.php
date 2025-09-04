<?php

declare(strict_types=1);

namespace App\Form\Interface;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * ModuleFormManagerLocator.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => ModuleFormManagerLocator::class, 'key' => 'module_form_manager_locator'],
])]
class ModuleFormManagerLocator implements ModuleFormManagerInterface
{
    /**
     * ModuleFormManagerLocator constructor.
     */
    public function __construct(

    ) {
    }

}
