<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('varredura', function (Blueprint $table) {
            $table->id();
            $table->integer('revisao_ele')->default(0);
            $table->integer('revisao_tub')->default(0);
            $table->boolean('status_ele')->default(false);
            $table->boolean('status_tub')->default(false);
            $table->string('treino_status', 20)->default('pendente')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('varredura');
    }
};
