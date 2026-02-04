<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;

class LevantamentoStdExport implements FromQuery, WithHeadings
{
    public function __construct(public int $orcMLstdId) {}

    private array $roles = [
        'encarregado_mecanica',
        'encarregado_tubulacao',
        'encarregado_eletrica',
        'encarregado_andaime',
        'encarregado_civil',
        'lider',

        'mecanico_ajustador',
        'mecanico_montador',
        'encanador',
        'caldeireiro',
        'lixador',
        'montador',

        'soldador_er',
        'soldador_tig',
        'soldador_mig',

        'ponteador',

        'eletricista_controlista',
        'eletricista_montador',
        'instrumentista',

        'montador_de_andaime',
        'pintor',
        'jatista',
        'pedreiro',
        'carpinteiro',
        'armador',
        'ajudante',
    ];

    public function headings(): array
    {
        $heads = [
            'disciplina',
            'descricao',

            // ELE (aqui vai NOME, mas mantém cabeçalho)
            'ele_std_ele_tipo_id',
            'ele_std_ele_material_id',
            'ele_std_ele_conexao_id',
            'ele_std_ele_espessura_id',
            'ele_std_ele_extremidade_id',
            'ele_std_ele_dimensao_id',
            'ele_std_ele_prob',
            'ele_std',

            // TUB (aqui vai NOME, mas mantém cabeçalho)
            'tub_std_tub_tipo_id',
            'tub_std_tub_material_id',
            'tub_std_tub_schedule_id',
            'tub_std_tub_extremidade_id',
            'tub_std_tub_diametro_id',
            'tub_std_tub_prob',
            'tub_hh_un',
            'tub_kg_hh',
            'tub_kg_un',
            'tub_m2_un',
        ];

        foreach ($this->roles as $r) {
            $heads[] = "ele_$r + tub_$r";
        }

        return $heads;
    }

