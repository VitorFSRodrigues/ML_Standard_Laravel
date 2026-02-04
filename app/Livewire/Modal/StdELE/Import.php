<?php

namespace App\Livewire\Modal\StdELE;

use App\Models\StdELE;
use App\Models\StdEleTipo;
use App\Models\StdEleMaterial;
use App\Models\StdEleConexao;
use App\Models\StdEleEspessura;
use App\Models\StdEleExtremidade;
use App\Models\StdEleDimensao;
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

    /**
     * "10,05%" -> 0.1005
     * "10,05"  -> 0.1005
     * "0,1005" -> 0.1005
     */
    private function normalizePercent($value): float
    {
        if ($value === null || $value === '') return 0.0;

        $raw = trim((string) $value);
        $hasPercent = str_contains($raw, '%');

        $raw = str_replace(['%', ' '], '', $raw);

        // BR format
        if (str_contains($raw, ',')) {
            $raw = str_replace('.', '', $raw);
            $raw = str_replace(',', '.', $raw);
        }

        $num = is_numeric($raw) ? (float) $raw : 0.0;

        // 10 ou 10% => divide por 100
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

    /**
     * Mapa FIXO das colunas do Excel (mesmo modelo do export)
     */
    private function expectedColumns(): array
    {
        return [
            'TIPO',
            'MATERIAL',
            'CONEXÃO',
            'ESPESSURA',
            'EXTREMIDADE',
            'DIMENSÃO',
            'STD',

            'ENCARREGADO MECÂNICA',
            'ENCARREGADO TUBULAÇÃO',
            'ENCARREGADO ELÉTRICA',
            'ENCARREGADO ANDAIME',
            'ENCARREGADO CIVIL',

            'LÍDER',

            'MECÂNICO AJUSTADOR',
            'MECÂNICO MONTADOR',
            'ENCANADOR',
            'CALDEIREIRO',
            'LIXADOR',
            'MONTADOR',

            'SOLDADOR ER',
            'SOLDADOR TIG',
            'SOLDADOR MIG',

            'PONTEADOR',

            'ELETRICISTA CONTROLISTA',
            'ELETRICISTA MONTADOR',
            'INSTRUMENTISTA',

            'MONTADOR DE ANDAIME',
            'PINTOR',
            'JATISTA',
            'PEDREIRO',
            'CARPINTEIRO',
            'ARMADOR',
            'AJUDANTE',
        ];
    }

    public function import(): void
    {
        $this->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls'],
        ], [
            'file.required' => 'Selecione um arquivo Excel.',
            'file.mimes' => 'Formato inválido. Use XLSX.',
        ]);

        $spreadsheet = IOFactory::load($this->file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);

        if (count($rows) < 2) {
            $this->addError('file', 'Arquivo vazio.');
            return;
        }

        // Header row (linha 1)
        $headerRaw = $rows[1];
        $header = [];
        foreach ($headerRaw as $k => $v) {
            $header[$k] = $this->normalizeUpper($v);
        }

        $map = array_flip($header);

        // valida colunas obrigatórias
        foreach ($this->expectedColumns() as $colName) {
            if (!isset($map[$colName])) {
                $this->addError('file', "Coluna obrigatória ausente: {$colName}");
                return;
            }
        }

        DB::beginTransaction();

        try {
            foreach ($rows as $idx => $line) {
                if ($idx === 1) continue; // header

                $tipoNome = $this->normalizeUpper($line[$map['TIPO']] ?? '');
                $matNome  = $this->normalizeUpper($line[$map['MATERIAL']] ?? '');
                $conNome  = $this->normalizeUpper($line[$map['CONEXÃO']] ?? '');
                $espNome  = $this->normalizeUpper($line[$map['ESPESSURA']] ?? '');
                $extNome  = $this->normalizeUpper($line[$map['EXTREMIDADE']] ?? '');
                $dimNome  = $this->normalizeUpper($line[$map['DIMENSÃO']] ?? '');

                // linha vazia = pula
                if (!$tipoNome && !$matNome && !$conNome && !$espNome && !$extNome && !$dimNome) {
                    continue;
                }

                // resolve IDs (criando caso não exista)
                $tipoId = $this->resolveOrCreate(StdEleTipo::class, $tipoNome);
                $matId  = $this->resolveOrCreate(StdEleMaterial::class, $matNome);
                $conId  = $this->resolveOrCreate(StdEleConexao::class, $conNome);
                $espId  = $this->resolveOrCreate(StdEleEspessura::class, $espNome);
                $extId  = $this->resolveOrCreate(StdEleExtremidade::class, $extNome);
                $dimId  = $this->resolveOrCreate(StdEleDimensao::class, $dimNome);

                $payload = [
                    'std_ele_tipo_id'        => $tipoId,
                    'std_ele_material_id'    => $matId,
                    'std_ele_conexao_id'     => $conId,
                    'std_ele_espessura_id'   => $espId,
                    'std_ele_extremidade_id' => $extId,
                    'std_ele_dimensao_id'    => $dimId,
                    'std' => (float) ($line[$map['STD']] ?? 0),
                ];

                // cargos / percentuais (precisa somar 100%)
                $sum = 0.0;

                // Mapa determinístico: coluna excel -> campo do banco
                $percentMap = [
                    'ENCARREGADO MECÂNICA'   => 'encarregado_mecanica',
                    'ENCARREGADO TUBULAÇÃO'  => 'encarregado_tubulacao',
                    'ENCARREGADO ELÉTRICA'   => 'encarregado_eletrica',
                    'ENCARREGADO ANDAIME'    => 'encarregado_andaime',
                    'ENCARREGADO CIVIL'      => 'encarregado_civil',

                    'LÍDER' => 'lider',

                    'MECÂNICO AJUSTADOR' => 'mecanico_ajustador',
                    'MECÂNICO MONTADOR'  => 'mecanico_montador',
                    'ENCANADOR'          => 'encanador',
                    'CALDEIREIRO'        => 'caldeireiro',
                    'LIXADOR'            => 'lixador',
                    'MONTADOR'           => 'montador',

                    'SOLDADOR ER'  => 'soldador_er',
                    'SOLDADOR TIG' => 'soldador_tig',
                    'SOLDADOR MIG' => 'soldador_mig',

                    'PONTEADOR' => 'ponteador',

                    'ELETRICISTA CONTROLISTA' => 'eletricista_controlista',
                    'ELETRICISTA MONTADOR'    => 'eletricista_montador',
                    'INSTRUMENTISTA'          => 'instrumentista',

                    'MONTADOR DE ANDAIME' => 'montador_de_andaime',
                    'PINTOR'              => 'pintor',
                    'JATISTA'             => 'jatista',
                    'PEDREIRO'            => 'pedreiro',
                    'CARPINTEIRO'         => 'carpinteiro',
                    'ARMADOR'             => 'armador',
                    'AJUDANTE'            => 'ajudante',
                ];

                foreach ($percentMap as $excelCol => $dbField) {
                    $val = $line[$map[$excelCol]] ?? 0;
                    $payload[$dbField] = $this->normalizePercent($val);
                    $sum += $payload[$dbField];
                }

                if (abs($sum - 1.0) > 0.0001) {
                    DB::rollBack();
                    $this->addError(
                        'file',
                        "Linha {$idx}: soma dos cargos precisa ser 100% (atual: " . number_format($sum * 100, 2, ',', '.') . "%)."
                    );
                    return;
                }

                // UPSERT por combinação única
                $existing = StdELE::query()
                    ->where('std_ele_tipo_id', $tipoId)
                    ->where('std_ele_material_id', $matId)
                    ->where('std_ele_conexao_id', $conId)
                    ->where('std_ele_espessura_id', $espId)
                    ->where('std_ele_extremidade_id', $extId)
                    ->where('std_ele_dimensao_id', $dimId)
                    ->first();

                if ($existing) {
                    $existing->update($payload);
                } else {
                    StdELE::create($payload);
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
        return view('livewire.modal.std-ele.import');
    }
}
