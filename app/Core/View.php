<?php

declare(strict_types=1);

namespace App\Core;

final class View
{
    /** @param array<string, mixed> $data */
    public static function render(string $view, array $data = [], ?string $layout = null): void
    {
        $viewFile = HMS_ROOT . '/app/views/' . str_replace('.', '/', $view) . '.php';
        if (!is_file($viewFile)) {
            throw new \RuntimeException('View not found: ' . $view);
        }

        extract($data, EXTR_SKIP);

        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        if ($layout === null) {
            Response::html((string) $content);
            return;
        }

        $layoutFile = HMS_ROOT . '/app/views/layouts/' . $layout . '.php';
        if (!is_file($layoutFile)) {
            throw new \RuntimeException('Layout not found: ' . $layout);
        }

        ob_start();
        require $layoutFile;
        Response::html((string) ob_get_clean());
    }
}
