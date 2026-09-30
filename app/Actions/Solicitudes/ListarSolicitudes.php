<?php

namespace App\Actions\Solicitudes;

use App\Models\Solicitud;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListarSolicitudes
{
    /**
     * @param  array{estado?: string, prioridad?: string}  $filtros
     */
    public function __invoke(User $user, array $filtros = [], int $porPagina = 15): LengthAwarePaginator
    {
        $query = Solicitud::query()->latest();

        if ($user->isCliente()) {
            $query->where('cliente_id', $user->id);
        }

        if (! empty($filtros['estado'])) {
            $query->where('estado', $filtros['estado']);
        }

        if (! empty($filtros['prioridad'])) {
            $query->where('prioridad', $filtros['prioridad']);
        }

        return $query->paginate($porPagina);
    }
}
