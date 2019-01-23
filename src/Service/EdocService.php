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
use App\Repository\EDoc\CaseFileRepository;
use App\Repository\EDoc\DocumentRepository;
use App\ShareFile\Item;
use ItkDev\Edoc\Entity\ArchiveFormat;
use ItkDev\Edoc\Entity\CaseFile;
use ItkDev\Edoc\Entity\Document;
use ItkDev\Edoc\Entity\Entity;
use ItkDev\Edoc\Util\Edoc;
use ItkDev\Edoc\Util\EdocClient;
use ItkDev\Edoc\Util\ItemListType;

class EdocService
{
    /** @var DocumentRepository */
    private $documentRepository;

    /** @var CaseFileRepository */
    private $caseFileRepository;

    /** @var Archiver */
    private $archiver;

    /** @var array */
    private $configuration;

    /** @var EdocClient */
    private $client;

    /** @var Edoc */
    private $edoc;

    public function __construct(CaseFileRepository $caseFileRepository, DocumentRepository $documentRepository)
    {
        $this->caseFileRepository = $caseFileRepository;
        $this->documentRepository = $documentRepository;
    }

    public function __call($name, array $arguments)
    {
        return $this->edoc()->{$name}(...$arguments);
    }

    public function setArchiver(Archiver $archiver)
    {
        $this->archiver = $archiver;
        $this->configuration = $archiver->getConfigurationValue('edoc', []);
        $this->validateConfiguration();
    }

    /**
     * Check that we can connect to ShareFile.
     */
    public function connect()
    {
//        $this->getArchiveFormats();
    }

    public function getHearings()
    {
        return $this->getCases();
    }

    /**
     * Get or create a hearing.
     *
     * @param string $name   the hearing name
     * @param bool   $create if true, a new hearing will be created
     * @param array  $data   additional data for new hearing
     *
     * @return CaseFile
     */
    public function getHearing(Item $item, bool $create = false, array $data = [])
    {
        $caseFile = $this->caseFileRepository->findOneBy([
            'shareFileItemId' => $item->id,
            'archiver' => $this->archiver,
        ]);
        $hearing = $caseFile ? $this->getCaseById($caseFile->getCaseFileIdentifier()) : null;
        if (null !== $hearing || !$create) {
            // @TODO Update hearing.
            return $hearing;
        }

        return $this->createHearing($item, $data);
    }

    /**
     * Create a hearing.
     *
     * @param string $name the hearing name
     * @param array  $data additional data for new hearing
     *
     * @return CaseFile
     */
    public function createHearing(Item $item, array $data = [])
    {
        $name = $this->getCaseFileName($item);
        $data += [
            'TitleText' => $name,
        ];

        if (isset($this->configuration['case_file']['defaults'])) {
            $data += $this->configuration['case_file']['defaults'];
        }

        $caseFile = $this->edoc()->createCaseFile($data);

        $this->caseFileRepository->created($caseFile, $item, $this->archiver);

        return $caseFile;
    }

    /**
     * Get a hearing reponse.
     *
     * @param CaseFile $hearing
     * @param string   $item
     * @param bool     $create  if true, a new response will be created
     * @param array    $data    additional data for new response
     *
     * @return Document
     */
    public function getResponse(CaseFile $hearing, Item $item, bool $create = false, array $data = [])
    {
//        $document = $this->getDocumentByName($hearing, $item->name);
//        if (null !== $document || !$create) {
//            return $document;
//        }

        $document = $this->documentRepository->findOneBy([
            'shareFileItemId' => $item->id,
            'archiver' => $this->archiver,
        ]);

        $response = $document ? $this->getDocumentById($document->getDocumentIdentifier()) : null;
        if (null !== $response || !$create) {
            // @TODO Update response
            return $response;
        }

        return $this->createResponse($hearing, $item, $data);
    }

