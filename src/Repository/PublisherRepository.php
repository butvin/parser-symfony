<?php

namespace App\Repository;

use App\Entity\Application;
use App\Entity\Publisher;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Publisher|null find($id, $lockMode = null, $lockVersion = null)
 * @method Publisher|null findOneBy(array $criteria, array $orderBy = null)
 * @method Publisher[]    findAll()
 * @method Publisher[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PublisherRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Publisher::class);
    }

    public function getSortedOrdered()
    {
        return $this->createQueryBuilder('p')
//            ->andWhere('p.deletedAt >= :value')
//            ->andWhere("DATE_ADD(p.deletedAt,24,'hour') >= :value")
//            ->andWhere("p.deletedAt >= :value")
//            ->setParameter('value', new \DateTime(Application::BANNED_TTL))
            ->orderBy('p.deletedAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
