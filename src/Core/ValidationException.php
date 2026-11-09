<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Raised when user input or a business rule fails. Its messages are safe to
 * show to the user.
 */
final class ValidationException extends RuntimeException
{
    /** @param array<string, string> $errors Messages keyed by form field. */
    public function __construct(public readonly array $errors)
    {
        parent::__construct(implode(' ', $errors));
    }

    public static function withMessage(string $message, string $field = 'general'): self
    {
        return new self([$field => $message]);
    }
}
