<?php
/**
 * @var int $status
 * @var string $message
 */
?>
<div class="error-page">
    <p class="error-page__code"><?= (int) $status ?></p>
    <h1><?= e($message) ?></h1>
    <a href="<?= url('/') ?>" class="button button--primary">Volver al catálogo</a>
</div>
