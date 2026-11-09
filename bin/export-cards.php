<?php

declare(strict_types=1);

/*
 * Writes the imported cards to database/cards.sql, the snapshot used by
 * bin/setup.php. Run it after `php bin/import-cards.php` to update the snapshot.
 */

use App\Services\Tcgdex\CardExporter;

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$container = require dirname(__DIR__) . '/bootstrap.php';

$path  = ROOT_PATH . '/database/cards.sql';
$count = $container->get(CardExporter::class)->export($path);

if ($count === 0) {
    fwrite(STDERR, "No imported cards found. Run `php bin/import-cards.php` first.\n");
    exit(1);
}

printf("✔ Exported %d cards to database/cards.sql\n", $count);
