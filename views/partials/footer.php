<?php
/**
 * @var string $appName
 * @var list<array> $navCategories
 */
?>
<footer class="site-footer">
    <div class="container site-footer__grid">
        <div>
            <a href="<?= url('/') ?>" class="brand brand--footer">
                <span class="brand__mark" aria-hidden="true"></span>
                <span class="brand__name"><?= e($appName) ?></span>
            </a>
            <p class="site-footer__text">
                Tienda de cartas coleccionables desarrollada como proyecto de portfolio con PHP 8 y MySQL.
            </p>
        </div>

        <nav aria-label="Categorías del pie">
            <h2 class="site-footer__heading">Catálogo</h2>
            <ul class="plain-list site-footer__links">
                <?php foreach ($navCategories as $navCategory): ?>
                    <li><a href="<?= url('/', ['category' => $navCategory['id']]) ?>"><?= e($navCategory['name']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </nav>

        <nav aria-label="Cuenta">
            <h2 class="site-footer__heading">Mi cuenta</h2>
            <ul class="plain-list site-footer__links">
                <li><a href="<?= url('/album') ?>">Mi álbum</a></li>
                <li><a href="<?= url('/orders') ?>">Mis pedidos</a></li>
                <li><a href="<?= url('/cart') ?>">Carrito</a></li>
            </ul>
        </nav>
    </div>

    <div class="container site-footer__legal">
        Datos e imágenes de cartas: <a href="https://tcgdex.dev" rel="noopener" target="_blank">TCGdex</a>.
        Pokémon y sus personajes son marcas de Nintendo, Creatures Inc., GAME FREAK inc. y The Pokémon Company.
        Este sitio es un proyecto de demostración sin fines comerciales y no está afiliado a ninguna de ellas.
    </div>
</footer>
