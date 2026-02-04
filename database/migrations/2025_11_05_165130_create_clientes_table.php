<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();

            // rastreio PipeDrive (organization)
            $table->unsignedBigInteger('pipedrive_org_id')->nullable()->unique();

            $table->string('nome_cliente', 255);
            $table->string('nome_fantasia', 255)->nullable();

            $table->string('endereco_completo', 255)->nullable();
            $table->string('municipio', 255)->nullable();
            $table->string('estado', 255)->nullable();
            $table->string('pais', 255)->nullable();

            $table->string('cnpj', 255)->nullable();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
