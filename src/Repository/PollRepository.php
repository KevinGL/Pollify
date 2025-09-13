<?php

namespace App\Repository;

use App\Entity\Poll;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Poll>
 */
class PollRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Poll::class);
    }

    //    /**
    //     * @return Poll[] Returns an array of Poll objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Poll
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    public function paginatePolls(int $page, int $limit): array
    {
        return $this->createQueryBuilder("p")
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function getNbPages($limit): int
    {
        $results = $this->createQueryBuilder("r")->getQuery()->getResult();
        
        return count($results) / $limit;
    }
}
