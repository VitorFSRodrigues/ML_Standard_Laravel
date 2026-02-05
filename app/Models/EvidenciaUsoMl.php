<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvidenciaUsoMl extends Model
{
    use HasFactory;

    protected $table = 'evidencias_uso_ml';

    protected $fillable = [
        'orc_ml_std_id',
        'qtd_itens_ele',
        'qtd_itens_tub',
        'data_modificacao',
        'tempo_normal_hr',
        'tempo_ml_hr',
    ];

    protected $casts = [
        'orc_ml_std_id' => 'integer',
        'qtd_itens_ele' => 'integer',
        'qtd_itens_tub' => 'integer',
        'data_modificacao' => 'datetime',
        'tempo_normal_hr' => 'decimal:2',
        'tempo_ml_hr' => 'decimal:2',
    ];

    public function orcMlStd()
    {
        return $this->belongsTo(OrcMLstd::class, 'orc_ml_std_id');
    }
}
