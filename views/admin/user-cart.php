<?php
/**
 * @var array $user
 * @var list<array> $items
 * @var array{subtotal: float, shipping: float, total: float} $totals
 */
?>
<a href="<?= url('/admin') ?>" class="back-link">← Panel de administración</a>

<header class="page-header">
    <h1>Carrito de <?= e($user['email']) ?></h1>
</header>

<?php if ($items === []): ?>
    <p class="empty-state">El carrito de este usuario está vacío.</p>
<?php else: ?>
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Precio</th>
                    <th>Cantidad</th>
                    <th>Reservado hasta</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?= e($item['name']) ?></td>
                        <td><?= money($item['price']) ?></td>
                        <td><?= (int) $item['quantity'] ?></td>
                        <td><?= e(date('d/m/Y H:i', strtotime($item['reserved_until']))) ?></td>
                        <td class="text-right"><?= money($item['line_total']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?= $view->partial('partials/order-summary', ['totals' => $totals]) ?>

    <form method="post" action="<?= url('/admin/users/' . $user['id'] . '/cart/clear') ?>" class="actions"
          data-confirm="¿Vaciar este carrito? El stock reservado volverá a estar disponible.">
        <?= csrf_field() ?>
        <button type="submit" class="button button--danger">Vaciar carrito</button>
    </form>
<?php endif; ?>
