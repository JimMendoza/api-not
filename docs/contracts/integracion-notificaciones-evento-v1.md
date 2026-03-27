# Integracion Notificaciones Evento v1

Fecha de corte: 2026-03-27

## Objetivo

Canal unico para eventos del sistema principal que crea inbox elegible y despacha push real por cola.

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
- Si el tramite no tiene seguimiento activo para el usuario destino, no se crea inbox ni se despacha push y la respuesta devuelve `reason = not_followed`.
- Si el tramite si tiene seguimiento activo, primero se crea inbox (`notificaciones`) y luego se despacha el job push.
- Si el push falla en worker, no se pierde inbox.
- Nunca se persiste `tipo = prueba_push`.

Mapeo actual de `evento -> tipo` en BD:

- `tramite_registrado -> tramite_registrado`
- `tramite_derivado -> tramite_derivado`
- `cambio_estado -> estado`
- `movimiento_hoja_ruta -> movimiento_hoja_ruta`

## Response 200 con seguimiento activo

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
    "fechaHora": "2026-03-27 16:00"
  },
  "push": {
    "provider": "fcm",
    "configured": true,
    "queued": true,
    "attemptedDevices": 1,
    "sentDevices": 0,
    "invalidatedDevices": 0,
    "reason": "queued"
  }
}
```

## Response 200 sin seguimiento activo

```json
{
  "mensaje": "Evento de notificación procesado correctamente.",
  "notificacion": null,
  "push": {
    "provider": "fcm",
    "configured": true,
    "queued": false,
    "attemptedDevices": 0,
    "sentDevices": 0,
    "invalidatedDevices": 0,
    "reason": "not_followed"
  }
}
```

## Payload push FCM emitido por worker

```json
{
  "notification": {
    "title": "Trámite derivado",
    "body": "El trámite TRM-001 fue derivado a Gerencia General."
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
- `422` request invalido
- `404` tramite no encontrado
- `503` integracion no configurada
