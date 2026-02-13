<?php

namespace Database\Seeders;

use App\Modules\MLStandard\Models\StdELE;
use App\Modules\MLStandard\Models\StdEleTipo;
use App\Modules\MLStandard\Models\StdEleMaterial;
use App\Modules\MLStandard\Models\StdEleConexao;
use App\Modules\MLStandard\Models\StdEleEspessura;
use App\Modules\MLStandard\Models\StdEleExtremidade;
use App\Modules\MLStandard\Models\StdEleDimensao;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StdELESeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/StdSeeders/Std_ele.xlsx');

        DB::transaction(function () use ($path) {
            $this->seedSimpleSheet($path, 'TIPO', StdEleTipo::class);
            $this->seedSimpleSheet($path, 'MATERIAL', StdEleMaterial::class);
            $this->seedSimpleSheet($path, 'CONEXAO', StdEleConexao::class);
            $this->seedSimpleSheet($path, 'ESPESSURA', StdEleEspessura::class);
            $this->seedSimpleSheet($path, 'EXTREMIDADE', StdEleExtremidade::class);
            $this->seedSimpleSheet($path, 'DIMENSAO', StdEleDimensao::class);

            $this->seedStdSheet($path, 'STD');
        });
    }

    /**
     * Aba simples com 1 coluna "nome" (assumindo nome na coluna A, e linha 1 cabeçalho)
     */
    private function seedSimpleSheet(string $path, string $sheetName, string $modelClass): void
    {
        $sheet = $this->loadSingleSheet($path, $sheetName);

        $highestRow = $sheet->getHighestRow();

        for ($r = 2; $r <= $highestRow; $r++) {
            $nome = trim((string) $sheet->getCell('A' . $r)->getValue());
            if ($nome === '') continue;

            $modelClass::updateOrCreate(['nome' => $nome], ['nome' => $nome]);
        }

        $this->freeSheet($sheet);
    }

    /**
     * Aba STD com cabeçalho na linha 1
     */
    private function seedStdSheet(string $path, string $sheetName): void
    {
        $sheet = $this->loadSingleSheet($path, $sheetName);

        $highestRow = $sheet->getHighestRow();

        for ($r = 2; $r <= $highestRow; $r++) {

            // ✅ colunas fixas (como no print)
            $tipoId        = (int) $sheet->getCell("A{$r}")->getValue();
            $materialId    = (int) $sheet->getCell("B{$r}")->getValue();
            $conexaoId     = (int) $sheet->getCell("C{$r}")->getValue();
            $espessuraId   = (int) $sheet->getCell("D{$r}")->getValue();
            $extremidadeId = (int) $sheet->getCell("E{$r}")->getValue();
            $dimensaoId    = (int) $sheet->getCell("F{$r}")->getValue();

            // se faltar qualquer FK, pula a linha (evita FK error)
            if ($tipoId <= 0 || $materialId <= 0 || $conexaoId <= 0 || $espessuraId <= 0 || $extremidadeId <= 0 || $dimensaoId <= 0) {
                continue;
            }

            $data = [
                'std_ele_tipo_id'        => $tipoId,
                'std_ele_material_id'    => $materialId,
                'std_ele_conexao_id'     => $conexaoId,
                'std_ele_espessura_id'   => $espessuraId,
                'std_ele_extremidade_id' => $extremidadeId,
                'std_ele_dimensao_id'    => $dimensaoId,

                'std' => $this->num((string) $sheet->getCell("G{$r}")->getValue()),

                'encarregado_mecanica'      => $this->num((string) $sheet->getCell("H{$r}")->getValue()),
                'encarregado_tubulacao'     => $this->num((string) $sheet->getCell("I{$r}")->getValue()),
                'encarregado_eletrica'      => $this->num((string) $sheet->getCell("J{$r}")->getValue()),
                'encarregado_andaime'       => $this->num((string) $sheet->getCell("K{$r}")->getValue()),
                'encarregado_civil'         => $this->num((string) $sheet->getCell("L{$r}")->getValue()),
                'lider'                     => $this->num((string) $sheet->getCell("M{$r}")->getValue()),

                'mecanico_ajustador'        => $this->num((string) $sheet->getCell("N{$r}")->getValue()),
                'mecanico_montador'         => $this->num((string) $sheet->getCell("O{$r}")->getValue()),
                'encanador'                 => $this->num((string) $sheet->getCell("P{$r}")->getValue()),
                'caldeireiro'               => $this->num((string) $sheet->getCell("Q{$r}")->getValue()),
                'lixador'                   => $this->num((string) $sheet->getCell("R{$r}")->getValue()),
                'montador'                  => $this->num((string) $sheet->getCell("S{$r}")->getValue()),

                'soldador_er'               => $this->num((string) $sheet->getCell("T{$r}")->getValue()),
                'soldador_tig'              => $this->num((string) $sheet->getCell("U{$r}")->getValue()),
                'soldador_mig'              => $this->num((string) $sheet->getCell("V{$r}")->getValue()),

                'ponteador'                 => $this->num((string) $sheet->getCell("W{$r}")->getValue()),

                'eletricista_controlista'   => $this->num((string) $sheet->getCell("X{$r}")->getValue()),
                'eletricista_montador'      => $this->num((string) $sheet->getCell("Y{$r}")->getValue()),
                'instrumentista'            => $this->num((string) $sheet->getCell("Z{$r}")->getValue()),

                'montador_de_andaime'       => $this->num((string) $sheet->getCell("AA{$r}")->getValue()),
                'pintor'                    => $this->num((string) $sheet->getCell("AB{$r}")->getValue()),
                'jatista'                   => $this->num((string) $sheet->getCell("AC{$r}")->getValue()),
                'pedreiro'                  => $this->num((string) $sheet->getCell("AD{$r}")->getValue()),
                'carpinteiro'               => $this->num((string) $sheet->getCell("AE{$r}")->getValue()),
                'armador'                   => $this->num((string) $sheet->getCell("AF{$r}")->getValue()),
                'ajudante'                  => $this->num((string) $sheet->getCell("AG{$r}")->getValue()),
            ];

            StdELE::updateOrCreate([
                'std_ele_tipo_id'        => $tipoId,
                'std_ele_material_id'    => $materialId,
                'std_ele_conexao_id'     => $conexaoId,
                'std_ele_espessura_id'   => $espessuraId,
                'std_ele_extremidade_id' => $extremidadeId,
                'std_ele_dimensao_id'    => $dimensaoId,
            ], $data);
        }

        $this->freeSheet($sheet);
    }

    /**
     * ✅ Carrega apenas 1 aba (economiza MUITA memória)
     */
    private function loadSingleSheet(string $path, string $sheetName): Worksheet
    {
        $reader = IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true);
        $reader->setLoadSheetsOnly([$sheetName]);

        $spreadsheet = $reader->load($path);
        $sheet = $spreadsheet->getSheetByName($sheetName);

        // prende referência do spreadsheet no worksheet pra liberar depois
        $sheet->_parent = $spreadsheet;

        return $sheet;
    }

    /**
     * ✅ Libera memória depois que terminou a aba
     */
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

    private function buildHeaderMap(Worksheet $sheet, string $highestCol): array
    {
        $map = [];

        // Lê do A até highest column (linha 1)
        foreach (range('A', $highestCol) as $col) {
            $name = trim((string) $sheet->getCell($col . '1')->getValue());
            if ($name === '') continue;

            $map[$this->normalize($name)] = $col;
        }

        return $map;
    }

    private function cell(Worksheet $sheet, array $map, string $headerName, int $row): string
    {
        $key = $this->normalize($headerName);
        $col = $map[$key] ?? null;

        if (!$col) return '';

        return trim((string) $sheet->getCell($col . $row)->getValue());
    }

    private function idByName(string $modelClass, string $nome): int
    {
        $nome = trim($nome);
        if ($nome === '') {
            // caso venha em branco, cria uma opção "N/A" para não quebrar FK
            $nome = 'N/A';
        }

        $id = $modelClass::where('nome', $nome)->value('id');
        if ($id) return (int) $id;

        return (int) $modelClass::create(['nome' => $nome])->id;
    }

    private function normalize(string $s): string
    {
        $s = mb_strtoupper(trim($s));
        $s = str_replace(
            ['Á','À','Â','Ã','Ä','É','È','Ê','Ë','Í','Ì','Î','Ï','Ó','Ò','Ô','Õ','Ö','Ú','Ù','Û','Ü','Ç'],
            ['A','A','A','A','A','E','E','E','E','I','I','I','I','O','O','O','O','O','U','U','U','U','C'],
            $s
        );
        $s = preg_replace('/\s+/', ' ', $s);
        return $s;
    }

    private function num(string $value): float
    {
        $value = trim($value);
        if ($value === '') return 0.0;

        // aceita 1.234,56 ou 1234,56 ou 1234.56
        // remove espaços
        $value = str_replace(' ', '', $value);

        // se tem vírgula e ponto, assume ponto é milhar e vírgula é decimal
        if (str_contains($value, ',') && str_contains($value, '.')) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        } else {
            // se só tem vírgula, vira decimal
            $value = str_replace(',', '.', $value);
        }

        return is_numeric($value) ? (float) $value : 0.0;
    }
}