<?php

/*
 * This file is part of hoeringsportal-sync-files.
 *
 * (c) 2018 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace App\Service;

use Deskpro\API\DeskproClient;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class DeskproService
{
    /** @var DeskproClient */
    private $client;

    /** @var array */
    private $config;

    public function __construct(ParameterBagInterface $parameters)
    {
        $this->config = [
            'deskpro_api_code_key' => $parameters->get('deskpro_api_code_key'),
            'deskpro_url' => $parameters->get('deskpro_url'),
        ];
    }

    public function getReplyData($replyId)
    {
        $response = $this->client()->get('/ticket_custom_fields/{id}', ['id' => $replyId]);

        return $response->getData();
    }

    /**
     * Get a Deskpro client.
     */
    private function client()
    {
        if (null === $this->client) {
            // https://github.com/deskpro/deskpro-api-client-php
            $client = new Client(['connect_timeout' => 2]);
            $this->client = new DeskproClient($this->config['deskpro_url'], $client);
            $authKey = explode(':', $this->config['deskpro_api_code_key']);
            $this->client->setAuthKey(...$authKey);
        }

        return $this->client;
    }
}
