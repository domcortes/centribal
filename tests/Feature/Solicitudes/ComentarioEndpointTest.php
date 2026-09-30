<?php

namespace Tests\Feature\Solicitudes;

use App\Models\Solicitud;
use App\Models\User;
use App\Services\ConfigSystemService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class ComentarioEndpointTest extends TestCase
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

    public function test_the_assigned_agente_can_comment(): void
    {
        $agente = User::factory()->agente()->create();
        $solicitud = Solicitud::factory()->create(['agente_id' => $agente->id]);

        $response = $this->postJson(
            "/api/solicitudes/{$solicitud->id}/comentarios",
            ['texto' => 'Esperando respuesta del cliente.'],
            $this->headersPara($agente),
        );

        $response->assertCreated()->assertJsonFragment(['tipo' => 'comentario']);
    }

    public function test_a_foreign_agente_is_forbidden_when_the_flag_is_disabled(): void
    {
        app(ConfigSystemService::class)->set('estado.permitir_modificar_ticket_ajeno', 'false');

        $dueno = User::factory()->agente()->create();
        $otroAgente = User::factory()->agente()->create();
        $solicitud = Solicitud::factory()->create(['agente_id' => $dueno->id]);

        $this->postJson(
            "/api/solicitudes/{$solicitud->id}/comentarios",
            ['texto' => 'No debería poder.'],
            $this->headersPara($otroAgente),
        )->assertForbidden();
    }

    public function test_a_cliente_cannot_comment(): void
    {
        $cliente = User::factory()->create();
        $solicitud = Solicitud::factory()->create(['cliente_id' => $cliente->id]);

        $this->postJson(
            "/api/solicitudes/{$solicitud->id}/comentarios",
            ['texto' => 'Intento del cliente.'],
            $this->headersPara($cliente),
        )->assertForbidden();
    }

    public function test_a_guest_cannot_comment(): void
    {
        $solicitud = Solicitud::factory()->create();

        $this->postJson("/api/solicitudes/{$solicitud->id}/comentarios", ['texto' => 'x'])
            ->assertUnauthorized();
    }

    public function test_texto_is_required(): void
    {
        $agente = User::factory()->agente()->create();
        $solicitud = Solicitud::factory()->create(['agente_id' => $agente->id]);

        $this->postJson(
            "/api/solicitudes/{$solicitud->id}/comentarios",
            [],
            $this->headersPara($agente),
        )->assertUnprocessable();
    }

    public function test_repeating_the_idempotency_key_returns_the_same_comment_with_200(): void
    {
        $agente = User::factory()->agente()->create();
        $solicitud = Solicitud::factory()->create(['agente_id' => $agente->id]);
        $headers = $this->headersPara($agente) + ['Idempotency-Key' => 'clave-http'];

        $primero = $this->postJson(
            "/api/solicitudes/{$solicitud->id}/comentarios",
            ['texto' => 'Texto'],
            $headers,
        )->assertCreated();

        $segundo = $this->postJson(
            "/api/solicitudes/{$solicitud->id}/comentarios",
            ['texto' => 'Texto'],
            $headers,
        )->assertOk();

        $this->assertSame($primero->json('id'), $segundo->json('id'));
    }
}
