<?php

declare(strict_types=1);

namespace App\Form\Manager;

use Symfony\Component\Form\FormInterface;

/**
 * GlobalManagerInterface.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
interface GlobalManagerInterface
{
    public function setForm(string $formClassname, mixed $entity = null, mixed $formManager = null, array $formOptions = []);

    public function getForm(): ?FormInterface;

    public function getRedirection(): ?string;

    public function delete(mixed $entityToDelete): ?string;
}
