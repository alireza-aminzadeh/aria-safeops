<?php

namespace App\Command;

use App\Domain\Contractor\Entity\ContractorCertification;
use App\Domain\Contractor\Entity\ContractorTrainingRecord;
use App\Domain\Shared\Entity\Tenant;
use App\Infrastructure\Notification\HseRecipientResolver;
use App\Infrastructure\Notification\NotificationInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * قابل‌اجرا با cron روزانه: docker compose exec app php bin/console app:notify:expiring-certifications
 * گواهی‌های پیمانکاران و دوره‌های آموزشی‌ای که در ۳۰ روز آینده منقضی می‌شوند
 * (یا در ۷ روز گذشته منقضی شده‌اند) را به مدیران HSE هر تنانت اطلاع می‌دهد.
 */
#[AsCommand(name: 'app:notify:expiring-certifications', description: 'Notify HSE managers about expiring contractor certifications/training')]
final class NotifyExpiringCertificationsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly NotificationInterface $notifier,
        private readonly HseRecipientResolver $recipients,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $from = new \DateTimeImmutable('-7 days');
        $to = new \DateTimeImmutable('+30 days');

        /** @var list<ContractorCertification> $certs */
        $certs = $this->em->createQueryBuilder()
            ->select('c')
            ->from(ContractorCertification::class, 'c')
            ->andWhere('c.expiresAt BETWEEN :from AND :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getResult();

        /** @var list<ContractorTrainingRecord> $trainings */
        $trainings = $this->em->createQueryBuilder()
            ->select('t')
            ->from(ContractorTrainingRecord::class, 't')
            ->andWhere('t.expiresAt BETWEEN :from AND :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getResult();

        if ($certs === [] && $trainings === []) {
            $output->writeln('چیزی برای اطلاع‌رسانی نیست.');

            return Command::SUCCESS;
        }

        $lines = [];
        foreach ($certs as $c) {
            $lines[] = sprintf('گواهی %s (%s) — %s در تاریخ %s', $c->getType(), $c->getContractor()->getCompanyName(), $c->isExpiringSoon() ? 'نزدیک انقضا' : 'وضعیت', $c->getExpiresAt()->format('Y-m-d'));
        }
        foreach ($trainings as $t) {
            $lines[] = sprintf('دوره %s (%s) — %s در تاریخ %s', $t->getCourseName(), $t->getContractor()->getCompanyName(), 'انقضا', $t->getExpiresAt()?->format('Y-m-d') ?? '—');
        }
        $body = "موارد زیر نیاز به پیگیری تمدید دارند:\n\n" . implode("\n", $lines);

        $tenants = $this->em->getRepository(Tenant::class)->findAll();
        $sent = 0;
        foreach ($tenants as $tenant) {
            $recipients = $this->recipients->emailsFor($tenant);
            if ($recipients === []) {
                continue;
            }
            $this->notifier->send($recipients, sprintf('[Aria SafeOps] %d گواهی/دوره نیازمند پیگیری', count($lines)), $body);
            $sent++;
        }

        $output->writeln(sprintf('اطلاع‌رسانی برای %d تنانت ارسال شد (%d مورد).', $sent, count($lines)));

        return Command::SUCCESS;
    }
}
