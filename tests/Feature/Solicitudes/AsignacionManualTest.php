<?php

namespace Tests\Feature\Solicitudes;

use App\Models\Solicitud;
use App\Models\User;
use App\Services\ConfigSystemService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class AsignacionManualTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        app(ConfigSystemService::class)->set('asignacion.modo', 'manual');
    }

    public function test_an_agente_can_assign_an_unassigned_solicitud_to_themselves(): void
    {
        $agente = User::factory()->agente()->create();
        $solicitud = Solicitud::factory()->create(['agente_id' => null]);

        $response = $this->patchJson("/api/solicitudes/{$solicitud->id}/asignar", [], [
            'Authorization' => 'Bearer '.JWTAuth::fromUser($agente),
        ]);

        $response->assertOk()->assertJsonFragment(['agente_id' => $agente->id]);
        $this->assertDatabaseHas('solicitudes', ['id' => $solicitud->id, 'agente_id' => $agente->id]);
    }

    public function test_assigning_an_already_assigned_solicitud_returns_a_conflict(): void
    {
        $agenteOriginal = User::factory()->agente()->create();
        $otroAgente = User::factory()->agente()->create();
        $solicitud = Solicitud::factory()->create(['agente_id' => $agenteOriginal->id]);

        $response = $this->patchJson("/api/solicitudes/{$solicitud->id}/asignar", [], [
            'Authorization' => 'Bearer '.JWTAuth::fromUser($otroAgente),
        ]);

        $response->assertStatus(409);
        $this->assertDatabaseHas('solicitudes', ['id' => $solicitud->id, 'agente_id' => $agenteOriginal->id]);
    }

    public function test_a_cliente_cannot_assign_a_solicitud(): void
    {
        $cliente = User::factory()->create();
        $solicitud = Solicitud::factory()->create(['agente_id' => null]);

        $this->patchJson("/api/solicitudes/{$solicitud->id}/asignar", [], [
            'Authorization' => 'Bearer '.JWTAuth::fromUser($cliente),
        ])->assertForbidden();
    }

    public function test_a_guest_cannot_assign_a_solicitud(): void
    {
        $solicitud = Solicitud::factory()->create(['agente_id' => null]);

        $this->patchJson("/api/solicitudes/{$solicitud->id}/asignar")->assertUnauthorized();
    }
}
