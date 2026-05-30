<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterKategoriObat extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $fillable = [
        'nama',
        'deskripsi',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    // Relations
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function obats(): HasMany
    {
        return $this->hasMany(MasterObat::class, 'kategori_id');
    }

    // Scopes
    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        if (blank($term)) return $q;
        return $q->where(function ($qq) use ($term) {
            $qq->where('nama', 'ilike', "%{$term}%")
               ->orWhere('deskripsi', 'ilike', "%{$term}%");
        });
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }
}