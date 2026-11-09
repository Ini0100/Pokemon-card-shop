<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class UserRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT id, email, role, is_active FROM users WHERE id = ?');
        $statement->execute([$id]);

        return $statement->fetch() ?: null;
    }

    /** @return array<string, mixed>|null Includes the password hash. */
    public function findByEmail(string $email): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM users WHERE email = ?');
        $statement->execute([$email]);

        return $statement->fetch() ?: null;
    }

    public function emailExists(string $email): bool
    {
        $statement = $this->pdo->prepare('SELECT 1 FROM users WHERE email = ?');
        $statement->execute([$email]);

        return (bool) $statement->fetchColumn();
    }

    public function create(
        string $email,
        string $passwordHash,
        string $role = 'customer',
        bool $isActive = false,
        ?string $activationTokenHash = null,
    ): int {
        $this->pdo->prepare(
            'INSERT INTO users (email, password_hash, role, is_active, activation_token_hash)
             VALUES (?, ?, ?, ?, ?)'
        )->execute([$email, $passwordHash, $role, (int) $isActive, $activationTokenHash]);

        return (int) $this->pdo->lastInsertId();
    }

    public function activateByTokenHash(string $tokenHash): bool
    {
        $statement = $this->pdo->prepare(
            'UPDATE users SET is_active = 1, activation_token_hash = NULL WHERE activation_token_hash = ?'
        );
        $statement->execute([$tokenHash]);

        return $statement->rowCount() === 1;
    }

    /** @return list<array<string, mixed>> */
    public function all(): array
    {
        return $this->pdo->query(
            'SELECT id, email, role, is_active, created_at FROM users ORDER BY id'
        )->fetchAll();
    }

    public function delete(int $id): void
    {
        $this->pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
    }
}
