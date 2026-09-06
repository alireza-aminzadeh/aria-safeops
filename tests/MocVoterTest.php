<?php

namespace App\Tests;

use App\Domain\Moc\Entity\MocRequest;
use App\Domain\Moc\Voter\MocVoter;
use App\Domain\Shared\Entity\Tenant;
use App\Domain\Shared\Entity\User;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class MocVoterTest extends TestCase
{
    private function mocFor(Tenant $tenant): MocRequest
    {
        $moc = new MocRequest();
        $moc->setTenant($tenant);
        $moc->setDescription('تغییر مسیر خط لوله بخار');

        return $moc;
    }

    private function tokenFor(?User $user): TokenInterface
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        return $token;
    }

    public function testHseManagerCanApproveAndClose(): void
    {
        $tenant = new Tenant('شرکت الف');
        $moc = $this->mocFor($tenant);
        $hse = new User($tenant, 'hse@test.local', 'مدیر ایمنی', ['ROLE_HSE_MANAGER'], 'hash');
        $voter = new MocVoter();
        $token = $this->tokenFor($hse);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $moc, [MocVoter::APPROVE]));
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $moc, [MocVoter::CLOSE]));
    }

    public function testPermitIssuerAndOperatorCanUpdateButNotApprove(): void
    {
        $tenant = new Tenant('شرکت الف');
        $moc = $this->mocFor($tenant);
        $issuer = new User($tenant, 'issuer@test.local', 'صادرکننده مجوز', ['ROLE_PERMIT_ISSUER'], 'hash');
        $operator = new User($tenant, 'op@test.local', 'اپراتور', ['ROLE_OPERATOR'], 'hash');
        $voter = new MocVoter();

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($this->tokenFor($issuer), $moc, [MocVoter::UPDATE]));
        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($this->tokenFor($issuer), $moc, [MocVoter::APPROVE]));
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($this->tokenFor($operator), $moc, [MocVoter::UPDATE]));
        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($this->tokenFor($operator), $moc, [MocVoter::CLOSE]));
    }

    /** ایزولاسیون تننت: مدیر HSE یک تننت دیگر نباید بتواند MOC این تننت را تأیید کند. */
    public function testUserFromOtherTenantIsDenied(): void
    {
        $tenantA = new Tenant('شرکت الف');
        $tenantB = new Tenant('شرکت ب');
        $moc = $this->mocFor($tenantA);
        $foreignHse = new User($tenantB, 'hse@other.local', 'مدیر ب', ['ROLE_HSE_MANAGER'], 'hash');
        $voter = new MocVoter();

        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($this->tokenFor($foreignHse), $moc, [MocVoter::APPROVE]));
    }
}