    public function query()
    {
        /**
         * ======================================================
         * SELECT ELE (exporta NOMES)
         * ======================================================
         */
        $eleSelect = [
            'i.ordem as _ordem',
            'i.disciplina',
            'i.descricao',

            // ✅ NOMES (mantendo os headers antigos)
            DB::raw('CASE WHEN i.ignorar_desc = 1 THEN NULL ELSE et.nome END as ele_std_ele_tipo_id'),
            DB::raw('CASE WHEN i.ignorar_desc = 1 THEN NULL ELSE em.nome END as ele_std_ele_material_id'),
            DB::raw('CASE WHEN i.ignorar_desc = 1 THEN NULL ELSE ec.nome END as ele_std_ele_conexao_id'),
            DB::raw('CASE WHEN i.ignorar_desc = 1 THEN NULL ELSE ee.nome END as ele_std_ele_espessura_id'),
            DB::raw('CASE WHEN i.ignorar_desc = 1 THEN NULL ELSE ex.nome END as ele_std_ele_extremidade_id'),
            DB::raw('CASE WHEN i.ignorar_desc = 1 THEN NULL ELSE ed.nome END as ele_std_ele_dimensao_id'),

            // prob (ML)
            DB::raw('CASE WHEN i.ignorar_desc = 1 THEN NULL ELSE i.prob END as ele_std_ele_prob'),

            // std (da std_ele_valor)
            DB::raw('CASE WHEN i.ignorar_desc = 1 THEN 0 ELSE se.std END as ele_std'),

            // TUB = NULL no bloco ELE
            DB::raw('NULL as tub_std_tub_tipo_id'),
            DB::raw('NULL as tub_std_tub_material_id'),
            DB::raw('NULL as tub_std_tub_schedule_id'),
            DB::raw('NULL as tub_std_tub_extremidade_id'),
            DB::raw('NULL as tub_std_tub_diametro_id'),
            DB::raw('NULL as tub_std_tub_prob'),
            DB::raw('NULL as tub_hh_un'),
            DB::raw('NULL as tub_kg_hh'),
            DB::raw('NULL as tub_kg_un'),
            DB::raw('NULL as tub_m2_un'),
        ];

        foreach ($this->roles as $r) {
            $eleSelect[] = DB::raw("CASE WHEN i.ignorar_desc = 1 THEN 0 ELSE COALESCE(se.$r, 0) END as sum_$r");
        }

        $qELE = DB::table('orc_ml_std_itens as i')
            ->where('i.orc_ml_std_id', $this->orcMLstdId)
            ->where('i.disciplina', 'ELE')

            // joins domínios -> NOMES
            ->leftJoin('std_ele_tipo        as et', 'i.std_ele_tipo_id',        '=', 'et.id')
            ->leftJoin('std_ele_material    as em', 'i.std_ele_material_id',    '=', 'em.id')
            ->leftJoin('std_ele_conexao     as ec', 'i.std_ele_conexao_id',     '=', 'ec.id')
            ->leftJoin('std_ele_espessura   as ee', 'i.std_ele_espessura_id',   '=', 'ee.id')
            ->leftJoin('std_ele_extremidade as ex', 'i.std_ele_extremidade_id', '=', 'ex.id')
            ->leftJoin('std_ele_dimensao    as ed', 'i.std_ele_dimensao_id',    '=', 'ed.id')

            // std_ele_valor para std + funções
            ->leftJoin('std_ele_valor as se', function ($join) {
                $join->on('i.std_ele_tipo_id', '=', 'se.std_ele_tipo_id')
                    ->on('i.std_ele_material_id', '=', 'se.std_ele_material_id')
                    ->on('i.std_ele_conexao_id', '=', 'se.std_ele_conexao_id')
                    ->on('i.std_ele_espessura_id', '=', 'se.std_ele_espessura_id')
                    ->on('i.std_ele_extremidade_id', '=', 'se.std_ele_extremidade_id')
                    ->on('i.std_ele_dimensao_id', '=', 'se.std_ele_dimensao_id');
            })
            ->select($eleSelect);

        /**
         * ======================================================
         * SELECT TUB (exporta NOMES)
         * ======================================================
         */
        $tubSelect = [
            'i.ordem as _ordem',
            'i.disciplina',
            'i.descricao',

            // ELE = NULL no bloco TUB
            DB::raw('NULL as ele_std_ele_tipo_id'),
            DB::raw('NULL as ele_std_ele_material_id'),
            DB::raw('NULL as ele_std_ele_conexao_id'),
            DB::raw('NULL as ele_std_ele_espessura_id'),
            DB::raw('NULL as ele_std_ele_extremidade_id'),
            DB::raw('NULL as ele_std_ele_dimensao_id'),
            DB::raw('NULL as ele_std_ele_prob'),
            DB::raw('NULL as ele_std'),

            // ✅ NOMES (mantendo os headers antigos)
            DB::raw('CASE WHEN i.ignorar_desc = 1 THEN NULL ELSE tt.nome END as tub_std_tub_tipo_id'),
            DB::raw('CASE WHEN i.ignorar_desc = 1 THEN NULL ELSE tm.nome END as tub_std_tub_material_id'),
            DB::raw('CASE WHEN i.ignorar_desc = 1 THEN NULL ELSE ts.nome END as tub_std_tub_schedule_id'),
            DB::raw('CASE WHEN i.ignorar_desc = 1 THEN NULL ELSE tex.nome END as tub_std_tub_extremidade_id'),
            DB::raw('CASE WHEN i.ignorar_desc = 1 THEN NULL ELSE td.nome END as tub_std_tub_diametro_id'),

            // prob (ML)
            DB::raw('CASE WHEN i.ignorar_desc = 1 THEN NULL ELSE i.prob END as tub_std_tub_prob'),

            // valores (da std_tub_valor)
            DB::raw('CASE WHEN i.ignorar_desc = 1 THEN 0 ELSE st.hh_un END as tub_hh_un'),
            DB::raw('CASE WHEN i.ignorar_desc = 1 THEN 0 ELSE st.kg_hh END as tub_kg_hh'),
            DB::raw('CASE WHEN i.ignorar_desc = 1 THEN 0 ELSE st.kg_un END as tub_kg_un'),
            DB::raw('CASE WHEN i.ignorar_desc = 1 THEN 0 ELSE st.m2_un END as tub_m2_un'),
        ];

        foreach ($this->roles as $r) {
            $tubSelect[] = DB::raw("CASE WHEN i.ignorar_desc = 1 THEN 0 ELSE COALESCE(st.$r, 0) END as sum_$r");
        }

        $qTUB = DB::table('orc_ml_std_itens as i')
            ->where('i.orc_ml_std_id', $this->orcMLstdId)
            ->where('i.disciplina', 'TUB')

            // joins domínios -> NOMES
            ->leftJoin('std_tub_tipo        as tt',  'i.std_tub_tipo_id',        '=', 'tt.id')
            ->leftJoin('std_tub_material    as tm',  'i.std_tub_material_id',    '=', 'tm.id')
            ->leftJoin('std_tub_schedule    as ts',  'i.std_tub_schedule_id',    '=', 'ts.id')
            ->leftJoin('std_tub_extremidade as tex', 'i.std_tub_extremidade_id', '=', 'tex.id')
            ->leftJoin('std_tub_diametro    as td',  'i.std_tub_diametro_id',    '=', 'td.id')

            // std_tub_valor para hh/kg/m2 + funções
            ->leftJoin('std_tub_valor as st', function ($join) {
                $join->on('i.std_tub_tipo_id', '=', 'st.std_tub_tipo_id')
                    ->on('i.std_tub_material_id', '=', 'st.std_tub_material_id')
                    ->on('i.std_tub_schedule_id', '=', 'st.std_tub_schedule_id')
                    ->on('i.std_tub_extremidade_id', '=', 'st.std_tub_extremidade_id')
                    ->on('i.std_tub_diametro_id', '=', 'st.std_tub_diametro_id');
            })
            ->select($tubSelect);

        /**
         * ======================================================
         * UNION + ORDER (ordem importação)
         * ======================================================
         */
        $union = $qELE->unionAll($qTUB);

        $finalSelect = [
            'u.disciplina',
            'u.descricao',

            'u.ele_std_ele_tipo_id',
            'u.ele_std_ele_material_id',
            'u.ele_std_ele_conexao_id',
            'u.ele_std_ele_espessura_id',
            'u.ele_std_ele_extremidade_id',
            'u.ele_std_ele_dimensao_id',
            'u.ele_std_ele_prob',
            'u.ele_std',

            'u.tub_std_tub_tipo_id',
            'u.tub_std_tub_material_id',
            'u.tub_std_tub_schedule_id',
            'u.tub_std_tub_extremidade_id',
            'u.tub_std_tub_diametro_id',
            'u.tub_std_tub_prob',
            'u.tub_hh_un',
            'u.tub_kg_hh',
            'u.tub_kg_un',
            'u.tub_m2_un',
        ];

        foreach ($this->roles as $r) {
            $finalSelect[] = "u.sum_$r";
        }

        return DB::query()
            ->fromSub($union, 'u')
            ->orderBy('u._ordem')
            ->select($finalSelect);
    }
}
