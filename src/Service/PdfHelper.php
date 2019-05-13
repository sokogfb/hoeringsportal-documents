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
use App\Repository\ArchiverRepository;
use App\ShareFile\Item;
use Mpdf\Mpdf;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LogLevel;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Templating\EngineInterface;

class PdfHelper
{
    use LoggerAwareTrait;

    /** @var ArchiverRepository */
    private $archiverRepository;

    /** @var ShareFileService */
    private $shareFileService;

    /** @var Filesystem */
    private $filesystem;

    /** @var \Twig */
    private $twig;

    /** @var ParameterBagInterface */
    private $params;

    public function __construct(
        ArchiverRepository $archiverRepository,
        ShareFileService $shareFileService,
        Filesystem $filesystem,
        EngineInterface $twig,
        ParameterBagInterface $params
    ) {
        $this->archiverRepository = $archiverRepository;
        $this->shareFileService = $shareFileService;
        $this->filesystem = $filesystem;
        $this->twig = $twig;
        $this->params = $params;
    }

    public function getData($archiverId, $hearingId)
    {
        $this->debug('Getting archiver');
        $archiver = $this->getArchiver($archiverId);
        $this->shareFileService->setArchiver($archiver);
        $this->debug('Getting hearing');
        $hearing = $this->shareFileService->findHearing($hearingId);
        $this->debug('Getting responses');
        $responses = $this->getResponses($hearing);

        $this->debug('Getting file data');
        // Pdf files indexed by response id.
        $files = [];
        $fileNamePattern = '*-offentlig*.pdf';

        foreach ($responses as $response) {
            $responseFiles = $this->shareFileService->getFiles($response);
            $responseFiles = array_filter($responseFiles, function (Item $file) use ($fileNamePattern) {
                return fnmatch($fileNamePattern, $file->name);
            });
            if (0 < \count($responseFiles)) {
                $file = reset($responseFiles);
                $this->debug($file->getId());
                $files[$response->getId()] = $file;
            }
        }

        // Remove responses with no pdf file.
        $responses = array_filter($responses, function (Item $response) use ($files) {
            return isset($files[$response->getId()]);
        });

        $filename = $this->getDataFilename($hearingId);

        // Build hearing metadata.
        $hearing = json_decode(json_encode($hearing), true);
        $firstResponse = reset($responses);
        if ($firstResponse) {
            $metadata = $firstResponse->metadata;
            $hearing['_metadata'] = [
                'hearing_url' => 'https://deltag.aarhus.dk/node/'.preg_replace('/^[^\d]+/', '', $hearingId),
                'hearing_name' => $metadata['ticket_data']['hearing_name'] ?? null,
                'department_title' => $metadata['ticket_data']['department_title'] ?? null,
            ];
        }

        $this->debug('Writing datafile '.$filename);
        $this->filesystem->dumpFile($filename, json_encode([
            'archiver' => $archiver,
            'hearing' => $hearing,
            'responses' => $responses,
            'files' => $files,
        ]));

        return $filename;
    }

    public function combine($hearingId)
    {
        $data = $this->getHearingData($hearingId);
        $archiver = $this->getArchiver($data);
        $this->shareFileService->setArchiver($archiver);

        return $this->buildCombinedPdf($data);
    }

    public function share($hearingId)
    {
        $data = $this->getHearingData($hearingId);
        $archiver = $this->getArchiver($data);
        $this->shareFileService->setArchiver($archiver);
        $filename = $this->getDataFilename($hearingId, '-combined.pdf');
        if (!$this->filesystem->exists($filename)) {
            throw new \RuntimeException('Cannot find file to share for hearing '.$hearingId);
        }

        $parentId = $this->getHearingValue($data, 'Id');

        $this->debug(sprintf('Sharing file %s to %s', $filename, $parentId));

        $result = $this->shareFileService->uploadFile($filename, $parentId);

        if ('OK' !== $result) {
            throw new \RuntimeException('Error uploading file: '.$filename);
        }

        try {
            $result = $this->shareFileService->findFile(basename($filename), $parentId);
        } catch (\Exception $e) {
            throw new \RuntimeException(sprintf('Cannot get shared file %s in %s', $filename, $parentId));
        }

        return $result;
    }

    public function archive($hearingId)
    {
        throw new \RuntimeException(__METHOD__.' is not implemented!');
    }

    public function buildCombinedPdf(array $data)
    {
        $this->debug('Downloading pdf files');
        $dirname = $this->downloadFiles($data);
        $this->debug('Combining pdf files');
        $filename = $this->combineFiles($data, $dirname);

        return $filename;
    }

    public function setArchiver(Archiver $archiver)
    {
        $this->archiver = $archiver;
        $this->shareFileService->setArchiver($archiver);
    }

    /**
     * Get data filename.
     *
     * @param $hearingId
     * @param mixed $suffix
     *
     * @return string
     */
    private function getDataFilename($hearingId, $suffix = '.json')
    {
        $directory = $this->getDataDirectory($hearingId);
        $filename = $directory.'/'.$hearingId.$suffix;

        $this->filesystem->mkdir(\dirname($filename));

        return $filename;
    }

    private function getHearingData($hearingId)
    {
        $filename = $this->getDataFilename($hearingId);
        if (!$this->filesystem->exists($filename)) {
            throw new \RuntimeException('Cannot find datafile for hearing '.$hearingId);
        }
        $data = file_get_contents($filename);

        return json_decode($data, true);
    }

    private function getArchiver($id)
    {
        if (isset($id['archiver']['id'])) {
            $id = $id['archiver']['id'];
        }

        $archiver = $this->archiverRepository->find($id) ?? $this->archiverRepository->findOneBy(['name' => $id]);

        if (null === $archiver) {
            throw new \RuntimeException('Invalid archiver: '.$archiverId);
        }

        return $archiver;
    }

