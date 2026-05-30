<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserBiodata extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $table = 'user_biodatas';

    protected $fillable = [
        'user_id',
        'nik',
        'no_kk',
        'jenis_kelamin',
        'tempat_lahir',
        'tanggal_lahir',
        'alamat_jalan',
        'alamat_rt',
        'alamat_rw',
        'alamat_dusun',
        'alamat_desa',
        'alamat_kecamatan',
        'alamat_kabupaten',
        'alamat_provinsi',
        'alamat_kodepos',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Accessor untuk display
    public function getAlamatLengkapAttribute(): string
    {
        $parts = array_filter([
            $this->alamat_jalan,
            $this->alamat_rt ? 'RT ' . $this->alamat_rt : null,
            $this->alamat_rw ? 'RW ' . $this->alamat_rw : null,
            $this->alamat_dusun,
            $this->alamat_desa,
            $this->alamat_kecamatan,
            $this->alamat_kabupaten,
            $this->alamat_provinsi,
            $this->alamat_kodepos,
        ]);

        return implode(', ', $parts);
    }

    public function getJenisKelaminLabelAttribute(): string
    {
        return match ($this->jenis_kelamin) {
            'L' => 'Laki-laki',
            'P' => 'Perempuan',
            default => '-',
        };
    }
}
