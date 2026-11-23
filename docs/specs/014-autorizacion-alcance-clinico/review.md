---
spec: 014-autorizacion-alcance-clinico
verdict: changes_requested
round: 2
date: 2026-11-22
base: ee81669d
head: 65fa924d
human_signoff: no requerido
---

# Review · 014 Autorización por alcance clínico

## Resumen
`changes_requested`. Las correcciones de la ronda 1 están bien: R1–R11 y R17 corregidos y probados. Pero la
corrección de CA15 introduce un importante nuevo (R26): si el dentista asignado está desactivado, quien edita la ficha
con el doctor fijo no puede guardarla y no ve el error. Quedan también cuatro menores: el resto de R12 (R27), R28,
R29 y R30. El usuario decide corregir los cinco antes de liberar. R13–R16 y R18–R25 siguen aceptados como deuda (ronda
1, en `history/review.r1.md`).
Conteo (hallazgos nuevos): bloqueante 0 · importante 1 · menor 4

Revisión del diff `ee81669d..65fa924d` (30.0 KB, `review-pack --base ee81669d`) por un único revisor independiente
(Opus) con los encargos A, C+D y S juntos. Quien consolida verificó R26 abriendo el código.

## Verificación automática
| Comando | Resultado |
|---|---|
| `php artisan test` (suite completa) | ✅ 1711 en verde, 20 865 aserciones (ronda 1: 1705) |
| `vendor/bin/pint --test` sobre los 19 PHP tocados | ✅ (T046) |
| `python .ai/bin/aidd.py validate` sin ruta | ✅ 0 errores |
| Build | ➖ no aplica |

## Criterios de aceptación
| CA | Test | Estado | Nota |
|---|---|---|---|
| CA1–CA6, CA14 | los de la ronda 1 | ✅ | sin cambios |
| CA7 | `AlcanceExpedientesTest::test_el_alta_con_una_cita_del_propio_paciente_se_acepta` y los de la ronda 1 | ✅ | R3 corregido |
| CA8 | `AlcancePacientesTest::test_un_dentista_desactivado_no_se_asigna_como_doctor`, `AlcanceVistasTest::test_el_doctor_obligatorio_del_asistente_es_accesible` | ✅ | R4 y R6 corregidos; R30 menor |
| CA9 | `AlcanceCitasTest::test_la_preseleccion_ajena_se_ignora`, `::test_un_paciente_sin_dentista_solo_lo_agenda_el_admin` (dentista y asistente) | ✅ | R2 y R10 corregidos |
| CA10 | `AlcancePacientesTest::test_el_dentista_que_reasigna_fuera_vuelve_al_directorio` (con el nuevo dentista por HTTP) | ✅ | R9 corregido |
| CA11, CA13 | `AlcanceCitasTest::test_agendar_con_un_dentista_ajeno_se_deniega` (`assertDenegado`, dentista y asistente) | ✅ | R1 corregido |
| CA12 | `AlcancePacientesTest::test_sin_auditoria_no_se_muestra_la_ficha` (también la edición) | ✅ | R11 corregido |
| CA15 | `AlcancePacientesTest::test_quien_llega_por_una_cita_no_cambia_el_doctor`, `AlcanceVistasTest::test_el_doctor_es_fijo_para_quien_no_puede_reasignar` | ⚠ | R26 (doctor asignado desactivado), R28, R29 |

## Conformidad con el plan
- Cambios fuera del plan: ninguno (`scope.md`: 0 archivos sin tarea, 0 tareas sin diff).
- Partes del plan sin implementar: ninguna. D21 y TM16 aplicados.

## Constitution Check (sobre el código)
| Principio | Resultado | Evidencia |
|---|---|---|
| P3 | ✅ | `reasignar-paciente`; fuera del alcance sigue el 404 de `editar-paciente`, que va antes |
| P4 | ✅ | Sin `DB::raw` en `app/Autorizacion/` ni en el código nuevo (el nombre del doctor se concatena en PHP) |
| P7 | ✅ | El 403 de CA13 y el de CA15 se registran sobre el paciente |
| P9 | ⚠ | Sin `style=""` nuevos; R27: `.autor-expediente` sigue sin regla |
| Resto | ✅ / ➖ | Sin cambios respecto a la ronda 1 |

