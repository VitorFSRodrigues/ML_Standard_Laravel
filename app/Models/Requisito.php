<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Enums\RegimeTrabalho;

class Requisito extends Model
{
    protected $table = 'requisitos';

    protected $fillable = [
        'triagem_id',
        'orcamentista_id',
        'quantitativo_pico',
        'regime_trabalho',
        'icms_percent',
        'conferente_comercial_id',
        'conferente_orcamentista_id',
        'caracteristicas_especiais',
        'data_inicio_obra',
        'prazo_obra',
    ];

    protected $casts = [
        'quantitativo_pico' => 'integer',
        'icms_percent'      => 'decimal:2',
        'regime_trabalho'   => RegimeTrabalho::class, // usa enum PHP
    ];

    public function triagem()            { return $this->belongsTo(Triagem::class, 'triagem_id'); }
    public function orcamentista()       { return $this->belongsTo(Orcamentista::class, 'orcamentista_id'); }
    public function conferenteComercial(){ return $this->belongsTo(ConferenteComercial::class, 'conferente_comercial_id'); }
    public function conferenteOrcamentista(){ return $this->belongsTo(ConferenteOrcamentista::class, 'conferente_orcamentista_id'); }
    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (is_null($model->icms_percent)) {
                $model->icms_percent = config('fiscal.icms_default');
            }
        });
    }

}
