<?php

namespace App\Enums;

enum EstadoSolicitud: string
{
    case Abierta = 'abierta';
    case EnProgreso = 'en_progreso';
    case Resuelta = 'resuelta';
    case Cerrada = 'cerrada';
    case Reabierta = 'reabierta';

    public function puedeTransicionarA(self $destino): bool
    {
        return match ($this) {
            self::Abierta => $destino === self::EnProgreso,
            self::EnProgreso => $destino === self::Resuelta,
            self::Resuelta => in_array($destino, [self::Cerrada, self::Reabierta], true),
            self::Cerrada => $destino === self::Reabierta,
            self::Reabierta => $destino === self::EnProgreso,
        };
    }
}
