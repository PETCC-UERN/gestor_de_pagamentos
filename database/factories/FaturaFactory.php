<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Cliente;
use App\Models\Fatura;

class FaturaFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Fatura::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'cliente_id' => Cliente::factory(),
            'valor' => fake()->randomFloat(2, 0, 99999999.99),
            'vencimento' => fake()->date(),
            'status' => fake()->word(),
        ];
    }
}
