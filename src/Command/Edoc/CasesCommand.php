<?php

/*
 * This file is part of hoeringsportal-sync-files.
 *
 * (c) 2018 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace App\Command\Edoc;

use App\Command\Command;
use App\Service\EdocService;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CasesCommand extends Command
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
        $this->setName('app:edoc:cases')
            ->addArgument('cmd', InputArgument::REQUIRED, 'The command')
            ->addArgument('args', InputArgument::OPTIONAL | InputArgument::IS_ARRAY, 'The command arguments')
            ->setHelp('Usage: %command.name% -- cmd [arguments...]
        ');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        $cmd = $input->getArgument('cmd');
        $args = $input->getArgument('args');

        if (method_exists($this, $cmd)) {
            $this->{$cmd}($output, ...$args);
        } else {
            /** @var FormatterHelper $formatter */
            $formatter = $this->getHelper('formatter');
            $message = $formatter->formatBlock(['Invalid command: '.$cmd], 'error', true);
            $output->writeln([
                $message,
                '',
                $this->getProcessedHelp(),
            ]);
        }
    }

    private function list(OutputInterface $output)
    {
        $cases = $this->edoc->getCases();

        $this->writeTable($cases, true);
    }
}
