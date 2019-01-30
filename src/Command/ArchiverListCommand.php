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
use App\Repository\ArchiverRepository;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;

class ArchiverListCommand extends Command
{
    /** @var ArchiverRepository */
    private $repository;

    public function __construct(ArchiverRepository $repository)
    {
        parent::__construct();
        $this->repository = $repository;
    }

    public function configure()
    {
        $this->setName('app:archiver:list')
            ->addOption('type', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'The types to list')
            ->addOption('field', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'The fields to list')
            ->addOption('enabled', null, InputOption::VALUE_REQUIRED, 'If not set, all archivers will be listed.');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $types = $input->getOption('type');
        $fields = $input->getOption('field');
        $enabled = $input->getOption('enabled');

        if (empty($fields)) {
            $fields = array_map(function (\ReflectionProperty $property) {
                return $property->name;
            }, (new \ReflectionClass(Archiver::class))->getProperties());
        }

        $criteria = [];
        if (!empty($types)) {
            $criteria['type'] = $types;
        }
        if (null !== $enabled) {
            $criteria['enabled'] = \in_array($enabled, ['yes', 1, 'true'], true);
        }

        $archivers = $this->repository->findBy($criteria);

        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        if (1 === \count($fields)) {
            foreach ($archivers as $archiver) {
                foreach ($fields as $field) {
                    $value = $propertyAccessor->getValue($archiver, $field);
                    $output->writeln($value);
                }
            }
        } else {
            $table = new Table($output);
            $first = true;
            foreach ($archivers as $archiver) {
                $values = array_map(function ($field) use ($archiver, $propertyAccessor) {
                    return $propertyAccessor->getValue($archiver, $field);
                }, $fields);

                if ($first) {
                    $table->setHeaders($fields);
                    $first = false;
                }

                $table->addRow($values);
            }
            $table->render();
        }
//        header('Content-type: text/plain'); echo var_export($archivers, true); die(__FILE__.':'.__LINE__.':'.__METHOD__);
//        header('Content-type: text/plain'); echo var_export($types, true); die(__FILE__.':'.__LINE__.':'.__METHOD__);
    }
}
