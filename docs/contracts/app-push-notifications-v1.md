# App Push Notifications v1 (Deprecado)

Fecha de deprecacion: 2026-03-23

Este documento queda solo como referencia historica.

Los contratos vigentes son:

- `POST /api/integracion/notificaciones/evento` en `docs/contracts/integracion-notificaciones-evento-v1.md`
- Configuracion de notificaciones v2 en `docs/contracts/app-notificaciones-configuracion-v2.md`

Cambios principales respecto a v1:

- Se elimina el modelo legacy de configuracion (`solo_tramites_seguidos`, `notificar_cambios_estado`, `notificar_movimientos_hoja_ruta`, `solo_eventos_importantes`, `frecuencia_notificacion`).
- La regla de entrega push ahora usa quiet hours por usuario (`silenciar_fuera_de_horario`, `hora_silencio_inicio`, `hora_silencio_fin`, `zona_horaria`).
- El flujo inbox-first se mantiene: siempre se crea inbox y luego se evalua el envio push.
