<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ClientesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rows = [
            ['id'=>1,'nome_cliente'=>'SAINT GOBAIN - SEKURIT - SP','nome_fantasia'=>'SAINTGOBAIN','endereco_completo'=>'Rua Rui Barbosa, 346, Centro, Mauá, SP, CEP: 09.390-000','municipio'=>'Mauá','estado'=>'SP','pais'=>'Brasil','cnpj'=>'61.064.838/0089-75'],
            ['id'=>2,'nome_cliente'=>'TRÊS CORAÇÕES - CE','nome_fantasia'=>'TRÊSCORAÇÕES','endereco_completo'=>'Rua Rufino Ferreira da Silva, 200 SANTA CLARA EUSEBIO - CE 61760-000','municipio'=>'Eusébio','estado'=>'CE','pais'=>'Brasil','cnpj'=>'63.310.411/0010-94'],
            ['id'=>3,'nome_cliente'=>'CONSTRUTORA CERTA - RN','nome_fantasia'=>'ConstrutoraCerta','endereco_completo'=>'Rua Romualdo Galvao, 2109, Cond. Trade Center, Sala 503, LAGOA NOVA, NATAL, RN, CEP: 59056-165','municipio'=>'Natal','estado'=>'RN','pais'=>'Brasil','cnpj'=>'08.210.031/0001-89'],
            ['id'=>4,'nome_cliente'=>'DOW BRASIL INDUSTRIA E COMERCIO DE PRODUTOS QUÍMICOS LTDA','nome_fantasia'=>'DOWBR','endereco_completo'=>'Rua ROD. Matoim s/n, Rótula 3, Bairro Zip, Municipio Candeias-BA','municipio'=>'Candeias','estado'=>'BA','pais'=>'Brasil','cnpj'=>'60.435.351/0017-14'],
            ['id'=>5,'nome_cliente'=>'BASF - WEISÓPOLIS - PR','nome_fantasia'=>'BASF','endereco_completo'=>'Rua Rio Piquiri, 650, Weisópolis, PR, CEP: 83.322-010','municipio'=>'Pinhais','estado'=>'PR','pais'=>'Brasil','cnpj'=>'02.930.855/0001-47'],
        ];

        foreach ($rows as $r) {
            \App\Models\Cliente::updateOrCreate(['id'=>$r['id']], $r);
        }
    }
}
