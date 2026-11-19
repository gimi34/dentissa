---
spec: 050-hora-clinica
verdict: changes_requested
round: 1
date: 2026-11-18
base: 3e02d57
head: 563259e
human_signoff: no requerido
---

# Review · 050 La hora de la clínica en todo el panel

## Resumen
`changes_requested`: la hora de la clínica funciona en todo el alcance y la suite está en verde, pero CA10 se puede
saltar: una próxima visita para hoy enviada a mano con hora (`2026-11-17 10:00`) pasa la validación (R1). Las cinco
reglas de nacimiento tienen el mismo defecto (R2). El usuario decide corregir R1, R2 y seis menores; cinco menores van
al roadmap. Accesibilidad y SEO sin revisor propio: el diff no cambia marcado, clases ni metadatos, solo valores de
fecha y el año del pie.
Conteo: bloqueante 1 · importante 1 · menor 11

## Verificación automática
| Comando | Resultado |
|---|---|
| `php artisan test` (suite completa, sobre 563259e) | ✅ 1368 en verde, 18797 aserciones (línea base: 1348 en verde, 0 fallos) |
| Regresión de CA9 (las suites de T016) | ✅ 400 en verde, 5223 aserciones, igual que la línea base |
| `vendor/bin/pint --test` sobre lo tocado | ✅ 16 archivos |
| `python .ai/bin/aidd.py validate` | ✅ 0 errores |
| Build | ➖ sin cambios de assets |

## Criterios de aceptación
| CA | Test | Estado | Nota |
|---|---|---|---|
| CA1 | `HoraClinicaPanelesTest::test_los_paneles_del_personal_muestran_el_dia_de_la_clinica`, `test_mis_doctores_cuenta_las_citas_de_hoy_de_la_clinica` | ✅ | |
| CA2 | `HoraClinicaPanelesTest::test_el_paciente_ve_como_proxima_su_cita_de_esta_noche` | ✅ | |
| CA3 | `HoraClinicaPanelesTest::test_la_ficha_del_paciente_muestra_como_proxima_la_cita_de_esta_noche` | ⚠ | La edad falla el día del cumpleaños en siete fechas antiguas y el test solo mira la víspera (R3, R4) |
| CA4 | `HoraClinicaPanelesTest::test_la_agenda_usa_el_dia_de_la_clinica` | ✅ | Caracterización, comprobada rompiendo la zona |
| CA5 | `HoraClinicaFormulariosTest` (propone, acepta, aviso) | ⚠ | El nacimiento acepta textos con zona (R2) |
| CA6 | `HoraClinicaPanelesTest::test_las_cuentas_del_mes_usan_el_mes_de_la_clinica` | ✅ | |
| CA7 | `HoraClinicaFechasSistemaTest`, `HistorialCitaTest` | ✅ | |
| CA8 | `RelojClinicaTest`, `HoraClinicaPanelesTest::test_los_bordes_del_dia_de_la_clinica` | ✅ | |
| CA9 | Suites de T016 | ✅ | 400 en verde, sin cambios |
| CA10 | `HoraClinicaFormulariosTest::test_el_servidor_rechaza_fechas_pasadas_segun_el_dia_de_la_clinica` | ❌ | Una fecha de hoy con hora pasa (R1, R2) |

## Conformidad con el plan
- Cambios fuera del plan: ninguno (`scope.md`: 36 archivos, 0 sin tarea).
- Partes del plan sin implementar: ninguna (0 tareas sin diff).

## Constitution Check (sobre el código)
| Principio | Resultado | Evidencia |
|---|---|---|
| P1 Spec aprobada | ✅ | Aprobada el 2026-11-18 tras `/analyze` r3 |
| P2 Tests primero | ✅ | Tests en rojo antes de implementar (notas de T010–T015) |
| P3 Autorización | ❌ aceptado (D9) | El diff no añade accesos ni quita comprobaciones |
| P4 Validación en servidor | ❌ → R1, R2 | Reglas en servidor, pero `date` admite valores con hora o zona |
| P5 Datos sensibles | ➖ | Sin datos nuevos |
| P6 Vulnerabilidades | ✅ | Sin dependencias nuevas; audits limpios |
| P7 Auditoría | ❌ aceptado (D9) | El diff no quita eventos |
| P8 Logs | ➖ | Sin logs nuevos |
| P9 Accesibilidad y diseño | ✅ | Sin cambios de marcado ni estilos |
| P10 Páginas públicas | ✅ | Solo cambia el año del pie; `PaginasPublicasTest` y `PaginaInicioTest` en verde |

## Seguridad
| Herramienta | Resultado |
|---|---|
| gitleaks 8.21.2 | ✅ 0 hallazgos |
| semgrep 1.99.0 (p/php) | ✅ 0 hallazgos |
| `composer audit` | ✅ crít: 0 · alta: 0 |
| `npm audit --audit-level=high` | ✅ 0 vulnerabilidades |

| Tema OWASP | Resultado | Evidencia |
|---|---|---|
| A01:2025 Control de acceso | ➖ | Excepción P3 aceptada (D9); el diff no la empeora |
| A05:2025 Injection (validación de entrada) | ❌ → R1, R2 | `ExpedientesController.php:121, 207`; TM2 ✅ (`RelojClinica::fechaDe`) |
| A06:2025 Insecure Design | ❌ → R1 | TM1 incompleto para valores con hora |

