<?php

declare(strict_types=1);

namespace App\Services\Tcgdex;

use JsonException;
use RuntimeException;

/**
 * Minimal client for the TCGdex REST API (https://tcgdex.dev), a free and
 * open-source (MIT) database of Pokémon TCG cards. No API key is required.
 */
final class TcgdexClient
{
    private const BASE_URL    = 'https://api.tcgdex.net/v2';
    private const USER_AGENT  = 'TCG-Market-Importer/1.0 (portfolio project)';
    private const TIMEOUT     = 20;
    private const MAX_RETRIES = 5;
    private const MAX_BACKOFF_SECONDS = 30;

    /** @return array<string, mixed> */
    public function set(string $language, string $setId): array
    {
        return $this->get("/{$language}/sets/" . rawurlencode($setId));
    }

    /** @return array<string, mixed> */
    public function card(string $language, string $cardId): array
    {
        return $this->get("/{$language}/cards/" . rawurlencode($cardId));
    }

    /** @return array<string, mixed> */
    private function get(string $path): array
    {
        $lastError = '';

        for ($attempt = 1; $attempt <= self::MAX_RETRIES; $attempt++) {
            $retryAfter = null;
            $handle     = curl_init(self::BASE_URL . $path);
            curl_setopt_array($handle, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => self::TIMEOUT,
                CURLOPT_USERAGENT      => self::USER_AGENT,
                CURLOPT_HTTPHEADER     => ['Accept: application/json'],
                CURLOPT_HEADERFUNCTION => static function ($curl, string $header) use (&$retryAfter): int {
                    if (preg_match('/^Retry-After:\s*(\d+)/i', $header, $matches)) {
                        $retryAfter = (int) $matches[1];
                    }

                    return strlen($header);
                },
            ]);

            $body   = curl_exec($handle);
            $status = curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
            $error  = curl_error($handle);
            curl_close($handle);

            if ($body !== false && $status === 200) {
                try {
                    return json_decode($body, true, 512, JSON_THROW_ON_ERROR);
                } catch (JsonException $e) {
                    throw new RuntimeException("Invalid JSON from TCGdex for {$path}.", 0, $e);
                }
            }

            if ($status === 404) {
                throw new RuntimeException("TCGdex resource not found: {$path}");
            }

            $lastError = $error !== '' ? $error : "HTTP {$status}";

            // Exponential back-off (1s, 2s, 4s…), honouring Retry-After on 429/503.
            if ($attempt < self::MAX_RETRIES) {
                sleep(min($retryAfter ?? 2 ** ($attempt - 1), self::MAX_BACKOFF_SECONDS));
            }
        }

        throw new RuntimeException("TCGdex request failed for {$path}: {$lastError}");
    }
}
