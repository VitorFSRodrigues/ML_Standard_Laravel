<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Fase extends Model
{
    protected $table = 'fases';

    protected $fillable = [
        'triagem_id', 'revisao', 'versao', 'comentario'
    ];

    protected $casts = [
        'triagem_id' => 'integer',
        'revisao'    => 'integer',
        'versao'     => 'integer',
        'comentario' => 'string',
    ];

    public function triagem()
    {
        return $this->belongsTo(Triagem::class);
    }
}
