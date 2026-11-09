<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\ValidationException;
use PDO;

/**
 * Turns the user's cart into an order. Payment is simulated: no card data is
 * collected or stored.
 */
final class CheckoutService
{
    /** Categories whose products are added to the collector album when purchased. */
    private const COLLECTIBLE_CATEGORIES = ['boosters', 'cards'];

    public const SHIPPING_FIELDS = [
        'recipient_name'   => ['label' => 'Nombre completo', 'max' => 150],
        'shipping_address' => ['label' => 'Dirección',       'max' => 255],
        'city'             => ['label' => 'Ciudad',          'max' => 100],
        'postal_code'      => ['label' => 'Código postal',   'max' => 20],
        'country'          => ['label' => 'País',            'max' => 100],
    ];

    public function __construct(
        private readonly PDO $pdo,
        private readonly CartService $cart,
    ) {
    }

    /**
     * @param array<string, string> $shipping Values for each key of SHIPPING_FIELDS.
     * @return int The new order ID.
     */
    public function placeOrder(int $userId, array $shipping): int
    {
        $shipping = $this->validateShipping($shipping);
        $this->cart->releaseExpiredReservations();

        return Database::transaction($this->pdo, function () use ($userId, $shipping): int {
            $lines = $this->lockCartLines($userId);

            if ($lines === []) {
                throw ValidationException::withMessage(
                    'Tu carrito está vacío. Es posible que la reserva de los productos haya caducado.'
                );
            }

            $totals = CartService::totals($lines);

            $this->pdo->prepare(
                'INSERT INTO orders (user_id, recipient_name, shipping_address, city, postal_code, country,
                                     subtotal, shipping_total, total)
                 VALUES (:user_id, :recipient_name, :shipping_address, :city, :postal_code, :country,
                         :subtotal, :shipping_total, :total)'
            )->execute($shipping + [
                'user_id'        => $userId,
                'subtotal'       => $totals['subtotal'],
                'shipping_total' => $totals['shipping'],
                'total'          => $totals['total'],
            ]);

            $orderId = (int) $this->pdo->lastInsertId();

            $insertItem = $this->pdo->prepare(
                'INSERT INTO order_items (order_id, product_id, product_name, unit_price, shipping_cost, quantity)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );

            foreach ($lines as $line) {
                $insertItem->execute([
                    $orderId, $line['product_id'], $line['name'], $line['price'], $line['shipping_cost'], $line['quantity'],
                ]);
            }

            $this->addCollectiblesToAlbum($userId);

            // Stock was already deducted when the items were reserved.
            $this->pdo->prepare('DELETE FROM cart_items WHERE user_id = ?')->execute([$userId]);

            return $orderId;
        });
    }

    /**
     * @param array<string, string> $input
     * @return array<string, string>
     */
    private function validateShipping(array $input): array
    {
        $values = [];
        $errors = [];

        foreach (self::SHIPPING_FIELDS as $field => $rules) {
            $value = trim((string) ($input[$field] ?? ''));

            if ($value === '') {
                $errors[$field] = "El campo «{$rules['label']}» es obligatorio.";
            } elseif (mb_strlen($value) > $rules['max']) {
                $errors[$field] = "El campo «{$rules['label']}» no puede superar {$rules['max']} caracteres.";
            }

            $values[$field] = $value;
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        return $values;
    }

    /** @return list<array<string, mixed>> */
    private function lockCartLines(int $userId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT ci.product_id, ci.quantity, p.name, p.price, p.shipping_cost
               FROM cart_items ci
               JOIN products p ON p.id = ci.product_id
              WHERE ci.user_id = ?
              ORDER BY ci.id
                FOR UPDATE'
        );
        $statement->execute([$userId]);

        return $statement->fetchAll();
    }

    private function addCollectiblesToAlbum(int $userId): void
    {
        $placeholders = implode(',', array_fill(0, count(self::COLLECTIBLE_CATEGORIES), '?'));

        $this->pdo->prepare(
            "INSERT INTO album_items (user_id, product_id, quantity)
             SELECT ci.user_id, ci.product_id, ci.quantity
               FROM cart_items ci
              WHERE ci.user_id = ?
                AND EXISTS (SELECT 1
                              FROM product_categories pc
                              JOIN categories c ON c.id = pc.category_id
                             WHERE pc.product_id = ci.product_id AND c.slug IN ({$placeholders}))
             ON DUPLICATE KEY UPDATE quantity = album_items.quantity + VALUES(quantity)"
        )->execute([$userId, ...self::COLLECTIBLE_CATEGORIES]);
    }
}
