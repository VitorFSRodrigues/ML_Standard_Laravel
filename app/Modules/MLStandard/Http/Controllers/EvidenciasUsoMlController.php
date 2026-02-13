<?php

namespace App\Modules\MLStandard\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\MLStandard\Models\EvidenciaUsoMl;
use App\Modules\MLStandard\Models\EvidenciaUsoMlConfig;
use App\Modules\MLStandard\Models\OrcMLstd;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EvidenciasUsoMlController extends Controller
{
    public function index()
    {
        $this->ensureEvidenceRows();
        $this->refreshEvidenceItemCounts();

        $config = $this->currentConfig();

        return view('mlstandard::orc-mlstd.evidencias-uso', [
            'tempoEleMin' => (float) $config->tempo_levantamento_ele_min,
            'tempoTubMin' => (float) $config->tempo_levantamento_tub_min,
        ]);
    }

    public function updateTempos(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tempo_levantamento_ele_min' => ['required', 'numeric', 'min:0.01'],
            'tempo_levantamento_tub_min' => ['required', 'numeric', 'min:0.01'],
        ], [
            'tempo_levantamento_ele_min.required' => 'Informe o tempo de ELE.',
            'tempo_levantamento_ele_min.numeric' => 'O tempo de ELE deve ser numerico.',
            'tempo_levantamento_ele_min.min' => 'O tempo de ELE deve ser maior que zero.',
            'tempo_levantamento_tub_min.required' => 'Informe o tempo de TUB.',
            'tempo_levantamento_tub_min.numeric' => 'O tempo de TUB deve ser numerico.',
            'tempo_levantamento_tub_min.min' => 'O tempo de TUB deve ser maior que zero.',
        ]);

        $config = $this->currentConfig();
        $config->update([
            'tempo_levantamento_ele_min' => (float) $validated['tempo_levantamento_ele_min'],
            'tempo_levantamento_tub_min' => (float) $validated['tempo_levantamento_tub_min'],
        ]);
        $this->refreshEvidenceItemCounts();

        return redirect()
            ->route('mlstandard.orcamentos.evidencias-uso.index')
            ->with('success', 'Tempos medios atualizados com sucesso.');
    }

    private function currentConfig(): EvidenciaUsoMlConfig
    {
        return EvidenciaUsoMlConfig::query()->firstOrCreate(
            ['id' => 1],
            [
                'tempo_levantamento_ele_min' => 1.00,
                'tempo_levantamento_tub_min' => 1.00,
            ]
        );
    }

    private function ensureEvidenceRows(): void
    {
        $missingIds = OrcMLstd::query()
            ->leftJoin('evidencias_uso_ml as ev', 'ev.orc_ml_std_id', '=', 'orc_ml_std.id')
            ->whereNull('ev.id')
            ->pluck('orc_ml_std.id')
            ->all();

        if (empty($missingIds)) {
            return;
        }

        $rows = [];
        $now = now();

        foreach ($missingIds as $id) {
            $rows[] = [
                'orc_ml_std_id' => (int) $id,
                'qtd_itens_ele' => null,
                'qtd_itens_tub' => null,
                'data_modificacao' => null,
                'tempo_normal_hr' => null,
                'tempo_ml_hr' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        EvidenciaUsoMl::query()->upsert(
            $rows,
            ['orc_ml_std_id'],
            ['updated_at']
        );
    }

    private function refreshEvidenceItemCounts(): void
    {
        $config = $this->currentConfig();

        $statsByOrc = DB::table('orc_ml_std_itens as i')
            ->selectRaw("
                i.orc_ml_std_id as orc_ml_std_id,
                SUM(CASE WHEN i.disciplina = 'ELE' THEN 1 ELSE 0 END) as qtd_itens_ele,
                SUM(CASE WHEN i.disciplina = 'TUB' THEN 1 ELSE 0 END) as qtd_itens_tub,
                MAX(i.updated_at) as data_modificacao
            ")
            ->groupBy('i.orc_ml_std_id');

        $rows = OrcMLstd::query()
            ->joinSub($statsByOrc, 'st', function ($join): void {
                $join->on('st.orc_ml_std_id', '=', 'orc_ml_std.id');
            })
            ->join('evidencias_uso_ml as ev', 'ev.orc_ml_std_id', '=', 'orc_ml_std.id')
            ->selectRaw("
                orc_ml_std.id as orc_ml_std_id,
                COALESCE(st.qtd_itens_ele, 0) as qtd_itens_ele,
                COALESCE(st.qtd_itens_tub, 0) as qtd_itens_tub,
                st.data_modificacao as data_modificacao,
                orc_ml_std.created_at as orc_created_at,
                ev.data_modificacao as data_modificacao_atual,
                ev.tempo_normal_hr as tempo_normal_hr_atual,
                ev.tempo_ml_hr as tempo_ml_hr_atual
            ")
            ->get();

        if ($rows->isEmpty()) {
            return;
        }

        $now = now();
        $payload = [];

        foreach ($rows as $row) {
            $qtdEle = (int) $row->qtd_itens_ele;
            $qtdTub = (int) $row->qtd_itens_tub;
            if ($qtdEle === 0 && $qtdTub === 0) {
                continue;
            }

            $tempoNormalCalc = (($qtdEle * (float) $config->tempo_levantamento_ele_min)
                + ($qtdTub * (float) $config->tempo_levantamento_tub_min)) / 60;
            $tempoNormalHr = $tempoNormalCalc > 0
                ? round($tempoNormalCalc, 2)
                : ($row->tempo_normal_hr_atual !== null ? (float) $row->tempo_normal_hr_atual : null);

            $tempoMlHr = null;
            if (! empty($row->data_modificacao) && ! empty($row->orc_created_at)) {
                $seconds = \Carbon\Carbon::parse($row->orc_created_at)
                    ->diffInSeconds(\Carbon\Carbon::parse($row->data_modificacao), false);
                $seconds = max($seconds, 0);
                $tempoMlHr = round($seconds / 3600, 2);
            }

            if ($tempoMlHr === null || $tempoMlHr <= 0) {
                $tempoMlHr = $row->tempo_ml_hr_atual !== null ? (float) $row->tempo_ml_hr_atual : null;
            }

            $payload[] = [
                'orc_ml_std_id' => (int) $row->orc_ml_std_id,
                'qtd_itens_ele' => $qtdEle,
                'qtd_itens_tub' => $qtdTub,
                'data_modificacao' => $row->data_modificacao ?? $row->data_modificacao_atual,
                'tempo_normal_hr' => $tempoNormalHr,
                'tempo_ml_hr' => $tempoMlHr,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        EvidenciaUsoMl::query()->upsert(
            $payload,
            ['orc_ml_std_id'],
            ['qtd_itens_ele', 'qtd_itens_tub', 'data_modificacao', 'tempo_normal_hr', 'tempo_ml_hr', 'updated_at']
        );
    }
}
