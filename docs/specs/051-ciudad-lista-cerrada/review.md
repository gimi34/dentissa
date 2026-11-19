---
spec: 051-ciudad-lista-cerrada
verdict: changes_requested
round: 1
date: 2026-11-18
base: 0667c58
head: c364592
human_signoff: no requerido
---

# Review · 051 Ciudad elegida de una lista cerrada

## Resumen
Lo implementado cumple los 12 criterios y la constitución, con las excepciones aceptadas de P3 y P7,
y no tiene hallazgos de seguridad. Un importante lo deja en `changes_requested`: el registro con correo
queda atascado tras un error del paso 2, regresión de D13. El usuario decide corregirlo junto con
cuatro menores baratos.
Conteo: bloqueante 0 · importante 1 · menor 6

## Verificación automática
| Comando | Resultado |
|---|---|
| `php artisan test` (suite completa, base propia `dentissa_051_test` con la migración aplicada, como en la CI) | ✅ 1497 tests, 0 fallos (línea base de T002: 1370, 0 fallos) — ejecutada sobre el código de `57d2de9`; después solo hubo commits de documentación |
| `vendor/bin/pint --test` sobre los 17 PHP tocados | ✅ |
| `python .ai/bin/aidd.py validate docs/specs/051-ciudad-lista-cerrada` | ✅ 0 errores |
| Medición en el navegador (Playwright, 320/390/1440 px) | ✅ 88/88 en `/implement`; no probó reenviar el formulario desde el paso 2 (→ R1) |

## Criterios de aceptación
| CA | Test | Estado | Nota |
|---|---|---|---|
| CA1 | `CiudadListaCerradaTest::test_el_registro_ofrece_la_lista_de_ciudades`, `CiudadesTest` | ✅ | |
| CA2 | `CiudadListaCerradaTest::test_el_perfil_ofrece_la_lista_con_la_ciudad_guardada` | ✅ | |
| CA3 | `CiudadListaCerradaTest::test_el_alta_y_la_edicion_ofrecen_la_lista` | ✅ | |
| CA4 | `test_cada_formulario_guarda_una_ciudad_de_la_lista`, `test_sin_indicar_deja_al_paciente_sin_ciudad` | ✅ | |
| CA5 | `test_una_ciudad_fuera_de_la_lista_se_rechaza_en_cada_formulario`, `test_un_array_en_ciudad_se_rechaza`, `CiudadDeLaListaTest` | ⚠ | Rechaza bien, pero en el registro con correo el formulario no se puede reenviar (R1) |
| CA6 | `test_la_ciudad_antigua_de_otro_paciente_se_rechaza` | ✅ | |
| CA7 | `test_el_filtro_solo_ofrece_ciudades_de_la_lista`, `test_ciudades_distintas_no_cuenta_otro_lugar` | ✅ | |
| CA8 | `CorreccionCiudadesTest::test_la_correccion_arregla_las_que_coinciden`, `..._dos_veces_no_cambia_nada` | ✅ | La migración en sí no tiene test (R5) |
| CA9 | `CorreccionCiudadesTest::test_la_correccion_conserva_las_que_no_coinciden` | ✅ | |
| CA10 | `test_la_ciudad_fuera_de_la_lista_muestra_el_aviso`, `test_guardar_sin_cambiar_conserva_la_ciudad_antigua`, `test_elegir_otra_sustituye_la_ciudad_antigua` | ✅ | |
| CA11 | `test_la_ficha_y_el_directorio_muestran_la_ciudad_guardada` (caracterización) | ✅ | |
| CA12 | `CorreccionCiudadesTest::test_la_correccion_registra_un_evento_con_totales_y_sin_valores` | ✅ | El test toma el último evento de toda la tabla (R7) |

## Conformidad con el plan
- Cambios fuera del plan: ninguno sin tarea (`scope.md`: 25 archivos, todos citados). Añadidos con
  tarea durante `/implement`: `tests/Feature/Concerns/EventosDelTest.php` y cuatro tests de
  `tests/Feature/Auditoria/` (T030, T031); el método `Ciudades::antigua()` (nota de T020) y el toque de
  44 px del registro (nota de T022).
- Partes del plan sin implementar: ninguna. T001 y T002 no tienen diff de código: son el sistema de
  diseño y la puesta al día de la rama.

## Constitution Check (sobre el código)
| Principio | Resultado | Evidencia |
|---|---|---|
| P1 Spec aprobada | ✅ | spec, design, plan y tareas aprobados; `/analyze` r4 en pass |
| P2 Test que falla antes | ✅ | notas de T010–T016; caracterizaciones y controles comprobados rompiéndolos |
| P3 Autorización | ❌ aceptado | excepción del plan (aprobada por el usuario el 2026-11-18); `test_el_asistente_sigue_sin_poder_editar_un_paciente` |
| P4 Validación y bindings | ✅ | `CiudadDeLaLista` en los cinco `validate()`; sin `DB::raw`/`whereRaw` nuevos; semgrep limpio |
| P5 Nada sensible | ✅ | gitleaks sin fugas |
| P6 Sin vulnerabilidades altas | ✅ | composer audit, auditoría npm y trivy sin altas ni críticas |
| P7 Auditoría | ❌ aceptado | excepción del plan para los formularios; la corrección registra `pacientes_ciudades_corregidas` en modo crítico |
| P8 Logs sin datos sensibles | ✅ | sin `Log::` nuevos; el evento solo lleva dos enteros |
| P9 Sistema de diseño | ✅ | bloques 051 solo con tokens (test con control positivo); `system.md` 1.20.0 |
| P10 Rutas indexables | ➖ | `/registro` conserva título, descripción y canónica (`PaginasPublicasTest`) |

