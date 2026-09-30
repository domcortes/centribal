<?php

namespace App\Enums;

enum TipoEventoHistorial: string
{
    case Creada = 'creada';
    case Asignada = 'asignada';
    case CambioEstado = 'cambio_estado';
    case Comentario = 'comentario';
}
