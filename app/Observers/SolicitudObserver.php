<?php

namespace App\Observers;

use App\Enums\ModoAsignacion;
use App\Enums\TipoEventoHistorial;
use App\Jobs\IntentarAsignacionAutomatica;
use App\Jobs\RegistrarEventoHistorial;
use App\Models\Solicitud;
use App\Services\ConfigSystemService;
use Illuminate\Support\Facades\Auth;

class SolicitudObserver
{
    public function __construct(
        private ConfigSystemService $config,
    ) {}

    public function created(Solicitud $solicitud): void
    {
        RegistrarEventoHistorial::dispatch(
            $solicitud->id,
            Auth::id(),
            TipoEventoHistorial::Creada,
        );

        if ($this->asignacionEsAutomatica()) {
            IntentarAsignacionAutomatica::dispatch($solicitud->id);
        }
    }

    private function asignacionEsAutomatica(): bool
    {
        return $this->config->get('asignacion.modo') === ModoAsignacion::Automatica->value;
    }
}
