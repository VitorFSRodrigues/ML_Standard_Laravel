<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evidencias_uso_ml_configs', function (Blueprint $table) {
            $table->id();
            $table->decimal('tempo_levantamento_ele_min', 8, 2)->default(1.00);
            $table->decimal('tempo_levantamento_tub_min', 8, 2)->default(1.00);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evidencias_uso_ml_configs');
    }
};

