<?php

namespace Database\Seeders;

use App\Models\DictEleConexao;
use App\Models\DictEleDimensao;
use App\Models\DictEleEspessura;
use App\Models\DictEleExtremidade;
use App\Models\DictEleMaterial;
use App\Models\DictEleTipo;
use App\Models\DictTubDiametro;
use App\Models\DictTubExtremidade;
use App\Models\DictTubMaterial;
use App\Models\DictTubSchedule;
use App\Models\DictTubTipo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DictSeeder extends Seeder
{
    public function run(): void
    {
        $basePath = database_path('seeders/StdSeeders');

        $datasets = [
            ['file' => 'dict_ele_conexao.csv', 'model' => DictEleConexao::class, 'revisao' => 31],
            ['file' => 'dict_ele_dimensao.csv', 'model' => DictEleDimensao::class, 'revisao' => 31],
            ['file' => 'dict_ele_espessura.csv', 'model' => DictEleEspessura::class, 'revisao' => 31],
            ['file' => 'dict_ele_extremidade.csv', 'model' => DictEleExtremidade::class, 'revisao' => 31],
            ['file' => 'dict_ele_material.csv', 'model' => DictEleMaterial::class, 'revisao' => 31],
            ['file' => 'dict_ele_tipo.csv', 'model' => DictEleTipo::class, 'revisao' => 31],
            ['file' => 'dict_tub_diametro.csv', 'model' => DictTubDiametro::class, 'revisao' => 30],
            ['file' => 'dict_tub_extremidade.csv', 'model' => DictTubExtremidade::class, 'revisao' => 30],
            ['file' => 'dict_tub_material.csv', 'model' => DictTubMaterial::class, 'revisao' => 30],
            ['file' => 'dict_tub_schedule.csv', 'model' => DictTubSchedule::class, 'revisao' => 30],
            ['file' => 'dict_tub_tipo.csv', 'model' => DictTubTipo::class, 'revisao' => 30],
        ];

        DB::transaction(function () use ($basePath, $datasets) {
            foreach ($datasets as $dataset) {
                $this->seedCsv(
                    $basePath . DIRECTORY_SEPARATOR . $dataset['file'],
                    $dataset['model'],
                    $dataset['revisao']
                );
            }
        });
    }

    private function seedCsv(string $path, string $modelClass, int $revisao): void
    {
        if (!is_file($path)) {
            return;
        }

        $handle = fopen($path, 'r');
        if ($handle === false) {
            return;
        }

        fgetcsv($handle, 0, ';');

        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            $termo = $this->normalize($row[0] ?? '');
            $descricao = $this->normalize($row[1] ?? '');

            if ($termo === '' && $descricao === '') {
                continue;
            }

            $modelClass::updateOrCreate(
                [
                    'Termo' => $termo,
                    'Revisao' => $revisao,
                ],
                [
                    'Termo' => $termo,
                    'Descricao_Padrao' => $descricao,
                    'Revisao' => $revisao,
                ]
            );
        }

        fclose($handle);
    }

    private function normalize(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $value = preg_replace('/\s+/u', ' ', $value);

        return mb_strtoupper($value, 'UTF-8');
    }
}
