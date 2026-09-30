<?php

namespace App\Policies;

use App\Enums\EstadoSolicitud;
use App\Models\Solicitud;
use App\Models\User;
use App\Services\ConfigSystemService;

class SolicitudPolicy
{
    public function __construct(
        private ConfigSystemService $config,
    ) {}

    public function actualizarEstado(User $user, Solicitud $solicitud, EstadoSolicitud $nuevoEstado): bool
    {
        if ($user->isAgente()) {
            return $this->agentePuedeIntervenir($user, $solicitud);
        }

        if ($user->isCliente()) {
            return $this->clientePuedeReabrir($user, $solicitud, $nuevoEstado);
        }

        return false;
    }

    public function comentar(User $user, Solicitud $solicitud): bool
    {
        return $user->isAgente() && $this->agentePuedeIntervenir($user, $solicitud);
    }

    public function ver(User $user, Solicitud $solicitud): bool
    {
        if ($user->isAgente()) {
            return true;
        }

        return $user->isCliente() && $solicitud->cliente_id === $user->id;
    }

    private function agentePuedeIntervenir(User $user, Solicitud $solicitud): bool
    {
        if ($solicitud->agente_id === null) {
            return false;
        }

        if ($solicitud->agente_id === $user->id) {
            return true;
        }

        return $this->config->get('estado.permitir_modificar_ticket_ajeno') === 'true';
    }

    private function clientePuedeReabrir(User $user, Solicitud $solicitud, EstadoSolicitud $nuevoEstado): bool
    {
        if ($solicitud->cliente_id !== $user->id) {
            return false;
        }

        return $nuevoEstado === EstadoSolicitud::Reabierta
            && in_array($solicitud->estado, [EstadoSolicitud::Resuelta, EstadoSolicitud::Cerrada], true);
    }
}
