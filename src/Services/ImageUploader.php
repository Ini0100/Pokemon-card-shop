<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Core\ValidationException;
use finfo;
use RuntimeException;

/** Stores product images uploaded from the admin panel. */
final class ImageUploader
{
    private const ALLOWED_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    ];

    public function __construct(private readonly Config $config)
    {
    }

    /**
     * Validates and moves the uploaded file, returning its public path.
     *
     * @param array<string, mixed> $file An entry of $_FILES.
     */
    public function store(array $file): string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
            throw ValidationException::withMessage('No se ha podido subir la imagen.', 'image');
        }

        $maxBytes = (int) $this->config->get('uploads.max_bytes');

        if ($file['size'] > $maxBytes) {
            throw ValidationException::withMessage(
                sprintf('La imagen no puede superar %d MB.', $maxBytes / 1024 / 1024),
                'image',
            );
        }

        // Detect the type from the file contents, never from the client-provided name.
        $mimeType  = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $extension = self::ALLOWED_TYPES[$mimeType] ?? null;

        if ($extension === null) {
            throw ValidationException::withMessage('Formato no permitido. Usa JPG, PNG, WEBP o GIF.', 'image');
        }

        $directory = (string) $this->config->get('uploads.directory');

        if (!is_dir($directory) && !mkdir($directory, 0755, true)) {
            throw new RuntimeException("Upload directory [{$directory}] could not be created.");
        }

        $filename = bin2hex(random_bytes(16)) . '.' . $extension;

        if (!move_uploaded_file($file['tmp_name'], $directory . '/' . $filename)) {
            throw new RuntimeException('Uploaded file could not be moved.');
        }

        return $this->config->get('uploads.public_prefix') . '/' . $filename;
    }

    /** Deletes a previously uploaded image; bundled catalog images are left untouched. */
    public function delete(string $publicPath): void
    {
        $prefix = $this->config->get('uploads.public_prefix') . '/';

        if (!str_starts_with($publicPath, $prefix)) {
            return;
        }

        $file = $this->config->get('uploads.directory') . '/' . basename($publicPath);

        if (is_file($file)) {
            unlink($file);
        }
    }
}
