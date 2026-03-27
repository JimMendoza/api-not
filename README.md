# api-not

Backend movil sobre Laravel para NOT, conectado a la BD unica `tramite` y con persistencia propia en el esquema `app_mobile`.

## Runtime actual

- Framework: Laravel 6
- PHP: 7.4+
- BD: PostgreSQL `tramite`
- Cache: `database` sobre `public.cache`
- Queue: `database`
- Push: FCM HTTP v1
- Auth movil: token propio en `app_mobile.usuario_tokens`
- Hoja de ruta: pendiente funcional, responde `501` controlado

## Estructura relevante

- `app/Http/Controllers/Api/App`: endpoints moviles
- `app/Http/Controllers/Api/Integracion`: endpoints de integracion
- `app/Http/Middleware`: auth de app e integracion
- `app/Repositories/Identity`: acceso a `seguridad` y `maestro`
- `app/Repositories/Tramites`: lectura real de `virtual.REMITO` y seguimiento
- `app/Repositories/Notifications`: inbox, configuracion y dispositivos push
- `app/Services/Auth`: emision y resolucion de tokens moviles
- `app/Services/Push`: proveedor FCM y orquestacion de inbox/push
- `app/Jobs`: jobs asincronos de push
- `database/migrations`: migraciones reales activas
- `docs/contracts`: contratos backend publicados

## Variables criticas de entorno

Minimas:

```env
APP_NAME="api-not"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=tramite
DB_USERNAME=postgres
DB_PASSWORD=

CACHE_DRIVER=database
CACHE_CONNECTION=pgsql
CACHE_TABLE=cache

QUEUE_CONNECTION=database
MOBILE_PUSH_QUEUE=push
MOBILE_LOG_CHANNEL=stack

FCM_ENABLED=true
FCM_PROJECT_ID=not-gore-callao
FCM_SERVICE_ACCOUNT_JSON_PATH=storage/app/firebase/service-account.json

INTEGRACION_API_TOKEN=<token-seguro>
```

## Instalacion local

1. `composer install`
2. Configurar `.env`
3. `php artisan key:generate` si el proyecto aun no tiene `APP_KEY`
4. `php artisan migrate`
5. `php artisan optimize:clear`
6. `php artisan config:cache`

## Ejecucion local

API HTTP:

```powershell
php artisan serve --host=0.0.0.0 --port=8000
```

Worker para push:

```powershell
php artisan queue:work --queue=push --tries=3
```

## Contratos publicados

- `docs/contracts/app-api-v1.md`
- `docs/contracts/app-me-v1.md`
- `docs/contracts/app-notificaciones-configuracion-v2.md`
- `docs/contracts/integracion-notificaciones-evento-v1.md`
- `docs/contracts/app-mvp-matrix-v1.md`

## Validacion tecnica

```powershell
php artisan optimize:clear
php artisan config:cache
php artisan route:list --path=api
vendor/bin/phpunit tests/Feature
```

## Smoke minimo

1. `POST /api/app/entidades`
2. `POST /api/app/login`
3. `GET /api/app/me`
4. `GET /api/app/tramites`
5. `GET /api/app/notificaciones`
6. `GET /api/app/notificaciones/resumen`
7. `GET /api/app/notificaciones/configuracion`
8. `PUT /api/app/dispositivos/push-token`
9. `POST /api/integracion/notificaciones/evento`
10. `GET /api/app/tramites/{id}/hoja-ruta` esperando `501`

## Reglas de negocio criticas

- `/api/app/me` es la fuente unica de verdad del usuario autenticado.
- `POST /api/app/login` solo autentica y emite token.
- `POST /api/app/entidades` es publico y sin auth.
- Inbox historico: una notificacion ya creada sigue visible aunque el tramite deje de seguirse luego.
- Emision futura: si el tramite no tiene seguimiento activo, no se crea inbox ni se despacha push y la integracion responde `reason = not_followed`.
- Push usa `inbox-first`: primero se crea la notificacion elegible y luego se despacha el job de push.
- Quiet hours se evalua siempre en `America/Lima`.

## Troubleshooting rapido

- `401 No autenticado.`: revisar bearer token y `app_mobile.usuario_tokens`.
- `503` en integracion: falta `INTEGRACION_API_TOKEN`.
- Push no llega: revisar `FCM_*`, dispositivo activo en `app_mobile.usuario_dispositivos` y worker `queue:work` corriendo.
- Error de cache/throttle: confirmar `CACHE_DRIVER=database` y existencia de `public.cache`.
- `queue_dispatch_failed`: revisar `QUEUE_CONNECTION`, tablas `jobs/failed_jobs` y worker.
- `501` en hoja-ruta: comportamiento esperado mientras no exista fuente real de movimientos.
