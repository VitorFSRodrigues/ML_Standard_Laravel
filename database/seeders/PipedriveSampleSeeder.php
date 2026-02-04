<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\Cliente;
use App\Models\Triagem;

class PipedriveSampleSeeder extends Seeder
{
    /** Map do sufixo -> Característica */
    private const MAP_CARAC = [
        ''    => 'Montagem',
        'E'   => 'Fabricação',
        'F'   => 'FAST',
        'P'   => 'Painéis',
        'ENG' => 'Engenharia',
    ];

    public function run(): void
    {
        // -----------------------------
        // 1) ORGANIZAÇÕES -> CLIENTES
        // -----------------------------
        // IDs fixos (1..3) como no seu quadro
        $orgs = [
            [
                'id'               => 1,
                'nome_cliente'     => 'ERO BRASIL (Antiga MINERAÇÃO CARAÍBA)',
                'nome_fantasia'    => 'ERO',
                'endereco_completo'=> 'Loc Fazenda Caraiba, S/N SEDE JAGUARARI - BA 48960-000',
                'municipio'        => 'Jaguarari',
                'estado'           => 'BA',
                'pais'             => 'Brasil',
                'cnpj'             => '42.509.257/0001-13',
            ],
            [
                'id'               => 2,
                'nome_cliente'     => 'ULTRACARGO - RJ',
                'nome_fantasia'    => 'ULTRACARGO',
                'endereco_completo'=> 'Rua General Gurjão s/nº, Caju, Rio de Janeiro-RJ. CEP: 20931-040',
                'municipio'        => 'Rio de Janeiro',
                'estado'           => 'RJ',
                'pais'             => 'Brasil',
                'cnpj'             => '14.688.220/0015-60',
            ],
            [
                'id'               => 3,
                'nome_cliente'     => 'GERDAU - MARACANAÚ',
                'nome_fantasia'    => 'GERDAU',
                'endereco_completo'=> 'Avenida Parque Oeste, 1400 - Distrito Industrial, Maracanaú - CE, Brasil',
                'municipio'        => 'Maracanaú',
                'estado'           => 'CE',
                'pais'             => 'Brasil',
                'cnpj'             => '07.358.761/0013-00',
            ],
        ];

        // upsert para manter ids
        foreach ($orgs as $o) {
            Cliente::query()->updateOrCreate(
                ['id' => $o['id']],
                $o
            );
        }

        // ------------------------------------------
        // 2) “DEALS” SIMULADOS -> LINHAS EM TRIAGEM
        // ------------------------------------------
        // Cada elemento representa 1 deal do Pipedrive
        $deals = [
            // Seed 1
            [
                'org_id'            => 1,
                'cliente_final_id'  => 1,
                'title'             => 'Montagem Eletromecânica',
                'cidade_obra'       => 'Jaguarari',
                'estado_obra'       => 'BA',
                'pais_obra'         => 'Brasil',
                'regime_contrato'   => 'Empreitada Global',
                // números (qualquer um pode estar vazio)
                'numero_engenharia' => null,          // 9defc... => ex.: 9998Eng (aqui não veio)
                'numero_mcm'        => '9999',        // eb6739...
                'numero_metal'      => null,          // 07f020...
                'numero_fast'       => null,          // 0d4fe4...
                'numero_paineis'    => null,          // af8eee...
            ],
            // Seed 2
            [
                'org_id'            => 2,
                'cliente_final_id'  => 3,
                'title'             => 'Execução de Projeto Executivo',
                'cidade_obra'       => 'Rio de Janeiro',
                'estado_obra'       => 'RJ',
                'pais_obra'         => 'Brasil',
                'regime_contrato'   => 'Empreitada Global',
                'numero_engenharia' => '9998Eng',
                'numero_mcm'        => null,
                'numero_metal'      => null,
                'numero_fast'       => null,
                'numero_paineis'    => null,
            ],
            // Seed 3
            [
                'org_id'            => 2,
                'cliente_final_id'  => 2,
                'title'             => 'Execução de EPC',
                'cidade_obra'       => 'Maracanaú',
                'estado_obra'       => 'CE',
                'pais_obra'         => 'Brasil',
                'regime_contrato'   => 'Empreitada Global',
                'numero_engenharia' => '9997Eng',
                'numero_mcm'        => '9996',
                'numero_metal'      => '9995E',
                'numero_fast'       => '9994F',
                'numero_paineis'    => '9993P',
            ],
        ];

        foreach ($deals as $deal) {
            // percorre possíveis campos de número em ordem de prioridade
            foreach ([
                $deal['numero_engenharia'],
                $deal['numero_mcm'],
                $deal['numero_metal'],
                $deal['numero_fast'],
                $deal['numero_paineis'],
            ] as $rawNumero) {
                if (empty($rawNumero)) {
                    continue;
                }

                [$numero, $carac] = $this->parseNumeroECaracteristica($rawNumero);

                Triagem::query()->create([
                    'cliente_id'               => $deal['org_id'],
                    'cliente_final_id'         => $deal['cliente_final_id'],
                    'numero_orcamento'         => $numero,                 // apenas os 4 dígitos
                    'caracteristica_orcamento' => $carac,                  // pela regra
                    'tipo_servico'             => null,                    // preenchido depois
                    'regime_contrato'          => $deal['regime_contrato'],
                    'descricao_servico'        => $deal['title'],
                    'descricao_resumida'       => null,
                    'condicao_pagamento_ddl'   => 0,

                    // novos campos (2.4)
                    'cidade_obra'              => $deal['cidade_obra'],
                    'estado_obra'              => $deal['estado_obra'],
                    'pais_obra'                => $deal['pais_obra'],

                    // default/fluxo
                    'status'                   => true,
                    'destino'                  => 'triagem',
                    'orcamentista_id'          => null,
                ]);
            }
        }
    }

    /**
     * Recebe algo como "9998Eng", "9995E", "9999", e retorna:
     *  - numero_orcamento: "9998"
     *  - caracteristica_orcamento: 'Engenharia' | 'Fabricação' | 'FAST' | 'Painéis' | 'Montagem'
     */
    private function parseNumeroECaracteristica(string $raw): array
    {
        if (!preg_match('/^(\d{4})(.*)$/u', trim($raw), $m)) {
            // fallback seguro
            return [trim($raw), self::MAP_CARAC['']];
        }

        $num = $m[1];
        $rest = strtoupper(trim($m[2] ?? ''));

        // normaliza 'ENG' se vier em caixa mista
        if (Str::startsWith($rest, 'ENG')) {
            $rest = 'ENG';
        }

        $carac = self::MAP_CARAC[$rest] ?? self::MAP_CARAC[''];
        return [$num, $carac];
    }
}
