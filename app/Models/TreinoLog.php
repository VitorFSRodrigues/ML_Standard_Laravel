<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TreinoLog extends Model
{
    protected $table = 'treino_logs';

    protected $fillable = [
        'job_id',
        'varredura_id',
        'status',
        'message',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];
}
