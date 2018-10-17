<?php

/*
 * This file is part of hoeringsportal-sync-files.
 *
 * (c) 2018 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace App\Command\ShareFile2eDoc;

use App\Service\EdocService;
use App\Service\ShareFileService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SyncCommand extends Command
{
    /** @var ShareFileService */
    private $shareFile;

    /** @var EdocService */
    private $edoc;

    public function __construct(ShareFileService $shareFile, EdocService $edoc)
    {
        parent::__construct();
        $this->shareFile = $shareFile;
        $this->edoc = $edoc;
    }

    protected function configure()
    {
        $this
            ->setName('app:sharefile2edoc:sync')
            ->addArgument('since', InputArgument::REQUIRED, 'Check for files updated since this date');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $date = null;

        try {
            $since = $input->getArgument('since');
            $date = $this->getDateTime($since);
        } catch (\Exception $exception) {
            $args = ['Invalid datetime: '.$since];
            if ($output->isVerbose()) {
                $args[] = $exception->getCode();
                $args[] = $exception;
            }

            throw new InvalidArgumentException(...$args);
        }
        $new = $this->getDateTime();

        $shareFileData = $this->shareFile->getUpdatedFiles($date);
        foreach ($shareFileData as $shareFileHearing) {
            $edocHearing = $this->edoc->getHearing($shareFileHearing['Name'], true);
            if (null === $edocHearing) {
                throw new RuntimeException('Error creating hearing: '.$shareFileHearing['Name']);
            }
            foreach ($shareFileHearing['_responses'] as $shareFileResponse) {
                $edocResponse = $this->edoc->getResponse($edocHearing, $shareFileResponse['Name'], true);
                if (null === $edocResponse) {
                    throw new RuntimeException('Error creating response: '.$shareFileResponse['Name']);
                }
                foreach ($shareFileResponse['_files'] as $shareFileFile) {
                    $fileContents = $this->shareFile->downloadFile($shareFileFile);
                    $edocFile = $this->edoc->attachFile($edocResponse, $shareFileFile['Name'], $fileContents);
                    if (null === $edocFile) {
                        throw new RuntimeException('Error attaching file: '.$shareFileFile['Name']);
                    }
                }
            }
        }
    }

    private function getDateTime($time = 'now')
    {
        return new \DateTime($time, new \DateTimeZone('UTC'));
    }
}
