<?php

namespace Database\Seeders;

use App\Modules\MLRetreinamentos\Models\MlFeedbackSample;
use App\Modules\MLStandard\Models\OrcMLstd;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MlFeedbackSamplesSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/StdSeeders/Treinar_modelo.xlsx');

        DB::transaction(function () use ($path) {
            $sheet = $this->loadSingleSheet($path);
            $orcMap = $this->buildOrcMap();

            $highestRow = $sheet->getHighestRow();

            for ($r = 2; $r <= $highestRow; $r++) {
                $disciplina = $this->up($this->cell($sheet, "A{$r}"));
                $numero = $this->cellFormatted($sheet, "B{$r}");
                $descricao = trim((string) $sheet->getCell("C{$r}")->getValue());

                if ($disciplina === '' || $numero === '' || $descricao === '') {
                    continue;
                }

                $orcId = $orcMap[$numero] ?? null;
                if (!$orcId) {
                    $this->command?->warn("OrcMLstd nao encontrado: {$numero} (linha {$r}).");
                    continue;
                }

                $mlPred = $this->buildPredJson($disciplina, $sheet, $r);
                if ($mlPred === null) {
                    $this->command?->warn("Disciplina invalida: {$disciplina} (linha {$r}).");
                    continue;
                }

                MlFeedbackSample::updateOrCreate(
                    [
                        'orc_ml_std_id' => $orcId,
                        'disciplina' => $disciplina,
                        'descricao_original' => $descricao,
                        'orc_ml_std_item_id' => null,
                    ],
                    [
                        'ml_pred_json' => $mlPred,
                        'reason' => MlFeedbackSample::REASON_BOTH,
                        'status' => MlFeedbackSample::STATUS_TREINADO,
                    ]
                );
            }

            $this->freeSheet($sheet);
        });
    }

    private function buildPredJson(string $disciplina, Worksheet $sheet, int $row): ?array
    {
        if ($disciplina === 'ELE') {
            return [
                'tipo' => $this->up($this->cell($sheet, "D{$row}")),
                'material' => $this->up($this->cell($sheet, "E{$row}")),
                'conexao' => $this->up($this->cell($sheet, "F{$row}")),
                'espessura' => $this->up($this->cell($sheet, "G{$row}")),
                'extremidade' => $this->up($this->cell($sheet, "H{$row}")),
                'dimensao' => $this->up($this->cell($sheet, "I{$row}")),
            ];
        }

        if ($disciplina === 'TUB') {
            return [
                'tipo' => $this->up($this->cell($sheet, "J{$row}")),
                'material' => $this->up($this->cell($sheet, "K{$row}")),
                'schedule' => $this->up($this->cell($sheet, "L{$row}")),
                'extremidade' => $this->up($this->cell($sheet, "M{$row}")),
                'diametro' => $this->up($this->cell($sheet, "N{$row}")),
            ];
        }

        return null;
    }

    private function buildOrcMap(): array
    {
        $map = [];

        OrcMLstd::query()
            ->select('id', 'numero_orcamento', 'rev')
            ->orderByDesc('rev')
            ->orderByDesc('id')
            ->chunk(1000, function ($rows) use (&$map) {
                foreach ($rows as $row) {
                    $numero = trim((string) $row->numero_orcamento);
                    if ($numero === '' || isset($map[$numero])) {
                        continue;
                    }

                    $map[$numero] = (int) $row->id;
                }
            });

        return $map;
    }

    private function loadSingleSheet(string $path): Worksheet
    {
        $reader = IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true);
        $sheetNames = $reader->listWorksheetNames($path);
        $reader->setLoadSheetsOnly([$sheetNames[0] ?? 'Sheet1']);

        $spreadsheet = $reader->load($path);
        $sheet = $spreadsheet->getSheet(0);

        $sheet->_parent = $spreadsheet;

        return $sheet;
    }

    private function freeSheet(Worksheet $sheet): void
    {
        $spreadsheet = $sheet->_parent ?? null;

        if ($spreadsheet) {
            $spreadsheet->disconnectWorksheets();
        }

        unset($sheet);
        unset($spreadsheet);

        gc_collect_cycles();
    }

    private function cell(Worksheet $sheet, string $cell): string
    {
        return trim((string) $sheet->getCell($cell)->getValue());
    }

    private function cellFormatted(Worksheet $sheet, string $cell): string
    {
        $value = $sheet->getCell($cell)->getFormattedValue();
        if ($value === null || $value === '') {
            $value = $sheet->getCell($cell)->getValue();
        }

        return trim((string) $value);
    }

    private function up(?string $value): string
    {
        return mb_strtoupper(trim((string) $value), 'UTF-8');
    }
}
