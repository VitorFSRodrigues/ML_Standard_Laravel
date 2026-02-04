<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StdELE extends Model
{
    protected $table = 'std_ele_valor';

    protected $fillable = [
        'std_ele_tipo_id',
        'std_ele_material_id',
        'std_ele_conexao_id',
        'std_ele_espessura_id',
        'std_ele_extremidade_id',
        'std_ele_dimensao_id',
        'std',
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
    public function tipo()        { return $this->belongsTo(StdEleTipo::class, 'std_ele_tipo_id'); }
    public function material()    { return $this->belongsTo(StdEleMaterial::class, 'std_ele_material_id'); }
    public function conexao()     { return $this->belongsTo(StdEleConexao::class, 'std_ele_conexao_id'); }
    public function espessura()   { return $this->belongsTo(StdEleEspessura::class, 'std_ele_espessura_id'); }
    public function extremidade() { return $this->belongsTo(StdEleExtremidade::class, 'std_ele_extremidade_id'); }
    public function dimensao()    { return $this->belongsTo(StdEleDimensao::class, 'std_ele_dimensao_id'); }
}