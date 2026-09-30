<?php

namespace App\Actions\Solicitudes;

use App\Enums\EstadoSolicitud;
use App\Models\Solicitud;
use App\Models\User;
use Illuminate\Database\QueryException;

class CrearSolicitud
{
    /**
     * @param  array{asunto: string, descripcion: string, prioridad: string}  $datos
     */
    public function __invoke(User $cliente, array $datos, ?string $idempotencyKey = null): Solicitud
    {
        if ($idempotencyKey && $existente = $this->buscarPorIdempotencyKey($cliente, $idempotencyKey)) {
            return $existente;
        }

        try {
            return Solicitud::create([
                'cliente_id' => $cliente->id,
                'agente_id' => null,
                'asunto' => $datos['asunto'],
                'descripcion' => $datos['descripcion'],
                'prioridad' => $datos['prioridad'],
                'estado' => EstadoSolicitud::Abierta,
                'idempotency_key' => $idempotencyKey,
            ]);
        } catch (QueryException $exception) {
            if ($idempotencyKey && $this->esViolacionDeUnicidad($exception)) {
                return $this->buscarPorIdempotencyKey($cliente, $idempotencyKey)
                    ?? throw $exception;
            }

            throw $exception;
        }
    }

    private function buscarPorIdempotencyKey(User $cliente, string $idempotencyKey): ?Solicitud
    {
        return Solicitud::where('cliente_id', $cliente->id)
            ->where('idempotency_key', $idempotencyKey)
            ->first();
    }

    private function esViolacionDeUnicidad(QueryException $exception): bool
    {
        return $exception->getCode() === '23000';
    }
}
