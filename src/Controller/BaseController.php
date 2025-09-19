<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Wallet\CategoryType;
use App\Form\Manager\GlobalManagerInterface;
use App\Service\CoreLocatorInterface;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * BaseController.
 *
 * App base controller
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
abstract class BaseController extends AbstractController
{
    protected mixed $entityClassname = null;

    /**
     * BaseController constructor.
     */
    public function __construct(
        protected readonly CoreLocatorInterface $coreLocator,
        protected readonly PaginatorInterface $paginator,
        protected readonly GlobalManagerInterface $globalFormManager,
    ) {
    }

    /**
     * To get entities Pagination.
     */
    protected function getPagination(int $limit = 15): PaginationInterface|RedirectResponse
    {
        $referEntity = new $this->entityClassname();
        $interface = $referEntity && method_exists($referEntity, 'getInterface') ? $referEntity::getInterface() : [];
        $masterField = !empty($interface['masterField']) ? $interface['masterField'] : false;
        $repository = $this->coreLocator->em()->getRepository($this->entityClassname);
        $entities = $masterField ? $repository->findBy([$masterField => $this->coreLocator->request()->get($masterField)], ['adminName' => 'ASC'])
            : $repository->findBy([], ['adminName' => 'ASC']);

//        dump($masterField);
//        dd($entities);

        $paginator = $this->paginator->paginate(
            $entities,
            $this->coreLocator->request()->query->getInt('page', 1),
            $limit,
            ['wrap-queries' => true]
        );

        $currentPage = $this->coreLocator->request()->query->getInt('page', 1);
        if ($paginator->count() === 0 && $currentPage > 1) {
            return $this->redirectToRoute('admin_'.$interface['name'].'_index');
        }

        return $paginator;
    }

    /**
     * To get default arguments.
     */
    protected function defaultArguments(): array
    {
        return [
            'companyName' => $_ENV['APP_COMPANY_NAME'],
            'securityKey' => $_ENV['SECRET_KEY'],
            'allowedIP' => $this->coreLocator->checkIP(),
            'referClass' => $this->entityClassname ? new $this->entityClassname() : [],
            'interface' => $this->entityClassname && method_exists($this->entityClassname, 'getInterface') ? $this->entityClassname::getInterface() : [],
            'buttons' => $this->entityClassname && method_exists($this->entityClassname, 'getButtons') ? $this->entityClassname::getButtons() : [],
        ];
    }
}
