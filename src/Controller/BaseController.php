<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\CoreLocatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * BaseController.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
abstract class BaseController extends AbstractController
{
    protected array $arguments = [];

    /**
     * BaseController constructor.
     */
    public function __construct(protected CoreLocatorInterface $coreLocator)
    {
    }

    /**
     * To get default arguments.
     */
    protected function coreArguments(): array
    {
        return array_merge($this->arguments, [
            'companyName' => $this->coreLocator->companyName(),
            'securityKey' => $_ENV['APP_SECRET_KEY'],
            'webpack' => $this->coreLocator->inAdmin() ? 'back' : 'front',
            'inAdmin' => $this->coreLocator->inAdmin(),
            'allowedIP' => $this->coreLocator->checkIP(),
            'isDebug' => $this->coreLocator->isDebug(),
        ]);
    }
}
