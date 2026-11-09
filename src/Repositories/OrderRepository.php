<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class OrderRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return list<array<string, mixed>> */
    public function forUser(int $userId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT o.id, o.total, o.status, o.created_at, SUM(oi.quantity) AS item_count
               FROM orders o
               JOIN order_items oi ON oi.order_id = o.id
              WHERE o.user_id = ?
              GROUP BY o.id
              ORDER BY o.created_at DESC, o.id DESC'
        );
        $statement->execute([$userId]);

        return $statement->fetchAll();
    }

    /** Only returns the order if it belongs to the given user. */
    public function findForUser(int $orderId, int $userId): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM orders WHERE id = ? AND user_id = ?');
        $statement->execute([$orderId, $userId]);

        return $statement->fetch() ?: null;
    }

    /** @return list<array<string, mixed>> */
    public function items(int $orderId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT oi.*, p.image_path
               FROM order_items oi
               LEFT JOIN products p ON p.id = oi.product_id
              WHERE oi.order_id = ?
              ORDER BY oi.id'
        );
        $statement->execute([$orderId]);

        return $statement->fetchAll();
    }
}
