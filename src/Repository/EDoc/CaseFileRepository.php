<?php

/*
 * This file is part of hoeringsportal-sync-files.
 *
 * (c) 2018 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace App\Repository\EDoc;

use App\Entity\Archiver;
use App\Entity\EDoc\CaseFile;
use App\ShareFile\Item;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use ItkDev\Edoc\Entity\CaseFile as EDocCaseFile;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method null|CaseFile find($id, $lockMode = null, $lockVersion = null)
 * @method null|CaseFile findOneBy(array $criteria, array $orderBy = null)
 * @method CaseFile[]    findAll()
 * @method CaseFile[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CaseFileRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, CaseFile::class);
    }

    public function created(EDocCaseFile $caseFile, Item $item, Archiver $archiver)
    {
        $entity = $this->findOneBy([
            'caseFileIdentifier' => $caseFile->CaseFileIdentifier,
            'shareFileItemId' => $item->id,
            'archiver' => $archiver,
        ]);

        if (null === $entity) {
            $entity = (new CaseFile())
                ->setCaseFileIdentifier($caseFile->CaseFileIdentifier)
                ->setShareFileItemId($item->id)
                ->setArchiver($archiver);
        }

        $entity
            ->setData([
                'sharefile' => $item->getData(),
                'edoc' => $caseFile->getData(),
            ])
            ->setUpdatedAt(new \DateTime());

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();

        return $entity;
    }

    // /**
    //  * @return Document[] Returns an array of Document objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('d.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Document
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
