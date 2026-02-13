<?php

namespace App\Modules\MLStandard\Livewire\Modal\StdTUB;

use App\Modules\MLStandard\Models\StdTUB;
use App\Modules\MLStandard\Models\StdTubTipo;
use App\Modules\MLStandard\Models\StdTubMaterial;
use App\Modules\MLStandard\Models\StdTubSchedule;
use App\Modules\MLStandard\Models\StdTubExtremidade;
use App\Modules\MLStandard\Models\StdTubDiametro;
use Illuminate\Support\Facades\DB;
use Livewire\WithFileUploads;
use LivewireUI\Modal\ModalComponent;
use PhpOffice\PhpSpreadsheet\IOFactory;

class Import extends ModalComponent
{
    use WithFileUploads;

    public $file;

    public static function destroyOnClose(): bool
    {
        return true;
    }

    private function normalizeUpper(?string $txt): string
    {
        $txt = trim((string) $txt);
        $txt = mb_strtoupper($txt, 'UTF-8');
        $txt = preg_replace('/\s+/', ' ', $txt);
        return $txt ?? '';
    }

    private function normalizePercent($value): float
    {
        if ($value === null || $value === '') return 0.0;

        $raw = trim((string) $value);
        $hasPercent = str_contains($raw, '%');

        $raw = str_replace(['%', ' '], '', $raw);

        if (str_contains($raw, ',')) {
            $raw = str_replace('.', '', $raw);
            $raw = str_replace(',', '.', $raw);
        }

        $num = is_numeric($raw) ? (float) $raw : 0.0;

        if ($hasPercent || $num > 1) {
            $num = $num / 100;
        }

        if ($num < 0) $num = 0;
        if ($num > 1) $num = 1;

        return round($num, 6);
    }

    private function percentFields(): array
    {
        return [
            'encarregado_mecanica','encarregado_tubulacao','encarregado_eletrica','encarregado_andaime','encarregado_civil',
            'lider',
            'mecanico_ajustador','mecanico_montador','encanador','caldeireiro','lixador','montador',
            'soldador_er','soldador_tig','soldador_mig',
            'ponteador',
            'eletricista_controlista','eletricista_montador','instrumentista',
            'montador_de_andaime','pintor','jatista','pedreiro','carpinteiro','armador','ajudante',
        ];
    }

    private function resolveOrCreate(string $modelClass, string $nome): int
    {
        $row = $modelClass::query()->firstOrCreate(['nome' => $nome]);
        return (int) $row->id;
    }

    public function import(): void
    {
        $this->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls'],
        ], [
            'file.required' => 'Selecione um arquivo Excel.',
            'file.mimes' => 'Formato inválido. Use XLSX.',
        ]);

        $path = $this->file->getRealPath();

        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);

        if (count($rows) < 2) {
            $this->addError('file', 'Arquivo vazio.');
            return;
        }

        // Header (linha 1)
        $header = array_map(fn($h) => $this->normalizeUpper($h), $rows[1]);

        // ✅ esperamos colunas principais
        // TIPO | MATERIAL | SCHEDULE | EXTREMIDADE | DIÂMETRO | HH/UN | KG/HH | KG/UN | M2/UN | (cargos...)
        $map = array_flip($header);

        $required = ['TIPO','MATERIAL','SCHEDULE','EXTREMIDADE','DIÂMETRO','HH/UN','KG/HH','KG/UN','M2/UN'];
        foreach ($required as $req) {
            if (!isset($map[$req])) {
                $this->addError('file', "Coluna obrigatória ausente no Excel: {$req}");
                return;
            }
        }

        DB::beginTransaction();

        try {
            $inserted = 0;
            $updated = 0;

            foreach ($rows as $idx => $line) {
                if ($idx === 1) continue; // header

                $tipoNome   = $this->normalizeUpper($line[$map['TIPO']] ?? '');
                $matNome    = $this->normalizeUpper($line[$map['MATERIAL']] ?? '');
                $schNome    = $this->normalizeUpper($line[$map['SCHEDULE']] ?? '');
                $extNome    = $this->normalizeUpper($line[$map['EXTREMIDADE']] ?? '');
                $diaNome    = $this->normalizeUpper($line[$map['DIÂMETRO']] ?? '');

                // pula linha vazia
                if (!$tipoNome && !$matNome && !$schNome && !$extNome && !$diaNome) {
                    continue;
                }

                $tipoId = $this->resolveOrCreate(StdTubTipo::class, $tipoNome);
                $matId  = $this->resolveOrCreate(StdTubMaterial::class, $matNome);
                $schId  = $this->resolveOrCreate(StdTubSchedule::class, $schNome);
                $extId  = $this->resolveOrCreate(StdTubExtremidade::class, $extNome);
                $diaId  = $this->resolveOrCreate(StdTubDiametro::class, $diaNome);

                $payload = [
                    'std_tub_tipo_id' => $tipoId,
                    'std_tub_material_id' => $matId,
                    'std_tub_schedule_id' => $schId,
                    'std_tub_extremidade_id' => $extId,
                    'std_tub_diametro_id' => $diaId,

                    'hh_un' => (float) ($line[$map['HH/UN']] ?? 0),
                    'kg_hh' => (float) ($line[$map['KG/HH']] ?? 0),
                    'kg_un' => (float) ($line[$map['KG/UN']] ?? 0),
                    'm2_un' => (float) ($line[$map['M2/UN']] ?? 0),
                ];

                // cargos (se existirem no Excel)
                $sum = 0.0;
                foreach ($this->percentFields() as $f) {
                    $colName = $this->normalizeUpper(str_replace('_', ' ', $f));
                    // no export você provavelmente vai usar o nome real, então tentamos achar por aproximação
                    // (se quiser eu deixo um mapa fixo do export pra ficar 100% determinístico)
                    $foundKey = null;
                    foreach ($header as $k => $h) {
                        if (str_contains($h, strtoupper(str_replace('_', ' ', $f)))) {
                            $foundKey = $k;
                            break;
                        }
                    }

                    $val = $foundKey ? ($line[$foundKey] ?? 0) : 0;
                    $payload[$f] = $this->normalizePercent($val);
                    $sum += $payload[$f];
                }

                if (abs($sum - 1.0) > 0.0001) {
                    DB::rollBack();
                    $this->addError('file', "Linha {$idx}: soma dos cargos precisa ser 100% (atual: " . number_format($sum*100, 2, ',', '.') . "%).");
                    return;
                }

                // upsert por combinação
                $existing = StdTUB::query()
                    ->where('std_tub_tipo_id', $tipoId)
                    ->where('std_tub_material_id', $matId)
                    ->where('std_tub_schedule_id', $schId)
                    ->where('std_tub_extremidade_id', $extId)
                    ->where('std_tub_diametro_id', $diaId)
                    ->first();

                if ($existing) {
                    $existing->update($payload);
                    $updated++;
                } else {
                    StdTUB::create($payload);
                    $inserted++;
                }
            }

            DB::commit();

            $this->dispatch('reloadPowergrid');
            $this->closeModal();

        } catch (\Throwable $e) {
            DB::rollBack();
            $this->addError('file', 'Erro ao importar: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('mlstandard::livewire.modal.std-tub.import');
    }
}


