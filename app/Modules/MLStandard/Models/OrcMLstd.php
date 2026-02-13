<?php

namespace App\Modules\MLStandard\Models;

use App\Models\Orcamentista;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrcMLstd extends Model
{
    use HasFactory;

    protected $table = 'orc_ml_std';

    protected $fillable = [
        'numero_orcamento',
        'rev',
        'orcamentista_id',
    ];

    protected $casts = [
        'rev' => 'integer',
    ];

    public function orcamentista()
    {
        return $this->belongsTo(Orcamentista::class, 'orcamentista_id');
    }

    public function evidenciaUsoMl()
    {
        return $this->hasOne(EvidenciaUsoMl::class, 'orc_ml_std_id');
    }
}
