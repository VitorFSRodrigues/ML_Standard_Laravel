<?php

namespace App\Modules\MLStandard\Models;

use Illuminate\Database\Eloquent\Model;

class StdEleConexao extends Model
{
    protected $table = 'std_ele_conexao';

    protected $fillable = [
        'nome',
    ];
    
    protected $casts = [
        'nome' => 'string',
    ];
}
