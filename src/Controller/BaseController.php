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

    protected ?string $template = null;

    protected ?string $pageTitle = null;
    protected ?string $pageIcon = null;

    protected array $breadcrumb = [];

    protected array $arguments = [];

    /**
     * BaseController constructor.
     */
    public function __construct(
        protected readonly RequestStack $requestStack,
        protected readonly CoreLocatorInterface $coreLocator,
        protected readonly PaginatorInterface $paginator,
        protected readonly GlobalManagerInterface $globalFormManager,
    ) {
    }

    /**
     * Index.
     */
    protected function index(): Response
    {
        return $this->viewRender('index');
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
    private function viewRender(string $view): Response
    {
        $arguments = $this->defaultArguments();
        $interface = $arguments['interface'];

        $paginator = $this->getPagination($this->paginationLimit);
        if ($paginator instanceof RedirectResponse) {
            return $paginator;
        }

        $form = null;
        if ($this->formType && $this->classname) {
            $entityRequest = $this->requestStack->getMainRequest()->get($interface['name']);
            $entity = !$entityRequest ? new $this->classname() : $this->coreLocator->em()->getRepository($this->classname)->find(intval($entityRequest));
            $formManager = $this->globalFormManager;
            $formManager->setForm($this->formType, $entity);
            $form = $formManager->getForm();
            if ($formManager->getRedirection()) {
                return $this->redirect($formManager->getRedirection());
            }
        }

        $template = $this->template ?: 'back/'.$view.'.html.twig';

        return $this->render($template, array_merge($arguments, [
            'form' => $form ? $form->createView() : false,
            'formErrors' => $form && $form->isSubmitted() && !$form->isValid(),
            'pagination' => $paginator,
        ]));
    }

    /**
     * To get entities Pagination.
     */
    protected function getPagination(int $limit = 15): PaginationInterface|RedirectResponse
    {
        $referEntity = $this->classname ? new $this->classname() : false;
        $interface = $referEntity && method_exists($referEntity, 'getInterface') ? $referEntity::getInterface() : [];
        $masterField = !empty($interface['masterField']) ? $interface['masterField'] : false;
        $repository = $this->classname ? $this->coreLocator->em()->getRepository($this->classname) : false;
        $entities = $masterField && $repository ? $repository->findBy([$masterField => $this->coreLocator->request()->get($masterField)], ['adminName' => 'ASC'])
            : ($repository ? $repository->findBy([], ['adminName' => 'ASC']) : []);

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
        if (empty($this->arguments['breadcrumb'])) {
            $this->breadcrumb();
        }

        return [
            'companyName' => $_ENV['APP_COMPANY_NAME'],
            'securityKey' => $_ENV['SECRET_KEY'],
            'allowedIP' => $this->coreLocator->checkIP(),
            'referClass' => $this->classname ? new $this->classname() : [],
            'interface' => $this->classname && method_exists($this->classname, 'getInterface') ? $this->classname::getInterface() : [],
            'buttons' => $this->classname && method_exists($this->classname, 'getButtons') ? $this->classname::getButtons() : [],
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
        $request = $this->coreLocator->request();

        if ('admin_dashboard' !== $request->get('_route')) {
            $label = $this->coreLocator->translator()->trans('Tableau de bord', [], 'admin_breadcrumb');
            $dashboardArgs = $this->coreLocator->routeArgs('admin_dashboard');
            $this->breadcrumb[$label] = $this->coreLocator->router()->generate('admin_dashboard', $dashboardArgs);
        }

//        if (empty($items)) {
//            $interface = $this->class ? $this->getInterface($this->class) : [];
//            if (!empty($interface['classname']) && !empty($interface['name']) && $request->get($interface['name']) && $this->coreLocator->routeExist('admin_'.$interface['name'].'_index')) {
//                $entityConfiguration = $this->coreLocator->em()->getRepository(Entity::class)->findOneBy([
//                    'website' => $request->get('website'),
//                    'className' => $interface['classname'],
//                ]);
//                $breadcrumb = $this->coreLocator->translator()->trans('breadcrumb', [], 'entity_'.$interface['name']);
//                $plural = $this->coreLocator->translator()->trans('plural', [], 'entity_'.$interface['name']);
//                $title = 'breadcrumb' !== $breadcrumb ? $breadcrumb : ('plural' !== $plural ? $plural : $entityConfiguration->getAdminName());
//                $items[$title] = 'admin_'.$interface['name'].'_index';
//            }
//        }
//
//        foreach ($items as $label => $route) {
//            $asUrl = str_contains($route, '/');
//            $routeArgs = !$asUrl ? $this->coreLocator->routeArgs($route) : false;
//            $this->arguments['breadcrumb'][$label] = $asUrl ? $route : $this->coreLocator->router()->generate($route, $routeArgs);
//        }
    }
}
