<?php

declare(strict_types=1);

namespace App\Services\Tcgdex;

use App\Core\Database;
use PDO;
use RuntimeException;

/**
 * Imports the cards of a TCGdex set into the catalog.
 *
 * Only card data and the remote image URL are stored: images are served by
 * the TCGdex CDN and never copied into this project. The import is idempotent:
 * existing cards (matched by `external_id`) are updated and keep their stock.
 */
final class CardImporter
{
    /** Card data is only complete in English for vintage sets; set names exist in Spanish. */
    private const DATA_LANGUAGE = 'en';
    private const LABEL_LANGUAGE = 'es';

    /** Pause between API calls to be gentle with a free community service. */
    private const REQUEST_DELAY_MICROSECONDS = 50_000;

    private const KINDS = [
        'Pokemon' => ['pokemon', 'Pokémon'],
        'Trainer' => ['trainer', 'Entrenador'],
        'Energy'  => ['energy', 'Energía'],
    ];

    private const ELEMENTS = [
        'Grass'     => ['grass', 'Planta'],
        'Fire'      => ['fire', 'Fuego'],
        'Water'     => ['water', 'Agua'],
        'Lightning' => ['lightning', 'Eléctrico'],
        'Psychic'   => ['psychic', 'Psíquico'],
        'Fighting'  => ['fighting', 'Lucha'],
        'Colorless' => ['colorless', 'Incoloro'],
        'Darkness'  => ['darkness', 'Siniestro'],
        'Metal'     => ['metal', 'Metálico'],
        'Dragon'    => ['dragon', 'Dragón'],
        'Fairy'     => ['fairy', 'Hada'],
    ];

    /** Shop rules per rarity: label, minimum/fallback price (€), initial stock, shipping (€). */
    private const RARITIES = [
        'common'    => ['label' => 'Común',      'min_price' => 0.50, 'fallback_price' => 0.50,  'stock' => 40, 'shipping' => 0.90],
        'uncommon'  => ['label' => 'Poco común', 'min_price' => 0.75, 'fallback_price' => 1.00,  'stock' => 25, 'shipping' => 0.90],
        'rare'      => ['label' => 'Rara',       'min_price' => 1.50, 'fallback_price' => 4.00,  'stock' => 12, 'shipping' => 0.90],
        'rare_holo' => ['label' => 'Rara holo',  'min_price' => 5.00, 'fallback_price' => 25.00, 'stock' => 5,  'shipping' => 2.50],
    ];

    private const DEFAULT_RARITY_RULES = ['min_price' => 0.50, 'fallback_price' => 1.00, 'stock' => 20, 'shipping' => 0.90];

    /** @var array<string, int> Cache of property value IDs keyed by "property:code". */
    private array $valueIds = [];

    public function __construct(
        private readonly PDO $pdo,
        private readonly TcgdexClient $client,
    ) {
    }

    /**
     * @param (callable(string): void)|null $onProgress Receives one line per imported card.
     * @return array{set: string, created: int, updated: int, failed: list<string>}
     */
    public function importSet(string $setId, ?callable $onProgress = null): array
    {
        $set     = $this->client->set(self::DATA_LANGUAGE, $setId);
        $setName = $this->localizedSetName($setId) ?? $set['name'];
        $total   = (int) ($set['cardCount']['official'] ?? count($set['cards'] ?? []));

        if (empty($set['cards'])) {
            throw new RuntimeException("Set [{$setId}] has no cards available.");
        }

        $categoryId   = $this->cardsCategoryId();
        $collectionId = $this->propertyValueId('collection', $setId, $setName);
        $result       = ['set' => $setName, 'created' => 0, 'updated' => 0, 'failed' => []];

        foreach ($set['cards'] as $summary) {
            usleep(self::REQUEST_DELAY_MICROSECONDS);

            // One unavailable card must not abort the whole set; re-running the import completes it.
            try {
                $card = $this->client->card(self::DATA_LANGUAGE, $summary['id']);

                $created = Database::transaction($this->pdo, fn (): bool => $this->saveCard(
                    $card, $setName, $total, $categoryId, $collectionId,
                ));
            } catch (RuntimeException $e) {
                $result['failed'][] = $summary['id'];
                $onProgress && $onProgress(sprintf('  ! %-28s %s (%s)', $summary['name'], $summary['id'], $e->getMessage()));

                continue;
            }

            $result[$created ? 'created' : 'updated']++;
            $onProgress && $onProgress(sprintf('  %s %-28s %s', $created ? '+' : '~', $card['name'], $card['id']));
        }

        return $result;
    }

    /** @param array<string, mixed> $card */
    private function saveCard(array $card, string $setName, int $setTotal, int $categoryId, int $collectionId): bool
    {
        $externalId = 'tcgdex:' . $card['id'];
        $rarity     = $this->rarityCode($card);
        $rules      = self::RARITIES[$rarity] ?? self::DEFAULT_RARITY_RULES;
        $existingId = $this->productIdByExternalId($externalId);

        $data = [
            // Vintage sets print most rares twice (holo and non-holo) under the same name.
            'name'          => $rarity === 'rare_holo' ? "{$card['name']} (Holo)" : $card['name'],
            'description'   => $this->describe($card, $setName, $setTotal),
            'price'         => $this->price($card, $rules),
            'shipping_cost' => $rules['shipping'],
            'image_path'    => $card['image'],
        ];

        if ($existingId === null) {
            $this->pdo->prepare(
                'INSERT INTO products (external_id, name, description, price, shipping_cost, stock, image_path)
                 VALUES (:external_id, :name, :description, :price, :shipping_cost, :stock, :image_path)'
            )->execute($data + ['external_id' => $externalId, 'stock' => $rules['stock']]);

            $productId = (int) $this->pdo->lastInsertId();
        } else {
            // Refresh catalog data and price, but never overwrite the shop's stock.
            $this->pdo->prepare(
                'UPDATE products
                    SET name = :name, description = :description, price = :price,
                        shipping_cost = :shipping_cost, image_path = :image_path
                  WHERE id = :id'
            )->execute($data + ['id' => $existingId]);

            $productId = $existingId;
        }

        $this->pdo->prepare('INSERT IGNORE INTO product_categories (product_id, category_id) VALUES (?, ?)')
            ->execute([$productId, $categoryId]);

        $this->replaceAttributes($productId, $this->attributeValueIds($card, $rarity, $collectionId));

        return $existingId === null;
    }

