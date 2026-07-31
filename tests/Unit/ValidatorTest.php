<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ValidatorTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function validNames(): array
    {
        return [
            'simple' => ['Ana'],
            'compound' => ['João da Silva'],
            'apostrophe' => ["D'Angelo"],
            'hyphen' => ['Maria-Clara'],
            'accented' => ['José Antônio Gonçalves'],
        ];
    }

    #[DataProvider('validNames')]
    public function testAcceptsValidNames(string $name): void
    {
        $validator = new Validator();
        $validator->name('name', $name);

        $this->assertFalse($validator->fails());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidNames(): array
    {
        return [
            'empty' => [''],
            'single char' => ['A'],
            'too long' => [str_repeat('a', 61)],
            'digits' => ['João 123'],
            'html' => ['<script>alert(1)</script>'],
            'underscore' => ['user_name'],
        ];
    }

    #[DataProvider('invalidNames')]
    public function testRejectsInvalidNames(string $name): void
    {
        $validator = new Validator();
        $validator->name('name', $name);

        $this->assertNotNull($validator->error('name'));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function validEmails(): array
    {
        return [
            'simple' => ['user@example.com'],
            'subdomain and plus' => ['a.b+c@sub.domain.org'],
            'digits' => ['user123@mail99.com.br'],
        ];
    }

    #[DataProvider('validEmails')]
    public function testAcceptsValidEmails(string $email): void
    {
        $validator = new Validator();
        $validator->email('email', $email);

        $this->assertFalse($validator->fails());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidEmails(): array
    {
        return [
            'empty' => [''],
            'no at' => ['plain'],
            'no local part' => ['@no.com'],
            'no dot in domain' => ['a@b'],
            'double at' => ['user@@x.com'],
            'inner space' => ['user name@x.com'],
            'too long' => [str_repeat('a', 250) . '@x.com'],
        ];
    }

    #[DataProvider('invalidEmails')]
    public function testRejectsInvalidEmails(string $email): void
    {
        $validator = new Validator();
        $validator->email('email', $email);

        $this->assertNotNull($validator->error('email'));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function validPasswords(): array
    {
        return [
            'typical' => ['Senha@123'],
            'minimum length' => ['Abcdef1!'],
            'spaces allowed inside' => ['Uma Frase1!'],
        ];
    }

    #[DataProvider('validPasswords')]
    public function testAcceptsStrongPasswords(string $password): void
    {
        $validator = new Validator();
        $validator->password('password', $password);

        $this->assertFalse($validator->fails());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function weakPasswords(): array
    {
        return [
            'empty' => [''],
            'no uppercase' => ['senha@123'],
            'no lowercase' => ['SENHA@123'],
            'no digit' => ['Senha@abc'],
            'no special char' => ['Senha1234'],
            'too short' => ['S@1a'],
            'over 72 bytes' => [str_repeat('Aa1@', 19)],
        ];
    }

    #[DataProvider('weakPasswords')]
    public function testRejectsWeakPasswords(string $password): void
    {
        $validator = new Validator();
        $validator->password('password', $password);

        $this->assertNotNull($validator->error('password'));
    }

    public function testRejectsMismatchedConfirmation(): void
    {
        $validator = new Validator();
        $validator->confirmation('password_confirmation', 'Senha@123', 'Senha@124');

        $this->assertNotNull($validator->error('password_confirmation'));
    }

    public function testAcceptsMatchingConfirmation(): void
    {
        $validator = new Validator();
        $validator->confirmation('password_confirmation', 'Senha@123', 'Senha@123');

        $this->assertFalse($validator->fails());
    }

    public function testCollectsErrorsAcrossFields(): void
    {
        $validator = new Validator();
        $validator->name('name', '');
        $validator->email('email', 'bad');
        $validator->password('password', 'weak');

        $this->assertTrue($validator->fails());
        $this->assertCount(3, $validator->errors());
    }
}
