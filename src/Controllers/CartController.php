<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\ValidationException;
use App\Services\Auth;
use App\Services\CartService;

final class CartController extends Controller
{
    public function __construct(
        private readonly Auth $auth,
        private readonly CartService $cart,
    ) {
    }

    public function index(): void
    {
        $userId = $this->auth->requireUser();
        $this->cart->releaseExpiredReservations();

        $items = $this->cart->items($userId);

        $this->render('cart/index', [
            'title'  => 'Carrito',
            'items'  => $items,
            'totals' => CartService::totals($items),
        ]);
    }

    public function add(Request $request): void
    {
        $userId    = $this->auth->requireUser();
        $productId = $request->int('product_id');

        try {
            $this->cart->releaseExpiredReservations();
            $this->cart->add($userId, $productId, max(1, $request->int('quantity', 1)));
            $this->flash('success', 'Producto añadido al carrito.');
        } catch (ValidationException $e) {
            $this->flash('error', $e->getMessage());
            $this->redirect('/products/' . $productId);
        }

        $this->redirect('/cart');
    }

    public function update(Request $request, int $id): void
    {
        $userId = $this->auth->requireUser();

        try {
            $this->cart->updateQuantity($userId, $id, $request->int('quantity'));
            $this->flash('success', 'Carrito actualizado.');
        } catch (ValidationException $e) {
            $this->flash('error', $e->getMessage());
        }

        $this->redirect('/cart');
    }

    public function remove(Request $request, int $id): void
    {
        $userId = $this->auth->requireUser();

        try {
            $this->cart->remove($userId, $id);
            $this->flash('success', 'Producto eliminado del carrito.');
        } catch (ValidationException $e) {
            $this->flash('error', $e->getMessage());
        }

        $this->redirect('/cart');
    }
}
