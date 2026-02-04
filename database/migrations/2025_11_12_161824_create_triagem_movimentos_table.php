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
        Schema::create('triagem_movimentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('triagem_id')->constrained('triagem')->cascadeOnDelete();
            $table->string('de',   20);
            $table->string('para', 20);
            $table->foreignId('orcamentista_id')->nullable()->constrained('orcamentistas');
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->timestamp('moved_at')->useCurrent();
            $table->timestamps();
            $table->unsignedBigInteger('from_orcamentista_id')->nullable()->after('para');
            $table->unsignedBigInteger('to_orcamentista_id')->nullable()->after('from_orcamentista_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('triagem_movimentos');
    }
};
