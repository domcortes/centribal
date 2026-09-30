<?php

namespace Tests\Unit\Policies;

use App\Enums\EstadoSolicitud;
use App\Models\Solicitud;
use App\Models\User;
use App\Policies\SolicitudPolicy;
use App\Services\ConfigSystemService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SolicitudPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_an_agente_can_update_their_own_ticket(): void
    {
        $agente = User::factory()->agente()->create();
        $solicitud = Solicitud::factory()->create(['agente_id' => $agente->id]);

        $this->assertTrue(
            app(SolicitudPolicy::class)->actualizarEstado($agente, $solicitud, EstadoSolicitud::EnProgreso),
        );
    }

    public function test_an_agente_cannot_update_an_unassigned_ticket(): void
    {
        $agente = User::factory()->agente()->create();
        $solicitud = Solicitud::factory()->create(['agente_id' => null]);

        $this->assertFalse(
            app(SolicitudPolicy::class)->actualizarEstado($agente, $solicitud, EstadoSolicitud::EnProgreso),
        );
    }

    public function test_an_agente_can_update_a_foreign_ticket_when_the_flag_allows_it(): void
    {
        app(ConfigSystemService::class)->set('estado.permitir_modificar_ticket_ajeno', 'true');

        $dueno = User::factory()->agente()->create();
        $otroAgente = User::factory()->agente()->create();
        $solicitud = Solicitud::factory()->create(['agente_id' => $dueno->id]);

        $this->assertTrue(
            app(SolicitudPolicy::class)->actualizarEstado($otroAgente, $solicitud, EstadoSolicitud::Resuelta),
        );
    }

    public function test_an_agente_cannot_update_a_foreign_ticket_when_the_flag_forbids_it(): void
    {
        app(ConfigSystemService::class)->set('estado.permitir_modificar_ticket_ajeno', 'false');

        $dueno = User::factory()->agente()->create();
        $otroAgente = User::factory()->agente()->create();
        $solicitud = Solicitud::factory()->create(['agente_id' => $dueno->id]);

        $this->assertFalse(
            app(SolicitudPolicy::class)->actualizarEstado($otroAgente, $solicitud, EstadoSolicitud::Resuelta),
        );
    }

    public function test_a_cliente_can_reopen_their_own_resolved_ticket(): void
    {
        $cliente = User::factory()->create();
        $solicitud = Solicitud::factory()->create([
            'cliente_id' => $cliente->id,
            'estado' => EstadoSolicitud::Resuelta,
        ]);

        $this->assertTrue(
            app(SolicitudPolicy::class)->actualizarEstado($cliente, $solicitud, EstadoSolicitud::Reabierta),
        );
    }

    public function test_a_cliente_cannot_reopen_a_ticket_that_is_not_resolved_or_closed(): void
    {
        $cliente = User::factory()->create();
        $solicitud = Solicitud::factory()->create([
            'cliente_id' => $cliente->id,
            'estado' => EstadoSolicitud::Abierta,
        ]);

        $this->assertFalse(
            app(SolicitudPolicy::class)->actualizarEstado($cliente, $solicitud, EstadoSolicitud::Reabierta),
        );
    }

    public function test_a_cliente_cannot_set_any_other_state(): void
    {
        $cliente = User::factory()->create();
        $solicitud = Solicitud::factory()->create([
            'cliente_id' => $cliente->id,
            'estado' => EstadoSolicitud::Abierta,
        ]);

        $this->assertFalse(
            app(SolicitudPolicy::class)->actualizarEstado($cliente, $solicitud, EstadoSolicitud::EnProgreso),
        );
    }

    public function test_a_cliente_cannot_reopen_someone_elses_ticket(): void
    {
        $cliente = User::factory()->create();
        $solicitud = Solicitud::factory()->create([
            'estado' => EstadoSolicitud::Resuelta,
        ]);

        $this->assertFalse(
            app(SolicitudPolicy::class)->actualizarEstado($cliente, $solicitud, EstadoSolicitud::Reabierta),
        );
    }

    public function test_an_agente_can_comment_on_their_own_ticket(): void
    {
        $agente = User::factory()->agente()->create();
        $solicitud = Solicitud::factory()->create(['agente_id' => $agente->id]);

        $this->assertTrue(app(SolicitudPolicy::class)->comentar($agente, $solicitud));
    }

    public function test_an_agente_cannot_comment_on_an_unassigned_ticket(): void
    {
        $agente = User::factory()->agente()->create();
        $solicitud = Solicitud::factory()->create(['agente_id' => null]);

        $this->assertFalse(app(SolicitudPolicy::class)->comentar($agente, $solicitud));
    }

    public function test_an_agente_can_comment_on_a_foreign_ticket_when_the_flag_allows_it(): void
    {
        app(ConfigSystemService::class)->set('estado.permitir_modificar_ticket_ajeno', 'true');

        $dueno = User::factory()->agente()->create();
        $otroAgente = User::factory()->agente()->create();
        $solicitud = Solicitud::factory()->create(['agente_id' => $dueno->id]);

        $this->assertTrue(app(SolicitudPolicy::class)->comentar($otroAgente, $solicitud));
    }

    public function test_an_agente_cannot_comment_on_a_foreign_ticket_when_the_flag_forbids_it(): void
    {
        app(ConfigSystemService::class)->set('estado.permitir_modificar_ticket_ajeno', 'false');

        $dueno = User::factory()->agente()->create();
        $otroAgente = User::factory()->agente()->create();
        $solicitud = Solicitud::factory()->create(['agente_id' => $dueno->id]);

        $this->assertFalse(app(SolicitudPolicy::class)->comentar($otroAgente, $solicitud));
    }

    public function test_a_cliente_cannot_comment(): void
    {
        $cliente = User::factory()->create();
        $solicitud = Solicitud::factory()->create(['cliente_id' => $cliente->id]);

        $this->assertFalse(app(SolicitudPolicy::class)->comentar($cliente, $solicitud));
    }
}
