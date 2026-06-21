<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class JadwalCgd extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'jadwal_cgds';

    protected $fillable = [
        'tgl_input',
        'tgl_jadwal_cgd',
        'jam_mulai',
        'jam_berakhir',
        'puasa',
        'tempat',
        'catatan',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'tgl_input' => 'date',
            'tgl_jadwal_cgd' => 'date',
        ];
    }

    // ============ RELATIONS ============

    public function peserta(): HasMany
    {
        return $this->hasMany(JadwalCgdPeserta::class, 'jadwal_cgd_id');
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

    public function scopeUpcoming(Builder $q): Builder
    {
        return $q->where('status', 'aktif')
            ->where('tgl_jadwal_cgd', '>=', now()->format('Y-m-d'))
            ->orderBy('tgl_jadwal_cgd', 'asc');
    }

    public function scopePast(Builder $q): Builder
    {
        return $q->where('tgl_jadwal_cgd', '<', now()->format('Y-m-d'))
            ->orderBy('tgl_jadwal_cgd', 'desc');
    }

    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        if (blank($term)) {
            return $q;
        }

        return $q->where(function ($qq) use ($term) {
            $qq->where('tempat', 'like', "%{$term}%")
                ->orWhere('catatan', 'like', "%{$term}%");
        });
    }

    // ============ ACCESSORS ============

    public function getJamMulaiFormatAttribute(): string
    {
        if (! $this->jam_mulai) {
            return '-';
        }

        return substr($this->jam_mulai, 0, 5);  // "07:00:00" → "07:00"
    }

    public function getJamBerakhirFormatAttribute(): string
    {
        if (! $this->jam_berakhir) {
            return '-';
        }

        return substr($this->jam_berakhir, 0, 5);
    }

    /**
     * Durasi dalam jam (decimal)
     * e.g. 07:00 - 10:00 = 3.0
     */
    public function getDurasiJamAttribute(): float
    {
        if (! $this->jam_mulai || ! $this->jam_berakhir) {
            return 0;
        }
        $start = strtotime($this->jam_mulai);
        $end = strtotime($this->jam_berakhir);
        if ($end <= $start) {
            return 0;
        }

        return round(($end - $start) / 3600, 1);
    }

    public function getDurasiLabelAttribute(): string
    {
        $jam = $this->durasi_jam;
        if ($jam <= 0) {
            return '-';
        }
        if ($jam == floor($jam)) {
            return ((int) $jam).' jam';
        }

        return $jam.' jam';
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'aktif' => 'Aktif',
            'nonaktif' => 'Nonaktif',
            'selesai' => 'Selesai',
            default => ucfirst($this->status),
        };
    }

    /**
     * Apakah jadwal sudah lewat?
     */
    public function getIsPastAttribute(): bool
    {
        return $this->tgl_jadwal_cgd && $this->tgl_jadwal_cgd->isPast();
    }

    /**
     * Apakah jadwal hari ini?
     */
    public function getIsTodayAttribute(): bool
    {
        return $this->tgl_jadwal_cgd && $this->tgl_jadwal_cgd->isToday();
    }
}
