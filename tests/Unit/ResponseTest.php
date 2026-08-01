<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Response;
use PHPUnit\Framework\TestCase;

final class ResponseTest extends TestCase
{
    public function testHtmlSetsContentTypeAndStatus(): void
    {
        $response = Response::html('<p>oi</p>', 201);

        $this->assertSame(201, $response->status);
        $this->assertSame('<p>oi</p>', $response->body);
        $this->assertSame('text/html; charset=utf-8', $response->headers['Content-Type']);
    }

    public function testRedirectSetsLocationHeader(): void
    {
        $response = Response::redirect('/login');

        $this->assertSame(302, $response->status);
        $this->assertSame('/login', $response->headers['Location']);
        $this->assertSame('', $response->body);
    }

    public function testDownloadSetsAttachmentHeaders(): void
    {
        $response = Response::download('conteudo', 'dados.csv', 'text/csv; charset=UTF-8');

        $this->assertSame(200, $response->status);
        $this->assertSame('text/csv; charset=UTF-8', $response->headers['Content-Type']);
        $this->assertSame('attachment; filename="dados.csv"', $response->headers['Content-Disposition']);
    }
}
