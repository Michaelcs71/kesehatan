<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class PengingatMoLog extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $table = 'pengingat_mo_logs';

    protected $fillable = [
        'id_jo',
        'id_user',
        'nama_pasien',
        'nama_obat',
        'tgl_minum_obat',
        'jam_minum_obat',
        'jam_slot_target',
        'patuh_menit',
        'foto_obat',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'tgl_minum_obat' => 'date',
            'patuh_menit'    => 'integer',
        ];
    }

    // ============ RELATIONS ============

    public function jadwalMo(): BelongsTo
    {
        return $this->belongsTo(JadwalMinumObat::class, 'id_jo');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
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

    public function scopeForUser(Builder $q, string $userId): Builder
    {
        return $q->where('id_user', $userId);
    }

    public function scopeForJadwal(Builder $q, string $jadwalId): Builder
    {
        return $q->where('id_jo', $jadwalId);
    }

    public function scopeByDate(Builder $q, string $date): Builder
    {
        return $q->where('tgl_minum_obat', $date);
    }

    public function scopeBetweenDates(Builder $q, string $start, string $end): Builder
    {
        return $q->whereBetween('tgl_minum_obat', [$start, $end]);
    }

    public function scopeToday(Builder $q): Builder
    {
        return $q->where('tgl_minum_obat', now()->format('Y-m-d'));
    }

    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        if (blank($term)) return $q;
        return $q->where(function ($qq) use ($term) {
            $qq->where('nama_pasien', 'ilike', "%{$term}%")
                ->orWhere('nama_obat', 'ilike', "%{$term}%");
        });
    }

    // ============ ACCESSORS ============

    public function getJamMinumObatFormatAttribute(): string
    {
        if (!$this->jam_minum_obat) return '-';
        return substr($this->jam_minum_obat, 0, 5);
    }

    public function getJamSlotTargetFormatAttribute(): string
    {
        if (!$this->jam_slot_target) return '-';
        return substr($this->jam_slot_target, 0, 5);
    }

    /**
     * Patuh label dengan format "+30 menit", "-15 menit", "tepat waktu"
     */
    public function getPatuhLabelAttribute(): string
    {
        $menit = $this->patuh_menit;
        if ($menit === 0) return 'Tepat waktu';
        if ($menit > 0) return '+' . $menit . ' menit (telat)';
        return abs($menit) . ' menit lebih awal';
    }

    /**
     * Patuh kategori untuk badge color & analytics
     */
    public function getPatuhKategoriAttribute(): string
    {
        $menit = abs($this->patuh_menit);
        if ($menit <= 15) return 'tepat_waktu';      // ±15 menit toleransi
        if ($menit <= 60) return 'terlambat';         // 16-60 menit
        return 'sangat_terlambat';                    // >60 menit
    }

    public function getPatuhBadgeColorAttribute(): string
    {
        return match ($this->patuh_kategori) {
            'tepat_waktu'      => 'success',
            'terlambat'        => 'warning',
            'sangat_terlambat' => 'danger',
            default            => 'secondary',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'aktif'    => 'Aktif',
            'nonaktif' => 'Nonaktif',
            default    => ucfirst($this->status),
        };
    }

    /**
     * URL foto (publik via storage symlink)
     */
    public function getFotoUrlAttribute(): ?string
    {
        return $this->foto_obat ? Storage::url($this->foto_obat) : null;
    }

    // ============ HELPER METHODS ============

    /**
     * Calculate patuh_menit dari jam_slot_target dan jam_minum_obat
     * Return: integer menit (negatif = lebih awal, positif = telat)
     */
    public static function calculatePatuhMenit(?string $jamSlotTarget, string $jamMinumObat): int
    {
        if (!$jamSlotTarget) return 0;

        $slotMinutes  = self::timeToMinutes($jamSlotTarget);
        $actualMinutes = self::timeToMinutes($jamMinumObat);

        return $actualMinutes - $slotMinutes;
    }

    protected static function timeToMinutes(string $time): int
    {
        [$h, $m] = array_pad(explode(':', $time), 2, 0);
        return ((int) $h) * 60 + ((int) $m);
    }
}
