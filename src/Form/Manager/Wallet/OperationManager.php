<?php

declare(strict_types=1);

namespace App\Form\Manager\Wallet;

use App\Entity\Wallet\Operation;
use App\Entity\Wallet\Outsider;
use App\Service\CoreLocatorInterface;
use App\Service\Urlizer;

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

    public function execute(Operation $operation): void
    {
        if (!$operation->getOutsider() && $operation->getAdminName()) {
            $position = count($this->coreLocator->em()->getRepository(Outsider::class)->findBy([
                'createdBy' => $this->coreLocator->user()
            ])) + 1;
            $outsider = $this->coreLocator->em()->getRepository(Outsider::class)->findOneBy([
                'createdBy' => $this->coreLocator->user(),
                'adminName' => $operation->getAdminName(),
            ]);
            if ($outsider) {
                $operation->setOutsider($outsider);
            } else {
                $outsider = new Outsider();
                $outsider->setAdminName($operation->getAdminName());
                $outsider->setSlug(Urlizer::urlize($operation->getAdminName()));
                $outsider->setCreatedBy($this->coreLocator->user());
                $outsider->setPosition($position);
                $this->coreLocator->em()->persist($outsider);
                $this->coreLocator->em()->flush();
            }
        }
    }
}