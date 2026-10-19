---
spec: 035-movil-pacientes-expedientes
verdict: approved
round: 3
date: 2026-10-19
base: 6362a14
head: de8531c
human_signoff: no requerido
---

# Review · 035 Móvil para pacientes y expedientes del personal

## Resumen
Ronda 3 (`--rerun` sobre `6362a14..de8531c`, 2,9 KB de diff de código): R21 queda resuelto y no hay
hallazgos nuevos. Revisado sin subagente, releyendo los archivos desde cero (diff pequeño, `/review`
modo `--rerun`). La spec puede liberarse; R3, R15–R20 y R22 siguen aceptados → roadmap.
Conteo: bloqueante 0 · importante 0 · menor 0

## Verificación automática
| Comando | Resultado |
|---|---|
| `php artisan test` | ✅ 328 tests (6500 aserciones); línea base de T002: 310, sin fallos |
| `MovilPacientesExpedientesTest`, dos veces | ✅ 18 y 18 |
| `pint --test` (PHP tocados) | ✅ |
| `aidd.py validate docs/specs/035-movil-pacientes-expedientes` | ✅ 0 errores |
| `node docs/design/capturar.mjs --medir` | ➖ no se repite: el diff de la ronda no toca `movil.css`, el layout ni `menu.js` (`AGENTS.md`); el último, «todo en orden» sobre `b92d4c5` (ronda 2) |

## Criterios de aceptación
| CA | Test | Estado | Nota |
|---|---|---|---|
| CA6 | `MovilPacientesExpedientesTest::test_el_campo_con_error_del_expediente_sigue_rojo_con_foco` y `test_el_bloque_035_de_app_css_solo_usa_tokens_y_reproduce_los_estilos` | ✅ | R21 resuelto |
| CA1–CA5, CA7–CA11 | sin cambios desde la ronda 2 | ✅ | |

## Conformidad con el plan
- Cambios fuera del plan: ninguno (`scope.md`: 2 archivos de código, ambos en T054 y T055).
- Partes sin implementar: ninguna.

## Constitution Check (sobre el código)
| Principio | Resultado | Evidencia |
|---|---|---|
| P2 Test que falla antes y pasa después | ✅ | T054 falló por la razón esperada antes de T055 (nota de la tarea) |
| P9 Solo el sistema de diseño | ✅ | la regla cambiada solo usa `--red` |
| Resto | ✅ / ➖ | sin cambios respecto a la ronda 2 |

## Seguridad
| Herramienta | Resultado |
|---|---|
| gitleaks (`883af40..HEAD`) | ✅ sin fugas |
| semgrep, composer/npm audit, trivy | ➖ sin cambios desde la ronda 2 (sin dependencias ni código PHP de la app en el diff); resultados de la ronda 2 en verde |

| Tema OWASP (2025) | Resultado | Evidencia |
|---|---|---|
| A01–A10 | ➖ | el diff es un selector CSS y un test |

## Observabilidad
- No aplica (sin logs ni eventos).

## Seguimiento de la ronda 2
| ID | Estado | Evidencia |
|---|---|---|
| R21 | resuelto | `public/css/app.css:655` `.form-expediente .field.field-error` (0,3,0), después de `.form-expediente .field:focus` (0,3,0) en `:648`: con foco gana el rojo; el `<style>` de la vista (`.field`, `.field:focus`) es menos específico. Test que falla si cambia el selector o el orden |
| R22 | aceptado | → roadmap (ronda 2) |

## Hallazgos
| ID | Severidad | Archivo:línea | Hallazgo | Sugerencia |
|---|---|---|---|---|
| — | — | — | Sin hallazgos nuevos | — |

## Preparación para release
- Rollback factible: sí; solo vistas, CSS y tests, sin migraciones (`deploy/volver.sh v0.8.0`).
- Migraciones: no hay. Feature flags: el plan no pide ninguno.
- Docs: `architecture.md` (T090); `deployment.md` sin cambios (T091). SCA sin críticas ni altas.
- Versión prevista: minor (funcionalidad nueva compatible), tras la v0.8.0.

## Tareas añadidas
- Ninguna.

## Aceptados sin tarea
Los de las rondas anteriores siguen vigentes (ronda 1: R3, R15–R20; ronda 2: R22, en `history/`).
