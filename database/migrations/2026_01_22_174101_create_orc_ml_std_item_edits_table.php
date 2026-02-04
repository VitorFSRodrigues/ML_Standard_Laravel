<?php

// database/migrations/xxxx_create_orc_ml_std_item_edits_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('orc_ml_std_item_edits', function (Blueprint $table) {
            $table->id();

            $table->foreignId('orc_ml_std_item_id')
                ->constrained('orc_ml_std_itens')
                ->cascadeOnDelete();

            $table->string('disciplina', 3); // ELE | TUB
            $table->string('campo', 40);     // std_tub_tipo_id etc

            $table->unsignedBigInteger('old_value')->nullable();
            $table->unsignedBigInteger('new_value')->nullable();

            $table->foreignId('user_id')->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orc_ml_std_item_edits');
    }
};

