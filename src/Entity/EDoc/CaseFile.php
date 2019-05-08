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
 * @ORM\Entity(repositoryClass="App\Repository\EDoc\CaseFileRepository")
 * @ORM\Table(name="edoc_case_file", indexes={@ORM\Index(name="casefile_idx", columns={"case_file_identifier", "share_file_item_id", "archiver_id"})})
 */
class CaseFile
{
    use TimestampableEntity;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="UUID")
     * @ORM\Column(type="string")
     */
    private $id;

    /**
     * The eDoc case file identifier.
     *
     * @ORM\Column(type="string", length=255)
     */
    private $caseFileIdentifier;

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

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getCaseFileIdentifier(): ?string
    {
        return $this->caseFileIdentifier;
    }

    public function setCaseFileIdentifier(string $caseFileIdentifier): self
    {
        $this->caseFileIdentifier = $caseFileIdentifier;

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
}
