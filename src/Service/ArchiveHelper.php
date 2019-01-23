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
use Doctrine\ORM\EntityManagerInterface;
use ItkDev\Edoc\Entity\ArchiveFormat;
use Psr\Log\LoggerInterface;

class ArchiveHelper
{
    /** @var ShareFileService */
    private $shareFile;

    /** @var EdocService */
    private $edoc;

    public function __construct(ShareFileService $shareFile, EdocService $edoc, CaseFileRepository $caseFileRepository, EntityManagerInterface $entityManager)
    {
        $this->shareFile = $shareFile;
        $this->edoc = $edoc;
        $this->caseFileRepository = $caseFileRepository;
        $this->entityManager = $entityManager;
    }

    public function archive(Archiver $archiver, LoggerInterface $logger)
    {
        if (!$archiver->isEnabled()) {
            throw new \RuntimeException('Archiver '.$archiver.' is not enabled.');
        }

        $this->shareFile->setArchiver($archiver);
        $this->edoc->setArchiver($archiver);

//        try {
//            $logger->info('Checking connection to ShareFile');
//            $this->shareFile->connect();
//        } catch (\Exception $ex) {
//            throw new RuntimeException('Cannot connect to ShareFile.', $ex->getCode(), $ex);
//        }

//        try {
//            $logger->info('Checking connection to eDoc');
//            $this->edoc->connect();
//        } catch (\Exception $ex) {
//            throw new RuntimeException('Cannot connect to eDoc.', $ex->getCode(), $ex);
//        }

        $startTime = new \DateTime();
        $date = $archiver->getLastRunAt();

        try {
            $logger->info('Getting files updated since '.$date->format(\DateTime::ATOM).' from ShareFile');

            $shareFileData = $this->shareFile->getUpdatedFiles($date);

            foreach ($shareFileData as $shareFileHearing) {
                $edocHearing = null;

                foreach ($shareFileHearing->getChildren() as $shareFileResponse) {
                    try {
                        if (null === $edocHearing) {
                            if ($archiver->getCreateHearing()) {
                                $logger->info('Creating hearing: '.$shareFileHearing->name);
                                $edocHearing = $this->edoc->getHearing($shareFileHearing, true);
                                if (null === $edocHearing) {
                                    throw new RuntimeException('Error creating hearing: '.$shareFileHearing['Name']);
                                }
                            } else {
                                $logger->info('Getting hearing for response '.$shareFileResponse->name);
                                $edocCaseFileId = $shareFileResponse->metadata['ticket_data']['edoc_case_id'] ?? null;
                                if (null === $edocCaseFileId) {
                                    throw new RuntimeException('Cannot get eDoc Case File Id from item '.$shareFileResponse->name.' ('.$shareFileResponse->id.')');
                                }
                                $edocHearing = $this->edoc->getCaseBySequenceNumber($edocCaseFileId);
                                if (null === $edocHearing) {
                                    throw new RuntimeException('Cannot get eDoc Case File: '.$edocCaseFileId);
                                }
                            }
                        }

                        $logger->info($shareFileResponse->name);
                        $edocResponse = $this->edoc->getResponse($edocHearing, $shareFileResponse);
                        $logger->info('Getting file contents from ShareFile');
                        // @TODO: Decide which file(s) to store in eDoc
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
                    } catch (\Throwable $t) {
                        // @TODO Log exception and send email with exception report.
                    }
                }
            }

            $archiver->setLastRunAt($startTime);
            $this->entityManager->persist($archiver);
            $this->entityManager->flush();
        } catch (\Throwable $t) {
            throw $t;
        }
    }
}
