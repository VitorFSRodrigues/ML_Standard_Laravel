<?php

namespace App\Modules\MLRetreinamentos\Models;

use Illuminate\Database\Eloquent\Model;

class DictEleTipo extends Model
{
    protected $table = 'dict_ele_tipo';
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
