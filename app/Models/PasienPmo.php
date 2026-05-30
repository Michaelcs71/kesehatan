<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PasienPmo extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $table = 'pasien_pmos';

    protected $fillable = [
        'id_user',
        'pmo_user_id',
        'nama_pasien',
        'nik',
        'nama_pmo',
        'jenis_pmo',
        'tanggal_regis',
        'status_diabetes',
        'is_active',
        'catatan',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_regis' => 'date',
            'is_active'     => 'boolean',
        ];
    }

    // ============ RELATIONS ============

    /**
     * Pasien (user dengan role pasien)
     */
    public function pasien(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    /**
     * PMO (user dengan role pmo)
     */
    public function pmo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pmo_user_id');
    }

    /**
     * Audit relations
     */
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
        return $q->where('is_active', true);
    }

    public function scopeInactive(Builder $q): Builder
    {
        return $q->where('is_active', false);
    }

    public function scopeForPmo(Builder $q, string $pmoUserId): Builder
    {
        return $q->where('pmo_user_id', $pmoUserId);
    }

    public function scopeForPasien(Builder $q, string $pasienUserId): Builder
    {
        return $q->where('id_user', $pasienUserId);
    }

    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        if (blank($term)) return $q;
        return $q->where(function ($qq) use ($term) {
            $qq->where('nama_pasien', 'like', "%{$term}%")
                ->orWhere('nama_pmo', 'like', "%{$term}%")
                ->orWhere('nik', 'like', "%{$term}%");
        });
    }

    // ============ ACCESSORS ============

    public function getJenisPmoLabelAttribute(): string
    {
        return $this->jenis_pmo;
    }

    public function getStatusDiabetesLabelAttribute(): string
    {
        return $this->status_diabetes;
    }
}
