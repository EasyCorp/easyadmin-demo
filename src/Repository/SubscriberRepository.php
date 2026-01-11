<?php

namespace App\Repository;

use App\Entity\Subscriber;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Subscriber>
 */
class SubscriberRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Subscriber::class);
    }

    public function countActive(): int
    {
        return $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.isConfirmed = :confirmed')
            ->andWhere('s.unsubscribedAt IS NULL')
            ->setParameter('confirmed', true)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countPending(): int
    {
        return $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.isConfirmed = :confirmed')
            ->andWhere('s.unsubscribedAt IS NULL')
            ->setParameter('confirmed', false)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
