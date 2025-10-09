<?php

declare(strict_types=1);

namespace App\Form\Manager\Wallet;

use App\Entity\Wallet\Operation;
use Symfony\Component\Form\FormInterface;

/**
 * OperationInterface.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
interface OperationInterface
{
    public function execute(Operation $operation, FormInterface $form): void;
}