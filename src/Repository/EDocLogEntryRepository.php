<?php

/*
 * This file is part of hoeringsportal-sync-files.
 *
 * (c) 2018–2019 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace App\Repository;

use App\Entity\EDocLogEntry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method null|EDocLogEntry find($id, $lockMode = null, $lockVersion = null)
 * @method null|EDocLogEntry findOneBy(array $criteria, array $orderBy = null)
 * @method EDocLogEntry[]    findAll()
 * @method EDocLogEntry[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EDocLogEntryRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, EDocLogEntry::class);
    }
}
