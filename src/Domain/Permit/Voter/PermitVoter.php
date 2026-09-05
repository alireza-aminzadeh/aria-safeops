<?php

namespace App\Domain\Permit\Voter;

use App\Domain\Permit\Entity\Permit;
use App\Domain\Shared\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class PermitVoter extends Voter
{
    public const ISSUE = 'PERMIT_ISSUE';
    public const APPROVE = 'PERMIT_APPROVE';
    public const REJECT = 'PERMIT_REJECT';
    public const ACTIVATE = 'PERMIT_ACTIVATE';
    public const SUSPEND = 'PERMIT_SUSPEND';
    public const CLOSE = 'PERMIT_CLOSE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $subject instanceof Permit && str_starts_with($attribute, 'PERMIT_');
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User || !$subject instanceof Permit) {
            return false;
        }

        if ((string) $subject->getTenant()->getId() !== (string) $user->getTenant()->getId()) {
            return false;
        }

        $roles = $user->getRoles();
        $isAdmin = in_array('ROLE_ADMIN', $roles, true);
        $isHse = $isAdmin || in_array('ROLE_HSE_MANAGER', $roles, true);
        $isIssuer = $isHse || in_array('ROLE_PERMIT_ISSUER', $roles, true);

        return match ($attribute) {
            self::APPROVE, self::REJECT, self::ACTIVATE, self::SUSPEND => $isHse,
            self::ISSUE, self::CLOSE => $isIssuer,
            default => false,
        };
    }
}
