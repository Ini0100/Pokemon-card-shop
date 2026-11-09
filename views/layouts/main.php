<?php
/**
 * @var string $content
 * @var string $appName
 * @var string|null $title
 * @var \App\Core\View $view
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light dark">
    <title><?= e(isset($title) ? "{$title} · {$appName}" : "{$appName} · Tienda de cartas coleccionables") ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@600;700;800&display=swap">
    <link rel="stylesheet" href="<?= asset('assets/css/app.css') ?>">

    <script src="<?= asset('assets/js/app.js') ?>" defer></script>
</head>
<body>
    <a href="#main" class="skip-link">Saltar al contenido</a>

    <?= $view->partial('partials/header', get_defined_vars()) ?>

    <main id="main" class="site-main">
        <div class="container">
            <?= $view->partial('partials/flash', ['flashes' => $flashes]) ?>
            <?= $content ?>
        </div>
    </main>

    <?= $view->partial('partials/footer', ['appName' => $appName, 'navCategories' => $navCategories]) ?>
</body>
</html>
