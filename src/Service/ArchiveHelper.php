<?php

/*
 * This file is part of hoeringsportal-sync-files.
 *
 * (c) 2018 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace App\Service;

use App\Entity\Archiver;
use App\Exception\RuntimeException;
use App\Repository\EDoc\CaseFileRepository;
use ItkDev\Edoc\Entity\ArchiveFormat;
use Psr\Log\LoggerInterface;

class ArchiveHelper
{
    /** @var ShareFileService */
    private $shareFile;

    /** @var EdocService */
    private $edoc;

    public function __construct(ShareFileService $shareFile, EdocService $edoc, CaseFileRepository $caseFileRepository)
    {
        $this->shareFile = $shareFile;
        $this->edoc = $edoc;
        $this->caseFileRepository = $caseFileRepository;
    }

    public function archive(Archiver $archiver, LoggerInterface $logger)
    {
        if (!$archiver->isEnabled()) {
            throw new \RuntimeException('Archiver '.$archiver.' is not enabled.');
        }

        $this->shareFile->setArchiver($archiver);
        $this->edoc->setArchiver($archiver);

        try {
            $logger->info('Checking connection to ShareFile');
            $this->shareFile->connect();
        } catch (\Exception $ex) {
            throw new RuntimeException('Cannot connect to ShareFile.', $ex->getCode(), $ex);
        }

        try {
            $logger->info('Checking connection to eDoc');
            $this->edoc->connect();
        } catch (\Exception $ex) {
            throw new RuntimeException('Cannot connect to eDoc.', $ex->getCode(), $ex);
        }

        $date = $archiver->getLastRunAt();

        $logger->info('Getting files updated since '.$date->format(\DateTime::ATOM).' from ShareFile');
        $shareFileData = $this->shareFile->getUpdatedFiles($date);

        foreach ($shareFileData as $shareFileHearing) {
            $edocHearing = $this->edoc->getHearing($shareFileHearing, true);
            if (null === $edocHearing) {
                throw new RuntimeException('Error creating hearing: '.$shareFileHearing['Name']);
            }

            $logger->info($shareFileHearing->name);
            foreach ($shareFileHearing->getChildren() as $shareFileResponse) {
                $edocResponse = $this->edoc->getResponse($edocHearing, $shareFileResponse);
                $logger->info($shareFileResponse->name);
                $logger->info('Getting file contents from ShareFile');
                $fileContents = $this->shareFile->downloadFile($shareFileResponse);
                $fileData = [
                    'ArchiveFormatCode' => ArchiveFormat::ZIP,
                    'DocumentContents' => base64_encode($fileContents),
                ];
                if (null === $edocResponse) {
                    $logger->info('Creating new document in eDoc');
                    $edocResponse = $this->edoc->createResponse($edocHearing, $shareFileResponse, [
                        'DocumentVersion' => $fileData,
                    ]);
                } else {
                    $logger->info('Updating document in eDoc');
                    // @TODO: Check that file actually is newer than the one stored in eDoc.
                    $edocResponse = $this->edoc->updateResponse($edocResponse, $shareFileResponse, $fileData);
                }
                if (null === $edocResponse) {
                    throw new RuntimeException('Error creating response: '.$shareFileResponse['Name']);
                }
            }
        }
    }
}
