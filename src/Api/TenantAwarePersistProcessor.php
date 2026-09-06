<?php

namespace App\Api;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use App\Domain\Incident\Entity\Incident;
use App\Domain\Incident\SafetyKpiCalculator;
use App\Domain\Integration\Entity\EquipmentHold;
use App\Domain\Moc\Entity\MocRequest;
use App\Domain\Permit\Entity\Permit;
use App\Domain\Shared\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

final class TenantAwarePersistProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private readonly ProcessorInterface $persist,
        private readonly Security $security,
        private readonly EntityManagerInterface $em,
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

        if ($operation instanceof Post && $data instanceof Incident) {
            // طبقه‌بندی «ثبت‌شدنی» (Recordable) طبق قرارداد OSHA 1904/API 754 همیشه در لحظهٔ
            // ثبت به‌صورت خودکار محاسبه می‌شود؛ مدیر HSE می‌تواند بعداً از طریق PATCH اصلاح کند.
            $data->setRecordable(SafetyKpiCalculator::isRecordableByDefault($data->getType(), $data->getSeverity()));
        }

        if ($operation instanceof Post) {
            if ($data instanceof Permit) {
                $this->assertEquipmentNotHeld($data->getEquipmentTag());
            }
            if ($data instanceof MocRequest && $data->getEquipmentTag()) {
                $this->assertEquipmentNotHeld($data->getEquipmentTag());
            }
        }

        return $this->persist->process($data, $operation, $uriVariables, $context);
    }

    private function assertEquipmentNotHeld(string $equipmentTag): void
    {
        $hold = $this->em->getRepository(EquipmentHold::class)->findOneBy([
            'equipmentTag' => $equipmentTag,
        ]);
        if ($hold instanceof EquipmentHold && $hold->getStatus() === 'open') {
            throw new UnprocessableEntityHttpException(
                sprintf('تجهیز %s به‌خاطر آنومالی باز پتروپایش مسدود است.', $equipmentTag),
            );
        }
    }
}
