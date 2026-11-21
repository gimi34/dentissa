---
spec: 011-perfil-cliente
plan: plan.md
impl_base: ba00cb7
status: approved
---

# Tareas · 011 Perfil y contraseña del paciente

Formato:
`- [ ] T### [P] <verbo + qué> — <archivos> — hecho cuando: <criterio> — cubre: CA# — depende: T###`
- T001–T089: trabajo. T090–T099: reservadas.
- `[P]` = paralelizable con las demás `[P]` de su grupo una vez cumplidas sus dependencias (no
  comparte archivos con otra tarea abierta).
- `[-]` = obsoleta (`— obsoleta: <motivo> (AAAA-MM-DD)`): no cuenta como abierta ni como hecha.
- Bajo una tarea: `  - nota: …`, `  - bloqueo: AAAA-MM-DD — <qué falta> — <skill a ejecutar>`.
- **Alcance:** solo CA6 y CA7 cambian comportamiento. CA1, CA2 y TM5 reciben tests de caracterización; CA3, CA4 y CA5
  ya tienen test (`ContrasenaDistintaTest`, `PerfilCambioCorreoTest`) y solo tienen que seguir en verde (plan,
  Estrategia de pruebas).
- **Un solo archivo de tests nuevo**, `tests/Feature/PerfilClienteTest.php`: las tareas de test lo comparten y van en
  orden, sin `[P]`. Usa el escenario de `EscenarioEstadosCita` (admin, dentistas, asistente y pacientes).
- **Dos sesiones reales (`/analyze` A1, A2):** T014–T016 siguen el patrón de `SesionVigenteTest` (041): la otra
  sesión entra de verdad (`POST /login`, o el retorno de Google con Socialite simulado), se guardan sus datos
  (`session()->all()`) y se cambia de equipo con `flushSession()` y `forgetGuards()`; al volver a ella,
  `forgetGuards()` y `withSession(<sus datos>)`. Sin esto el guard conserva la cuenta en memoria y ningún test de CA6
  distingue nada (plan, Estrategia de pruebas).
- **Línea base (2026-11-20, `impl_base` ba00cb7):** suite completa 1502 en verde (19 463 aserciones), sin fallos previos.
- **Datos de prueba:** contraseñas de ejemplo ficticias y sin espacios al principio ni al final (`TrimStrings`
  recorta `password_actual`, 043/A8).

## Constitution Check
Contra `plan.md` (constitución 1.3.0). Lo que el desglose podría romper:

| Principio | Resultado | Justificación / ajuste | Cómo se verifica |
|---|---|---|---|
| P1 Spec aprobada antes de código | ✅ | Spec y plan aprobados (2026-11-20). Ninguna tarea de código empieza antes de que `tasks.md` esté `approved` y `/analyze` en `pass`. | manual: `aidd.py status` antes de `/implement` |
| P2 Test que falla antes y pasa después | ✅ | T013–T015, de test, van antes que T020 y T021, y cada tarea de implementación depende de su test. Los de caracterización (T010–T012, T016) pasan desde el principio y se comprueban rompiendo un momento el código que protegen, con su nota. | test: T010–T016 |
| P3 Autorización en servidor | ✅ | T020 añade `role:cliente` a `PUT /cliente/perfil`; T013 prueba el denegado (admin, dentista y asistente → 403) y T011 el permitido (cliente). `GET /cliente/perfil` no cambia (plan D3 → spec 015). | test: T013 (`test_el_personal_no_puede_guardar_el_perfil_de_paciente`), T011 (`test_el_paciente_guarda_su_perfil`) |
| P4 Entrada validada y SQL con bindings | ✅ | T021 sube la versión en el mismo `update` del query builder, calculada con la columna, sin `DB::raw` con entrada del usuario. | lint: semgrep `p/php` en T022 |
| P5 Nada sensible en el repo | ✅ | Contraseñas de ejemplo ficticias en T010–T016. | lint: gitleaks en T022 |
| P6 Sin vulnerabilidades críticas/altas | ✅ | Ninguna tarea toca dependencias. | lint: `composer audit --locked` y `node .github/scripts/auditar-npm.mjs` en T022 |
| P7 Auditoría de datos sensibles | ❌ aceptado | Excepción aprobada por el usuario el 2026-11-20 (plan D7 → specs 021 y 022 y el registro de entradas y salidas, que incluye los cierres de sesión forzados por T021, `/analyze` A5): ninguna tarea añade eventos. Lo que sí se audita, el 403 del personal como `acceso_denegado`, lo prueba T013. | test: T013 |
| P8 Correlación y logs limpios | ✅ | Ninguna tarea añade `Log::`. | manual: diff sin `Log::` en T022 |
| P9 La interfaz usa solo el sistema de diseño | ➖ | Ninguna tarea toca vistas, clases ni estilos (plan D4). | — |
| P10 Rutas indexables descubribles | ➖ | Sin rutas nuevas ni cambiadas indexables (plan). | — |

