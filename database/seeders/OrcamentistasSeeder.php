<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrcamentistasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rows = [
            ['nome'=>'Alisson Jose Silva de Lima','email'=>'alisson.lima@mcmmontagens.com.br'],
            ['nome'=>'Bruno Ribeiro Hachenburg','email'=>'bruno.hachenburg@mcmmontagens.com.br'],
            ['nome'=>'Diego Jose da Silva','email'=>'diego.silva@mcmmontagens.com.br'],
            ['nome'=>'Edilson Ricardo Marques Ferreira','email'=>'ricardo.ferreira@mcmmontagens.com.br'],
            ['nome'=>'Felipe Alípio dos Santos Silva','email'=>'felipe.silva@mcmmontagens.com.br'],
            ['nome'=>'Gerson Marinovic','email'=>'gerson.marinovic@mcmmontagens.com.br'],
            ['nome'=>'Hugo Leonardo da Silva Cavalcanti','email'=>'hugo.cavalcanti@mcmmontagens.com.br'],
            ['nome'=>'Jacira Maria Dias Coelho','email'=>'jacira.coelho@mcmmontagens.com.br'],
            ['nome'=>'Jefferson Torres de Santana','email'=>'jefferson.torres@mcmmontagens.com.br'],
            ['nome'=>'Joao Alfredo da Silva','email'=>'joao.alfredo@mcmmontagens.com.br'],
            ['nome'=>'Jose Roberto Pereira Campos Junior','email'=>'jose.campos@mcmmontagens.com.br'],
            ['nome'=>'Petrus Romero de Oliveira Soares','email'=>'petrus.soares@mcmmontagens.com.br'],
            ['nome'=>'Vitor Fernando Souza Rodrigues','email'=>'vitor.rodrigues@mcmmontagens.com.br'],
        ];

        foreach ($rows as $r) {
            DB::table('orcamentistas')->updateOrInsert(
                ['email' => $r['email']],
                array_merge($r, ['created_at' => now(), 'updated_at' => now()])
            );
        }
    }
}
