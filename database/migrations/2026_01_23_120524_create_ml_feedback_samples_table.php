<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ml_feedback_samples', function (Blueprint $table) {
            $table->id();

            // contexto
            $table->string('disciplina', 10)->index(); // ELE | TUB
            $table->foreignId('varredura_id')
                ->nullable()
                ->constrained('varredura')
                ->nullOnDelete();
            $table->unsignedBigInteger('orc_ml_std_id')->nullable()->index();
            $table->unsignedBigInteger('orc_ml_std_item_id')->nullable()->index();
            $table->integer('ordem')->default(0)->index();

            // dado base
            $table->text('descricao_original');

            // dados do ML
            $table->json('ml_pred_json')->nullable();        // nomes previstos pelo ML
            $table->string('ml_prob_str', 32)->nullable();   // ex: "99/88/77/100/92/95"
            $table->unsignedSmallInteger('ml_min_prob')->nullable()->index(); // menor prob da linha

            // dados pós edição do usuário
            $table->json('user_final_json')->nullable();     // nomes finais após user ajustar
            $table->boolean('was_edited')->default(false)->index();
            $table->json('edited_fields_json')->nullable();  // ex: ["std_ele_tipo_id","std_ele_material_id"]

            // motivo de ter ido pro feedback
            $table->string('reason', 20)->index(); // LOW_CONFIDENCE | USER_EDIT | BOTH

            /**
             * ✅ Aprovação de treino
             * por padrão começa NÃO REVISADO, o treinador aprova
             */
            $table->string('status', 20)->default('NÃO REVISADO')->index(); // REPROVADO | APROVADO | NÃO REVISADO

            $table->unsignedBigInteger('created_by')->nullable()->index();

            $table->timestamps();

            /**
             * ✅ Índices de performance
             */
            $table->index(['disciplina', 'status', 'updated_at'], 'idx_mlfb_disc_status_updated');
            $table->index(['status', 'updated_at'], 'idx_mlfb_status_updated');

            /**
             * ✅ Garante que seu firstOrNew([item_id, disciplina]) não duplica
             * Mesmo se item_id for NULL não quebra (DB permite vários NULLs em UNIQUE)
             */
            $table->unique(['orc_ml_std_item_id', 'disciplina'], 'uq_mlfb_item_disc');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ml_feedback_samples');
    }
};
