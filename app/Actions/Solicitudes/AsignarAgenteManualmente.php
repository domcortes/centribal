<?php

namespace App\Actions\Solicitudes;

use App\Enums\TipoEventoHistorial;
use App\Jobs\RegistrarEventoHistorial;
use App\Models\Solicitud;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AsignarAgenteManualmente
{
    public function __invoke(Solicitud $solicitud, User $agente): ?Solicitud
    {
        $actualizadas = Solicitud::where('id', $solicitud->id)
            ->whereNull('agente_id')
            ->update(['agente_id' => $agente->id]);

        if ($actualizadas === 0) {
            return null;
        }

        RegistrarEventoHistorial::dispatch(
            $solicitud->id,
            Auth::id(),
            TipoEventoHistorial::Asignada,
            ['agente_id' => $agente->id],
        );

        return $solicitud->fresh();
    }
}
