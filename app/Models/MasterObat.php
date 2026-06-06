<?php

namespace App\Models;

use App\Enums\StatusObat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class MasterObat extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'master_obats';

    protected $fillable = [
        'nama',
        'kategori_id',
        'dosis_default',
        'satuan_id',
        'deskripsi',
        'aturan_minum',
        'efek_samping',
        'kontraindikasi',
        'foto_path',
        'status',
        'created_by',
        'verified_by',
        'verified_at',
        'catatan_verifikasi',
    ];

    protected function casts(): array
    {
        return [
            'status' => StatusObat::class,
            'verified_at' => 'datetime',
        ];
    }

    /* ========== RELATIONS ========== */

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(MasterKategoriObat::class, 'kategori_id');
    }

    public function satuan(): BelongsTo
    {
        return $this->belongsTo(MasterSatuanObat::class, 'satuan_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /* ========== SCOPES ========== */

    public function scopeApproved(Builder $q): Builder
    {
        return $q->where('status', StatusObat::APPROVED->value);
    }

    public function scopePending(Builder $q): Builder
    {
        return $q->where('status', StatusObat::PENDING->value);
    }

    public function scopeRejected(Builder $q): Builder
    {
        return $q->where('status', StatusObat::REJECTED->value);
    }

    public function scopeOwnedBy(Builder $q, string $userId): Builder
    {
        return $q->where('created_by', $userId);
    }

    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        if (blank($term)) {
            return $q;
        }

        return $q->where(function ($q) use ($term) {
            $q->where('nama', 'like', "%{$term}%")
                ->orWhere('dosis_default', 'like', "%{$term}%");
        });
    }

    public function scopeKategoriId(Builder $q, ?string $kategoriId): Builder
    {
        if (blank($kategoriId)) {
            return $q;
        }

        return $q->where('kategori_id', $kategoriId);
    }

    /* ========== HELPERS ========== */

    public function isPending(): bool
    {
        return $this->status === StatusObat::PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === StatusObat::APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === StatusObat::REJECTED;
    }

    public function getFotoUrlAttribute(): ?string
    {
        return $this->foto_path ? Storage::url($this->foto_path) : null;
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->nama.' ('.($this->dosis_default ?? '-').')';
    }

    public function getKategoriNamaAttribute(): ?string
    {
        return $this->kategori?->nama;
    }

    public function getSatuanNamaAttribute(): ?string
    {
        return $this->satuan?->nama;
    }
}
