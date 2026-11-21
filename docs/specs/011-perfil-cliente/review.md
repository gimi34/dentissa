---
spec: 011-perfil-cliente
verdict: changes_requested
round: 1
date: 2026-11-21
base: ba00cb7
head: 6f07615
human_signoff: no requerido
---

# Review · 011 Perfil y contraseña del paciente

## Resumen
`changes_requested`: sin bloqueantes ni importantes; los siete criterios se cumplen con tests que fallan al romper lo que
protegen. El usuario decide corregir antes de liberar dos menores de defensa en profundidad del cambio de contraseña (R1,
la versión que guarda la sesión con dos cambios simultáneos; R2, la sesión antigua que queda en el almacén). R3–R9 se
aceptan como deuda.
Revisores independientes: C+D (`sonnet`) y S (`opus`). El revisor A (`opus`) se cortó por el límite de uso del modelo
antes de devolver nada; su encargo (criterio por criterio) lo hizo la sesión principal releyendo desde cero
`PerfilClienteTest`, el controlador, `SesionVigente` y los tests existentes de CA3–CA5, sin hallazgos propios.
Conteo: bloqueante 0 · importante 0 · menor 9

## Verificación automática
| Comando | Resultado |
|---|---|
| `php artisan test` (suite completa, contenedor efímero) | ✅ 1511 en verde, 19 605 aserciones (línea base: 1502, sin fallos) |
| `vendor/bin/pint --test` sobre los 3 PHP tocados | ✅ |
| `npm run build` | ➖ sin cambios de assets |
| `aidd.py validate docs/specs/011-perfil-cliente` | ✅ 0 errores |

## Criterios de aceptación
| CA | Test | Estado | Nota |
|---|---|---|---|
| CA1 | `PerfilClienteTest::test_el_paciente_ve_sus_datos_y_su_dentista` | ✅ | caracterización; falla sin el nombre del dentista en la vista |
| CA2 | `PerfilClienteTest::test_el_paciente_guarda_su_perfil`, `…_guardar_crea_la_ficha_si_no_existia` | ✅ | caracterización; el segundo falla sin el `insert` de la ficha |
| CA3 | `ContrasenaDistintaTest` (existentes); `PerfilClienteTest::test_un_cambio_rechazado_no_cierra_ninguna_sesion` (sin la actual, A9) | ✅ | |
| CA4 | `ContrasenaDistintaTest::test_una_cuenta_sin_contrasena_crea_la_suya_en_el_perfil` | ✅ | |
| CA5 | `PerfilCambioCorreoTest` | ✅ | 041 |
| CA6 | `PerfilClienteTest::test_cambiar_la_contrasena_cierra_las_otras_sesiones`, `…_crear_la_contrasena_de_una_cuenta_de_google_tambien_cierra_las_otras`, `…_la_sesion_actual_sigue_abierta_con_otro_identificador`, `…_un_cambio_rechazado_no_cierra_ninguna_sesion`, `…_recordarme_no_crea_una_cookie_de_recuerdo` | ✅ | dos sesiones reales; cada control comprobado rompiéndolo (T021) |
| CA7 | `PerfilClienteTest::test_el_personal_no_puede_guardar_el_perfil_de_paciente` | ✅ | admin, dentista y asistente; 403, sin ficha, cuenta intacta y `acceso_denegado` |

## Conformidad con el plan
- Cambios fuera del plan: ninguno (`scope.md`: 3 archivos de código, todos con tarea).
- Partes del plan sin implementar: ninguna. Desviación registrada: `usuarios` no tiene `remember_token` (nota de T012); D5 se
  sigue cumpliendo.

