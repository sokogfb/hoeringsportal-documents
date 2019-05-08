<?php

/*
 * This file is part of hoeringsportal-sync-files.
 *
 * (c) 2018–2019 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace App\Command\Edoc;

use App\Command\Command;
use App\Service\EdocService;
use ItkDev\Edoc\Util\ItemListType;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ItemListCommand extends Command
{
    /** @var EdocService */
    private $edoc;

    public function __construct(EdocService $edoc)
    {
        parent::__construct();
        $this->edoc = $edoc;
    }

    public function configure()
    {
        parent::configure();
        $types = implode(', ', ItemListType::getValues());
        $this->setName('app:edoc:item-list')
            ->addArgument('type', InputArgument::REQUIRED, 'The item list type ('.$types.')');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);
        $this->edoc->setArchiver($this->archiver);

        $type = $input->getArgument('type');
        if (!\in_array($type, ItemListType::getValues(), true)) {
            throw new RuntimeException('Invalid item list type: '.$type);
        }

        $items = $this->edoc->getItemList($type);

        $this->writeTable($items);
    }
}
