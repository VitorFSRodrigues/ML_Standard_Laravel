<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modelos_ml', function (Blueprint $table) {
            $table->id();
            $table->string('disciplina', 10)->index();
            $table->date('data');
            $table->integer('revisao');
            $table->float('acuracia')->nullable();
            $table->string('treino_job_id', 64)->nullable()->index();
            $table->string('treino_status', 20)->nullable()->index();
            $table->timestamp('treino_created_at')->nullable();
            $table->timestamp('treino_started_at')->nullable();
            $table->timestamp('treino_finished_at')->nullable();
            $table->timestamp('treino_data_at')->nullable();
            $table->float('treino_exact_match_ratio')->nullable();
            $table->integer('treino_n_samples')->nullable();
            $table->integer('treino_n_train')->nullable();
            $table->integer('treino_n_test')->nullable();
            $table->json('treino_classification_report')->nullable();
            $table->text('treino_error')->nullable();
            $table->boolean('is_current')->default(false)->index();
            $table->timestamps();

            $table->unique(['disciplina', 'revisao'], 'uq_modelos_ml_disc_rev');
            $table->index(['disciplina', 'data'], 'idx_modelos_ml_disc_data');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modelos_ml');
    }
};
