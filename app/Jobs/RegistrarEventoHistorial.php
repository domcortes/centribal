<?php

namespace App\Jobs;

use App\Enums\TipoEventoHistorial;
use App\Models\HistorialSolicitud;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class RegistrarEventoHistorial implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public int $solicitudId,
        public ?int $userId,
        public TipoEventoHistorial $tipo,
        public array|string|null $detalle = null,
    ) {}

    public function handle(): void
    {
        HistorialSolicitud::create([
            'solicitud_id' => $this->solicitudId,
            'user_id' => $this->userId,
            'tipo' => $this->tipo,
            'detalle' => $this->detalle,
        ]);
    }

    public function failed(Throwable $exception): void
    {
        Log::error('No fue posible registrar el evento de historial.', [
            'solicitud_id' => $this->solicitudId,
            'tipo' => $this->tipo->value,
            'exception' => $exception,
        ]);
    }
}
