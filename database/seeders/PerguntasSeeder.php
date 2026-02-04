<?php

namespace Database\Seeders;

use App\Models\Pergunta;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PerguntasSeeder extends Seeder
{
public function run(): void
    {
        $rows = [
            ['descricao' => 'Já realizamos projetos com a Contratante?',                                         'peso' => 3,  'padrao' => true],
            ['descricao' => 'Já realizamos projetos com o Contato?',                                             'peso' => 5,  'padrao' => true],
            ['descricao' => 'Conhecemos as políticas e exigências da Planta?',                                   'peso' => 6,  'padrao' => true],
            ['descricao' => 'O escopo é de especialidade da MCM?',                                               'peso' => 10, 'padrao' => true],
            ['descricao' => 'Já realizamos projetos de tipo similar?',                                           'peso' => 1,  'padrao' => true],
            ['descricao' => 'A MCM tem uma boa imagem com o cliente ?',                                          'peso' => 15, 'padrao' => true],
            ['descricao' => 'Temos experiência no segmento?',                                                    'peso' => 3,  'padrao' => true],
            ['descricao' => 'O porte do escopo está dentro do range competitivo da MCM?',                        'peso' => 5,  'padrao' => true],
            ['descricao' => 'Possuímos conhecimento na região do projeto?',                                      'peso' => 3,  'padrao' => true],
            ['descricao' => 'Possuímos base na região do projeto?',                                              'peso' => 5,  'padrao' => true],
            ['descricao' => 'Nossos concorrentes não possuem base na região da obra?',                           'peso' => 2,  'padrao' => true],
            ['descricao' => 'Sindicato é irrelevante na concorrência?',                                          'peso' => 2,  'padrao' => true],
            ['descricao' => 'Temos vantagens técnicas sobre os concorrentes?',                                   'peso' => 5,  'padrao' => true],
            ['descricao' => 'Concorrentes têm estrutura similar a nossa?',                                       'peso' => 1,  'padrao' => true],
            ['descricao' => 'Valor estimado da obra é atrativo para a MCM?',                                     'peso' => 4,  'padrao' => true],
            ['descricao' => 'A pessoa-chave na decisão tem preferência? / A preferencia é nossa?',               'peso' => 30, 'padrao' => true],
        ];

        // evita duplicar seeder se rodar mais de uma vez
        foreach ($rows as $r) {
            Pergunta::firstOrCreate(
                ['descricao' => $r['descricao']],
                $r
            );
        }
    }
}
