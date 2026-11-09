<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use RuntimeException;

/** Runs plain .sql files (schema, seed, data dumps) statement by statement. */
final class SqlScript
{
    public static function run(PDO $pdo, string $path): int
    {
        if (!is_file($path)) {
            throw new RuntimeException("SQL file not found: {$path}");
        }

        $statements = self::split((string) file_get_contents($path));

        foreach ($statements as $statement) {
            $pdo->exec($statement);
        }

        return count($statements);
    }

    /**
     * Splits SQL into statements on `;`, ignoring semicolons inside quoted
     * strings/identifiers and skipping `--` and `#` line comments.
     *
     * @return list<string>
     */
    public static function split(string $sql): array
    {
        $statements = [];
        $current    = '';
        $quote      = null;
        $length     = strlen($sql);

        for ($i = 0; $i < $length; $i++) {
            $char = $sql[$i];

            if ($quote !== null) {
                $current .= $char;

                if ($char === '\\' && $quote !== '`') {
                    $current .= $sql[++$i] ?? ''; // Escaped character.
                } elseif ($char === $quote) {
                    if (($sql[$i + 1] ?? '') === $quote) {
                        $current .= $sql[++$i]; // Doubled quote ('') inside a string.
                    } else {
                        $quote = null;
                    }
                }

                continue;
            }

            if ($char === "'" || $char === '"' || $char === '`') {
                $quote    = $char;
                $current .= $char;
            } elseif (($char === '-' && ($sql[$i + 1] ?? '') === '-') || $char === '#') {
                $end = strpos($sql, "\n", $i);
                $i   = $end === false ? $length : $end;
                $current .= "\n";
            } elseif ($char === ';') {
                self::push($statements, $current);
                $current = '';
            } else {
                $current .= $char;
            }
        }

        self::push($statements, $current);

        return $statements;
    }

    /** @param list<string> $statements */
    private static function push(array &$statements, string $statement): void
    {
        $statement = trim($statement);

        if ($statement !== '') {
            $statements[] = $statement;
        }
    }
}
