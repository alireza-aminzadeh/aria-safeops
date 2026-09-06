<?php

namespace App\Infrastructure\Notification;

use Psr\Log\LoggerInterface;

/**
 * پیش‌فرض امن — تا زمانی که NOTIFICATIONS_ENABLED=true نشده، اعلان‌ها فقط
 * لاگ می‌شوند (بدون نیاز به سرویس SMTP واقعی).
 */
final class LogNotificationAdapter implements NotificationInterface
{
    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public function send(array $recipients, string $subject, string $body): void
    {
        $this->logger->info('[notification:log-only] {subject} → {recipients}', [
            'subject' => $subject,
            'recipients' => implode(', ', $recipients),
            'body' => $body,
        ]);
    }
}
