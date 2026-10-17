<?php

namespace App\Enums;

enum InstallationMaterialCategory: string
{
    case RielEstructuraSolar = 'riel-estructura-solar';
    case UnionRiel = 'union-riel';
    case ConectorTierra = 'conector-tierra';
    case ConectorMc4 = 'conector-mc4';
    case EndClamp = 'end-clamp';
    case MidClamp = 'mid-clamp';
    case Otro = 'otro';

    public function label(): string
    {
        return match ($this) {
            self::RielEstructuraSolar => 'Riel estructura solar',
            self::UnionRiel => 'Uniones de riel',
            self::ConectorTierra => 'Conectores de tierra',
            self::ConectorMc4 => 'Conectores MC4',
            self::EndClamp => 'End clamp',
            self::MidClamp => 'Mid clamp',
            self::Otro => 'Otro',
        };
    }

    /** Las categorías distintas de "otro" admiten un solo material. */
    public function isSingleEntry(): bool
    {
        return $this !== self::Otro;
    }
}
