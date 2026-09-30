<?php

namespace Tests\Feature\Solicitudes;

use App\Actions\Solicitudes\CrearSolicitud;
use App\Enums\EstadoSolicitud;
use App\Models\Solicitud;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class SolicitudIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_repeating_the_same_idempotency_key_does_not_duplicate_the_solicitud(): void
    {
        $cliente = User::factory()->create();
        $headers = [
            'Authorization' => 'Bearer '.JWTAuth::fromUser($cliente),
            'Idempotency-Key' => 'clave-123',
        ];
        $payload = ['asunto' => 'Asunto', 'descripcion' => 'Descripción', 'prioridad' => 'baja'];

        $first = $this->postJson('/api/solicitudes', $payload, $headers)->assertCreated();
        $second = $this->postJson('/api/solicitudes', $payload, $headers)->assertOk();

        $this->assertSame($first->json('id'), $second->json('id'));
        $this->assertDatabaseCount('solicitudes', 1);
    }

    public function test_the_same_idempotency_key_can_be_reused_by_a_different_cliente(): void
    {
        $clienteA = User::factory()->create();
        $clienteB = User::factory()->create();
        $accion = app(CrearSolicitud::class);
        $datos = ['asunto' => 'Asunto', 'descripcion' => 'Descripción', 'prioridad' => 'baja'];

        $solicitudA = $accion($clienteA, $datos, 'clave-compartida');
        $solicitudB = $accion($clienteB, $datos, 'clave-compartida');

        $this->assertNotSame($solicitudA->id, $solicitudB->id);
        $this->assertDatabaseCount('solicitudes', 2);
    }

    public function test_the_unique_index_rejects_a_duplicate_key_at_the_database_level(): void
    {
        $cliente = User::factory()->create();

        Solicitud::create([
            'cliente_id' => $cliente->id,
            'asunto' => 'Asunto',
            'descripcion' => 'Descripción',
            'prioridad' => 'baja',
            'estado' => EstadoSolicitud::Abierta,
            'idempotency_key' => 'clave-duplicada',
        ]);

        $this->expectException(QueryException::class);

        Solicitud::create([
            'cliente_id' => $cliente->id,
            'asunto' => 'Otro asunto',
            'descripcion' => 'Otra descripción',
            'prioridad' => 'alta',
            'estado' => EstadoSolicitud::Abierta,
            'idempotency_key' => 'clave-duplicada',
        ]);
    }
}
