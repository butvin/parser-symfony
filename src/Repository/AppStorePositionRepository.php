<?php

namespace App\Repository;

use App\Entity\AppStorePosition;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method AppStorePosition|null find($id, $lockMode = null, $lockVersion = null)
 * @method AppStorePosition|null findOneBy(array $criteria, array $orderBy = null)
 * @method AppStorePosition[]    findAll()
 * @method AppStorePosition[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AppStorePositionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AppStorePosition::class);
    }

    public function findLastResults(string $ratingType, int $applicationId,  $createDate)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.ratingType = :ratingType')
            ->andWhere('s.application = :application')
            ->andWhere('s.createdAt >= :dateStart')
            ->andWhere('s.createdAt <= :dateFinish')
            ->setParameter('ratingType', $ratingType)
            ->setParameter('application', $applicationId)
            ->setParameter('dateStart', (new \DateTime($createDate))->format('Y-m-d 00:00:00'))
            ->setParameter('dateFinish',   (new \DateTime($createDate))->format('Y-m-d 23:59:59'))
            ->orderBy('s.id', 'DESC')
            ->getQuery()
            ->getResult()
            ;
    }

    public function findLastResult(string $country, string $ratingType, int $applicationId)
    {
        return $this->createQueryBuilder('s')
            ->select('s.currIndex')
            ->andWhere('s.country = :country')
            ->andWhere('s.ratingType = :ratingType')
            ->andWhere('s.application = :application')
            ->andWhere('s.currIndex IS NOT NULL')
            ->setParameter('country', $country)
            ->setParameter('ratingType', $ratingType)
            ->setParameter('application', $applicationId)
            ->orderBy('s.id , s.country', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getResult()
            ;
    }
}
