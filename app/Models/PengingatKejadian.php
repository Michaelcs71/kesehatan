<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PengingatKejadian extends Model
{
    use HasUuids;

    public const STATUS_MENUNGGU = 'menunggu';

    public const STATUS_DIKONFIRMASI = 'dikonfirmasi';

    public const STATUS_TERLEWAT = 'terlewat';

    protected $table = 'pengingat_kejadian';

    protected $fillable = [
        'jenis',
        'jadwal_id',
        'id_pasien_pmo',
        'user_pasien_id',
        'user_pmo_id',
        'waktu_jadwal',
        'status',
        'konfirmasi_log_id',
        'dikonfirmasi_pada',
        'jumlah_push',
        'jumlah_wa_pasien',
        'jumlah_wa_pmo',
        'terakhir_dikirim_pada',
        'eskalasi_pmo',
    ];

    protected function casts(): array
    {
        return [
            'waktu_jadwal' => 'datetime',
            'dikonfirmasi_pada' => 'datetime',
            'terakhir_dikirim_pada' => 'datetime',
            'eskalasi_pmo' => 'boolean',
            'jumlah_push' => 'integer',
            'jumlah_wa_pasien' => 'integer',
            'jumlah_wa_pmo' => 'integer',
        ];
    }

    public function scopeMenunggu(Builder $q): Builder
    {
        return $q->where('status', self::STATUS_MENUNGGU);
    }

    public function pasien(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_pasien_id');
    }

    public function pmo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_pmo_id');
    }
}
