<?php

namespace App\Helper;

use Doctrine\ORM\EntityManagerInterface;

trait DataBaseReconnect
{
    protected function reconnect(EntityManagerInterface $em): void
    {
        if ($em->getConnection()->ping() === false) {
            $em->getConnection()->close();
            $em->getConnection()->connect();
        }
    }
}