## Preparación
No hace falta: sin dependencias, configuración, interruptor ni migración (`version_sesion` existe desde la 041; plan,
Rollout).

## Tests (antes de implementar)
- [x] T010 Crear `PerfilClienteTest` con el escenario y escribir `test_el_paciente_ve_sus_datos_y_su_dentista` (el paciente abre `/cliente/perfil` y ve su nombre, su correo, sus datos de la ficha y el nombre de su dentista asignado) — tests/Feature/PerfilClienteTest.php — hecho cuando: pasa (caracterización) y quitar un momento el nombre del dentista de `resources/views/cliente/perfil.blade.php` lo hace fallar; se anota la rotura — cubre: CA1
  - nota: comprobado rompiendo: sin el nombre del dentista en `cliente/perfil.blade.php` el test falla («Dr. Diego Primero»); al restaurar, pasa.
- [x] T011 Escribir `test_el_paciente_guarda_su_perfil` (`PUT /cliente/perfil` como paciente actualiza sus datos en `usuarios` y en `pacientes` y redirige con el mensaje de éxito) y `test_guardar_crea_la_ficha_si_no_existia` (un paciente sin fila en `pacientes` guarda y se le crea, enlazada a su cuenta) — tests/Feature/PerfilClienteTest.php — hecho cuando: los dos pasan (caracterización) y quitar un momento el `insert` de la ficha en `PerfilController::update` hace fallar el segundo; se anota la rotura — cubre: CA2 — depende: T010
  - nota: comprobado rompiendo: sin el `insert` de la ficha en `PerfilController::update`, `test_guardar_crea_la_ficha_si_no_existia` falla; al restaurar, pasa. El primero comprueba también que el dentista asignado no cambia.
- [x] T012 Escribir `test_recordarme_no_crea_una_cookie_de_recuerdo` (iniciar sesión con la casilla «Recordarme» marcada no deja ninguna cookie `remember_web_*` en la respuesta y `remember_token` sigue vacío) — tests/Feature/PerfilClienteTest.php — hecho cuando: pasa (caracterización) y pasar un momento `true` como `remember` a `Auth::attempt` en `AuthController` lo hace fallar; se anota la rotura — cubre: CA6 — depende: T011
  - nota: A8 — el test cubre los tres caminos que abren sesión: `POST /login` con «Recordarme» marcado, el registro y el retorno de Google de una cuenta vinculada (Socialite simulado, patrón de `SesionVigenteTest`). En los tres comprueba la redirección de siempre, la sesión abierta y que no hay cookie `remember_web_*`.
  - nota: comprobado rompiendo cada camino por separado, pasando `remember = true` en `AuthController` (`Auth::attempt`), `GoogleAuthController` (`Auth::login`) y `RegisterController` (`Auth::attempt`): el test falla en los tres; al restaurar, pasa.
  - nota: dato del arnés que corrige lo que el plan (D5) y `/analyze` daban por hecho: `usuarios` **no tiene** columna `remember_token` (está solo en la tabla `users`, sin uso). Pedir el recuerdo da un 500 (`Unknown column 'remember_token'`), así que la cookie no se puede emitir y el test falla en la redirección. Las aserciones sobre `remember_token` se quitaron porque no prueban nada. TM5 sigue cumpliéndose (plan D5: el inicio de sesión no emite la cookie).
- [x] T013 Escribir `test_el_personal_no_puede_guardar_el_perfil_de_paciente`: `PUT /cliente/perfil` como admin, dentista y asistente → 403, sin fila nueva en `pacientes`, sin cambios en su fila de `usuarios` y con un evento `acceso_denegado` en la auditoría por cada uno — tests/Feature/PerfilClienteTest.php — hecho cuando: falla por la razón esperada (hoy 302 y se crea la ficha) — cubre: CA7 — depende: T012
  - nota: fallaba por la razón esperada antes de T020: «Expected response status code [403] but received 302». Comprueba también el detalle del evento (`accion_solicitada` = `cliente.perfil.update`). Va en el mismo commit que T020, porque `AGENTS.md` no admite commits con tests en rojo.
