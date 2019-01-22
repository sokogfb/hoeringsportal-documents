<?php

/*
 * This file is part of hoeringsportal-sync-files.
 *
 * (c) 2018 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace App\Command;

use App\Entity\Archiver;
use App\Exception\RuntimeException;
use App\Repository\ArchiverRepository;
use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class Command extends BaseCommand
{
    /** @var InputInterface */
    protected $input;

    /** @var OutputInterface */
    protected $output;

    /** @var ArchiverRepository */
    protected $archiverRepository;

    /** @var Archiver */
    protected $archiver;

    public function setArchiverRepository(ArchiverRepository $archiverRepository)
    {
        $this->archiverRepository = $archiverRepository;
    }

    protected function configure()
    {
        $this->addArgument('archiver', InputArgument::REQUIRED, 'Archiver to run (name or id)');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;

        $archiverId = $input->getArgument('archiver');
        $this->archiver = $this->archiverRepository->findOneByNameOrId($archiverId);

        if (null === $this->archiver) {
            throw new RuntimeException('Invalid archiver: '.$archiverId);
        }
    }

    protected function writeTable($data, $vertical = false)
    {
        $isAssoc = function (array $arr) {
            if ([] === $arr) {
                return false;
            }

            return array_keys($arr) !== range(0, \count($arr) - 1);
        };

        if (!\is_array($data) || $isAssoc($data)) {
            $data = [$data];
        }

        $table = new Table($this->output);
        $rowCount = 0;

        foreach ($data as $item) {
            // Clean up item.
            $item = array_map(function ($value) {
                return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }, json_decode(json_encode($item ?? []), true));

            if ($vertical) {
                if ($rowCount > 0) {
                    $table->addRow(new TableSeparator());
                }
                foreach ($item as $key => $value) {
                    $table->addRow([$key, $value]);
                }
            } else {
                if (0 === $rowCount) {
                    $table->setHeaders(array_keys($item));
                }
                $table->addRow($item);
            }
            ++$rowCount;
        }

        $table->render();
        $this->output->writeln('#rows: '.$rowCount);
    }
}
