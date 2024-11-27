<?php

namespace NFService\Sicoob\Services;

use NFService\Sicoob\Client\HttpClient;
use NFService\Sicoob\Sicoob;
use Valitron\Validator;

class ContaCorrente
{
    protected HttpClient $client;

    public function __construct(Sicoob $sicoob)
    {
        $this->client = $sicoob->getClient();
    }

    /**
     * @param int $numeroContaCorrente
     * @param array $params {
     *     @type int $diaInicial
     *     @type int $diaFinal
     *     @type bool $agruparCNAB
     * }
     * @return array
     * @throws \Exception
     */
    public function extrato(int $mes, int $ano, int $numeroContaCorrente, array $params = [])
    {
        $queryParameters = array_merge($params, [
            'mes' => $mes,
            'ano' => $ano,
            'numeroContaCorrente' => $numeroContaCorrente
        ]);

        $this->validarDadosRequisicao($queryParameters);

        return $this->client->requisicao("/extrato/{$queryParameters['mes']}/{$queryParameters['ano']}", 'GET', null,  $queryParameters);
    }

    private function validarDadosRequisicao(array $dados): void
    {
        $v = new Validator($dados);
        $v->rule('required', ['mes', 'ano', 'numeroContaCorrente']);
        $v->rule('min', 'mes', 1);
        $v->rule('max', 'mes', 12);
        $v->rule('min', 'ano', 1900);
        $v->rule('max', 'ano', 2200);
        $v->rule('optional', ['diaInicial', 'diaFinal', 'agruparCNAB']);
        $v->rule('min', 'diaInicial', 1);
        $v->rule('max', 'diaInicial', 31);
        $v->rule('min', 'diaFinal', 1);
        $v->rule('max', 'diaFinal', 31);
        $v->rule('boolean', 'agruparCNAB');
        
        if(!$v->validate()) {
            $errors = $v->errors();
            foreach($errors as $key => $value) {
                $errors[$key] = implode(', ', $value);
            }
            throw new \Exception('Erro de validação: ' . implode(', ', $errors));
        }
    }
}