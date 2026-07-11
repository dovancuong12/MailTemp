<?php

namespace App\Repository;

use App\Entity\TemporaryEmailBox;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TemporaryEmailBox>
 */
class TemporaryEmailBoxRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TemporaryEmailBox::class);
    }

    public function findOlderThan(\DateTimeImmutable $olderThan): array
    {
        // Keep a mailbox alive as long as it is actively used: base retention on
        // the last access time, falling back to creation time when it was never
        // opened. This stops actively-used inboxes from being reset.
        return $this->createQueryBuilder('t')
            ->where('COALESCE(t.lastAccessedAt, t.createdAt) < :olderThan')
            ->setParameter('olderThan', $olderThan)
            ->getQuery()
            ->getResult();
    }
}
