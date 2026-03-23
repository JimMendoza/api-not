# App Notificaciones Configuracion v2

Fecha de corte: 2026-03-23

## Estado

Contrato vigente para:

- `GET /api/app/notificaciones/configuracion`
- `PUT /api/app/notificaciones/configuracion`

`v1` queda deprecado por simplificacion funcional.

## Alcance Peru

- Backend y app operan con zona horaria unica: `America/Lima`.
- `zona_horaria` es server-side source of truth.
- Si el cliente envia otra zona, backend la normaliza a `America/Lima`.

## Modelo final

Campos canonicos (snake_case):

- `silenciar_fuera_de_horario` (bool)
- `hora_silencio_inicio` (string `HH:mm`)
- `hora_silencio_fin` (string `HH:mm`)
- `zona_horaria` (string fija `America/Lima`)
- `mostrar_contador_no_leidas` (bool)

Campos eliminados (legacy):

- `solo_tramites_seguidos`
- `notificar_cambios_estado`
- `notificar_movimientos_hoja_ruta`
- `solo_eventos_importantes`
- `frecuencia_notificacion`

## GET configuracion

- Metodo: `GET`
- URL: `/api/app/notificaciones/configuracion`
- Auth: `Authorization: Bearer <accessToken>`

Response `200`:

```json
{
  "silenciar_fuera_de_horario": false,
  "hora_silencio_inicio": "22:00",
  "hora_silencio_fin": "07:00",
  "zona_horaria": "America/Lima",
  "mostrar_contador_no_leidas": true
}
```

## PUT configuracion

- Metodo: `PUT`
- URL: `/api/app/notificaciones/configuracion`
- Auth: `Authorization: Bearer <accessToken>`

Request (campo `zona_horaria` aceptado por compatibilidad):

```json
{
  "silenciar_fuera_de_horario": true,
  "hora_silencio_inicio": "21:30",
  "hora_silencio_fin": "06:15",
  "zona_horaria": "Europe/Madrid",
  "mostrar_contador_no_leidas": false
}
```

Persistencia/respuesta canonical (`200`):

```json
{
  "silenciar_fuera_de_horario": true,
  "hora_silencio_inicio": "21:30",
  "hora_silencio_fin": "06:15",
  "zona_horaria": "America/Lima",
  "mostrar_contador_no_leidas": false
}
```

Validaciones:

- `silenciar_fuera_de_horario`: `required|boolean`
- `hora_silencio_inicio`: `required|date_format:H:i|different:hora_silencio_fin`
- `hora_silencio_fin`: `required|date_format:H:i|different:hora_silencio_inicio`
- `zona_horaria`: forzada por backend a `America/Lima`
- `mostrar_contador_no_leidas`: `required|boolean`

Error `422`:

```json
{
  "mensaje": "Datos invalidos.",
  "errores": {
    "hora_silencio_inicio": ["..."],
    "mostrar_contador_no_leidas": ["..."]
  }
}
```

## Regla de envio push asociada

- El inbox (`notificaciones`) siempre se crea.
- Si `silenciar_fuera_de_horario=true` y la hora actual en `America/Lima` cae dentro de la ventana `[hora_silencio_inicio, hora_silencio_fin)`, el push no se envia.
- Si la ventana cruza medianoche (ej. `22:00` -> `07:00`), la regla se evalua como tramo nocturno continuo.

Reason de push en este caso:

- `silenciar_fuera_de_horario`
