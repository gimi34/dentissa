---
spec: 048-citas-panel-detalle
status: approved            # draft | approved (solo el usuario aprueba)
exploration: none           # sin ciclo de candidatos (propuesta de la spec, aprobada con ella el 2026-11-14); motivo en "Resultado"
system_version: 1.16.0
created: 2026-11-14
updated: 2026-11-14
---

# Diseño · 048 Abrir las citas del panel de inicio

Hecho con `/design-spec 048` en modo sin exploración, sobre `docs/design/system.md` 1.16.0. Los
criterios están en la spec («Criterios de diseño», K1–K4). No hay `design/criterios.json` ni
candidatos.

## Resultado

**Sin diseño nuevo.** La 048 no cambia lo que muestra ninguna cita: convierte en enlace cada fila
(o tarjeta, en el móvil) de las tablas del panel y cada elemento de la línea de tiempo de la
dentista. Lo único que se ve distinto es el estado al pasar el puntero y el foco, que salen de
tokens que ya existen (`--p50`, `--focus-ring`). Por eso no hay ronda de candidatos: el usuario lo
valida en pantalla durante `/implement`.

## Pantallas

| Panel | Lista | Escritorio | Móvil (< 900 px) |
|---|---|---|---|
| Administración | «Citas de hoy» | fila de la tabla | tarjeta de `tabla-tarjetas` |
| Asistente | «Agenda de hoy» | fila de la tabla | tarjeta de `tabla-tarjetas` |
| Dentista | «Agenda de hoy» | elemento de `.timeline` (el ojo se queda como señal) | igual, con la disposición de la 034 |
| Paciente | «Historial» | fila de la tabla | tarjeta de `tabla-tarjetas` |

## Componentes

**Cita que abre su detalle** (nuevo en el sistema): el enlace es uno solo por cita y vive en el dato
principal (la hora, la fecha o, en la dentista, el ojo); un pseudoelemento suyo cubre toda la fila,
tarjeta o elemento, así que pulsar en cualquier parte lo abre.
- **Reposo:** igual que hoy; la hora o fecha conserva su color.
- **Puntero encima:** fondo `--p50` en toda la fila o tarjeta y cursor de enlace.
- **Foco:** anillo `--focus-ring-width` en `--focus-ring` por dentro del borde de la fila o tarjeta
  (con su `--radius` en el móvil), para que no lo recorte el contenedor de la tabla.
- **Nombre accesible:** el texto visible primero y después el resto: «10:00, Ana López, ver
  detalle»; en el historial, «14/10/2026, Limpieza, ver detalle».

## Cambios a incorporar al sistema

| Cambio | Modo | Dónde |
|---|---|---|
| Componente «Cita que abre su detalle» (reposo, puntero, foco, nombre accesible) | nuevo | `system.md` → «Lista, tabla y tarjeta» y `system.html` |

Versión: la siguiente a la vigente cuando se fusione (1.17.0 si nadie la toma antes; la 046 también
sube el sistema y la segunda en fusionarse se renumera).

## Movimiento
Ninguno.

## Accesibilidad
- Una sola parada de foco por cita, sin enlaces anidados; Intro la abre.
- Foco visible con contraste ≥ 3:1 (`--focus-ring` sobre `--white` y sobre `--p50`).
- Objetivo de toque: la fila o tarjeta entera, ≥ 44 px de alto a 320, 390 y 1440 px.
- El estado de la cita sigue con su texto (no solo color).
- Sin JavaScript funciona igual: son enlaces.

## Decisiones
| Fecha | Tipo | Pregunta | Decisión | Fuente |
|---|---|---|---|---|
| 2026-11-14 | diseño | Ciclo de candidatos | Sin exploración (`exploration: none`): componentes del sistema y validación en pantalla del usuario | usuario (aprobó la spec 048 con esa propuesta, 2026-11-14) |
