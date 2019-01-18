<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use ApiPlatform\Core\Annotation\ApiResource;

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
}
