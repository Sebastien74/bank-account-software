<?php

declare(strict_types=1);

namespace App\Form\Manager;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * RecaptchaInterface.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
interface RecaptchaInterface
{
    public function execute(Request $request, bool $flashBag = false, ?FormInterface $form = null): bool;
}
