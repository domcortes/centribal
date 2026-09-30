<?php

namespace App\Actions\Solicitudes;

use App\Enums\EstadoSolicitud;
use App\Enums\TipoEventoHistorial;
use App\Jobs\RegistrarEventoHistorial;
use App\Models\Solicitud;
use App\Models\User;
use App\Services\ConfigSystemService;
use Illuminate\Support\Facades\Auth;

class AsignarAgenteAutomaticamente
{
    public function __construct(
        private ConfigSystemService $config,
    ) {}

    public function __invoke(Solicitud $solicitud): ?Solicitud
    {
        if ($solicitud->agente_id !== null) {
            return null;
        }

        $agente = $this->elegirAgenteDisponible();

        if (! $agente) {
            return null;
        }

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

    private function elegirAgenteDisponible(): ?User
    {
        $maxConcurrentes = (int) $this->config->get('asignacion.max_tickets_concurrentes', '5');

        return User::query()
            ->role('agente')
            ->withCount([
                'solicitudesAsignadas as tickets_activos' => fn ($query) => $query->whereIn('estado', [
                    EstadoSolicitud::Abierta,
                    EstadoSolicitud::EnProgreso,
                ]),
            ])
            ->withCount('solicitudesAsignadas as tickets_totales')
            ->get()
            ->filter(fn (User $agente) => $agente->tickets_activos < $maxConcurrentes)
            ->sortBy('tickets_totales')
            ->first();
    }
}
