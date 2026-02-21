<?php

declare(strict_types=1);

namespace App\Controller\Back;

use App\Form\Manager\GlobalManagerInterface;
use App\Service\AdminLocatorInterface;
use App\Service\CoreLocatorInterface;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * BaseController.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
abstract class BaseController extends \App\Controller\BaseController
{
    protected int $paginationLimit = 10;
    protected ?string $classname = null;
    protected ?string $model = null;
    protected ?string $formType = null;
    protected ?string $formFilterType = null;
    protected mixed $entity = null;
    protected bool $forceEntities = false;
    protected array $entities = [];
    protected ?string $template = null;
    protected ?string $addBtnLabel = null;
    protected ?string $pageTitle = null;
    protected ?string $pageIcon = 'tachometer-alt';
    protected ?string $pageCode = null;
    protected bool $exportModal = true;
    protected array $breadcrumb = [];

    /**
     * BaseController constructor.
     */
    public function __construct(
        protected CoreLocatorInterface $coreLocator,
        protected AdminLocatorInterface $adminLocator,
        protected GlobalManagerInterface $globalFormManager,
        protected mixed $formManager,
    ) {
        parent::__construct($coreLocator);
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
     * Show.
     */
    protected function show(): Response
    {
        return $this->viewRender('show');
    }

    /**
     * Render view.
     */
    private function viewRender(string $view, ?PaginatorInterface $paginator = null): Response
    {
        $arguments = $this->adminArguments($view);

        $paginator = $paginator ? $this->getPagination($paginator, $this->paginationLimit) : false;
        if ($paginator instanceof RedirectResponse) {
            return $paginator;
        }

        $form = null;
        if ($this->formType && $this->classname && 'show' !== $view) {
            $formManager = $this->globalFormManager;
            $formManager->setForm($this->formType, $this->entity, $this->formManager);
            $form = $formManager->getForm();
            if ($formManager->getRedirection()) {
                return $this->redirect($formManager->getRedirection());
            }
        }

        $this->template = $this->template ?: 'back/core/'.$view.'.html.twig';
        $arguments = array_merge($arguments, [
            'form' => $form ? $form->createView() : false,
            'formErrors' => $form && $form->isSubmitted() && !$form->isValid(),
            'pagination' => $paginator,
        ]);

        if ($this->coreLocator->request()->query->get('ajax')) {
            return new JsonResponse(['success' => true, 'html' => $this->renderView($this->template, $arguments)]);
        } else {
            return $this->render($this->template, $arguments);
        }
    }

    /**
     * To get entities Pagination.
     */
    protected function getPagination(PaginatorInterface $paginator, int $limit = 10): PaginationInterface|RedirectResponse
    {
        $interface = $this->coreLocator->entityInterface($this->classname);
        $repository = $this->classname ? $this->coreLocator->em()->getRepository($this->classname) : false;

        if (!$this->forceEntities) {
            $qbArgs = [];
            if ($interface['masterField']) {
                $qbArgs[$interface['masterField']] = $this->coreLocator->request()->attributes->get($interface['masterField']);
            }
            $this->entities = $repository ? $repository->findBy($qbArgs, [$interface['orderBy'] => $interface['orderSort']]) : [];
        }

        $paginator = $paginator->paginate(
            $this->entities,
            $this->coreLocator->request()->query->getInt('page', 1),
            -1 === $limit ? 1000000000 : $limit,
            ['wrap-queries' => true]
        );

        foreach ($this->coreLocator->request()->query->all() as $key => $value) {
            if ('page' === $key || 'ajax' === $key) {
                continue;
            }
            $paginator->setParam($key, $value);
        }

        if ($this->model) {
            $items = [];
            foreach ($paginator->getItems() as $item) {
                $items[] = $this->model::fromEntity($item, $this->coreLocator);
            }
            $paginator->setItems($items);
        }

        $currentPage = $this->coreLocator->request()->attributes->getInt('page', 1);
        if ($paginator->count() === 0 && $currentPage > 1) {
            return $this->redirectToRoute('back_'.$interface['name'].'_index');
        }

        return $paginator;
    }

    /**
     * To get default arguments.
     */
    protected function adminArguments(?string $view = null): array
    {
        $arguments = [];
        $interface = $this->coreLocator->entityInterface($this->classname);
        $entityRequest = !empty($interface['name']) ? $this->coreLocator->request()->attributes->get($interface['name']) : false;
        $this->entity = !$entityRequest && $this->classname ? new $this->classname()
            : ($this->classname && is_numeric($entityRequest) ? $this->coreLocator->em()->getRepository($this->classname)->find(intval($entityRequest)) : false);
        $this->entity = ($this->classname && $this->entity instanceof $this->classname && $this->model && 'show' === $view) ? $this->model::fromEntity($this->entity, $this->coreLocator) : $this->entity;

        if ($entityRequest && !$this->entity) {
            throw $this->createNotFoundException();
        }

        if (empty($this->arguments['breadcrumb'])) {
            $this->breadcrumb();
        }

        $interface = $this->coreLocator->entityInterface($this->classname);

        $arguments['interface'] = $interface;
        $arguments['entity'] = $this->entity;
        $arguments['paginationLimit'] = $this->paginationLimit;
        $arguments['addBtnLabel'] = $this->addBtnLabel;
        $arguments['pageTitle'] = $this->pageTitle;
        $arguments['pageIcon'] = $this->pageIcon;
        $arguments['pageCode'] = $this->pageCode;
        $arguments['breadcrumb'] = $this->breadcrumb;
        $arguments['exportModal'] = $this->exportModal;

        $arguments = $this->formSearch($arguments, $view);
        $arguments = $this->formExport($arguments, $view);
        $arguments = $this->formFilter($arguments, $view);

        return array_merge($this->arguments, $interface, $this->coreArguments(), $arguments);
    }

    /**
     * formExport.
     */
    private function formExport(array $arguments = [], ?string $view = null): array
    {
        if ('index' === $view && !empty($arguments['interface']['export'])) {
            $arguments['formExport'] = $formExport = $this->createForm(\App\Form\Type\ExportType::class, null, [
                'classname' => $this->classname,
                'exportModal' => $this->exportModal,
            ]);
            $formExport->handleRequest($this->coreLocator->request());
            if ($formExport->isSubmitted() && $formExport->isValid()) {
                $this->adminLocator->exportInterface()->execute($formExport, $this->entities);
            }
            $arguments['formExportErrors'] = $formExport->isSubmitted() && !$formExport->isValid();
        }

        return $arguments;
    }

    /**
     * formFilter.
     */
    private function formFilter(array $arguments = [], ?string $view = null): array
    {
        if ('index' === $view && $this->formFilterType) {
            $arguments['filtersForm'] = $filtersForm = $this->createForm($this->formFilterType, null, ['method' => 'GET']);
            $filtersForm->handleRequest($this->coreLocator->request());
            if ($filtersForm->isSubmitted()) {
                $this->entities = $this->adminLocator->searchInterface()->execute($filtersForm, $this->classname);
                $this->forceEntities = true;
                $arguments['filtersFormSubmitted'] = true;
            }
        }

        return $arguments;
    }

    /**
     * formSearch.
     */
    private function formSearch(array $arguments = [], ?string $view = null): array
    {
        if ('index' === $view && !empty($arguments['interface']['export'])) {
            $arguments['formSearch'] = $formSearch = $this->createForm(\App\Form\Type\SearchType::class, null, ['method' => 'GET']);
            $formSearch->handleRequest($this->coreLocator->request());
            if ($formSearch->isSubmitted() && $formSearch->getData()['text']) {
                $this->entities = $this->adminLocator->searchInterface()->execute($formSearch, $this->classname);
                $this->forceEntities = true;
            }
        }

        return $arguments;
    }

    /**
     * Breadcrumb.
     */
    protected function breadcrumb(array $items = []): void
    {
        if ('back_wallet_index' !== $this->coreLocator->request()->attributes->get('_route')) {
            $label = $this->coreLocator->translator()->trans('Comptes', [], 'back_breadcrumb');
            $dashboardArgs = $this->coreLocator->routeArgs('back_wallet_index');
            $this->breadcrumb[$label] = $this->coreLocator->router()->generate('back_wallet_index', $dashboardArgs, UrlGeneratorInterface::ABSOLUTE_URL);
        }

        foreach ($items as $label => $route) {
            $asUrl = str_contains($route, '/');
            $routeArgs = !$asUrl ? $this->coreLocator->routeArgs($route, $this->entity) : [];
            if (is_array($routeArgs)) {
                $this->breadcrumb[$label] = $asUrl ? $route : $this->coreLocator->router()->generate($route, $routeArgs, UrlGeneratorInterface::ABSOLUTE_URL);
            }
        }

        $this->arguments['breadcrumb'] = $this->breadcrumb;
    }
}
