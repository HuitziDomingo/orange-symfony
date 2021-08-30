<?php

namespace App\Repository;

use App\Entity\UserMembership;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method UserMembership|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserMembership|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserMembership[]    findAll()
 * @method UserMembership[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserMembershipRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserMembership::class);
    }

    // /**
    //  * @return UserMembership[] Returns an array of UserMembership objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('u.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?UserMembership
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
