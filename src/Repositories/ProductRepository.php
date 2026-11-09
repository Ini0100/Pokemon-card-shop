<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class ProductRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * One page of products matching the filter, plus the total number of matches.
     *
     * @return array{items: list<array<string, mixed>>, total: int}
     */
    public function paginate(ProductFilter $filter): array
    {
        [$where, $params] = $this->whereClause($filter);

        $count = $this->pdo->prepare("SELECT COUNT(*) FROM products p {$where}");
        $count->execute($params);

        $statement = $this->pdo->prepare(
            "SELECT p.id, p.name, p.price, p.stock, p.image_path,
                    (SELECT pv.label
                       FROM product_property_values ppv
                       JOIN property_values pv ON pv.id = ppv.property_value_id
                       JOIN properties pr      ON pr.id = pv.property_id AND pr.code = 'collection'
                      WHERE ppv.product_id = p.id
                      LIMIT 1) AS collection
               FROM products p {$where}
              ORDER BY {$filter->orderBySql()}
              LIMIT {$filter->perPage} OFFSET {$filter->offset()}"
        );
        $statement->execute($params);

        return ['items' => $statement->fetchAll(), 'total' => (int) $count->fetchColumn()];
    }

    /**
     * Faceted search: selected values of the same property are OR-ed,
     * different properties are AND-ed (e.g. (Fire OR Water) AND Rare).
     *
     * @return array{string, list<mixed>} The WHERE clause (possibly empty) and its parameters.
     */
    private function whereClause(ProductFilter $filter): array
    {
        $conditions = [];
        $params     = [];

        if ($filter->categoryId !== null) {
            $conditions[] = 'EXISTS (SELECT 1 FROM product_categories pc
                                     WHERE pc.product_id = p.id AND pc.category_id = ?)';
            $params[] = $filter->categoryId;
        }

        if ($filter->search !== '') {
            $conditions[] = '(p.name LIKE ? OR p.description LIKE ?)';
            $term = '%' . addcslashes($filter->search, '%_\\') . '%';
            array_push($params, $term, $term);
        }

        if ($filter->minPrice !== null) {
            $conditions[] = 'p.price >= ?';
            $params[] = $filter->minPrice;
        }

        if ($filter->maxPrice !== null) {
            $conditions[] = 'p.price <= ?';
            $params[] = $filter->maxPrice;
        }

        foreach ($this->groupValuesByProperty($filter->propertyValueIds) as $valueIds) {
            $placeholders = implode(',', array_fill(0, count($valueIds), '?'));
            $conditions[] = "EXISTS (SELECT 1 FROM product_property_values ppv
                                     WHERE ppv.product_id = p.id AND ppv.property_value_id IN ({$placeholders}))";
            array_push($params, ...$valueIds);
        }

        return [$conditions === [] ? '' : 'WHERE ' . implode(' AND ', $conditions), $params];
    }

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM products WHERE id = ?');
        $statement->execute([$id]);

        return $statement->fetch() ?: null;
    }

    /**
     * Human-readable attributes of a product, e.g. [['property' => 'Rareza', 'value' => 'Rara']].
     *
     * @return list<array{property: string, value: string}>
     */
    public function attributes(int $productId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT pr.name AS property, pv.label AS value
               FROM product_property_values ppv
               JOIN property_values pv ON pv.id = ppv.property_value_id
               JOIN properties pr      ON pr.id = pv.property_id
              WHERE ppv.product_id = ?
              ORDER BY pr.id'
        );
        $statement->execute([$productId]);

        return $statement->fetchAll();
    }

    /** @return list<array<string, mixed>> */
    public function allWithCategories(): array
    {
        return $this->pdo->query(
            "SELECT p.id, p.name, p.price, p.stock, p.shipping_cost,
                    GROUP_CONCAT(c.name ORDER BY c.name SEPARATOR ', ') AS categories
               FROM products p
               LEFT JOIN product_categories pc ON pc.product_id = p.id
               LEFT JOIN categories c          ON c.id = pc.category_id
              GROUP BY p.id
              ORDER BY p.id"
        )->fetchAll();
    }

    /**
     * @param array{name: string, description: string, price: float, shipping_cost: float, stock: int, image_path: string} $data
     */
    public function create(array $data, int $categoryId): int
    {
        return Database::transaction($this->pdo, function () use ($data, $categoryId): int {
            $this->pdo->prepare(
                'INSERT INTO products (name, description, price, shipping_cost, stock, image_path)
                 VALUES (:name, :description, :price, :shipping_cost, :stock, :image_path)'
            )->execute($data);

            $productId = (int) $this->pdo->lastInsertId();

            $this->pdo->prepare('INSERT INTO product_categories (product_id, category_id) VALUES (?, ?)')
                ->execute([$productId, $categoryId]);

            return $productId;
        });
    }

    public function delete(int $id): void
    {
        $this->pdo->prepare('DELETE FROM products WHERE id = ?')->execute([$id]);
    }

    /**
     * @param list<int> $valueIds
     * @return array<int, list<int>> Value IDs keyed by property ID.
     */
    private function groupValuesByProperty(array $valueIds): array
    {
        if ($valueIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($valueIds), '?'));
        $statement    = $this->pdo->prepare(
            "SELECT id, property_id FROM property_values WHERE id IN ({$placeholders})"
        );
        $statement->execute($valueIds);

        $groups = [];
        foreach ($statement->fetchAll() as $row) {
            $groups[(int) $row['property_id']][] = (int) $row['id'];
        }

        return $groups;
    }
}
