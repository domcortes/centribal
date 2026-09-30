<?php

namespace Tests\Feature\Solicitudes;

use App\Enums\TipoEventoHistorial;
use App\Jobs\RegistrarEventoHistorial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class SolicitudHistorialTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_solicitud_dispatches_the_historial_job(): void
    {
        Queue::fake();

        $cliente = User::factory()->create();

        $this->postJson('/api/solicitudes', [
            'asunto' => 'Asunto',
            'descripcion' => 'Descripción',
            'prioridad' => 'media',
        ], ['Authorization' => 'Bearer '.JWTAuth::fromUser($cliente)])->assertCreated();

        Queue::assertPushed(RegistrarEventoHistorial::class, function (RegistrarEventoHistorial $job) use ($cliente) {
            return $job->userId === $cliente->id
                && $job->tipo === TipoEventoHistorial::Creada;
        });
    }

    public function test_the_historial_job_persists_the_event(): void
    {
        $cliente = User::factory()->create();

        $this->postJson('/api/solicitudes', [
            'asunto' => 'Asunto',
            'descripcion' => 'Descripción',
            'prioridad' => 'media',
        ], ['Authorization' => 'Bearer '.JWTAuth::fromUser($cliente)])->assertCreated();

        $this->assertDatabaseCount('historial_solicitudes', 1);
    }
}
