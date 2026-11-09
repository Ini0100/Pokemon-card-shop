<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Core\Database;
use App\Core\ValidationException;
use PDO;

/**
 * Shopping cart with stock reservation: adding a product removes it from the
 * available stock until the reservation expires or the order is placed.
 *
 * Rows are always locked in the same order (product, then cart line) to avoid
 * deadlocks between concurrent requests.
 */
final class CartService
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly Config $config,
    ) {
    }

    /** Returns the stock of every expired reservation and removes those cart lines. */
    public function releaseExpiredReservations(): void
    {
        $now     = $this->now();
        $expired = $this->pdo->prepare('SELECT id, product_id FROM cart_items WHERE reserved_until < ?');
        $expired->execute([$now]);

        foreach ($expired->fetchAll() as $candidate) {
            Database::transaction($this->pdo, function () use ($candidate, $now): void {
                $this->lockProduct((int) $candidate['product_id']);

                // Re-check under lock: the line may have been renewed or removed meanwhile.
                $statement = $this->pdo->prepare('SELECT * FROM cart_items WHERE id = ? AND reserved_until < ? FOR UPDATE');
                $statement->execute([$candidate['id'], $now]);
                $line = $statement->fetch();

                if ($line) {
                    $this->adjustStock((int) $line['product_id'], (int) $line['quantity']);
                    $this->pdo->prepare('DELETE FROM cart_items WHERE id = ?')->execute([$line['id']]);
                }
            });
        }
    }

    public function countItems(int $userId): int
    {
        $statement = $this->pdo->prepare('SELECT COALESCE(SUM(quantity), 0) FROM cart_items WHERE user_id = ?');
        $statement->execute([$userId]);

        return (int) $statement->fetchColumn();
    }

    /** @return list<array<string, mixed>> */
    public function items(int $userId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT ci.id, ci.product_id, ci.quantity, ci.reserved_until,
                    p.name, p.price, p.shipping_cost, p.stock, p.image_path,
                    p.price * ci.quantity AS line_total
               FROM cart_items ci
               JOIN products p ON p.id = ci.product_id
              WHERE ci.user_id = ?
              ORDER BY ci.created_at, ci.id'
        );
        $statement->execute([$userId]);

        return $statement->fetchAll();
    }

    /**
     * Shipping is charged once per order, using the highest shipping cost
     * among its products (everything ships in a single parcel).
     *
     * @param list<array<string, mixed>> $items
     * @return array{subtotal: float, shipping: float, total: float}
     */
    public static function totals(array $items): array
    {
        $subtotal = array_sum(array_map(static fn (array $i): float => (float) $i['price'] * (int) $i['quantity'], $items));
        $shipping = $items === [] ? 0.0 : max(array_map(static fn (array $i): float => (float) $i['shipping_cost'], $items));

        return [
            'subtotal' => round($subtotal, 2),
            'shipping' => round($shipping, 2),
            'total'    => round($subtotal + $shipping, 2),
        ];
    }

    public function add(int $userId, int $productId, int $quantity = 1): void
    {
        if ($quantity < 1) {
            throw ValidationException::withMessage('La cantidad debe ser al menos 1.');
        }

        Database::transaction($this->pdo, function () use ($userId, $productId, $quantity): void {
            $product = $this->lockProduct($productId);

            if ($product === null) {
                throw ValidationException::withMessage('El producto no existe.');
            }

            if ((int) $product['stock'] < $quantity) {
                throw ValidationException::withMessage('No hay stock suficiente de este producto.');
            }

            $this->adjustStock($productId, -$quantity);

            $this->pdo->prepare(
                'INSERT INTO cart_items (user_id, product_id, quantity, reserved_until)
                 VALUES (?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity),
                                         reserved_until = VALUES(reserved_until)'
            )->execute([$userId, $productId, $quantity, $this->reservationDeadline()]);
        });
    }

    public function updateQuantity(int $userId, int $cartItemId, int $quantity): void
    {
        if ($quantity <= 0) {
            $this->remove($userId, $cartItemId);

            return;
        }

        Database::transaction($this->pdo, function () use ($userId, $cartItemId, $quantity): void {
            $line    = $this->findLine($userId, $cartItemId);
            $product = $this->lockProduct((int) $line['product_id']);
            $line    = $this->lockLine($cartItemId);
            $delta   = $quantity - (int) $line['quantity'];

            if ($delta > (int) $product['stock']) {
                throw ValidationException::withMessage(
                    sprintf('Solo quedan %d unidades adicionales de «%s».', $product['stock'], $product['name'])
                );
            }

            $this->adjustStock((int) $line['product_id'], -$delta);

            $this->pdo->prepare('UPDATE cart_items SET quantity = ?, reserved_until = ? WHERE id = ?')
                ->execute([$quantity, $this->reservationDeadline(), $cartItemId]);
        });
    }

    public function remove(int $userId, int $cartItemId): void
    {
        Database::transaction($this->pdo, function () use ($userId, $cartItemId): void {
            $line = $this->findLine($userId, $cartItemId);
            $this->lockProduct((int) $line['product_id']);
            $line = $this->lockLine($cartItemId);

            $this->adjustStock((int) $line['product_id'], (int) $line['quantity']);

            $this->pdo->prepare('DELETE FROM cart_items WHERE id = ?')->execute([$cartItemId]);
        });
    }

    /** Empties a cart, returning every reserved unit to stock. */
    public function clear(int $userId): void
    {
        foreach ($this->items($userId) as $line) {
            $this->remove($userId, (int) $line['id']);
        }
    }

    /** @return array<string, mixed> */
    private function findLine(int $userId, int $cartItemId): array
    {
        $statement = $this->pdo->prepare('SELECT * FROM cart_items WHERE id = ? AND user_id = ?');
        $statement->execute([$cartItemId, $userId]);

        return $statement->fetch() ?: throw ValidationException::withMessage('El producto ya no está en tu carrito.');
    }

    /** @return array<string, mixed> */
    private function lockLine(int $cartItemId): array
    {
        $statement = $this->pdo->prepare('SELECT * FROM cart_items WHERE id = ? FOR UPDATE');
        $statement->execute([$cartItemId]);

        return $statement->fetch() ?: throw ValidationException::withMessage('El producto ya no está en tu carrito.');
    }

    /** @return array<string, mixed>|null */
    private function lockProduct(int $productId): ?array
    {
        $statement = $this->pdo->prepare('SELECT id, name, stock FROM products WHERE id = ? FOR UPDATE');
        $statement->execute([$productId]);

        return $statement->fetch() ?: null;
    }

    /** Adds (positive) or removes (negative) units; callers must hold the product lock. */
    private function adjustStock(int $productId, int $change): void
    {
        if ($change === 0) {
            return;
        }

        $sql = $change > 0
            ? 'UPDATE products SET stock = stock + ? WHERE id = ?'
            : 'UPDATE products SET stock = stock - ? WHERE id = ?';

        $this->pdo->prepare($sql)->execute([abs($change), $productId]);
    }

    private function reservationDeadline(): string
    {
        $minutes = max(1, (int) $this->config->get('cart.reservation_minutes', 60));

        return date('Y-m-d H:i:s', time() + $minutes * 60);
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
