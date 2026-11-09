<?php
/**
 * @var list<array> $items
 * @var array{subtotal: float, shipping: float, total: float} $totals
 */
?>
<header class="page-header">
    <h1>Carrito</h1>
</header>

<?php if ($items === []): ?>
    <p class="empty-state">Tu carrito está vacío. <a href="<?= url('/') ?>">Ver catálogo</a></p>
<?php else: ?>
    <p class="reservation-note">
        <?= $view->partial('partials/icon', ['name' => 'lock']) ?>
        Tus productos están reservados durante <?= (int) config('cart.reservation_minutes') ?> minutos
        desde la última modificación del carrito.
    </p>

    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Precio</th>
                    <th>Cantidad</th>
                    <th class="text-right">Total</th>
                    <th><span class="visually-hidden">Acciones</span></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td>
                            <a href="<?= url('/products/' . $item['product_id']) ?>" class="table-product">
                                <img src="<?= product_image($item['image_path']) ?>" alt="">
                                <?= e($item['name']) ?>
                            </a>
                        </td>
                        <td><?= money($item['price']) ?></td>
                        <td>
                            <form method="post" action="<?= url('/cart/items/' . $item['id']) ?>" class="quantity-form">
                                <?= csrf_field() ?>
                                <input type="number" name="quantity" value="<?= (int) $item['quantity'] ?>" min="0"
                                       max="<?= (int) $item['quantity'] + (int) $item['stock'] ?>"
                                       aria-label="Cantidad de <?= e($item['name']) ?>">
                                <button type="submit" class="button button--small">Actualizar</button>
                            </form>
                        </td>
                        <td class="text-right"><?= money($item['line_total']) ?></td>
                        <td>
                            <form method="post" action="<?= url('/cart/items/' . $item['id'] . '/delete') ?>">
                                <?= csrf_field() ?>
                                <button type="submit" class="button button--small button--danger">Quitar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?= $view->partial('partials/order-summary', ['totals' => $totals]) ?>

    <div class="actions">
        <a href="<?= url('/') ?>" class="button button--ghost">Seguir comprando</a>
        <a href="<?= url('/checkout') ?>" class="button button--primary">Finalizar compra</a>
    </div>
<?php endif; ?>
