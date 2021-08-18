<?php

namespace App\Repository;

use App\Entity\Application;
use App\Entity\Position;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use \Exception;

/**
 * @method Position|null find($id, $lockMode = null, $lockVersion = null)
 * @method Position|null findOneBy(array $criteria, array $orderBy = null)
 * @method Position[]    findAll()
 * @method Position[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PositionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Position::class);
    }

    /**
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws Exception
     */
    final public function getPrevPosition(Application $application, string $ratingType, string $country): ?Position
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.application = :app')
            ->andWhere('p.ratingType = :rating')
            ->andWhere('p.country = :country')
            ->orderBy('p.id', 'desc')
            ->setParameter('app', $application)
            ->setParameter('country', $country)
            ->setParameter('rating', $ratingType)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * @throws Exception
     */
    final public function getDailyPositions(Application $application, \DateTime $date): ?array
    {
        $start = new \DateTime(
            sprintf('%s 00:00:00', $date->format('Y-m-d'))
        );

        $end = new \DateTime(
            sprintf('%s 23:59:59', $date->format('Y-m-d'))
        );

        return $this->createQueryBuilder('p')
            ->andWhere('p.createdAt BETWEEN :from AND :to')
            ->andWhere('p.application = :appId')
            ->orderBy('p.id', 'desc')
            ->setParameter('appId', $application->getId())
            ->setParameter('from', $start)
            ->setParameter('to', $end)
            ->getQuery()
            ->getResult()
        ;
    }
}