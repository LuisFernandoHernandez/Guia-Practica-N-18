# Registro de cambios TaskBoard API

## 2026-11-06

### Corregido
- MT-013: `GET /api/transacciones` responde 422 si `?estado` no es Iniciada, Completada o Fallida (antes: 200 con lista vacía).
  - ▲ Clientes que enviaban estados mal escritos verán 422[cite: 39].

### Agregado
- MT-014: `GET /api/transacciones/filtrar` ahora soporta los parámetros opcionales `monto_min` y `monto_max` validados como números mayores o iguales a 0, asegurando que el máximo sea mayor o igual al mínimo cuando ambos se envían. Ninguna funcionalidad existente fue alterada.
