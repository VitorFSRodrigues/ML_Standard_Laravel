<?php

namespace App\Enums;

enum DestinoTriagem: string
{
    case TRIAGEM = 'triagem';
    case ACERVO  = 'acervo';
    case ORCAMENTO   = 'orcamento';

    public static function options(): array
    {
        return array_map(fn($c) => $c->value, self::cases());
    }

}
