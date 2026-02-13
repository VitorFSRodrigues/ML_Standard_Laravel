<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pergunta extends Model
{
    protected $table = 'perguntas';

    protected $fillable = [
        'descricao',
        'peso',
        'padrao',
    ];

    protected $casts = [
        'peso'   => 'integer',
        'padrao' => 'boolean', // no SQLite vira 0/1, no PHP vira bool
    ];

    /** Escopos úteis */
    public function scopePadrao($query)
    {
        return $query->where('padrao', true);
    }

    public function scopeOrdenado($query)
    {
        return $query->orderBy('peso', 'desc')->orderBy('id');
    }
}
