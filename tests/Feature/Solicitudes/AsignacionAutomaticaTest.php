<?php

namespace Tests\Feature\Solicitudes;

use App\Enums\EstadoSolicitud;
use App\Models\Solicitud;
use App\Models\User;
use App\Services\ConfigSystemService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class AsignacionAutomaticaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    private function crearSolicitudComoCliente(): Solicitud
    {
        $cliente = User::factory()->create();

        $response = $this->postJson('/api/solicitudes', [
            'asunto' => 'Asunto',
            'descripcion' => 'Descripción',
            'prioridad' => 'media',
        ], ['Authorization' => 'Bearer '.JWTAuth::fromUser($cliente)])->assertCreated();

        return Solicitud::findOrFail($response->json('id'));
    }

    public function test_a_new_solicitud_is_assigned_to_the_agente_with_least_historical_load(): void
    {
        $agenteOcupado = User::factory()->agente()->create();
        $agenteConMenosHistorial = User::factory()->agente()->create();
        $agenteConMasHistorial = User::factory()->agente()->create();

        Solicitud::factory()->count(6)->create([
            'agente_id' => $agenteOcupado->id,
            'estado' => EstadoSolicitud::EnProgreso,
        ]);
        Solicitud::factory()->count(3)->create([
            'agente_id' => $agenteConMenosHistorial->id,
            'estado' => EstadoSolicitud::Resuelta,
        ]);
        Solicitud::factory()->count(5)->create([
            'agente_id' => $agenteConMasHistorial->id,
            'estado' => EstadoSolicitud::Resuelta,
        ]);

        $solicitud = $this->crearSolicitudComoCliente();

        $this->assertSame($agenteConMenosHistorial->id, $solicitud->fresh()->agente_id);
    }

    public function test_an_agente_at_or_over_the_threshold_is_not_considered_available(): void
    {
        app(ConfigSystemService::class)->set('asignacion.max_tickets_concurrentes', '2');

        $agenteOcupado = User::factory()->agente()->create();
        Solicitud::factory()->count(2)->create([
            'agente_id' => $agenteOcupado->id,
            'estado' => EstadoSolicitud::EnProgreso,
        ]);

        $solicitud = $this->crearSolicitudComoCliente();

        $this->assertNull($solicitud->fresh()->agente_id);
    }

    public function test_solicitud_remains_unassigned_when_no_agente_exists(): void
    {
        $solicitud = $this->crearSolicitudComoCliente();

        $this->assertNull($solicitud->fresh()->agente_id);
    }

    public function test_automatic_assignment_is_skipped_when_mode_is_manual(): void
    {
        app(ConfigSystemService::class)->set('asignacion.modo', 'manual');

        User::factory()->agente()->create();

        $solicitud = $this->crearSolicitudComoCliente();

        $this->assertNull($solicitud->fresh()->agente_id);
    }
}
