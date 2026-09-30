<?php

namespace Tests\Feature\Solicitudes;

use App\Enums\EstadoSolicitud;
use App\Models\Solicitud;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class ListarSolicitudesTest extends TestCase
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

    public function test_a_cliente_only_sees_their_own_solicitudes(): void
    {
        $cliente = User::factory()->create();
        $otroCliente = User::factory()->create();

        Solicitud::factory()->create(['cliente_id' => $cliente->id]);
        Solicitud::factory()->create(['cliente_id' => $otroCliente->id]);

        $response = $this->getJson('/api/solicitudes', $this->headersPara($cliente));

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_an_agente_sees_all_solicitudes(): void
    {
        $agente = User::factory()->agente()->create();

        Solicitud::factory()->count(3)->create();

        $response = $this->getJson('/api/solicitudes', $this->headersPara($agente));

        $this->assertCount(3, $response->json('data'));
    }

    public function test_it_filters_by_estado(): void
    {
        $agente = User::factory()->agente()->create();

        Solicitud::factory()->create(['estado' => EstadoSolicitud::Abierta]);
        Solicitud::factory()->create(['estado' => EstadoSolicitud::EnProgreso]);

        $response = $this->getJson('/api/solicitudes?estado=en_progreso', $this->headersPara($agente));

        $this->assertCount(1, $response->json('data'));
        $this->assertSame('en_progreso', $response->json('data.0.estado'));
    }

    public function test_it_filters_by_prioridad(): void
    {
        $agente = User::factory()->agente()->create();

        Solicitud::factory()->create(['prioridad' => 'alta']);
        Solicitud::factory()->create(['prioridad' => 'baja']);

        $response = $this->getJson('/api/solicitudes?prioridad=alta', $this->headersPara($agente));

        $this->assertCount(1, $response->json('data'));
    }

    public function test_the_list_does_not_include_historial(): void
    {
        $agente = User::factory()->agente()->create();
        Solicitud::factory()->create();

        $response = $this->getJson('/api/solicitudes', $this->headersPara($agente));

        $this->assertArrayNotHasKey('comentarios', $response->json('data.0'));
        $this->assertArrayNotHasKey('cambios', $response->json('data.0'));
    }

    public function test_a_guest_cannot_list_solicitudes(): void
    {
        $this->getJson('/api/solicitudes')->assertUnauthorized();
    }
}
