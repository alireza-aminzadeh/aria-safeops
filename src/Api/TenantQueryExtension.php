<?php

namespace App\Api;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Domain\Incident\Entity\Incident;
use App\Domain\Moc\Entity\MocRequest;
use App\Domain\Permit\Entity\Permit;
use App\Domain\Shared\Entity\User;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;

final class TenantQueryExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{
    public function __construct(private readonly Security $security)
    {
    }

    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        $this->restrict($queryBuilder, $resourceClass);
    }

    public function applyToItem(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, array $identifiers, ?Operation $operation = null, array $context = []): void
    {
        $this->restrict($queryBuilder, $resourceClass);
    }

    /**
     * منابع API Platform که ستون/رابطهٔ `tenant` روی خودشان دارند و باید طبق
     * تننت کاربر جاری فیلتر شوند. توجه: `Contractor` عمداً اینجا نیست — در
     * مدل دادهٔ فعلی، پیمانکار سطح تننت ندارد (فرض: پیمانکار مشترک بین
     * سایت‌ها)؛ اگر این فرض عوض شد، اول ستون `tenant_id` باید به Contractor
     * اضافه شود، بعد اینجا لیست شود.
     *
     * @var list<class-string>
     */
    private const TENANT_SCOPED_RESOURCES = [Permit::class, MocRequest::class, Incident::class, User::class];

    private function restrict(QueryBuilder $queryBuilder, string $resourceClass): void
    {
        if (!in_array($resourceClass, self::TENANT_SCOPED_RESOURCES, true)) {
            return;
        }
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return;
        }
        $root = $queryBuilder->getRootAliases()[0];
        $queryBuilder
            ->andWhere(sprintf('%s.tenant = :aria_tenant', $root))
            ->setParameter('aria_tenant', $user->getTenant());
    }
}
