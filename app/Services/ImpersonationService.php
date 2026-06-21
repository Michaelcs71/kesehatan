<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ImpersonationService
{
    public const SESSION_KEY = 'impersonator_id';

    /** Role yang boleh diimpersonate (tidak pernah superadmin). */
    private const ROLE_BOLEH = ['admin', 'pmo', 'pasien'];

    /**
     * Mulai mode POV: jadi user wakil dari role tujuan.
     *
     * @throws \InvalidArgumentException role tidak valid
     * @throws \RuntimeException tak ada user aktif untuk role itu
     */
    public static function mulai(string $roleValue): User
    {
        if (! in_array($roleValue, self::ROLE_BOLEH, true)) {
            throw new \InvalidArgumentException('Role tidak valid untuk mode POV.');
        }

        $target = User::query()
            ->where('role', $roleValue)
            ->where('is_active', true)
            ->orderBy('created_at')
            ->first();

        if (! $target) {
            throw new \RuntimeException('Belum ada user aktif untuk role tersebut.');
        }

        $asalId = Auth::id();
        session([self::SESSION_KEY => $asalId]);
        Auth::login($target);

        Log::info('[impersonate] mulai', [
            'oleh' => $asalId, 'menjadi' => $target->id, 'role' => $roleValue,
        ]);

        return $target;
    }

    /** Kembali ke superadmin asli. Aman bila key sudah hilang. */
    public static function kembali(): void
    {
        $asalId = session(self::SESSION_KEY);
        session()->forget(self::SESSION_KEY);

        if (! $asalId) {
            return;
        }

        $asal = User::find($asalId);
        if ($asal) {
            Auth::login($asal);
            Log::info('[impersonate] kembali', ['ke' => $asal->id]);
        }
    }

    public static function sedangImpersonate(): bool
    {
        return session()->has(self::SESSION_KEY);
    }

    public static function impersonator(): ?User
    {
        $id = session(self::SESSION_KEY);

        return $id ? User::find($id) : null;
    }
}
