<?php

namespace App\Repository;

use App\Domain\Shared\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
final class UserRepository extends ServiceEntityRepository implements UserLoaderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function loadUserByIdentifier(string $identifier): ?UserInterface
    {
        $normalized = strtolower($identifier);

        return $this->createQueryBuilder('u')
            ->where('LOWER(u.username) = :id OR LOWER(u.email) = :id')
            ->setParameter('id', $normalized)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
