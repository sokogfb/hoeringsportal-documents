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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

class BuildCommand extends Command
{
    protected static $defaultName = 'app:pdf:build';

    /** @var PdfHelper */
    private $helper;

    public function __construct(PdfHelper $pdfHelper, ArchiverRepository $archiverRepository)
    {
        parent::__construct();
        $this->helper = $pdfHelper;
    }

    public function configure()
    {
        $this->addArgument('args', InputArgument::REQUIRED | InputArgument::IS_ARRAY);
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->helper->setLogger(new ConsoleLogger($output));

        $args = $input->getArgument('args');
        $cmd = array_shift($args);
        $method = $this->getCommandName($cmd);

        if (!method_exists($this->helper, $method)) {
            throw new InvalidArgumentException('Invalid command: '.$cmd);
        }
        $result = \call_user_func_array([$this->helper, $method], $args);

        if ($output->isDebug()) {
            $output->writeln(json_encode([$cmd => $result], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }
    }

    private function getCommandName(string $name)
    {
        return lcfirst(str_replace('-', '', ucwords($name, '-')));
    }
}
