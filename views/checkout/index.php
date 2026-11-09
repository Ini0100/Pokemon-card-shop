<?php
/**
 * @var list<array> $items
 * @var array{subtotal: float, shipping: float, total: float} $totals
 * @var array<string, array{label: string, max: int}> $fields
 * @var array<string, string> $errors
 * @var array<string, string> $old
 */
?>
<header class="page-header">
    <h1>Finalizar compra</h1>
</header>

<div class="split">
    <form method="post" action="<?= url('/checkout') ?>" class="form card">
        <?= csrf_field() ?>
        <h2>Datos de envío</h2>

        <?= $view->partial('partials/form-errors', ['errors' => $errors]) ?>

        <?php foreach ($fields as $name => $field): ?>
            <div class="field<?= isset($errors[$name]) ? ' has-error' : '' ?>">
                <label for="<?= e($name) ?>"><?= e($field['label']) ?></label>
                <input type="text" id="<?= e($name) ?>" name="<?= e($name) ?>"
                       value="<?= e($old[$name] ?? '') ?>" maxlength="<?= $field['max'] ?>" required>
            </div>
        <?php endforeach; ?>

        <p class="demo-note">
            Proyecto de demostración: el pago es simulado y no se solicitan ni almacenan datos de tarjeta.
        </p>

        <button type="submit" class="button button--primary button--block">
            Confirmar pedido · <?= money($totals['total']) ?>
        </button>
    </form>

    <aside class="card summary-card">
        <h2>Resumen</h2>
        <ul class="plain-list summary-items">
            <?php foreach ($items as $item): ?>
                <li>
                    <span><?= (int) $item['quantity'] ?> × <?= e($item['name']) ?></span>
                    <span><?= money($item['line_total']) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
        <?= $view->partial('partials/order-summary', ['totals' => $totals]) ?>
    </aside>
</div>
