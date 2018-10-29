<?php

/*
 * This file is part of hoeringsportal-sync-files.
 *
 * (c) 2018 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace App\Service;

use ItkDev\Edoc\Entity\ArchiveFormat;
use ItkDev\Edoc\Entity\CaseFile;
use ItkDev\Edoc\Entity\Document;
use ItkDev\Edoc\Entity\Entity;
use ItkDev\Edoc\Util\Edoc;
use ItkDev\Edoc\Util\EdocClient;
use ItkDev\Edoc\Util\ItemListType;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class EdocService
{
    /** @var array */
    private $configuration;

    private $client;
    private $edoc;

    public function __construct(ParameterBagInterface $parameters)
    {
        $this->configuration = array_filter($parameters->all(), function ($key) {
            return preg_match('/^edoc_/', $key);
        }, ARRAY_FILTER_USE_KEY);
    }

    public function __call($name, array $arguments)
    {
        return $this->edoc()->{$name}(...$arguments);
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
    public function getHearing(string $name, bool $create = false, array $data = [])
    {
        $hearing = $this->getCaseByName($name);
        if (null !== $hearing || !$create) {
            return $hearing;
        }

        return $this->createHearing($name, $data);
    }

    /**
     * Create a hearing.
     *
     * @param string $name the hearing name
     * @param array  $data additional data for new hearing
     *
     * @return CaseFile
     */
    public function createHearing(string $name, array $data = [])
    {
        // Add default (and required) values.
        $data += [
            // getItemList CaseType
            'CaseFileTypeCode' => 300001, // "Administrativsag"
            'CaseFileManagerReference' => 514916, // "adm\\svcedocmkb"
            // getItemList Project
            'Project' => 500089,
            'TitleText' => $name,
            // ???
            'HasPersonrelatedInfo' => false,
            // getItemList HandlingCodeTree
            'HandlingCodeId' => 504063, // "K04 Høringer"
            // getItemList PrimaryCodeTree
            'PrimaryCode' => 500002,
        ];

        return $this->edoc()->createCaseFile($data);
    }

    /**
     * Get a hearing reponse.
     *
     * @param CaseFile $hearing
     * @param string   $name
     * @param bool     $create  if true, a new response will be created
     * @param array    $data    additional data for new response
     *
     * @return Document
     */
    public function getResponse(CaseFile $hearing, string $name, bool $create = false, array $data = [])
    {
        $document = $this->getDocumentByName($hearing, $name);
        if (null !== $document || !$create) {
            return $document;
        }

        return $this->createResponse($hearing, $name, $data);
    }

    /**
     * Create a hearin responseg.
     *
     * @param string $name the response name
     * @param array  $data data for new response
     *
     * @return Document
     */
    public function createResponse(CaseFile $hearing, string $name, array $data)
    {
        // Add default (and required) values.
        $data += [
                    'TitleText' => $name,
                    // getItemList DocumentType
                    'DocumentTypeReference' => 111, // "Udgående dokument"
                    'DocumentCategoryCode' => '',
        ];

        return $this->edoc()->createDocumentAndDocumentVersion($hearing, $data);
    }

    public function updateResponse(Document $response, array $data)
    {
        return $this->edoc()->createDocumentVersion($response, $data);
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
            'Project' => $this->configuration['edoc_project_id'],
        ];

        return $this->edoc()->searchCaseFile($criteria);
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

    public function getDocumentByName(CaseFile $case, string $name)
    {
        $result = $this->edoc()->searchDocument([
            'CaseFileReference' => $case->CaseFileIdentifier,
            'TitleText' => $name,
        ]);

        return 1 === \count($result) ? reset($result) : null;
    }

    private function edoc()
    {
        if (null === $this->edoc) {
            $this->edoc = new Edoc($this->client(), $this->configuration['edoc_user_identifier']);
        }

        return $this->edoc;
    }

    private function client()
    {
        if (null === $this->client) {
            $this->client = new EdocClient(null, [
                'location' => $this->configuration['edoc_ws_url'],
                'username' => $this->configuration['edoc_ws_username'],
                'password' => $this->configuration['edoc_ws_password'],
//            'trace' => true,
            ]);
        }

        return $this->client;
    }
}
