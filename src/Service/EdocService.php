<?php

/*
 * This file is part of hoeringsportal-sync-files.
 *
 * (c) 2018 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class EdocService
{
    /** @var array */
    private $configuration;

    public function __construct(ParameterBagInterface $parameters)
    {
        $this->configuration = [
            'edoc_ws_url' => $parameters->get('edoc_ws_url'),
            'edoc_username' => $parameters->get('edoc_ws_username'),
            'edoc_password' => $parameters->get('edoc_ws_password'),
        ];
    }

    public function getFiles($path = null)
    {
        return [
            __METHOD__,
            \func_get_args(),
            $this->configuration,
        ];
    }
}
