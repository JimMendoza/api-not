# Matriz Backend MVP - Contratos App Móvil v1

Fecha de corte: 2026-03-27

## Alcance oficial
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
- `PUT /api/app/dispositivos/push-token`
- `DELETE /api/app/dispositivos/push-token`
- `POST /api/integracion/notificaciones/evento`

## Estado por endpoint
- `POST /api/app/entidades`: **congelado**
- `POST /api/app/login`: **congelado**
- `GET /api/app/me`: **congelado**
- `POST /api/app/logout`: **congelado**
- `GET /api/app/modulos`: **congelado**
- `GET /api/app/tramites`: **congelado**
- `GET /api/app/tramites/{id}`: **congelado**
- `POST /api/app/tramites/{id}/seguir`: **congelado**
- `DELETE /api/app/tramites/{id}/seguir`: **congelado**
- `GET /api/app/notificaciones`: **congelado**
- `GET /api/app/notificaciones/resumen`: **congelado**
- `PATCH /api/app/notificaciones/{id}/leida`: **congelado**
- `GET /api/app/notificaciones/configuracion`: **congelado**
- `PUT /api/app/notificaciones/configuracion`: **congelado**
- `PUT /api/app/dispositivos/push-token`: **congelado**
- `DELETE /api/app/dispositivos/push-token`: **congelado**
- `POST /api/integracion/notificaciones/evento`: **congelado**
- `GET /api/app/tramites/{id}/hoja-ruta`: **pendiente controlado** (`501`)

## Reglas oficiales de no ruptura
- `/api/app/me` sigue siendo la fuente unica de verdad del usuario autenticado.
- `login` queda limitado a autenticacion y emision de token.
- `POST /api/app/entidades` es el metodo final y no requiere auth.
- `empresa.id` se mantiene como alias transicional de `codigo`.
- Configuracion de notificaciones mantiene snake_case.
- Inbox historico: notificaciones ya creadas siguen visibles.
- Emision futura: solo con seguimiento activo; en caso contrario `reason = not_followed`.

## Documento principal

Contrato consolidado: `docs/contracts/app-api-v1.md`