    /** @return list<int> */
    private function attributeValueIds(array $card, string $rarity, int $collectionId): array
    {
        $ids = [$collectionId];

        if (isset(self::KINDS[$card['category']])) {
            [$code, $label] = self::KINDS[$card['category']];
            $ids[] = $this->propertyValueId('card_kind', $code, $label);
        }

        $rarityLabel = self::RARITIES[$rarity]['label'] ?? (string) ($card['rarity'] ?? $rarity);
        $ids[] = $this->propertyValueId('rarity', $rarity, $rarityLabel);

        foreach ($card['types'] ?? [] as $type) {
            [$code, $label] = self::ELEMENTS[$type] ?? [self::slug($type), $type];
            $ids[] = $this->propertyValueId('element', $code, $label);
        }

        return array_values(array_unique($ids));
    }

    /** @param list<int> $valueIds */
    private function replaceAttributes(int $productId, array $valueIds): void
    {
        $this->pdo->prepare('DELETE FROM product_property_values WHERE product_id = ?')->execute([$productId]);

        $insert = $this->pdo->prepare(
            'INSERT INTO product_property_values (product_id, property_value_id) VALUES (?, ?)'
        );

        foreach ($valueIds as $valueId) {
            $insert->execute([$productId, $valueId]);
        }
    }

    /**
     * TCGdex reports vintage holofoil rares as "Rare" with only a holo variant.
     *
     * @param array<string, mixed> $card
     */
    private function rarityCode(array $card): string
    {
        $rarity   = self::slug((string) ($card['rarity'] ?? 'common'));
        $variants = $card['variants'] ?? [];

        if ($rarity === 'rare' && !empty($variants['holo']) && empty($variants['normal'])) {
            return 'rare_holo';
        }

        return $rarity;
    }

    /**
     * Cardmarket trend price (EUR) when available, never below the rarity minimum.
     *
     * @param array<string, mixed> $card
     * @param array<string, float|int> $rules
     */
    private function price(array $card, array $rules): float
    {
        $market = $card['pricing']['cardmarket'] ?? [];
        $price  = $market['trend'] ?? $market['avg'] ?? null;

        if (!is_numeric($price) || $price <= 0) {
            $price = $rules['fallback_price'];
        }

        return round(max((float) $price, $rules['min_price']), 2);
    }

    /** @param array<string, mixed> $card */
    private function describe(array $card, string $setName, int $setTotal): string
    {
        $number = sprintf('n.º %s/%d', $card['localId'], $setTotal);

        $description = match ($card['category']) {
            'Trainer' => "Carta de Entrenador de la expansión {$setName} ({$number}).",
            'Energy'  => "Carta de Energía de la expansión {$setName} ({$number}).",
            default   => sprintf(
                'Carta Pokémon%s de la expansión %s (%s).',
                isset($card['types'][0]) ? ' de tipo ' . (self::ELEMENTS[$card['types'][0]][1] ?? $card['types'][0]) : '',
                $setName,
                $number,
            ),
        };

        if (!empty($card['illustrator'])) {
            $description .= " Ilustración de {$card['illustrator']}.";
        }

        return $description;
    }

    private function localizedSetName(string $setId): ?string
    {
        try {
            return $this->client->set(self::LABEL_LANGUAGE, $setId)['name'] ?? null;
        } catch (RuntimeException) {
            return null;
        }
    }

    private function cardsCategoryId(): int
    {
        $id = $this->pdo->query("SELECT id FROM categories WHERE slug = 'cards'")->fetchColumn();

        return $id !== false ? (int) $id : throw new RuntimeException('Category "cards" is missing. Run database/seed.sql first.');
    }

    private function productIdByExternalId(string $externalId): ?int
    {
        $statement = $this->pdo->prepare('SELECT id FROM products WHERE external_id = ?');
        $statement->execute([$externalId]);
        $id = $statement->fetchColumn();

        return $id === false ? null : (int) $id;
    }

    /** Returns the ID of a property value, creating it (or refreshing its label) if needed. */
    private function propertyValueId(string $propertyCode, string $valueCode, string $label): int
    {
        $key = "{$propertyCode}:{$valueCode}";

        if (isset($this->valueIds[$key])) {
            return $this->valueIds[$key];
        }

        $propertyId = $this->pdo->prepare('SELECT id FROM properties WHERE code = ?');
        $propertyId->execute([$propertyCode]);
        $propertyId = $propertyId->fetchColumn();

        if ($propertyId === false) {
            throw new RuntimeException("Property \"{$propertyCode}\" is missing. Run database/seed.sql first.");
        }

        $this->pdo->prepare(
            'INSERT INTO property_values (property_id, code, label) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE label = VALUES(label), id = LAST_INSERT_ID(id)'
        )->execute([$propertyId, $valueCode, $label]);

        return $this->valueIds[$key] = (int) $this->pdo->lastInsertId();
    }

    private static function slug(string $value): string
    {
        return trim(preg_replace('/[^a-z0-9]+/', '_', strtolower($value)), '_');
    }
}
