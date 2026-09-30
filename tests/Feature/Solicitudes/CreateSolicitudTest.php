<?php

namespace Tests\Feature\Solicitudes;

use App\Enums\EstadoSolicitud;
use App\Enums\TipoEventoHistorial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class CreateSolicitudTest extends TestCase
{
    use RefreshDatabase;

    private function tokenFor(User $user): string
    {
        return JWTAuth::fromUser($user);
    }

    public function test_a_cliente_can_create_a_solicitud(): void
    {
        $cliente = User::factory()->create();

        $response = $this->postJson('/api/solicitudes', [
            'asunto' => 'No puedo iniciar sesión',
            'descripcion' => 'Me aparece un error 500 al intentar entrar al portal.',
            'prioridad' => 'alta',
        ], ['Authorization' => 'Bearer '.$this->tokenFor($cliente)]);

        $response->assertCreated()->assertJsonFragment([
            'asunto' => 'No puedo iniciar sesión',
            'prioridad' => 'alta',
            'estado' => EstadoSolicitud::Abierta->value,
        ]);

        $this->assertDatabaseHas('solicitudes', [
            'cliente_id' => $cliente->id,
            'agente_id' => null,
            'estado' => EstadoSolicitud::Abierta->value,
        ]);

        $this->assertDatabaseHas('historial_solicitudes', [
            'solicitud_id' => $response->json('id'),
            'user_id' => $cliente->id,
            'tipo' => TipoEventoHistorial::Creada->value,
        ]);
    }

    public function test_an_agente_cannot_create_a_solicitud(): void
    {
        $agente = User::factory()->agente()->create();

        $response = $this->postJson('/api/solicitudes', [
            'asunto' => 'Asunto',
            'descripcion' => 'Descripción',
            'prioridad' => 'baja',
        ], ['Authorization' => 'Bearer '.$this->tokenFor($agente)]);

        $response->assertForbidden();
        $this->assertDatabaseCount('solicitudes', 0);
    }

    public function test_a_guest_cannot_create_a_solicitud(): void
    {
        $this->postJson('/api/solicitudes', [
            'asunto' => 'Asunto',
            'descripcion' => 'Descripción',
            'prioridad' => 'baja',
        ])->assertUnauthorized();
    }

    public function test_prioridad_must_be_a_valid_value(): void
    {
        $cliente = User::factory()->create();

        $response = $this->postJson('/api/solicitudes', [
            'asunto' => 'Asunto',
            'descripcion' => 'Descripción',
            'prioridad' => 'urgentisima',
        ], ['Authorization' => 'Bearer '.$this->tokenFor($cliente)]);

        $response->assertUnprocessable()->assertJsonValidationErrors('prioridad');
    }

    public function test_asunto_descripcion_y_prioridad_son_requeridos(): void
    {
        $cliente = User::factory()->create();

        $response = $this->postJson('/api/solicitudes', [], [
            'Authorization' => 'Bearer '.$this->tokenFor($cliente),
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors(['asunto', 'descripcion', 'prioridad']);
    }
}
