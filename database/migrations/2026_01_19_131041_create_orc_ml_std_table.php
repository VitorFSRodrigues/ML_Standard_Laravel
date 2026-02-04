<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('orc_ml_std', function (Blueprint $table) {
            $table->id();

            $table->string('numero_orcamento');
            $table->integer('rev')->default(0);
            $table->unsignedBigInteger('orcamentista_id');

            $table->timestamps();

            $table->foreign('orcamentista_id')
                ->references('id')
                ->on('orcamentistas')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // opcional (recomendável para não duplicar número)
            $table->unique(['numero_orcamento', 'rev']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orc_ml_std');
    }
};

