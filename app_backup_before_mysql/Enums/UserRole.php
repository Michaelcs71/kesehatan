<?php

namespace App\Enums;

enum UserRole: string
{
    case PENGUNJUNG = 'pengunjung';
    case PASIEN = 'pasien';
    case PMO = 'pmo';
    case ADMIN = 'admin';
    case SUPERADMIN = 'superadmin';

    public function label(): string
    {
        return match ($this) {
            self::PENGUNJUNG => 'Pengunjung',
            self::PASIEN     => 'Pasien',
            self::PMO        => 'PMO (Pendamping Minum Obat)',
            self::ADMIN      => 'Admin',
            self::SUPERADMIN => 'Super Admin',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::PENGUNJUNG => 'secondary',
            self::PASIEN     => 'primary',
            self::PMO        => 'success',
            self::ADMIN      => 'warning',
            self::SUPERADMIN => 'danger',
        };
    }

    public static function selfRegisterable(): array
    {
        return [self::PASIEN, self::PMO];
    }

    public function isAdmin(): bool
    {
        return in_array($this, [self::ADMIN, self::SUPERADMIN]);
    }

    public function isSuperadmin(): bool
    {
        return $this === self::SUPERADMIN;
    }
}