<?php

declare(strict_types=1);

/*
 * Imports Pokémon TCG cards from TCGdex into the catalog.
 *
 * Usage:
 *   php bin/import-cards.php                 Base Set, Jungle and Fossil
 *   php bin/import-cards.php base1 base4     Any TCGdex set IDs
 *
 * Safe to run repeatedly: existing cards are updated and keep their stock.
 */

use App\Services\Tcgdex\CardImporter;

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$container = require dirname(__DIR__) . '/bootstrap.php';

$setIds = array_slice($argv, 1) ?: ['base1', 'base2', 'base3'];

foreach ($setIds as $setId) {
    if (!preg_match('/^[a-z0-9.\-]+$/i', $setId)) {
        fwrite(STDERR, "Invalid set ID: {$setId}\n");
        exit(1);
    }
}

$importer = $container->get(CardImporter::class);
$started  = microtime(true);
$exitCode = 0;

foreach ($setIds as $setId) {
    echo "Importing {$setId}…\n";

    try {
        $result = $importer->importSet($setId, static function (string $line): void {
            echo $line, "\n";
        });

        printf("✔ %s: %d new, %d updated\n", $result['set'], $result['created'], $result['updated']);

        if ($result['failed'] !== []) {
            fwrite(STDERR, sprintf(
                "⚠ %d card(s) could not be fetched: %s. Run the import again to retry them.\n",
                count($result['failed']),
                implode(', ', $result['failed']),
            ));
            $exitCode = 1;
        }

        echo "\n";
    } catch (Throwable $e) {
        fwrite(STDERR, "✖ {$setId}: {$e->getMessage()}\n\n");
        $exitCode = 1;
    }
}

printf("Done in %.1fs. Card images are served by the TCGdex CDN (https://tcgdex.dev).\n", microtime(true) - $started);
exit($exitCode);
