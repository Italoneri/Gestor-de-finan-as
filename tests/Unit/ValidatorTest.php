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

    /**
     * @return array<string, array{string}>
     */
    public static function validLabels(): array
    {
        return [
            'simple' => ['Mercado'],
            'with digits' => ['Cartão 2'],
            'accented' => ['Poupança Caixa'],
            'hyphen' => ['Vale-refeição'],
        ];
    }

    #[DataProvider('validLabels')]
    public function testAcceptsValidLabels(string $label): void
    {
        $validator = new Validator();
        $validator->label('name', $label);

        $this->assertFalse($validator->fails());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidLabels(): array
    {
        return [
            'empty' => [''],
            'single char' => ['A'],
            'too long' => [str_repeat('a', 41)],
            'html' => ['<b>x</b>'],
            'symbols' => ['Conta #1!'],
        ];
    }

    #[DataProvider('invalidLabels')]
    public function testRejectsInvalidLabels(string $label): void
    {
        $validator = new Validator();
        $validator->label('name', $label);

        $this->assertNotNull($validator->error('name'));
    }

    public function testAcceptsSixDigitHexColors(): void
    {
        $validator = new Validator();
        $validator->color('color', '#0ea5e9');

        $this->assertFalse($validator->fails());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidColors(): array
    {
        return [
            'empty' => [''],
            'missing hash' => ['0ea5e9'],
            'shorthand' => ['#fff'],
            'uppercase' => ['#0EA5E9'],
            'named colour' => ['red'],
            'css function' => ['rgb(14 165 233)'],
            'style injection' => ['#fff;background:url(x)'],
        ];
    }

    #[DataProvider('invalidColors')]
    public function testRejectsInvalidColors(string $color): void
    {
        $validator = new Validator();
        $validator->color('color', $color);

        $this->assertNotNull($validator->error('color'));
    }

    public function testAcceptsRealDates(): void
    {
        $validator = new Validator();
        $validator->date('date', '2026-02-28');

        $this->assertFalse($validator->fails());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidDates(): array
    {
        return [
            'empty' => [''],
            'wrong format' => ['28/02/2026'],
            'nonexistent day' => ['2026-02-30'],
            'nonexistent month' => ['2026-13-01'],
            'garbage' => ['ontem'],
        ];
    }

    #[DataProvider('invalidDates')]
    public function testRejectsInvalidDates(string $date): void
    {
        $validator = new Validator();
        $validator->date('date', $date);

        $this->assertNotNull($validator->error('date'));
    }

    public function testAcceptsFreeTextDescriptions(): void
    {
        $validator = new Validator();
        $validator->description('description', 'Compras no mercado (cartão), 2x sem juros');

        $this->assertFalse($validator->fails());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidDescriptions(): array
    {
        return [
            'empty' => [''],
            'single char' => ['x'],
            'too long' => [str_repeat('a', 101)],
            'control chars' => ["linha\numa"],
        ];
    }

    #[DataProvider('invalidDescriptions')]
    public function testRejectsInvalidDescriptions(string $description): void
    {
        $validator = new Validator();
        $validator->description('description', $description);

        $this->assertNotNull($validator->error('description'));
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
