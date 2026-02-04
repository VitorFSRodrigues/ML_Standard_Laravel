<?php

namespace Database\Seeders;

use App\Models\ModeloMl;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ModelosMlSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/StdSeeders/modelos_ml.xlsx');

        DB::transaction(function () use ($path) {
            $sheet = $this->loadSingleSheet($path);
            $highestRow = $sheet->getHighestRow();

            for ($r = 2; $r <= $highestRow; $r++) {
                $disciplina = $this->up($this->cell($sheet, "A{$r}"));
                $data = $this->cellDate($sheet, "B{$r}");
                $revisao = $this->cellInt($sheet, "C{$r}");
                $acuracia = $this->cellFloat($sheet, "D{$r}");

                if ($disciplina === '' || $data === null) {
                    continue;
                }

                ModeloMl::updateOrCreate(
                    [
                        'disciplina' => $disciplina,
                        'revisao' => $revisao,
                    ],
                    [
                        'data' => $data,
                        'acuracia' => $acuracia,
                        'treino_exact_match_ratio' => $acuracia,
                    ]
                );
            }

            // Define os modelos atuais (apenas ELE r31 e TUB r30)
            ModeloMl::query()->update(['is_current' => false]);
            ModeloMl::query()
                ->where('disciplina', 'ELE')
                ->where('revisao', 31)
                ->update(['is_current' => true]);
            ModeloMl::query()
                ->where('disciplina', 'TUB')
                ->where('revisao', 30)
                ->update(['is_current' => true]);

            $this->freeSheet($sheet);
        });
    }

    /**
     * Carrega apenas a primeira aba (economiza memoria).
     */
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

    private function cellInt(Worksheet $sheet, string $cell): int
    {
        $value = $sheet->getCell($cell)->getValue();
        if ($value === null || $value === '') {
            return 0;
        }

        return (int) $value;
    }

    private function cellFloat(Worksheet $sheet, string $cell): ?float
    {
        $value = $sheet->getCell($cell)->getValue();
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (float) $value : null;
    }

    private function cellDate(Worksheet $sheet, string $cell): ?string
    {
        $value = $sheet->getCell($cell)->getValue();

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (is_numeric($value)) {
            return ExcelDate::excelToDateTimeObject($value)->format('Y-m-d');
        }

        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function up(?string $value): string
    {
        return mb_strtoupper(trim((string) $value), 'UTF-8');
    }
}
