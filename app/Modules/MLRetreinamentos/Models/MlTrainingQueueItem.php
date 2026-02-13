<?php

namespace App\Modules\MLRetreinamentos\Models;

use Illuminate\Database\Eloquent\Model;

class MlTrainingQueueItem extends Model
{
    public const STATUS_QUEUED = 'QUEUED';
    public const STATUS_SENT   = 'SENT';
    public const STATUS_FAILED = 'FAILED';
    public const STATUS_DONE   = 'DONE';

    protected $fillable = [
        'ml_feedback_sample_id',
        'disciplina',
        'status',
        'approved_by',
        'approved_at',
        'sent_by',
        'sent_at',
        'last_error',
    ];

    public function sample()
    {
        return $this->belongsTo(MlFeedbackSample::class, 'ml_feedback_sample_id');
    }
}
