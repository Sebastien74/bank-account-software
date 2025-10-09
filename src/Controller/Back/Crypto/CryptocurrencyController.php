<?php

declare(strict_types=1);

namespace App\Controller\Back\Crypto;

use App\Controller\BaseController;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * CryptocurrencyController.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_ADMIN')]
#[Route('/back-%security_token%/cryptocurrencies/', schemes: '%protocol%')]
class CryptocurrencyController extends BaseController
{
    protected ?string $pageIcon = 'envelope-open-dollar';

    /**
     * Cryptocurrency index.
     */
    #[Route('index', name: 'back_cryptocurrency_index', methods: 'GET|POST')]
    public function index(PaginatorInterface $paginator): Response
    {
        $this->pageTitle = $this->coreLocator->translator()->trans('Gestion des budgets', [], 'back');

        return parent::index($paginator);
    }
}
