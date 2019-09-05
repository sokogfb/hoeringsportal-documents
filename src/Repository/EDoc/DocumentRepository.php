<?php

/*
 * This file is part of hoeringsportal-sync-files.
 *
 * (c) 2018–2019 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace App\Repository\EDoc;

use App\Entity\Archiver;
use App\Entity\EDoc\Document;
use App\ShareFile\Item;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use ItkDev\Edoc\Entity\Document as EDocDocument;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method null|Document find($id, $lockMode = null, $lockVersion = null)
 * @method null|Document findOneBy(array $criteria, array $orderBy = null)
 * @method Document[]    findAll()
 * @method Document[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DocumentRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Document::class);
    }

    public function created(EDocDocument $document, Item $item, Archiver $archiver)
    {
        $documentIdentifier = $document->DocumentIdentifier;
        $shareFileItemStreamId = $item->streamId;

        $entity = $this->findOneBy([
            'documentIdentifier' => $documentIdentifier,
            'shareFileItemStreamId' => $shareFileItemStreamId,
            'archiver' => $archiver,
        ]);

        if (null === $entity) {
            $entity = (new Document())
                ->setDocumentIdentifier($documentIdentifier)
                ->setShareFileItemStreamId($shareFileItemStreamId)
                ->setArchiver($archiver);
        }

        $entity
            ->setShareFileItemId($item->id)
            ->setData([
                'sharefile' => $item->getData(),
                'edoc' => $document->getData(),
            ])
            ->setUpdatedAt(new \DateTime());

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();

        return $entity;
    }

    public function updated(EDocDocument $document, Item $item, Archiver $archiver)
    {
        $this->created($document, $item, $archiver);
    }
}
