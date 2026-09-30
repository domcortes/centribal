<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SolicitudDetalleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'asunto' => $this->asunto,
            'descripcion' => $this->descripcion,
            'prioridad' => $this->prioridad,
            'estado' => $this->estado,
            'cliente_id' => $this->cliente_id,
            'agente_id' => $this->agente_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'cambios' => HistorialSolicitudResource::collection($this->cambios),
            'comentarios' => $this->when(
                $request->user()?->isAgente(),
                fn () => HistorialSolicitudResource::collection($this->comentarios),
            ),
        ];
    }
}
