<?php

namespace App\Repository;

use App\Entity\Workout;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class WorkoutRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Workout::class);
    }

    public function findByMonth(string $year, string $month)
    {
        return $this->createQueryBuilder('w')
            ->where('w.start LIKE :year')
            ->setParameter('year', "$year-$month%")
            ->getQuery()->getResult();
    }

    public function persist(Workout $workoutEntity): void
    {
        $this->getEntityManager()->persist($workoutEntity);
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }
}