## Observabilidad
- Eventos de auditoría de la spec emitidos y probados: no aplica (la 050 no añade eventos); suite `Auditoria/` en verde.
- Datos sensibles en logs del código tocado: ninguno añadido.
- `request_id` presente en los logs nuevos: no hay logs nuevos.

## SEO
| Tema | Resultado | Evidencia |
|---|---|---|
| Indexación, canónica y metadatos | ➖ | El único cambio en rutas públicas es el año del pie (`layouts/publico.blade.php`) |
- Sección "Descubribilidad" del plan aplicada: no aplica.

## Hallazgos
| ID | Severidad | Archivo:línea | Hallazgo | Sugerencia |
|---|---|---|---|---|
| R1 | bloqueante | `app/Http/Controllers/ExpedientesController.php:121, 207` | `date` + `after:<hoy>` compara con las 00:00: `2026-11-17 10:00` pasa y CA10 exige rechazarlo (comprobado con el validador) | Exigir `AAAA-MM-DD` en el alta y en la ficha, con mensaje en español y test por `POST` y `PUT` |
| R2 | importante | `PacientesController.php:92, 274`, `PerfilController.php:39`, `RegisterController.php:37, 127` | La regla `date` del nacimiento acepta `2026-11-17T00:00:00+14:00` y `2026-11-20 -10 days` (comprobado con el validador), que además pueden fallar al guardar | Exigir `AAAA-MM-DD` en las cinco reglas, con test por formulario |
| R3 | menor | `resources/views/pacientes/show.blade.php:56` | La edad sale un año corta el día del cumpleaños si la medianoche de esa fecha no existió en la zona (1950-02-12 el 2026-02-12 da 75, comprobado) | Calcular con fechas puras |
| R4 | menor | `tests/Feature/HoraClinicaPanelesTest.php:138` | CA3: el test solo comprueba la víspera, no que la edad suba el día 18 | Comprobar 26 años a las 00:00 del 18 |
| R5 | menor | `app/Agenda/RelojClinica.php:69` | `fechaDe("2026-11-18\n")` lanza una excepción en lugar de dar `null` (comprobado; `TrimStrings` lo evita por HTTP) | Modificador `D` y caso en el test |
| R6 | menor | `tests/Feature/HoraClinicaFormulariosTest.php:181` | El caso de D10 pasaría aunque el guardado no se ejecutara | Cambiar también el diagnóstico y comprobar que se guardó |
| R7 | menor | `tests/Feature/HistorialCitaTest.php:112` | Espera la hora de México sin fijar la zona: depende del entorno | Fijar `clinica.zona_horaria` en el test |
| R8 | menor | `app/Http/Controllers/PersonalController.php:211-212` | Mes y año salen de dos instantes distintos | Un solo `ahora()` |
| R9 | menor | `resources/views/citas/cliente-agendar.blade.php:65` y otros formularios | Un campo enviado como arreglo vuelve a `old()` y da 500 una vez (preexistente) | Mostrar `old()` solo si es texto |
| R10 | menor | `resources/views/expedientes/show.blade.php:152` | D10 solo aplica en el servidor: el `min` del navegador impide reenviar una fecha vencida (sin pérdida de datos) | Dejarlo como está o añadir `novalidate` |
| R11 | menor | `app/Http/Controllers/CitasController.php:100`, `EstadosCita.php:151`, `HorarioDentista.php:66`, `ReprogramacionCitas.php:109`, `ReportesController.php:33`, `ReporteCitas.php:229` | La zona se sigue resolviendo a mano fuera de `RelojClinica` (correcto, pero duplicado) | Unificar en un `/refactor` |
| R12 | menor | `resources/views/expedientes/show.blade.php:165` | `deCita()` se usa para una fecha de calendario y la cuenta de días queda en la vista | Método propio en `RelojClinica` |
| R13 | menor | `tests/Feature/HoraClinicaFormulariosTest.php:116` | La próxima visita del 18 coincide con el valor por defecto (los casos del 20 y de hoy sí distinguen) | Quitar el caso o usar otra fecha |

## Preparación para release
- Rollback factible: sí, `deploy/volver.sh <tag anterior>`, sin migraciones.
- Migraciones compatibles con la versión anterior: no hay migraciones.
- Docs actualizadas (architecture, deployment): sí (T090, T091).

## Tareas añadidas
- T040 ← R1, R2, R6
- T041 ← R1
- T042 ← R2
- T043 ← R3, R4, R5
- T044 ← R3, R5
- T045 ← R7
- T046 ← R8

## Aceptados sin tarea
Aceptados por el usuario el 2026-11-18:
- **R9** → roadmap: un campo enviado como arreglo vuelve a `old()` y da 500 una vez en los formularios (preexistente).
- **R10** → roadmap: la decisión D10 solo aplica en el servidor; el `min` del navegador impide reenviar una fecha vencida.
- **R11** → roadmap: unificar en `RelojClinica` la zona que aún se resuelve a mano en otros servicios.
- **R12** → roadmap: método propio para fechas de calendario y sacar de la vista la cuenta de días de la próxima visita.
- **R13** → roadmap: el caso de la próxima visita del 18 coincide con el valor por defecto.
