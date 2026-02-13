<?php

namespace App\Enums;

enum RegimeTrabalho: string
{
    case Normal44  = 'Normal (44 horas semanais)';
    case Extra     = 'Extra';
    case DozeTrintaSeis = '12/36 horas';
    case VinteQuatro = '24 horas';

    public static function options(): array
    {
        return array_map(fn(self $e) => $e->value, self::cases());
    }
}