- [x] T014 Escribir, con dos sesiones reales (nota de cabecera), `test_cambiar_la_contrasena_cierra_las_otras_sesiones` (la otra sesión entra con `POST /login` y se guardan sus datos; desde otro equipo el paciente entra y cambia su contraseña; al volver a la otra sesión, su siguiente petición va a `/login` y queda sin autenticar; `version_sesion` subió en 1) y `test_crear_la_contrasena_de_una_cuenta_de_google_tambien_cierra_las_otras` (lo mismo con una cuenta sin `password_hash` que entra por el retorno de Google, plan D6) — tests/Feature/PerfilClienteTest.php — hecho cuando: los dos fallan por la razón esperada (hoy la versión no sube y la otra sesión sigue dentro) — cubre: CA6 — depende: T013
  - nota: los dos fallaban antes de T021 por la razón esperada («Failed asserting that 0 is identical to 1»: la versión no sube). Dos sesiones reales con `entrarConContrasena` / `entrarConGoogle`, `otroEquipo()` y `assertSesionCerrada()`, como `SesionVigenteTest`. Van en el mismo commit que T021 (`AGENTS.md`: sin commits en rojo).
- [x] T015 Escribir `test_la_sesion_actual_sigue_abierta_con_otro_identificador`: el paciente entra con `POST /login` y cambia su contraseña enviando la cookie de sesión con un identificador conocido y válido (40 caracteres alfanuméricos); la cookie de sesión de la respuesta trae otro identificador. Después, con `forgetGuards()` y los datos de esa sesión, `/cliente/perfil` responde 200 y sigue autenticada (plan, Estrategia de pruebas, `/analyze` A2) — tests/Feature/PerfilClienteTest.php — hecho cuando: falla por la razón esperada (hoy el identificador de la respuesta es el enviado) — cubre: CA6 — depende: T014
  - nota: si el arnés no permite fijar el identificador por la cookie, se anota aquí y se usa la comprobación equivalente más cercana antes de seguir (plan, Estrategia de pruebas).
  - nota: el arnés sí permite fijar el identificador: `withCookie(config('session.cookie'), <40 caracteres>)` cifra la cookie como el navegador, y `getCookie()` de la respuesta da el identificador nuevo. Antes de T021 fallaba primero en la versión (no subía); sus roturas propias (sin `regenerate()`, sin el `put`) se hacen en T021.
- [x] T016 Escribir `test_un_cambio_rechazado_no_cierra_ninguna_sesion` con los cuatro rechazos del perfil: validación (nueva de menos de 8 caracteres), contraseña actual incorrecta, límite de intentos agotado y la misma contraseña (043). En cada uno, con la otra sesión real abierta (nota de cabecera), `version_sesion` no cambia, la contraseña guardada es la misma y la otra sesión sigue entrando a `/cliente/perfil` — tests/Feature/PerfilClienteTest.php — hecho cuando: pasa hoy (protege el orden de T021; su rotura se hace en T021) — cubre: CA6 — depende: T015
  - nota: A9 — incluye el rechazo sin `password_actual`, con «Ingresa tu contraseña actual.» y la contraseña sin cambiar; comprobado rompiendo: con la regla en `nullable` el test falla (500, «Undefined array key password_actual»); al restaurar, pasa. Los cinco rechazos (nueva corta, sin la actual, actual incorrecta, la misma y límite agotado) dejan la versión en 0, el mismo hash y la otra sesión abierta.

## Implementación
- [x] T020 Añadir `->middleware('role:cliente')` a la ruta `cliente.perfil.update` (`PUT /cliente/perfil`), como ya lleva `cliente.perfil.password` (plan D3) — routes/web.php — hecho cuando: pasa T013, siguen en verde T010 y T011, y quitar el middleware un momento hace fallar T013 (CA7, TM1) — cubre: CA7 — depende: T013
  - nota: comprobado: sin el middleware T013 falla (302 en lugar de 403, la medición previa a T020); con él pasa. Siguen en verde T010, T011, `PerfilCambioCorreoTest`, `ContrasenaDistintaTest`, `CiudadListaCerradaTest` y `HoraClinicaFormulariosTest` (126 tests). Comentario en la ruta, como el de la 043.
