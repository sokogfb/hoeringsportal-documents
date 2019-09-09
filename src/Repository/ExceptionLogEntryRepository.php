<?php

/*
 * This file is part of hoeringsportal-sync-files.
 *
 * (c) 2018–2019 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace App\Repository;

use App\Entity\ExceptionLogEntry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method null|ExceptionLogEntry find($id, $lockMode = null, $lockVersion = null)
 * @method null|ExceptionLogEntry findOneBy(array $criteria, array $orderBy = null)
 * @method ExceptionLogEntry[]    findAll()
 * @method ExceptionLogEntry[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ExceptionLogEntryRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, ExceptionLogEntry::class);
    }
}
