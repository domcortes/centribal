<?php

namespace App\Models;

use App\Enums\EstadoSolicitud;
use App\Enums\Prioridad;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Solicitud extends Model
{
    use HasFactory;

    protected $table = 'solicitudes';

    protected $fillable = [
        'cliente_id',
        'agente_id',
        'asunto',
        'descripcion',
        'prioridad',
        'estado',
        'idempotency_key',
    ];

    protected function casts(): array
    {
        return [
            'prioridad' => Prioridad::class,
            'estado' => EstadoSolicitud::class,
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cliente_id');
    }

    public function agente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agente_id');
    }

    public function historial(): HasMany
    {
        return $this->hasMany(HistorialSolicitud::class);
    }
}
