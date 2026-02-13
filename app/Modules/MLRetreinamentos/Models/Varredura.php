<?php

namespace App\Modules\MLRetreinamentos\Models;

use Illuminate\Database\Eloquent\Model;

class Varredura extends Model
{
    protected $table = 'varredura';

    protected $fillable = [
        'revisao_ele',
        'revisao_tub',
        'status_ele',
        'status_tub',
        'treino_status',
    ];

    protected $casts = [
        'revisao_ele' => 'integer',
        'revisao_tub' => 'integer',
        'status_ele' => 'boolean',
        'status_tub' => 'boolean',
    ];

    public function mlFeedbackSamples()
    {
        return $this->hasMany(MlFeedbackSample::class, 'varredura_id');
    }
}
