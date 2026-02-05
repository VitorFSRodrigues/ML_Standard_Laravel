<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvidenciaUsoMlConfig extends Model
{
    use HasFactory;

    protected $table = 'evidencias_uso_ml_configs';

    protected $fillable = [
        'tempo_levantamento_ele_min',
        'tempo_levantamento_tub_min',
    ];

    protected $casts = [
        'tempo_levantamento_ele_min' => 'float',
        'tempo_levantamento_tub_min' => 'float',
    ];
}

