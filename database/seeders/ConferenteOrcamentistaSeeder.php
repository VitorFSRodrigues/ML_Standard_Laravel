<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ConferenteOrcamentistaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rows = [
            ['nome'=>'Edilson Ricardo Marques Ferreira','email'=>'ricardo.ferreira@mcmmontagens.com.br'],
            ['nome'=>'Gerson Marinovic','email'=>'gerson.marinovic@mcmmontagens.com.br'],
            ['nome'=>'Jacira Maria Dias Coelho','email'=>'jacira.coelho@mcmmontagens.com.br'],
        ];

        foreach ($rows as $r) {
            DB::table('conferente_orcamentista')->updateOrInsert(
                ['email' => $r['email']],
                array_merge($r, ['created_at' => now(), 'updated_at' => now()])
            );
        }
    }
}
