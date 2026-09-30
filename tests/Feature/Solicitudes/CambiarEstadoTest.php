<?php

namespace Tests\Feature\Solicitudes;

use App\Actions\Solicitudes\CambiarEstadoSolicitud;
use App\Enums\EstadoSolicitud;
use App\Exceptions\SolicitudEnConflictoException;
use App\Exceptions\TransicionEstadoInvalidaException;
use App\Models\Solicitud;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CambiarEstadoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_a_valid_transition_updates_the_estado_and_registers_historial(): void
    {
        $agente = User::factory()->agente()->create();
        $solicitud = Solicitud::factory()->create([
            'agente_id' => $agente->id,
            'estado' => EstadoSolicitud::Abierta,
        ]);

        $accion = app(CambiarEstadoSolicitud::class);
        $resultado = $accion($solicitud, EstadoSolicitud::EnProgreso);

        $this->assertSame(EstadoSolicitud::EnProgreso, $resultado->estado);
        $this->assertDatabaseHas('historial_solicitudes', [
            'solicitud_id' => $solicitud->id,
            'tipo' => 'cambio_estado',
        ]);

        $entrada = \App\Models\HistorialSolicitud::where('solicitud_id', $solicitud->id)
            ->where('tipo', 'cambio_estado')
            ->firstOrFail();

        $this->assertSame('abierta', $entrada->detalle['estado_anterior']);
        $this->assertSame('en_progreso', $entrada->detalle['estado_nuevo']);
    }

    public function test_repeating_the_same_target_estado_is_idempotent(): void
    {
        $solicitud = Solicitud::factory()->create(['estado' => EstadoSolicitud::EnProgreso]);

        $accion = app(CambiarEstadoSolicitud::class);
        $resultado = $accion($solicitud, EstadoSolicitud::EnProgreso);

        $this->assertSame(EstadoSolicitud::EnProgreso, $resultado->estado);
        $this->assertDatabaseMissing('historial_solicitudes', ['tipo' => 'cambio_estado']);
    }

    public function test_an_invalid_transition_throws(): void
    {
        $solicitud = Solicitud::factory()->create(['estado' => EstadoSolicitud::Abierta]);

        $this->expectException(TransicionEstadoInvalidaException::class);

        app(CambiarEstadoSolicitud::class)($solicitud, EstadoSolicitud::Cerrada);
    }

    public function test_a_lost_race_against_a_different_estado_throws_a_conflict(): void
    {
        $solicitud = Solicitud::factory()->create(['estado' => EstadoSolicitud::Resuelta]);

        $solicitudDesactualizada = Solicitud::find($solicitud->id);
        $solicitud->update(['estado' => EstadoSolicitud::Cerrada]);

        $this->expectException(SolicitudEnConflictoException::class);

        app(CambiarEstadoSolicitud::class)($solicitudDesactualizada, EstadoSolicitud::Reabierta);
    }

    public function test_a_lost_race_against_the_same_target_estado_is_treated_as_idempotent(): void
    {
        $solicitud = Solicitud::factory()->create(['estado' => EstadoSolicitud::Abierta]);

        $solicitudDesactualizada = Solicitud::find($solicitud->id);
        $solicitud->update(['estado' => EstadoSolicitud::EnProgreso]);

        $resultado = app(CambiarEstadoSolicitud::class)($solicitudDesactualizada, EstadoSolicitud::EnProgreso);

        $this->assertSame(EstadoSolicitud::EnProgreso, $resultado->estado);
    }
}
