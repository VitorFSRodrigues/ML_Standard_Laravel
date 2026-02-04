<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ml_training_queue_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('ml_feedback_sample_id')
                ->constrained('ml_feedback_samples')
                ->cascadeOnDelete();

            $table->string('disciplina', 10)->index(); // ELE|TUB

            // estado do envio/treino
            $table->string('status')->default('QUEUED')->index(); // QUEUED|SENT|FAILED|DONE

            // trilha
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();

            $table->unsignedBigInteger('sent_by')->nullable();
            $table->timestamp('sent_at')->nullable();

            $table->text('last_error')->nullable();

            $table->timestamps();

            $table->unique(['ml_feedback_sample_id'], 'uq_training_queue_sample');
            $table->index(['status', 'updated_at'], 'idx_queue_status_updated');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ml_training_queue_items');
    }
};
