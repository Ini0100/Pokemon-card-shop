<?php

declare(strict_types=1);

namespace App\Core;

/** Synchronizer-token CSRF protection, validated for every POST request. */
final class Csrf
{
    public const FIELD = '_token';
    private const SESSION_KEY = '_csrf_token';

    public function __construct(private readonly Session $session)
    {
    }

    public function token(): string
    {
        $token = $this->session->get(self::SESSION_KEY);

        if (!is_string($token)) {
            $token = bin2hex(random_bytes(32));
            $this->session->set(self::SESSION_KEY, $token);
        }

        return $token;
    }

    public function validate(Request $request): void
    {
        $submitted = $request->input(self::FIELD);

        if (!is_string($submitted) || !hash_equals($this->token(), $submitted)) {
            throw HttpException::pageExpired();
        }
    }
}
