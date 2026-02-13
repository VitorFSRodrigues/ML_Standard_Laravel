<?php

namespace Database\Seeders;

use App\Modules\MLStandard\Models\EvidenciaUsoMl;
use App\Modules\MLStandard\Models\OrcMLstd;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EvidenciasUsoMlSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/StdSeeders/evidencias_uso_ML.xlsx');

        DB::transaction(function () use ($path) {
            $orcMap = $this->mapOrcamentoToId();

            $sheet = $this->loadSingleSheet($path);
            $highestRow = $sheet->getHighestRow();

            for ($r = 2; $r <= $highestRow; $r++) {
                $numeroOrcamento = $this->cell($sheet, "A{$r}");
                if ($numeroOrcamento === '') {
                    continue;
                }

                $rev = $this->cellIntNullable($sheet, "B{$r}") ?? 0;
                $key = $this->mapKey($numeroOrcamento, $rev);
                $orcId = $orcMap[$key] ?? null;
                if (!$orcId) {
                    continue;
                }

                $qtdEle = $this->cellIntNullable($sheet, "C{$r}");
                $qtdTub = $this->cellIntNullable($sheet, "D{$r}");
                $dataModificacao = $this->cellDateTime($sheet, "E{$r}");
                $tempoNormalHr = $this->cellFloat($sheet, "F{$r}");
                $tempoMlHr = $this->cellFloat($sheet, "G{$r}");

                /** @var EvidenciaUsoMl $row */
                $row = EvidenciaUsoMl::query()->firstOrNew([
                    'orc_ml_std_id' => $orcId,
                ]);

                $row->qtd_itens_ele = $qtdEle;
                $row->qtd_itens_tub = $qtdTub;
                $row->data_modificacao = $dataModificacao;
                $row->tempo_normal_hr = $tempoNormalHr;
                $row->tempo_ml_hr = $tempoMlHr;

                if (!$row->exists) {
                    $row->created_at = now();
                }

                $row->updated_at = $dataModificacao ? Carbon::parse($dataModificacao) : now();
                $row->save();
            }

            $this->freeSheet($sheet);
        });
    }

    private function mapOrcamentoToId(): array
    {
        $map = [];

        $rows = OrcMLstd::query()
            ->select(['id', 'numero_orcamento', 'rev'])
            ->orderByDesc('rev')
            ->orderByDesc('id')
            ->get();

        foreach ($rows as $row) {
            $numero = (string) $row->numero_orcamento;
            $rev = (int) $row->rev;
            $key = $this->mapKey($numero, $rev);
            if ($numero === '' || isset($map[$key])) {
                continue;
            }

            $map[$key] = (int) $row->id;
        }

        return $map;
    }

    private function mapKey(string $numeroOrcamento, int $rev): string
    {
        return trim($numeroOrcamento) . '|' . $rev;
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

    private function cellIntNullable(Worksheet $sheet, string $cell): ?int
    {
        $value = $sheet->getCell($cell)->getValue();
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    private function cellFloat(Worksheet $sheet, string $cell): ?float
    {
        $value = $sheet->getCell($cell)->getValue();
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $raw = str_replace(',', '.', trim((string) $value));

        return is_numeric($raw) ? (float) $raw : null;
    }

    private function cellDateTime(Worksheet $sheet, string $cell): ?string
    {
        $value = $sheet->getCell($cell)->getValue();

        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->format('Y-m-d H:i:s');
        }

        if (is_numeric($value)) {
            return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d H:i:s');
        }

        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }

        try {
            return Carbon::parse($raw)->format('Y-m-d H:i:s');
        } catch (\Throwable $e) {
            return null;
        }
    }
}
