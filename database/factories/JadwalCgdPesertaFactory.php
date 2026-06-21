<?php

namespace Database\Factories;

use App\Models\JadwalCgd;
use App\Models\JadwalCgdPeserta;
use App\Models\PasienPmo;
use Illuminate\Database\Eloquent\Factories\Factory;

class JadwalCgdPesertaFactory extends Factory
{
    protected $model = JadwalCgdPeserta::class;

    public function definition(): array
    {
        return [
            'jadwal_cgd_id' => JadwalCgd::factory(),
            'id_pasien_pmo' => PasienPmo::factory(),
            'nama_pasien' => $this->faker->name(),
            'nama_pmo' => $this->faker->name(),
            'dikirim_dibuat_pada' => null,
            'dikirim_h1_pada' => null,
        ];
    }
}
