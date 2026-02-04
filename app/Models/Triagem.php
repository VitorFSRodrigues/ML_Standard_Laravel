<?php

namespace App\Models;

use App\Enums\CaracteristicaOrcamento;
use App\Enums\RegimeContrato;
use App\Enums\TipoServico;
use App\Enums\DestinoTriagem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\TriagemPergunta;

class Triagem extends Model
{
    use SoftDeletes;

    protected $table = 'triagem';
    
    protected $fillable = [
        'pipedrive_deal_id',
        'cliente_id',           // agora string (nome)
        'cliente_final_id',     // string (nome),
        'numero_orcamento',
        'caracteristica_orcamento',
        'tipo_servico',
        'regime_contrato',
        'descricao_servico',
        'descricao_resumida',
        'condicao_pagamento_ddl',
        'cidade_obra', 'estado_obra', 'pais_obra',
        'status',
        'destino', 
        'moved_at', 
        'moved_by', 
        'orcamentista_id',
    ];

    /**
     * Casts:
     * - enums: converte string do DB <-> enum (PHP) automaticamente
     * - datas/ints conforme necessário
     */
    protected $casts = [
        'caracteristica_orcamento' => CaracteristicaOrcamento::class,
        'tipo_servico'             => TipoServico::class,
        'regime_contrato'          => RegimeContrato::class,
        'condicao_pagamento_ddl'   => 'integer',
        'destino'                  => DestinoTriagem::class,
        'status'                   => 'boolean',
        'moved_at'                 => 'datetime',
    ];

    /** Relacionamentos */
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function clienteFinal()
    {
        return $this->belongsTo(Cliente::class, 'cliente_final_id');
    }

    /** Perguntas/respostas vinculadas a esta triagem */
    public function triagemPerguntas()
    {
        return $this->hasMany(TriagemPergunta::class, 'triagem_id')
                    ->with('pergunta'); // opcional: já traz descrição/peso
    }  
    
    public function orcamentista()
    {
        return $this->belongsTo(Orcamentista::class);
    }

    public function scopeNaTriagem($q){return $q->where('destino', DestinoTriagem::TRIAGEM->value);}
    public function scopeNoAcervo($q){return $q->where('destino', DestinoTriagem::ACERVO->value);}
    public function scopeEmOrcamento($q, ?int $orcamentistaId = null)
    {
        $q->where('destino', DestinoTriagem::ORCAMENTO->value);
        if ($orcamentistaId) $q->where('orcamentista_id', $orcamentistaId);
        return $q;
    }
    public function scopeAtivos($q)   { return $q->where('status', true); }
    public function scopeInativos($q) { return $q->where('status', false); }
}