- [x] T021 En `updatePassword`, subir `version_sesion` en el mismo `update` que guarda `password_hash` (`version_sesion + 1`, calculado con la columna), leer la versión nueva y después `$request->session()->regenerate()` y `session()->put('version_sesion', <nueva>)`, antes del `back()` de siempre, sin tocar el mensaje (plan D1, D2, D4) — app/Http/Controllers/PerfilController.php — hecho cuando: pasan T014, T015 y T016, siguen en verde `ContrasenaDistintaTest` y `PerfilCambioCorreoTest`, y se comprueba rompiendo un momento cada control, con su test en rojo: subir la versión antes de las comprobaciones (T016, TM4), quitar el `regenerate()` (T015, TM3), quitar el `put` de la versión en la sesión (T015: la sesión actual se cierra al leer de nuevo la cuenta) y quitar la subida (T014, TM2) — cubre: CA6 — depende: T014, T015, T016
  - nota: la versión sube con `increment('version_sesion', 1, [password_hash, actualizado_en])`: una sola escritura, calculada por la columna, y la versión nueva se lee después para guardarla en la sesión.
  - nota: A6 — sin ningún `DB::raw` nuevo (grep del diff del controlador: 0).
  - nota: comprobado rompiendo cada control, con su test en rojo y en verde al restaurar: subir la versión al principio, antes de las comprobaciones (T016 y los demás de CA6 en rojo, TM4); sin `regenerate()` (T015, TM3); sin el `put` de la versión (T015: la sesión actual se cierra al releer la cuenta); sin la subida, con `update` (T014 ×2 y T015, TM2). `PerfilCambioCorreoTest`, `ContrasenaDistintaTest` y `SesionVigenteTest` siguen en verde (45 tests con `PerfilClienteTest`).
  - nota: el commit 6eeb2ec salió sin marcar T014–T016 y T021 en este archivo (fallo del script al anotar); se marcan en el commit siguiente.

- [x] T023 Escribir `test_un_cambio_simultaneo_cierra_tambien_la_sesion_actual`: el paciente cambia su contraseña mientras otro cambio de la misma cuenta sube la versión justo después de la escritura de este (simulado con un `DB::listen` que, una sola vez, tras el `update` de `usuarios` con `password_hash`, sube `version_sesion` en 1). La sesión actual guarda en `version_sesion` la versión que escribió su propio cambio (1) y, al leer de nuevo la cuenta (versión 2), su siguiente petición va a `/login` — tests/Feature/PerfilClienteTest.php — hecho cuando: falla por la razón esperada (hoy la sesión guarda 2 y sigue dentro) — cubre: CA6 — depende: T021
  - nota: añadida por /review: R1
  - nota: R1 — fallaba antes de T024 por la razón esperada («Failed asserting that 2 is identical to 1»: la sesión guardaba la versión del otro cambio). El listener de consultas simula el otro cambio una sola vez, tras el `update` con `password_hash`, en la misma conexión.
- [x] T024 En `updatePassword`, calcular la versión nueva dentro de una transacción, leyendo la de la cuenta con bloqueo (`lockForUpdate()`) y escribiendo esa versión + 1 junto con `password_hash` en un solo `update`, y guardar en la sesión ese mismo valor, sin volver a leerlo; sin `DB::raw` — app/Http/Controllers/PerfilController.php — hecho cuando: pasan T023 y los demás de `PerfilClienteTest`, `ContrasenaDistintaTest` y `SesionVigenteTest`, y volver a leer la versión después de escribir hace fallar T023 — cubre: CA6 — depende: T023
  - nota: añadida por /review: R1
  - nota: R1 — `DB::transaction` con `lockForUpdate()->value('version_sesion') + 1` y un solo `update` con `password_hash`, `version_sesion` y `actualizado_en`; la sesión guarda el valor devuelto. Sin `DB::raw` (A6). Comprobado rompiendo: volver a leer la versión después de escribir hace fallar T023; quitar `version_sesion` del `update` hace fallar T014 ×2, T015 y T023. Siguen en verde `PerfilClienteTest`, `ContrasenaDistintaTest`, `SesionVigenteTest` y `PerfilCambioCorreoTest` (46 tests).
