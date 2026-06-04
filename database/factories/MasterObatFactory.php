<?php

namespace Database\Factories;

use App\Models\MasterObat;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MasterObatFactory extends Factory
{
    protected $model = MasterObat::class;

    public function definition(): array
    {
        return [
            'nama' => 'Metformin '.$this->faker->numberBetween(1, 9999),
            'status' => 'approved',
            'created_by' => User::factory(),
        ];
    }
}
