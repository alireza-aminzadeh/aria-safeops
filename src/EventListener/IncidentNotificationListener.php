<?php

namespace App\EventListener;

use App\Domain\Incident\Entity\Incident;
use App\Infrastructure\Notification\HseRecipientResolver;
use App\Infrastructure\Notification\NotificationInterface;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Events;

/**
 * حادثه با شدت high/critical به‌محض ثبت، بلادرنگ به مدیران HSE تنانت
 * اطلاع‌رسانی می‌شود — مستقل از اینکه مدیر HSE در همان لحظه داشبورد را
 * باز کرده باشد یا نه (Mercure فقط برای کاربران آنلاین کار می‌کند).
 */
#[AsDoctrineListener(event: Events::postPersist)]
final class IncidentNotificationListener
{
    private const NOTIFY_SEVERITIES = ['high', 'critical'];

    public function __construct(
        private readonly NotificationInterface $notifier,
        private readonly HseRecipientResolver $recipients,
    ) {
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $incident = $args->getObject();
        if (!$incident instanceof Incident || !in_array($incident->getSeverity(), self::NOTIFY_SEVERITIES, true)) {
            return;
        }

        $recipients = $this->recipients->emailsFor($incident->getTenant());
        if ($recipients === []) {
            return;
        }

        $this->notifier->send(
            $recipients,
            sprintf('[Aria SafeOps] حادثهٔ %s جدید ثبت شد', $incident->getSeverity() === 'critical' ? 'بحرانی' : 'با شدت بالا'),
            sprintf(
                "نوع: %s\nشدت: %s\nمحل: %s\nشرح: %s\nزمان گزارش: %s",
                $incident->getType(),
                $incident->getSeverity(),
                $incident->getLocation() ?? '—',
                $incident->getDescription(),
                $incident->getReportedAt()->format('Y-m-d H:i'),
            ),
        );
    }
}
