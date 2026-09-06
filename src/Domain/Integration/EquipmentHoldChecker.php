<?php

namespace App\Domain\Integration;

use App\Domain\Integration\Entity\EquipmentHold;
use Doctrine\ORM\EntityManagerInterface;

/**
 * نقطهٔ واحد بررسی «نگه‌داشت» تجهیز که از رویداد آنومالی باز PetroOps
 * (`PetroopsIntegrationController::ingest`) در `EquipmentHold` ذخیره می‌شود.
 *
 * قبل از این کلاس، این بررسی فقط در لحظهٔ **ایجاد** Permit/MOC انجام می‌شد
 * (`TenantAwarePersistProcessor`). اگر آنومالی *بعد* از ایجاد مجوز/MOC و *قبل*
 * از فعال‌سازی/اجرای فیزیکی باز می‌شد، هیچ گارد دیگری آن را نمی‌گرفت. این
 * سرویس حالا هم در لحظهٔ ایجاد و هم در گذارهای حساس گردش‌کار
 * (`PermitWorkflowSubscriber`, `MocWorkflowSubscriber`) صدا زده می‌شود تا
 * بلوکه‌سازی واقعاً در تمام طول عمر مجوز/MOC معتبر بماند، نه فقط در ایجاد.
 */
final class EquipmentHoldChecker
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function isBlocked(?string $equipmentTag): bool
    {
        return $this->findOpenHold($equipmentTag) instanceof EquipmentHold;
    }

    /** پیام فارسی قابل‌نمایش به کاربر، یا null اگر تجهیز نگه‌داشت باز ندارد. */
    public function blockerMessage(?string $equipmentTag): ?string
    {
        $hold = $this->findOpenHold($equipmentTag);
        if (!$hold instanceof EquipmentHold) {
            return null;
        }

        $score = $hold->getScore();

        return sprintf(
            'تجهیز %s به‌خاطر آنومالی باز پتروپایش مسدود است%s.',
            $hold->getEquipmentTag(),
            $score !== null ? sprintf(' (امتیاز %s)', number_format($score, 2)) : '',
        );
    }

    private function findOpenHold(?string $equipmentTag): ?EquipmentHold
    {
        $tag = trim((string) $equipmentTag);
        if ($tag === '') {
            return null;
        }

        $hold = $this->em->getRepository(EquipmentHold::class)->findOneBy(['equipmentTag' => $tag]);

        return $hold instanceof EquipmentHold && $hold->getStatus() === 'open' ? $hold : null;
    }
}
