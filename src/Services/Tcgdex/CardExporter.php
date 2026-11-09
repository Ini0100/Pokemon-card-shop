<?php

declare(strict_types=1);

namespace App\Services\Tcgdex;

use PDO;

/**
 * Dumps the imported cards to a portable SQL file (database/cards.sql) so a
 * fresh install does not need to call the TCGdex API.
 *
 * The dump references categories and attributes by code and products by
 * `external_id`, never by auto-increment IDs, and can be loaded repeatedly.
 * It contains card data (MIT-licensed, from TCGdex) and image URLs only.
 */
final class CardExporter
{
    private const ROWS_PER_INSERT = 50;

    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return int Number of exported cards. */
    public function export(string $path): int
    {
        $products = $this->pdo->query(
            "SELECT id, external_id, name, description, price, shipping_cost, stock, image_path
               FROM products
              WHERE external_id LIKE 'tcgdex:%'
              ORDER BY id"
        )->fetchAll();

        $attributes = $this->pdo->query(
            "SELECT p.external_id, pr.code AS property_code, pv.code AS value_code, pv.label
               FROM product_property_values ppv
               JOIN products p         ON p.id = ppv.product_id
               JOIN property_values pv ON pv.id = ppv.property_value_id
               JOIN properties pr      ON pr.id = pv.property_id
              WHERE p.external_id LIKE 'tcgdex:%'
              ORDER BY p.id, pr.id"
        )->fetchAll();

        $sql = [$this->header(count($products)), 'SET NAMES utf8mb4;'];

        $sql[] = '-- Attribute values used by the cards';
        foreach ($this->distinctValues($attributes) as $value) {
            $sql[] = sprintf(
                'INSERT INTO property_values (property_id, code, label) SELECT id, %s, %s FROM properties WHERE code = %s'
                . "\n    ON DUPLICATE KEY UPDATE label = VALUES(label);",
                $this->quote($value['value_code']),
                $this->quote($value['label']),
                $this->quote($value['property_code']),
            );
        }

        $sql[] = "\n-- Cards (re-loading refreshes catalog data but keeps the shop's stock)";
        foreach (array_chunk($products, self::ROWS_PER_INSERT) as $chunk) {
            $rows = array_map(fn (array $p): string => sprintf(
                '(%s, %s, %s, %s, %s, %d, %s)',
                $this->quote($p['external_id']),
                $this->quote($p['name']),
                $this->quote($p['description']),
                $p['price'],
                $p['shipping_cost'],
                $p['stock'],
                $this->quote($p['image_path']),
            ), $chunk);

            $sql[] = "INSERT INTO products (external_id, name, description, price, shipping_cost, stock, image_path) VALUES\n"
                . implode(",\n", $rows) . "\n"
                . 'ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description), price = VALUES(price),'
                . ' shipping_cost = VALUES(shipping_cost), image_path = VALUES(image_path);';
        }

        $sql[] = "\n-- Every imported card belongs to the \"cards\" category";
        $sql[] = "INSERT IGNORE INTO product_categories (product_id, category_id)\n"
            . "SELECT p.id, c.id FROM products p JOIN categories c ON c.slug = 'cards' WHERE p.external_id LIKE 'tcgdex:%';";

        $sql[] = "\n-- Card attributes, linked by codes through a temporary table";
        $sql[] = 'CREATE TEMPORARY TABLE card_attributes (external_id VARCHAR(64), property_code VARCHAR(50), value_code VARCHAR(50));';

        foreach (array_chunk($attributes, self::ROWS_PER_INSERT * 3) as $chunk) {
            $rows = array_map(fn (array $a): string => sprintf(
                '(%s, %s, %s)',
                $this->quote($a['external_id']),
                $this->quote($a['property_code']),
                $this->quote($a['value_code']),
            ), $chunk);

            $sql[] = "INSERT INTO card_attributes VALUES\n" . implode(",\n", $rows) . ';';
        }

        $sql[] = "INSERT IGNORE INTO product_property_values (product_id, property_value_id)\n"
            . "SELECT p.id, pv.id\n"
            . "  FROM card_attributes a\n"
            . "  JOIN products p         ON p.external_id = a.external_id\n"
            . "  JOIN properties pr      ON pr.code = a.property_code\n"
            . "  JOIN property_values pv ON pv.property_id = pr.id AND pv.code = a.value_code;";
        $sql[] = 'DROP TEMPORARY TABLE card_attributes;';

        file_put_contents($path, implode("\n", $sql) . "\n");

        return count($products);
    }

    /**
     * @param list<array<string, string>> $attributes
     * @return list<array<string, string>>
     */
    private function distinctValues(array $attributes): array
    {
        $values = [];

        foreach ($attributes as $attribute) {
            $values[$attribute['property_code'] . ':' . $attribute['value_code']] = $attribute;
        }

        return array_values($values);
    }

    private function header(int $count): string
    {
        return <<<SQL
            -- ---------------------------------------------------------------------------
            -- TCG Market — trading card catalog ({$count} cards)
            -- Generated by `php bin/export-cards.php`; do not edit by hand.
            --
            -- Card data from TCGdex (https://github.com/tcgdex/cards-database), MIT License.
            -- Images are referenced by URL and served by the TCGdex CDN; none are included.
            -- Pokémon and all related names are trademarks of Nintendo, Creatures Inc.,
            -- GAME FREAK inc. and The Pokémon Company.
            --
            -- Load after schema.sql and seed.sql (bin/setup.php does it automatically).
            -- ---------------------------------------------------------------------------
            SQL;
    }

    private function quote(string $value): string
    {
        return $this->pdo->quote($value);
    }
}
