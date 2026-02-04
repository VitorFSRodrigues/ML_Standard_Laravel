<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ConferenteComercialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rows = [
            ['nome'=>'André Luis Tenório da Silva','email'=>'andre.tenorio@mcmmontagens.com.br'],
            ['nome'=>'André Soares Nunes Machado','email'=>'andre.machado@mcmmontagens.com.br'],
            ['nome'=>'Edson Francisco da Silva','email'=>'edson.francisco@mcmmontagens.com.br'],
            // (linha repetida no enunciado – manteremos apenas uma)
            ['nome'=>'Ludmila Rates Santos Silva de Luccas','email'=>'ludmila.luccas@mcmmontagens.com.br'],
            ['nome'=>'Márcio César Marques Morato','email'=>'marcio.morato@mcmmontagens.com.br'],
            ['nome'=>'Regina Acioli','email'=>'regina.acioli@mcmmontagens.com.br'],
        ];

        foreach ($rows as $r) {
            DB::table('conferente_comercial')->updateOrInsert(
                ['email' => $r['email']],
                array_merge($r, ['created_at' => now(), 'updated_at' => now()])
            );
        }
    }
}
