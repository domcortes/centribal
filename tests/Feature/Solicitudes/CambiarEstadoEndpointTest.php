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

class CambiarEstadoEndpointTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    private function headersPara(User $user): array
    {
        return ['Authorization' => 'Bearer '.JWTAuth::fromUser($user)];
    }

    public function test_the_assigned_agente_can_change_the_estado(): void
    {
        $agente = User::factory()->agente()->create();
        $solicitud = Solicitud::factory()->create([
            'agente_id' => $agente->id,
            'estado' => EstadoSolicitud::Abierta,
        ]);

        $response = $this->patchJson(
            "/api/solicitudes/{$solicitud->id}/estado",
            ['estado' => 'en_progreso'],
            $this->headersPara($agente),
        );

        $response->assertOk()->assertJsonFragment(['estado' => 'en_progreso']);
    }

    public function test_a_foreign_agente_is_forbidden_when_the_flag_is_disabled(): void
    {
        app(ConfigSystemService::class)->set('estado.permitir_modificar_ticket_ajeno', 'false');

        $dueno = User::factory()->agente()->create();
        $otroAgente = User::factory()->agente()->create();
        $solicitud = Solicitud::factory()->create([
            'agente_id' => $dueno->id,
            'estado' => EstadoSolicitud::Abierta,
        ]);

        $this->patchJson(
            "/api/solicitudes/{$solicitud->id}/estado",
            ['estado' => 'en_progreso'],
            $this->headersPara($otroAgente),
        )->assertForbidden();
    }

    public function test_an_invalid_transition_returns_a_conflict(): void
    {
        $agente = User::factory()->agente()->create();
        $solicitud = Solicitud::factory()->create([
            'agente_id' => $agente->id,
            'estado' => EstadoSolicitud::Abierta,
        ]);

        $this->patchJson(
            "/api/solicitudes/{$solicitud->id}/estado",
            ['estado' => 'cerrada'],
            $this->headersPara($agente),
        )->assertStatus(409);
    }

    public function test_a_cliente_can_reopen_their_own_resolved_ticket(): void
    {
        $cliente = User::factory()->create();
        $solicitud = Solicitud::factory()->create([
            'cliente_id' => $cliente->id,
            'estado' => EstadoSolicitud::Resuelta,
        ]);

        $this->patchJson(
            "/api/solicitudes/{$solicitud->id}/estado",
            ['estado' => 'reabierta'],
            $this->headersPara($cliente),
        )->assertOk()->assertJsonFragment(['estado' => 'reabierta']);
    }

    public function test_a_cliente_cannot_set_an_arbitrary_estado(): void
    {
        $cliente = User::factory()->create();
        $solicitud = Solicitud::factory()->create([
            'cliente_id' => $cliente->id,
            'estado' => EstadoSolicitud::Abierta,
        ]);

        $this->patchJson(
            "/api/solicitudes/{$solicitud->id}/estado",
            ['estado' => 'en_progreso'],
            $this->headersPara($cliente),
        )->assertForbidden();
    }

    public function test_estado_must_be_a_valid_value(): void
    {
        $agente = User::factory()->agente()->create();
        $solicitud = Solicitud::factory()->create(['agente_id' => $agente->id]);

        $this->patchJson(
            "/api/solicitudes/{$solicitud->id}/estado",
            ['estado' => 'inventado'],
            $this->headersPara($agente),
        )->assertUnprocessable();
    }

    public function test_a_guest_cannot_change_estado(): void
    {
        $solicitud = Solicitud::factory()->create();

        $this->patchJson("/api/solicitudes/{$solicitud->id}/estado", ['estado' => 'en_progreso'])
            ->assertUnauthorized();
    }
}
