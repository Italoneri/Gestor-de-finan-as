<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Dev mailer: appends messages to a log file instead of sending them.
 * Verification/reset links show up in storage/mail.log — swap for a real
 * SMTP implementation behind the same interface in production.
 */
final class LogMailer implements MailerInterface
{
    public function __construct(private readonly string $logPath)
    {
    }

    public function send(string $to, string $subject, string $body): void
    {
        $entry = sprintf(
            "[%s] To: %s | Subject: %s\n%s\n---\n",
            date('c'),
            $to,
            $subject,
            $body,
        );
        file_put_contents($this->logPath, $entry, FILE_APPEND | LOCK_EX);
    }
}
