<?php

namespace Tests\Feature\Solicitudes;

use App\Actions\Solicitudes\AgregarComentarioSolicitud;
use App\Models\Solicitud;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class DetalleSolicitudTest extends TestCase
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

    public function test_an_agente_sees_comentarios_and_cambios(): void
    {
        $agente = User::factory()->agente()->create();
        $solicitud = Solicitud::factory()->create(['agente_id' => $agente->id]);

        $this->postJson(
            "/api/solicitudes/{$solicitud->id}/comentarios",
            ['texto' => 'Nota interna'],
            $this->headersPara($agente),
        )->assertCreated();

        $response = $this->getJson("/api/solicitudes/{$solicitud->id}", $this->headersPara($agente));

        $response->assertOk();
        $this->assertCount(1, $response->json('data.comentarios'));
        $this->assertSame('Nota interna', $response->json('data.comentarios.0.detalle.texto'));
        $this->assertIsArray($response->json('data.cambios'));
    }

    public function test_a_cliente_sees_cambios_but_never_comentarios(): void
    {
        $cliente = User::factory()->create();
        $agente = User::factory()->agente()->create();
        $solicitud = Solicitud::factory()->create([
            'cliente_id' => $cliente->id,
            'agente_id' => $agente->id,
        ]);

        app(AgregarComentarioSolicitud::class)($solicitud, $agente, 'Nota interna, invisible para el cliente');

        $response = $this->getJson("/api/solicitudes/{$solicitud->id}", $this->headersPara($cliente));

        $response->assertOk();
        $this->assertArrayNotHasKey('comentarios', $response->json('data'));
    }

    public function test_a_cliente_cannot_view_someone_elses_solicitud(): void
    {
        $cliente = User::factory()->create();
        $solicitud = Solicitud::factory()->create();

        $this->getJson("/api/solicitudes/{$solicitud->id}", $this->headersPara($cliente))
            ->assertForbidden();
    }

    public function test_an_agente_can_view_any_solicitud(): void
    {
        $agente = User::factory()->agente()->create();
        $solicitud = Solicitud::factory()->create();

        $this->getJson("/api/solicitudes/{$solicitud->id}", $this->headersPara($agente))
            ->assertOk();
    }

    public function test_a_guest_cannot_view_a_solicitud(): void
    {
        $solicitud = Solicitud::factory()->create();

        $this->getJson("/api/solicitudes/{$solicitud->id}")->assertUnauthorized();
    }
}
