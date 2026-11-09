<?php
/**
 * @var array<string, string> $errors
 * @var array<string, string> $old
 */
?>
<div class="auth-card">
    <h1>Iniciar sesión</h1>

    <?= $view->partial('partials/form-errors', ['errors' => $errors ?? []]) ?>

    <form method="post" action="<?= url('/login') ?>" class="form">
        <?= csrf_field() ?>

        <div class="field">
            <label for="email">Correo electrónico</label>
            <input type="email" id="email" name="email" value="<?= e($old['email'] ?? '') ?>" autocomplete="email" required autofocus>
        </div>

        <div class="field">
            <label for="password">Contraseña</label>
            <input type="password" id="password" name="password" autocomplete="current-password" required>
        </div>

        <button type="submit" class="button button--primary button--block">Entrar</button>
    </form>

    <p class="muted">¿No tienes cuenta? <a href="<?= url('/register') ?>">Regístrate</a></p>
</div>
