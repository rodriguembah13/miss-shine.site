<?php

namespace App\Repository;

use App\Entity\Vote;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Vote|null find($id, $lockMode = null, $lockVersion = null)
 * @method Vote|null findOneBy(array $criteria, array $orderBy = null)
 * @method Vote[]    findAll()
 * @method Vote[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class VoteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Vote::class);
    }

    public function getVoteACCEPTED(){
        $vote=0;
       $votes=$this->findBy(['status'=>"ACCEPTED"]);
       foreach ($votes as $vote_){
           $vote+=$vote_->getNombreVote();
       }
       return $vote;
    }
    public function findOneByLast()
    {
        return $this->createQueryBuilder('s')
            ->setMaxResults(1)
            ->orderBy('s.id', 'DESC')
            ->getQuery()
            ->getOneOrNullResult()
            ;
    }
    public function findByDay($datebegin,$date_end,$id)
    {
        $value=date('Y-m-d');
        $value2=date('Y-m-d');
        $date = date_create($value2);
        date_add($date, date_interval_create_from_date_string("1 days"));
        return $this->createQueryBuilder('f')
            ->andWhere('f.createdAt BETWEEN :dateBegin AND :dateEnd')
            ->andWhere('f.candidat = :c')
            ->setParameter('dateBegin', $datebegin)
            ->setParameter('dateEnd', $date_end)
            ->setParameter('c',$id)
            ->orderBy('f.fixtureid', 'DESC')
            ->getQuery()
            ->getResult()
            ;
    }
}
