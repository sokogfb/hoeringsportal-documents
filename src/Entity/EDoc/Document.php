<?php

/*
 * This file is part of hoeringsportal-sync-files.
 *
 * (c) 2018–2019 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace App\Entity\EDoc;

use App\Entity\Archiver;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * @ORM\Entity(repositoryClass="App\Repository\EDoc\DocumentRepository")
 * @ORM\Table(name="edoc_document")
 */
class Document
{
    use TimestampableEntity;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="UUID")
     * @ORM\Column(type="string")
     */
    private $id;

    /**
     * The eDoc document id.
     *
     * @ORM\Column(type="string", length=255)
     */
    private $documentIdentifier;

    /**
     * @ORM\Column(type="json")
     */
    private $data = [];

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Archiver")
     * @ORM\JoinColumn(nullable=false)
     */
    private $archiver;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $shareFileItemId;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $shareFileItemStreamId;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getDocumentIdentifier(): ?string
    {
        return $this->documentIdentifier;
    }

    public function setDocumentIdentifier(string $documentIdentifier): self
    {
        $this->documentIdentifier = $documentIdentifier;

        return $this;
    }

    public function getData(): ?array
    {
        return $this->data;
    }

    public function setData(array $data): self
    {
        $this->data = $data;

        return $this;
    }

    public function getArchiver(): ?Archiver
    {
        return $this->archiver;
    }

    public function setArchiver(?Archiver $archiver): self
    {
        $this->archiver = $archiver;

        return $this;
    }

    public function getShareFileItemId(): ?string
    {
        return $this->shareFileItemId;
    }

    public function setShareFileItemId(string $shareFileItemId): self
    {
        $this->shareFileItemId = $shareFileItemId;

        return $this;
    }

    public function getShareFileItemStreamId(): ?string
    {
        return $this->shareFileItemStreamId;
    }

    public function setShareFileItemStreamId(string $shareFileItemStreamId): self
    {
        $this->shareFileItemStreamId = $shareFileItemStreamId;

        return $this;
    }
}
