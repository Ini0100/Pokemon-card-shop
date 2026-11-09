<?php
/**
 * @var string $appName
 * @var array|null $currentUser
 * @var list<array> $navCategories
 * @var int $cartCount
 * @var array|null $category
 * @var \App\Core\View $view
 */
$activeCategoryId = isset($category['id']) ? (int) $category['id'] : null;
$icon = static fn (string $name): string => $view->partial('partials/icon', ['name' => $name]);
?>
<header class="site-header">
    <div class="container site-header__bar">
        <a href="<?= url('/') ?>" class="brand" aria-label="<?= e($appName) ?>, inicio">
            <span class="brand__mark" aria-hidden="true"></span>
            <span class="brand__name"><?= e($appName) ?></span>
        </a>

        <form method="get" action="<?= url('/') ?>" class="header-search" role="search">
            <?= $icon('search') ?>
            <label for="header-search" class="visually-hidden">Buscar productos</label>
            <input type="search" id="header-search" name="q" placeholder="Buscar cartas, sobres, accesorios…">
        </form>

        <nav class="user-nav" aria-label="Cuenta">
            <?php if ($currentUser): ?>
                <?php if ($currentUser['role'] === 'admin'): ?>
                    <a href="<?= url('/admin') ?>" class="icon-link" title="Administración">
                        <?= $icon('shield') ?><span class="icon-link__label">Admin</span>
                    </a>
                <?php endif; ?>
                <a href="<?= url('/album') ?>" class="icon-link" title="Mi álbum">
                    <?= $icon('album') ?><span class="icon-link__label">Álbum</span>
                </a>
                <a href="<?= url('/orders') ?>" class="icon-link" title="Mis pedidos">
                    <?= $icon('package') ?><span class="icon-link__label">Pedidos</span>
                </a>
                <a href="<?= url('/cart') ?>" class="icon-link icon-link--cart" title="Carrito">
                    <?= $icon('cart') ?><span class="icon-link__label">Carrito</span>
                    <?php if ($cartCount > 0): ?>
                        <span class="counter" aria-label="<?= (int) $cartCount ?> artículos"><?= (int) $cartCount ?></span>
                    <?php endif; ?>
                </a>
                <form method="post" action="<?= url('/logout') ?>">
                    <?= csrf_field() ?>
                    <button type="submit" class="icon-link" title="Cerrar sesión">
                        <?= $icon('logout') ?><span class="visually-hidden">Cerrar sesión</span>
                    </button>
                </form>
            <?php else: ?>
                <a href="<?= url('/register') ?>" class="button button--ghost">Crear cuenta</a>
                <a href="<?= url('/login') ?>" class="button button--primary"><?= $icon('user') ?>Entrar</a>
            <?php endif; ?>
        </nav>
    </div>

    <nav class="category-nav" aria-label="Categorías">
        <div class="container category-nav__list">
            <a href="<?= url('/') ?>" class="category-nav__link<?= $activeCategoryId === null ? ' is-active' : '' ?>">Todo</a>
            <?php foreach ($navCategories as $navCategory): ?>
                <a href="<?= url('/', ['category' => $navCategory['id']]) ?>"
                   class="category-nav__link<?= $activeCategoryId === (int) $navCategory['id'] ? ' is-active' : '' ?>"
                   <?= $activeCategoryId === (int) $navCategory['id'] ? 'aria-current="page"' : '' ?>>
                    <?= e($navCategory['name']) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </nav>
</header>
