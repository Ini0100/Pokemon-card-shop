<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\ValidationException;
use App\Services\Auth;
use App\Services\RegistrationService;

final class AuthController extends Controller
{
    public function __construct(
        private readonly Auth $auth,
        private readonly RegistrationService $registration,
    ) {
    }

    public function showLogin(): void
    {
        if ($this->auth->id() !== null) {
            $this->redirect('/');
        }

        $this->render('auth/login', ['title' => 'Iniciar sesión']);
    }

    public function login(Request $request): void
    {
        $email = $request->string('email');

        try {
            $user = $this->auth->attempt($email, (string) $request->input('password', ''));
        } catch (ValidationException $e) {
            $this->render('auth/login', [
                'title'  => 'Iniciar sesión',
                'errors' => $e->errors,
                'old'    => ['email' => $email],
            ], 422);

            return;
        }

        $this->redirect($user['role'] === 'admin' ? '/admin' : '/');
    }

    public function logout(): void
    {
        $this->auth->logout();
        $this->redirect('/');
    }

    public function showRegister(): void
    {
        $this->render('auth/register', ['title' => 'Crear cuenta']);
    }

    public function register(Request $request): void
    {
        $email = $request->string('email');

        try {
            $activationUrl = $this->registration->register(
                $email,
                (string) $request->input('password', ''),
                (string) $request->input('password_confirmation', ''),
            );
        } catch (ValidationException $e) {
            $this->render('auth/register', [
                'title'  => 'Crear cuenta',
                'errors' => $e->errors,
                'old'    => ['email' => $email],
            ], 422);

            return;
        }

        $this->flash('success', 'Cuenta creada. Te hemos enviado un correo con el enlace de activación.');

        if (config('app.env') === 'local' && config('mail.host') === '') {
            $this->flash('info', 'Entorno local sin SMTP: activa la cuenta desde este enlace → ' . $activationUrl);
        }

        $this->redirect('/login');
    }

    public function activate(Request $request): void
    {
        if ($this->registration->activate($request->string('token'))) {
            $this->flash('success', 'Cuenta activada. Ya puedes iniciar sesión.');
        } else {
            $this->flash('error', 'El enlace de activación no es válido o ya se ha utilizado.');
        }

        $this->redirect('/login');
    }
}
