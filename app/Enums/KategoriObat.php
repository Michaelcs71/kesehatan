<?php

namespace App\Enums;

enum KategoriObat: string
{
    case ORAL    = 'oral';
    case INJEKSI = 'injeksi';
    case INSULIN = 'insulin';
    case LAINNYA = 'lainnya';

    public function label(): string
    {
        return match ($this) {
            self::ORAL    => 'Oral (Tablet/Kapsul)',
            self::INJEKSI => 'Injeksi',
            self::INSULIN => 'Insulin',
            self::LAINNYA => 'Lainnya',
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