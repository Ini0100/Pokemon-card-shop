<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\HttpException;
use App\Core\Response;
use App\Core\Session;
use App\Core\ValidationException;
use App\Repositories\UserRepository;

final class Auth
{
    private const SESSION_KEY = 'user_id';

    /** @var array<string, mixed>|false|null false = not yet loaded */
    private array|false|null $user = false;

    public function __construct(
        private readonly Session $session,
        private readonly UserRepository $users,
    ) {
    }

    /** @return array<string, mixed>|null */
    public function user(): ?array
    {
        if ($this->user === false) {
            $id = $this->session->get(self::SESSION_KEY);
            $this->user = is_int($id) ? $this->users->find($id) : null;
        }

        return $this->user;
    }

    public function id(): ?int
    {
        return isset($this->user()['id']) ? (int) $this->user()['id'] : null;
    }

    public function isAdmin(): bool
    {
        return ($this->user()['role'] ?? null) === 'admin';
    }

    /** @return array<string, mixed> The authenticated user. */
    public function attempt(string $email, string $password): array
    {
        $user = $this->users->findByEmail($email);

        // Same message for unknown e-mail and wrong password to avoid user enumeration.
        if ($user === null || !password_verify($password, $user['password_hash'])) {
            throw ValidationException::withMessage('Correo electrónico o contraseña incorrectos.');
        }

        if (!$user['is_active']) {
            throw ValidationException::withMessage(
                'Tu cuenta aún no está activada. Revisa tu correo y pulsa el enlace de activación.'
            );
        }

        $this->session->regenerate();
        $this->session->set(self::SESSION_KEY, (int) $user['id']);
        $this->user = false;

        return $user;
    }

    public function logout(): void
    {
        $this->session->destroy();
        $this->user = null;
    }

    /** Ensures a user is logged in, redirecting to the login page otherwise. */
    public function requireUser(): int
    {
        $id = $this->id();

        if ($id === null) {
            $this->session->flash('info', 'Inicia sesión para continuar.');
            Response::redirect(url('/login'));
        }

        return $id;
    }

    public function requireAdmin(): int
    {
        $id = $this->requireUser();

        if (!$this->isAdmin()) {
            throw HttpException::forbidden();
        }

        return $id;
    }
}
