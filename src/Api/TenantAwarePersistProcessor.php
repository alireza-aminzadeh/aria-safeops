<?php

namespace App\Api;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Domain\Incident\Entity\Incident;
use App\Domain\Moc\Entity\MocRequest;
use App\Domain\Permit\Entity\Permit;
use App\Domain\Shared\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class TenantAwarePersistProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private readonly ProcessorInterface $persist,
        private readonly Security $security,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        $user = $this->security->getUser();
        if ($user instanceof User) {
            if ($data instanceof Permit) {
                $data->setTenant($user->getTenant());
                $data->setRequestedBy($user);
            }
            if ($data instanceof MocRequest) {
                $data->setTenant($user->getTenant());
                $data->setRequestedBy($user);
            }
            if ($data instanceof Incident) {
                $data->setTenant($user->getTenant());
                $data->setReportedBy($user);
            }
        }

        return $this->persist->process($data, $operation, $uriVariables, $context);
    }
}
