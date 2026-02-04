<?php

namespace App\Enums;

enum RegimeContrato: string
{
    case EmpreitadaGlobal = 'Empreitada Global';
    case Administracao    = 'Administração';
    case PrecoUnitario    = 'Preço Unitário';
    case Parada           = 'Parada';

    public static function options(): array
    {
        return array_map(fn(self $e) => $e->value, self::cases());
    }
}

