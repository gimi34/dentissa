---
spec: 035-movil-pacientes-expedientes
verdict: changes_requested
round: 2
date: 2026-10-19
base: 7ed2fae
head: 6362a14
human_signoff: no requerido
---

# Review · 035 Móvil para pacientes y expedientes del personal

## Resumen
Ronda 2 (`--rerun` sobre `7ed2fae..6362a14`): los 13 hallazgos a corregir de la ronda 1 (R1, R2, R4–R14)
están resueltos, más T052/T053 (iconos de los demás formularios), y la verificación automática está en
verde. Un revisor único con perfil `critical` (`opus`) encontró dos menores nuevos: el usuario decide
corregir R21 (el campo con error de la ficha del expediente pierde el rojo con foco) y acepta R22 → roadmap.
Conteo: bloqueante 0 · importante 0 · menor 2

## Verificación automática
| Comando | Resultado |
|---|---|
| `php artisan test` | ✅ 327 tests (6495 aserciones); línea base de T002: 310, sin fallos |
| Tests de móvil (`MovilPacientesExpedientesTest`, `MovilPacienteTest`), dos veces | ✅ 44 y 44 |
| `pint --test` (PHP tocados) | ✅ 2 archivos |
| `aidd.py validate docs/specs/035-movil-pacientes-expedientes` | ✅ 0 errores |
| `node docs/design/capturar.mjs --medir` | ✅ «todo en orden», código 0, sobre el código final `b92d4c5` (T051, línea base v0.8.0 de la misma sesión, 2026-10-19); desde ahí solo cambian documentos y capturas |
| `npm run build` | ➖ no aplica |

## Criterios de aceptación
| CA | Test | Estado | Nota |
|---|---|---|---|
| CA1–CA5 | `MovilPacientesExpedientesTest`, `--medir` | ✅ | |
| CA6 | `MovilPacientesExpedientesTest::test_cada_campo_validado_muestra_su_error_junto_a_el` (dos envíos reales de la ficha y edición del paciente), `--medir` (errores también en las ediciones) | ✅ | R21: con foco, el borde del campo con error de la ficha no es rojo (el mensaje y `aria-invalid` sí) |
| CA7 | `test_los_iconos_decorativos_llevan_aria_hidden`, `test_los_botones_del_formulario_no_heredan_el_reparto_de_la_ficha`, `--medir` | ✅ | |
| CA8, CA9 | `--medir` (palabra de 60 letras en las migas, con control positivo), `MovilPacienteTest::test_los_textos_largos_parten_de_linea` | ✅ | R1 resuelto |
| CA10 | `--medir` (escritorio igual a la línea base) | ✅ | |
| CA11 | `MovilPacienteTest::test_los_pares_del_menu_cumplen_aa` (también el fondo del panel) | ✅ | |

## Conformidad con el plan
- Cambios fuera del plan: ninguno (`scope.md`: 0 archivos sin tarea). `MovilPacienteTest` en T049 como archivo extra justificado (lista de `anywhere`).
- Partes sin implementar: ninguna; T042, T050 y T051 solo tocan `docs/` (fuera de `code.diff`).

## Constitution Check (sobre el código)
| Principio | Resultado | Evidencia |
|---|---|---|
| P2 Test que falla antes y pasa después | ✅ | T042 y T045 fallaron por la razón esperada antes de T046–T049; T043, T044 y T052 comprobados rompiendo (notas de las tareas) |
| P9 Solo el sistema de diseño · WCAG 2.1 AA | ✅ | reglas nuevas solo con tokens (`--red`, `--space-3`, `--campo-fecha-max` nuevo en `system.md`); el `style="max-width:220px"` de la fecha pasa a clase |
| Resto (P1, P3–P8, P10) | ✅ / ➖ | sin cambios respecto a la ronda 1: el diff no toca controladores, rutas, dependencias ni datos |

