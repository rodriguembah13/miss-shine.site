<?php

namespace App\Repository;

use App\Entity\ModelSms;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ModelSms|null find($id, $lockMode = null, $lockVersion = null)
 * @method ModelSms|null findOneBy(array $criteria, array $orderBy = null)
 * @method ModelSms[]    findAll()
 * @method ModelSms[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ModelSmsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ModelSms::class);
    }

    // /**
    //  * @return ModelSms[] Returns an array of ModelSms objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('m.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?ModelSms
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
