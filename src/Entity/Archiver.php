<?php

/*
 * This file is part of hoeringsportal-sync-files.
 *
 * (c) 2018 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace App\Entity;

use App\Entity\EDoc\CaseFile;
use App\Entity\EDoc\Document;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Loggable\Loggable;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Yaml\Yaml;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ArchiverRepository")
 * @Gedmo\Loggable()
 * @UniqueEntity("name")
 */
class Archiver implements Loggable
{
    use TimestampableEntity;

    /**
     * @ORM\Id()
     * @ORM\Column(type="guid")
     * @ORM\GeneratedValue(strategy="UUID")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Gedmo\Versioned()
     */
    private $name;

    /**
     * @ORM\Column(type="text")
     * @Gedmo\Versioned()
     */
    private $configuration;

    /**
     * @ORM\Column(type="boolean")
     * @Gedmo\Versioned()
     */
    private $enabled;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $lastRunAt;

    // /**
    //  * @ORM\OneToMany(targetEntity="App\Entity\EDocLogEntry", mappedBy="archiver", orphanRemoval=true)
    //  */
    // private $eDocLogEntries;

    public function __construct()
    {
        $this->eDocLogEntries = new ArrayCollection();
    }

    // /**
    //  * @return Collection|EDocLogEntry[]
    //  */
    // public function getEDocLogEntries(): Collection
    // {
    //     return $this->eDocLogEntries;
    // }

    // public function addEDocLogEntry(EDocLogEntry $eDocLogEntry): self
    // {
    //     if (!$this->eDocLogEntries->contains($eDocLogEntry)) {
    //         $this->eDocLogEntries[] = $eDocLogEntry;
    //         $eDocLogEntry->setArchiver($this);
    //     }

    //     return $this;
    // }

    // public function removeEDocLogEntry(EDocLogEntry $eDocLogEntry): self
    // {
    //     if ($this->eDocLogEntries->contains($eDocLogEntry)) {
    //         $this->eDocLogEntries->removeElement($eDocLogEntry);
    //         // set the owning side to null (unless already changed)
    //         if ($eDocLogEntry->getArchiver() === $this) {
    //             $eDocLogEntry->setArchiver(null);
    //         }
    //     }

    //     return $this;
    // }

    public function __toString()
    {
        return $this->name ?? self::class;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getConfiguration(): ?string
    {
        return $this->configuration;
    }

    public function setConfiguration(string $configuration): self
    {
        $this->configuration = $configuration;

        return $this;
    }

    public function isEnabled(): ?bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function getLastRunAt(): ?\DateTimeInterface
    {
        return $this->lastRunAt;
    }

    public function setLastRunAt(?\DateTimeInterface $lastRunAt): self
    {
        $this->lastRunAt = $lastRunAt;

        return $this;
    }

    public function getConfigurationValue(string $key = null, $default = null)
    {
        $configuration = Yaml::parse($this->getConfiguration());

        return $key ? ($configuration[$key] ?? $default) : $configuration;
    }

    public function getCreateHearing(): bool
    {
        $value = $this->getConfigurationValue('edoc');

        return isset($value['project_id']);
    }

    public function getEdocCaseFileUrl(CaseFile $caseFile)
    {
        $data = $caseFile->getData()['edoc'];
        $baseUrl = $this->getConfigurationValue('edoc')['admin_url'] ?? '';

        return $baseUrl
            .'/locator.aspx?'
            .http_build_query([
                'name' => 'DMS.Case.Details.Simplified.300001',
                'module' => 'Case',
                'subtype' => 300001, // ???
                'recno' => $data['RecordIdentifier'] ?? null,
            ]);
    }

    public function getEdocDocumentUrl(Document $document)
    {
        $data = $document->getData()['edoc'];
        $baseUrl = $this->getConfigurationValue('edoc')['admin_url'] ?? '';

        return $baseUrl
            .'/locator.aspx?'
            .http_build_query([
                'name' => 'DMS.Document.Details.Simplified.2',
                'module' => 'Document',
                'subtype' => 2, // ???
                'recno' => $data['RecordIdentifier'],
            ]);
    }
}
