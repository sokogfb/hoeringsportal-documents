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
use App\Entity\ExceptionLogEntry;
use App\Entity\Log;
use App\Exception\RuntimeException;
use App\Repository\EDoc\CaseFileRepository;
use Doctrine\ORM\EntityManagerInterface;
use ItkDev\Edoc\Entity\ArchiveFormat;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerTrait;

class ArchiveHelper
{
    use LoggerAwareTrait;
    use LoggerTrait;

    /** @var ShareFileService */
    private $shareFile;

    /** @var EdocService */
    private $edoc;

    /** @var CaseFileRepository */
    private $caseFileRepository;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var \Swift_Mailer */
    private $mailer;

    /** @var Archiver */
    private $archiver;

    public function __construct(ShareFileService $shareFile, EdocService $edoc, CaseFileRepository $caseFileRepository, EntityManagerInterface $entityManager, \Swift_Mailer $mailer)
    {
        $this->shareFile = $shareFile;
        $this->edoc = $edoc;
        $this->caseFileRepository = $caseFileRepository;
        $this->entityManager = $entityManager;
        $this->mailer = $mailer;
    }

    public function archive(Archiver $archiver)
    {
        $this->archiver = $archiver;

        try {
            if (!$archiver->isEnabled()) {
                throw new \RuntimeException('Archiver '.$archiver.' is not enabled.');
            }

            $this->shareFile->setArchiver($archiver);
            $this->edoc->setArchiver($archiver);

            $startTime = new \DateTime();
            $date = $archiver->getLastRunAt();

            $this->info('Getting files updated since '.$date->format(\DateTime::ATOM).' from ShareFile');

            $shareFileData = $this->shareFile->getUpdatedFiles($date);

            foreach ($shareFileData as $shareFileHearing) {
                $edocHearing = null;

                foreach ($shareFileHearing->getChildren() as $shareFileResponse) {
                    try {
                        if (null === $edocHearing) {
                            if ($archiver->getCreateHearing()) {
                                $this->info('Creating hearing: '.$shareFileHearing->name);
                                $shareFileHearing->metadata = $shareFileResponse->metadata;
                                $edocHearing = $this->edoc->getHearing($shareFileHearing, true);
                                if (null === $edocHearing) {
                                    throw new RuntimeException('Error creating hearing: '.$shareFileHearing['Name']);
                                }
                            } else {
                                $this->info('Getting hearing for response '.$shareFileResponse->name);
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

                        $this->info($shareFileResponse->name);
                        $edocResponse = $this->edoc->getResponse($edocHearing, $shareFileResponse);
                        $this->info('Getting file contents from ShareFile');
                        // @TODO: Decide which file(s) to store in eDoc
                        $fileContents = $this->shareFile->downloadFile($shareFileResponse);
                        $fileData = [
                            'ArchiveFormatCode' => ArchiveFormat::ZIP,
                            'DocumentContents' => base64_encode($fileContents),
                        ];
                        if (null === $edocResponse) {
                            $this->info('Creating new document in eDoc');
                            $edocResponse = $this->edoc->createResponse($edocHearing, $shareFileResponse, [
                                'DocumentVersion' => $fileData,
                            ]);
                        } else {
                            $this->info('Updating document in eDoc');
                            // @TODO: Check that file actually is newer than the one stored in eDoc.
                            $edocResponse = $this->edoc->updateResponse($edocResponse, $shareFileResponse, $fileData);
                        }
                        if (null === $edocResponse) {
                            throw new RuntimeException('Error creating response: '.$shareFileResponse['Name']);
                        }
                    } catch (\Throwable $t) {
                        $this->logException($t);
                    }
                }
            }

            $archiver->setLastRunAt($startTime);
            $this->entityManager->persist($archiver);
            $this->entityManager->flush();
        } catch (\Throwable $t) {
            $this->logException($t);
        }
    }

    public function log($level, $message, array $context = [])
    {
        if (null !== $this->logger) {
            $this->logger->log($level, $message, $context);
        }
    }

    private function logException(\Throwable $t)
    {
        $this->emergency($t->getMessage());
        $logEntry = new ExceptionLogEntry($t);
        $this->entityManager->persist($logEntry);
        $this->entityManager->flush();

        if (null !== $this->archiver) {
            $config = $this->archiver->getConfigurationValue('[notifications][email]');

            $message = (new \Swift_Message($t->getMessage()))
                ->setFrom($config['from'])
                ->setTo($config['to'])
                ->setBody(
                    $t->getTraceAsString(),
                    'text/plain'
                );

            $this->mailer->send($message);
        }
    }
}
