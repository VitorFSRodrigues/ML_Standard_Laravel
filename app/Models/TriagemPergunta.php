<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TriagemPergunta extends Model
{
    protected $table = 'triagem_pergunta';

    /** Valores permitidos para a coluna 'resposta' (ENUM lógico no app) */
    public const RESPOSTAS = ['V', 'F', 'NA'];

    protected $fillable = [
        'triagem_id',
        'pergunta_id',
        'resposta',
        'observacao',
    ];

    protected $casts = [
        'triagem_id' => 'integer',
        'pergunta_id' => 'integer',
        'resposta'    => 'string',   // pode virar enum PHP depois
    ];

    /** --------- Relacionamentos --------- */
    public function triagem()
    {
        return $this->belongsTo(Triagem::class, 'triagem_id');
    }

    public function pergunta()
    {
        return $this->belongsTo(Pergunta::class, 'pergunta_id');
    }

    /** --------- Escopos úteis --------- */
    public function scopeDaTriagem($query, int $triagemId)
    {
        return $query->where('triagem_id', $triagemId);
    }

    public function scopeComPergunta($query)
    {
        return $query->with('pergunta:id,descricao,peso,padrao');
    }

}
