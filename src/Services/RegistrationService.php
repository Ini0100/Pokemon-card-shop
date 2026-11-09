<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\ValidationException;
use App\Repositories\UserRepository;

final class RegistrationService
{
    public const MIN_PASSWORD_LENGTH = 8;

    public function __construct(
        private readonly UserRepository $users,
        private readonly Mailer $mailer,
    ) {
    }

    /**
     * Creates an inactive account and sends the activation link.
     *
     * @return string The activation URL (exposed in local environments for convenience).
     */
    public function register(string $email, string $password, string $passwordConfirmation): string
    {
        $email = mb_strtolower(trim($email));
        self::validateCredentials($email, $password);

        $errors = [];

        if ($password !== $passwordConfirmation) {
            $errors['password_confirmation'] = 'Las contraseñas no coinciden.';
        }

        if ($this->users->emailExists($email)) {
            $errors['email'] = 'Ya existe una cuenta con este correo electrónico.';
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        // Only the hash is stored, so a leaked database cannot be used to activate accounts.
        $token = bin2hex(random_bytes(32));
        $this->users->create($email, password_hash($password, PASSWORD_DEFAULT), 'customer', false, hash('sha256', $token));

        $activationUrl = absolute_url('/activate', ['token' => $token]);
        $this->mailer->sendActivationLink($email, $activationUrl);

        return $activationUrl;
    }

    public function activate(string $token): bool
    {
        return $token !== '' && $this->users->activateByTokenHash(hash('sha256', $token));
    }

    /** Shared by self-registration and the admin "create user" form. */
    public static function validateCredentials(string $email, string $password): void
    {
        $errors = [];

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = 'Introduce un correo electrónico válido.';
        }

        if (mb_strlen($password) < self::MIN_PASSWORD_LENGTH) {
            $errors['password'] = sprintf('La contraseña debe tener al menos %d caracteres.', self::MIN_PASSWORD_LENGTH);
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }
    }
}
