<?php

namespace App\Repository;

use App\Entity\Candidat;
use App\Entity\Configuration;
use App\Entity\Edition;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Candidat|null find($id, $lockMode = null, $lockVersion = null)
 * @method Candidat|null findOneBy(array $criteria, array $orderBy = null)
 * @method Candidat[]    findAll()
 * @method Candidat[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CandidatRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Candidat::class);
    }

     /**
    //  * @return Candidat[] Returns an array of Candidat objects
    //  */

    public function findByEdition(Edition $edition)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.edition = :val')
            ->setParameter('val', $edition)
            ->orderBy('c.vote', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }
    public function findOneByLast(): ?Candidat
    {
        return $this->createQueryBuilder('s')
            ->setMaxResults(1)
            ->orderBy('s.vote', 'DESC')
            ->getQuery()
            ->getOneOrNullResult()
            ;
    }

    /*
    public function findOneBySomeField($value): ?Candidat
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
