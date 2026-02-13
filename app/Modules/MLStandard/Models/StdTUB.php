<?php

namespace App\Modules\MLStandard\Models;

use Illuminate\Database\Eloquent\Model;

class StdTUB extends Model
{
    protected $table = 'std_tub_valor';

    protected $fillable = [
        'std_tub_tipo_id',
        'std_tub_material_id',
        'std_tub_schedule_id',
        'std_tub_extremidade_id',
        'std_tub_diametro_id',
        'hh_un',
        'kg_hh',
        'kg_un',
        'm2_un',

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

    public function tipo()        { return $this->belongsTo(StdTubTipo::class, 'std_tub_tipo_id'); }
    public function material()    { return $this->belongsTo(StdTubMaterial::class, 'std_tub_material_id'); }
    public function schedule()    { return $this->belongsTo(StdTubSchedule::class, 'std_tub_schedule_id'); }
    public function extremidade() { return $this->belongsTo(StdTubExtremidade::class, 'std_tub_extremidade_id'); }
    public function diametro()    { return $this->belongsTo(StdTubDiametro::class, 'std_tub_diametro_id'); }
}
