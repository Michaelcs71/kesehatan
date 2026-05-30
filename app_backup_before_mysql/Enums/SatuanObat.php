<?php

namespace App\Enums;

enum SatuanObat: string
{
    case TABLET  = 'tablet';
    case KAPSUL  = 'kapsul';
    case ML      = 'ml';
    case MG      = 'mg';
    case IU      = 'IU';
    case SACHET  = 'sachet';

    public function label(): string
    {
        return match ($this) {
            self::TABLET => 'Tablet',
            self::KAPSUL => 'Kapsul',
            self::ML     => 'ml (Mililiter)',
            self::MG     => 'mg (Miligram)',
            self::IU     => 'IU (International Unit)',
            self::SACHET => 'Sachet',
        };
    }

    public static function options(): array
    {
        $opts = [];
        foreach (self::cases() as $case) {
            $opts[$case->value] = $case->label();
        }
        return $opts;
    }
}