<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StdEleMaterial extends Model
{
    protected $table = 'std_ele_material';

    protected $fillable = [
        'nome',
    ];

    protected $casts = [
        'nome' => 'string',
    ];
}
