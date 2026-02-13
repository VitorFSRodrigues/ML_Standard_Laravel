<?php

namespace App\Modules\MLStandard\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StdTubSchedule extends Model
{
    protected $table = 'std_tub_schedule';
    protected $fillable = ['nome'];
}
