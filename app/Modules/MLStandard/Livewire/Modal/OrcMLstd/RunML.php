<?php

namespace App\Modules\MLStandard\Livewire\Modal\OrcMLstd;

use App\Modules\MLStandard\Models\OrcMLstdItem;

use App\Modules\MLStandard\Models\StdELE;
use App\Modules\MLStandard\Models\StdEleTipo;
use App\Modules\MLStandard\Models\StdEleMaterial;
use App\Modules\MLStandard\Models\StdEleConexao;
use App\Modules\MLStandard\Models\StdEleEspessura;
use App\Modules\MLStandard\Models\StdEleExtremidade;
use App\Modules\MLStandard\Models\StdEleDimensao;

use App\Modules\MLStandard\Models\StdTUB;
use App\Modules\MLStandard\Models\StdTubTipo;
use App\Modules\MLStandard\Models\StdTubMaterial;
use App\Modules\MLStandard\Models\StdTubSchedule;
use App\Modules\MLStandard\Models\StdTubExtremidade;
use App\Modules\MLStandard\Models\StdTubDiametro;
use App\Modules\MLRetreinamentos\Models\ModeloMl;

use App\Services\MlFeedbackService;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use LivewireUI\Modal\ModalComponent;

class RunML extends ModalComponent
{
    public int $orcMLstdId;
    public string $disciplina; // ELE|TUB

    public int $startId;
    public int $endId;

    /** ==========================
     *  Caches (nome -> id)
     *  ========================== */
    private array $mapEleTipo = [];
    private array $mapEleMaterial = [];
    private array $mapEleConexao = [];
    private array $mapEleEspessura = [];
    private array $mapEleExtremidade = [];
    private array $mapEleDimensao = [];

    private array $mapTubTipo = [];
    private array $mapTubMaterial = [];
    private array $mapTubSchedule = [];
    private array $mapTubExtremidade = [];
    private array $mapTubDiametro = [];

    /** ==========================
     *  Caches (combinação -> valores)
     *  ========================== */
    private array $cacheEleStdByKey = [];
    private array $cacheTubValsByKey = []; // hh_un/kg_hh/kg_un/m2_un

    public static function destroyOnClose(): bool
    {
        return true;
    }

    public function mount(int $orcMLstdId, string $disciplina): void
    {
        $this->orcMLstdId = $orcMLstdId;
        $this->disciplina = strtoupper($disciplina);

        $min = OrcMLstdItem::where('orc_ml_std_id', $this->orcMLstdId)
            ->where('disciplina', $this->disciplina)
            ->min('id');

        $max = OrcMLstdItem::where('orc_ml_std_id', $this->orcMLstdId)
            ->where('disciplina', $this->disciplina)
            ->max('id');

        $this->startId = $min ?? 0;
        $this->endId   = $max ?? 0;
    }

    public function run(): void
    {
        $data = $this->validate([
            'startId' => ['required', 'integer', 'min:1'],
            'endId'   => ['required', 'integer', 'gte:startId'],
        ], [
            'startId.required' => 'Informe a linha inicial.',
            'endId.required'   => 'Informe a linha final.',
            'endId.gte'        => 'A linha final deve ser maior ou igual à inicial.',
        ]);

        // ✅ Itens no range (ordem fiel da importação)
        $items = OrcMLstdItem::query()
            ->where('orc_ml_std_id', $this->orcMLstdId)
            ->where('disciplina', $this->disciplina)
            ->whereBetween('id', [$data['startId'], $data['endId']])
            ->orderBy('ordem')
            ->get();

        if ($items->isEmpty()) {
            $this->addError('startId', 'Nenhuma linha encontrada nesse intervalo.');
            return;
        }

        // ✅ Pré-carrega caches nome->id (uma vez)
        $this->warmUpNameMaps();

        // chama API
        $baseUrl = config('services.ml_api.url', 'http://127.0.0.1:8001');
        $endpoint = $this->disciplina === 'ELE'
            ? '/AplicacaoMLELE'
            : '/AplicacaoMLTUB';

        $revisaoAtiva = ModeloMl::query()
            ->where('disciplina', $this->disciplina)
            ->where('is_current', true)
            ->value('revisao');

        $payload = [
            'textos' => $items->pluck('descricao')->values()->all(),
        ];

        if ($revisaoAtiva !== null) {
            $payload['revisao'] = (int) $revisaoAtiva;
        }

        $res = Http::timeout(120)->post($baseUrl . $endpoint, $payload);

        if (!$res->ok()) {
            $this->addError('startId', 'Erro ao executar ML. Verifique se a API está rodando.');
            return;
        }

        $results = $res->json('results') ?? $res->json();

        if (!is_array($results) || count($results) !== $items->count()) {
            $this->addError('startId', 'Retorno inesperado da API (quantidade divergente).');
            return;
        }

        $svc = app(MlFeedbackService::class);
        $userId = auth()->id();

        // ✅ Aplica tudo dentro de transaction
        DB::transaction(function () use ($items, $results, $svc, $userId) {
            foreach ($items->values() as $i => $item) {
                $r = $results[$i] ?? [];

                if ($this->disciplina === 'ELE') {
                    $this->applyEleOptimized($item, $r, $svc, $userId);
                } else {
                    $this->applyTubOptimized($item, $r, $svc, $userId);
                }
            }
        });

        $this->dispatch('reloadPowergrid');
        $this->closeModal();
    }

