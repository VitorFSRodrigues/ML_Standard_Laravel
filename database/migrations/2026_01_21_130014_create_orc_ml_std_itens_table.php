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
        Schema::create('orc_ml_std_itens', function (Blueprint $table) {
            $table->id();

            $table->foreignId('orc_ml_std_id')
                ->constrained('orc_ml_std')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->integer('ordem')->default(0);
            $table->string('disciplina', 3); // ELE | TUB
            $table->text('descricao');
            $table->boolean('ignorar_desc')->default(false);

            // % probabilidade
            $table->string('prob', 18)->nullable(); // 0-100
            $table->json('edited_fields')->nullable();
            $table->text('user_edits')->nullable(); // JSON com campos editados

            // ===== ELE (chaves) =====
            $table->foreignId('std_ele_tipo_id')->nullable()->constrained('std_ele_tipo')->nullOnDelete();
            $table->foreignId('std_ele_material_id')->nullable()->constrained('std_ele_material')->nullOnDelete();
            $table->foreignId('std_ele_conexao_id')->nullable()->constrained('std_ele_conexao')->nullOnDelete();
            $table->foreignId('std_ele_espessura_id')->nullable()->constrained('std_ele_espessura')->nullOnDelete();
            $table->foreignId('std_ele_extremidade_id')->nullable()->constrained('std_ele_extremidade')->nullOnDelete();
            $table->foreignId('std_ele_dimensao_id')->nullable()->constrained('std_ele_dimensao')->nullOnDelete();

            $table->decimal('std_ele', 12, 4)->nullable();

            // ===== TUB (chaves) =====
            $table->foreignId('std_tub_tipo_id')->nullable()->constrained('std_tub_tipo')->nullOnDelete();
            $table->foreignId('std_tub_material_id')->nullable()->constrained('std_tub_material')->nullOnDelete();
            $table->foreignId('std_tub_schedule_id')->nullable()->constrained('std_tub_schedule')->nullOnDelete();
            $table->foreignId('std_tub_extremidade_id')->nullable()->constrained('std_tub_extremidade')->nullOnDelete();
            $table->foreignId('std_tub_diametro_id')->nullable()->constrained('std_tub_diametro')->nullOnDelete();

            $table->decimal('hh_un', 12, 4)->nullable();
            $table->decimal('kg_hh', 12, 4)->nullable();
            $table->decimal('kg_un', 12, 4)->nullable();
            $table->decimal('m2_un', 12, 4)->nullable();

            $table->timestamps();

            $table->index(['orc_ml_std_id', 'disciplina']);
            $table->unique(['orc_ml_std_id', 'ordem'], 'orc_ml_std_itens_ordem_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orc_ml_std_itens');
    }
};
