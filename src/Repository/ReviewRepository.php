<?php

namespace App\Repository;

use App\Entity\Review;
use App\Enum\ReviewStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Review>
 */
class ReviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Review::class);
    }

    /**
     * @return Review[]
     */
    public function findLatestPending(int $maxResults): array
    {
        return $this->findBy(['status' => ReviewStatus::Pending], ['publishedAt' => 'DESC'], $maxResults);
    }
}
