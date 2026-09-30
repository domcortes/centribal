<?php

namespace App\Models;

use App\Enums\TipoEventoHistorial;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistorialSolicitud extends Model
{
    const UPDATED_AT = null;

    protected $table = 'historial_solicitudes';

    protected $fillable = [
        'solicitud_id',
        'user_id',
        'tipo',
        'detalle',
        'idempotency_key',
    ];

    protected function casts(): array
    {
        return [
            'tipo' => TipoEventoHistorial::class,
            'detalle' => 'array',
        ];
    }

    public function solicitud(): BelongsTo
    {
        return $this->belongsTo(Solicitud::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
