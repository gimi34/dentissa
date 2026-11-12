---
result: pass
round: 2
mode: delta
constitution_version: 1.3.0
date: 2026-11-12
spec_sha: 9df4fc4e2c3b
plan_sha: 7605fc1399bb
tasks_sha: eca430681d8d
---

# Análisis · 045 Términos de uso y aviso de privacidad

## Resumen
**Pass.** Los catorce hallazgos de la ronda 1 ([history/analysis.r1.md](history/analysis.r1.md)) quedaron
resueltos, sin efectos nuevos, según el análisis delta hecho por un subagente (perfil `standard`) sobre el diff
de spec, plan, tareas, acta y texto del aviso. El delta encontró dos más (A15, A16), corregidos y comprobados
en la consolidación de esta misma ronda. Ningún CRÍTICO ni ALTO abierto.
Conteo (nuevos de esta ronda): CRÍTICA 0 · ALTA 0 · MEDIA 1 · BAJA 1, los dos resueltos

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 8 / 8 |
| Amenazas con control y test | 2 / 2 (TM2 con las dos vistas en `VISTAS`) |
| Principios de la constitución evaluados | 10 / 10 (en las líneas cambiadas; el resto, en la ronda 1) |

## Seguimiento de rondas anteriores
| ID | Severidad | Estado | Evidencia |
|---|---|---|---|
| A1 | ALTA | resuelto | `plan.md` P4, TM2 y Trazabilidad, y T013: las dos vistas en `VISTAS` de `PaginasPublicasTest` (`:81`, test en `:428`) |
| A2 | ALTA | resuelto | `plan.md` P9 y T014: vistas y rutas nuevas en `test_las_vistas_publicas_no_llevan_estilos_en_linea` (`PaginasPublicasCssTest.php:157`, `:297`) |
| A3 | ALTA | resuelto | `c4/privacidad.html` sin «Recordarme» (`AuthController.php:97` llama a `Auth::attempt` sin `remember`); el registro de seguridad, «los accesos denegados y algunos cambios de la cuenta, como el restablecimiento de la contraseña» (`Auditoria.php:102`, `RecuperarContrasenaController.php:165`); `design.md` → «Decisiones» (`refinado`) |
| A4 | ALTA | resuelto | `plan.md` P6 y D10, y T002: 3.0.58 publicada el 2026-11-02, cumple los 7 días |
| A5 | MEDIA | resuelto | CA8 y P10 con `test_el_texto_es_el_aprobado` y `test_todos_los_enlaces_internos_resuelven`; T013 ya no lo atribuye a `test_el_contenido_va_en_el_html_del_servidor` |
| A6 | MEDIA | resuelto | La medición con `medir.mjs` pasa a T027 (depende de T003 y T024); T024 depende de T012, T022 y T023; la caracterización de CA7, tras T023; sin ciclos |
| A7 | MEDIA | resuelto | T011: el texto de la etiqueta sin las dos anotaciones es exactamente el de CA4 |
| A8 | MEDIA | resuelto con efecto nuevo (→ A15) | El aviso nombra el contacto de emergencia (`pacientes.contacto_emergencia`, `telefono_emergencia`) y el navegador; «en tu sesión» no era exacto (A15) |
| A9 | MEDIA | resuelto | Spec («Auditoría») y plan (P7): el alta aún no se audita, pendiente en `docs/observability.md` para la 021 |
| A10 | MEDIA | resuelto | `docs/security.md` en el plan y en T030 |
| A11 | BAJA | resuelto | T010 (`test_el_indice_va_antes_de_las_secciones`) comprueba la estructura accesible |
| A12 | BAJA | resuelto | T021: `route:list` y los tests de T010 pasan de `404` a fallar por la vista |
| A13 | BAJA | resuelto | CA5: «hoy el registro con Google ya lo rechaza; el normal solo lo impide el navegador» |
| A14 | BAJA | resuelto | `plan.md` → Riesgos lista los archivos que comparte con la 040 |

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A15 | Ambigüedad / CA3 | MEDIA | `c4/privacidad.html` («Datos técnicos») | Decía que la sesión guarda la IP y el navegador; con `SESSION_DRIVER=file` (local y producción, `deploy/README.md:87`) la sesión no los guarda. Sí los guarda el log de acceso de nginx (`docker/nginx/dentissa.prod.conf:19`, `$remote_addr` y `$http_user_agent`) | **Resuelto en esta ronda:** «en los registros del servidor y de seguridad del sitio» (`design.md` → «Decisiones», `refinado`); `c4` vuelto a medir: puertas y KPIs en verde |
| A16 | Inconsistencia | BAJA | T010, T023 | T010 prueba CA8 (`test_el_texto_es_el_aprobado`) sin CA8 en `cubre`; T023 exige que pase T013 sin depender de ella | **Resuelto en esta ronda:** CA8 en `cubre` de T010 y T013 en `depende` de T023 |

## Decisiones pendientes del usuario
- Ninguna. Spec, plan y tareas volvieron a `draft` por las correcciones y necesitan su aprobación antes de
  `/implement`.

## Aceptados
Ninguno.
