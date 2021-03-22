<?php

namespace App\Repository;

use App\Entity\Point;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PointRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Point::class);
    }

    public function persist(Point $pointEntity): void
    {
        $this->getEntityManager()->persist($pointEntity);
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }
}
