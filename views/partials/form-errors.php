<?php /** @var array<string, string> $errors */ ?>
<?php if (!empty($errors)): ?>
    <div class="alert alert--error" role="alert">
        <ul class="plain-list">
            <?php foreach ($errors as $error): ?>
                <li><?= e($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>
