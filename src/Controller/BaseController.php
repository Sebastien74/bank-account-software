<?php

declare(strict_types=1);

namespace App\Controller;

use App\Form\Manager\GlobalManagerInterface;
use App\Service\CoreLocatorInterface;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * BaseController.
 *
 * App base controller
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
abstract class BaseController extends AbstractController
{
    protected int $paginationLimit = 15;
    protected ?string $classname = null;
    protected ?string $formType = null;
    protected mixed $entity = null;
    protected bool $forceEntities = false;
    protected array $entities = [];
    protected ?string $template = null;
    protected ?string $addBtnLabel = null;
    protected ?string $pageTitle = null;
    protected ?string $pageIcon = 'wallet';
    protected array $breadcrumb = [];
    protected array $arguments = [];

    /**
     * BaseController constructor.
     */
    public function __construct(
        protected CoreLocatorInterface $coreLocator,
        protected GlobalManagerInterface $globalFormManager,
        protected mixed $formManager,
    ) {
    }

    /**
     * Index.
     */
    protected function index(PaginatorInterface $paginator): Response
    {
        return $this->viewRender('index', $paginator);
    }

    /**
     * Edit.
     */
    protected function edit(): Response
    {
        return $this->viewRender('edit');
    }

    /**
     * Render view.
     */
    private function viewRender(string $view, ?PaginatorInterface $paginator = null): Response
    {
        $arguments = $this->defaultArguments();

        $paginator = $paginator ? $this->getPagination($paginator, $this->paginationLimit) : false;
        if ($paginator instanceof RedirectResponse) {
            return $paginator;
        }

        $form = null;
        if ($this->formType && $this->classname) {
            $formManager = $this->globalFormManager;
            $formManager->setForm($this->formType, $this->entity, $this->formManager);
            $form = $formManager->getForm();
            if ($formManager->getRedirection()) {
                return $this->redirect($formManager->getRedirection());
            }
        }

        $template = $this->template ?: 'back/core/'.$view.'.html.twig';

        return $this->render($template, array_merge($arguments, [
            'form' => $form ? $form->createView() : false,
            'formErrors' => $form && $form->isSubmitted() && !$form->isValid(),
            'pagination' => $paginator,
        ]));
    }

    /**
     * To get entities Pagination.
     */
    protected function getPagination(PaginatorInterface $paginator, int $limit = 15): PaginationInterface|RedirectResponse
    {
        $referEntity = $this->classname ? new $this->classname() : false;
        $interface = $referEntity && method_exists($referEntity, 'getInterface') ? $referEntity::getInterface() : [];
        $masterField = !empty($interface['masterField']) ? $interface['masterField'] : false;
        $orderBy = !empty($interface['orderBy']) ? $interface['orderBy'] : 'adminName';
        $orderSort = !empty($interface['orderSort']) ? $interface['orderSort'] : 'ASC';

        $repository = $this->classname ? $this->coreLocator->em()->getRepository($this->classname) : false;
        if (!$this->forceEntities) {
            $this->entities = $masterField && $repository ? $repository->findBy([$masterField => $this->coreLocator->request()->get($masterField)], [$orderBy => $orderSort])
                : ($repository ? $repository->findBy([], [$orderBy => $orderSort]) : []);
        }

        $paginator = $paginator->paginate(
            $this->entities,
            $this->coreLocator->request()->query->getInt('page', 1),
            $limit,
            ['wrap-queries' => true]
        );

        $currentPage = $this->coreLocator->request()->query->getInt('page', 1);
        if ($paginator->count() === 0 && $currentPage > 1) {
            return $this->redirectToRoute('back_'.$interface['name'].'_index');
        }

        return $paginator;
    }

    /**
     * To get default arguments.
     */
    protected function defaultArguments(): array
    {
        $interface = $this->classname && method_exists($this->classname, 'getInterface') ? $this->classname::getInterface() : [];
        $entityRequest = !empty($interface['name']) ? $this->coreLocator->request()->get($interface['name']) : false;
        $this->entity = !$entityRequest && $this->classname ? new $this->classname() : ($this->classname ? $this->coreLocator->em()->getRepository($this->classname)->find(intval($entityRequest)) : false);

        if (empty($this->arguments['breadcrumb'])) {
            $this->breadcrumb();
        }

        return [
            'companyName' => $_ENV['APP_COMPANY_NAME'],
            'securityKey' => $_ENV['SECRET_KEY'],
            'inAdmin' => $this->coreLocator->inAdmin(),
            'allowedIP' => $this->coreLocator->checkIP(),
            'referClass' => $this->classname ? new $this->classname() : [],
            'interface' => $this->classname && method_exists($this->classname, 'getInterface') ? $this->classname::getInterface() : [],
            'buttons' => $this->classname && method_exists($this->classname, 'getButtons') ? $this->classname::getButtons() : [],
            'entity' => $this->entity,
            'paginationLimit' => $this->paginationLimit,
            'addBtnLabel' => $this->addBtnLabel,
            'pageTitle' => $this->pageTitle,
            'pageIcon' => $this->pageIcon,
            'breadcrumb' => $this->breadcrumb,
        ];
    }

    /**
     * Breadcrumb.
     */
    protected function breadcrumb(array $items = []): void
    {
        if ('back_dashboard' !== $this->coreLocator->request()->get('_route')) {
            $label = $this->coreLocator->translator()->trans('Tableau de bord', [], 'back_breadcrumb');
            $dashboardArgs = $this->coreLocator->routeArgs('back_dashboard');
            $this->breadcrumb[$label] = $this->coreLocator->router()->generate('back_dashboard', $dashboardArgs, UrlGeneratorInterface::ABSOLUTE_URL);
        }

        foreach ($items as $label => $route) {
            $asUrl = str_contains($route, '/');
            $routeArgs = !$asUrl ? $this->coreLocator->routeArgs($route, $this->entity) : false;
            $this->breadcrumb[$label] = $asUrl ? $route : $this->coreLocator->router()->generate($route, $routeArgs, UrlGeneratorInterface::ABSOLUTE_URL);
        }
    }
}
