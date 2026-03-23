# Matriz Backend MVP - Contratos App Móvil v1

Fecha de corte: 2026-03-19

## Alcance (consumo Flutter reportado)
- `POST /api/app/entidades`
- `POST /api/app/login`
- `GET /api/app/me`
- `POST /api/app/logout`
- `GET /api/app/modulos`
- `GET /api/app/tramites`
- `GET /api/app/tramites/{id}`
- `GET /api/app/tramites/{id}/hoja-ruta`
- `POST /api/app/tramites/{id}/seguir`
- `DELETE /api/app/tramites/{id}/seguir`
- `GET /api/app/notificaciones`
- `GET /api/app/notificaciones/resumen`
- `PATCH /api/app/notificaciones/{id}/leida`
- `GET /api/app/notificaciones/configuracion`
- `PUT /api/app/notificaciones/configuracion`

## Envelope y errores oficiales
- Éxito: JSON directo (sin `data`), código HTTP 2xx.
- Error estándar de dominio/controlador: `{"mensaje": "..."}`.
- Error de validación (`ApiRequest`): `{"mensaje": "Datos inválidos.", "errores": {...}}` con `422`.
- Error de autenticación middleware token: `{"mensaje": "No autenticado."}` con `401`.

## Naming canónico
- Trámites: camelCase (`estadoActual`, `notificacionesNoLeidas`, `fechaHora`, `nroDoc`).
- Notificaciones: camelCase (`tramiteId`, `codigoTramite`, `fechaHora`).
- Configuración de notificaciones: snake_case (compatibilidad histórica del recurso).
- Entidad: `codigo` canónico, `id` alias transicional.

## Estado por endpoint (MVP)
- `POST /api/app/entidades`: **inestable**
- `POST /api/app/login`: **congelado**
- `GET /api/app/me`: **congelado**
- `POST /api/app/logout`: **casi congelado**
- `GET /api/app/modulos`: **congelado**
- `GET /api/app/tramites`: **casi congelado**
- `GET /api/app/tramites/{id}`: **casi congelado**
- `GET /api/app/tramites/{id}/hoja-ruta`: **casi congelado**
- `POST /api/app/tramites/{id}/seguir`: **casi congelado**
- `DELETE /api/app/tramites/{id}/seguir`: **casi congelado**
- `GET /api/app/notificaciones`: **casi congelado**
- `GET /api/app/notificaciones/resumen`: **casi congelado**
- `PATCH /api/app/notificaciones/{id}/leida`: **casi congelado**
- `GET /api/app/notificaciones/configuracion`: **congelado**
- `PUT /api/app/notificaciones/configuracion`: **congelado**

## Compatibilidades transicionales
- `empresa.id` en `/api/app/me` y `/api/app/entidades` se mantiene como alias de `empresa.codigo`/`codigo`.

## No romper sin aprobación
- Rutas y métodos HTTP publicados bajo `/api/app/*`.
- `401` con `{"mensaje":"No autenticado."}` en endpoints protegidos.
- Contrato de `/api/app/me` como fuente única de verdad de usuario autenticado.
- `login` limitado a autenticación (`accessToken`, `tokenType`).
- `logout` debe revocar token vigente y responder éxito lógico.
- `hoja-ruta` debe mantener shape, orden ascendente por `fecha_hora` y `404` en trámite no visible.
- Contrato de configuración de notificaciones en snake_case.
