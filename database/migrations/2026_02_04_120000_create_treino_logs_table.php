<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('treino_logs', function (Blueprint $table) {
            $table->id();
            $table->string('job_id', 64)->index();
            $table->unsignedBigInteger('varredura_id')->nullable()->index();
            $table->string('status', 20)->index();
            $table->text('message')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('treino_logs');
    }
};
