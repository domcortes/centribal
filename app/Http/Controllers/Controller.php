<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Centribal — Sistema de Gestión de Solicitudes de Soporte',
    description: 'API REST para que clientes creen solicitudes de soporte y agentes internos las revisen, asignen, resuelvan y comenten.',
)]
#[OA\Server(url: '/api', description: 'Servidor API')]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT',
)]
#[OA\Tag(name: 'Auth', description: 'Autenticación vía JWT')]
#[OA\Tag(name: 'Solicitudes', description: 'Ciclo de vida de solicitudes de soporte')]
abstract class Controller
{
    //
}
