<?php

declare(strict_types=1);

namespace App\Core;

final class View
{
    public function __construct(private readonly string $viewsPath)
    {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function render(string $template, array $data = [], int $status = 200): Response
    {
        $content = $this->capture($template, $data);
        $html = $this->capture('layout', [...$data, 'content' => $content]);

        return Response::html($html, $status);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function capture(string $template, array $data): string
    {
        extract($data, EXTR_SKIP);
        ob_start();
        require $this->viewsPath . '/' . $template . '.php';

        return (string) ob_get_clean();
    }
}
