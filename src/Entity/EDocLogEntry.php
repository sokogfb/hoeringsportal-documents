<?php

/*
 * This file is part of hoeringsportal-sync-files.
 *
 * (c) 2018 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * @ApiResource(
 *     collectionOperations={"get"},
 *     itemOperations={"get"}
 * )
 * @ORM\Entity(repositoryClass="App\Repository\EDocLogEntryRepository")
 */
class EDocLogEntry
{
    use TimestampableEntity;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $eDocCaseId;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $hearingId;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $replyId;

    /**
     * @ORM\Column(type="json")
     */
    private $data = [];

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Archiver", inversedBy="eDocLogEntries")
     * @ORM\JoinColumn(nullable=false)
     */
    private $archiver;

    public function __toString()
    {
        return self::class;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEDocCaseId(): ?string
    {
        return $this->eDocCaseId;
    }

    public function setEDocCaseId(string $eDocCaseId): self
    {
        $this->eDocCaseId = $eDocCaseId;

        return $this;
    }

    public function getHearingId(): ?string
    {
        return $this->hearingId;
    }

    public function setHearingId(string $hearingId): self
    {
        $this->hearingId = $hearingId;

        return $this;
    }

    public function getReplyId(): ?string
    {
        return $this->replyId;
    }

    public function setReplyId(string $replyId): self
    {
        $this->replyId = $replyId;

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
}
