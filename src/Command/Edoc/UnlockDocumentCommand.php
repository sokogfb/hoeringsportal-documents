<?php

/*
 * This file is part of hoeringsportal-sync-files.
 *
 * (c) 2018–2019 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace App\Command\Edoc;

use App\Command\Command;
use App\Service\EdocService;
use ItkDev\Edoc\Entity\Document;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UnlockDocumentCommand extends Command
{
    protected $archiverType = 'sharefile2edoc';

    /** @var EdocService */
    private $edoc;

    public function __construct(EdocService $edoc)
    {
        parent::__construct();
        $this->edoc = $edoc;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);
        $this->edoc->setArchiver($this->archiver);

        $documentId = $input->getArgument('document-id');
        $lock = $input->getOption('lock');

        $document = $this->edoc->getDocumentById($documentId);
        $this->writeDocument($document, $output);

        if ($lock) {
            $output->writeln('Locking document');
            $this->edoc->lockDocument($document);
            $document = $this->edoc->getDocumentById($documentId);
            $this->writeDocument($document, $output);
        } else {
            $output->writeln('Unlocking document');
            $this->edoc->unlockDocument($document);
            $document = $this->edoc->getDocumentById($documentId);
            $this->writeDocument($document, $output);
        }
    }

    protected function configure()
    {
        parent::configure();
        $this->setName('app:edoc:unlock-document')
            ->addArgument('document-id', InputArgument::REQUIRED, 'The document id')
            ->addOption('lock', InputOption::VALUE_NONE);
    }

    private function writeDocument(Document $document, OutputInterface $output)
    {
        $data = $document->getData();

        $output->writeln([
            'TitleText:              '.$data['TitleText'],
            'DocumentIdentifier:     '.$data['DocumentIdentifier'],
            'DocumentTypeReference:  '.$data['DocumentTypeReference'],
            'DocumentStatusCode:     '.$data['DocumentStatusCode'],
            'Links.OpenDocument:     '.$data['Links']['OpenDocument'],
        ]);
    }
}
