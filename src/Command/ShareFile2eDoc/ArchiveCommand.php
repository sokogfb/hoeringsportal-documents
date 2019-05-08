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
use App\Service\ArchiveHelper;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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
        $this->setName('app:sharefile2edoc:archive')
            ->addOption('hearing-item-id', null, InputOption::VALUE_REQUIRED, 'Hearing item id to archive')
            ->addOption('last-run-at', null, InputOption::VALUE_REQUIRED, 'Use this time as value of Archiver.lastRunAt');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $hearingItemId = $input->getOption('hearing-item-id');
        parent::execute($input, $output);

        if ($lastRunAt = $input->getOption('last-run-at')) {
            try {
                $this->archiver->setLastRunAt(new \DateTime($lastRunAt));
            } catch (\Exception $ex) {
                throw new RuntimeException('Invalid last-run-at value: '.$lastRunAt);
            }
        }

        $logger = new ConsoleLogger($output);
        $this->helper->setLogger($logger);
        $this->helper->archive($this->archiver, $hearingItemId);
    }
}