- [x] T025 Escribir `test_la_sesion_antigua_desaparece_del_almacen`: el paciente entra y hace una petición con la cookie de un identificador conocido, para que el almacén de sesiones guarde esa sesión; después cambia su contraseña con la misma cookie. El almacén ya no tiene nada para el identificador conocido (`read()` del manejador de sesiones vacío) y la sesión nueva sigue dentro — tests/Feature/PerfilClienteTest.php — hecho cuando: falla por la razón esperada (hoy la sesión antigua sigue en el almacén) — cubre: CA6 — depende: T024
  - nota: añadida por /review: R2
  - nota: R2 — fallaba antes de T026 por la razón esperada («La sesión antigua ya no está en el almacén»: `read()` del manejador devolvía la sesión). Antes del cambio comprueba que el almacén sí guardó la sesión del identificador conocido, para que la aserción final no pase por accidente.
- [x] T026 Borrar la sesión antigua al renovar el identificador en `updatePassword` (`regenerate(true)`) — app/Http/Controllers/PerfilController.php — hecho cuando: pasan T025 y el resto de `PerfilClienteTest`, quitar el `true` hace fallar T025, y la suite completa y Pint sobre los PHP tocados siguen en verde — cubre: CA6 — depende: T025
  - nota: añadida por /review: R2
  - nota: R2 — `regenerate(true)`. Comprobado rompiendo: sin el `true`, T025 falla; al restaurar, pasa. 2026-11-21: suite completa 1513 en verde (19 633 aserciones; 1511 de la ronda anterior y 2 nuevos), Pint limpio, sin `Log::` ni `DB::raw` nuevos.
## Integración y documentación
- [x] T022 Pasar la verificación final: suite completa, Pint sobre los 3 PHP tocados (`routes/web.php`, `PerfilController.php` y `PerfilClienteTest.php`), gitleaks sobre el diff, semgrep `p/php` sobre los archivos tocados, `composer audit --locked`, `node .github/scripts/auditar-npm.mjs` y revisión de que el diff no tiene `Log::` nuevos — sin archivos — hecho cuando: todo en verde, con `ContrasenaDistintaTest` y `PerfilCambioCorreoTest` (los tests existentes de CA3, CA4 y CA5) en verde, y `composer audit` sin altas ni críticas nuevas respecto a la línea base del plan — cubre: CA3, CA4, CA5 — depende: T020, T021
  - nota: 2026-11-20 — suite completa 1511 en verde (19 605 aserciones; 1502 de la línea base y 9 nuevos), con `ContrasenaDistintaTest` y `PerfilCambioCorreoTest` (CA3, CA4, CA5) en verde; Pint limpio en los 3 PHP; gitleaks (`dir`) sin fugas en los 3 archivos y en `docs/specs/011-perfil-cliente`; semgrep `p/php` 0 hallazgos (con `--no-git-ignore`, porque el worktree no tiene `.git` propio dentro del contenedor); `composer audit --locked` sin altas ni críticas (solo la baja de `laravel/framework` de la línea base); `auditar-npm.mjs` sin avisos.
  - nota: A6 — el diff de `app`, `routes` y `tests` desde `impl_base` no añade ningún `Log::` ni `DB::raw` (grep: 0).
- [x] T090 Actualizar `docs/architecture.md`: el cambio de contraseña desde el perfil también sube `version_sesion` (como el restablecimiento de la 041) y `role:cliente` va también en `PUT /cliente/perfil` (plan, Impacto en arquitectura) — docs/architecture.md — hecho cuando: las dos frases están en su sección y `aidd.py validate` sigue sin errores
  - nota: fila «Perfil» de la tabla de módulos (la versión sube en la misma escritura, `SesionVigente` cierra las otras sesiones y la actual se regenera) y párrafo del grupo `/cliente` (`role:cliente` también al guardar el perfil, spec 011).
