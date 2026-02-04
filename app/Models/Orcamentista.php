<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Orcamentista extends Model
{
    use HasFactory;
    
    protected $table = 'orcamentistas';

    protected $fillable = [
        'nome','email'
    ];
    public function triagens()
    {
        // Triagens encaminhadas para orçamento e vinculadas a este orçamentista
        return $this->hasMany(Triagem::class, 'orcamentista_id');
    }
}
