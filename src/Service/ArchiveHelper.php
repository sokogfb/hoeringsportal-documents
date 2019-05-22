<?php

/*
 * This file is part of hoeringsportal-sync-files.
 *
 * (c) 2018–2019 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace App\Service;

use App\Entity\Archiver;
use App\Entity\ExceptionLogEntry;
use App\Exception\RuntimeException;
use App\Repository\EDoc\CaseFileRepository;
use Doctrine\ORM\EntityManagerInterface;
use ItkDev\Edoc\Entity\ArchiveFormat;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerTrait;

class ArchiveHelper extends AbstractArchiveHelper
{
    use LoggerAwareTrait;
    use LoggerTrait;

    /**
     * {@inheritdoc}
     */
    protected $archiverType = 'sharefile2edoc';

    /** @var ShareFileService */
    private $shareFile;

    /** @var EdocService */
    private $edoc;

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
        $this->entityManager = $entityManager;
        $this->mailer = $mailer;
    }

    public function archive(Archiver $archiver, $hearingItemId = null)
    {
        $this->archiver = $archiver;

        try {
            if (!$archiver->isEnabled()) {
                throw new \RuntimeException('Archiver '.$archiver.' is not enabled.');
            }

            if ($archiver->getType() !== $this->archiverType) {
                throw new \RuntimeException('Cannot handle archiver with type'.$archiver->getType());
            }

            $this->shareFile->setArchiver($archiver);
            $this->edoc->setArchiver($archiver);

            $startTime = new \DateTime();
            $date = null;

            // @FIXME: Getting data from ShareFile should be moved into a service/helper.
            if (null !== $hearingItemId) {
                $this->info('Getting hearing '.$hearingItemId);
                $hearing = $this->shareFile->getHearing($hearingItemId);
                $shareFileData = [$hearing];
            } else {
                $date = $archiver->getLastRunAt() ?? new \DateTime('-1 month ago');
                $this->info('Getting files updated since '.$date->format(\DateTime::ATOM).' from ShareFile');
                $shareFileData = $this->shareFile->getUpdatedFiles($date);
            }

            if (empty($shareFileData)) {
                return;
            }

            foreach ($shareFileData as $shareFileHearing) {
                $edocHearing = null;

                foreach ($shareFileHearing->getChildren() as $shareFileResponse) {
                    try {
                        $caseWorker = null;
                        $departmentId = $shareFileResponse->metadata['ticket_data']['department_id'] ?? null;
                        $organisationReference = $archiver->getEdocOrganizationReference($departmentId);
                        if (null === $organisationReference) {
                            throw new RuntimeException('Unknown department: '.$departmentId.' on item '.$shareFileResponse->id);
                        }

                        if (null === $edocHearing) {
                            // $azident = $shareFileResponse->metadata['agent_data']['az'] ?? null;

                            // $caseWorker = $this->edoc->getCaseWorkerByAz($azident);
                            // if (null !== $azident && null === $caseWorker) {
                            //     throw new RuntimeException('Unknown case worker '.$azident.' on item '.$shareFileResponse->id);
                            // }

                            if ($archiver->getCreateHearing()) {
                                $this->info('Getting hearing: '.$shareFileHearing->name);
                                $shareFileHearing->metadata = $shareFileResponse->metadata;

                                $data = [];
                                if (null !== $caseWorker) {
                                    $data['CaseFileManagerReference'] = $caseWorker['CaseWorkerId'];
                                }
                                if (null !== $organisationReference) {
                                    $data['OrganisationReference'] = $organisationReference;
                                }

                                $edocHearing = $this->edoc->getHearing($shareFileHearing, true, $data);
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

                        $sourceFile = null;
                        $sourceFileCreatedAt = null;
                        $sourceFileType = null;
                        $pattern = $this->archiver->getConfigurationValue('[edoc][sharefile_file_name_pattern]');
                        if (null !== $pattern) {
                            $files = $this->shareFile->getFiles($shareFileResponse);
                            foreach ($files as $file) {
                                if (fnmatch($pattern, $file['Name'])) {
                                    $sourceFile = $file;
                                    $sourceFileCreatedAt = $file->creationDate;
                                    $sourceFileType = $this->archiver->getConfigurationValue('[edoc][sharefile_file_type]');
                                }
                            }
                            if (null === $sourceFile) {
                                throw new RuntimeException('Cannot find file matching pattern '.$pattern.' for item '.$shareFileResponse->id);
                            }
                        } else {
                            $sourceFile = $shareFileResponse;
                            $sourceFileCreatedAt = $shareFileResponse->creationDate;
                            $sourceFileType = ArchiveFormat::ZIP;
                        }
                        $fileContents = $this->shareFile->downloadFile($sourceFile);
                        if (null === $fileContents) {
                            throw new RuntimeException('Cannot get file contents for item '.$shareFileResponse->id);
                        }
                        $fileData = [
                            'ArchiveFormatCode' => $sourceFileType,
                            'DocumentContents' => base64_encode($fileContents),
                        ];
                        if (null === $edocResponse) {
                            $this->info('Creating new document in eDoc');

                            $data = [
                                'DocumentVersion' => $fileData,
                            ];
                            if (null !== $caseWorker) {
                                $data['CaseManagerReference'] = $caseWorker['CaseWorkerId'];
                            }
                            if (null !== $organisationReference) {
                                $data['OrganisationReference'] = $organisationReference;
                            }

                            $edocResponse = $this->edoc->createResponse($edocHearing, $shareFileResponse, $data);
                        } else {
                            $documentUpdatedAt = $this->edoc->getDocumentUpdatedAt($edocResponse);
                            if ($documentUpdatedAt < $sourceFileCreatedAt) {
                                $this->info('Updating document in eDoc');
                                $edocResponse = $this->edoc->updateResponse(
                                    $edocResponse,
                                    $shareFileResponse,
                                    $fileData
                                );
                            } else {
                                $this->info('Document in eDoc is already up to date');
                            }
                        }
                        if (null === $edocResponse) {
                            throw new RuntimeException('Error creating response: '.$shareFileResponse['Name']);
                        }
                    } catch (\Throwable $t) {
                        $this->logException($t);
                    }
                }

                // Overview files
                $overviews = [
                    [
                        'pattern' => $this->archiver->getConfigurationValue('[edoc][sharefile_file_combined_name_pattern]', '*-combined.pdf'),
                        'format' => $this->archiver->getConfigurationValue('[edoc][sharefile_file_combined_format]', ArchiveFormat::PDF),
                        'title' => $shareFileHearing->getName().' - samlede høringssvar',
                    ],
                    [
                        'pattern' => $this->archiver->getConfigurationValue('[edoc][sharefile_file_overview_name_pattern]', 'overblik.xlsx'),
                        'format' => $this->archiver->getConfigurationValue('[edoc][sharefile_file_overview_format]', ArchiveFormat::XLSX),
                        'title' => $shareFileHearing->getName().' - overblik over høringssvar',
                    ],
                ];
                foreach ($overviews as $overview) {
                    // We archive the overview before the hearing has been created in eDoc.
                    if (null === $edocHearing) {
                        continue;
                    }

                    $pattern = $overview['pattern'] ?? null;
                    $title = $overview['title'] ?? null;
                    $format = $overview['format'] ?? ArchiveFormat::PDF;

                    try {
                        $this->info(sprintf('Getting overview file "%s" from ShareFile', $title));

                        $edocResponse = $this->edoc->getResponse($edocHearing, $shareFileHearing);
                        $sourceFile = null;
                        $sourceFileCreatedAt = null;
                        $sourceFileType = null;
                        if (null !== $pattern) {
                            $files = $this->shareFile->getFiles($shareFileHearing);
                            foreach ($files as $file) {
                                if (fnmatch($pattern, $file['Name'])) {
                                    $sourceFile = $file;
                                    $sourceFileCreatedAt = $file->creationDate;
                                    $sourceFileType = $format;
                                }
                            }
                        }

                        if (null !== $sourceFile) {
                            $fileContents = $this->shareFile->downloadFile($sourceFile);
                            if (null === $fileContents) {
                                throw new RuntimeException('Cannot get file contents for item '.$sourceFile->id);
                            }
                            $fileData = [
                                'ArchiveFormatCode' => $sourceFileType,
                                'DocumentContents' => base64_encode($fileContents),
                            ];
                            if (null === $edocResponse) {
                                $this->info('Creating new document in eDoc');

                                $data = [
                                    'DocumentVersion' => $fileData,
                                ];
                                if (null !== $caseWorker) {
                                    $data['CaseManagerReference'] = $caseWorker['CaseWorkerId'];
                                }
                                if (null !== $organisationReference) {
                                    $data['OrganisationReference'] = $organisationReference;
                                }

                                $data['TitleText'] = $title;

                                $edocResponse = $this->edoc->createResponse($edocHearing, $shareFileHearing, $data);
                            } else {
                                $documentUpdatedAt = $this->edoc->getDocumentUpdatedAt($edocResponse);
                                if ($documentUpdatedAt < $sourceFileCreatedAt) {
                                    $this->info('Updating document in eDoc');
                                    $edocResponse = $this->edoc->updateResponse(
                                        $edocResponse,
                                        $shareFileHearing,
                                        $fileData
                                    );
                                } else {
                                    $this->info('Document in eDoc is already up to date');
                                }
                            }
                            if (null === $edocResponse) {
                                throw new RuntimeException(sprintf('Error creating overview "%s" (%s)', $title, $shareFileHearing['Name']));
                            }
                        }
                    } catch (\Throwable $t) {
                        $this->logException($t);
                    }
                }
            }

            if (null === $hearingItemId) {
                $archiver->setLastRunAt($startTime);
                $this->entityManager->persist($archiver);
                $this->entityManager->flush();
            }
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
