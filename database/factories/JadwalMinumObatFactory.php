<?php

namespace Database\Factories;

use App\Models\JadwalMinumObat;
use App\Models\MasterObat;
use App\Models\PasienPmo;
use Illuminate\Database\Eloquent\Factories\Factory;

class JadwalMinumObatFactory extends Factory
{
    protected $model = JadwalMinumObat::class;

    public function definition(): array
    {
        return [
            'id_pasien_pmo'      => PasienPmo::factory(),
            'obat_id'            => MasterObat::factory(),
            'nama_pasien'        => $this->faker->name(),
            'nama_pmo'           => $this->faker->name(),
            'tgl_mulai'          => now()->subDay()->toDateString(),
            'jam_mulai'          => '08:00:00',
            'frekuensi_per_hari' => 1,
            'status'             => 'aktif',
        ];
    }
}
