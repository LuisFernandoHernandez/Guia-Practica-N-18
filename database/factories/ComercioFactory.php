<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ComercioFactory extends Factory
{
    protected $model = \App\Models\Comercio::class;

    public function definition(): array
    {
        return [
            'user_id'         => User::factory(), // Asocia automáticamente el usuario de prueba
            'nombre_comercio' => $this->faker->company(), // Nombre comercial obligatorio
            'rubro'           => $this->faker->word(), // Rubro obligatorio requerido por la base de datos
        ];
    }
}
