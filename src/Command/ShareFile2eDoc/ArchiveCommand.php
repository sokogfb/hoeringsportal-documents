<?php

/*
 * This file is part of hoeringsportal-sync-files.
 *
 * (c) 2018 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace App\Command\ShareFile2eDoc;

use App\Command\Command;
use App\Service\ArchiveHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

class ArchiveCommand extends Command
{
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
        $this->setName('app:sharefile2edoc:archive');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        $logger = new ConsoleLogger($output);
        $this->helper->archive($this->archiver, $logger);
    }
}
