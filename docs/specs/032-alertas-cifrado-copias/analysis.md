---
result: pass
round: 3
mode: delta
constitution_version: 1.3.0
date: 2026-10-25
spec_sha: 7ba3fdfb034e
plan_sha: f474398adc2e
tasks_sha: 2816acdd4523
---

# Análisis · 032 Alertas y copias cifradas en producción

## Resumen
Resultado **pass**.
- Las correcciones de la ronda 2 resuelven los ocho hallazgos abiertos, incluido el alto A29: la
  orden remota espera la primera línea descifrada antes de tocar la base. A32 y A36 dejan dos
  efectos nuevos de severidad media (A49, A50).
- El delta deja tres hallazgos nuevos (A49–A51), que el usuario acepta como notas de sus tareas.
  No hay cambio estructural.
- Delta de 131 líneas, revisado por un subagente independiente (perfil `standard`, `sonnet`).
- Abiertos sin aceptar: CRÍTICA 0 · ALTA 0 · MEDIA 0 · BAJA 0. Aceptados en total: 15 (12 de la
  ronda 2 y 3 de esta).

Ronda anterior: [history/analysis.r2.md](history/analysis.r2.md).

Conteo (nuevos): CRÍTICA 0 · ALTA 0 · MEDIA 2 · BAJA 1

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 10 / 10 |
| Amenazas con control y test | 15 / 15 |
| Principios de la constitución evaluados | 10 / 10 (P7 y P8 «❌ aceptado» con fuente usuario) |

## Seguimiento de rondas anteriores
| ID | Severidad | Estado | Evidencia |
|---|---|---|---|
| A29 | ALTA | resuelto | D3 pasada 2: la orden remota espera la primera línea antes de `DROP`/`CREATE`; éxito con código 0 de los dos lados y `-- Dump completed`. T016 `test_restaurar_no_toca_la_base_sin_datos_descifrados`; T029 y T044 con la caché vaciada. Implementable con `read` y `printf`/`cat` en bash |
| A32 | MEDIA | resuelto con efecto nuevo (→ A50) | D4, T037, T017 |
| A33 | MEDIA | resuelto | Contratos y D7 (`metodo` por la ruta del callback, `routes/web.php:27`), T010, T020 |
| A34 | MEDIA | resuelto | Contratos (cuarto mensaje), T016, T029 |
| A36 | MEDIA | resuelto con efecto nuevo (→ A49) | D8 (aviso `formato`), T011, T023, T024, T091 |
| A37 | MEDIA | resuelto | P7 «❌ aceptado» con la excepción de la 030 en el plan y en las tareas |
| A38 | MEDIA | resuelto | P8 cita `history/analysis.r1.md` |
| A46 | BAJA | resuelto | TM8 con fuente usuario (2026-10-25) |

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A49 | Cobertura de riesgos | MEDIA | D8, T011, T023 | Con menos de 20 líneas en 5 min, que es el tráfico normal de la clínica, el aviso `formato` no salta y un cambio de `log_format` vuelve a apagar el aviso de 5xx sin ruido. | Aceptado → nota de T011, T023 |
| A50 | Ambigüedad | MEDIA | D4, T037, T017 | No está definido cómo se cuenta «0 pendientes» con `migrate:status --pending`, que da una tabla y no un número. Tampoco el caso del primer despliegue, sin copia. | Aceptado → nota de T017, T037, T016 |
| A51 | Ambigüedad | BAJA | D3, T029 | No se dice cómo conoce la orden remota la última línea cargada. | Aceptado → nota de T016, T029 |

## Decisiones del usuario
- 2026-10-25: aceptar A49, A50 y A51 como notas de sus tareas y dar el análisis por aprobado.

## Aceptados
Aceptados por el usuario el 2026-10-25 (ronda 2):
- **A30** → nota de T041: el `verificar.sh` completo de T041 anota como esperado el FALLO «temporizador activo»; el de T042 sale todo OK.
- **A31** → nota de T044: la comparación con la foto se hace antes de dar tráfico, o excluyendo tablas volátiles declaradas.
- **A35** → nota de T098, T044: 030 CA7 anotado como modificado por 032 CA9; duración de `restaurar.sh --volver` frente a 15 min.
- **A39** → nota de T051: el roadmap dice que la 020 lleva el `scheduler` a producción, mueve `dentissa:vigilar` y retira el temporizador.
- **A40** → nota de T017, T030: cualquier segundo argumento distinto de `--sin-levantar` se rechaza antes del checkout.
- **A41** → nota de T042: el ensayo de la copia en claro usa un señuelo sin datos.
- **A42** → nota de T019, T032: TLS obligatorio en el envío.
- **A43** → nota de T016, T029: `<ref>` y `DENTISSA_APP` validados o citados en la orden remota.
- **A44** → nota de T012, T024: lectura incremental o con tope del canal `seguridad`.
- **A45** → nota de T010, T020: `name` del canal, o el valor real en el contrato.
- **A47** → nota de T050: coste del descubrimiento de listeners o `event:cache`, anotado en el RNF.
- **A48** → nota de T098: la 001 solo se anota; no entra en `extends`.

Aceptados por el usuario el 2026-10-25 (ronda 3):
- **A49** → nota de T011, T023: el aviso `formato` también salta con poco tráfico, con su caso en VigilanteTest.
- **A50** → nota de T017, T037, T016: criterio de «0 pendientes» sobre la salida real de Laravel 12 o el recuento de `migrations`, distinto de un fallo; caso sin copia coherente.
- **A51** → nota de T016, T029: la orden remota registra la última línea cargada con un miembro de su tubería; caso de volcado cortado tras la primera línea.
