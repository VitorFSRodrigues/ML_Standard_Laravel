<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evidencias_uso_ml', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orc_ml_std_id')
                ->constrained('orc_ml_std')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->unsignedInteger('qtd_itens_ele')->nullable();
            $table->integer('qtd_itens_tub')->nullable();
            $table->dateTime('data_modificacao')->nullable();
            $table->decimal('tempo_normal_hr', 12, 2)->nullable();
            $table->decimal('tempo_ml_hr', 12, 2)->nullable();
            $table->timestamps();

            $table->unique('orc_ml_std_id', 'evidencias_uso_ml_orc_ml_std_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evidencias_uso_ml');
    }
};
