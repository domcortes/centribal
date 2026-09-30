<?php

namespace Tests\Unit\Services;

use App\Services\ConfigSystemService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ConfigSystemServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_it_reads_the_seeded_default_values(): void
    {
        $config = app(ConfigSystemService::class);

        $this->assertSame('automatica', $config->get('asignacion.modo'));
        $this->assertSame('5', $config->get('asignacion.max_tickets_concurrentes'));
    }

    public function test_it_returns_the_default_when_the_key_does_not_exist(): void
    {
        $config = app(ConfigSystemService::class);

        $this->assertSame('valor-por-defecto', $config->get('clave.inexistente', 'valor-por-defecto'));
    }

    public function test_set_overwrites_the_value_and_the_cache_immediately(): void
    {
        $config = app(ConfigSystemService::class);

        $config->set('asignacion.modo', 'manual');

        $this->assertSame('manual', $config->get('asignacion.modo'));
        $this->assertDatabaseHas('config_system', ['clave' => 'asignacion.modo', 'valor' => 'manual']);
    }
}
