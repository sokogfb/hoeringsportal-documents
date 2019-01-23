<?php

/*
 * This file is part of hoeringsportal-sync-files.
 *
 * (c) 2018 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace App\ShareFile;

class Item extends Entity
{
    /**
     * @var string
     */
    public $id;

    public $name;

    public $progenyEditDate;

    public $creationDate;

    public $metadata;

    /**
     * @var Item[]
     */
    protected $children;

    public function setChildren(array $children)
    {
        $this->children = $children;

        return $this;
    }

    /**
     * @return Item[]
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    protected function build(array $data)
    {
        parent::build($data);
        $this->id = $data['Id'];
        $this->name = $data['Name'];
        $this->progenyEditDate = $data['ProgenyEditDate'] ?? null;
        $this->creationDate = $data['CreationDate'];
        $this->metadata = $data['_metadata'] ?? null;
    }
}
