<?php

namespace App\Repository;

use App\Entity\AppStoreApplication;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method AppStoreApplication|null find($id, $lockMode = null, $lockVersion = null)
 * @method AppStoreApplication|null findOneBy(array $criteria, array $orderBy = null)
 * @method AppStoreApplication[]    findAll()
 * @method AppStoreApplication[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AppStoreApplicationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AppStoreApplication::class);
    }
}
