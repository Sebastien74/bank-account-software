<?php

declare(strict_types=1);

namespace App\Form\Manager;

use Symfony\Component\Form\FormInterface;

interface GlobalManagerInterface
{
    public function setForm(string $formClassname, mixed $entity);
    public function getForm(): ?FormInterface;
    public function getRedirection(): ?string;
    public function delete(string $entityClassname): ?string;
}