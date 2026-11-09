<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\HttpException;
use App\Core\Request;
use App\Repositories\OrderRepository;
use App\Services\Auth;

final class OrderController extends Controller
{
    public function __construct(
        private readonly Auth $auth,
        private readonly OrderRepository $orders,
    ) {
    }

    public function index(): void
    {
        $userId = $this->auth->requireUser();

        $this->render('orders/index', [
            'title'  => 'Mis pedidos',
            'orders' => $this->orders->forUser($userId),
        ]);
    }

    public function show(Request $request, int $id): void
    {
        $userId = $this->auth->requireUser();
        $order  = $this->orders->findForUser($id, $userId) ?? throw HttpException::notFound('El pedido no existe.');

        $this->render('orders/show', [
            'title' => "Pedido #{$id}",
            'order' => $order,
            'items' => $this->orders->items($id),
        ]);
    }
}