## Seguridad
| Herramienta | Resultado |
|---|---|
| gitleaks v8.21.2 (archivos del diff, `--no-git`) | ✅ 0 hallazgos |
| semgrep 1.99.0 `p/php` (PHP del diff) | ✅ 0 hallazgos |
| composer audit + `auditar-npm.mjs` | ✅ crít: 0 · alta: 0 (aviso aparte: EX5 ya se puede retirar) |
| trivy 0.58.1 fs HIGH,CRITICAL | ✅ 0 hallazgos |

| Tema OWASP | Resultado | Evidencia |
|---|---|---|
| A01:2025 Control de acceso | ➖ | rutas y autorización sin cambios (excepción P3) |
| A05:2025 Inyección (SQL y XSS) | ✅ | binding y lista en `?ciudad=`; salida escapada de la ciudad antigua (`test_la_ciudad_antigua_se_muestra_escapada`) |
| A06:2025 Diseño inseguro | ✅ | TM1–TM6 con control y test, comprobados rompiéndolos |
| A08:2025 Integridad de datos | ✅ | corrección y evento en la misma transacción; idempotente |
| A09:2025 Registro y alertas | ✅ | evento crítico sin valores |
| A10:2025 Condiciones excepcionales | ✅ | un array en `ciudad` da error de validación, no 500 |
| Proceso (rutas protegidas, instrucciones a agentes) | ✅ | no se tocan rutas protegidas; nada sospechoso en lo añadido |

## Observabilidad
- Eventos de auditoría de la spec emitidos y probados: sí, `pacientes_ciudades_corregidas` (CA12),
  con test de evento y de transacción con almacén que falla.
- Datos sensibles en logs del código tocado: ninguno.
- `request_id` presente en los logs nuevos: no hay logs nuevos; el evento lleva el que asigna `Auditoria`.

## SEO
| Tema | Resultado | Evidencia |
|---|---|---|
| 3. Indexación y canónica de `/registro` | ✅ | el diff no toca `<title>`, `description`, canónica ni `robots`; `PaginasPublicasTest` en verde |
- Sección "Descubribilidad" del plan aplicada: sí (No aplica: sin rutas nuevas ni metadatos cambiados).

## Hallazgos
| ID | Severidad | Archivo:línea | Hallazgo | Sugerencia |
|---|---|---|---|---|
| R1 | importante | `resources/views/auth/register.blade.php:405-410`; `tests/Feature/CiudadListaCerradaTest.php:187` | Tras un error del paso 2, D13 abre el paso 2, pero la contraseña vuelve vacía y es `required` en el paso 1 oculto: «Crear mi cuenta» no envía nada ni muestra mensaje y la persona queda atascada. El test solo busca `goTo(2);` | Al pulsar «Crear mi cuenta», si el paso 1 no es válido, volver al paso 1 y marcar el campo; test y comprobación en el navegador que lo reenvían |
| R2 | menor | `resources/views/pacientes/index.blade.php:77-78` | La etiqueta «Ciudad» del filtro no está asociada a su desplegable (previo a la 051; igual en «Doctor» y «Buscar») | Asociar `for`/`id` en los filtros del directorio |
| R3 | menor | `resources/views/pacientes/create.blade.php:138-149`; `resources/views/cliente/perfil.blade.php:83-97` | El cálculo del aviso y de `aria-describedby` está copiado en las dos vistas | Moverlo a un parcial |
| R4 | menor | `tests/Feature/CiudadListaCerradaTest.php` (`formulariosSinEsaCiudad`) | Proveedor que solo devuelve `formularios()` | Usar `formularios` |
| R5 | menor | `tests/Feature/CorreccionCiudadesTest.php` | Ningún test ejecuta la migración: si `up()` dejara de llamar al servicio, nada fallaría | Test que ejecute `up()` y compruebe el evento |
| R6 | menor | `resources/views/auth/register.blade.php:~320` | Dos líneas en blanco seguidas donde estaba el bloque de `currentStep` | Dejar una |
| R7 | menor | `tests/Feature/CorreccionCiudadesTest.php:98` | El test del evento toma el último de toda la tabla, que puede ser el de la migración | `EventosDelTest` y exigir un único evento durante el test |

## Preparación para release
- Rollback factible: sí. `deploy/volver.sh <tag anterior>`; la migración no se deshace, pero los datos
  corregidos son válidos para la versión anterior (documentado en `docs/deployment.md`).
- Migraciones compatibles con la versión anterior: sí; solo datos, sin cambio de esquema.
- Docs actualizadas (architecture, deployment): sí (T090, T091), y `observability.md` con el evento.
- Feature flags: no aplica (plan D11).

## Tareas añadidas
- T032 ← R1 (test)
- T033 ← R1, R6
- T034 ← R4, R5, R7

## Aceptados sin tarea
Aceptados por el usuario el 2026-11-18:
- **R2** → roadmap: asociar las etiquetas de los filtros del directorio de pacientes (Buscar, Ciudad, Doctor) con sus campos; deuda previa a la 051.
- **R3** → roadmap: extraer a un parcial el aviso y el `aria-describedby` del campo «Ciudad», hoy copiados en perfil y en alta/edición.
