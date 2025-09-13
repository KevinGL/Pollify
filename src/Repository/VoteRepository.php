<?php

namespace App\Repository;

use App\Entity\Poll;
use App\Entity\Vote;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Vote>
 */
class VoteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Vote::class);
    }

    //    /**
    //     * @return Vote[] Returns an array of Vote objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('v')
    //            ->andWhere('v.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('v.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Vote
    //    {
    //        return $this->createQueryBuilder('v')
    //            ->andWhere('v.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    public function findByUserPoll($user, $poll): ?Vote
    {
        return $this->createQueryBuilder("v")
            ->where("v.user = :user")
            ->andWhere("v.poll = :poll")
            ->setParameter("user", $user)
            ->setParameter("poll", $poll)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getChoicesWithVotes(Poll $poll): array
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('c.id AS choice_id, c.title AS choice_title, COUNT(v.id) AS votes_count')
            ->from(\App\Entity\Choice::class, 'c')
            ->leftJoin('c.votes', 'v')
            ->where('c.poll = :poll')
            ->setParameter('poll', $poll)
            ->groupBy('c.id')
            ->orderBy('c.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
