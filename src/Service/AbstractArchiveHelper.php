<?php

/*
 * This file is part of hoeringsportal-sync-files.
 *
 * (c) 2018–2019 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace App\Service;

abstract class AbstractArchiveHelper
{
    /**
     * The archiver type this ArchiveHelper can handle.
     *
     * @var string
     */
    protected $archiverType = '';
}
