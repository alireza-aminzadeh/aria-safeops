<?php

namespace App\Command;

use App\Domain\Contractor\Entity\Contractor;
use App\Domain\Contractor\Entity\ContractorCertification;
use App\Domain\Permit\Entity\PermitType;
use App\Domain\Shared\Entity\Tenant;
use App\Domain\Shared\Entity\User;
use App\Domain\Shared\OperatorCredentials;
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
