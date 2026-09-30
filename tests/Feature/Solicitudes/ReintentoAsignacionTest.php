<?php

namespace Tests\Feature\Solicitudes;

use App\Actions\Solicitudes\CambiarEstadoSolicitud;
use App\Enums\EstadoSolicitud;
use App\Jobs\ReintentarAsignacionesPendientes;
use App\Models\Solicitud;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ReintentoAsignacionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    private function resolver(Solicitud $solicitud): Solicitud
    {
        return app(CambiarEstadoSolicitud::class)($solicitud, EstadoSolicitud::Resuelta);
    }

    public function test_resolving_a_solicitud_dispatches_a_retry_for_pending_ones(): void
    {
        Queue::fake();

        $agente = User::factory()->agente()->create();
        $solicitud = Solicitud::factory()->create([
            'agente_id' => $agente->id,
            'estado' => EstadoSolicitud::EnProgreso,
        ]);

        $this->resolver($solicitud);

        Queue::assertPushed(ReintentarAsignacionesPendientes::class);
    }

    public function test_the_oldest_pending_solicitud_is_assigned_when_capacity_frees_up(): void
    {
        $agente = User::factory()->agente()->create();

        $ticketsActivos = Solicitud::factory()->count(5)->create([
            'agente_id' => $agente->id,
            'estado' => EstadoSolicitud::EnProgreso,
        ]);

        $pendienteAntigua = Solicitud::factory()->create(['agente_id' => null]);
        DB::table('solicitudes')->where('id', $pendienteAntigua->id)
            ->update(['created_at' => now()->subDay()]);

        $pendienteReciente = Solicitud::factory()->create(['agente_id' => null]);

        $this->resolver($ticketsActivos->first());

        $this->assertSame($agente->id, $pendienteAntigua->fresh()->agente_id);
        $this->assertNull($pendienteReciente->fresh()->agente_id);
    }

    public function test_closing_a_solicitud_without_pending_ones_does_nothing(): void
    {
        $agente = User::factory()->agente()->create();
        $solicitud = Solicitud::factory()->create([
            'agente_id' => $agente->id,
            'estado' => EstadoSolicitud::EnProgreso,
        ]);

        $this->resolver($solicitud);
        app(CambiarEstadoSolicitud::class)($solicitud->fresh(), EstadoSolicitud::Cerrada);

        $this->assertDatabaseCount('solicitudes', 1);
    }
}
