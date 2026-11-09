<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\ValidationException;
use App\Services\Auth;
use App\Services\CartService;
use App\Services\CheckoutService;

final class CheckoutController extends Controller
{
    public function __construct(
        private readonly Auth $auth,
        private readonly CartService $cart,
        private readonly CheckoutService $checkout,
    ) {
    }

    public function show(): void
    {
        $userId = $this->auth->requireUser();
        $this->cart->releaseExpiredReservations();

        $this->renderForm($userId);
    }

    public function store(Request $request): void
    {
        $userId   = $this->auth->requireUser();
        $shipping = [];

        foreach (array_keys(CheckoutService::SHIPPING_FIELDS) as $field) {
            $shipping[$field] = $request->string($field);
        }

        try {
            $orderId = $this->checkout->placeOrder($userId, $shipping);
        } catch (ValidationException $e) {
            $this->renderForm($userId, $e->errors, $shipping, 422);

            return;
        }

        $this->flash('success', "¡Gracias por tu compra! Tu pedido #{$orderId} se ha registrado correctamente.");
        $this->redirect('/orders/' . $orderId);
    }

    /**
     * @param array<string, string> $errors
     * @param array<string, string> $old
     */
    private function renderForm(int $userId, array $errors = [], array $old = [], int $status = 200): void
    {
        $items = $this->cart->items($userId);

        if ($items === [] && $errors === []) {
            $this->flash('info', 'Tu carrito está vacío.');
            $this->redirect('/cart');
        }

        $this->render('checkout/index', [
            'title'  => 'Finalizar compra',
            'items'  => $items,
            'totals' => CartService::totals($items),
            'fields' => CheckoutService::SHIPPING_FIELDS,
            'errors' => $errors,
            'old'    => $old,
        ], $status);
    }
}
