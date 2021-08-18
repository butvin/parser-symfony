<?php

namespace App\Repository;

use App\Entity\AppStorePublisher;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method AppStorePublisher|null find($id, $lockMode = null, $lockVersion = null)
 * @method AppStorePublisher|null findOneBy(array $criteria, array $orderBy = null)
 * @method AppStorePublisher[]    findAll()
 * @method AppStorePublisher[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AppStorePublisherRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AppStorePublisher::class);
    }
}
