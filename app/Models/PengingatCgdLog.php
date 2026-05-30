<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class PengingatCgdLog extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $table = 'pengingat_cgd_logs';

    // Batas normal per gender (untuk patuh_selisih)
    const BATAS_NORMAL_PEREMPUAN = 140;
    const BATAS_NORMAL_LAKI_LAKI = 200;

    // Threshold kategori (FIXED, tidak gender-based)
    const THRESHOLD_NORMAL           = 140;
    const THRESHOLD_TIDAK_TERKONTROL = 199;
    const THRESHOLD_TINGGI           = 299;

    protected $fillable = [
        'id_cgd',
        'id_user',
        'nama_pasien',
        'jenis_kelamin',
        'tempat_cgd',
        'tgl_cgd',
        'jam_cgd',
        'hasil_mgdl',
        'kategori_hasil',
        'patuh_selisih',
        'foto_layar',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'tgl_cgd'       => 'date',
            'hasil_mgdl'    => 'integer',
            'patuh_selisih' => 'integer',
        ];
    }

    // ============ RELATIONS ============

    public function jadwalCgd(): BelongsTo
    {
        return $this->belongsTo(JadwalCgd::class, 'id_cgd');
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

    public function scopeForCgd(Builder $q, string $cgdId): Builder
    {
        return $q->where('id_cgd', $cgdId);
    }

    public function scopeKategori(Builder $q, string $kategori): Builder
    {
        return $q->where('kategori_hasil', $kategori);
    }

    public function scopeBetweenDates(Builder $q, string $start, string $end): Builder
    {
        return $q->whereBetween('tgl_cgd', [$start, $end]);
    }

    public function scopeToday(Builder $q): Builder
    {
        return $q->where('tgl_cgd', now()->format('Y-m-d'));
    }

    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        if (blank($term)) return $q;
        return $q->where(function ($qq) use ($term) {
            $qq->where('nama_pasien', 'like', "%{$term}%")
                ->orWhere('tempat_cgd', 'like', "%{$term}%");
        });
    }

    // ============ ACCESSORS ============

    public function getJamCgdFormatAttribute(): string
    {
        if (!$this->jam_cgd) return '-';
        return substr($this->jam_cgd, 0, 5);
    }

    public function getKategoriLabelAttribute(): string
    {
        return match ($this->kategori_hasil) {
            'normal'           => 'Normal Terkontrol',
            'tidak_terkontrol' => 'Tidak Terkontrol',
            'tinggi'           => 'Tinggi',
            'berbahaya'        => 'Berbahaya',
            default            => ucfirst(str_replace('_', ' ', $this->kategori_hasil)),
        };
    }

    public function getKategoriPesanAttribute(): string
    {
        return match ($this->kategori_hasil) {
            'normal'           => 'Gula darah normal. Tetap patuh minum obat ya!',
            'tidak_terkontrol' => 'Kurangi asupan gula. Patuh minum obat rutin ya!',
            'tinggi'           => 'Segera ke rumah sakit / puskesmas terdekat.',
            'berbahaya'        => 'Anda memerlukan bantuan dokter SEKARANG juga!',
            default            => '',
        };
    }

    public function getKategoriColorAttribute(): string
    {
        return match ($this->kategori_hasil) {
            'normal'           => 'success',
            'tidak_terkontrol' => 'warning',
            'tinggi'           => 'danger',
            'berbahaya'        => 'dark',
            default            => 'secondary',
        };
    }

    public function getKategoriIconAttribute(): string
    {
        return match ($this->kategori_hasil) {
            'normal'           => 'ri-check-double-line',
            'tidak_terkontrol' => 'ri-alert-line',
            'tinggi'           => 'ri-alarm-warning-line',
            'berbahaya'        => 'ri-error-warning-fill',
            default            => 'ri-question-line',
        };
    }

    public function getJenisKelaminLabelAttribute(): string
    {
        return match ($this->jenis_kelamin) {
            'L'     => 'Laki-laki',
            'P'     => 'Perempuan',
            default => '-',
        };
    }

    public function getPatuhLabelAttribute(): string
    {
        if ($this->patuh_selisih > 0) {
            return '+' . $this->patuh_selisih . ' mg/dL di atas batas normal';
        }
        if ($this->patuh_selisih < 0) {
            return abs($this->patuh_selisih) . ' mg/dL di bawah batas normal';
        }
        return 'Tepat di batas normal';
    }

    public function getBatasNormalAttribute(): int
    {
        return self::getBatasNormalPerGender($this->jenis_kelamin);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'aktif'    => 'Aktif',
            'nonaktif' => 'Nonaktif',
            default    => ucfirst($this->status),
        };
    }

    public function getFotoUrlAttribute(): ?string
    {
        return $this->foto_layar ? Storage::url($this->foto_layar) : null;
    }

    // ============ STATIC HELPERS ============

    public static function determineKategori(int $hasil): string
    {
        if ($hasil <= self::THRESHOLD_NORMAL) return 'normal';
        if ($hasil <= self::THRESHOLD_TIDAK_TERKONTROL) return 'tidak_terkontrol';
        if ($hasil <= self::THRESHOLD_TINGGI) return 'tinggi';
        return 'berbahaya';
    }

    public static function getBatasNormalPerGender(?string $jenisKelamin): int
    {
        return match ($jenisKelamin) {
            'L'     => self::BATAS_NORMAL_LAKI_LAKI,
            'P'     => self::BATAS_NORMAL_PEREMPUAN,
            default => self::BATAS_NORMAL_PEREMPUAN,
        };
    }

    public static function calculatePatuhSelisih(int $hasil, ?string $jenisKelamin): int
    {
        return $hasil - self::getBatasNormalPerGender($jenisKelamin);
    }
}
