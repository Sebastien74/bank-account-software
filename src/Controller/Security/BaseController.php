<?php

declare(strict_types=1);

namespace App\Controller\Security;

use App\Service\CoreLocatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * BaseController.
 *
 * App base controller
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
    protected function defaultArguments(): array
    {
        return array_merge($this->arguments, [
            'companyName' => $this->coreLocator->companyName(),
            'securityKey' => $_ENV['APP_SECRET_KEY'],
            'localeSwitcher' => 'en' == $this->coreLocator->locale() ? 'fr' : 'en',
        ]);
    }
}