- [x] T091 Actualizar `docs/deployment.md` y `docs/observability.md` si cambiaron variables, entornos, pasos de deploy, logs, eventos de auditoría o métricas — sin cambios (plan, Observabilidad y Rollout); `docs/security.md` y `docs/roadmap.md` se actualizan en `/release` (plan, Impacto en arquitectura); se anota en la tarea
  - nota: `docs/deployment.md` y `docs/observability.md` sin cambios: sin variables, pasos de deploy, logs, eventos ni métricas nuevos (la excepción P7 ya consta como pendiente en `docs/observability.md`: entradas y salidas, cambios de pacientes y cambios de contraseña).
  - nota: A7 — documentos que cambian de destino y que `/release` debe conciliar para no dejar ninguna referencia a la 016 ni a la 015 en lo que resuelve la 011: `docs/security.md` (RS2.a, RS2.b, RS9.a y RS9.b, según el plan, Impacto); `docs/roadmap.md` objetivo 2 (añadir la 011: RS2.a en parte, RS2.b y RS9.a) y la excepción de la 051 en Pendientes («`role:cliente` en `PUT /cliente/perfil` → spec 015», ya hecho por la 011); `docs/specs/README.md` objetivo 2 (016 sesiones-seguras deja de incluir el cierre de sesiones al cambiar la contraseña desde el perfil); `docs/specs/014-autorizacion-alcance-clinico/spec.md` Fuera de alcance («Cierre de sesiones al … cambiar credenciales (spec 016)»: el cambio desde el perfil ya lo hace la 011), con una fila de Historial.
- [x] T092 Marcar spec como `implemented`

## Despliegue (lo ejecuta `/release`)
- [ ] T095 Desplegar a staging y verificar criterios de aceptación. No hay staging (objetivo 5): la comprobación se hace en producción, dentro de T097, con una cuenta de paciente de prueba y sin escribir ninguna contraseña en ningún documento:
  - CA6: con dos navegadores abiertos con la misma cuenta de paciente, cambiar la contraseña en uno → el otro vuelve al inicio de sesión en su siguiente clic y el primero sigue dentro
  - CA7: el 403 del personal en `PUT /cliente/perfil` lo prueba T013; si se intenta en producción, aparece en `/admin/auditoria` como `acceso_denegado`
- [ ] T096 Aprobación humana para producción
- [ ] T097 Desplegar a producción y vigilar métricas del plan (Rollout): `deploy/desplegar.sh vX.Y.Z` (sin migraciones), `verificar.sh` y la comprobación de T095; vigilar 5xx en `/cliente/perfil` y `/cliente/perfil/password` (aviso `dentissa-vigilar`). Rollback: `bash deploy/volver.sh <tag anterior>`, sin copia
- [ ] T098 Marcar spec como `released`

## Cobertura
| Criterio | Tarea(s) de test | Tarea(s) de implementación |
|---|---|---|
| CA1 | T010 | ninguna: ya se cumple (caracterización; la rotura de T010 lo prueba) |
| CA2 | T011 | ninguna: ya se cumple (caracterización; la rotura de T011 lo prueba) |
| CA3 | `ContrasenaDistintaTest` (existente), en verde en T021 y T022 | ninguna: ya se cumple (043); T022 confirma que sigue en verde |
| CA4 | `ContrasenaDistintaTest` (existente), en verde en T021 y T022 | ninguna: ya se cumple (043); T022 confirma que sigue en verde |
| CA5 | `PerfilCambioCorreoTest` (existente), en verde en T021 y T022 | ninguna: ya se cumple (041); T022 confirma que sigue en verde |
| CA6 | T012, T014, T015, T016, T023, T025 | T021, T024, T026 |
| CA7 | T013 | T020 |

| Amenaza (TM#) | Tarea(s) de control | Tarea(s) de test |
|---|---|---|
| TM1 | T020 | T013 |
| TM2 | T021, T024 | T014, T023 |
| TM3 | T021, T026 | T015, T025 |
| TM4 | T021 | T016 |
| TM6 | ninguna: fuera de esta spec → spec 018 (plan, Modelo de amenazas; decisión del usuario, 2026-11-20, `/analyze` A4) | ninguna (fuera) |
| TM5 | ninguna: ya se cumple (`Auth::attempt` sin `remember`, plan D5); la rotura de T012 lo prueba | T012 |

| Cambio del plan (módulo) | Tarea(s) |
|---|---|
| routes/web.php | T020 |
| app/Http/Controllers/PerfilController.php | T021, T024, T026 |
| tests/Feature/PerfilClienteTest.php | T010, T011, T012, T013, T014, T015, T016, T023, T025 |

| Requisito no funcional | Tarea(s) de test o verificación manual (T095) |
|---|---|
| Ninguno declarado en la spec | — |
