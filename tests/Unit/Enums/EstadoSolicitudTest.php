<?php

namespace Tests\Unit\Enums;

use App\Enums\EstadoSolicitud;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class EstadoSolicitudTest extends TestCase
{
    /**
     * @return array<string, array{0: EstadoSolicitud, 1: EstadoSolicitud, 2: bool}>
     */
    public static function transiciones(): array
    {
        return [
            'abierta -> en_progreso' => [EstadoSolicitud::Abierta, EstadoSolicitud::EnProgreso, true],
            'abierta -> resuelta directo' => [EstadoSolicitud::Abierta, EstadoSolicitud::Resuelta, false],
            'abierta -> cerrada directo' => [EstadoSolicitud::Abierta, EstadoSolicitud::Cerrada, false],
            'en_progreso -> resuelta' => [EstadoSolicitud::EnProgreso, EstadoSolicitud::Resuelta, true],
            'en_progreso -> abierta' => [EstadoSolicitud::EnProgreso, EstadoSolicitud::Abierta, false],
            'resuelta -> cerrada' => [EstadoSolicitud::Resuelta, EstadoSolicitud::Cerrada, true],
            'resuelta -> reabierta' => [EstadoSolicitud::Resuelta, EstadoSolicitud::Reabierta, true],
            'resuelta -> en_progreso directo' => [EstadoSolicitud::Resuelta, EstadoSolicitud::EnProgreso, false],
            'cerrada -> reabierta' => [EstadoSolicitud::Cerrada, EstadoSolicitud::Reabierta, true],
            'cerrada -> en_progreso directo' => [EstadoSolicitud::Cerrada, EstadoSolicitud::EnProgreso, false],
            'reabierta -> en_progreso' => [EstadoSolicitud::Reabierta, EstadoSolicitud::EnProgreso, true],
            'reabierta -> resuelta directo' => [EstadoSolicitud::Reabierta, EstadoSolicitud::Resuelta, false],
        ];
    }

    #[DataProvider('transiciones')]
    public function test_transiciones_validas(EstadoSolicitud $desde, EstadoSolicitud $hasta, bool $esperado): void
    {
        $this->assertSame($esperado, $desde->puedeTransicionarA($hasta));
    }
}
