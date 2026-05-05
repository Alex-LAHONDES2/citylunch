<?php

namespace App\Repository;

use App\Entity\Bag;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Bag>
 */
class BagRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Bag::class);
    }

    public function findOneByDeliverer(User $deliverer): ?Bag
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.deliverer = :deliverer')
            ->setParameter('deliverer', $deliverer)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneByDelivererWithItems(User $deliverer): ?Bag
    {
        return $this->createQueryBuilder('b')
            ->addSelect('i', 'p')
            ->leftJoin('b.items', 'i')
            ->leftJoin('i.product', 'p')
            ->andWhere('b.deliverer = :deliverer')
            ->setParameter('deliverer', $deliverer)
            ->getQuery()
            ->getOneOrNullResult();
    }

    //    /**
    //     * @return Bag[] Returns an array of Bag objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Bag
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
