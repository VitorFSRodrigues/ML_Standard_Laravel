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
        Schema::create('std_ele_valor', function (Blueprint $table) {
            $table->id();

            $table->foreignId('std_ele_tipo_id')->constrained('std_ele_tipo')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('std_ele_material_id')->constrained('std_ele_material')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('std_ele_conexao_id')->constrained('std_ele_conexao')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('std_ele_espessura_id')->constrained('std_ele_espessura')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('std_ele_extremidade_id')->constrained('std_ele_extremidade')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('std_ele_dimensao_id')->constrained('std_ele_dimensao')->cascadeOnUpdate()->restrictOnDelete();

            // ✅ Valores (use decimal pra evitar bug de float)
            $table->decimal('std', 12, 4)->default(0);

            $table->decimal('encarregado_mecanica', 12, 4)->default(0);
            $table->decimal('encarregado_tubulacao', 12, 4)->default(0);
            $table->decimal('encarregado_eletrica', 12, 4)->default(0);
            $table->decimal('encarregado_andaime', 12, 4)->default(0);
            $table->decimal('encarregado_civil', 12, 4)->default(0);

            $table->decimal('lider', 12, 4)->default(0);

            $table->decimal('mecanico_ajustador', 12, 4)->default(0);
            $table->decimal('mecanico_montador', 12, 4)->default(0);
            $table->decimal('encanador', 12, 4)->default(0);
            $table->decimal('caldeireiro', 12, 4)->default(0);
            $table->decimal('lixador', 12, 4)->default(0);
            $table->decimal('montador', 12, 4)->default(0);

            $table->decimal('soldador_er', 12, 4)->default(0);
            $table->decimal('soldador_tig', 12, 4)->default(0);
            $table->decimal('soldador_mig', 12, 4)->default(0);

            $table->decimal('ponteador', 12, 4)->default(0);

            $table->decimal('eletricista_controlista', 12, 4)->default(0);
            $table->decimal('eletricista_montador', 12, 4)->default(0);
            $table->decimal('instrumentista', 12, 4)->default(0);

            $table->decimal('montador_de_andaime', 12, 4)->default(0);
            $table->decimal('pintor', 12, 4)->default(0);
            $table->decimal('jatista', 12, 4)->default(0);
            $table->decimal('pedreiro', 12, 4)->default(0);
            $table->decimal('carpinteiro', 12, 4)->default(0);
            $table->decimal('armador', 12, 4)->default(0);
            $table->decimal('ajudante', 12, 4)->default(0);

            $table->timestamps();

            // ✅ evita duplicidade para mesma combinação
            $table->unique([
                'std_ele_tipo_id',
                'std_ele_material_id',
                'std_ele_conexao_id',
                'std_ele_espessura_id',
                'std_ele_extremidade_id',
                'std_ele_dimensao_id',
            ], 'std_ele_valor_unique_combo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('std_ele_valor');
    }
};
