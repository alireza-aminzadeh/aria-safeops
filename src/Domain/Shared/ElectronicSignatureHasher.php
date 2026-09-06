<?php

namespace App\Domain\Shared;

/**
 * محاسبهٔ اثر انگشت رمزنگاری‌شدهٔ یک امضای الکترونیک. برای اثبات عدم دستکاری،
 * هش زنجیرهٔ ممیزی مرتبط (linkedAuditHash) هم در مادهٔ هش لحاظ می‌شود؛ یعنی
 * تغییر گذشته‌نگر هر رکورد ممیزی، امضاهای بعدی را نامنطبق می‌کند.
 */
final class ElectronicSignatureHasher
{
    public static function hash(
        string $entityType,
        string $entityId,
        string $action,
        ?string $signerId,
        string $signerName,
        string $signedAtAtom,
        string $linkedAuditHash,
    ): string {
        $material = implode('|', [
            $entityType,
            $entityId,
            $action,
            $signerId ?? '',
            $signerName,
            $signedAtAtom,
            $linkedAuditHash,
        ]);

        return hash('sha256', $material);
    }
}
