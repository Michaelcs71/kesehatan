<?php

namespace Database\Factories;

use App\Models\PasienPmo;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PasienPmoFactory extends Factory
{
    protected $model = PasienPmo::class;

    public function definition(): array
    {
        return [
            'id_user'         => User::factory(),
            'pmo_user_id'     => User::factory(),
            'nama_pasien'     => $this->faker->name(),
            'nik'             => (string) $this->faker->numerify('################'),
            'nama_pmo'        => $this->faker->name(),
            'jenis_pmo'       => 'Keluarga',
            'tanggal_regis'   => now()->toDateString(),
            'status_diabetes' => 'Sedang',
            'is_active'       => true,
        ];
    }
}
