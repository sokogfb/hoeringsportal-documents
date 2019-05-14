<?php

/*
 * This file is part of hoeringsportal-sync-files.
 *
 * (c) 2018–2019 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace App\Util;

use App\Entity\Archiver;

trait ArchiverAwareTrait
{
    /**
     * The archiver instance.
     *
     * @var Archiver
     */
    protected $archiver;

    /**
     * Sets the archiver.
     *
     * @param Archiver $archiver
     */
    public function setArchiver(Archiver $archiver)
    {
        $this->archiver = $archiver;
    }

    /**
     * Gets the archiver.
     *
     * @return Archiver
     */
    public function getArchiver()
    {
        return $this->archiver;
    }
}
