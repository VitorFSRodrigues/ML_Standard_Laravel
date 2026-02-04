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
        Schema::create('triagem_pergunta', function (Blueprint $table) {
            $table->id();

            // FKs
            $table->foreignId('triagem_id')
                  ->constrained('triagem')        // tabela triagem
                  ->cascadeOnDelete()
                  ->cascadeOnUpdate();

            $table->foreignId('pergunta_id')
                  ->constrained('perguntas')      // tabela perguntas
                  ->restrictOnDelete()
                  ->cascadeOnUpdate();

            // Resposta: ENUM (em SQLite vira string; validamos na aplicação)
            $table->enum('resposta', ['V', 'F', 'NA']);

            $table->string('observacao', 255)->nullable();

            $table->timestamps();

            // Evita duplicidade da mesma pergunta na mesma triagem
            $table->unique(['triagem_id', 'pergunta_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('triagem_pergunta');
    }
};
