<?php

/*
 * This file is part of hoeringsportal-sync-files.
 *
 * (c) 2018–2019 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace App\Command\ShareFile2eDoc;

use App\Command\Command;
use App\Entity\Archiver;
use App\Service\ArchiveHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

class ArchiveCommand extends Command
{
    protected $archiverType = Archiver::TYPE_SHAREFILE2EDOC;

    /** @var ArchiveHelper */
    private $helper;

    public function __construct(ArchiveHelper $helper)
    {
        parent::__construct();
        $this->helper = $helper;
    }

    protected function configure()
    {
        parent::configure();
        $this->setName('app:sharefile2edoc:archive')
            ->addOption('hearing-item-id', null, InputOption::VALUE_REQUIRED, 'Hearing item id to archive');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        $hearingItemId = $input->getOption('hearing-item-id');
        $logger = new ConsoleLogger($output);
        $this->helper->setLogger($logger);
        $this->helper->archive($this->archiver, $hearingItemId);
    }
}
