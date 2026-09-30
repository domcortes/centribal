<?php

namespace App\Http\Requests\Solicitud;

use App\Enums\EstadoSolicitud;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class CambiarEstadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'estado' => ['required', new Enum(EstadoSolicitud::class)],
        ];
    }
}
