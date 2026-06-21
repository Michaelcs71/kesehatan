<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PengaturanPengingat extends Model
{
    use HasUuids;

    protected $table = 'pengaturan_pengingat';

    protected $fillable = [
        'mo_aktif',
        'mo_jumlah',
        'mo_interval_menit',
        'mo_pmo_mulai_ke',
        'cgd_aktif',
        'cgd_dibuat_aktif',
        'cgd_jam_h1',
    ];

    protected function casts(): array
    {
        return [
            'mo_aktif' => 'boolean',
            'mo_jumlah' => 'integer',
            'mo_interval_menit' => 'integer',
            'mo_pmo_mulai_ke' => 'integer',
            'cgd_aktif' => 'boolean',
            'cgd_dibuat_aktif' => 'boolean',
        ];
    }

    /**
     * Nilai default sistem (dipakai service fallback & seeder).
     *
     * @return array<string,mixed>
     */
    public static function defaults(): array
    {
        return [
            'mo_aktif' => true,
            'mo_jumlah' => 4,
            'mo_interval_menit' => 15,
            'mo_pmo_mulai_ke' => 3,
            'cgd_aktif' => true,
            'cgd_dibuat_aktif' => true,
            'cgd_jam_h1' => '17:00',
        ];
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