## Seguridad
| Herramienta | Resultado |
|---|---|
| gitleaks (diff desde `impl_base`) | ✅ 0 hallazgos (T046) |
| semgrep `p/php` | ✅ 0 hallazgos (T046) |
| `composer audit --locked` | crít: 0 · alta: 0 · baja: 1 (R25, ronda 1) |
| `auditar-npm.mjs` | ✅ |

| Tema OWASP (2025) | Resultado | Evidencia |
|---|---|---|
| A01 Control de acceso | ✅ | CA15: la denegación va antes de validar y de guardar; un array o `''` cuentan como cambio o como nulo; el admin y el asignado ven el `select` |
| A09 Auditoría | ✅ | R1 corregido (`assertDenegado` sobre el paciente) |

## Observabilidad
- Eventos de auditoría emitidos y probados: sí, también los 403 nuevos de CA13 y CA15 sobre el paciente.
- Datos sensibles en logs: ninguno (0 `Log::` nuevos en `app/`, `resources/` y `routes/`).

## Hallazgos
| ID | Severidad | Archivo:línea | Hallazgo | Sugerencia |
|---|---|---|---|---|
| R26 | importante | app/Http/Controllers/PacientesController.php:401; resources/views/pacientes/create.blade.php (rama del doctor fijo) | Si el dentista asignado está desactivado, quien tiene al paciente solo por una cita reenvía su id en el campo oculto: no hay cambio, así que no pasa por el Gate, pero la regla de dentista activo lo rechaza. La rama fija no muestra el error de `dentista_id`: la ficha no se guarda y no dice por qué | Validar `dentista_id` contra los dentistas activos solo cuando cambia (el actual se acepta, como `CiudadDeLaLista($paciente->ciudad)`), con un test del asignado desactivado |
| R27 | menor | resources/views/expedientes/create.blade.php:45 | Resto de R12: `<p class="autor-expediente">` sigue sin regla en `public/css`; solo sirve de gancho de test | Regla con tokens para `.autor-expediente`, comprobada en el test de T042 |
| R28 | menor | tests/Feature/AlcancePacientesTest.php:197-201 | En el bucle `[$this->d1, null]`, el `assertDenegado` de la vuelta con `null` coincide con el evento de la primera: no prueba que quitar el doctor deje su propio registro | Contar los `acceso_denegado` en cada vuelta |
| R29 | menor | tests/Feature/AlcancePacientesTest.php (CA15) | Sin caso de CA15 con un paciente sin doctor del alcance por una cita: que se guarde sin cambiar el doctor y que asignarse a sí mismo sea 403 (rama `$asignado !== null`) | Añadir los dos casos |
| R30 | menor | app/Http/Controllers/PacientesController.php (`DOCTOR_DEL_ASISTENTE`); resources/views/pacientes/create.blade.php | Con un error en el alta del asistente, `aria-describedby="error-dentista_id ayuda-dentista_id"` lee dos veces «Asigna uno de tus dentistas.»: el mensaje de error y la ayuda son el mismo texto | Un mensaje de error distinto de la ayuda |

## Preparación para release
- Rollback factible: sí, sin migraciones ni flags (sin cambios respecto a la ronda 1).
- Docs actualizadas: `docs/architecture.md` con `reasignar-paciente` (T046); `docs/deployment.md` sin cambios.
- Cobertura de riesgos: RS1.a–RS1.e implementadas y probadas, con CA15 en RS1.a; R26 afecta a la edición de la ficha.

## Tareas añadidas
- T050, T051 ← R26
- T052 ← R27
- T053 ← R28, R29
- T054 ← R30
- T055 ← repetir la verificación final

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
