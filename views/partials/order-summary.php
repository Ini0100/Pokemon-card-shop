<?php /** @var array{subtotal: float, shipping: float, total: float} $totals */ ?>
<dl class="summary">
    <dt>Subtotal</dt>
    <dd><?= money($totals['subtotal']) ?></dd>
    <dt>Envío</dt>
    <dd><?= money($totals['shipping']) ?></dd>
    <dt class="summary__total">Total</dt>
    <dd class="summary__total"><?= money($totals['total']) ?></dd>
</dl>