    /** =========================================
     *  Warmup caches nome -> id
     *  ========================================= */
    private function warmUpNameMaps(): void
    {
        if ($this->disciplina === 'ELE') {
            $this->mapEleTipo        = $this->pluckIdByNome(StdEleTipo::class);
            $this->mapEleMaterial    = $this->pluckIdByNome(StdEleMaterial::class);
            $this->mapEleConexao     = $this->pluckIdByNome(StdEleConexao::class);
            $this->mapEleEspessura   = $this->pluckIdByNome(StdEleEspessura::class);
            $this->mapEleExtremidade = $this->pluckIdByNome(StdEleExtremidade::class);
            $this->mapEleDimensao    = $this->pluckIdByNome(StdEleDimensao::class);
        } else {
            $this->mapTubTipo        = $this->pluckIdByNome(StdTubTipo::class);
            $this->mapTubMaterial    = $this->pluckIdByNome(StdTubMaterial::class);
            $this->mapTubSchedule    = $this->pluckIdByNome(StdTubSchedule::class);
            $this->mapTubExtremidade = $this->pluckIdByNome(StdTubExtremidade::class);
            $this->mapTubDiametro    = $this->pluckIdByNome(StdTubDiametro::class);
        }
    }

    private function pluckIdByNome(string $modelClass): array
    {
        // retorna array ['NOME' => id]
        return $modelClass::query()->pluck('id', 'nome')->toArray();
    }

    /** =========================================
     *  APPLY ELE (Otimizado)
     *  ========================================= */
    private function applyEleOptimized(
        OrcMLstdItem $item,
        array $r,
        MlFeedbackService $svc,
        ?int $userId
    ): void {
        $tipo        = $this->up($r['tipo'] ?? '');
        $material    = $this->up($r['material'] ?? '');
        $conexao     = $this->up($r['conexao'] ?? '');
        $espessura   = $this->up($r['espessura'] ?? '');
        $extremidade = $this->up($r['extremidade'] ?? '');
        $dimensao    = $this->up($r['dimensao'] ?? '');
        $prob        = trim((string)($r['prob'] ?? ''));

        // ✅ nome -> id (O(1) no array)
        $tipoId        = $this->mapEleTipo[$tipo] ?? null;
        $materialId    = $this->mapEleMaterial[$material] ?? null;
        $conexaoId     = $this->mapEleConexao[$conexao] ?? null;
        $espessuraId   = $this->mapEleEspessura[$espessura] ?? null;
        $extremidadeId = $this->mapEleExtremidade[$extremidade] ?? null;
        $dimensaoId    = $this->mapEleDimensao[$dimensao] ?? null;

        // ✅ busca STD por combinação com cache
        $stdValor = null;

        if ($tipoId && $materialId && $conexaoId && $espessuraId && $extremidadeId && $dimensaoId) {
            $key = $this->eleKey($tipoId, $materialId, $conexaoId, $espessuraId, $extremidadeId, $dimensaoId);

            if (array_key_exists($key, $this->cacheEleStdByKey)) {
                $stdValor = $this->cacheEleStdByKey[$key];
            } else {
                $stdValor = StdELE::query()
                    ->where('std_ele_tipo_id', $tipoId)
                    ->where('std_ele_material_id', $materialId)
                    ->where('std_ele_conexao_id', $conexaoId)
                    ->where('std_ele_espessura_id', $espessuraId)
                    ->where('std_ele_extremidade_id', $extremidadeId)
                    ->where('std_ele_dimensao_id', $dimensaoId)
                    ->value('std');

                // cache (pode ser null)
                $this->cacheEleStdByKey[$key] = $stdValor;
            }
        }

        $item->update([
            'std_ele_tipo_id'        => $tipoId,
            'std_ele_material_id'    => $materialId,
            'std_ele_conexao_id'     => $conexaoId,
            'std_ele_espessura_id'   => $espessuraId,
            'std_ele_extremidade_id' => $extremidadeId,
            'std_ele_dimensao_id'    => $dimensaoId,
            'prob'                   => $prob,
            'std_ele'                => $stdValor,
        ]);

        // ✅ feedback low confidence (sempre com UPPER)
        $predNames = [
            'tipo'        => $tipo,
            'material'    => $material,
            'conexao'     => $conexao,
            'espessura'   => $espessura,
            'extremidade' => $extremidade,
            'dimensao'    => $dimensao,
        ];

        $svc->storeLowConfidence($item, $predNames, $prob, $userId, 90);
    }

