<?php

namespace Database\Factories;

use App\Enums\EstadoSolicitud;
use App\Enums\Prioridad;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Solicitud>
 */
class SolicitudFactory extends Factory
{
    public function definition(): array
    {
        return [
            'cliente_id' => User::factory(),
            'agente_id' => null,
            'asunto' => fake()->sentence(4),
            'descripcion' => fake()->paragraph(),
            'prioridad' => fake()->randomElement(Prioridad::cases()),
            'estado' => EstadoSolicitud::Abierta,
        ];
    }
}
