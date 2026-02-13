<?php

namespace App\Modules\MLStandard\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StdTubExtremidade extends Model
{
    protected $table = 'std_tub_extremidade';
    protected $fillable = ['nome'];
}
