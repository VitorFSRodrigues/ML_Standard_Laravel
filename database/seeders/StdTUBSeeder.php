<?php

namespace Database\Seeders;

use App\Models\StdTUB;
use App\Models\StdTubTipo;
use App\Models\StdTubMaterial;
use App\Models\StdTubSchedule;
use App\Models\StdTubExtremidade;
use App\Models\StdTubDiametro;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StdTUBSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/StdSeeders/Std_tub.xlsx');

        DB::transaction(function () use ($path) {
            $this->seedSimpleSheet($path, 'TIPO', StdTubTipo::class);
            $this->seedSimpleSheet($path, 'MATERIAL', StdTubMaterial::class);
            $this->seedSimpleSheet($path, 'SCHEDULE', StdTubSchedule::class);
            $this->seedSimpleSheet($path, 'EXTREMIDADE', StdTubExtremidade::class);
            $this->seedSimpleSheet($path, 'DIAMETRO', StdTubDiametro::class);

            $this->seedStdSheet($path, 'STD');
        });
    }

    private function seedSimpleSheet(string $path, string $sheetName, string $modelClass): void
    {
        $sheet = $this->loadSingleSheet($path, $sheetName);
        $highestRow = $sheet->getHighestRow();

        for ($r = 2; $r <= $highestRow; $r++) {
            $colA = trim((string) $sheet->getCell("A{$r}")->getValue());
            $colB = trim((string) $sheet->getCell("B{$r}")->getValue());

            // ✅ caso a aba tenha ID + NOME
            if (is_numeric($colA) && $colB !== '') {
                $modelClass::updateOrCreate(
                    ['id' => (int) $colA],
                    ['nome' => $colB]
                );
                continue;
            }

            // ✅ caso seja só nome na coluna A
            $nome = $colA;
            if ($nome === '') continue;

            $modelClass::updateOrCreate(['nome' => $nome], ['nome' => $nome]);
        }

        $this->freeSheet($sheet);
    }

    private function seedStdSheet(string $path, string $sheetName): void
    {
        $sheet = $this->loadSingleSheet($path, $sheetName);
        $highestRow = $sheet->getHighestRow();

        for ($r = 2; $r <= $highestRow; $r++) {

            // ✅ manter o padrão: STD usa ID_* no Excel
            // Assumindo: A=ID_TIPO, B=ID_MATERIAL, C=ID_SCHEDULE, D=ID_EXTREMIDADE, E=ID_DIAMETRO
            $tipoId        = (int) $sheet->getCell("A{$r}")->getValue();
            $materialId    = (int) $sheet->getCell("B{$r}")->getValue();
            $scheduleId    = (int) $sheet->getCell("C{$r}")->getValue();
            $extremidadeId = (int) $sheet->getCell("D{$r}")->getValue();
            $diametroId    = (int) $sheet->getCell("E{$r}")->getValue();

            // ✅ pula linha incompleta (evita FK error)
            if ($tipoId <= 0 || $materialId <= 0 || $scheduleId <= 0 || $extremidadeId <= 0 || $diametroId <= 0) {
                continue;
            }

            // F=STD
            $data = [
                'std_tub_tipo_id'         => $tipoId,
                'std_tub_material_id'     => $materialId,
                'std_tub_schedule_id'     => $scheduleId,
                'std_tub_extremidade_id'  => $extremidadeId,
                'std_tub_diametro_id'     => $diametroId,

                'hh_un'                   => $this->num((string) $sheet->getCell("F{$r}")->getValue()),
                'kg_hh'                   => $this->num((string) $sheet->getCell("G{$r}")->getValue()),
                'kg_un'                   => $this->num((string) $sheet->getCell("H{$r}")->getValue()),
                'm2_un'                   => $this->num((string) $sheet->getCell("I{$r}")->getValue()),

                // A partir daqui é o mesmo bloco de cargos:
                'encarregado_mecanica'    => $this->num((string) $sheet->getCell("J{$r}")->getValue()),
                'encarregado_tubulacao'   => $this->num((string) $sheet->getCell("K{$r}")->getValue()),
                'encarregado_eletrica'    => $this->num((string) $sheet->getCell("L{$r}")->getValue()),
                'encarregado_andaime'     => $this->num((string) $sheet->getCell("M{$r}")->getValue()),
                'encarregado_civil'       => $this->num((string) $sheet->getCell("N{$r}")->getValue()),

                'lider'                   => $this->num((string) $sheet->getCell("O{$r}")->getValue()),

                'mecanico_ajustador'      => $this->num((string) $sheet->getCell("P{$r}")->getValue()),
                'mecanico_montador'       => $this->num((string) $sheet->getCell("Q{$r}")->getValue()),
                'encanador'               => $this->num((string) $sheet->getCell("R{$r}")->getValue()),
                'caldeireiro'             => $this->num((string) $sheet->getCell("S{$r}")->getValue()),
                'lixador'                 => $this->num((string) $sheet->getCell("T{$r}")->getValue()),
                'montador'                => $this->num((string) $sheet->getCell("U{$r}")->getValue()),

                'soldador_er'             => $this->num((string) $sheet->getCell("V{$r}")->getValue()),
                'soldador_tig'            => $this->num((string) $sheet->getCell("W{$r}")->getValue()),
                'soldador_mig'            => $this->num((string) $sheet->getCell("X{$r}")->getValue()),

                'ponteador'               => $this->num((string) $sheet->getCell("Y{$r}")->getValue()),

                'eletricista_controlista' => $this->num((string) $sheet->getCell("Z{$r}")->getValue()),
                'eletricista_montador'    => $this->num((string) $sheet->getCell("AA{$r}")->getValue()),
                'instrumentista'          => $this->num((string) $sheet->getCell("AB{$r}")->getValue()),

                'montador_de_andaime'     => $this->num((string) $sheet->getCell("AC{$r}")->getValue()),
                'pintor'                  => $this->num((string) $sheet->getCell("AD{$r}")->getValue()),
                'jatista'                 => $this->num((string) $sheet->getCell("AE{$r}")->getValue()),
                'pedreiro'                => $this->num((string) $sheet->getCell("AF{$r}")->getValue()),
                'carpinteiro'             => $this->num((string) $sheet->getCell("AG{$r}")->getValue()),
                'armador'                 => $this->num((string) $sheet->getCell("AH{$r}")->getValue()),
                'ajudante'                => $this->num((string) $sheet->getCell("AI{$r}")->getValue()),
            ];

            StdTUB::updateOrCreate([
                'std_tub_tipo_id'        => $tipoId,
                'std_tub_material_id'    => $materialId,
                'std_tub_schedule_id'    => $scheduleId,
                'std_tub_extremidade_id' => $extremidadeId,
                'std_tub_diametro_id'    => $diametroId,
            ], $data);
        }

        $this->freeSheet($sheet);
    }

    private function loadSingleSheet(string $path, string $sheetName): Worksheet
    {
        $reader = IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true);
        $reader->setLoadSheetsOnly([$sheetName]);

        $spreadsheet = $reader->load($path);
        $sheet = $spreadsheet->getSheetByName($sheetName);

        // para liberar depois
        $sheet->_parent = $spreadsheet;

        return $sheet;
    }

    private function freeSheet(Worksheet $sheet): void
    {
        $spreadsheet = $sheet->_parent ?? null;

        if ($spreadsheet) {
            $spreadsheet->disconnectWorksheets();
        }

        unset($sheet, $spreadsheet);
        gc_collect_cycles();
    }

    private function num(string $value): float
    {
        $value = trim($value);
        if ($value === '') return 0.0;

        $value = str_replace(' ', '', $value);

        if (str_contains($value, ',') && str_contains($value, '.')) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        } else {
            $value = str_replace(',', '.', $value);
        }

        return is_numeric($value) ? (float) $value : 0.0;
    }
}
