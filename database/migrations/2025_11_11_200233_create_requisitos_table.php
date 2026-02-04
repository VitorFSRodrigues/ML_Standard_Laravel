<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('requisitos', function (Blueprint $table) {
            $table->id();

            // FK -> triagem (1:1)
            $table->foreignId('triagem_id')
                  ->constrained('triagem')
                  ->cascadeOnDelete()
                  ->cascadeOnUpdate();

            // preenchimentos posteriores / edição pelo usuário
            $table->foreignId('orcamentista_id')->nullable()
                  ->constrained('orcamentistas')->nullOnDelete()->cascadeOnUpdate();

            $table->unsignedInteger('quantitativo_pico')->nullable();

            // ENUM salvo como string (validação no app)
            $table->enum('regime_trabalho', [
                'Normal (44 horas semanais)', 'Extra', '12/36 horas', '24 horas'
            ])->nullable();

            // ICMS: default 20.50
            $table->decimal('icms_percent', 5, 2)->default(20.50);

            $table->foreignId('conferente_comercial_id')->nullable()
                  ->constrained('conferente_comercial')->nullOnDelete()->cascadeOnUpdate();

            $table->foreignId('conferente_orcamentista_id')->nullable()
                  ->constrained('conferente_orcamentista')->nullOnDelete()->cascadeOnUpdate();

            $table->string('caracteristicas_especiais', 255)->nullable();

            // **Movidos de Triagem para Requisitos**
            $table->date('data_inicio_obra')->nullable();
            $table->unsignedInteger('prazo_obra')->nullable(); // dias

            $table->timestamps();

            $table->unique('triagem_id'); // 1 requisito por triagem
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requisitos');
    }
};
