<?php

namespace App\Infrastructure\Notification;

final class NotificationFactory
{
    public function __construct(
        private readonly LogNotificationAdapter $log,
        private readonly MailerNotificationAdapter $mailer,
    ) {
    }

    public function create(): NotificationInterface
    {
        return self::isEnabled() ? $this->mailer : $this->log;
    }

    public static function isEnabled(): bool
    {
        return getenv('NOTIFICATIONS_ENABLED') === 'true';
    }
}
