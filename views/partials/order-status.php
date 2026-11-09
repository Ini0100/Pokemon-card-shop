<?php /** @var string $status */ ?>
<?php if ($status === 'shipped'): ?>
    <span class="tag tag--success">Enviado</span>
<?php else: ?>
    <span class="tag">Pendiente</span>
<?php endif; ?>
