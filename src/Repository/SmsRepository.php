<?php

namespace App\Repository;

use App\Entity\Sms;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Sms|null find($id, $lockMode = null, $lockVersion = null)
 * @method Sms|null findOneBy(array $criteria, array $orderBy = null)
 * @method Sms[]    findAll()
 * @method Sms[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SmsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Sms::class);
    }

    /**
     * @return Sms[] Returns an array of Sms objects
     */
    public function findByAll($size)
    {
        return $this->createQueryBuilder('s')
            ->orderBy('s.id', 'DESC')
            ->setMaxResults($size)
            ->getQuery()
            ->getResult()
        ;
    }
    public function deleteCascadeTest(){
        $em = $this->getEntityManager();
       // $em->beginTransaction();
        $list=$this->findBy(['recepteur'=>"test"]);

        foreach ($list as $sms){
            $em->remove($sms);
        }
        $em->flush();
    }
    /*
    public function findOneBySomeField($value): ?Sms
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
