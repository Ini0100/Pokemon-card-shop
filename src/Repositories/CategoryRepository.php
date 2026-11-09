<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class CategoryRepository
{
    /** @var list<array<string, mixed>>|null */
    private ?array $all = null;

    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return list<array{id: int, slug: string, name: string}> */
    public function all(): array
    {
        return $this->all ??= $this->pdo->query('SELECT id, slug, name FROM categories ORDER BY id')->fetchAll();
    }

    /** @return array{id: int, slug: string, name: string}|null */
    public function find(int $id): ?array
    {
        foreach ($this->all() as $category) {
            if ((int) $category['id'] === $id) {
                return $category;
            }
        }

        return null;
    }

    /** @return array{id: int, slug: string, name: string}|null */
    public function findBySlug(string $slug): ?array
    {
        foreach ($this->all() as $category) {
            if ($category['slug'] === $slug) {
                return $category;
            }
        }

        return null;
    }

    /**
     * Categories with their product count and a cover image (their most expensive product).
     *
     * @return list<array{id: int, slug: string, name: string, product_count: int, cover_image: string|null}>
     */
    public function allWithCovers(): array
    {
        return $this->pdo->query(
            'SELECT c.id, c.slug, c.name,
                    COUNT(pc.product_id) AS product_count,
                    (SELECT p.image_path
                       FROM products p
                       JOIN product_categories cover ON cover.product_id = p.id
                      WHERE cover.category_id = c.id
                      ORDER BY p.price DESC
                      LIMIT 1) AS cover_image
               FROM categories c
               LEFT JOIN product_categories pc ON pc.category_id = c.id
              GROUP BY c.id
              ORDER BY c.id'
        )->fetchAll();
    }

    /**
     * Filter groups for a category; only values actually used by its products are listed.
     *
     * @return list<array{id: int, name: string, values: list<array{id: int, label: string}>}>
     */
    public function filtersFor(int $categoryId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT pr.id AS property_id, pr.name AS property_name, pv.id AS value_id, pv.label
               FROM category_properties cp
               JOIN properties pr      ON pr.id = cp.property_id
               JOIN property_values pv ON pv.property_id = pr.id
              WHERE cp.category_id = :category_id
                AND EXISTS (
                    SELECT 1
                      FROM product_property_values ppv
                      JOIN product_categories pc ON pc.product_id = ppv.product_id
                     WHERE ppv.property_value_id = pv.id AND pc.category_id = :category_id_inner
                )
              ORDER BY pr.id, pv.id'
        );
        $statement->execute(['category_id' => $categoryId, 'category_id_inner' => $categoryId]);

        $groups = [];
        foreach ($statement->fetchAll() as $row) {
            $propertyId = (int) $row['property_id'];
            $groups[$propertyId] ??= ['id' => $propertyId, 'name' => $row['property_name'], 'values' => []];
            $groups[$propertyId]['values'][] = ['id' => (int) $row['value_id'], 'label' => $row['label']];
        }

        return array_values($groups);
    }
}
