<?php

namespace App\Controller;

use App\Domain\Contractor\Entity\ContractorCertification;
use App\Domain\Incident\Entity\Incident;
use App\Domain\Moc\Entity\MocRequest;
use App\Domain\Permit\Entity\Permit;
use App\Domain\Shared\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class DashboardController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    #[Route('/api/dashboard', methods: ['GET'])]
    public function __invoke(#[CurrentUser] User $user): JsonResponse
    {
        $tenant = $user->getTenant();

        return $this->json([
            'permits' => $this->counts(Permit::class, $tenant),
            'mocs' => $this->counts(MocRequest::class, $tenant),
            'incidents' => $this->counts(Incident::class, $tenant),
            'expiringCertifications' => $this->expiringCerts(),
        ]);
    }

    /**
     * @return array<string, int>
     */
    private function counts(string $class, mixed $tenant): array
    {
        $rows = $this->em->createQueryBuilder()
            ->select('e.status AS status, COUNT(e.id) AS c')
            ->from($class, 'e')
            ->andWhere('e.tenant = :tenant')
            ->setParameter('tenant', $tenant)
            ->groupBy('e.status')
            ->getQuery()
            ->getArrayResult();

        $out = [];
        foreach ($rows as $row) {
            $out[(string) $row['status']] = (int) $row['c'];
        }

        return $out;
    }

    /**
     * @return list<array{id: string, type: string, expiresAt: string, companyName: string, expiringSoon: bool}>
     */
    private function expiringCerts(): array
    {
        /** @var list<ContractorCertification> $certs */
        $certs = $this->em->createQueryBuilder()
            ->select('cert')
            ->from(ContractorCertification::class, 'cert')
            ->join('cert.contractor', 'c')
            ->addSelect('c')
            ->where('cert.expiresAt <= :limit')
            ->setParameter('limit', new \DateTimeImmutable('+30 days'))
            ->orderBy('cert.expiresAt', 'ASC')
            ->setMaxResults(12)
            ->getQuery()
            ->getResult();

        $out = [];
        foreach ($certs as $cert) {
            $out[] = [
                'id' => (string) $cert->getId(),
                'type' => $cert->getType(),
                'expiresAt' => $cert->getExpiresAt()->format('Y-m-d'),
                'companyName' => $cert->getContractor()->getCompanyName(),
                'expiringSoon' => $cert->isExpiringSoon(),
            ];
        }

        return $out;
    }
}