## Constitution Check (sobre el código)
| Principio | Resultado | Evidencia |
|---|---|---|
| P1 Spec aprobada | ✅ | spec, plan y tareas aprobados; `/analyze` r2 pass |
| P2 Test antes | ✅ | CA6 y CA7 fallaban antes (notas de T013–T015); caracterizaciones comprobadas rompiendo (T010–T012, T016) |
| P3 Autorización en servidor | ✅ | `routes/web.php:214` `role:cliente`; test del denegado y del permitido |
| P4 Entrada y SQL | ✅ | `increment` del query builder con bindings; sin `DB::raw` nuevo; semgrep 0 |
| P5 Nada sensible | ✅ | contraseñas de ejemplo; gitleaks sin fugas |
| P6 Vulnerabilidades | ✅ | sin cambios de dependencias; SCA abajo |
| P7 Auditoría | ❌ aceptado | excepción del usuario (plan D7): sin evento por el cambio de contraseña, el guardado del perfil ni los cierres forzados; el 403 sí se audita (test de CA7). El código no va más allá |
| P8 Logs | ✅ | sin `Log::` nuevos |
| P9 Sistema de diseño | ➖ | sin cambios de vista |
| P10 Rutas indexables | ➖ | sin rutas públicas |

## Seguridad
| Herramienta | Resultado |
|---|---|
| gitleaks v8.21.2 (`dir` sobre los 3 archivos y la carpeta de la spec) | ✅ 0 hallazgos |
| semgrep 1.99.0 `p/php` (`--no-git-ignore`) | ✅ 0 hallazgos |
| `composer audit --locked` + `auditar-npm.mjs` | crít: 0 · alta: 0 · media: 0 (1 baja preexistente de `laravel/framework`, ignorada por gravedad) |
| trivy 0.58.1 `fs --severity HIGH,CRITICAL` | ✅ 0 hallazgos |

| Tema OWASP | Resultado | Evidencia |
|---|---|---|
| A01:2025 Control de acceso (TM1) | ✅ | `role:cliente` en `PUT /cliente/perfil`; `abort(403)` genérico sin datos de otra cuenta |
| A07:2025 Autenticación y sesiones (TM2) | ✅ | la versión sube en la misma escritura que el hash; `SesionVigente` (grupo `web`, antes del controlador) cierra las sesiones con versión menor |
| A07:2025 Fijación de sesión (TM3) | ✅ con matiz (R2) | `regenerate()` y `put` de la versión nueva; la sesión antigua queda en el almacén pero inservible por la versión |
| A07:2025 Rechazo sin efecto (TM4) | ✅ | el `increment` va después de todas las comprobaciones; cinco rechazos probados |
| A07:2025 «Recordarme» (TM5) | ✅ | ningún camino pide `remember`; test sobre los tres |
| TM6 | ➖ | fuera → spec 018 (decisión del usuario) |
| Concurrencia del cambio de contraseña | ❌ menor (R1) | `increment` y lectura separados |
| A03/A05 Validación y SQL | ✅ | sin entradas nuevas; bindings |
| A09 Registro y alertas | ➖ | excepción P7; sin datos sensibles en logs |
| Proceso | ✅ | sin rutas protegidas tocadas sin tarea; sin instrucciones sospechosas |

## Observabilidad
- Eventos de auditoría de la spec emitidos y probados: no hay eventos nuevos (excepción P7). El `acceso_denegado` del 403
  del personal lo emite el registro central de la 020 y lo prueba `test_el_personal_no_puede_guardar_el_perfil_de_paciente`.
- Datos sensibles en logs del código tocado: ninguno (sin `Log::` nuevos).
- `request_id` presente en los logs nuevos: no hay logs nuevos.

