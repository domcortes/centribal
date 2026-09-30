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

class IntentarAsignacionAutomatica implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public int $solicitudId,
    ) {}

    public function handle(ConfigSystemService $config, AsignarAgenteAutomaticamente $asignar): void
    {
        if ($config->get('asignacion.modo') !== ModoAsignacion::Automatica->value) {
            return;
        }

        $solicitud = Solicitud::find($this->solicitudId);

        if (! $solicitud) {
            return;
        }

        $asignar($solicitud);
    }

    public function failed(Throwable $exception): void
    {
        Log::error('No fue posible ejecutar la asignación automática de agente.', [
            'solicitud_id' => $this->solicitudId,
            'exception' => $exception,
        ]);
    }
}
