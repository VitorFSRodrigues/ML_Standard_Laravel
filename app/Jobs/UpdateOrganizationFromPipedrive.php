<?php

namespace App\Jobs;

use App\Models\Cliente;
use App\Services\PipedriveService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class UpdateOrganizationFromPipedrive implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function __construct(public int $orgId, public ?string $action = null) {}

    public function handle(PipedriveService $svc): void
    {
        Log::info('UpdateOrganizationFromPipedrive:start', ['orgId' => $this->orgId, 'action' => $this->action]);

        $org = $svc->getOrganization($this->orgId);
        if (!$org) {
            Log::warning('UpdateOrganizationFromPipedrive:not_found', ['orgId' => $this->orgId]);
            return;
        }

        // mapeie como você já faz
        $map = $svc->mapOrgToCliente($org);

        // 1) upsert dos campos “sempre válidos”
        $cliente = Cliente::updateOrCreate(
            ['pipedrive_org_id' => $org['id']],
            [
                'nome_cliente'  => $map['nome_cliente'] ?? ($org['name'] ?? '—'),
                'nome_fantasia' => Str::before(($map['nome_cliente'] ?? ($org['name'] ?? '')), ' '),
            ]
        );

        // 2) endereço e afins apenas se vierem preenchidos
        $addr = [
            'endereco_completo' => $map['endereco_completo'] ?? ($org['address'] ?? null),
            'municipio'         => $map['municipio'] ?? null,
            'estado'            => $map['estado'] ?? null,
            'pais'              => $map['pais'] ?? null,
            'cnpj'              => $map['cnpj'] ?? null,
        ];
        $addr = array_filter($addr, fn($v) => !is_null($v));

        if (!empty($addr)) {
            $cliente->fill($addr)->save();
            Log::info('UpdateOrganizationFromPipedrive:address_applied', [
                'orgId'  => $this->orgId,
                'fields' => array_keys($addr),
            ]);
        }

        Log::info('UpdateOrganizationFromPipedrive:end', ['orgId' => $this->orgId, 'cliente_id' => $cliente->id]);
    }
}
