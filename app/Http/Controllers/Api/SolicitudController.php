<?php

namespace App\Http\Controllers\Api;

use App\Actions\Solicitudes\AgregarComentarioSolicitud;
use App\Actions\Solicitudes\AsignarAgenteManualmente;
use App\Actions\Solicitudes\CambiarEstadoSolicitud;
use App\Actions\Solicitudes\CrearSolicitud;
use App\Actions\Solicitudes\ListarSolicitudes;
use App\Actions\Solicitudes\ObtenerHistorialSolicitud;
use App\Enums\EstadoSolicitud;
use App\Http\Controllers\Controller;
use App\Http\Requests\Solicitud\AgregarComentarioRequest;
use App\Http\Requests\Solicitud\CambiarEstadoRequest;
use App\Http\Requests\Solicitud\StoreSolicitudRequest;
use App\Http\Resources\SolicitudDetalleResource;
use App\Http\Resources\SolicitudResource;
use App\Models\Solicitud;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use OpenApi\Attributes as OA;

class SolicitudController extends Controller
{
    #[OA\Get(
        path: '/solicitudes',
        tags: ['Solicitudes'],
        summary: 'Lista/filtra solicitudes (paginado). El cliente solo ve las suyas.',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'estado', in: 'query', required: false, schema: new OA\Schema(
                type: 'string', enum: ['abierta', 'en_progreso', 'resuelta', 'cerrada', 'reabierta'],
            )),
            new OA\Parameter(name: 'prioridad', in: 'query', required: false, schema: new OA\Schema(
                type: 'string', enum: ['baja', 'media', 'alta'],
            )),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Listado paginado de solicitudes'),
            new OA\Response(response: 401, description: 'No autenticado'),
        ],
    )]
    public function index(Request $request, ListarSolicitudes $listar): AnonymousResourceCollection
    {
        $solicitudes = $listar(
            $request->user(),
            $request->only('estado', 'prioridad'),
            (int) $request->integer('per_page', 15),
        );

        return SolicitudResource::collection($solicitudes);
    }

    #[OA\Get(
        path: '/solicitudes/{solicitud}',
        tags: ['Solicitudes'],
        summary: 'Detalle de una solicitud, con cambios y comentarios (comentarios solo para agentes)',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'solicitud', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Detalle de la solicitud'),
            new OA\Response(response: 403, description: 'No autorizado a ver esta solicitud'),
            new OA\Response(response: 404, description: 'No encontrada'),
        ],
    )]
    public function show(Request $request, Solicitud $solicitud, ObtenerHistorialSolicitud $obtenerHistorial): SolicitudDetalleResource
    {
        Gate::authorize('ver', $solicitud);

        $historial = $obtenerHistorial($solicitud);

        $solicitud->setAttribute('cambios', $historial['cambios']);
        $solicitud->setAttribute('comentarios', $historial['comentarios']);

        return new SolicitudDetalleResource($solicitud);
    }

    #[OA\Post(
        path: '/solicitudes',
        tags: ['Solicitudes'],
        summary: 'Crea una solicitud de soporte (solo cliente)',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'Idempotency-Key', in: 'header', required: false,
                description: 'Repetir la misma clave devuelve el recurso ya creado en vez de duplicarlo',
                schema: new OA\Schema(type: 'string'),
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['asunto', 'descripcion', 'prioridad'],
                properties: [
                    new OA\Property(property: 'asunto', type: 'string', example: 'No puedo iniciar sesión'),
                    new OA\Property(property: 'descripcion', type: 'string', example: 'Me aparece un error 500 al entrar.'),
                    new OA\Property(property: 'prioridad', type: 'string', enum: ['baja', 'media', 'alta']),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 201, description: 'Solicitud creada'),
            new OA\Response(response: 200, description: 'Replay idempotente de una solicitud ya creada'),
            new OA\Response(response: 403, description: 'Solo el rol cliente puede crear solicitudes'),
            new OA\Response(response: 422, description: 'Datos inválidos'),
        ],
    )]
    public function store(StoreSolicitudRequest $request, CrearSolicitud $crearSolicitud): JsonResponse
    {
        $solicitud = $crearSolicitud(
            $request->user(),
            $request->validated(),
            $request->header('Idempotency-Key'),
        );

        return response()->json($solicitud, $solicitud->wasRecentlyCreated ? 201 : 200);
    }

    #[OA\Patch(
        path: '/solicitudes/{solicitud}/asignar',
        tags: ['Solicitudes'],
        summary: 'Un agente se auto-asigna una solicitud sin agente',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'solicitud', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Solicitud asignada'),
            new OA\Response(response: 403, description: 'Solo el rol agente puede asignarse solicitudes'),
            new OA\Response(response: 409, description: 'La solicitud ya tiene un agente asignado'),
        ],
    )]
    public function asignar(Request $request, Solicitud $solicitud, AsignarAgenteManualmente $asignar): JsonResponse
    {
        $asignada = $asignar($solicitud, $request->user());

        if (! $asignada) {
            return response()->json([
                'message' => 'La solicitud ya tiene un agente asignado.',
            ], 409);
        }

        return response()->json($asignada);
    }

    #[OA\Patch(
        path: '/solicitudes/{solicitud}/estado',
        tags: ['Solicitudes'],
        summary: 'Cambia el estado de una solicitud (agente dueño/ajeno según flag, o cliente reabriendo la suya)',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'solicitud', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['estado'],
                properties: [
                    new OA\Property(
                        property: 'estado', type: 'string',
                        enum: ['abierta', 'en_progreso', 'resuelta', 'cerrada', 'reabierta'],
                    ),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Estado actualizado (o ya estaba en ese estado: idempotente)'),
            new OA\Response(response: 403, description: 'No autorizado para esta transición'),
            new OA\Response(response: 409, description: 'Transición inválida o conflicto de concurrencia'),
        ],
    )]
    public function cambiarEstado(
        CambiarEstadoRequest $request,
        Solicitud $solicitud,
        CambiarEstadoSolicitud $cambiarEstado,
    ): JsonResponse {
        $nuevoEstado = EstadoSolicitud::from($request->validated('estado'));

        Gate::authorize('actualizarEstado', [$solicitud, $nuevoEstado]);

        return response()->json($cambiarEstado($solicitud, $nuevoEstado));
    }

    #[OA\Post(
        path: '/solicitudes/{solicitud}/comentarios',
        tags: ['Solicitudes'],
        summary: 'Agrega un comentario interno (solo agente, nunca visible para el cliente)',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'solicitud', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(
                name: 'Idempotency-Key', in: 'header', required: false,
                description: 'Repetir la misma clave devuelve el comentario ya creado en vez de duplicarlo',
                schema: new OA\Schema(type: 'string'),
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['texto'],
                properties: [new OA\Property(property: 'texto', type: 'string', example: 'Contacté al cliente por teléfono.')],
            ),
        ),
        responses: [
            new OA\Response(response: 201, description: 'Comentario creado'),
            new OA\Response(response: 200, description: 'Replay idempotente de un comentario ya creado'),
            new OA\Response(response: 403, description: 'No autorizado a comentar esta solicitud'),
            new OA\Response(response: 422, description: 'Texto requerido'),
        ],
    )]
    public function comentar(
        AgregarComentarioRequest $request,
        Solicitud $solicitud,
        AgregarComentarioSolicitud $agregarComentario,
    ): JsonResponse {
        Gate::authorize('comentar', $solicitud);

        $comentario = $agregarComentario(
            $solicitud,
            $request->user(),
            $request->validated('texto'),
            $request->header('Idempotency-Key'),
        );

        return response()->json($comentario, $comentario->wasRecentlyCreated ? 201 : 200);
    }
}
