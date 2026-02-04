<?php

namespace Database\Seeders;

use App\Models\OrcMLstd;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OrcMLstdSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/StdSeeders/orc_treinados.xlsx');

        DB::transaction(function () use ($path) {
            $sheet = $this->loadSingleSheet($path);
            $highestRow = $sheet->getHighestRow();

            for ($r = 2; $r <= $highestRow; $r++) {
                $numero = $this->cellFormatted($sheet, "A{$r}");
                $rev = $this->cellInt($sheet, "B{$r}");
                $orcamentistaId = $this->cellInt($sheet, "C{$r}");

                if ($numero === '') {
                    continue;
                }

                OrcMLstd::updateOrCreate(
                    [
                        'numero_orcamento' => $numero,
                        'rev' => $rev,
                    ],
                    ['orcamentista_id' => $orcamentistaId > 0 ? $orcamentistaId : 2]
                );
            }

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

    private function cellFormatted(Worksheet $sheet, string $cell): string
    {
        $value = $sheet->getCell($cell)->getFormattedValue();
        if ($value === null || $value === '') {
            $value = $sheet->getCell($cell)->getValue();
        }

        return trim((string) $value);
    }

    private function cellInt(Worksheet $sheet, string $cell): int
    {
        $value = $sheet->getCell($cell)->getValue();
        if ($value === null || $value === '') {
            return 0;
        }

        return (int) $value;
    }
}
