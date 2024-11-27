<?php

namespace NFService\Sicoob\Services;

use GuzzleHttp\Exception\GuzzleException;
use NFService\Sicoob\Sicoob;


class Auth
{
    protected Sicoob $sicoob;

    public function __construct(Sicoob $sicoob)
    {
        $this->sicoob = $sicoob;
    }

    public function gerarToken(): string | GuzzleException
    {
        $client = new \GuzzleHttp\Client();

        try {
            $response = $client->request('POST', $this->sicoob->getBaseUrl(), [
                'form_params' => [
                    'client_id' => $this->sicoob->getClientId(),
                    'grant_type' => 'client_credentials',
                    'scope' => $this->sicoob->getPermissions(),
                ],
                'cert' => $this->sicoob->getCertificatePub()
            ]);

            $response = json_decode($response->getBody()->getContents());

            $this->sicoob->setExpiresIn(time() + $response->expires_in);
            $this->sicoob->setToken($response->access_token);
            return $response->access_token;
        } catch (GuzzleException $e) {
            $this->sicoob->setToken('');
            $this->sicoob->setExpiresIn(0);
            return $e;
        }
    }
}