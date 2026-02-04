<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TriagemMovimento extends Model
{
    protected $fillable = ['triagem_id','de','para','user_id','moved_at', 'from_orcamentista_id', 'to_orcamentista_id',];

    public function triagem() { return $this->belongsTo(Triagem::class); }
    public function user()    { return $this->belongsTo(User::class); }
}