    /** =========================================
     *  APPLY TUB (Otimizado)
     *  ========================================= */
    private function applyTubOptimized(
        OrcMLstdItem $item,
        array $r,
        MlFeedbackService $svc,
        ?int $userId
    ): void {
        $tipo        = $this->up($r['tipo'] ?? '');
        $material    = $this->up($r['material'] ?? '');
        $schedule    = $this->up($r['schedule'] ?? '');
        $extremidade = $this->up($r['extremidade'] ?? '');
        $diametro    = $this->up($r['diametro'] ?? '');
        $prob        = trim((string)($r['prob'] ?? ''));

        $tipoId        = $this->mapTubTipo[$tipo] ?? null;
        $materialId    = $this->mapTubMaterial[$material] ?? null;
        $scheduleId    = $this->mapTubSchedule[$schedule] ?? null;
        $extremidadeId = $this->mapTubExtremidade[$extremidade] ?? null;
        $diametroId    = $this->mapTubDiametro[$diametro] ?? null;

        $hh_un = $kg_hh = $kg_un = $m2_un = null;

        // ✅ busca valores por combinação com cache
        if ($tipoId && $materialId && $scheduleId && $extremidadeId && $diametroId) {
            $key = $this->tubKey($tipoId, $materialId, $scheduleId, $extremidadeId, $diametroId);

            if (array_key_exists($key, $this->cacheTubValsByKey)) {
                [$hh_un, $kg_hh, $kg_un, $m2_un] = $this->cacheTubValsByKey[$key];
            } else {
                $row = StdTUB::query()
                    ->where('std_tub_tipo_id', $tipoId)
                    ->where('std_tub_material_id', $materialId)
                    ->where('std_tub_schedule_id', $scheduleId)
                    ->where('std_tub_extremidade_id', $extremidadeId)
                    ->where('std_tub_diametro_id', $diametroId)
                    ->first(['hh_un', 'kg_hh', 'kg_un', 'm2_un']);

                if ($row) {
                    $hh_un = $row->hh_un;
                    $kg_hh = $row->kg_hh;
                    $kg_un = $row->kg_un;
                    $m2_un = $row->m2_un;
                }

                $this->cacheTubValsByKey[$key] = [$hh_un, $kg_hh, $kg_un, $m2_un];
            }
        }

        $item->update([
            'std_tub_tipo_id'        => $tipoId,
            'std_tub_material_id'    => $materialId,
            'std_tub_schedule_id'    => $scheduleId,
            'std_tub_extremidade_id' => $extremidadeId,
            'std_tub_diametro_id'    => $diametroId,

            'prob'  => $prob,
            'hh_un' => $hh_un,
            'kg_hh' => $kg_hh,
            'kg_un' => $kg_un,
            'm2_un' => $m2_un,
        ]);

        $predNames = [
            'tipo'        => $tipo,
            'material'    => $material,
            'schedule'    => $schedule,
            'extremidade' => $extremidade,
            'diametro'    => $diametro,
        ];

        $svc->storeLowConfidence($item, $predNames, $prob, $userId, 90);
    }

    /** =========================================
     *  Keys de cache
     *  ========================================= */
    private function eleKey($tipoId, $materialId, $conexaoId, $espessuraId, $extremidadeId, $dimensaoId): string
    {
        return implode('|', [
            (int)$tipoId,
            (int)$materialId,
            (int)$conexaoId,
            (int)$espessuraId,
            (int)$extremidadeId,
            (int)$dimensaoId,
        ]);
    }

    private function tubKey($tipoId, $materialId, $scheduleId, $extremidadeId, $diametroId): string
    {
        return implode('|', [
            (int)$tipoId,
            (int)$materialId,
            (int)$scheduleId,
            (int)$extremidadeId,
            (int)$diametroId,
        ]);
    }

    private function up(?string $v): string
    {
        return mb_strtoupper(trim((string) $v), 'UTF-8');
    }

    public function render()
    {
        return view('mlstandard::livewire.modal.orc-mlstd.run-ml');
    }
}


