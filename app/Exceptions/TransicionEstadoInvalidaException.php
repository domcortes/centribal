<?php

namespace App\Exceptions;

use App\Enums\EstadoSolicitud;
use DomainException;

class TransicionEstadoInvalidaException extends DomainException
{
    public function __construct(EstadoSolicitud $desde, EstadoSolicitud $hasta)
    {
        parent::__construct("No se puede cambiar de '{$desde->value}' a '{$hasta->value}'.");
    }
}
