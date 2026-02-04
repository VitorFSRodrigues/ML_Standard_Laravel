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
        Schema::create('perguntas', function (Blueprint $table) {
            $table->id();                              // PK
            $table->string('descricao', 255);         // VARCHAR(255)
            $table->integer('peso');                  // INT
            $table->boolean('padrao')->default(false);// BOOL (no SQLite vira INTEGER 0/1)
            $table->timestamps();                     // created_at / updated_at (opcional, mas recomendado)

            // Índices úteis (opcional)
            $table->index('padrao');
            $table->index('peso');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('perguntas');
    }
};
