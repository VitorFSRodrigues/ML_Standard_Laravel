<?php

namespace App\Jobs;

use App\Models\Cliente;
use App\Models\Triagem;
use App\Models\Requisito;
use App\Services\PipedriveService;
use App\Enums\CaracteristicaOrcamento;
use App\Enums\RegimeContrato;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Str;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Arr;

class ImportDealFromPipedrive implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function __construct(public int $dealId) {}

    public function handle(PipedriveService $svc): void
    {
        Log::info('ImportDealFromPipedrive:start', ['dealId' => $this->dealId]);

        $deal = $svc->getDeal($this->dealId);
        if (!$deal) {
            Log::warning('ImportDealFromPipedrive:deal_not_found', ['dealId' => $this->dealId]);
            return;
        }

        $cf = $deal['custom_fields'] ?? [];

        $getVal = function ($raw) {
            if (is_array($raw)) {
                if (array_key_exists('value', $raw)) return $raw['value'];
                if (($raw['type'] ?? null) === 'org'  && isset($raw['id'])) return (int)$raw['id'];
                if (($raw['type'] ?? null) === 'enum' && isset($raw['id'])) return (int)$raw['id'];
                if (($raw['type'] ?? null) === 'set'  && isset($raw['values'][0]['id'])) return (int)$raw['values'][0]['id'];
            }
            return $raw;
        };

        $cfg  = config('pipedrive.deal_fields');
        $opts = [
            'estado_map' => config('pipedrive.estado_obra_options', []),
            'regime_map' => config('pipedrive.regime_contrato_options', []),
        ];

        // --------- CLIENTE (org_id) ----------
        $orgIdRaw = $deal['org_id'] ?? 0;
        $orgId    = is_array($orgIdRaw) ? (int)($orgIdRaw['value'] ?? 0) : (int)$orgIdRaw;

        Log::info('job.org_id.from_api', [
            'dealId'   => $this->dealId,
            'org_id'   => $orgId,
            'org_raw'  => $orgIdRaw,
            'org_name' => $deal['org_name'] ?? null,
        ]);

        $cliente = null;
        if ($orgId > 0) {
            $org    = $svc->getOrganization($orgId); // pode ser null se 404
            $orgMap = $org ? $svc->mapOrgToCliente($org) : null;

            // 1) Campos "sempre válidos" (não incluem endereço)
            $cliente = Cliente::updateOrCreate(
                ['pipedrive_org_id' => $orgMap['id'] ?? $orgId],
                [
                    'nome_cliente'  => $orgMap['nome_cliente'] ?? ($deal['org_name'] ?? '—'),
                    'nome_fantasia' => $this->firstWord($orgMap['nome_cliente'] ?? ($deal['org_name'] ?? '—')),
                ]
            );

            // 2) Endereço e afins: só aplica se vierem preenchidos (não gravar NULL)
            $addrPayload = [
                'endereco_completo' => $orgMap['endereco_completo'] ?? ($org['address'] ?? null),
                'municipio'         => $orgMap['municipio']         ?? null,
                'estado'            => $orgMap['estado']            ?? null,
                'pais'              => $orgMap['pais']              ?? null,
                'cnpj'              => $orgMap['cnpj']              ?? null,
            ];
            $addrPayload = array_filter($addrPayload, fn ($v) => !is_null($v));

            if (!empty($addrPayload)) {
                $cliente->fill($addrPayload)->save();
                Log::info('cliente.endereco.atualizado', [
                    'pipedrive_org_id' => $cliente->pipedrive_org_id,
                    'campos'           => array_keys($addrPayload),
                ]);
            }

            if (!$org) {
                Log::warning('job.org.fallback_minimal_cliente', ['org_id' => $orgId]);
            }
        }
        
        // --------- CLIENTE FINAL ----------
        $cliFinalKey   = $cfg['cliente_final_id'];
        $cliFinalRaw   = $cf[$cliFinalKey] ?? $deal[$cliFinalKey] ?? null;
        $cliFinalOrgId = (int)$getVal($cliFinalRaw);

        $clienteFinal = null;
        if ($cliFinalOrgId) {
            $org    = $svc->getOrganization($cliFinalOrgId);
            $orgMap = $org ? $svc->mapOrgToCliente($org) : null;

            // 1) Campos "sempre válidos"
            $clienteFinal = Cliente::updateOrCreate(
                ['pipedrive_org_id' => $orgMap['id'] ?? $cliFinalOrgId],
                [
                    'nome_cliente'  => $orgMap['nome_cliente'] ?? '—',
                    'nome_fantasia' => $this->firstWord($orgMap['nome_cliente'] ?? '—'),
                ]
            );

            // 2) Endereço e afins: só se vierem preenchidos
            $addrPayload = [
                'endereco_completo' => $orgMap['endereco_completo'] ?? ($org['address'] ?? null),
                'municipio'         => $orgMap['municipio']         ?? null,
                'estado'            => $orgMap['estado']            ?? null,
                'pais'              => $orgMap['pais']              ?? null,
                'cnpj'              => $orgMap['cnpj']              ?? null,
            ];
            $addrPayload = array_filter($addrPayload, fn ($v) => !is_null($v));

            if (!empty($addrPayload)) {
                $clienteFinal->fill($addrPayload)->save();
                Log::info('cliente_final.endereco.atualizado', [
                    'pipedrive_org_id' => $clienteFinal->pipedrive_org_id,
                    'campos'           => array_keys($addrPayload),
                ]);
            }
        }

        // --------- NÚMEROS DE ORÇAMENTO (por campo) ----------
        $mapCaracteristicaPorCampo = [
            // Engenharia
            '9defc0927da2bc7149e87b17d8523b4ab9cd42cb' => \App\Enums\CaracteristicaOrcamento::Engenharia,
            // MCM = Montagem
            'eb6739bfedd3711afeb82683f3f3025459bf8500' => \App\Enums\CaracteristicaOrcamento::Montagem,
            // Metal = Fabricação
            '07f020f7f63882a1129a08179edd6b7bf66fa63d' => \App\Enums\CaracteristicaOrcamento::Fabricacao,
            // Fast
            '0d4fe4c52379243de4ecd86d215d80c81ca86275' => \App\Enums\CaracteristicaOrcamento::FAST,
            // Painéis
            'af8eeedfcf899c09871343b07178411e95583c47' => \App\Enums\CaracteristicaOrcamento::Paineis,
        ];

        $itens = []; // cada item: ['numero' => '5555P' , 'carac' => CaracteristicaOrcamento::Paineis]

        foreach ($mapCaracteristicaPorCampo as $fieldKey => $caracEnum) {
            $raw = $cf[$fieldKey] ?? $deal[$fieldKey] ?? null; // webhook OU api flat
            $val = $getVal($raw);
            if (!empty($val)) {
                $itens[] = [
                    'numero' => (string) $val,   // mantém exatamente como o comercial digitou
                    'carac'  => $caracEnum,      // vem do campo correto
                ];
            }
        }

        // dedup por número (mantém o primeiro)
        $dedup = [];
        foreach ($itens as $i) {
            $dedup[$i['numero']] = $dedup[$i['numero']] ?? $i;
        }
        $itens = array_values($dedup);

        if (empty($itens)) {
            Log::info('ImportDealFromPipedrive:no_numbers', ['dealId' => $this->dealId]);
            return;
        }

        // --------- DEMAIS CAMPOS ----------
        $descricaoServico = (string)($deal[$cfg['descricao_servico']] ?? '');

        $cidRaw = $cf[$cfg['cidade_obra']] ?? $deal[$cfg['cidade_obra']] ?? null;
        $estRaw = $cf[$cfg['estado_obra']] ?? $deal[$cfg['estado_obra']] ?? null;
        $paisRaw= $cf[$cfg['pais_obra']]   ?? $deal[$cfg['pais_obra']]   ?? null;

        $cidade = (string)$getVal($cidRaw);
        $estado = $getVal($estRaw);
        if (is_int($estado)) $estado = ($opts['estado_map'][$estado] ?? $estado);
        $estado = (string)$estado;

        $pais   = (string)$getVal($paisRaw);

        // Regime de contrato (enum id -> label -> enum App\Enums\RegimeContrato)
        $regRaw = $cf[$cfg['regime_contrato']] ?? $deal[$cfg['regime_contrato']] ?? null;
        $regVal = $getVal($regRaw); // pode vir id enum
        if (is_int($regVal)) $regVal = $opts['regime_map'][$regVal] ?? $regVal;

        $regime = RegimeContrato::tryFrom((string)$regVal) ?? match ((string)$regVal) {
            'Empreitada Global' => RegimeContrato::EmpreitadaGlobal,
            'Administração'     => RegimeContrato::Administracao,
            'Preço Unitário'    => RegimeContrato::PrecoUnitario,
            'Parada'            => RegimeContrato::Parada,
            default             => RegimeContrato::EmpreitadaGlobal, // fallback seguro
        };

        Log::info('job.regime.debug', [
            'reg_raw' => $regRaw,
            'reg_val' => $regVal,
            'final'   => $regime->value,
        ]);

        // --------- CRIA UMA TRIAGEM PARA CADA NÚMERO ----------
        foreach ($itens as $item) {
            $numero = $this->stripSufixoNumero($item['numero']);
            /** @var \App\Enums\CaracteristicaOrcamento $carac */
            $carac  = $item['carac'];

            Log::info('job.triagem.insert', [
                'numero'           => $numero,
                'regime_contrato'  => $regime->value,
                'caracteristica'   => $carac->value,
                'cliente_id'       => $cliente?->id,
                'cliente_final_id' => $clienteFinal?->id,
            ]);

            Log::info('job.numero.normalize', [
                'raw'      => $item['numero'],
                'stripped' => $numero,
            ]);

            $t = Triagem::firstOrCreate(
                [
                    'pipedrive_deal_id' => $this->dealId,
                    'numero_orcamento'  => $numero,
                ],
                [
                    'cliente_id'               => $cliente?->id,
                    'cliente_final_id'         => $clienteFinal?->id,
                    'descricao_servico'        => $descricaoServico,
                    'regime_contrato'          => $regime->value,
                    'caracteristica_orcamento' => $carac->value,
                    'cidade_obra'              => $cidade,
                    'estado_obra'              => $estado,
                    'pais_obra'                => $pais,
                    'status'                   => true,
                    'destino'                  => 'triagem',
                    'condicao_pagamento_ddl' => 0, // se quiser garantir default aqui
                ]
            );

            Requisito::firstOrCreate(
                ['triagem_id' => $t->id],
                ['icms_percent' => 20.50]
            );
        }

        Log::info('ImportDealFromPipedrive:end', ['dealId' => $this->dealId, 'created' => count($itens)]);

    }
    private function firstWord(?string $name): string
    {
        $name = trim((string) $name);
        if ($name === '') return '';
        // pega tudo até o primeiro espaço
        return Str::before($name, ' ');
    }
    private function stripSufixoNumero(string $n): string
    {
        // limpa espaços e remove TODOS os sufixos válidos repetidos no final (E|F|P|Eng)
        // ex.: 5558PP -> 5558 ; 5556EE -> 5556 ; 5554EngEng -> 5554 ; 5555P -> 5555
        $n = trim($n);

        // remove sufixos repetidos no fim (case-insensitive)
        $n = preg_replace('/(?:Eng|E|F|P)+$/i', '', $n) ?? $n;

        // por via das dúvidas, remove espaços novamente
        return trim($n);
    }

}
