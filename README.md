# Sistema de Gestión de Solicitudes de Soporte — Centribal

API REST en Laravel 12 para que clientes creen solicitudes de soporte y agentes internos las revisen, asignen, resuelvan y comenten.

## Instalación

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan jwt:secret
touch database/database.sqlite
php artisan migrate --seed
php artisan serve
```

La API queda en `http://127.0.0.1:8000/api`. La asignación automática y el historial corren en cola; sin worker quedan pendientes en la tabla `jobs`:

```bash
php artisan queue:work
```

Tests: `php artisan test` (90 tests). Documentación interactiva: `http://127.0.0.1:8000/api/documentation`.

## Usuarios de prueba

Password `password` para todos.

| Email | Rol |
|---|---|
| `admin@centribal.com` | admin |
| `agente1@centribal.com` / `agente2@centribal.com` | agente |
| `cliente1@centribal.com` / `cliente2@centribal.com` | cliente |

## Endpoints

Auth JWT (`Authorization: Bearer <token>`, TTL 1h).

| Método | Ruta | Rol |
|---|---|---|
| POST | `/api/login` | público |
| POST | `/api/logout`, `/api/refresh` | autenticado |
| GET | `/api/me` | autenticado |
| POST | `/api/solicitudes` | cliente |
| GET | `/api/solicitudes`, `/api/solicitudes/{id}` | autenticado (scoped por rol) |
| PATCH | `/api/solicitudes/{id}/asignar` | agente |
| PATCH | `/api/solicitudes/{id}/estado` | agente/cliente (según regla) |
| POST | `/api/solicitudes/{id}/comentarios` | agente |

## Arquitectura

- **Actions** (`app/Actions/Solicitudes`) por caso de uso; controladores solo traducen HTTP.
- **Policies** (`SolicitudPolicy`) para autorización que combina rol + dueño + flags — no lógica dispersa en controladores.
- **Roles**: `spatie/laravel-permission`, guard `api`.
- **Colas**: historial y asignación automática son async (no bloquean la respuesta al crear/actualizar un ticket).
- **Concurrencia**: UPDATE atómico condicionado (`WHERE columna = valor_esperado`) para asignación y cambio de estado; sin locks explícitos.
- **Idempotencia**: header `Idempotency-Key` en creación de solicitud y comentarios, respaldado por índice único en BD. El cambio de estado es idempotente por diseño (repetir la misma transición no falla).
- **Config runtime** (`config_system`, cacheada): modo de asignación (automática/manual), umbral de carga por agente, y si un agente puede tocar un ticket ajeno.
- **Máquina de estados**: `abierta → en_progreso → resuelta → cerrada`, con `reabierta` como único camino de vuelta (cliente solo puede reabrir su propio ticket; cualquier otra transición fuera del grafo es `409`).
