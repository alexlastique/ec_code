<?php

namespace App\Repository;

use App\Entity\BookRead;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BookRead>
 */
class BookReadRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BookRead::class);
    }

    /**
     * Method to find all ReadBook entities by user_id
     * @param int $userId
     * @param bool $readState
     * @return array
     */
    public function findByUserId(int $userId): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.user_id = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('r.created_at', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findReadByUserId(int $userId): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.user_id = :userId')
            ->andWhere('r.is_read = true')
            ->setParameter('userId', $userId)
            ->orderBy('r.created_at', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
