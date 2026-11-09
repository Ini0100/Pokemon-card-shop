<?php
/**
 * @var \App\Repositories\ProductFilter $filter
 * @var array|null $category
 * @var list<array> $filters
 * @var list<array> $products   Current page
 * @var int $total              Matches across all pages
 * @var int $pages
 * @var list<array> $featuredGroups
 * @var list<array> $heroCards
 * @var \App\Core\View $view
 */
use App\Repositories\ProductFilter;

$icon       = static fn (string $name): string => $view->partial('partials/icon', ['name' => $name]);
$resultText = $total . ' producto' . ($total === 1 ? '' : 's');
?>

<?php if ($featuredGroups !== []): ?>
    <section class="hero">
        <div class="hero__content">
            <p class="eyebrow"><?= $icon('sparkles') ?> Edición Básica, Jungla y Fósil · Las expansiones de 1999</p>
            <h1 class="hero__title">Completa tu colección, <span class="text-gradient">sobre a sobre</span></h1>
            <p class="hero__lead">
                Compra sobres, cartas sueltas y accesorios. Abre tus sobres en el álbum digital y descubre qué cartas te tocan.
            </p>
            <div class="hero__actions">
                <a href="<?= url('/', ['category' => $featuredGroups[0]['id']]) ?>" class="button button--accent button--large">
                    Ver sobres <?= $icon('arrow') ?>
                </a>
                <a href="<?= url('/album') ?>" class="button button--glass button--large">Mi álbum</a>
            </div>
        </div>
        <div class="hero__art" aria-hidden="true">
            <?php foreach ($heroCards as $card): ?>
                <img src="<?= product_image($card['image_path'], 'high') ?>" alt="">
            <?php endforeach; ?>
        </div>
    </section>

    <ul class="perks plain-list">
        <li><?= $icon('truck') ?> Envío combinado: pagas solo el envío más caro del pedido</li>
        <li><?= $icon('lock') ?> Stock reservado mientras completas tu compra</li>
        <li><?= $icon('album') ?> Álbum digital con todas tus cartas</li>
    </ul>

    <section class="section">
        <header class="section__header">
            <h2>Explora por categoría</h2>
        </header>
        <div class="category-grid">
            <?php foreach ($featuredGroups as $group): ?>
                <a href="<?= url('/', ['category' => $group['id']]) ?>" class="category-tile">
                    <?php if ($group['cover_image']): ?>
                        <img src="<?= product_image($group['cover_image']) ?>" alt="" loading="lazy">
                    <?php endif; ?>
                    <span class="category-tile__name"><?= e($group['name']) ?></span>
                    <span class="category-tile__count"><?= (int) $group['product_count'] ?> productos</span>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="section">
        <header class="section__header">
            <h2>Todo el catálogo</h2>
            <p class="muted"><?= $resultText ?></p>
        </header>
        <div class="product-grid">
            <?php foreach ($products as $product): ?>
                <?= $view->partial('partials/product-card', ['product' => $product]) ?>
            <?php endforeach; ?>
        </div>
        <?= $view->partial('partials/pagination', ['filter' => $filter, 'pages' => $pages]) ?>
    </section>

<?php else: ?>
    <div class="catalog">
        <details class="filters-panel" data-filters open>
            <summary class="filters-panel__toggle"><?= $icon('filter') ?> Filtros</summary>

            <form method="get" action="<?= url('/') ?>" class="filters">
                <?php if ($category): ?>
                    <input type="hidden" name="category" value="<?= (int) $category['id'] ?>">
                <?php endif; ?>

                <div class="field">
                    <label for="q">Buscar</label>
                    <input type="search" id="q" name="q" value="<?= e($filter->search) ?>" placeholder="Nombre o descripción">
                </div>

                <?php foreach ($filters as $group): ?>
                    <fieldset class="filters__group">
                        <legend><?= e($group['name']) ?></legend>
                        <div class="chip-group">
                            <?php foreach ($group['values'] as $value): ?>
                                <label class="chip">
                                    <input type="checkbox" name="values[]" value="<?= $value['id'] ?>"
                                        <?= in_array($value['id'], $filter->propertyValueIds, true) ? 'checked' : '' ?>>
                                    <span><?= e($value['label']) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </fieldset>
                <?php endforeach; ?>

                <fieldset class="filters__group">
                    <legend>Precio (€)</legend>
                    <div class="field-row">
                        <input type="number" name="min_price" min="0" step="0.01" placeholder="Mín."
                               value="<?= e($filter->minPrice) ?>" aria-label="Precio mínimo">
                        <span class="muted" aria-hidden="true">–</span>
                        <input type="number" name="max_price" min="0" step="0.01" placeholder="Máx."
                               value="<?= e($filter->maxPrice) ?>" aria-label="Precio máximo">
                    </div>
                </fieldset>

                <div class="filters__actions">
                    <button type="submit" class="button button--primary button--block">Aplicar filtros</button>
                    <a href="<?= url('/', $category ? ['category' => $category['id']] : []) ?>" class="button button--ghost button--block">
                        Limpiar
                    </a>
                </div>
            </form>
        </details>

        <section class="catalog__results">
            <header class="catalog__toolbar">
                <div>
                    <h1><?= e($category['name'] ?? ($filter->isFiltered() ? 'Resultados' : 'Todos los productos')) ?></h1>
                    <p class="muted">
                        <?= $resultText ?>
                        <?php if ($filter->search !== ''): ?>
                            para «<?= e($filter->search) ?>»
                        <?php endif; ?>
                    </p>
                </div>

                <form method="get" action="<?= url('/') ?>" class="sort-form" data-autosubmit>
                    <?php foreach ($filter->propertyValueIds as $valueId): ?>
                        <input type="hidden" name="values[]" value="<?= $valueId ?>">
                    <?php endforeach; ?>
                    <?php foreach (['category' => $category['id'] ?? null, 'q' => $filter->search, 'min_price' => $filter->minPrice, 'max_price' => $filter->maxPrice] as $name => $value): ?>
                        <?php if ($value !== null && $value !== ''): ?>
                            <input type="hidden" name="<?= $name ?>" value="<?= e($value) ?>">
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <label for="sort" class="muted">Ordenar</label>
                    <select id="sort" name="sort">
                        <?php foreach (ProductFilter::SORT_OPTIONS as $value => $label): ?>
                            <option value="<?= e($value) ?>" <?= $filter->sort === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <noscript><button type="submit" class="button button--small">Ordenar</button></noscript>
                </form>
            </header>

            <?php if ($products === []): ?>
                <div class="empty-state">
                    <?= $icon('search') ?>
                    <p>No hay productos que coincidan con tu búsqueda.</p>
                    <a href="<?= url('/', $category ? ['category' => $category['id']] : []) ?>" class="button">Quitar filtros</a>
                </div>
            <?php else: ?>
                <div class="product-grid">
                    <?php foreach ($products as $product): ?>
                        <?= $view->partial('partials/product-card', ['product' => $product]) ?>
                    <?php endforeach; ?>
                </div>
            <?= $view->partial('partials/pagination', ['filter' => $filter, 'pages' => $pages]) ?>
            <?php endif; ?>
        </section>
    </div>
<?php endif; ?>
