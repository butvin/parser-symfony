<?php

namespace App\Repository;

use App\Entity\Application;
use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Publisher;

/**
 * @method Application|null find($id, $lockMode = null, $lockVersion = null)
 * @method Application|null findOneBy(array $criteria, array $orderBy = null)
 * @method Application[]    findAll()
 * @method Application[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ApplicationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Application::class);
    }

    final public function getDistinctCategories(): ?array
    {
        $sql = "
        select distinct a.category_id, c.external_id from application a
        left join category c on a.category_id = c.id
        where  a.deleted_at is null
                ";

        $rsm = (new ResultSetMapping())
            ->addEntityResult(Category::class, 'c')
            ->addScalarResult('category_id', 'id')
            ->addScalarResult('external_id', 'external_id');

        return $this
            ->getEntityManager()
            ->createNativeQuery($sql, $rsm)
            ->getResult()
            ;
    }

    final public function getPlayMarketApplications(): ?array
    {
        $qb = $this->createQueryBuilder('a');
        $expr = $qb->expr();

        return $qb
            ->select('a')
            ->leftJoin(
                Publisher::class,
                'p',
                Join::WITH,
                'p.id = a.publisher',
                null
            )
            ->where(
                $expr->andX(
                    $expr->isNull('a.deletedAt'),
                    $expr->isNull('p.deletedAt'),
                    'p.type = :type'
                )
            )
            ->setParameter('type', Publisher::TYPE_PLAY_STORE)
            ->getQuery()
            ->getResult()
        ;
    }

    final public function getPlayMarketAppsByCategory(Category $category): ?array
    {
        $qb = $this->createQueryBuilder('a');
        $expr = $qb->expr();

        return $qb
            ->select('a')
            ->leftJoin(Publisher::class,'p', Join::WITH, 'p.id = a.publisher')
            ->leftJoin(Category::class,'c', Join::WITH, 'c.id = a.category')
            ->andWhere($expr->andX($expr->isNull('a.deletedAt'), $expr->isNull('p.deletedAt')))
            ->andWhere('p.type = :type')
            ->andWhere('a.category = :category')
            ->setParameter('type', Publisher::TYPE_PLAY_STORE)
            ->setParameter('category', $category)
            ->getQuery()
            ->getResult()
        ;
    }
}
