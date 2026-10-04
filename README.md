# Tareas API

![Tests](https://github.com/JoseBenitezAlba/tareas-api/actions/workflows/tests.yml/badge.svg)
![PHP](https://img.shields.io/badge/PHP-8.4-777BB4?logo=php&logoColor=white)
![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel&logoColor=white)

**Demo:** https://tareas-api-80m0.onrender.com (usuario `demo@example.com`, contraseña `password`). Está en el plan gratuito de Render: si lleva un rato sin usarse, la primera petición tarda cerca de un minuto. Los datos se reinician en cada arranque.

API REST de gestión de tareas hecha con **Laravel 13**, autenticación por token con **Laravel Sanctum**, tests automáticos y CI con **GitHub Actions**.

## Qué incluye

- Registro, login y logout con tokens (Sanctum).
- CRUD completo de tareas, privado por usuario: nadie puede ver ni tocar las tareas de otro (Policy, respuesta 403).
- Filtros por estado y prioridad, búsqueda por título y paginación.
- Validación con Form Requests y respuestas JSON con API Resources.
- Límite de peticiones en registro y login.
- 15 tests de feature (autenticación, validación, permisos, filtros, paginación).
- CI: los tests se ejecutan en cada push y pull request.

## Puesta en marcha

Requisitos: PHP 8.3 o superior, Composer y la extensión SQLite.

```bash
git clone https://github.com/JoseBenitezAlba/tareas-api.git
cd tareas-api
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan serve
```

El seeder crea el usuario `demo@example.com` con contraseña `password` y 8 tareas de ejemplo. Solo para desarrollo local.

## Tests

```bash
php artisan test
```

## Endpoints

Todas las rutas llevan el prefijo `/api`. Las marcadas con 🔒 necesitan la cabecera `Authorization: Bearer <token>`.

| Método | Ruta | Descripción |
|---|---|---|
| POST | `/register` | Crea usuario y devuelve token |
| POST | `/login` | Devuelve token |
| POST | `/logout` 🔒 | Revoca el token actual |
| GET | `/me` 🔒 | Usuario autenticado |
| GET | `/tasks` 🔒 | Lista tus tareas. Query: `status`, `priority`, `search`, `per_page` (máx. 100) |
| POST | `/tasks` 🔒 | Crea una tarea |
| GET | `/tasks/{id}` 🔒 | Ver una tarea |
| PUT/PATCH | `/tasks/{id}` 🔒 | Actualizar |
| DELETE | `/tasks/{id}` 🔒 | Borrar (204) |

Campos de una tarea: `title` (obligatorio), `description`, `status` (`pending`, `in_progress`, `done`), `priority` (`low`, `medium`, `high`), `due_date` (fecha).

### Ejemplo

```bash
# Login
curl -s -X POST http://127.0.0.1:8000/api/login \
  -H 'Accept: application/json' -H 'Content-Type: application/json' \
  -d '{"email":"demo@example.com","password":"password"}'

# Crear una tarea
curl -s -X POST http://127.0.0.1:8000/api/tasks \
  -H "Authorization: Bearer $TOKEN" \
  -H 'Accept: application/json' -H 'Content-Type: application/json' \
  -d '{"title":"Preparar entrevista","priority":"high","due_date":"2026-10-20"}'

# Filtrar
curl -s "http://127.0.0.1:8000/api/tasks?status=pending&priority=high" \
  -H "Authorization: Bearer $TOKEN" -H 'Accept: application/json'
```

Respuesta de ejemplo:

```json
{
  "data": {
    "id": 9,
    "title": "Preparar entrevista",
    "description": null,
    "status": "pending",
    "priority": "high",
    "due_date": "2026-10-20",
    "created_at": "2026-10-04T11:03:14+00:00",
    "updated_at": "2026-10-04T11:03:14+00:00"
  }
}
```

## Estructura

```
app/Http/Controllers/Api/   AuthController, TaskController
app/Http/Requests/          validación (Store/UpdateTaskRequest)
app/Http/Resources/         TaskResource
app/Policies/TaskPolicy.php autorización por propietario
tests/Feature/              AuthTest, TaskApiTest
.github/workflows/tests.yml CI
```

## Autor

José Manuel Benítez Alba, desarrollador web junior (PHP/Laravel), Cádiz. [GitHub](https://github.com/JoseBenitezAlba)
