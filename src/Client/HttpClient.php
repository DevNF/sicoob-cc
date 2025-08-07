<?php

namespace NFService\Sicoob\Client;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use NFService\Sicoob\Options\EnvironmentUrls;
use NFService\Sicoob\Sicoob;
use stdClass;


class HttpClient
{
    private bool $debug;
    private bool $sandbox;
    private string $base_url;
    private Sicoob $sicoob;
    private array $certificatePub;

    public function __construct(Sicoob $sicoob, bool $debug = false)
    {

        if($sicoob->getIsProduction()) {
            if(empty($sicoob->getCertificatePub())) {
                throw new Exception('Caminho do certificado público é obrigatório');
            }
        }
        $this->sicoob = $sicoob;
        $this->base_url = !$sicoob->getIsProduction() ? EnvironmentUrls::sandbox_url : EnvironmentUrls::production_url;
        $this->debug = $debug;
        $this->sandbox = !$sicoob->getIsProduction();
        $this->certificatePub = $sicoob->getCertificatePub();
    }

    public function requisicao(string $uri, string $metodo, ?array $corpo = null, ?array $params = null ): string | GuzzleException | array | stdClass | null
    {
        try {
            $client = new \GuzzleHttp\Client();

            $response = $client->request($metodo, $this->base_url . $uri, [
                'debug' => $this->debug,
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->sicoob->getToken(),
                    'client_id' => $this->sicoob->getClientId(),
                    'Content-Type' => 'application/json'
                ],
                'query' => $params,
                'json' => $corpo,
                'cert' => $this->certificatePub
            ]);


            return json_decode($response->getBody()->getContents());

        } catch (\GuzzleHttp\Exception\RequestException $e) {
            if($e->hasResponse()) {
                $res = json_decode($e->getResponse()->getBody()->getContents());

                if(!empty($res->errors) && !empty($res->errors[0]->detail)) {
                    return [
                        'message' => $res->errors[0]->detail
                    ];
                }

                if(!empty($res->detail)) return [
                    'message' => $res->detail,
                    'violacoes' => isset($res->violacoes) ? $res->violacoes : null
                ];
            }

            return $e->getMessage();
        } catch (\Exception $e) {
            return $e;
        }

    }
}
