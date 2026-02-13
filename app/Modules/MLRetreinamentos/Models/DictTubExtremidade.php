<?php

namespace App\Modules\MLRetreinamentos\Models;

use Illuminate\Database\Eloquent\Model;

class DictTubExtremidade extends Model
{
    protected $table = 'dict_tub_extremidade';
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
