<?php

declare(strict_types=1);

/*
 * One-step installation: creates the database and loads the schema, the demo
 * data and the card catalog snapshot. No internet connection is required.
 *
 * Usage:
 *   php bin/setup.php            Install into an empty (or missing) database
 *   php bin/setup.php --fresh    Wipe an existing installation and reinstall
 *   php bin/setup.php --import   Also refresh cards and prices from TCGdex
 */

use App\Core\Config;
use App\Core\SqlScript;
use App\Services\Tcgdex\CardImporter;

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$container = require dirname(__DIR__) . '/bootstrap.php';
$config    = $container->get(Config::class);

$options = array_slice($argv, 1);
$fresh   = in_array('--fresh', $options, true);
$import  = in_array('--import', $options, true);

$database = (string) $config->get('database.name');

if (!preg_match('/^[A-Za-z0-9_\-]+$/', $database)) {
    fwrite(STDERR, "✖ Invalid database name in .env: {$database}\n");
    exit(1);
}

// 1. Connect to the server (without selecting a database) and create it if needed.
try {
    $server = new PDO(
        sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $config->get('database.host'), $config->get('database.port')),
        (string) $config->get('database.username'),
        (string) $config->get('database.password'),
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
    );
} catch (PDOException $e) {
    fwrite(STDERR, "✖ Cannot connect to MySQL: {$e->getMessage()}\n  Is MySQL running and are the DB_* values in .env correct?\n");
    exit(1);
}

$server->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$server->exec("USE `{$database}`");

// 2. Refuse to overwrite existing data unless explicitly asked to.
$tables = $server->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);

if ($tables !== [] && !$fresh) {
    fwrite(STDERR, "✖ Database `{$database}` already contains tables.\n  Run with --fresh to delete ALL its data and reinstall.\n");
    exit(1);
}

// 3. Load schema, demo data and the card snapshot.
$steps = [
    'schema.sql' => 'Schema',
    'seed.sql'   => 'Demo data (users, categories, accessories)',
    'cards.sql'  => 'Card catalog snapshot',
];

foreach ($steps as $file => $label) {
    $count = SqlScript::run($server, ROOT_PATH . '/database/' . $file);
    printf("✔ %-45s %3d statements\n", $label, $count);
}

$cards = (int) $server->query("SELECT COUNT(*) FROM products WHERE external_id LIKE 'tcgdex:%'")->fetchColumn();
printf("✔ Installed into `%s`: %d cards.\n", $database, $cards);

// 4. Optionally refresh the catalog from the live API.
if ($import) {
    echo "\nRefreshing cards from TCGdex (needs internet, takes a few minutes)…\n";
    $importer = $container->get(CardImporter::class);

    foreach (['base1', 'base2', 'base3'] as $setId) {
        $result = $importer->importSet($setId);
        printf("✔ %s: %d new, %d updated, %d failed\n", $result['set'], $result['created'], $result['updated'], count($result['failed']));
    }
}

echo "\nDone. Start the app with: php -S localhost:8000 -t public public/index.php\n";
echo "Demo accounts: admin@tcgmarket.test / admin1234 · demo@tcgmarket.test / demo1234\n";
