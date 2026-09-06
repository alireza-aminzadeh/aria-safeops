<?php

namespace App\Infrastructure\Notification;

interface NotificationInterface
{
    /**
     * @param list<string> $recipients آدرس ایمیل گیرندگان
     */
    public function send(array $recipients, string $subject, string $body): void;
}
