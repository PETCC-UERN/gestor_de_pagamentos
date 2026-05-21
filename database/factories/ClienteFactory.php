<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Cliente;

class ClienteFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Cliente::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'nome' => fake()->word(),
            'email' => fake()->safeEmail(),
            'telefone' => fake()->word(),
        ];
    }
}
