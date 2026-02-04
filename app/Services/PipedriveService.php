<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;
use Carbon\Carbon;

class PipedriveService
{
    private string $base;
    private string $token;

    public function __construct()
    {
        $this->base  = (string) config('pipedrive.base_url');
        $this->token = (string) config('pipedrive.api_token');
    }

    /* -------------------- REST -------------------- */

    public function getDeal(int $dealId): array
    {
        try {
            $r = Http::get("{$this->base}/deals/{$dealId}", [
                'api_token' => $this->token,
            ]);

            $r->throw();

            $data = $r->json()['data'] ?? [];
            Log::info('pipedrive.getDeal.ok', [
                'dealId' => $dealId,
                'org_id' => $data['org_id'] ?? null,
                'base'   => $this->base,
            ]);

            return $data;
        } catch (Throwable $e) {
            Log::error('pipedrive.getDeal.fail', [
                'dealId' => $dealId,
                'msg'    => $e->getMessage(),
                'base'   => $this->base,
            ]);
            return [];
        }
    }

    public function getOrganization(int $orgId): ?array
    {
        try {
            $r = Http::get("{$this->base}/organizations/{$orgId}", [
                'api_token' => $this->token,
            ]);

            if ($r->status() === 404) {
                Log::warning('pipedrive.getOrganization.404', ['orgId' => $orgId]);
                return null;
            }

            $r->throw();
            $data = $r->json()['data'] ?? null;

            Log::info('pipedrive.getOrganization.ok', ['orgId' => $orgId]);

            return $data;
        } catch (Throwable $e) {
            Log::warning('pipedrive.getOrganization.fail', [
                'orgId' => $orgId,
                'msg'   => $e->getMessage(),
            ]);
            return null;
        }
    }

    /* -------------------- Mapping helpers -------------------- */

    /**
     * Extrai TODOS os números de orçamento preenchidos nas prioridades configuradas.
     * Retorna um array de strings (sem vazios/duplicados).
     */
    public function collectNumerosOrcamento(array $deal): array
    {
        $cf   = $deal['custom_fields'] ?? [];
        $cfg  = config('pipedrive.deal_fields');
        $prio = $cfg['numero_orcamento_prioridades'] ?? [];

        $out = [];
        foreach ($prio as $key) {
            $raw = $cf[$key] ?? null;
            $val = $this->extractCustom($raw);
            if ($val !== null && $val !== '') {
                $out[] = (string) $val;
            }
        }

        return array_values(array_unique($out));
    }

    /**
     * Mapeia Deal -> array para inserir em 'triagem' (uma ÚNICA linha).
     * OBS: para múltiplos números, use collectNumerosOrcamento() e sobrescreva 'numero_orcamento' por iteração.
     */
    public function mapDealToTriagem(array $deal): array
    {
        $cf  = $deal['custom_fields'] ?? [];
        $cfg = config('pipedrive.deal_fields');

        // número (primeira prioridade)
        $numero = null;
        foreach ($cfg['numero_orcamento_prioridades'] as $key) {
            $numero = $this->extractCustom($cf[$key] ?? null);
            if (!empty($numero)) break;
        }

        return [
            'pipedrive_deal_id'      => (int) Arr::get($deal, 'id', 0),

            'numero_orcamento'       => (string) $numero,

            // IDs (organizations)
            'cliente_id'             => (int) Arr::get($deal, $cfg['cliente_id'], 0),
            'cliente_final_id'       => (int) $this->extractCustom($cf[$cfg['cliente_final_id']] ?? null),

            'descricao_servico'      => (string) Arr::get($deal, $cfg['descricao_servico'], ''),

            // localização
            'cidade_obra'            => (string) $this->extractCustom($cf[$cfg['cidade_obra']] ?? null),
            'estado_obra'            => (string) $this->extractCustom($cf[$cfg['estado_obra']] ?? null),
            'pais_obra'              => (string) $this->extractCustom($cf[$cfg['pais_obra']] ?? null),

            // enum custom -> rótulo
            'regime_contrato'        => (string) $this->extractCustom($cf[$cfg['regime_contrato']] ?? null),

            // campos que ainda serão preenchidos no app
            'tipo_servico'           => null,
            'descricao_resumida'     => null,
            'condicao_pagamento_ddl' => 0,

            // defaults
            'status'                 => true,
            'destino'                => 'triagem',
        ];
    }

