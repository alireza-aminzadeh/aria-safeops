<?php

namespace App\Infrastructure\Notification;

use App\Domain\Shared\Entity\Tenant;
use App\Domain\Shared\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

/**
 * تشخیص آدرس ایمیل مدیران HSE/ادمین یک تنانت — گیرندهٔ پیش‌فرض همهٔ اعلان‌های
 * ایمنی (انقضای گواهی، حادثه بحرانی و…) تا زمانی که سامانهٔ توزیع نقش‌محورِ
 * دقیق‌تری اضافه شود.
 */
final class HseRecipientResolver
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    /** @return list<string> */
    public function emailsFor(Tenant $tenant): array
    {
        /** @var list<User> $users */
        $users = $this->em->getRepository(User::class)->findBy(['tenant' => $tenant]);
        $emails = [];
        foreach ($users as $user) {
            $roles = $user->getRoles();
            if (in_array('ROLE_HSE_MANAGER', $roles, true) || in_array('ROLE_ADMIN', $roles, true)) {
                $emails[] = $user->getEmail();
            }
        }

        return array_values(array_unique($emails));
    }
}
