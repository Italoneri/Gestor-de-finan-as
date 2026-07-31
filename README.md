# Controle Financeiro Pessoal

Personal finance tracker built in vanilla PHP 8.2 — no framework — to showcase
hand-rolled secure authentication (bcrypt hashing, regex input validation, CSRF,
brute-force protection, secure sessions) and clean layered architecture.

> Em construção — fase 1/9 (fundação). README completo chega na fase final.

## Quick start

```bash
composer install
cp .env.example .env
composer migrate
composer seed
composer serve   # http://localhost:8000
```

Test user: `teste@exemplo.com` / `Senha@123`

## Development

```bash
composer test    # PHPUnit
composer lint    # PSR-12 via phpcs
```
