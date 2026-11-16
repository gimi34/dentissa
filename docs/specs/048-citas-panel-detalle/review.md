---
spec: 048-citas-panel-detalle
verdict: approved
round: 2
date: 2026-11-14
base: f794c12
head: c887bca
human_signoff: gimi34 2026-11-15
---

# Review · 048 Abrir las citas del panel de inicio

## Resumen
`approved`: ronda corta (`--rerun`, sin subagentes; diff de 12 KB releído desde cero). R1–R6, R12 y
R16–R18 de la ronda 1 quedan corregidos en T025–T031 sin efectos nuevos. Ronda anterior:
[history/review.r1.md](history/review.r1.md).
Conteo: bloqueante 0 · importante 0 · menor 0 (los 9 menores aceptados de la ronda 1 siguen → roadmap)

## Verificación automática
| Comando | Resultado |
|---|---|
| `php artisan test` (suite completa) | ✅ 1011 en verde (15 547 aserciones) |
| `vendor/bin/pint --test` (tests tocados) | ✅ |
| `python .ai/bin/aidd.py validate docs/specs/048-citas-panel-detalle` | ✅ 0 errores |
| `design/medir.mjs` | ✅ 208 comprobaciones (con Intro y el anillo de la agenda sin radio) |

## Seguimiento
| ID | Estado | Evidencia |
|---|---|---|
| R1 | corregido | T025: `#acciones-proxima` con «Ver detalles», «Reprogramar» y «Cancelar» hacia una cita a más de 24 h; comprobado rompiendo |
| R2 | corregido | T026: «05/10/2026, Limpieza 048, ver detalle»; comprobado rompiendo |
| R3, R5 | corregidos | T027; comprobados rompiendo |
| R4 | corregido | T028 (falla antes), T029 (`.tabla-tarjetas …`), `medir.mjs` |
| R6 | corregido | T030: Intro abre la cita en los cuatro paneles |
| R12, R16, R17, R18 | corregidos | T031 |

## Seguridad
Sin cambios de dependencias ni de rutas, controladores o datos en la ronda; gitleaks, semgrep y la
SCA de la ronda 1 siguen vigentes.

## Hallazgos
Ninguno.

## Aceptados sin tarea
- **R7** → roadmap: el fondo y el anillo de la agenda de la dentista llegan a los bordes de la tarjeta en el escritorio (hoy 20 px hacia dentro)
- **R8** → roadmap: el nombre accesible del historial distingue dos citas del mismo día y servicio (p. ej. con el dentista)
- **R9** → roadmap: la celda «Hora» no repite el paciente en la navegación por celdas (p. ej. `aria-describedby`)
- **R10** → roadmap: regla «nada posicionado dentro de `.fila-cita`» en `system.md` o `z-index` del `::after` con token
- **R11** → roadmap: trait `Concerns/ConsultaDom` con `xpath()` y `clases()` para los tests que los copian
- **R13** → roadmap: restaurar `libxml_use_internal_errors` en ese trait
- **R14** → roadmap: comprobar en la CI que las reglas 048 ganan en la cascada (hoy solo `medir.mjs`)
- **R15** → roadmap: dividir `test_las_reglas_048_usan_tokens` por grupos
- **R19** → roadmap: documentar en `system.md` el punto de corte de 900 px
