<?php

namespace App\Jobs;

use App\Actions\Solicitudes\AsignarAgenteAutomaticamente;
use App\Enums\ModoAsignacion;
use App\Models\Solicitud;
use App\Services\ConfigSystemService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ReintentarAsignacionesPendientes implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function handle(ConfigSystemService $config, AsignarAgenteAutomaticamente $asignar): void
    {
        if ($config->get('asignacion.modo') !== ModoAsignacion::Automatica->value) {
            return;
        }

        $pendiente = Solicitud::whereNull('agente_id')->oldest()->first();

        if (! $pendiente) {
            return;
        }

        $asignar($pendiente);
    }

    public function failed(Throwable $exception): void
    {
        Log::error('No fue posible reintentar la asignación de solicitudes pendientes.', [
            'exception' => $exception,
        ]);
    }
}
