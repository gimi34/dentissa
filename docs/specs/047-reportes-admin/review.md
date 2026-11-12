---
spec: 047-reportes-admin
verdict: approved
round: 2
date: 2026-11-12
base: 403bc47
head: 24f49b2
human_signoff: gimi34 2026-11-12
---

# Review · 047 Reportes de la clínica para la administración

## Resumen
`approved`, con la aprobación del usuario (2026-11-12). La ronda 1 ([history/review.r1.md](history/review.r1.md))
no encontró bloqueantes y pidió corregir un aviso de seguridad ajeno a la spec (R1) y endurecer dos
tests (R4, R5). Las tres correcciones están hechas y verificadas: `composer audit` vuelve a salir con
0 y la suite completa pasa. Quedan dos menores (R2, R3), aceptados por el usuario sin tarea.

Ronda `--rerun`: se revisó el diff desde el `head` anterior (`code.diff` de 2,1 KB: dos archivos de
test, más `composer.lock`), los hallazgos abiertos y la verificación automática completa. Hecha sin
subagentes, releyendo los archivos desde cero.

Conteo: bloqueante 0 · importante 0 · menor 2 (aceptados) · corregidos 3

## Verificación automática
| Comando | Resultado |
|---|---|
| `php artisan test` (suite completa, con phpseclib 3.0.58) | ✅ 981 en verde (14 531 aserciones): los 980 de la ronda 1 más el test nuevo de T030 |
| `php artisan test tests/Feature/Reportes` (dos pasadas) | ✅ 70/70 las dos veces (493 aserciones) |
| `php artisan test --group=rnf --filter=ReportesRendimientoTest` | ✅ |
| `vendor/bin/pint --test` (los dos tests tocados) | ✅ |
| `python .ai/bin/aidd.py validate docs/specs/047-reportes-admin` | ✅ 0 errores |
| `node docs/design/capturar.mjs --medir` | ➖ no se repite: la ronda no toca vistas, CSS ni JS (resultado de la ronda 1 vigente) |
| Build | ➖ sin build: la pantalla no usa Vite |

## Seguimiento de la ronda 1
| ID | Severidad | Estado | Evidencia |
|---|---|---|---|
| R1 | importante | corregido (T029, `e190027`) | `phpseclib/phpseclib` 3.0.57 → 3.0.58; el diff del lockfile solo cambia ese paquete (`content-hash` igual); versión publicada el 2026-11-02, mismo repositorio, MIT; `composer audit --locked --ignore-severity=low --ignore-severity=medium` sale con 0 |
| R2 | menor | aceptado por el usuario (2026-11-12) | sin cambios |
| R3 | menor | aceptado por el usuario (2026-11-12) | sin cambios |
| R4 | menor | corregido (T030, `83b8066`) | `ReportesAccesoTest::test_el_acceso_denegado_queda_en_el_registro_de_auditoria` filtra por `actor_id` del dentista del test |
| R5 | menor | corregido (T030, `83b8066`) | `ReportesCssTest::test_el_aviso_de_error_cumple_el_contraste`; comprobado rompiendo el color de `.reporte-aviso` (falla) y restaurado |

## Criterios de aceptación
Los diez criterios siguen ✅ con los tests de la ronda 1 (tabla completa en
[history/review.r1.md](history/review.r1.md)); la ronda 2 no cambia código de la aplicación y añade
un test a CA7 (contraste del aviso) y refuerza el de CA9 (auditoría).

## Conformidad con el plan
- Cambios fuera del plan: `composer.lock` (T029), por decisión del usuario del 2026-11-12 para
  corregir R1; anotado en la Cobertura de `tasks.md`. `scope.md`: 2 archivos de código, 0 sin tarea.
- Partes del plan sin implementar: ninguna.

## Constitution Check (sobre el código)
Sin cambios respecto a la ronda 1, salvo:

| Principio | Resultado | Evidencia |
|---|---|---|
| P2 Test que falla antes | ✅ | el test nuevo de T030 se comprobó rompiendo lo que protege |
| P6 Sin vulnerabilidades críticas o altas | ✅ | `composer audit`, `npm audit` y trivy sin hallazgos (R1 corregido) |

## Seguridad
| Herramienta | Resultado |
|---|---|
| gitleaks v8.21.2 (`403bc47..HEAD`, 4 commits) | ✅ sin secretos |
| semgrep 1.99.0 `p/php` (`--baseline-commit 403bc47`) | ✅ 0 hallazgos (la ronda solo toca tests) |
| `composer audit --locked --ignore-severity=low --ignore-severity=medium` | ✅ sale con 0 |
| `npm audit --audit-level=high` | ✅ 0 vulnerabilidades |
| trivy 0.57.1 `fs --severity HIGH,CRITICAL` | ✅ sin hallazgos |

Temas OWASP: como en la ronda 1; A03:2025 Cadena de suministro pasa a ✅ (dependencia actualizada y
verificada según `shared/agent-security.md` §2).

## Observabilidad
Sin cambios: `acceso_denegado` emitido y probado (ahora por actor), sin logs nuevos ni datos
sensibles.

## Hallazgos
| ID | Severidad | Archivo:línea | Hallazgo | Sugerencia |
|---|---|---|---|---|
| R2 | menor | `app/Http/Controllers/ReportesController.php:37` | La vuelta de un periodo rechazado se detecta por los errores de la sesión: una consulta válida que llegue antes que la redirección muestra una vez el filtro con el error. Igual que el filtro de auditoría (spec 020) | Aceptar; al recargar se ven las cifras |
| R3 | menor | `resources/views/reportes/index.blade.php:86`, `tests/Feature/Reportes/ReportesVistasTest.php:304`, `plan.md:70` | El botón «Descargar» conserva el `id` `reporte-imprimir` y su test el nombre de «Imprimir»; `plan.md` y `tasks.md` no recogen el rótulo nuevo | Aceptar: el `design.md` registra el cambio |

La ronda 2 no añade hallazgos.

## Preparación para release
- Rollback factible: sí. Sin migraciones ni datos; `bash deploy/volver.sh <tag anterior>`.
- Migraciones: no hay. Feature flags: el plan no pide ninguno.
- Docs actualizadas (T090, T091): sí.
- SCA: sin vulnerabilidades críticas ni altas.
- La imagen de producción debe reconstruirse en `/release` para llevar phpseclib 3.0.58.

## Tareas añadidas
- T029 ← R1 (hecha)
- T030 ← R4, R5 (hecha)

## Aceptados sin tarea
Aceptados por el usuario el 2026-11-12:
- **R2** → roadmap: la vuelta de un periodo rechazado en «Reportes» se detecta por la sesión; una consulta válida que llegue antes que la redirección muestra una vez el error.
- **R3** → roadmap: el botón «Descargar» de «Reportes» conserva el `id` y el nombre de test de «Imprimir», y el plan de la 047 no recoge el rótulo nuevo.
