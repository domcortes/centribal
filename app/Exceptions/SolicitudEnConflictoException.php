<?php

namespace App\Exceptions;

use App\Enums\EstadoSolicitud;
use DomainException;

class SolicitudEnConflictoException extends DomainException
{
    public function __construct(EstadoSolicitud $estadoActual)
    {
        parent::__construct("La solicitud ya no está en el estado esperado. Estado actual: '{$estadoActual->value}'.");
    }
}
