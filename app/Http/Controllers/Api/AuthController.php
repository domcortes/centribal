<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;

class AuthController extends Controller
{
    #[OA\Post(
        path: '/login',
        tags: ['Auth'],
        summary: 'Login y emisión de JWT',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'cliente1@centribal.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'password'),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Token emitido', content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'access_token', type: 'string'),
                    new OA\Property(property: 'token_type', type: 'string', example: 'bearer'),
                    new OA\Property(property: 'expires_in', type: 'integer', example: 3600),
                ],
            )),
            new OA\Response(response: 422, description: 'Credenciales inválidas'),
            new OA\Response(response: 429, description: 'Demasiados intentos (rate limit: 5/min por IP)'),
        ],
    )]
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $token = Auth::guard('api')->attempt($request->credentials());
        } catch (JWTException $exception) {
            Log::error('No fue posible emitir el token JWT.', [
                'exception' => $exception,
            ]);

            return response()->json([
                'message' => 'No fue posible procesar la autenticación. Intenta nuevamente.',
            ], 500);
        }

        if (! $token) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales no son válidas.'],
            ]);
        }

        return $this->respondWithToken($token);
    }

    #[OA\Get(
        path: '/me',
        tags: ['Auth'],
        summary: 'Usuario autenticado',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Usuario actual'),
            new OA\Response(response: 401, description: 'No autenticado'),
        ],
    )]
    public function me(): JsonResponse
    {
        return response()->json(Auth::guard('api')->user());
    }

    #[OA\Post(
        path: '/logout',
        tags: ['Auth'],
        summary: 'Invalida el token actual',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Sesión cerrada'),
        ],
    )]
    public function logout(): JsonResponse
    {
        Auth::guard('api')->logout();

        return response()->json(['message' => 'Sesión cerrada correctamente.']);
    }

    #[OA\Post(
        path: '/refresh',
        tags: ['Auth'],
        summary: 'Emite un nuevo JWT a partir del actual',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Nuevo token emitido'),
        ],
    )]
    public function refresh(): JsonResponse
    {
        try {
            $token = Auth::guard('api')->refresh();
        } catch (JWTException $exception) {
            Log::error('No fue posible refrescar el token JWT.', [
                'exception' => $exception,
            ]);

            return response()->json([
                'message' => 'No fue posible refrescar la sesión. Vuelve a iniciar sesión.',
            ], 500);
        }

        return $this->respondWithToken($token);
    }

    private function respondWithToken(string $token): JsonResponse
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => Auth::guard('api')->factory()->getTTL() * 60,
        ]);
    }
}