    /**
     * Download files from ShareFile.
     *
     * @param array $items
     */
    private function downloadFiles(array $data)
    {
        try {
            $hearingId = $this->getHearingValue($data, 'Name');

            $dirname = $this->getDataDirectory($hearingId);
            $this->filesystem->mkdir($dirname);
            $this->debug('dirname: '.$dirname);

            $index = 0;
            foreach ($data['files'] as $responseId => $item) {
                ++$index;
                if (empty($item)) {
                    continue;
                }
                $item = new Item($item);
                $filename = $this->getPdfFilename($dirname, $responseId);

                if ($this->filesystem->exists($filename)) {
                    $itemCreationTime = new \DateTime($item->creationDate);
                    $fileMtime = new \DateTime();
                    $fileMtime->setTimestamp(filemtime($filename));
                    if ($fileMtime > $itemCreationTime) {
                        $this->debug(sprintf('% 4d/%d File %s already downloaded (%s)', $index, \count($data['files']), $item->getId(), $filename));

                        continue;
                    }
                }
                $this->debug(sprintf('% 4d/%d Downloading file %s (%s)', $index, \count($data['files']), $item->getId(), $filename));
                $contents = $this->shareFileService->downloadFile($item);
                $this->filesystem->dumpFile($filename, $contents);
            }

            return rtrim($dirname, '/');
        } catch (IOExceptionInterface $exception) {
            $this->log(LogLevel::EMERGENCY, 'An error occurred while creating your directory at '.$exception->getPath());
        }
    }

    private function getDataDirectory($path = null)
    {
        $directory = $this->params->get('kernel.project_dir').'/var/pdf';

        if (null !== $path) {
            $directory .= '/'.$path;
        }

        return $directory;
    }

    private function getPdfFilename(string $directory, $item)
    {
        $id = $item instanceof Item ? $item->getId() : $item;

        return $directory.'/'.$id.'.pdf';
    }

    private function getHearingValue(array $data, $key = null)
    {
        if (null !== $key) {
            if (!isset($data['hearing'][$key])) {
                throw new \OutOfBoundsException('No such key: '.$key);
            }

            return $data['hearing'][$key];
        }

        return $data['hearing'];
    }

    private function combineFiles(array $data, string $directory)
    {
        $hearingId = $this->getHearingValue($data, 'Name');

        $mpdf = new Mpdf();

        // @TODO Generate front page
        $this->debug('Generating front page');
        $frontPage = $this->generateFrontpage($data);
        $mpdf->WriteHTML($frontPage);

        $this->debug('Adding table of contents');
        $mpdf->TOCpagebreakByArray([
            'links' => true,
        ]);

//         $mpdf->SetHTMLHeader('
        // <table width="100%">
//     <tr>
//         <td width="100%">'.$hearing['title'].'</td>
//     </tr>
        // </table>'
        // );

        $mpdf->SetHTMLFooter(
            '
<table width="100%">
    <tr>
        <td width="50%">{DATE Y-m-d}</td>
        <td width="50%" style="text-align: right">{PAGENO}/{nbpg}</td>
    </tr>
</table>'
);

        $index = 0;
        foreach ($data['responses'] as $response) {
            ++$index;
            $response = new Item($response);
            $filename = $this->getPdfFilename($directory, $response);
            if (!$this->filesystem->exists($filename)) {
                continue;
            }

            $pagecount = $mpdf->SetSourceFile($filename);
            $this->debug(sprintf('% 4d/%d Adding file %s', $index, \count($data['responses']), $filename));

            for ($p = 1; $p <= $pagecount; ++$p) {
                $tplId = $mpdf->ImportPage($p);
                $size = $mpdf->GetTemplateSize($tplId);

                if ($index > 1 || $p > 1) {
                    $mpdf->AddPageByArray([
                        'orientation' => $size['width'] > $size['height'] ? 'L' : 'P',
                        'newformat' => [$size['width'], $size['height']],
                    ]);
                }
                if (1 === $p) {
                    $mpdf->TOC_Entry($response->getName() ?? $response->getId(), 0);
                }

                $mpdf->UseTemplate($tplId);
            }
        }

        $filename = $this->getDataFilename($hearingId, '-combined.pdf');
        $mpdf->Output($filename);

        return $filename;
    }

    private function debug($message, array $context = [])
    {
        if (null !== $this->logger) {
            $this->logger->debug($message, $context);
        }
    }

    /**
     * Get responses indexed by item id.
     *
     * @param Item $hearing
     * @param bool $includeFiles
     *
     * @return array|false
     */
    private function getResponses(Item $hearing, $includeFiles = false)
    {
        // @TODO Handle $includeFiles.

        $responses = $this->shareFileService->getResponses($hearing);

        // Split into organizations and persons.
        $organizations = array_filter($responses, function (Item $item) {
            return isset($item->metadata['ticket_data']['on_behalf_organization']);
        });
        $persons = array_filter($responses, function (Item $item) {
            return !isset($item->metadata['ticket_data']['on_behalf_organization']);
        });
        // Sort by creation time.
        usort($organizations, function (Item $a, Item $b) {
            return strcmp($a->creationDate, $b->creationDate);
        });
        usort($persons, function (Item $a, Item $b) {
            return strcmp($a->creationDate, $b->creationDate);
        });

        // Index by item id.
        return array_combine(
            array_column($responses, 'id'),
            $responses
        );
    }

    private function generateFrontPage(array $data)
    {
        $template = 'pdf/frontpage.html.twig';

        return $this->twig->render($template, $data);
    }
}
