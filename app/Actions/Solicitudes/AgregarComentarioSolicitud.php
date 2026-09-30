<?php

namespace App\Actions\Solicitudes;

use App\Enums\TipoEventoHistorial;
use App\Models\HistorialSolicitud;
use App\Models\Solicitud;
use App\Models\User;
use Illuminate\Database\QueryException;

class AgregarComentarioSolicitud
{
    public function __invoke(Solicitud $solicitud, User $autor, string $texto, ?string $idempotencyKey = null): HistorialSolicitud
    {
        if ($idempotencyKey && $existente = $this->buscarPorIdempotencyKey($solicitud, $autor, $idempotencyKey)) {
            return $existente;
        }

        try {
            return HistorialSolicitud::create([
                'solicitud_id' => $solicitud->id,
                'user_id' => $autor->id,
                'tipo' => TipoEventoHistorial::Comentario,
                'detalle' => ['texto' => $texto],
                'idempotency_key' => $idempotencyKey,
            ]);
        } catch (QueryException $exception) {
            if ($idempotencyKey && $this->esViolacionDeUnicidad($exception)) {
                return $this->buscarPorIdempotencyKey($solicitud, $autor, $idempotencyKey)
                    ?? throw $exception;
            }

            throw $exception;
        }
    }

    private function buscarPorIdempotencyKey(Solicitud $solicitud, User $autor, string $idempotencyKey): ?HistorialSolicitud
    {
        return HistorialSolicitud::where('solicitud_id', $solicitud->id)
            ->where('user_id', $autor->id)
            ->where('idempotency_key', $idempotencyKey)
            ->first();
    }

    private function esViolacionDeUnicidad(QueryException $exception): bool
    {
        return $exception->getCode() === '23000';
    }
}
