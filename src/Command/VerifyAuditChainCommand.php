<?php

namespace App\Command;

use App\Infrastructure\Audit\AuditLogger;
use App\Domain\Shared\Entity\Tenant;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:audit:verify-chain', description: 'Verify audit hash chain')]
final class VerifyAuditChainCommand extends Command
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $tenants = $this->em->getRepository(Tenant::class)->findAll();
        foreach ($tenants as $tenant) {
            $result = $this->audit->verifyChain($tenant->getId());
            $output->writeln($tenant->getName().': '.($result['valid'] ? 'OK' : 'BROKEN '.$result['brokenAt']));
        }
        return Command::SUCCESS;
    }
}
