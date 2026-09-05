<?php

namespace App\EventSubscriber;

use App\Domain\Shared\Entity\User;
use Doctrine\DBAL\Exception as DbalException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class TenantConnectionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly Security $security,
        private readonly EntityManagerInterface $em,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['onRequest', 4]];
    }

    public function onRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return;
        }

        try {
            $this->em->getConnection()->executeStatement(
                "SELECT set_config('app.tenant_id', ?, false)",
                [(string) $user->getTenant()->getId()],
            );
        } catch (DbalException) {
            // RLS is best-effort when the connection does not support set_config (e.g. tests).
        }
    }
}
