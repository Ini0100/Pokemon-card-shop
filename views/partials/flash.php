<?php /** @var list<array{type: string, message: string}> $flashes */ ?>
<?php foreach ($flashes as $flash): ?>
    <div class="alert alert--<?= e($flash['type']) ?>" role="status"><?= e($flash['message']) ?></div>
<?php endforeach; ?>
