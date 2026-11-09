<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\ValidationException;
use PDO;

/** Opens boosters from the user's album, granting random cards of the same collection. */
final class AlbumService
{
    /** Pack contents, one card per slot, each drawn from the listed rarity codes. */
    private const BOOSTER_SLOTS = [
        ['rare', 'rare_holo'],
        ['uncommon'],
        ['common'],
    ];

    /** Must equal count(BOOSTER_SLOTS); constant expressions cannot call count(). */
    public const CARDS_PER_BOOSTER = 3;

    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * Consumes one booster and adds its cards to the album.
     *
     * @return list<array{id: int, name: string, image_path: string}> The cards obtained.
     */
    public function openBooster(int $userId, int $boosterId): array
    {
        return Database::transaction($this->pdo, function () use ($userId, $boosterId): array {
            $owned = $this->pdo->prepare(
                'SELECT quantity FROM album_items WHERE user_id = ? AND product_id = ? FOR UPDATE'
            );
            $owned->execute([$userId, $boosterId]);
            $quantity = (int) $owned->fetchColumn();

            if ($quantity < 1) {
                throw ValidationException::withMessage('No tienes este sobre en tu álbum.');
            }

            $collectionId = $this->boosterCollection($boosterId);

            if ($collectionId === null) {
                throw ValidationException::withMessage('Este producto no es un sobre que se pueda abrir.');
            }

            $cards = $this->drawCards($collectionId);

            if ($cards === []) {
                throw ValidationException::withMessage('No hay cartas disponibles para esta colección.');
            }

            if ($quantity === 1) {
                $this->pdo->prepare('DELETE FROM album_items WHERE user_id = ? AND product_id = ?')
                    ->execute([$userId, $boosterId]);
            } else {
                $this->pdo->prepare('UPDATE album_items SET quantity = quantity - 1 WHERE user_id = ? AND product_id = ?')
                    ->execute([$userId, $boosterId]);
            }

            $addCard = $this->pdo->prepare(
                'INSERT INTO album_items (user_id, product_id, quantity) VALUES (?, ?, 1)
                 ON DUPLICATE KEY UPDATE quantity = quantity + 1'
            );

            foreach ($cards as $card) {
                $addCard->execute([$userId, $card['id']]);
            }

            return $cards;
        });
    }

    /** Collection property value of a booster, or null if the product is not a booster. */
    private function boosterCollection(int $productId): ?int
    {
        $statement = $this->pdo->prepare(
            "SELECT ppv.property_value_id
               FROM product_property_values ppv
               JOIN property_values pv    ON pv.id = ppv.property_value_id
               JOIN properties pr         ON pr.id = pv.property_id AND pr.code = 'collection'
               JOIN product_categories pc ON pc.product_id = ppv.product_id
               JOIN categories c          ON c.id = pc.category_id AND c.slug = 'boosters'
              WHERE ppv.product_id = ?
              LIMIT 1"
        );
        $statement->execute([$productId]);
        $value = $statement->fetchColumn();

        return $value === false ? null : (int) $value;
    }

    /**
     * Draws one random card per slot. A slot with no eligible card (e.g. a set
     * without uncommons) falls back to any card of the collection.
     *
     * @return list<array{id: int, name: string, image_path: string}>
     */
    private function drawCards(int $collectionValueId): array
    {
        $cards = [];

        foreach (self::BOOSTER_SLOTS as $rarities) {
            $drawnIds = array_column($cards, 'id');
            $card     = $this->randomCard($collectionValueId, $drawnIds, $rarities)
                ?? $this->randomCard($collectionValueId, $drawnIds);

            if ($card !== null) {
                $cards[] = $card;
            }
        }

        return $cards;
    }

    /**
     * @param list<int>         $excludeIds
     * @param list<string>|null $rarities Rarity codes, or null for any rarity.
     * @return array{id: int, name: string, image_path: string}|null
     */
    private function randomCard(int $collectionValueId, array $excludeIds, ?array $rarities = null): ?array
    {
        $sql = "SELECT p.id, p.name, p.image_path
                  FROM products p
                  JOIN product_categories pc       ON pc.product_id = p.id
                  JOIN categories c                ON c.id = pc.category_id AND c.slug = 'cards'
                  JOIN product_property_values ppv ON ppv.product_id = p.id AND ppv.property_value_id = ?
                 WHERE 1 = 1";
        $params = [$collectionValueId];

        if ($excludeIds !== []) {
            $sql .= ' AND p.id NOT IN (' . implode(',', array_fill(0, count($excludeIds), '?')) . ')';
            array_push($params, ...$excludeIds);
        }

        if ($rarities !== null) {
            $sql .= " AND EXISTS (SELECT 1
                                    FROM product_property_values r
                                    JOIN property_values rv ON rv.id = r.property_value_id
                                    JOIN properties rp      ON rp.id = rv.property_id AND rp.code = 'rarity'
                                   WHERE r.product_id = p.id
                                     AND rv.code IN (" . implode(',', array_fill(0, count($rarities), '?')) . '))';
            array_push($params, ...$rarities);
        }

        $statement = $this->pdo->prepare($sql . ' ORDER BY RAND() LIMIT 1');
        $statement->execute($params);

        return $statement->fetch() ?: null;
    }
}
