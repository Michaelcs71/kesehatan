<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class JadwalMinumObat extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $table = 'jadwal_minum_obats';

    protected $fillable = [
        'id_pasien_pmo',
        'obat_id',
        'nama_pasien',
        'nama_pmo',
        'tgl_mulai',
        'jam_mulai',
        'frekuensi_per_hari',
        'catatan_dosis',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'tgl_mulai'          => 'date',
            'frekuensi_per_hari' => 'integer',

        ];
    }

    // ============ RELATIONS ============

    public function pasienPmo(): BelongsTo
    {
        return $this->belongsTo(PasienPmo::class, 'id_pasien_pmo');
    }

    public function obat(): BelongsTo
    {
        return $this->belongsTo(MasterObat::class, 'obat_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deleter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    // ============ SCOPES ============

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('status', 'aktif');
    }

    public function scopeStatus(Builder $q, string $status): Builder
    {
        return $q->where('status', $status);
    }

    public function scopeForPasienPmo(Builder $q, string $pasienPmoId): Builder
    {
        return $q->where('id_pasien_pmo', $pasienPmoId);
    }

    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        if (blank($term)) return $q;
        return $q->where(function ($qq) use ($term) {
            $qq->where('nama_pasien', 'like', "%{$term}%")
                ->orWhere('nama_pmo', 'like', "%{$term}%")
                ->orWhereHas('obat', function ($obatQ) use ($term) {
                    $obatQ->where('nama', 'like', "%{$term}%");  // ← FIX: pakai 'nama'
                });
        });
    }

    // ============ ACCESSORS ============

    /**
     * Jam mulai formatted (e.g. "08:00")
     */
    public function getJamMulaiFormatAttribute(): string
    {
        if (!$this->jam_mulai) return '-';
        return substr($this->jam_mulai, 0, 5);  // "08:00:00" → "08:00"
    }

    /**
     * Interval jam antar minum (24 / frekuensi)
     * e.g. frekuensi 3 → setiap 8 jam
     */
    public function getIntervalJamAttribute(): float
    {
        if (!$this->frekuensi_per_hari || $this->frekuensi_per_hari <= 0) return 0;
        return round(24 / $this->frekuensi_per_hari, 1);
    }

    /**
     * Generate slot jam dalam sehari berdasarkan jam_mulai + frekuensi_per_hari
     * e.g. jam_mulai=08:00, frekuensi=3 → ['08:00', '16:00', '00:00']
     */
    public function getSlotJamHarianAttribute(): array
    {
        if (!$this->jam_mulai || !$this->frekuensi_per_hari) return [];

        $slots = [];
        $interval = 24 / $this->frekuensi_per_hari;

        [$h, $m] = array_pad(explode(':', $this->jam_mulai), 2, 0);
        $startMinutes = ((int) $h) * 60 + ((int) $m);

        for ($i = 0; $i < $this->frekuensi_per_hari; $i++) {
            $minutes = ($startMinutes + ($i * $interval * 60)) % (24 * 60);
            $hh = floor($minutes / 60);
            $mm = $minutes % 60;
            $slots[] = sprintf('%02d:%02d', $hh, $mm);
        }

        return $slots;
    }



    /**
     * Status label dengan badge color
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'aktif'    => 'Aktif',
            'nonaktif' => 'Nonaktif',
            'selesai'  => 'Selesai',
            default    => ucfirst($this->status),
        };
    }
}
