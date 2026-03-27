# API App e Integracion v1

Fecha de corte: 2026-03-27

## Convenciones generales

- Exito: JSON directo, sin envelope `data`
- Error de dominio: `{"mensaje": "..."}`
- Error de validacion: `{"mensaje": "Datos inválidos.", "errores": {...}}`
- Error de auth app: `401` con `{"mensaje": "No autenticado."}`
- Error de auth integracion: `401` con `{"mensaje": "No autorizado."}`

## Contratos App

### `POST /api/app/entidades`
- Auth: no
- Request: sin body obligatorio
- Response `200`:
```json
[
  {
    "codigo": "0002",
    "id": "0002",
    "nombre": "Gobierno Regional del Callao",
    "imagen": "logo.png"
  }
]
```
- Notas: `codigo` es canonico, `id` se mantiene como alias transicional.

### `POST /api/app/login`
- Auth: no
- Request:
```json
{
  "username": "20131257750",
  "password": "<temporal>",
  "codEmp": "0002"
}
```
- Response `200`:
```json
{
  "accessToken": "<token>",
  "tokenType": "Bearer"
}
```
- Error `401`: `{"mensaje": "Credenciales inválidas."}`
- Regla de sesion: el token movil expira a los `30` dias y cada request autenticado renueva la expiracion otros `30` dias desde el ultimo uso.

### `POST /api/app/logout`
- Auth: si
- Request opcional:
```json
{
  "deviceId": "android-device-01"
}
```
- Response `200`:
```json
{
  "mensaje": "Sesión cerrada correctamente."
}
```

### `GET /api/app/me`
- Auth: si
- Response `200`:
```json
{
  "username": "20131257750",
  "fullName": "Nombres ApellidoPaterno ApellidoMaterno",
  "empresa": {
    "codigo": "0002",
    "id": "0002",
    "nombre": "Gobierno Regional del Callao",
    "imagen": "logo.png"
  },
  "permisos": ["mesa_partes_virtual", "notificaciones"],
  "session": {
    "authenticated": true,
    "tokenType": "Bearer"
  }
}
```

### `GET /api/app/modulos`
- Auth: si
- Response `200`:
```json
[
  {
    "id": "mesa_partes_virtual",
    "nombre": "Mesa de Partes Virtual",
    "icono": "description"
  },
  {
    "id": "notificaciones",
    "nombre": "Notificaciones",
    "icono": "notifications"
  }
]
```

### `GET /api/app/tramites`
- Auth: si
- Response `200`:
```json
[
  {
    "id": 501,
    "codigo": "TRM-REAL-001",
    "titulo": "Solicitud de acceso",
    "fecha": "2026-03-24",
    "estadoActual": "Registrado",
    "siguiendo": true,
    "notificacionesNoLeidas": 2
  }
]
```

### `GET /api/app/tramites/{id}`
- Auth: si
- Response `200`:
```json
{
  "id": 501,
  "codigo": "TRM-REAL-001",
  "titulo": "Solicitud de acceso",
  "fecha": "2026-03-24",
  "estadoActual": "Registrado",
  "siguiendo": true,
  "notificacionesNoLeidas": 2,
  "descripcion": "Detalle del trámite"
}
```
- Error `404`: `{"mensaje": "Trámite no encontrado."}`

### `POST /api/app/tramites/{id}/seguir`
- Auth: si
- Response `200`:
```json
{
  "mensaje": "Trámite marcado para seguimiento."
}
```

### `DELETE /api/app/tramites/{id}/seguir`
- Auth: si
- Response `200`:
```json
{
  "mensaje": "Seguimiento eliminado."
}
```

### `GET /api/app/notificaciones`
- Auth: si
- Response `200`:
```json
[
  {
    "id": 9001,
    "tramiteId": 801,
    "codigoTramite": "TRM-NOTI-801",
    "titulo": "Pendiente 1",
    "mensaje": "Mensaje 1",
    "tipo": "estado",
    "leida": false,
    "fechaHora": "2026-03-24 12:00"
  }
]
```
- Regla: inbox historico. Una notificacion ya creada sigue visible aunque el seguimiento se desactive despues.

### `GET /api/app/notificaciones/resumen`
- Auth: si
- Response `200`:
```json
{
  "noLeidas": 3
}
```

### `PATCH /api/app/notificaciones/{id}/leida`
- Auth: si
- Response `200`:
```json
{
  "mensaje": "Notificación marcada como leída."
}
```
- Error `404`: `{"mensaje": "Notificación no encontrada."}`

### `GET /api/app/notificaciones/configuracion`
### `PUT /api/app/notificaciones/configuracion`
- Auth: si
- Contrato vigente: `docs/contracts/app-notificaciones-configuracion-v2.md`

### `PUT /api/app/dispositivos/push-token`
- Auth: si
- Request:
```json
{
  "deviceId": "android-device-01",
  "pushToken": "<fcm-token>",
  "platform": "android",
  "deviceName": "Pixel",
  "appVersion": "1.0.0"
}
```
- Response `200`:
```json
{
  "mensaje": "Token push registrado correctamente.",
  "dispositivo": {
    "deviceId": "android-device-01",
    "platform": "android",
    "deviceName": "Pixel",
    "appVersion": "1.0.0",
    "activo": true,
    "ultimoRegistroAt": "2026-03-27 12:00:00"
  }
}
```

### `DELETE /api/app/dispositivos/push-token`
- Auth: si
- Request:
```json
{
  "deviceId": "android-device-01"
}
```
- Response `200`:
```json
{
  "mensaje": "Token push invalidado correctamente."
}
```

## Contrato Integracion

### `POST /api/integracion/notificaciones/evento`
- Auth: token por `X-Integracion-Token` o bearer
- Request:
```json
{
  "tramiteId": 9901,
  "evento": "tramite_derivado",
  "payload": {
    "destino": "Gerencia General"
  }
}
```
- Eventos soportados:
  - `tramite_registrado`
  - `tramite_derivado`
  - `cambio_estado`
  - `movimiento_hoja_ruta`
- Response `200` con seguimiento activo y cola despachada:
```json
{
  "mensaje": "Evento de notificación procesado correctamente.",
  "notificacion": {
    "id": 101,
    "tramiteId": 9901,
    "codigoTramite": "TRM-INT-REAL-001",
    "titulo": "Trámite derivado",
    "mensaje": "El trámite TRM-INT-REAL-001 fue derivado a Gerencia General.",
    "tipo": "tramite_derivado",
    "leida": false,
    "fechaHora": "2026-03-27 12:00"
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
- Response `200` sin seguimiento activo:
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
- Reglas:
  - inbox historico se conserva
  - emision futura solo con seguimiento activo
  - si hay seguimiento activo, se crea inbox primero y luego se despacha job push
  - si el push falla en worker, el inbox ya creado no se pierde
