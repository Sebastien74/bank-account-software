<?php

declare(strict_types=1);

namespace App\Controller\Back\Wallet;

use App\Controller\Back\BaseController;
use App\Entity\Wallet\Operation;
use App\Entity\Wallet\Wallet;
use App\Form\Manager\GlobalManagerInterface;
use App\Form\Manager\Wallet\OperationInterface;
use App\Form\Type\Wallet\OperationType;
use App\Model\Wallet\WalletModel;
use App\Service\AdminLocatorInterface;
use App\Service\CoreLocatorInterface;
use Exception;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * WalletController.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_ADMIN')]
#[Route('/back-%security_token%/wallets/operations', schemes: '%protocol%')]
class OperationController extends BaseController
{
    protected int $paginationLimit = -1;
    protected bool $forceEntities = true;

    protected ?string $pageIcon = 'wallet';

    protected ?string $classname = Operation::class;
    protected ?string $formType = OperationType::class;

    /**
     * OperationController constructor.
     */
    public function __construct(
        protected CoreLocatorInterface $coreLocator,
        protected AdminLocatorInterface $adminLocator,
        protected GlobalManagerInterface $globalFormManager,
        protected OperationInterface $operation,
    ) {
        parent::__construct($coreLocator, $adminLocator, $globalFormManager, $operation);
    }

    /**
     * Operation index.
     *
     * @throws Exception
     */
    #[Route('/index/{wallet}', name: 'back_operation_index', methods: 'GET|POST')]
    public function index(PaginatorInterface $paginator): Response
    {
//        $this->operation->import();

        $this->template = 'back/pages/operations.html.twig';
        $wallet = $this->coreLocator->em()->getRepository(Wallet::class)->find($this->coreLocator->request()->get('wallet'));

        $yearRequest = $this->coreLocator->request()->get('year');
        $monthRequest = $this->coreLocator->request()->get('month');
        $date = $this->arguments['date'] = $yearRequest && $monthRequest ? new \DateTime($yearRequest.'/'.$monthRequest.'/01')
            : new \DateTime('now', new \DateTimeZone('Europe/Paris'));
        $this->arguments['currentYear'] = $year = !$this->coreLocator->request()->get('year') ? $date->format('Y') : $this->coreLocator->request()->get('year');
        $this->arguments['currentMonth'] = $month = !$this->coreLocator->request()->get('month') ? $date->format('m') : $this->coreLocator->request()->get('month');
        $prev = (clone $date)->modify('first day of last month');
        $next = (clone $date)->modify('first day of next month');
        $this->arguments['previousYear']  = $prev->format('Y');
        $this->arguments['previousMonth'] = $prev->format('m');
        $this->arguments['nextYear']  = $next->format('Y');
        $this->arguments['nextMonth'] = $next->format('m');

        $sort = $this->arguments['sort'] = !$this->coreLocator->request()->get('sort') ? 'date' : $this->coreLocator->request()->get('sort');
        $order = $this->arguments['order'] = !$this->coreLocator->request()->get('order') ? 'DESC' : $this->coreLocator->request()->get('order');

        $this->entities = $this->coreLocator->em()->getRepository(Operation::class)->findByYearMonth($year, $month, $sort, $order, new \DateTimeZone('Europe/Paris'));
        $this->arguments['wallet'] = $wallet = WalletModel::fromEntity($wallet, $this->coreLocator, ['operations' => $this->entities]);
        $this->pageTitle = $this->coreLocator->translator()->trans('Mes opérations :', [], 'back').' '.$wallet->title;

        return parent::index($paginator);
    }

    /**
     * Operation edit.
     */
    #[Route('/edit/{operation}', name: 'back_operation_edit', methods: 'GET|POST')]
    public function edit(): Response
    {
        $this->pageTitle = $this->coreLocator->translator()->trans('Mes opérations', [], 'back');

        dd('Mettre pointed dans fom pas new');

        return parent::edit();
    }

    /**
     * Operation pointed.
     */
    #[Route('/pointed/{operation}', name: 'back_operation_pointed', methods: 'POST')]
    public function pointed(Request $request, Operation $operation): JsonResponse
    {
        $operation->setPointed((bool) $request->get('status'));
        $this->coreLocator->em()->persist($operation);
        $this->coreLocator->em()->flush();

        return new JsonResponse(['success' => true]);
    }

    /**
     * Operation delete.
     */
    #[Route('/delete/{operation}', name: 'back_operation_delete', methods: 'GET')]
    public function delete(Operation $operation): RedirectResponse
    {
        return $this->redirect($this->globalFormManager->delete($operation));
    }

    /**
     * To set breadcrumb.
     */
    protected function breadcrumb(array $items = []): void
    {
        $items[$this->coreLocator->translator()->trans('Mes comptes', [], 'breadcrumb')] = 'back_wallet_index';
        $items[$this->coreLocator->translator()->trans('Opérations', [], 'breadcrumb')] = 'back_operation_index';
        if ($this->coreLocator->request()->get('objective')) {
            $items[$this->coreLocator->translator()->trans('Édition', [], 'back_breadcrumb')] = 'back_operation_edit';
        }

        parent::breadcrumb($items);
    }
}
