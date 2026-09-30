<?php

namespace App\Actions\Solicitudes;

use App\Enums\EstadoSolicitud;
use App\Enums\ModoAsignacion;
use App\Enums\TipoEventoHistorial;
use App\Exceptions\SolicitudEnConflictoException;
use App\Exceptions\TransicionEstadoInvalidaException;
use App\Jobs\RegistrarEventoHistorial;
use App\Jobs\ReintentarAsignacionesPendientes;
use App\Models\Solicitud;
use App\Services\ConfigSystemService;
use Illuminate\Support\Facades\Auth;

class CambiarEstadoSolicitud
{
    public function __construct(
        private ConfigSystemService $config,
    ) {}

    public function __invoke(Solicitud $solicitud, EstadoSolicitud $nuevoEstado): Solicitud
    {
        $estadoActual = $solicitud->estado;

        if ($estadoActual === $nuevoEstado) {
            return $solicitud;
        }

        if (! $estadoActual->puedeTransicionarA($nuevoEstado)) {
            throw new TransicionEstadoInvalidaException($estadoActual, $nuevoEstado);
        }

        $actualizadas = Solicitud::where('id', $solicitud->id)
            ->where('estado', $estadoActual)
            ->update(['estado' => $nuevoEstado]);

        if ($actualizadas === 0) {
            $solicitudActual = $solicitud->fresh();

            if ($solicitudActual->estado === $nuevoEstado) {
                return $solicitudActual;
            }

            throw new SolicitudEnConflictoException($solicitudActual->estado);
        }

        RegistrarEventoHistorial::dispatch(
            $solicitud->id,
            Auth::id(),
            TipoEventoHistorial::CambioEstado,
            ['estado_anterior' => $estadoActual->value, 'estado_nuevo' => $nuevoEstado->value],
        );

        if ($this->liberaCapacidad($nuevoEstado) && $this->asignacionEsAutomatica()) {
            ReintentarAsignacionesPendientes::dispatch();
        }

        return $solicitud->fresh();
    }

    private function liberaCapacidad(EstadoSolicitud $estado): bool
    {
        return in_array($estado, [EstadoSolicitud::Resuelta, EstadoSolicitud::Cerrada], true);
    }

    private function asignacionEsAutomatica(): bool
    {
        return $this->config->get('asignacion.modo') === ModoAsignacion::Automatica->value;
    }
}
