<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Server-side format validation. Regex here validates *shape*, never security:
 * password safety comes from bcrypt hashing plus login rate limiting, and SQL
 * safety comes from prepared statements — regardless of what these patterns do.
 */
final class Validator
{
    /**
     * \p{L} = any unicode letter, \p{M} = combining marks (accents kept when
     * text is decomposed); apostrophe, hyphen and space cover "D'Angelo",
     * "Maria-Clara" and compound names. {2,60} bounds the length. /u makes
     * the engine treat the subject as UTF-8.
     */
    public const NAME_PATTERN = "/^[\p{L}\p{M}' -]{2,60}$/u";

    /**
     * Same shape as NAME_PATTERN plus digits (\d): short user-facing labels
     * like category and account names ("Cartão 2", "Vale-refeição").
     */
    public const LABEL_PATTERN = "/^[\p{L}\p{M}\d' -]{2,40}$/u";

    /**
     * Deliberately loose structural check: non-space/@ chunk, "@", domain with
     * at least one dot. filter_var(FILTER_VALIDATE_EMAIL) below is the source
     * of truth — a fully RFC-strict regex is unmaintainable and rejects valid
     * real-world addresses.
     */
    public const EMAIL_PATTERN = '/^[^\s@]+@[^\s@]+\.[^\s@]+$/';

    /**
     * Four independent lookaheads, each scanning the whole string: (?=.*[a-z])
     * lowercase, (?=.*[A-Z]) uppercase, (?=.*\d) digit, (?=.*[^\w\s]) at least
     * one char that is neither alphanumeric/underscore nor whitespace (the
     * "special" class). Then .{8,} enforces the minimum length.
     */
    public const PASSWORD_PATTERN = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^\w\s]).{8,}$/';

    /** bcrypt silently ignores everything past 72 bytes, so longer input is rejected */
    private const PASSWORD_MAX_BYTES = 72;

    private const EMAIL_MAX_LENGTH = 254;

    /** @var array<string, string> */
    private array $errors = [];

    public function name(string $field, string $value): void
    {
        if (preg_match(self::NAME_PATTERN, $value) !== 1) {
            $this->errors[$field] =
                'Nome deve ter de 2 a 60 letras (acentos, espaços, hífen e apóstrofo são permitidos).';
        }
    }

    public function label(string $field, string $value): void
    {
        if (preg_match(self::LABEL_PATTERN, $value) !== 1) {
            $this->errors[$field] =
                'Nome deve ter de 2 a 40 caracteres (letras, números, espaços, hífen e apóstrofo).';
        }
    }

    public function email(string $field, string $value): void
    {
        $valid = strlen($value) <= self::EMAIL_MAX_LENGTH
            && preg_match(self::EMAIL_PATTERN, $value) === 1
            && filter_var($value, FILTER_VALIDATE_EMAIL) !== false;

        if (!$valid) {
            $this->errors[$field] = 'Informe um e-mail válido.';
        }
    }

    public function password(string $field, string $value): void
    {
        if (strlen($value) > self::PASSWORD_MAX_BYTES) {
            $this->errors[$field] = 'Senha deve ter no máximo 72 caracteres.';

            return;
        }
        if (preg_match(self::PASSWORD_PATTERN, $value) !== 1) {
            $this->errors[$field] =
                'Senha deve ter ao menos 8 caracteres, com maiúscula, minúscula, número e caractere especial.';
        }
    }

    public function confirmation(string $field, string $password, string $confirmation): void
    {
        if (!hash_equals($password, $confirmation)) {
            $this->errors[$field] = 'As senhas não conferem.';
        }
    }

    /**
     * For validations that don't fit a pattern rule (enum membership,
     * business checks) but should land in the same error bag.
     */
    public function fail(string $field, string $message): void
    {
        $this->errors[$field] = $message;
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    /**
     * @return array<string, string>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    public function error(string $field): ?string
    {
        return $this->errors[$field] ?? null;
    }
}
