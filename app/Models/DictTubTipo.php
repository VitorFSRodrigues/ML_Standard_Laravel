<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DictTubTipo extends Model
{
    protected $table = 'dict_tub_tipo';
    public $timestamps = false;

    protected $fillable = [
        'Termo',
        'Descricao_Padrao',
        'Revisao',
    ];

    protected $casts = [
        'Termo' => 'string',
        'Descricao_Padrao' => 'string',
        'Revisao' => 'integer',
    ];
}
