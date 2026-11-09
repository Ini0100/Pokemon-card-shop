<?php
/**
 * Page links that preserve the current filters.
 *
 * @var \App\Repositories\ProductFilter $filter
 * @var int $pages
 */
if ($pages <= 1) {
    return;
}

$current = $filter->page;
$link    = static fn (int $page): string => url('/', $filter->toQuery(['page' => $page > 1 ? $page : null]));

// Always show first/last pages and a window of two pages around the current one.
$visible = array_unique(array_filter(
    [1, $current - 2, $current - 1, $current, $current + 1, $current + 2, $pages],
    static fn (int $page): bool => $page >= 1 && $page <= $pages,
));
sort($visible);
?>
<nav class="pagination" aria-label="Paginación">
    <?php if ($current > 1): ?>
        <a href="<?= e($link($current - 1)) ?>" class="pagination__link" rel="prev">← Anterior</a>
    <?php endif; ?>

    <?php $previous = 0; ?>
    <?php foreach ($visible as $page): ?>
        <?php if ($page - $previous > 1): ?>
            <span class="pagination__gap" aria-hidden="true">…</span>
        <?php endif; ?>
        <?php if ($page === $current): ?>
            <span class="pagination__link is-current" aria-current="page"><?= $page ?></span>
        <?php else: ?>
            <a href="<?= e($link($page)) ?>" class="pagination__link"><?= $page ?></a>
        <?php endif; ?>
        <?php $previous = $page; ?>
    <?php endforeach; ?>

    <?php if ($current < $pages): ?>
        <a href="<?= e($link($current + 1)) ?>" class="pagination__link" rel="next">Siguiente →</a>
    <?php endif; ?>
</nav>
