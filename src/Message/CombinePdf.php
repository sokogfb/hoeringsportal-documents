<?php

/*
 * This file is part of hoeringsportal-sync-files.
 *
 * (c) 2018–2019 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace App\Message;

use App\Entity\Archiver;

class CombinePdf
{
    /** @var string */
    private $archiverId;

    /** @var string */
    private $hearingId;

    /** @var string */
    private $loggerFilename;

    public function __construct(string $archiverId, string $hearingId, string $loggerFilename)
    {
        $this->archiverId = $archiverId;
        $this->hearingId = $hearingId;
        $this->loggerFilename = $loggerFilename;
    }

    /**
     * @return Archiver
     */
    public function getArchiverId(): string
    {
        return $this->archiverId;
    }

    /**
     * @return CombinePdf
     */
    public function setArchiverId(string $archiverId): self
    {
        $this->archiverId = $archiverId;

        return $this;
    }

    public function getHearingId(): string
    {
        return $this->hearingId;
    }

    /**
     * @return CombinePdf
     */
    public function setHearingId(string $hearingId): self
    {
        $this->hearingId = $hearingId;

        return $this;
    }

    public function getLoggerFilename(): string
    {
        return $this->loggerFilename;
    }

    /**
     * @return CombinePdf
     */
    public function setLoggerFilename(string $loggerFilename): self
    {
        $this->loggerFilename = $loggerFilename;

        return $this;
    }
}
