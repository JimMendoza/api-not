# App Push Notifications v1 (Deprecado)

Fecha de deprecacion: 2026-03-27

Documento historico. Los contratos vigentes son:

- `docs/contracts/app-api-v1.md`
- `docs/contracts/integracion-notificaciones-evento-v1.md`
- `docs/contracts/app-notificaciones-configuracion-v2.md`

Notas vigentes:

- Quiet hours se evalua solo en `America/Lima`.
- Inbox historico se mantiene visible aunque el seguimiento se desactive luego.
- Emision futura de nuevas notificaciones solo ocurre si existe seguimiento activo.
- Para eventos elegibles, el flujo es inbox-first y luego dispatch asincrono del push por cola.
