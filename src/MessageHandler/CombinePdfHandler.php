<?php

/*
 * This file is part of hoeringsportal-sync-files.
 *
 * (c) 2018–2019 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace App\MessageHandler;

use App\Message\CombinePdf;
use App\Service\PdfHelper;
use App\Util\FileLogger;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class CombinePdfHandler implements MessageHandlerInterface
{
    /** @var PdfHelper */
    private $helper;

    public function __construct(PdfHelper $helper)
    {
        $this->helper = $helper;
    }

    public function __invoke(CombinePdf $message)
    {
        $logger = new FileLogger($message->getLoggerFilename());

        try {
            $logger->notice('start', ['status' => 'start']);
            $this->helper->setLogger($logger);
            $this->helper->setArchiver($message->getArchiverId());
            $this->helper->run($message->getHearingId());
            $logger->notice('done', ['status' => 'done']);
        } catch (\Throwable $t) {
            $logger->error($t->getMessage(), ['status' => 'error']);
        }
    }
}
