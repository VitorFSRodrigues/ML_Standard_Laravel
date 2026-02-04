<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('triagem', function (Blueprint $table) {
            $table->id();

            /**
             * IMPORTANTE: após integração com o PipeDrive,
             * cliente_id e cliente_final_id passam a guardar o NOME (string),
             * não mais FKs. O histórico de organizações ficará na tabela clientes.
             */
            $table->string('cliente_id', 255);        // nome (ex: "SAINTGOBAIN")
            $table->string('cliente_final_id', 255);  // nome (ex: "TRÊSCORAÇÕES")

            // rastreio PipeDrive (opcional)
            $table->unsignedBigInteger('pipedrive_deal_id')->nullable()->index();

            // número do orçamento
            $table->string('numero_orcamento', 255)->index();

            // ENUMs salvos como string; validação via aplicação
            // caracteristica_orcamento ∈ ['Montagem','Fabricação','FAST','Painéis','Engenharia']
            $table->string('caracteristica_orcamento', 20);

            // tipo_servico ∈ ['Manutenção','Empreitada','Engenharia','Fabricação'] (mantido p/ preenchimento posterior)
            $table->string('tipo_servico', 20)->nullable();

            /**
             * Regime de contrato atualizado (validação no app):
             * ['Empreitada Global','Administração','Preço Unitário','Parada']
             */
            $table->string('regime_contrato', 30);

            $table->string('descricao_servico', 255);
            $table->string('descricao_resumida', 255)->nullable();

            // DDL (dias de prazo de pagamento)
            $table->unsignedInteger('condicao_pagamento_ddl');

            // **Novos campos de localização da obra (ficam em Triagem)**
            $table->string('cidade_obra', 255)->nullable();
            $table->string('estado_obra', 255)->nullable();
            $table->string('pais_obra', 255)->nullable();

            // **REMOVIDOS de Triagem**: data_inicio_obra, prazo_obra (foram para requisitos)

            // Status & movimentação
            $table->boolean('status')->default(true)->index();           // ativo/inativo
            $table->string('destino', 20)->default('triagem')->index();  // triagem|acervo|orcamento
            $table->timestamp('moved_at')->nullable();
            $table->foreignId('moved_by')->nullable()->constrained('users'); // se não tiver users, mantenha nullable

            // vínculo opcional com orçamentista quando estiver em “orçamento”
            $table->foreignId('orcamentista_id')->nullable()
                  ->constrained('orcamentistas')->nullOnDelete();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('triagem');
    }
};
