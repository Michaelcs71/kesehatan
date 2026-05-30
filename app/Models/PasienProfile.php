<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PasienProfile extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $fillable = [
        'user_id',
        'pmo_id',
        'nik',
        'no_bpjs',
        'tanggal_lahir',
        'jenis_kelamin',
        'alamat',
        'kota',
        'provinsi',
        'tipe_diabetes',
        'tanggal_diagnosis',
        'tinggi_badan',
        'berat_badan',
        'catatan_medis',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_lahir'     => 'date',
            'tanggal_diagnosis' => 'date',
            'tinggi_badan'      => 'decimal:2',
            'berat_badan'       => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function pmo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pmo_id');
    }

    public function getUmurAttribute(): ?int
    {
        return $this->tanggal_lahir?->age;
    }

    public function getBmiAttribute(): ?float
    {
        if (!$this->tinggi_badan || !$this->berat_badan) {
            return null;
        }
        $heightInMeters = $this->tinggi_badan / 100;
        return round($this->berat_badan / ($heightInMeters * $heightInMeters), 2);
    }
}