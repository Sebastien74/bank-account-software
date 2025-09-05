<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\Core\WebsiteModel;
use Doctrine\ORM\NonUniqueResultException;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
    /**
     * BaseController constructor.
     */
    public function __construct(protected \App\Service\Interface\CoreLocatorInterface $coreLocator)
    {
    }

    /**
     * Get Request WebsiteModel.
     */
    protected function getWebsite(): ?WebsiteModel
    {
        return $this->coreLocator->website();
    }

    /**
     * Get Entity Interface.
     */
    protected function getInterface(string $classname, array $options = []): bool|array
    {
        try {
            return $this->coreLocator->interfaceHelper()->generate($classname, $options);
        } catch (NonUniqueResultException $e) {
            return false;
        }
    }

    /**
     * Get current namespace.
     */
    protected function getCurrentNamespace(Request $request): ?string
    {
        $matches = explode('::', $request->get('_controller'));

        return !empty($matches) ? $matches[0] : null;
    }

    /**
     * Get Tree of Entities.
     */
    protected function getTree(object|array $entities): array
    {
        return $this->coreLocator->treeService()->execute($entities);
    }

    /**
     * Generate pagination.
     */
    protected function getPagination(PaginatorInterface $paginator, $queryBuilder, int $queryLimit = 12): PaginationInterface
    {
        return $paginator->paginate(
            $queryBuilder,
            $this->coreLocator->request()->query->getInt('page', 1),
            $queryLimit,
            ['wrap-queries' => true]
        );
    }

    /**
     * Get Thumb ConfigurationModel.
     */
    protected function thumbConfiguration(WebsiteModel $website, string $classname, ?string $action = null, mixed $filter = null, ?string $type = null): array
    {
        return $this->coreLocator->thumbService()->thumbConfiguration($website, $classname, $action, $filter, $type);
    }

    /**
     * Get Thumb by filter.
     */
    protected function thumbConfigurationByFilter(WebsiteModel $website, string $classname, $filter = null): array
    {
        return $this->coreLocator->thumbService()->thumbConfigurationByFilter($website, $classname, $filter);
    }
}
