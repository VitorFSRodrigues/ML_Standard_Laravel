<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DictEleDimensao extends Model
{
    protected $table = 'dict_ele_dimensao';
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
