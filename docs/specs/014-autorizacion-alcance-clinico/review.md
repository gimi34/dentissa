---
spec: 014-autorizacion-alcance-clinico
verdict: approved
round: 3
date: 2026-11-22
base: 65fa924d
head: 8c015af0
human_signoff: no requerido
---

# Review · 014 Autorización por alcance clínico

## Resumen
`approved`. R26–R30 de la ronda 2 están corregidos y probados (T050–T054), y no hay hallazgos nuevos. R1–R12 y R17
quedaron corregidos en la ronda 2 (`history/review.r2.md`). R13–R16 y R18–R25 siguen aceptados como deuda y
`/release` los lleva al roadmap.
Conteo (hallazgos nuevos): bloqueante 0 · importante 0 · menor 0

Revisión del diff `65fa924d..8c015af0` (7.7 KB, `review-pack --base 65fa924d`, 4 archivos de código, 0 sin tarea).
La hizo quien consolida, releyendo desde cero los archivos tocados (`PacientesController::update`, `public/css/app.css`,
`AlcancePacientesTest`, `AlcanceVistasTest`), porque el diff está por debajo de ~30 KB.

## Verificación automática
| Comando | Resultado |
|---|---|
| `php artisan test` (suite completa) | ✅ 1713 en verde, 20 888 aserciones (ronda 2: 1711; línea base de `/implement`: 1645) |
| `vendor/bin/pint --test` sobre los 19 PHP tocados desde `impl_base` | ✅ |
| `python .ai/bin/aidd.py validate` sin ruta | ✅ 0 errores |
| Build | ➖ no aplica |

## Criterios de aceptación
| CA | Test | Estado | Nota |
|---|---|---|---|
| CA1–CA14 | los de las rondas 1 y 2 | ✅ | sin cambios |
| CA8 | `AlcanceVistasTest::test_el_doctor_obligatorio_del_asistente_es_accesible` (el error ya no repite la ayuda) | ✅ | R30 corregido |
| CA15 | `AlcancePacientesTest::test_quien_llega_por_una_cita_no_cambia_el_doctor` (un registro por intento), `::test_quien_llega_por_una_cita_guarda_la_ficha_con_el_doctor_desactivado`, `::test_sin_doctor_solo_el_admin_lo_asigna`, `AlcanceVistasTest::test_el_doctor_es_fijo_para_quien_no_puede_reasignar` | ✅ | R26, R28 y R29 corregidos |

## Conformidad con el plan
- Cambios fuera del plan: ninguno (`scope.md`: 0 archivos sin tarea, 0 tareas sin diff).
- Partes del plan sin implementar: ninguna.

## Constitution Check (sobre el código)
| Principio | Resultado | Evidencia |
|---|---|---|
| P2 | ✅ | T050 y la aserción de T054 fallaban antes; las roturas de T051 y T053 están comprobadas |
| P4 | ✅ | Sin `DB::raw` ni SQL nuevo; `dentista_id` sin cambio se acepta solo si es el valor guardado |
| P9 | ✅ | `.autor-expediente` con tokens (`--text-body`, `--text`); sin `style=""` |
| Resto | ✅ / ➖ | Sin cambios respecto a la ronda 2 |

## Seguridad
| Herramienta | Resultado |
|---|---|
| gitleaks (diff desde `impl_base`) | ✅ 0 hallazgos |
| semgrep `p/php` (19 PHP) | ✅ 0 hallazgos |
| `composer audit --locked` | crít: 0 · alta: 0 · baja: 1 (R25) |
| `auditar-npm.mjs` | ✅ sin altas ni críticas |

| Tema OWASP (2025) | Resultado | Evidencia |
|---|---|---|
| A01 Control de acceso | ✅ | R26 no abre ningún hueco: el 403 de CA15 va antes de validar, y la excepción solo acepta el `dentista_id` ya guardado |

## Observabilidad
- Eventos de auditoría: sin cambios; cada 403 de CA15 deja su propio registro (R28).
- Datos sensibles en logs: ninguno (0 `Log::` nuevos en el código).

## Hallazgos
| ID | Severidad | Archivo:línea | Hallazgo | Sugerencia |
|---|---|---|---|---|

Sin hallazgos nuevos. R26–R30: corregidos.

## Preparación para release
- Rollback factible: sí. `bash deploy/volver.sh <tag anterior>` sin copia; no hay migraciones ni flags.
- Migraciones compatibles: no hay.
- Docs actualizadas: `docs/architecture.md` (siete Gates de `AccesoClinico`) y `docs/observability.md`
  (`paciente_leido`, `expediente_leido`); `docs/deployment.md` sin cambios.
- SCA: ninguna crítica ni alta.
- Cobertura de riesgos: RS1.a–RS1.e implementadas y probadas (RS1.a con CA15). OB1.d dentro para ficha, edición y
  expediente; los listados, a la 022. El estado en `docs/security.md` y `docs/observability.md` lo cambia `/release`.

## Tareas añadidas
- Ninguna en esta ronda.

## Aceptados sin tarea
- **R13** → roadmap: el expediente ajeno y el inexistente dan cuerpos JSON distintos (`denyAsNotFound` frente a `abort(404)`); los tests solo comparan el estado
- **R14** → roadmap: `paciente_id`/`dentista_id` en array dan 500 en las altas y ediciones del admin (falta `string`)
- **R15** → roadmap: `buscar` en array da 500 en los directorios de pacientes y expedientes
- **R16** → roadmap: renombrar los tests del asistente que reciben el 403 del rol de la ruta, no del Gate
- **R18** → roadmap: helper común para «Gate y `denegar()` con la ruta» en Citas, Expedientes y Pacientes
- **R19** → roadmap: asignar el valor de `aplicarAPacientes` en vez de depender de la mutación del Builder
- **R20** → roadmap: evaluar una sola vez `editar-expediente` y `ver-paciente` en las vistas
- **R21** → roadmap: `$doctorObligatorio` desde el controlador, no con `isset($paciente)` en la vista
- **R22** → roadmap: separar la aserción de estilos en línea del estado vacío
- **R23** → roadmap: dividir `test_el_asistente_asigna_uno_de_sus_dentistas` en cuatro tests
- **R24** → roadmap: asociar programáticamente el autor fijo del alta de expediente
- **R25** → roadmap: explicar en el aviso de duplicado por qué no hay ficha que abrir; actualizar Laravel a ≥ 12.71.0 (GHSA-63hm-g96r-2m2q, bajo)
