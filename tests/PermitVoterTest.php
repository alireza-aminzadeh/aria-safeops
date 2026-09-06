<?php

namespace App\Tests;

use App\Domain\Permit\Entity\Permit;
use App\Domain\Permit\Entity\PermitType;
use App\Domain\Permit\Voter\PermitVoter;
use App\Domain\Shared\Entity\Tenant;
use App\Domain\Shared\Entity\User;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class PermitVoterTest extends TestCase
{
    private function permitFor(Tenant $tenant): Permit
    {
        $permit = new Permit();
        $permit->setTenant($tenant);
        $permit->setPermitType(new PermitType('hot_work', 'کار گرم', 'Hot Work', true, true));

        return $permit;
    }

    private function tokenFor(?User $user): TokenInterface
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        return $token;
    }

    public function testHseManagerCanApproveActivateAndSuspend(): void
    {
        $tenant = new Tenant('شرکت الف');
        $permit = $this->permitFor($tenant);
        $hse = new User($tenant, 'hse@test.local', 'مدیر ایمنی', ['ROLE_HSE_MANAGER'], 'hash');
        $voter = new PermitVoter();
        $token = $this->tokenFor($hse);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $permit, [PermitVoter::APPROVE]));
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $permit, [PermitVoter::ACTIVATE]));
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $permit, [PermitVoter::SUSPEND]));
    }

    public function testPermitIssuerCanIssueAndCloseButNotApprove(): void
    {
        $tenant = new Tenant('شرکت الف');
        $permit = $this->permitFor($tenant);
        $issuer = new User($tenant, 'issuer@test.local', 'صادرکننده مجوز', ['ROLE_PERMIT_ISSUER'], 'hash');
        $voter = new PermitVoter();
        $token = $this->tokenFor($issuer);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $permit, [PermitVoter::ISSUE]));
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $permit, [PermitVoter::CLOSE]));
        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $permit, [PermitVoter::APPROVE]));
    }

    public function testPlainOperatorCannotIssue(): void
    {
        $tenant = new Tenant('شرکت الف');
        $permit = $this->permitFor($tenant);
        $operator = new User($tenant, 'op@test.local', 'اپراتور', ['ROLE_OPERATOR'], 'hash');
        $voter = new PermitVoter();

        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($this->tokenFor($operator), $permit, [PermitVoter::ISSUE]));
    }

    /**
     * ایزولاسیون تننت: حتی ادمین یک تننت دیگر نباید بتواند روی مجوزهای
     * تننت دیگر رأی مثبت بگیرد. این دقیقاً همان شرطی است که PermitVoter
     * قبل از بررسی نقش، چک می‌کند.
     */
    public function testUserFromOtherTenantIsDeniedRegardlessOfRole(): void
    {
        $tenantA = new Tenant('شرکت الف');
        $tenantB = new Tenant('شرکت ب');
        $permit = $this->permitFor($tenantA);
        $foreignAdmin = new User($tenantB, 'admin@other.local', 'مدیر ب', ['ROLE_ADMIN'], 'hash');
        $voter = new PermitVoter();

        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($this->tokenFor($foreignAdmin), $permit, [PermitVoter::APPROVE]));
        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($this->tokenFor($foreignAdmin), $permit, [PermitVoter::CLOSE]));
    }

    public function testUnauthenticatedTokenAbstains(): void
    {
        $tenant = new Tenant('شرکت الف');
        $permit = $this->permitFor($tenant);
        $voter = new PermitVoter();

        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($this->tokenFor(null), $permit, [PermitVoter::APPROVE]));
    }
}
