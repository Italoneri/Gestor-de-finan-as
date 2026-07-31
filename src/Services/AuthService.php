<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Session;
use App\Models\User;
use App\Repositories\UserRepository;
use PDOException;

final class AuthService
{
    private const SESSION_KEY = 'user_id';

    /**
     * Verified when the e-mail is unknown so both login paths cost one bcrypt
     * comparison — otherwise response timing would reveal which e-mails exist.
     */
    private const DUMMY_HASH = '$2y$10$w4Lyx1V6mswOb3mq8/DhneQVeGHJczaD29kMnBNYdgvKR9Dfko5QS';

    public function __construct(
        private readonly UserRepository $users,
        private readonly LoginRateLimiter $rateLimiter,
        private readonly Session $session,
    ) {
    }

    public function register(string $name, string $email, string $password): RegisterResult
    {
        try {
            $userId = $this->users->create($name, $email, password_hash($password, PASSWORD_DEFAULT));
        } catch (PDOException $e) {
            if ((string) $e->getCode() === '23000') {
                return RegisterResult::EmailTaken;
            }
            throw $e;
        }

        $this->authenticate($userId);

        return RegisterResult::Registered;
    }

    public function attemptLogin(string $email, string $password, string $ip): LoginResult
    {
        if ($this->rateLimiter->tooManyAttempts($email, $ip)) {
            return LoginResult::RateLimited;
        }

        $user = $this->users->findByEmail($email);
        $hash = $user?->passwordHash ?? self::DUMMY_HASH;

        if (!password_verify($password, $hash) || $user === null) {
            $this->rateLimiter->recordFailure($email, $ip);

            return LoginResult::InvalidCredentials;
        }

        $this->rateLimiter->clear($email);
        $this->authenticate($user->id);

        return LoginResult::Success;
    }

    public function logout(): void
    {
        $this->session->destroy();
    }

    public function check(): bool
    {
        return $this->userId() !== null;
    }

    public function userId(): ?int
    {
        $id = $this->session->get(self::SESSION_KEY);

        return is_int($id) ? $id : null;
    }

    public function user(): ?User
    {
        $id = $this->userId();

        return $id === null ? null : $this->users->findById($id);
    }

    private function authenticate(int $userId): void
    {
        // new session id on privilege change kills fixated sessions
        $this->session->regenerate();
        $this->session->set(self::SESSION_KEY, $userId);
    }
}
