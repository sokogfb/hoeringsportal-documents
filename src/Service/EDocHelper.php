<?php

namespace App\Service;


use App\Repository\EDocLogEntryRepository;

class EDocHelper
{
    private $repository;

    public function __construct(EDocLogEntryRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getCreatedAt($replyId) {
        $item = $this->repository->findOneBy(['replyId' => $replyId], ['createdAt' => 'ASC']);

        return $item ? $item->getCreatedAt() : null;
    }

    public function getUpdatedAt($replyId) {
        $item = $this->repository->findOneBy(['replyId' => $replyId], ['createdAt' => 'ASC']);

        return $item ? $item->getUpdatedAt() : null;
    }
}
