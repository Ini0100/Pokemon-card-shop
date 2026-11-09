<?php
/**
 * @var array<string, string> $errors
 * @var array<string, string> $old
 */
use App\Services\RegistrationService;
?>
<div class="auth-card">
    <h1>Crear cuenta</h1>

    <?= $view->partial('partials/form-errors', ['errors' => $errors ?? []]) ?>

    <form method="post" action="<?= url('/register') ?>" class="form">
        <?= csrf_field() ?>

        <div class="field">
            <label for="email">Correo electrónico</label>
            <input type="email" id="email" name="email" value="<?= e($old['email'] ?? '') ?>" autocomplete="email" required autofocus>
        </div>

        <div class="field">
            <label for="password">Contraseña</label>
            <input type="password" id="password" name="password" autocomplete="new-password"
                   minlength="<?= RegistrationService::MIN_PASSWORD_LENGTH ?>" required>
            <small class="muted">Mínimo <?= RegistrationService::MIN_PASSWORD_LENGTH ?> caracteres.</small>
        </div>

        <div class="field">
            <label for="password_confirmation">Repite la contraseña</label>
            <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required>
        </div>

        <button type="submit" class="button button--primary button--block">Crear cuenta</button>
    </form>

    <p class="muted">¿Ya tienes cuenta? <a href="<?= url('/login') ?>">Inicia sesión</a></p>
</div>
