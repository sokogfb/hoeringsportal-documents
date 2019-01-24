<?php

/*
 * This file is part of hoeringsportal-sync-files.
 *
 * (c) 2018 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace App\Twig;

use App\Entity\EDoc\CaseFile;
use App\Entity\EDoc\Document;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class EdocExtension extends AbstractExtension
{
    public function getFunctions()
    {
        return [
            new TwigFunction('edoc_casefile_url', [$this, 'getCaseFileUrl']),
            new TwigFunction('edoc_casefile_title', [$this, 'getCaseFileTitle']),
            new TwigFunction('edoc_document_url', [$this, 'getDocumentUrl']),
            new TwigFunction('edoc_document_title', [$this, 'getDocumentTitle']),
        ];
    }

    public function getCaseFileUrl(CaseFile $caseFile)
    {
        return $caseFile->getArchiver()->getEdocCaseFileUrl($caseFile);
    }

    public function getCaseFileTitle(CaseFile $caseFile)
    {
        return $caseFile->getData()['edoc']['TitleText'] ?? $caseFile->getCaseFileIdentifier();
    }

    public function getDocumentUrl(Document $document)
    {
        return $document->getArchiver()->getEdocDocumentUrl($document);
    }

    public function getDocumentTitle(Document $document)
    {
        return $document->getData()['edoc']['TitleText'] ?? $document->getDocumentIdentifier();
    }
}
