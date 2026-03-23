# Integracion Notificaciones Evento v1

Fecha de corte: 2026-03-20

## Objetivo

Canal unico para eventos del sistema principal que crea inbox y luego intenta push real.

## Endpoint

- Metodo: `POST`
- URL: `/api/integracion/notificaciones/evento`
- Auth: token de integracion por header:
  - `X-Integracion-Token: <token>`
  - alternativa: `Authorization: Bearer <token>`
- Configuracion backend requerida:
  - `INTEGRACION_API_TOKEN`

## Request

```json
{
  "tramiteId": 1,
  "evento": "tramite_derivado",
  "payload": {
    "destino": "Gerencia General"
  }
}
```

Aliases aceptados:

- `tramite_id`

Eventos soportados:

- `tramite_registrado`
- `tramite_derivado`
- `cambio_estado`
- `movimiento_hoja_ruta`

## Reglas funcionales

- Si `tramiteId` no existe o no esta visible/activo para su usuario destino: `404`.
- Si `evento` no esta soportado: `422`.
- Primero se crea inbox (`notificaciones`), luego se intenta push.
- Si push falla, no se pierde inbox.
- Nunca se persiste `tipo = prueba_push`.

Mapeo actual de `evento -> tipo` en BD:

- `tramite_registrado -> tramite_registrado`
- `tramite_derivado -> tramite_derivado`
- `cambio_estado -> estado`
- `movimiento_hoja_ruta -> movimiento_hoja_ruta`

## Response 200

```json
{
  "mensaje": "Evento de notificación procesado correctamente.",
  "notificacion": {
    "id": 101,
    "tramiteId": 1,
    "codigoTramite": "TRM-001",
    "titulo": "Trámite derivado",
    "mensaje": "El trámite TRM-001 fue derivado a Gerencia General.",
    "tipo": "tramite_derivado",
    "leida": false,
    "fechaHora": "2026-03-20 16:00"
  },
  "push": {
    "provider": "fcm",
    "configured": true,
    "attemptedDevices": 1,
    "sentDevices": 1,
    "invalidatedDevices": 0,
    "reason": null
  }
}
```

## Payload push FCM emitido

`PushNotificationService` envia:

```json
{
  "notification": {
    "title": "Tramite derivado",
    "body": "El tramite TRM-001 fue derivado a Gerencia General."
  },
  "data": {
    "notificationId": "101",
    "tramiteId": "1",
    "codigoTramite": "TRM-001",
    "tipo": "tramite_derivado",
    "targetScreen": "notificaciones",
    "noLeidas": "3"
  },
  "android": {
    "priority": "high",
    "notification": {
      "channel_id": "notificaciones_generales",
      "notification_count": 3
    }
  }
}
```

Errores:

- `401` token de integracion invalido
- `422` request invalido (`evento` no soportado, etc.)
- `404` tramite no encontrado
- `503` integracion no configurada (`INTEGRACION_API_TOKEN` ausente)

## Endpoint anterior

`POST /api/app/notificaciones/prueba-push` queda deprecado y responde `410`:

```json
{
  "mensaje": "Endpoint deprecado. Use /api/integracion/notificaciones/evento."
}
```
