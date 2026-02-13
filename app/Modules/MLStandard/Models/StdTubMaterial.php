<?php

namespace App\Modules\MLStandard\Models;

use Illuminate\Database\Eloquent\Model;

class StdTubMaterial extends Model
{
    protected $table = 'std_tub_material';
    protected $fillable = ['nome'];
}