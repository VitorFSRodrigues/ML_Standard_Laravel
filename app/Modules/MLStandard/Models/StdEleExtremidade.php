<?php

namespace App\Modules\MLStandard\Models;

use Illuminate\Database\Eloquent\Model;

class StdEleExtremidade extends Model
{
    protected $table = 'std_ele_extremidade';

    protected $fillable = [
        'nome',
    ];

    protected $casts = [
        'nome' => 'string',
    ];
}
