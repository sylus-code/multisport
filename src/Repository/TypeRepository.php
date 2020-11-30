<?php

namespace App\Repository;

use App\Entity\Type;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class TypeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Type::class);
    }

    public function findOneBy(array $criteria, array $orderBy = null): Type
    {
        return parent::findOneBy($criteria, $orderBy);
    }

    public function persist(Type $type): void
    {
        $this->getEntityManager()->persist($type);
    }
}
