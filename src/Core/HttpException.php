<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class HttpException extends RuntimeException
{
    public function __construct(public readonly int $status, string $message = '')
    {
        parent::__construct($message, $status);
    }

    public static function notFound(string $message = 'Página no encontrada.'): self
    {
        return new self(404, $message);
    }

    public static function forbidden(string $message = 'No tienes permiso para acceder a esta página.'): self
    {
        return new self(403, $message);
    }

    public static function pageExpired(): self
    {
        return new self(419, 'La sesión ha caducado. Recarga la página e inténtalo de nuevo.');
    }
}