    /**
     * Create a hearing response.
     *
     * @param string $item the response name
     * @param array  $data data for new response
     *
     * @return Document
     */
    public function createResponse(CaseFile $hearing, Item $item, array $data)
    {
        $name = $this->getResponseName($item);
        $data += [
            'TitleText' => $name,
        ];

        if (isset($this->configuration['document']['defaults'])) {
            $data += $this->configuration['document']['defaults'];
        }

        $response = $this->edoc()->createDocumentAndDocumentVersion($hearing, $data);

        $this->documentRepository->created($response, $item, $this->archiver);

        return $response;
    }

    public function updateResponse(Document $response, Item $item, array $data)
    {
        $result = $this->edoc()->createDocumentVersion($response, $data);

        $this->documentRepository->updated($response, $item, $this->archiver);

        return $result;
    }

    /**
     * Attach a file to a document.
     *
     * @param Document $document
     * @param string   $name
     * @param $contents
     */
    public function attachFile(Document $document, string $name, $contents)
    {
        $this->edoc()->attachFile($document, $name, $contents);
    }

    public function getAttachments($documentId)
    {
        return $this->edoc()->getAttachments(['DocumentIdentifier' => $documentId]);
    }

    /**
     * @return ArchiveFormat[]
     */
    public function getArchiveFormats()
    {
        $result = $this->edoc()->getArchiveFormats();

        return $result;
    }

    /**
     * @param string $type mimetype or filename extension
     *
     * @return null|ArchiveFormat
     */
    public function getArchiveFormat(string $type)
    {
        $formats = $this->getArchiveFormats();

        foreach ($formats as $format) {
            if ($format->Mimetype === $type || 0 === strcasecmp($format->FileExtension, $type)) {
                return $format;
            }
        }

        return null;
    }

    /**
     * @return Entity[]
     */
    public function getCaseTypes()
    {
        $result = $this->edoc()->getItemList(ItemListType::CASE_TYPE);

        return $result;
    }

    /**
     * @param array $criteria
     *
     * @return array|CaseFile[]
     */
    public function getCases(array $criteria = [])
    {
        $criteria += [
            'Project' => $this->configuration['project_id'],
        ];

        return $this->edoc()->searchCaseFile($criteria);
    }

    public function getCaseById(string $id)
    {
        $result = $this->getCases(['CaseFileIdentifier' => $id]);

        return 1 === \count($result) ? reset($result) : null;
    }

    public function getCaseByName(string $name)
    {
        $result = $this->getCases(['TitleText' => $name]);

        return 1 === \count($result) ? reset($result) : null;
    }

    public function getDocuments(array $case)
    {
        $result = $this->edoc()->searchDocument($case);

        return $result;
    }

    public function getDocumentById(string $id)
    {
        $result = $this->edoc()->searchDocument(['DocumentIdentifier' => $id]);

        return 1 === \count($result) ? reset($result) : null;
    }

    public function getDocumentByName(CaseFile $case, string $name)
    {
        $result = $this->edoc()->searchDocument([
            'CaseFileReference' => $case->CaseFileIdentifier,
            'TitleText' => $name,
        ]);

        return 1 === \count($result) ? reset($result) : null;
    }

    private function getCaseFileName(Item $item)
    {
        return $item->name;
    }

    private function getResponseName(Item $item)
    {
        return $item->name;
    }

    private function validateConfiguration()
    {
        // @HACK
        if (null === $this->configuration) {
            return;
        }

        $requiredFields = ['ws_url', 'ws_username', 'ws_password', 'user_identifier'];

        foreach ($requiredFields as $field) {
            if (!isset($this->configuration[$field])) {
                throw new \RuntimeException('Configuration value "'.$field.'" missing.');
            }
        }
    }

    private function edoc()
    {
        if (null === $this->edoc) {
            $this->edoc = new Edoc($this->client(), $this->configuration['user_identifier']);
        }

        return $this->edoc;
    }

    private function client()
    {
        if (null === $this->client) {
            $this->client = new EdocClient(null, [
                'location' => $this->configuration['ws_url'],
                'username' => $this->configuration['ws_username'],
                'password' => $this->configuration['ws_password'],
                //            'trace' => true,
            ]);
        }

        return $this->client;
    }
}