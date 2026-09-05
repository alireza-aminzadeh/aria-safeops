<?php

namespace App\Domain\Moc\Voter;

use App\Domain\Moc\Entity\MocRequest;
use App\Domain\Shared\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class MocVoter extends Voter
{
    public const UPDATE = 'MOC_UPDATE';
    public const APPROVE = 'MOC_APPROVE';
    public const CLOSE = 'MOC_CLOSE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $subject instanceof MocRequest && str_starts_with($attribute, 'MOC_');
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User || !$subject instanceof MocRequest) {
            return false;
        }

        if ((string) $subject->getTenant()->getId() !== (string) $user->getTenant()->getId()) {
            return false;
        }

        $roles = $user->getRoles();
        $isAdmin = in_array('ROLE_ADMIN', $roles, true);
        $isHse = $isAdmin || in_array('ROLE_HSE_MANAGER', $roles, true);

        return match ($attribute) {
            self::APPROVE, self::CLOSE => $isHse,
            self::UPDATE => $isHse || in_array('ROLE_PERMIT_ISSUER', $roles, true) || in_array('ROLE_OPERATOR', $roles, true),
            default => false,
        };
    }
}
