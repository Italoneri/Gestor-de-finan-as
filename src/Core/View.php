<?php

declare(strict_types=1);

namespace App\Core;

final class View
{
    /** @var array<string, mixed> */
    private array $shared = [];

    public function __construct(private readonly string $viewsPath)
    {
    }

    /**
     * Values available to every template (auth state, csrf token for forms).
     */
    public function share(string $key, mixed $value): void
    {
        $this->shared[$key] = $value;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function render(string $template, array $data = [], int $status = 200): Response
    {
        $merged = [...$this->shared, ...$data];
        $content = $this->capture($template, $merged);
        $html = $this->capture('layout', [...$merged, 'content' => $content]);

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