    /**
     * Mapeia Organization -> Clientes
     */
    public function mapOrgToCliente(array $org): array
    {
        $cfg = config('pipedrive.organization_fields');

        return [
            'id'                => $org[$cfg['id']]                ?? null,
            'nome_cliente'      => $org[$cfg['nome_cliente']]      ?? null,
            'nome_fantasia'     => $org[$cfg['nome_cliente']]      ?? null,
            'endereco_completo' => $org[$cfg['endereco_completo']] ?? null,
            'municipio'         => $org[$cfg['municipio']]         ?? null,
            'estado'            => $org[$cfg['estado']]            ?? null,
            'pais'              => $org[$cfg['pais']]              ?? null,
            'cnpj'              => $org[$cfg['cnpj']]              ?? null,
        ];
    }

    /**
     * Normaliza diferentes formatos de campos custom do Pipedrive.
     * - escalares: string/int -> retorno direto
     * - {value: ...}           -> value
     * - {type: 'org', id: N}   -> id
     * - {type: 'enum', id}     -> rótulo via config('pipedrive.regime_contrato_options')
     * - {type: 'set', values:[{id}]} -> rótulo via config('pipedrive.estado_obra_options') (pega o 1º)
     */
    private function extractCustom($raw)
    {
        if (is_null($raw) || is_string($raw) || is_numeric($raw)) {
            return $raw;
        }

        if (is_array($raw)) {
            if (array_key_exists('value', $raw)) {
                return $raw['value'];
            }

            // Organization picker
            if (($raw['type'] ?? null) === 'org' && isset($raw['id'])) {
                return (int) $raw['id'];
            }

            // ENUM único: id -> rótulo via config
            if (($raw['type'] ?? null) === 'enum') {
                $id  = (int)($raw['id'] ?? $raw['value'] ?? 0);
                $map = config('pipedrive.regime_contrato_options', []);
                return $map[$id] ?? (string) $id; // se não achar, devolve o id como string
            }

            // SET (multiselect): pega o primeiro id -> rótulo via config
            if (($raw['type'] ?? null) === 'set') {
                $id  = (int)($raw['values'][0]['id'] ?? 0);
                $map = config('pipedrive.estado_obra_options', []);
                return $map[$id] ?? (string) $id;
            }
        }

        return null;
    }
    
    /**
     * Lista deals ordenados por update_time desc, filtrando por cutoff.
     * Retorna um Generator de arrays de deal (como a API devolve).
     */
    public function listDealsUpdatedSince(\DateTimeInterface $since): \Generator
    {
        $limit  = (int) config('pipedrive.sync.page_limit', 100);
        $start  = 0;
        $cutoff = Carbon::instance((clone $since))->utc(); // compara em UTC

        do {
            $resp = Http::get("{$this->base}/deals", [
                'api_token' => $this->token,
                'start'     => $start,
                'limit'     => $limit,
                'sort'      => 'update_time DESC',
                'status'    => 'all_not_deleted', // pega abertos/ganhos/perdidos não apagados
            ])->throw()->json();

            $items = $resp['data'] ?? [];
            if (!$items) {
                break;
            }

            $stopEarly = false;

            foreach ($items as $deal) {
                // alguns deals podem vir sem update_time — usa add_time como fallback
                $updatedAt = $deal['update_time'] ?? $deal['add_time'] ?? null;
                $updated   = $updatedAt ? Carbon::parse($updatedAt)->utc() : null;

                if ($updated && $updated->greaterThanOrEqualTo($cutoff)) {
                    yield $deal;
                } else {
                    // como a lista está DESC por update_time, se este já ficou antigo, podemos parar
                    $stopEarly = true;
                    break;
                }
            }

            $more  = (bool)($resp['additional_data']['pagination']['more_items_in_collection'] ?? false);
            $start = (int)($resp['additional_data']['pagination']['next_start'] ?? 0);

            if ($stopEarly) {
                break;
            }
        } while ($more);
    }
}