## Seguridad
| Herramienta | Resultado |
|---|---|
| gitleaks (`883af40..HEAD`) | ✅ sin fugas |
| semgrep (`p/php --metrics=off`, archivos tocados) | ✅ sin hallazgos |
| composer audit + npm audit | crít: 0 · alta: 0 · media: 0 |
| trivy fs | ✅ solo el falso positivo conocido de `vendor/mockery/mockery/docs/requirements.txt` |

| Tema OWASP (2025) | Resultado | Evidencia |
|---|---|---|
| A01–A10 | ✅ / ➖ | el diff solo añade clases, `aria-hidden`, CSS con tokens y tests; sin rutas, controladores, dependencias ni rutas protegidas |

## Observabilidad
- Eventos de auditoría de la spec: no aplica. Datos sensibles en logs: ninguno (sin logs nuevos).

## Seguimiento de la ronda 1
| ID | Estado | Evidencia |
|---|---|---|
| R1 | resuelto | `public/css/movil.css:271` `.migas a { overflow-wrap: anywhere }`, con el toque de la 033; `--medir` con 60 letras y control positivo (T042, T049) |
| R2 | resuelto | dos envíos reales de la ficha del expediente y PUT de la edición del paciente con errores (T043) |
| R4 | resuelto con efecto nuevo (→ R21) | `app.css:654` `.form-expediente .field-error { border-color: var(--red) }` |
| R5 | resuelto | reglas separadas en `movil.css:259,262`; test por regla (T045, T047) |
| R6 | resuelto | `aria-hidden` en los iconos de la ficha del paciente y la lista de expedientes, y en los demás formularios (T048, T052, T053) |
| R7 | resuelto | el plan cuenta once tokens (T050) |
| R8, R9, R10 | resueltos | tests contados, anclados a su sección y sin ocultos (T043) |
| R11 | resuelto | `MovilPacienteTest.php:364` (T044) |
| R12 | resuelto | `--medir` mide los errores en las ediciones y en la ficha (T042) |
| R13 | resuelto | `movil.css:257` `.topbar:has(.acciones-ficha) { padding-block: var(--space-3) }` (T047) |
| R14 | resuelto | `.campo-fecha` con `--campo-fecha-max` en el escritorio y sin máximo en el móvil (T046, T047) |

## Hallazgos
| ID | Severidad | Archivo:línea | Hallazgo | Sugerencia |
|---|---|---|---|---|
| R21 | menor | `public/css/app.css:654` (frente a `:648`) | En la ficha del expediente, el campo con error pierde el borde rojo mientras está enfocado: `.form-expediente .field:focus` (0,3,0) gana a `.form-expediente .field-error` (0,2,0). En los otros dos formularios el rojo se mantiene con foco (su `<style>` lo declara con `!important`). El error sigue indicado por el mensaje, `aria-invalid` y el anillo de foco; ningún test lo detecta | Test que falle hoy y regla con especificidad suficiente, después de la de foco |
| R22 | menor | `docs/design/capturar.mjs:752` (`enviarConErrores`) | En la ficha del expediente, `--medir` solo envía con errores el formulario del contenido; el error de «Guardar fecha», en la columna lateral, no se mide a 320 en el navegador (lo cubre el test PHP por su posición en el DOM) | → roadmap: un segundo envío con la fecha no válida en `admin-ficha-expediente@320` cuando se toque el guion |

## Preparación para release
- Rollback factible: sí; solo vistas, CSS y tests, sin migraciones (`deploy/volver.sh v0.8.0`).
- Migraciones: no hay. Feature flags: el plan no pide ninguno.
- Docs: `architecture.md` (T090); `deployment.md` sin cambios (T091). SCA sin críticas ni altas.

## Tareas añadidas
- T054 ← R21
- T055 ← R21

## Aceptados sin tarea
Aceptados por el usuario el 2026-10-19:
- **R22** → roadmap: `--medir` no envía con errores el formulario «Guardar fecha» de la ficha del expediente a 320; añadir ese envío cuando se toque `capturar.mjs`.
