<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StdEleTipo extends Model
{
    protected $table = 'std_ele_tipo';
    protected $fillable = ['nome'];
    protected $casts = ['nome' => 'string'];
}
