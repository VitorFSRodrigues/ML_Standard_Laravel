<?php

namespace App\Modules\MLStandard\Models;

use Illuminate\Database\Eloquent\Model;

class StdEleEspessura extends Model
{
    protected $table = 'std_ele_espessura';

    protected $fillable = [
        'nome',
    ];

    protected $casts = [
        'nome' => 'string',
    ];
}
