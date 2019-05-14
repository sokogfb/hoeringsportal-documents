<?php

/*
 * This file is part of hoeringsportal-sync-files.
 *
 * (c) 2018–2019 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace App\Command\Pdf;

use App\Command\Command;
use App\Entity\Archiver;
use App\Service\PdfHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

class CronCommand extends Command
{
    protected static $defaultName = 'app:pdf:cron';
    protected $archiverType = Archiver::TYPE_PDF_COMBINE;

    /** @var PdfHelper */
    private $helper;

    public function __construct(PdfHelper $pdfHelper)
    {
        parent::__construct();
        $this->helper = $pdfHelper;
    }

    public function configure()
    {
        parent::configure();

        $this->addOption('last-run-at', null, InputOption::VALUE_REQUIRED, 'Use this time as value of Archiver.lastRunAt');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);
        $this->helper->setLogger(new ConsoleLogger($output));
        $this->helper->setArchiver($this->archiver);
        $this->helper->process();
    }
}
