<?php

namespace App\Repository;

use App\Entity\EDocLogEntry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method EDocLogEntry|null find($id, $lockMode = null, $lockVersion = null)
 * @method EDocLogEntry|null findOneBy(array $criteria, array $orderBy = null)
 * @method EDocLogEntry[]    findAll()
 * @method EDocLogEntry[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EDocLogEntryRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, EDocLogEntry::class);
    }

    // /**
    //  * @return EDocLogEntry[] Returns an array of EDocLogEntry objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('e.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?EDocLogEntry
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
