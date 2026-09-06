<?php

namespace App\Infrastructure\Notification;

use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

/**
 * ارسال واقعی ایمیل. هرگز استثنا به بیرون پرتاب نمی‌کند — یک سرویس ایمیل
 * ناموفق/بدون‌پیکربندی هرگز نباید جریان اصلی برنامه (مثلاً ثبت حادثه) را
 * مختل کند؛ فقط در لاگ ثبت می‌شود.
 */
final class MailerNotificationAdapter implements NotificationInterface
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function send(array $recipients, string $subject, string $body): void
    {
        if ($recipients === []) {
            return;
        }
        $fromAddress = (string) (getenv('MAILER_FROM') ?: 'noreply@hse.aria-ai.ir');
        try {
            $email = (new Email())
                ->from($fromAddress)
                ->subject($subject)
                ->text($body);
            foreach ($recipients as $to) {
                $email->addTo($to);
            }
            $this->mailer->send($email);
        } catch (TransportExceptionInterface|\Throwable $e) {
            $this->logger->warning('ارسال اعلان ایمیلی ناموفق بود: {message}', [
                'message' => $e->getMessage(),
                'subject' => $subject,
                'recipients' => implode(', ', $recipients),
            ]);
        }
    }
}
