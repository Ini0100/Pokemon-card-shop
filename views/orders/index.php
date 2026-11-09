<?php /** @var list<array> $orders */ ?>
<header class="page-header">
    <h1>Mis pedidos</h1>
</header>

<?php if ($orders === []): ?>
    <p class="empty-state">Todavía no has realizado ningún pedido.</p>
<?php else: ?>
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th>Pedido</th>
                    <th>Fecha</th>
                    <th>Artículos</th>
                    <th>Estado</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><a href="<?= url('/orders/' . $order['id']) ?>">#<?= (int) $order['id'] ?></a></td>
                        <td><?= e(date('d/m/Y H:i', strtotime($order['created_at']))) ?></td>
                        <td><?= (int) $order['item_count'] ?></td>
                        <td><?= $view->partial('partials/order-status', ['status' => $order['status']]) ?></td>
                        <td class="text-right"><?= money($order['total']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
