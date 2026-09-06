<?php

namespace App\Tests;

use App\Domain\Shared\Entity\AuditLogEntry;
use App\Infrastructure\Audit\AuditLogger;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

/**
 * زنجیرهٔ هش AuditLogger (شبیه blockchain ساده) باید تضمین کند که هیچ رکوردی
 * در جدول audit_log بدون شکستن زنجیره قابل ویرایش/حذف نیست. این تست با یک
 * EntityManager جعلی درون‌حافظه‌ای (بدون نیاز به Postgres واقعی) رفتار
 * زنجیره را روی چند رکورد پیاپی و یک سناریوی دستکاری‌شده بررسی می‌کند.
 */
final class AuditLoggerHashChainTest extends TestCase
{
    /** @return array{0: AuditLogger, 1: \ArrayObject<int, AuditLogEntry>} */
    private function loggerWithInMemoryStore(): array
    {
        /** @var \ArrayObject<int, AuditLogEntry> $store */
        $store = new \ArrayObject();

        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findOneBy')->willReturnCallback(
            static fn (): ?AuditLogEntry => $store->count() === 0 ? null : $store[$store->count() - 1],
        );
        $repository->method('findBy')->willReturnCallback(
            static fn (): array => $store->getArrayCopy(),
        );

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repository);
        $em->method('persist')->willReturnCallback(static function (object $entity) use ($store): void {
            if ($entity instanceof AuditLogEntry) {
                $store[] = $entity;
            }
        });
        $em->method('flush');

        return [new AuditLogger($em), $store];
    }

    public function testEachEntryChainsToThePreviousHash(): void
    {
        [$logger, $store] = $this->loggerWithInMemoryStore();
        $tenantId = Uuid::v7();

        $logger->record($tenantId, 'permit', 'p-1', 'submit');
        $logger->record($tenantId, 'permit', 'p-1', 'activate');
        $logger->record($tenantId, 'permit', 'p-1', 'close');

        $this->assertCount(3, $store);
        $this->assertSame(str_repeat('0', 64), $store[0]->getPrevHash(), 'اولین رکورد باید به هش صفر (genesis) زنجیر شود.');
        $this->assertSame($store[0]->getHash(), $store[1]->getPrevHash());
        $this->assertSame($store[1]->getHash(), $store[2]->getPrevHash());

        // هش‌ها باید ۶۴ کاراکتری (sha256 hex) و برای محتوای متفاوت، متفاوت باشند.
        $this->assertSame(64, strlen($store[0]->getHash()));
        $this->assertNotSame($store[0]->getHash(), $store[1]->getHash());

        $this->assertTrue($logger->verifyChain($tenantId)['valid']);
    }

    public function testVerifyChainDetectsTamperedRecord(): void
    {
        [$logger, $store] = $this->loggerWithInMemoryStore();
        $tenantId = Uuid::v7();

        $logger->record($tenantId, 'permit', 'p-1', 'submit');
        $logger->record($tenantId, 'permit', 'p-1', 'activate');

        // شبیه‌سازی یک UPDATE مستقیم و غیرمجاز روی جدول audit_log در پایگاه‌داده:
        // رکورد دوم با prev_hash جعلی جایگزین می‌شود.
        $store[1] = new AuditLogEntry($tenantId, 'permit', 'p-1', 'activate', 'f'.str_repeat('0', 63), 'deadbeef');

        $result = $logger->verifyChain($tenantId);
        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('brokenAt', $result);
    }

    public function testGenesisRecordAlwaysStartsFromZeroHash(): void
    {
        [$logger, $store] = $this->loggerWithInMemoryStore();

        $logger->record(Uuid::v7(), 'incident', 'i-1', 'submit');

        $this->assertSame(str_repeat('0', 64), $store[0]->getPrevHash());
    }
}
