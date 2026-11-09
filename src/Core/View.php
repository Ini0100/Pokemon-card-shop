<?php

declare(strict_types=1);

namespace App\Core;

use Closure;
use RuntimeException;

/**
 * Renders PHP templates from the `views/` directory, optionally wrapped in a layout.
 * Shared data (navigation, cart counter…) is resolved lazily on each render.
 */
final class View
{
    /** @var list<Closure(): array<string, mixed>> */
    private array $sharedResolvers = [];

    public function __construct(private readonly string $viewsPath)
    {
    }

    /** @param Closure(): array<string, mixed> $resolver */
    public function share(Closure $resolver): void
    {
        $this->sharedResolvers[] = $resolver;
    }

    /** @param array<string, mixed> $data */
    public function render(string $template, array $data = [], ?string $layout = 'layouts/main'): string
    {
        foreach ($this->sharedResolvers as $resolver) {
            $data += $resolver();
        }

        $content = $this->renderFile($template, $data);

        if ($layout === null) {
            return $content;
        }

        return $this->renderFile($layout, ['content' => $content] + $data);
    }

    /** @param array<string, mixed> $data */
    public function partial(string $template, array $data = []): string
    {
        return $this->renderFile($template, $data);
    }

    /** @param array<string, mixed> $data */
    private function renderFile(string $template, array $data): string
    {
        $file = $this->viewsPath . '/' . $template . '.php';

        if (!is_file($file)) {
            throw new RuntimeException("View [{$template}] not found.");
        }

        $view = $this;
        extract($data, EXTR_SKIP);

        ob_start();
        require $file;

        return (string) ob_get_clean();
    }
}
