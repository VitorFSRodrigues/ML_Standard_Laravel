<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModeloMl extends Model
{
    protected $table = 'modelos_ml';

    protected $fillable = [
        'disciplina',
        'data',
        'revisao',
        'acuracia',
        'treino_job_id',
        'treino_status',
        'treino_created_at',
        'treino_started_at',
        'treino_finished_at',
        'treino_data_at',
        'treino_exact_match_ratio',
        'treino_n_samples',
        'treino_n_train',
        'treino_n_test',
        'treino_classification_report',
        'treino_error',
        'is_current',
    ];

    protected $casts = [
        'data' => 'date',
        'revisao' => 'integer',
        'acuracia' => 'float',
        'treino_created_at' => 'datetime',
        'treino_started_at' => 'datetime',
        'treino_finished_at' => 'datetime',
        'treino_data_at' => 'datetime',
        'treino_exact_match_ratio' => 'float',
        'treino_n_samples' => 'integer',
        'treino_n_train' => 'integer',
        'treino_n_test' => 'integer',
        'treino_classification_report' => 'array',
        'is_current' => 'boolean',
    ];
}
