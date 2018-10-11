<?php

/*
 * This file is part of hoeringsportal-sync-files.
 *
 * (c) 2018 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace App\Command;

use App\Service\EdocService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class EdocCommand extends Command
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
        $this->setName('app:edoc')
            ->addArgument('cmd');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $cmd = $input->getArgument('cmd');
        if (method_exists($this->edoc, $cmd)) {
            $args = [1, 2, 3];
            $result = \call_user_func([$this->edoc, $cmd], ...$args);
            header('Content-type: text/plain');
            echo var_export($result, true);
            die(__FILE__.':'.__LINE__.':'.__METHOD__);
        }
    }
}
