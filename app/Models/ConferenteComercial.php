<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConferenteComercial extends Model
{
    use HasFactory;
    
    protected $table = 'conferente_comercial';

    protected $fillable = [
        'nome','email'
    ];
}
