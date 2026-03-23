# Contrato API App Móvil - `/api/app/me` v1

Fecha de decisión: 2026-03-19

## Decisión
- `GET /api/app/me` es la fuente única de verdad del usuario autenticado.
- `POST /api/app/login` queda limitado a autenticación (emisión de token).

## Endpoint canónico
- URL: `/api/app/me`
- Método: `GET`
- Auth: `Authorization: Bearer <accessToken>`
- Content-Type recomendado: `application/json`

## Respuesta `200`
Sin envelope adicional (`response()->json` directo):

```json
{
  "username": "string",
  "fullName": "string",
  "empresa": {
    "codigo": "string",
    "id": "string",
    "nombre": "string",
    "imagen": "string|null"
  },
  "permisos": ["string"],
  "session": {
    "authenticated": true,
    "tokenType": "string"
  }
}
```

### Semántica de campos
- Identidad: `username`
- Perfil: `fullName`
- Entidad: `empresa.codigo` (canónico), `empresa.nombre`, `empresa.imagen`
- Alias transicional: `empresa.id` (mismo valor de `empresa.codigo`)
- Permisos: `permisos`
- Banderas de sesión: `session.authenticated`, `session.tokenType`

## Respuesta `401`
```json
{
  "mensaje": "No autenticado."
}
```

## Login (alcance)
`POST /api/app/login` retorna solo:

```json
{
  "accessToken": "string",
  "tokenType": "string"
}
```

No usar `login` como fuente de identidad, perfil, permisos ni entidad. Esa información debe leerse en `/api/app/me`.
