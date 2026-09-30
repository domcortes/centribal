<?php

namespace App\Actions\Solicitudes;

use App\Enums\TipoEventoHistorial;
use App\Models\Solicitud;
use Illuminate\Support\Collection;

class ObtenerHistorialSolicitud
{
    /**
     * @return array{comentarios: Collection, cambios: Collection}
     */
    public function __invoke(Solicitud $solicitud): array
    {
        $historial = $solicitud->historial()->oldest()->get();

        $particionado = $historial->partition(
            fn ($entrada) => $entrada->tipo === TipoEventoHistorial::Comentario,
        );

        return [
            'comentarios' => $particionado[0]->values(),
            'cambios' => $particionado[1]->values(),
        ];
    }
}
