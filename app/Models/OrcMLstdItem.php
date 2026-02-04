<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\StdTubTipo;
use App\Models\StdTubMaterial;
use App\Models\StdTubSchedule;
use App\Models\StdTubExtremidade;
use App\Models\StdTubDiametro;
use App\Models\StdEleTipo;
use App\Models\StdEleMaterial;
use App\Models\StdEleConexao;
use App\Models\StdEleEspessura;
use App\Models\StdEleExtremidade;
use App\Models\StdEleDimensao;

class OrcMLstdItem extends Model
{
    protected $table = 'orc_ml_std_itens';

    protected $fillable = [
        'orc_ml_std_id',
        'ordem',
        'disciplina',
        'descricao',
        'ignorar_desc',
        'prob',

        'std_ele_tipo_id',
        'std_ele_material_id',
        'std_ele_conexao_id',
        'std_ele_espessura_id',
        'std_ele_extremidade_id',
        'std_ele_dimensao_id',
        'std_ele',

        'std_tub_tipo_id',
        'std_tub_material_id',
        'std_tub_schedule_id',
        'std_tub_extremidade_id',
        'std_tub_diametro_id',
        'hh_un',
        'kg_hh',
        'kg_un',
        'm2_un',
    ];

    protected $casts = [
        'ignorar_desc' => 'boolean',
    ];

    public function orc()               {return $this->belongsTo(OrcMLstd::class, 'orc_ml_std_id');}
    public function stdTubTipo()        {return $this->belongsTo(StdTubTipo::class, 'std_tub_tipo_id');}
    public function stdTubMaterial()    {return $this->belongsTo(StdTubMaterial::class, 'std_tub_material_id');}
    public function stdTubSchedule()    {return $this->belongsTo(StdTubSchedule::class, 'std_tub_schedule_id');}
    public function stdTubExtremidade() {return $this->belongsTo(StdTubExtremidade::class, 'std_tub_extremidade_id');}
    public function stdTubDiametro()    {return $this->belongsTo(StdTubDiametro::class, 'std_tub_diametro_id');}

    public function stdEleTipo()        {return $this->belongsTo(StdEleTipo::class, 'std_ele_tipo_id');}
    public function stdEleMaterial()    {return $this->belongsTo(StdEleMaterial::class, 'std_ele_material_id');}
    public function stdEleConexao()    {return $this->belongsTo(StdEleConexao::class, 'std_ele_conexao_id');}
    public function stdEleEspessura() {return $this->belongsTo(StdEleEspessura::class, 'std_ele_espessura_id');}
    public function stdEleExtremidade() {return $this->belongsTo(StdEleExtremidade::class, 'std_ele_extremidade_id');}
    public function stdEleDimensao()    {return $this->belongsTo(StdEleDimensao::class, 'std_ele_dimensao_id');}
}
