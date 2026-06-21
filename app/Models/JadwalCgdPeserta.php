<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JadwalCgdPeserta extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'jadwal_cgd_peserta';

    protected $fillable = [
        'jadwal_cgd_id',
        'id_pasien_pmo',
        'nama_pasien',
        'nama_pmo',
        'dikirim_dibuat_pada',
        'dikirim_h1_pada',
    ];

    protected function casts(): array
    {
        return [
            'dikirim_dibuat_pada' => 'datetime',
            'dikirim_h1_pada' => 'datetime',
        ];
    }

    public function jadwalCgd(): BelongsTo
    {
        return $this->belongsTo(JadwalCgd::class, 'jadwal_cgd_id');
    }

    public function pasienPmo(): BelongsTo
    {
        return $this->belongsTo(PasienPmo::class, 'id_pasien_pmo');
    }
}
