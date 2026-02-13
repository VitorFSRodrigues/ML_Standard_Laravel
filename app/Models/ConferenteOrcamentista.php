<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConferenteOrcamentista extends Model
{
    use HasFactory;
    
    protected $table = 'conferente_orcamentista';

    protected $fillable = [
        'nome','email'
    ];
}