## Hallazgos
| ID | Severidad | Archivo:línea | Hallazgo | Sugerencia |
|---|---|---|---|---|
| R1 | menor | `app/Http/Controllers/PerfilController.php:129-133` | El `increment` y la lectura de la versión son dos sentencias sin transacción. Si otro cambio de la misma cuenta llega entre ellas (otro cambio desde el perfil o un restablecimiento), la sesión guarda la versión ajena y sobrevive a un cambio que debía cerrarla. Ventana de milisegundos. | La sesión guarda exactamente la versión que escribió su propio cambio, y un cambio concurrente la cierra en su siguiente petición (test que simule un cambio entre la escritura y la lectura). Corregir: T023, T024. |
| R2 | menor | `app/Http/Controllers/PerfilController.php:136` | `regenerate()` sin borrar la sesión antigua: con el driver `file` su fichero queda en disco hasta la limpieza, con la versión anterior. No se puede reutilizar (lo cierra `SesionVigente`), pero TM3 depende del control de TM2. | La sesión antigua desaparece del almacén al cambiar la contraseña (test con el identificador conocido). Corregir: T025, T026. |
| R3 | menor | `tests/Feature/PerfilClienteTest.php:202-222` | El test de TM3 no comprueba directamente que el identificador antiguo deje de valer; lo cubre `…_cierra_las_otras_sesiones`, que reproduce sus datos. | Aceptado (R2 lo refuerza). |
| R4 | menor | `app/Http/Controllers/PerfilController.php:129-139` | Un doble envío del formulario puede dejar fuera al propio paciente: la petición duplicada la cierra `SesionVigente` y su cookie puede pisar la buena. No es una vulnerabilidad. | Aceptado: desactivar el botón al enviar, si molesta. |
| R5 | menor | `app/Http/Controllers/PerfilController.php:129-139` | El cambio desde el perfil no avisa por correo al titular ni deja evento, a diferencia del restablecimiento (041). | Aceptado: excepción P7; proponer el aviso y el evento en la 016, la 018 o la 021. |
| R6 | menor | `tests/Feature/PerfilClienteTest.php:149-151` | `assertForbidden()` dentro del bucle de roles sin mensaje: si falla no se ve qué rol. | Aceptado: mensaje con el rol o un data provider. |
| R7 | menor | `tests/Feature/PerfilClienteTest.php:111-139` | El test de «Recordarme» mezcla tres caminos con estado compartido; un fallo en el primero oculta los otros. | Aceptado: separar en tres tests o un data provider. |
| R8 | menor | `tests/Feature/PerfilClienteTest.php:251-253` | El límite de 5 intentos está escrito a mano en el test, acoplado a `errorDeContrasenaActual`. | Aceptado: comentario o constante compartida. |
| R9 | menor | `tests/Feature/PerfilClienteTest.php:263-265` | Las constantes `ACTUAL` y `NUEVA` están en mitad de la clase. | Aceptado: moverlas junto a `PERFIL`. |

## Preparación para release
- Rollback factible: sí, `bash deploy/volver.sh <tag anterior>` sin copia (sin migraciones; las versiones subidas siguen
  siendo respetadas por la versión anterior).
- Migraciones compatibles con la versión anterior: no hay migraciones.
- Feature flags: el plan no pide ninguno.
- Docs actualizadas: `docs/architecture.md` (T090); `docs/deployment.md` y `docs/observability.md` sin cambios (T091).
  `docs/security.md` (RS2.a en parte, RS2.b, RS9.a, RS9.b), el roadmap, el índice de specs y la 014 se concilian en
  `/release` (nota A7 de T091).
- Cobertura de riesgos: RS2.b (CA7) y RS9.a (CA6) implementadas y probadas; RS2.a en parte. Ningún documento las da por
  mitigadas todavía.

## Tareas añadidas
- T023, T024 ← R1
- T025, T026 ← R2

## Aceptados sin tarea
Aceptados por el usuario el 2026-11-21:
- **R3** → roadmap: el test de TM3 no comprueba directamente que el identificador antiguo deje de valer (lo cubre el de las otras sesiones y R2)
- **R4** → roadmap: un doble envío del cambio de contraseña puede dejar fuera al propio paciente
- **R5** → roadmap: el cambio de contraseña desde el perfil no avisa por correo ni deja evento (excepción P7 → 016, 018 o 021)
- **R6** → roadmap: `assertForbidden()` sin el rol en el mensaje en el test de CA7
- **R7** → roadmap: separar los tres caminos del test de «Recordarme»
- **R8** → roadmap: el límite de 5 intentos escrito a mano en el test de rechazos
- **R9** → roadmap: constantes del test en mitad de la clase
