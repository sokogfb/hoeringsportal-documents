<?php

/*
 * This file is part of hoeringsportal-sync-files.
 *
 * (c) 2018–2019 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace App\Repository;

use App\Entity\Archiver;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method null|Archiver find($id, $lockMode = null, $lockVersion = null)
 * @method null|Archiver findOneBy(array $criteria, array $orderBy = null)
 * @method Archiver[]    findAll()
 * @method Archiver[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ArchiverRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Archiver::class);
    }

    public function findOneByNameOrId($value): ?Archiver
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.name = :val or s.id = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
