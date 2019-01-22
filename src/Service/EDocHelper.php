<?php

/*
 * This file is part of hoeringsportal-sync-files.
 *
 * (c) 2018 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace App\Service;

use App\Repository\EDocLogEntryRepository;

class EDocHelper
{
    private $repository;

    public function __construct(EDocLogEntryRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getCreatedAt($replyId)
    {
        $item = $this->repository->findOneBy(['replyId' => $replyId], ['createdAt' => 'ASC']);

        return $item ? $item->getCreatedAt() : null;
    }

    public function getUpdatedAt($replyId)
    {
        $item = $this->repository->findOneBy(['replyId' => $replyId], ['createdAt' => 'ASC']);

        return $item ? $item->getUpdatedAt() : null;
    }
}
