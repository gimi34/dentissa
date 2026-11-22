---
spec: 002-registro-pacientes
verdict: approved
round: 2
date: 2026-11-21
base: 93f7ad0
head: 95f37c35
human_signoff: no requerido
---

# Review · 002 Autorregistro de pacientes

## Resumen
`approved`: R1–R9 de la ronda 1 están corregidos y probados (T023–T028), sin hallazgos nuevos. R10–R15 siguen
aceptados como deuda y `/release` los lleva al roadmap.
`--rerun` sin subagentes: el diff desde la ronda 1 son 12,6 KB en 4 archivos (`RegisterController`, la vista del
registro, `RegistroPacientesTest` y `ReglasCss`); se releyó desde cero.
Conteo: bloqueante 0 · importante 0 · menor 0 (nuevos)

## Verificación automática
| Comando | Resultado |
|---|---|
| `php artisan test` (suite completa, contenedor efímero) | ✅ 1542 en verde, 20 061 aserciones (ronda 1: 1536; línea base: 1521) |
| `vendor/bin/pint --test` sobre los PHP tocados | ✅ |
| `aidd.py validate docs/specs/002-registro-pacientes` | ✅ 0 errores |

## Criterios de aceptación
Sin cambios respecto a la ronda 1 (`history/review.r1.md`), con los dos ⚠ resueltos:
- CA2 ✅: suma `test_los_empates_se_resuelven_por_antiguedad_en_orden`, que falla en todas las ejecuciones sin el orden por antigüedad (R2).
- CA8 ✅: suma `test_otro_error_no_revela_si_el_correo_tiene_cuenta` (R1), `…_sin_recuperacion_solo_se_ofrece_iniciar_sesion` (R6), `…_el_rechazo_conserva_el_correo_y_no_la_contrasena` (R7) y `…_el_aviso_general_se_anuncia` (R5).
- CA4 ✅: comprueba el teléfono y la ciudad guardados (R8).

## Conformidad con el plan
- Cambios fuera del plan: ninguno. Los cuatro archivos tienen tarea (T023–T028, añadidas por `/review`).
- Partes del plan sin implementar: ninguna. El control de TM1 ahora tiene dos fases: el correo repetido solo se comprueba con el resto del formulario válido (R1).

## Constitution Check (sobre el código)
Sin cambios respecto a la ronda 1, salvo:
- P9 ✅: el test comprueba el color real del mensaje (`--red`), el `color: inherit` y el subrayado de los enlaces (R3). El aviso general lleva `role="alert"` (R5).
- P10 ✅: `test_el_registro_sigue_indexable_con_su_titulo` fija el `GET /registro` (R4).
- P7: sigue la excepción aceptada; el diff no añade eventos ni logs.

## Seguridad
| Herramienta | Resultado |
|---|---|
| gitleaks v8.21.2 (`dir` sobre los 4 archivos) | ✅ 0 hallazgos |
| semgrep 1.99.0 `p/php` | ✅ 0 hallazgos |
| `composer audit` + `auditar-npm.mjs` | sin cambios de dependencias desde la ronda 1 (crít: 0 · alta: 0) |
| trivy | sin cambios de dependencias desde la ronda 1 (0 altas o críticas) |

| Tema OWASP | Resultado | Evidencia |
|---|---|---|
| A07:2025 Enumeración de cuentas (TM1, R1) | ✅ | `RegisterController.php:64-69`: la regla `unique` va en una segunda validación, que solo se ejecuta si la primera pasa. Con otro error, la respuesta es la misma con un correo registrado o nuevo, y el test lo compara. Queda solo el residuo aceptado de RS13.c, que exige crear la cuenta. |
| Resto de temas | ✅ | sin cambios respecto a la ronda 1 |

## Observabilidad
- Eventos de auditoría de la spec emitidos y probados: ninguno (excepción P7 → spec 021).
- Datos sensibles en logs del código tocado: ninguno.
- `request_id` presente en los logs nuevos: no hay logs nuevos.

## SEO
| Tema | Resultado | Evidencia |
|---|---|---|
| 3. Indexación y canónica | ✅ | `test_el_registro_sigue_indexable_con_su_titulo`: 200, título de siempre, sin `noindex` en la meta ni en la cabecera (R4) |
- Sección «Descubribilidad» del plan aplicada: sí.

## Hallazgos
### Seguimiento de la ronda 1
| ID | Severidad | Estado | Evidencia |
|---|---|---|---|
| R1 | importante | resuelto | T023 fallaba sin T024 («el correo registrado no da error propio»); T024 |
| R2 | importante | resuelto | T025: sin el orden por antigüedad, el test de la secuencia falló en 3 de 3 ejecuciones |
| R3 | importante | resuelto | T025: falla sin subrayado y con otro color en `.error-msg` |
| R4 | importante | resuelto | T025: falla con `noindex` en la vista |
| R5 | importante | resuelto | T027 fallaba sin T028; T028 (`role="alert"`, icono con `aria-hidden`) |
| R6–R9 | menor | resuelto | T026 (la rotura de la condición de recuperación hace fallar su test) |
| R10–R15 | menor | aceptados | sin cambios; ver «Aceptados sin tarea» |

### Nuevos
Ninguno.

## Preparación para release
- Rollback factible: sí, `bash deploy/volver.sh <tag anterior>`, sin la copia (no hay migraciones).
- Migraciones compatibles con la versión anterior: no hay migraciones.
- Docs actualizadas: `docs/architecture.md` (T090); `docs/deployment.md` y `docs/observability.md` sin cambios (T091). En `/release`, RS13.b pasa a mitigada; RS13.c sigue pendiente.
- Cobertura de riesgos: RS13.b (dentro) implementada y probada, con R1 corregido; RS13.c fuera, con destino una spec futura.

## Tareas añadidas
- T023, T024 ← R1 (ronda 1)
- T025 ← R2, R3, R4 (ronda 1)
- T026 ← R6, R7, R8, R9 (ronda 1)
- T027, T028 ← R5 (ronda 1)

## Aceptados sin tarea
Aceptados por el usuario el 2026-11-21 (ronda 1):
- **R10** → roadmap: el mensaje del correo de 12 px y sus enlaces de 71×14 px (cumplen WCAG 2.1 AA, pero son pequeños)
- **R11** → roadmap: el separador «·» entre los enlaces del rechazo se lee en voz alta
- **R12** → roadmap: la vista del registro decide los enlaces comparando el texto del mensaje con la constante
- **R13** → roadmap: el limitador `registro` no deja rastro de un sondeo (→ spec 021)
- **R14** → roadmap: diferencia de tiempos entre el alta nueva y el correo existente (dentro de RS13.c)
- **R15** → roadmap: con la recuperación desactivada, el mensaje ofrece recuperar la contraseña sin enlace
