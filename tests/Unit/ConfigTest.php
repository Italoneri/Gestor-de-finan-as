<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Config;
use PHPUnit\Framework\TestCase;

final class ConfigTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/config-test-' . uniqid();
        mkdir($this->dir);
    }

    protected function tearDown(): void
    {
        @unlink($this->dir . '/.env');
        @rmdir($this->dir);
    }

    public function testLoadsValuesIgnoringCommentsAndQuotes(): void
    {
        file_put_contents(
            $this->dir . '/.env',
            "# comment\nAPP_ENV=dev\nAPP_DEBUG=\"true\"\n\nDB_PASS='p=a=ss'\n"
        );

        $config = Config::load($this->dir);

        $this->assertSame('dev', $config->get('APP_ENV'));
        $this->assertTrue($config->bool('APP_DEBUG'));
        $this->assertSame('p=a=ss', $config->get('DB_PASS'));
        $this->assertSame('fallback', $config->get('MISSING', 'fallback'));
    }

    public function testMissingEnvFileYieldsDefaults(): void
    {
        $config = Config::load($this->dir);

        $this->assertSame('sqlite', $config->get('DB_DRIVER', 'sqlite'));
        $this->assertFalse($config->bool('APP_DEBUG'));
    }
}
