<?php

/*
 * This file is part of hoeringsportal-sync-files.
 *
 * (c) 2018–2019 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace App\Command\Pdf;

use App\Repository\ArchiverRepository;
use App\Service\PdfHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

class CombineCommand extends Command
{
    protected static $defaultName = 'app:pdf:combine';

    /** @var PdfHelper */
    private $helper;

    public function __construct(PdfHelper $pdfHelper, ArchiverRepository $archiverRepository)
    {
        parent::__construct();
        $this->helper = $pdfHelper;
        $this->archiverRepository = $archiverRepository;
    }

    public function configure()
    {
        $this
            ->addArgument('action', InputArgument::REQUIRED)
            ->addArgument('hearing', InputArgument::REQUIRED)
            ->addOption('archiver', null, InputOption::VALUE_REQUIRED);
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->helper->setLogger(new ConsoleLogger($output));

        $action = $input->getArgument('action');
        $hearing = $input->getArgument('hearing');
        $method = $this->getCommandName($action);

        if ($archiverId = $input->getOption('archiver')) {
            $archiver = $this->archiverRepository->findOneByNameOrId($archiverId);
            if (null === $archiver) {
                throw new RuntimeException('Invalid archiver: '.$archiverId);
            }
            $this->helper->setArchiver($archiver);
        }

        if (!method_exists($this->helper, $method)) {
            throw new InvalidArgumentException('Invalid command: '.$action);
        }

        $result = \call_user_func_array([$this->helper, $method], [$hearing]);

        if ($output->isDebug()) {
            $output->writeln(json_encode([$action => $result], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }
    }

    private function getCommandName(string $name)
    {
        return lcfirst(str_replace('-', '', ucwords($name, '-')));
    }
}
