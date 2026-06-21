<?php

namespace Database\Factories;

use App\Models\JadwalCgd;
use Illuminate\Database\Eloquent\Factories\Factory;

class JadwalCgdFactory extends Factory
{
    protected $model = JadwalCgd::class;

    public function definition(): array
    {
        return [
            'tgl_input' => now()->toDateString(),
            'tgl_jadwal_cgd' => now()->addDays(3)->toDateString(),
            'jam_mulai' => '07:00:00',
            'jam_berakhir' => '10:00:00',
            'puasa' => 'Wajib',
            'tempat' => $this->faker->city(),
            'status' => 'aktif',
        ];
    }
}
