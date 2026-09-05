<?php

namespace App\Command;

use App\Domain\Contractor\Entity\Contractor;
use App\Domain\Contractor\Entity\ContractorCertification;
use App\Domain\Contractor\Entity\ContractorTrainingRecord;
use App\Domain\Moc\Entity\HazopRegisterItem;
use App\Domain\Permit\Entity\PermitType;
use App\Domain\Psm\Entity\BowtieBarrier;
use App\Domain\Psm\Entity\LopaScenario;
use App\Domain\Shared\Entity\Tenant;
use App\Domain\Shared\Entity\User;
use App\Domain\Shared\OperatorCredentials;
use App\Domain\Shift\Entity\ToolboxTalk;
use App\Domain\Vision\Entity\VisionCamera;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Uid\Uuid;

#[AsCommand(name: 'app:seed', description: 'Seed demo tenant, users and permit types')]
final class SeedCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $hasher,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $tenantId = Uuid::fromString('11111111-1111-1111-1111-111111111111');
        $tenant = $this->em->find(Tenant::class, $tenantId);
        if (!$tenant) {
            $tenant = new Tenant('سایت عملیاتی نمونه آریا', $tenantId);
            $this->em->persist($tenant);
        }

        $this->upsertUser($tenant, 'admin@hse.aria-ai.ir', 'admin', 'مدیر سامانه', ['ROLE_ADMIN'], 'ChangeMe!Admin1');
        $this->upsertUser($tenant, 'hse@hse.aria-ai.ir', 'hse', 'مدیر HSE', ['ROLE_HSE_MANAGER'], 'ChangeMe!Hse1');
        $this->upsertUser($tenant, 'issuer@hse.aria-ai.ir', 'issuer', 'صادرکننده مجوز', ['ROLE_PERMIT_ISSUER'], 'ChangeMe!Issuer1');
        $this->upsertUser(
            $tenant,
            'alireza@hse.aria-ai.ir',
            OperatorCredentials::USERNAME,
            'علیرضا',
            ['ROLE_ADMIN'],
            OperatorCredentials::passwordFromEnvironment(),
            true,
        );

        $types = [
            ['hot_work', 'کار گرم', 'Hot Work', true, true],
            ['cold_work', 'کار سرد', 'Cold Work', false, false],
            ['confined_space', 'فضای بسته', 'Confined Space', true, true],
            ['working_at_height', 'کار در ارتفاع', 'Working at Height', false, false],
            ['excavation', 'حفاری', 'Excavation', false, true],
            ['electrical', 'برق', 'Electrical', false, true],
        ];
        foreach ($types as [$code, $fa, $en, $gas, $iso]) {
            $existing = $this->em->getRepository(PermitType::class)->findOneBy(['code' => $code]);
            if (!$existing) {
                $this->em->persist(new PermitType($code, $fa, $en, $gas, $iso));
            }
        }

        $contractor = $this->em->getRepository(Contractor::class)->findOneBy(['companyName' => 'پیمانکار نمونه پارس']);
        if (!$contractor) {
            $contractor = new Contractor();
            $contractor->setCompanyName('پیمانکار نمونه پارس');
            $contractor->setHsePrequalificationScore('82.50');
            $this->em->persist($contractor);
            $this->em->persist(new ContractorCertification(
                $contractor,
                'H2S Awareness',
                new \DateTimeImmutable('+20 days'),
            ));
        }
        if ($contractor->getTrainingRecords()->isEmpty()) {
            $this->em->persist(new ContractorTrainingRecord(
                $contractor,
                'PTW-01',
                'مجوز کار و گاز‌تست',
                new \DateTimeImmutable('-40 days'),
                new \DateTimeImmutable('+320 days'),
            ));
        }

        if (!$this->em->getRepository(HazopRegisterItem::class)->findOneBy(['deviation' => 'More flow'])) {
            $hazop = new HazopRegisterItem(
                'پمپ خوراک P-101',
                'More flow',
                'باز شدن کنترل ولو تخلیه',
                'فشار بالای ستون و فعال‌شدن PSV',
                'PSV-101، آلارم فشار، Trip پمپ',
                'high',
            );
            $hazop->setTenant($tenant);
            $hazop->setEquipmentTag('P-101');
            $this->em->persist($hazop);
            $lopa = new LopaScenario($hazop, 'شکست کنترل سطح', 3, '1e-4 /yr', 'medium');
            $hazop->addLopa($lopa);
            $this->em->persist($lopa);
            $prevention = new BowtieBarrier($hazop, 'prevention', 'کنترل سطح ستون + اینترلاک', 'high');
            $mitigation = new BowtieBarrier($hazop, 'mitigation', 'PSV و فلر', 'high');
            $hazop->addBarrier($prevention);
            $hazop->addBarrier($mitigation);
            $this->em->persist($prevention);
            $this->em->persist($mitigation);
        }

        if (!$this->em->getRepository(VisionCamera::class)->findOneBy(['name' => 'گیت واحد تقطیر'])) {
            $this->em->persist(new VisionCamera($tenant, 'گیت واحد تقطیر', 'CDU gate', 'rtsp://camera.local/cdu-gate'));
            $this->em->persist(new VisionCamera($tenant, 'محوطه P-101', 'process pump alley', 'rtsp://camera.local/p101'));
        }

        if (!$this->em->getRepository(ToolboxTalk::class)->findOneBy(['topic' => 'H2S و گاز‌تست'])) {
            $this->em->persist(new ToolboxTalk(
                $tenant,
                'H2S و گاز‌تست',
                'اتاق کنترل',
                new \DateTimeImmutable('-1 day'),
                'علیرضا',
                8,
                'یادآوری حد LEL و H2S قبل از کار گرم',
            ));
        }

        $this->em->flush();
        $output->writeln('Seed complete.');
        return Command::SUCCESS;
    }

    /**
     * @param list<string> $roles
     */
    private function upsertUser(
        Tenant $tenant,
        string $email,
        string $username,
        string $name,
        array $roles,
        string $plain,
        bool $resetPassword = false,
    ): void {
        $existing = $this->em->getRepository(User::class)->findOneBy(['username' => $username])
            ?? $this->em->getRepository(User::class)->findOneBy(['email' => $email]);

        if ($existing) {
            $existing->setUsername($username);
            if ($resetPassword) {
                $existing->setPasswordHash($this->hasher->hashPassword($existing, $plain));
            }
            return;
        }

        $user = new User($tenant, $email, $name, $roles, 'pending');
        $user->setUsername($username);
        $user->setPasswordHash($this->hasher->hashPassword($user, $plain));
        $this->em->persist($user);
    }
}
