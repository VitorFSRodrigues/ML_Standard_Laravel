<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Varredura;

class MlFeedbackSample extends Model
{
    protected $table = 'ml_feedback_samples';

    protected $fillable = [
        'disciplina',
        'varredura_id',
        'orc_ml_std_id',
        'orc_ml_std_item_id',
        'ordem',
        'descricao_original',
        'ml_pred_json',
        'ml_prob_str',
        'ml_min_prob',
        'user_final_json',
        'was_edited',
        'edited_fields_json',
        'reason',
        'status',
        'created_by',
    ];

    protected $casts = [
        'ml_pred_json'        => 'array',
        'user_final_json'     => 'array',
        'edited_fields_json'  => 'array',
        'was_edited'          => 'boolean',
        'ml_min_prob'         => 'integer',
        'varredura_id'        => 'integer',
        'orc_ml_std_id'       => 'integer',
        'orc_ml_std_item_id'  => 'integer',
        'ordem'               => 'integer',
    ];

    // ======================
    // STATUS
    // ======================
    public const STATUS_NAO_REVISADO = 'NÃO REVISADO'; // default inicial
    public const STATUS_REPROVADO    = 'REPROVADO';
    public const STATUS_APROVADO     = 'APROVADO';
    public const STATUS_TREINADO     = 'TREINADO';
    public const STATUS_DESCARTADO   = 'DESCARTADO';

    // ======================
    // REASON
    // ======================
    public const REASON_LOW_CONFIDENCE = 'LOW_CONFIDENCE';
    public const REASON_USER_EDIT      = 'USER_EDIT';
    public const REASON_BOTH           = 'BOTH';

    public function varredura()
    {
        return $this->belongsTo(Varredura::class, 'varredura_id');
    }
}
