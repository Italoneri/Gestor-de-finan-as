<?php

declare(strict_types=1);

namespace App\Core;

final class Response
{
    /**
     * @param array<string, string> $headers
     */
    private function __construct(
        public readonly int $status,
        public readonly string $body,
        public readonly array $headers,
    ) {
    }

    public static function html(string $body, int $status = 200): self
    {
        return new self($status, $body, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    public static function download(string $body, string $filename, string $contentType): self
    {
        return new self(200, $body, [
            'Content-Type' => $contentType,
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public static function redirect(string $to, int $status = 302): self
    {
        return new self($status, '', ['Location' => $to]);
    }

    public function send(): void
    {
        http_response_code($this->status);
        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }
        echo $this->body;
    }
}
