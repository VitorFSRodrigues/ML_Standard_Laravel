<?php

namespace App\Enums;

enum TipoServico: string
{
    case Manutencao = 'Manutenção';
    case Empreitada = 'Empreitada';
    case Engenharia = 'Engenharia';
    case Fabricacao = 'Fabricação';

    public static function options(): array
    {
        return array_map(fn(self $e) => $e->value, self::cases());
    }
}
