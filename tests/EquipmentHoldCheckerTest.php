<?php

namespace App\Tests;

use App\Domain\Integration\Entity\EquipmentHold;
use App\Domain\Integration\EquipmentHoldChecker;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;

final class EquipmentHoldCheckerTest extends TestCase
{
    private function checkerWithHold(?EquipmentHold $hold): EquipmentHoldChecker
    {
        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findOneBy')->willReturn($hold);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->with(EquipmentHold::class)->willReturn($repository);

        return new EquipmentHoldChecker($em);
    }

    public function testNoHoldMeansNotBlocked(): void
    {
        $checker = $this->checkerWithHold(null);

        $this->assertFalse($checker->isBlocked('P-101'));
        $this->assertNull($checker->blockerMessage('P-101'));
    }

    public function testOpenHoldBlocksAndExplainsWithScore(): void
    {
        $hold = new EquipmentHold('P-101', 'evt-1', 'open', new \DateTimeImmutable(), 0.93, 'ارتعاش غیرعادی');
        $checker = $this->checkerWithHold($hold);

        $this->assertTrue($checker->isBlocked('P-101'));
        $message = $checker->blockerMessage('P-101');
        $this->assertNotNull($message);
        $this->assertStringContainsString('P-101', $message);
        $this->assertStringContainsString('0.93', $message);
    }

    public function testResolvedHoldDoesNotBlock(): void
    {
        $hold = new EquipmentHold('P-101', 'evt-1', 'resolved', new \DateTimeImmutable());
        $checker = $this->checkerWithHold($hold);

        $this->assertFalse($checker->isBlocked('P-101'));
        $this->assertNull($checker->blockerMessage('P-101'));
    }

    public function testEmptyOrNullEquipmentTagIsNeverBlocked(): void
    {
        // حتی اگر یک نگه‌داشت باز در پایگاه‌داده باشد، بدون تگ تجهیز معنایی ندارد.
        $checker = $this->checkerWithHold(new EquipmentHold('P-101', 'evt-1', 'open', new \DateTimeImmutable()));

        $this->assertFalse($checker->isBlocked(null));
        $this->assertFalse($checker->isBlocked(''));
        $this->assertFalse($checker->isBlocked('   '));
    }
}
