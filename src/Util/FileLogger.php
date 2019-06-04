<?php

/*
 * This file is part of hoeringsportal-sync-files.
 *
 * (c) 2018–2019 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace App\Util;

use Psr\Log\AbstractLogger;

class FileLogger extends AbstractLogger
{
    private $filename;

    public function __construct(string $filename, bool $truncate = true)
    {
        $this->filename = $filename;
        if ($truncate) {
            file_put_contents($this->filename, '');
        }
    }

    public function log($level, $message, array $context = [])
    {
        $data = json_encode([
            'created_at' => (new \DateTime('now', new \DateTimeZone('UTC')))->format(\DateTime::ATOM),
            'level' => $level,
            'message' => $message,
            'context' => $context,
        ]).','.PHP_EOL;
        file_put_contents($this->filename, $data, FILE_APPEND);
    }

    public function getFilename()
    {
        return $this->filename;
    }

    public static function getContent(string $filename)
    {
        if (file_exists($filename)) {
            $content = file_get_contents($filename);

            return json_decode('['.rtrim(trim($content), ',').']');
        }

        return null;
    }
}
