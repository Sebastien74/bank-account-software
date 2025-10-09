<?php

declare(strict_types=1);

namespace App\Form\Manager\Wallet;

use App\Entity\Wallet\Operation;
use App\Entity\Wallet\Outsider;
use App\Service\CoreLocatorInterface;
use App\Service\Urlizer;
use Symfony\Component\Form\FormInterface;

/**
 * OperationManager.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class OperationManager implements OperationInterface
{
    /**
     * OperationManager constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
    }

    public function execute(Operation $operation, FormInterface $form): void
    {
        $adminName = $form->get('adminName')->getData();
        if ($adminName) {
            $position = count($this->coreLocator->em()->getRepository(Outsider::class)->findBy([
                'createdBy' => $this->coreLocator->user()
            ])) + 1;
            $outsider = $this->coreLocator->em()->getRepository(Outsider::class)->findOneBy([
                'createdBy' => $this->coreLocator->user(),
                'adminName' => $adminName,
            ]);
            if (!$outsider) {
                $outsider = new Outsider();
                $outsider->setAdminName($adminName);
                $outsider->setSlug(Urlizer::urlize($adminName));
                $outsider->setCreatedBy($this->coreLocator->user());
                $outsider->setPosition($position);
                $this->coreLocator->em()->persist($outsider);
            }
            $operation->setOutsider($outsider);
        }
    }
}