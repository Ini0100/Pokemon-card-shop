<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class AlbumRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * Album entries of a given category ("boosters" or "cards"), with the
     * collection each product belongs to.
     *
     * @return list<array<string, mixed>>
     */
    public function itemsInCategory(int $userId, string $categorySlug): array
    {
        $statement = $this->pdo->prepare(
            "SELECT p.id, p.name, p.image_path, a.quantity, collection.label AS collection
               FROM album_items a
               JOIN products p            ON p.id = a.product_id
               JOIN product_categories pc ON pc.product_id = p.id
               JOIN categories c          ON c.id = pc.category_id AND c.slug = ?
               LEFT JOIN (
                    SELECT ppv.product_id, pv.label, pv.id
                      FROM product_property_values ppv
                      JOIN property_values pv ON pv.id = ppv.property_value_id
                      JOIN properties pr      ON pr.id = pv.property_id AND pr.code = 'collection'
               ) AS collection ON collection.product_id = p.id
              WHERE a.user_id = ?
              ORDER BY collection.id, p.name"
        );
        $statement->execute([$categorySlug, $userId]);

        return $statement->fetchAll();
    }
}
