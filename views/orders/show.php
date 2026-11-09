<?php
/**
 * @var array $order
 * @var list<array> $items
 */
?>
<a href="<?= url('/orders') ?>" class="back-link">← Mis pedidos</a>

<header class="page-header">
    <h1>Pedido #<?= (int) $order['id'] ?></h1>
    <p class="muted">
        <?= e(date('d/m/Y H:i', strtotime($order['created_at']))) ?> ·
        <?= $view->partial('partials/order-status', ['status' => $order['status']]) ?>
    </p>
</header>

<div class="split">
    <div class="card">
        <h2>Productos</h2>
        <div class="table-wrapper">
            <table class="table">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Precio</th>
                        <th>Cantidad</th>
                        <th class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td class="table-product">
                                <?php if ($item['image_path']): ?>
                                    <img src="<?= product_image($item['image_path']) ?>" alt="">
                                <?php endif; ?>
                                <?= e($item['product_name']) ?>
                            </td>
                            <td><?= money($item['unit_price']) ?></td>
                            <td><?= (int) $item['quantity'] ?></td>
                            <td class="text-right"><?= money((float) $item['unit_price'] * (int) $item['quantity']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?= $view->partial('partials/order-summary', ['totals' => [
            'subtotal' => $order['subtotal'],
            'shipping' => $order['shipping_total'],
            'total'    => $order['total'],
        ]]) ?>
    </div>

    <aside class="card summary-card">
        <h2>Envío</h2>
        <address>
            <?= e($order['recipient_name']) ?><br>
            <?= e($order['shipping_address']) ?><br>
            <?= e($order['postal_code']) ?> <?= e($order['city']) ?><br>
            <?= e($order['country']) ?>
        </address>
    </aside>
</div>
