---
spec: 050-hora-clinica
verdict: approved
round: 2
date: 2026-11-18
base: 563259e
head: 631b603
human_signoff: no requerido
---

# Review · 050 La hora de la clínica en todo el panel

## Resumen
`approved`: R1 (bloqueante), R2 (importante) y los seis menores que el usuario decidió corregir están resueltos, cada
control comprobado rompiéndolo. Ronda hecha sin subagentes (diff de código de 18,6 KB), releyendo el diff desde cero.
Queda un menor nuevo y ajeno a la spec (R14), que va al roadmap junto con los cinco aceptados en la ronda 1.
Conteo: bloqueante 0 · importante 0 · menor 1

## Verificación automática
| Comando | Resultado |
|---|---|
| `php artisan test` (suite completa, sobre 631b603) | ✅ 1370 en verde, 18833 aserciones (línea base: 1348 en verde, 0 fallos) |
| `vendor/bin/pint --test` sobre lo tocado | ✅ 16 archivos |
| `python .ai/bin/aidd.py validate` | ✅ 0 errores |
| Build | ➖ sin cambios de assets |

## Criterios de aceptación
| CA | Test | Estado | Nota |
|---|---|---|---|
| CA1 | `HoraClinicaPanelesTest` (paneles y «Mis doctores») | ✅ | |
| CA2 | `HoraClinicaPanelesTest::test_el_paciente_ve_como_proxima_su_cita_de_esta_noche` | ✅ | |
| CA3 | `HoraClinicaPanelesTest::test_la_ficha_del_paciente_muestra_como_proxima_la_cita_de_esta_noche`, `RelojClinicaTest::test_la_edad_cambia_el_dia_del_cumpleanos_en_la_clinica` | ✅ | R3 y R4 resueltos |
| CA4 | `HoraClinicaPanelesTest::test_la_agenda_usa_el_dia_de_la_clinica` | ✅ | |
| CA5 | `HoraClinicaFormulariosTest` | ✅ | R2 resuelto |
| CA6 | `HoraClinicaPanelesTest::test_las_cuentas_del_mes_usan_el_mes_de_la_clinica` | ✅ | |
| CA7 | `HoraClinicaFechasSistemaTest`, `HistorialCitaTest` | ✅ | Verificación manual del usuario (T030) |
| CA8 | `RelojClinicaTest`, `HoraClinicaPanelesTest::test_los_bordes_del_dia_de_la_clinica` | ✅ | |
| CA9 | Suites de T016, dentro de la suite completa | ✅ | |
| CA10 | `HoraClinicaFormulariosTest::test_el_servidor_rechaza_fechas_pasadas_segun_el_dia_de_la_clinica`, `test_el_servidor_rechaza_fechas_con_hora_o_zona` | ✅ | R1 y R2 resueltos |

## Conformidad con el plan
- Cambios fuera del plan: ninguno; `RelojClinica::edad()` nace de R3 (T044).
- Partes del plan sin implementar: ninguna.

## Constitution Check (sobre el código)
| Principio | Resultado | Evidencia |
|---|---|---|
| P2 Tests primero | ✅ | T040 y T043 en rojo antes de corregir |
| P3 Autorización | ❌ aceptado (D9) | Las correcciones no tocan permisos |
| P4 Validación en servidor | ✅ | `date_format:Y-m-d` en las siete reglas |
| P6 Vulnerabilidades | ✅ | Sin dependencias nuevas |
| P7 Auditoría | ❌ aceptado (D9) | Las correcciones no tocan eventos |
| Resto de principios | ✅ / ➖ | Sin cambios desde la ronda 1 |

## Seguridad
| Herramienta | Resultado |
|---|---|
| gitleaks 8.21.2 | ✅ 0 hallazgos |
| semgrep 1.99.0 (p/php) | ✅ 0 hallazgos |
| `composer audit` | ✅ crít: 0 · alta: 0 |
| `npm audit --audit-level=high` | ✅ 0 vulnerabilidades |

| Tema OWASP | Resultado | Evidencia |
|---|---|---|
| A05:2025 Injection (validación de entrada) | ✅ | R1 y R2: cada regla comprobada rompiéndola por separado (notas de T041 y T042) |
| A06:2025 Insecure Design | ✅ | TM1 y TM2 con control y test |

## Observabilidad
- Eventos de auditoría de la spec emitidos y probados: no aplica (la 050 no añade eventos).
- Datos sensibles en logs del código tocado: ninguno añadido.
- `request_id` presente en los logs nuevos: no hay logs nuevos.

## SEO
| Tema | Resultado | Evidencia |
|---|---|---|
| Indexación, canónica y metadatos | ➖ | Las correcciones no tocan rutas públicas |
- Sección "Descubribilidad" del plan aplicada: no aplica.

## Hallazgos
### Seguimiento de la ronda 1
| ID | Severidad | Estado | Evidencia |
|---|---|---|---|
| R1 | bloqueante | resuelto | T040, T041 |
| R2 | importante | resuelto | T040, T042 |
| R3 | menor | resuelto | T043, T044 |
| R4 | menor | resuelto | T043 |
| R5 | menor | resuelto | T043, T044 |
| R6 | menor | resuelto | T040 |
| R7 | menor | resuelto | T045 |
| R8 | menor | resuelto | T046 |
| R9–R13 | menor | aceptados → roadmap | Ronda 1 |

### Nuevos
| ID | Severidad | Archivo:línea | Hallazgo | Sugerencia |
|---|---|---|---|---|
| R14 | menor | `app/Http/Controllers/ExpedientesController.php:134` | Un alta de expediente enviada a mano sin el campo `cita_id` da un error 500 (`Undefined array key`); el formulario siempre lo envía. Preexistente y ajeno a la spec | `/fix` aparte: `$data['cita_id'] ?? null` |

## Preparación para release
- Rollback factible: sí, `deploy/volver.sh <tag anterior>`, sin migraciones.
- Migraciones compatibles con la versión anterior: no hay migraciones.
- Docs actualizadas (architecture, deployment): sí (T090, T091).

## Tareas añadidas
Ninguna en esta ronda.

## Aceptados sin tarea
- **R14** → roadmap: un alta de expediente enviada a mano sin `cita_id` da un 500 (preexistente).
