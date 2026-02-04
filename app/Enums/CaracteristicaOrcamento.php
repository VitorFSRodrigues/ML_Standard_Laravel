<?php

namespace App\Enums;

enum CaracteristicaOrcamento: string
{
    case Montagem   = 'Montagem';
    case Fabricacao = 'Fabricação';
    case FAST       = 'FAST';
    case Paineis    = 'Painéis';
    case Engenharia = 'Engenharia';

    /** Retorna lista para <select> etc. */
    public static function options(): array
    {
        return array_map(fn(self $e) => $e->value, self::cases());
    }
}
