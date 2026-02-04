<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('dict_ele_espessura', function (Blueprint $table) {
            $table->id();
            $table->string('Termo');
            $table->string('Descricao_Padrao');
            $table->integer('Revisao');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dict_ele_espessura');
    }
};
