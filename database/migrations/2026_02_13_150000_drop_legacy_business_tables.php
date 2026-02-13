<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('fases');
        Schema::dropIfExists('triagem_movimentos');
        Schema::dropIfExists('requisitos');
        Schema::dropIfExists('conferente_comercial');
        Schema::dropIfExists('conferente_orcamentista');
        Schema::dropIfExists('triagem_pergunta');
        Schema::dropIfExists('triagem');
        Schema::dropIfExists('perguntas');
        Schema::dropIfExists('clientes');

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        // Intencionalmente sem rollback: o escopo do projeto foi reduzido para ML.
    }
};
