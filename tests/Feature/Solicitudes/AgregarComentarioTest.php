<?php

namespace Tests\Feature\Solicitudes;

use App\Actions\Solicitudes\AgregarComentarioSolicitud;
use App\Enums\TipoEventoHistorial;
use App\Models\HistorialSolicitud;
use App\Models\Solicitud;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AgregarComentarioTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_it_creates_a_historial_entry_of_type_comentario(): void
    {
        $agente = User::factory()->agente()->create();
        $solicitud = Solicitud::factory()->create(['agente_id' => $agente->id]);

        $entrada = app(AgregarComentarioSolicitud::class)($solicitud, $agente, 'Contacté al cliente por teléfono.');

        $this->assertSame(TipoEventoHistorial::Comentario, $entrada->tipo);
        $this->assertSame('Contacté al cliente por teléfono.', $entrada->detalle['texto']);
        $this->assertSame($agente->id, $entrada->user_id);
        $this->assertTrue($entrada->wasRecentlyCreated);
    }

    public function test_repeating_the_same_idempotency_key_does_not_duplicate_the_comment(): void
    {
        $agente = User::factory()->agente()->create();
        $solicitud = Solicitud::factory()->create(['agente_id' => $agente->id]);
        $accion = app(AgregarComentarioSolicitud::class);

        $primero = $accion($solicitud, $agente, 'Texto original', 'clave-1');
        $segundo = $accion($solicitud, $agente, 'Texto distinto ignorado', 'clave-1');

        $this->assertSame($primero->id, $segundo->id);
        $this->assertSame(1, HistorialSolicitud::where('tipo', 'comentario')->count());
    }

    public function test_two_different_agentes_can_reuse_the_same_idempotency_key(): void
    {
        $agenteA = User::factory()->agente()->create();
        $agenteB = User::factory()->agente()->create();
        $solicitud = Solicitud::factory()->create(['agente_id' => $agenteA->id]);
        $accion = app(AgregarComentarioSolicitud::class);

        $accion($solicitud, $agenteA, 'Comentario de A', 'clave-compartida');
        $accion($solicitud, $agenteB, 'Comentario de B', 'clave-compartida');

        $this->assertSame(2, HistorialSolicitud::where('tipo', 'comentario')->count());
    }
}
