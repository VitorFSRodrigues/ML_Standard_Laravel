<?php

namespace App\Modules\MLStandard\Models;

use Illuminate\Database\Eloquent\Model;

class StdEleDimensao extends Model
{
    protected $table = 'std_ele_dimensao';

    protected $fillable = [
        'nome',
    ];

    protected $casts = [
        'nome' => 'string',
    ];

}
