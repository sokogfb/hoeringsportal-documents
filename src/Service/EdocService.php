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
use App\Repository\EDoc\CaseFileRepository;
use App\Repository\EDoc\DocumentRepository;
use App\ShareFile\Item;
use App\Util\TemplateHelper;
use ItkDev\Edoc\Entity\ArchiveFormat;
use ItkDev\Edoc\Entity\CaseFile;
use ItkDev\Edoc\Entity\Document;
use ItkDev\Edoc\Entity\Entity;
use ItkDev\Edoc\Util\Edoc;
use ItkDev\Edoc\Util\EdocClient;
use ItkDev\Edoc\Util\ItemListType;

class EdocService
{
    /** @var CaseFileRepository */
    private $caseFileRepository;

    /** @var DocumentRepository */
    private $documentRepository;

    /** @var TemplateHelper */
    private $template;

    /** @var Archiver */
    private $archiver;

    /** @var array */
    private $configuration;

    /** @var EdocClient */
    private $client;

    /** @var Edoc */
    private $edoc;

    public function __construct(CaseFileRepository $caseFileRepository, DocumentRepository $documentRepository, TemplateHelper $template)
    {
        $this->caseFileRepository = $caseFileRepository;
        $this->documentRepository = $documentRepository;
        $this->template = $template;
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

    public function getDocument(CaseFile $case, Item $item)
    {
        $document = $this->documentRepository->findOneBy([
            'shareFileItemStreamId' => $item->streamId,
            'archiver' => $this->archiver,
        ]);

        return $document ? $this->getDocumentById($document->getDocumentIdentifier()) : null;
    }

    public function createDocument(CaseFile $case, Item $item, array $data = [])
    {
        $name = $item->getName();
        $data += [
            'TitleText' => $name,
        ];

        if (isset($this->configuration['document']['defaults'])) {
            $data += $this->configuration['document']['defaults'];
        }

        $document = $this->edoc()->createDocumentAndDocumentVersion($case, $data);

        $this->documentRepository->created($document, $item, $this->archiver);

        return $document;
    }

    public function updateDocument(Document $document, Item $item, array $data)
    {
        $result = $this->edoc()->createDocumentVersion($document, $data);

        $this->documentRepository->updated($document, $item, $this->archiver);

        return $result;
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
            'shareFileItemStreamId' => $item->streamId,
            'archiver' => $this->archiver,
        ]);

        $hearing = $caseFile ? $this->getCaseById($caseFile->getCaseFileIdentifier()) : null;
        if (null !== $hearing || !$create) {
            // @TODO Update hearing?
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

        if (isset($this->configuration['project_id'])) {
            $data += ['Project' => $this->configuration['project_id']];
        }

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
            'shareFileItemStreamId' => $item->streamId,
            'archiver' => $this->archiver,
        ]);

        $response = $document ? $this->getDocumentById($document->getDocumentIdentifier()) : null;
        if (null !== $response || !$create) {
            // @TODO Update response
            return $response;
        }

        return $this->createResponse($hearing, $item, $data);
    }

    public function getDocumentUpdatedAt(Document $document)
    {
        $document = $this->documentRepository->findOneBy([
            'documentIdentifier' => $document->DocumentIdentifier,
            'archiver' => $this->archiver,
        ]);

        return $document ? $document->getUpdatedAt() : null;
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
        return $this->edoc()->getArchiveFormats();
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
        return $this->edoc()->getItemList(ItemListType::CASE_TYPE);
    }

    /**
     * @param array $criteria
     *
     * @return array|CaseFile[]
     */
    public function getCases(array $criteria = [])
    {
        if (isset($this->configuration['project_id'])) {
            $criteria += [
                'Project' => $this->configuration['project_id'],
            ];
        }

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

    public function getCaseBySequenceNumber(string $number)
    {
        $result = $this->getCases(['SequenceNumber' => $number]);

        return 1 === \count($result) ? reset($result) : null;
    }

    public function getDocuments(array $case)
    {
        return $this->edoc()->searchDocument($case);
    }

    public function getDocumentsBy(array $criteria)
    {
        return $this->edoc()->searchDocument($criteria);
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

    public function getCaseWorkerByAz($az)
    {
        $az = 'adm\\'.$az;
        $result = $this->edoc()->getItemList(
            ItemListType::CASE_WORKER,
            [
                'CaseWorkerAccountName' => $az,
            ]
        );

        return 1 === \count($result) ? reset($result) : null;
    }

    private function getCaseFileName(Item $item)
    {
        $template = $this->configuration['case_file']['name'] ?? '{{ item.name }}';

        return $this->template->render($template, ['item' => ['name' => $item->name] + $item->metadata]);
    }

    private function getResponseName(Item $item)
    {
        $template = $this->configuration['document']['name'] ?? '{{ item.name }}';

        return $this->template->render($template, ['item' => ['name' => $item->name] + $item->metadata]);
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
