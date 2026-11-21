---
spec: 011-perfil-cliente
status: approved
constitution_version: 1.3.0
created: 2026-11-20
---

# Plan · 011 Perfil y contraseña del paciente

## Enfoque técnico
La spec ya describe el perfil tal como funciona. Solo quedan dos criterios abiertos, CA6 y CA7, y los dos se resuelven
en el perfil, con lo que ya existe.

- **CA6:** cambiar la contraseña desde el perfil sube `usuarios.version_sesion` en la misma escritura que guarda la
  contraseña nueva. Es el mecanismo que la 041 creó para el restablecimiento: el middleware `SesionVigente` cierra en
  su siguiente petición toda sesión de la cuenta con una versión menor. La sesión desde la que se hace el cambio
  sigue abierta: se renueva su identificador y guarda la versión nueva (D1, D2).
- **CA7:** `PUT /cliente/perfil` pasa a exigir `role:cliente`, como hizo la 043 con `PUT /cliente/perfil/password`.
  El personal recibe un 403, que el registro central de la 020 ya audita como `acceso_denegado`, y no se le crea
  ninguna ficha de paciente (D3).

CA1–CA4 ya se cumplen; los que no tienen test lo reciben ahora, de caracterización (P2). CA5 ya está cubierto por la 041.
No hay migraciones, dependencias, rutas nuevas ni cambios de pantalla: `version_sesion` existe desde la 041.

## Constitution Check
| Principio | Resultado | Justificación / ajuste | Cómo se verifica |
|---|---|---|---|
| P1 Spec aprobada antes de código | ✅ | Spec 011 aprobada por el usuario el 2026-11-20 (`/specify --edit`, tras validarla). No tiene sección «Diseño» ni pantallas nuevas, así que no necesita `design.md`. | manual: `aidd.py status` antes de `/implement` |
| P2 Test que falla antes y pasa después | ✅ | CA6 y CA7 cambian comportamiento: sus tests fallan hoy (las otras sesiones siguen abiertas; el personal guarda el perfil y se le crea una ficha). CA1, CA2 y TM5 describen lo que ya pasa: tests de caracterización, cada uno comprobado rompiendo el código que protege (Estrategia de pruebas). CA3, CA4 y CA5 ya tienen test. | test: ver Trazabilidad |
| P3 Autorización en servidor | ✅ | Los dos endpoints que cambian verifican rol y alcance. `PUT /cliente/perfil` pasa a `auth` + `role:cliente` (D3); `PUT /cliente/perfil/password` ya los tiene (043). En los dos el alcance es la cuenta de la sesión: no reciben ningún identificador de cuenta ni de paciente. `GET /cliente/perfil` no cambia y sigue solo con `auth` (decisión del usuario, 2026-11-20; → spec 015). | test: `PerfilClienteTest::test_el_personal_no_puede_guardar_el_perfil_de_paciente` (admin, dentista y asistente → 403), `…_el_paciente_guarda_su_perfil` (cliente permitido); `ContrasenaDistintaTest::test_el_personal_no_puede_cambiar_la_contrasena_por_el_perfil` (ya existe) |
| P4 Entrada validada y SQL con bindings | ✅ | Ninguna entrada nueva. La versión nueva se calcula en el servidor con la columna (`version_sesion + 1`) en el mismo `update` con bindings, sin `DB::raw` con entrada del usuario (D2). | lint: semgrep `p/php` sobre los archivos tocados |
| P5 Nada sensible en el repo | ✅ | Los tests usan contraseñas de ejemplo ficticias, como los de la 041 y la 043. | lint: gitleaks sobre el diff |
| P6 Sin vulnerabilidades críticas/altas | ✅ | Sin cambios en `composer.json`, `composer.lock` ni los archivos de npm. Línea base del 2026-11-20: `composer audit --locked`, 0 altas o críticas (1 baja de `laravel/framework`, ignorada por gravedad); `npm audit`, 0 vulnerabilidades (ficha `2026-11-20-chore-retirar-ex5`). | lint: `composer audit --locked` y `auditar-npm.mjs` en CI y `/release` |
| P7 Auditoría de datos sensibles | ❌ aceptado: el cambio de contraseña desde el perfil, el guardado del perfil y los cierres de sesión forzados por CA6 siguen sin evento de auditoría. La spec no tiene criterios de «Auditoría» y añadirlos queda para la 021 (cambios de contraseña), la 022 (cambios de pacientes) y el registro de entradas y salidas, según `docs/observability.md`; los cierres forzados de la recuperación (041) tampoco dejan evento — aprobado por el usuario el 2026-11-20 (cierres forzados: `/analyze` A5) | Lo que sí queda auditado: el 403 del personal en `PUT /cliente/perfil` lo registra el registro central de denegaciones de la 020 como `acceso_denegado`, sin código nuevo. | test: `PerfilClienteTest::test_el_personal_no_puede_guardar_el_perfil_de_paciente` comprueba el evento `acceso_denegado` |
| P8 Correlación y logs limpios | ✅ | No añade ningún `Log::`. La sesión renovada conserva el `request_id` de la petición (middleware de la 020). | manual: diff sin `Log::` |
| P9 La interfaz usa solo el sistema de diseño | ➖ | Ningún cambio de marcado, clase ni estilo: el mensaje de éxito y el formulario siguen igual (D4). | — |
| P10 Rutas indexables descubribles | ➖ | No crea ni cambia rutas indexables: el perfil exige sesión. | — |

## Cambios por módulo
| Módulo | Cambio | Riesgo |
|---|---|---|
| `routes/web.php` | `->middleware('role:cliente')` en la ruta `cliente.perfil.update` (`PUT /cliente/perfil`), como ya lleva `cliente.perfil.password` (D3) | Bajo: el personal no tiene enlaces a esa pantalla; recibe 403 en lugar de guardar |
| `app/Http/Controllers/PerfilController.php` | `updatePassword`: el `update` que guarda `password_hash` sube también `version_sesion` (`version_sesion + 1`); después, `$request->session()->regenerate()` y `session()->put('version_sesion', <nueva>)`, antes del `back()` de siempre (D1, D2) | Medio: si la sesión actual no guarda la versión nueva, `SesionVigente` la cerraría también (lo prueba CA6) |
| `tests/Feature/PerfilClienteTest.php` (nuevo) | Tests de CA1, CA2, CA6, CA7 y TM2–TM5 | — |

## Contratos y datos
- **Sin rutas nuevas, migraciones ni datos nuevos.** `usuarios.version_sesion` existe desde la 041
  (`version_sesion_en_usuarios`, entero con valor por defecto 0).
- **`PUT /cliente/perfil`:** middleware `auth` + `role:cliente`. Para el personal (admin, dentista, asistente) pasa de
  guardar y redirigir con éxito a **403**, sin escribir en `usuarios` ni en `pacientes`. Para el paciente, igual que hoy.
- **`PUT /cliente/perfil/password` con éxito:** la respuesta no cambia (`back()` con «Tu contraseña fue actualizada.»).
  Además, `usuarios.version_sesion` sube en 1 y la sesión actual cambia de identificador y guarda la versión nueva.
  Con error (validación, contraseña actual incorrecta, límite agotado o la misma contraseña) no cambia nada de esto.
- **Otras sesiones de la cuenta:** en su siguiente petición, `SesionVigente` las cierra y redirige a `/login`, como
  tras un restablecimiento (041).
- **Pantallas y estados:** ninguna pantalla nueva ni modificada.

| Qué ve cada rol en `PUT /cliente/perfil` | Hoy | Después |
|---|---|---|
| Paciente (`cliente`) | guarda su perfil | igual |
| Admin, dentista, asistente | guarda y se le crea una ficha de paciente | 403, sin cambios en la base |
| Sin sesión | redirige a `/login` | igual |

## Estrategia de pruebas
PHPUnit Feature con `DatabaseTransactions` contra la base de tests, en `tests/Feature/PerfilClienteTest.php`, con el
escenario de `EscenarioEstadosCita` (admin, dentistas, asistente y pacientes).

- **Dos sesiones reales (CA6, `/analyze` A1):** en los tests el guard y el almacén de sesión son compartidos, y el
  guard conserva en memoria la cuenta con la versión antigua; poner solo la versión en la sesión no simula otra
  sesión. Se sigue el patrón de `SesionVigenteTest` (041): la «otra» sesión entra de verdad (`POST /login`, o el
  retorno de Google con Socialite simulado para la cuenta sin contraseña) y se guardan sus datos (`session()->all()`);
  se cambia a otro equipo (`flushSession()` y `forgetGuards()`), la sesión «actual» entra y cambia la contraseña; al
  volver a la otra (`forgetGuards()` y `withSession(<sus datos>)`), su siguiente petición redirige a `/login` y queda
  sin autenticar. Falla hoy porque la versión no sube, y falla al quitar la subida.
- **La sesión actual (CA6, TM3, `/analyze` A1 y A2):** sin la cookie de sesión, el arnés asigna un identificador nuevo
  en cada petición, así que comparar identificadores entre peticiones no distingue nada. El cambio de contraseña se
  envía con la cookie de sesión de un identificador conocido y válido (40 caracteres alfanuméricos) y se comprueba
  que la cookie de la respuesta trae otro: en rojo sin `regenerate()`. Después, con `forgetGuards()` (la cuenta se
  lee de nuevo, como en un servidor real) y los datos de esa sesión, `/cliente/perfil` responde 200: en rojo si la
  sesión no guarda la versión nueva. Si el arnés no permite fijar el identificador así, `/implement` lo anota y
  busca la comprobación equivalente más cercana antes de seguir.
- **Rechazos (TM4):** los cuatro rechazos con la otra sesión real abierta: tras cada uno, `version_sesion` no cambia y
  la otra sesión sigue entrando.
- **Personal (CA7):** `PUT /cliente/perfil` como admin, dentista y asistente → 403, sin fila nueva en `pacientes` ni
  cambios en `usuarios`, y un `acceso_denegado` en la auditoría. Falla hoy (302 y ficha creada).
- **Caracterización (P2):** CA1 (la página muestra los datos y el dentista asignado) y CA2 (guardar actualiza
  `usuarios` y `pacientes`, y crea la ficha si el paciente no la tenía) pasan desde el principio. Cada una se comprueba
  rompiendo un momento el código que protege (p. ej. quitar el `insert` de la ficha o el nombre del dentista de la
  vista) y se anota en la tarea. Lo mismo con TM5.
- **Ya cubiertos:** CA3 (`ContrasenaDistintaTest`: sin la actual correcta y reglas de siempre), CA4
  (`…_una_cuenta_sin_contrasena_crea_la_suya_en_el_perfil`) y CA5 (`PerfilCambioCorreoTest`). Siguen en verde.
- Suite completa al terminar, Pint sobre lo tocado, gitleaks y semgrep `p/php` sobre el diff.

## Modelo de amenazas
| ID | Amenaza (STRIDE) | Categoría OWASP | Componente | Control | Test |
|---|---|---|---|---|---|
| TM1 | Elevation of privilege: alguien del personal guarda el perfil de paciente y se le crea una ficha de paciente con su cuenta | A01:2025 Control de acceso | `PUT /cliente/perfil` | `role:cliente` en la ruta (D3); el 403 se audita en el registro central de la 020 | `test_el_personal_no_puede_guardar_el_perfil_de_paciente` |
| TM2 | Spoofing: quien tiene una sesión robada sigue dentro después de que el paciente cambia su contraseña | A07:2025 Fallos de autenticación | `PUT /cliente/perfil/password`, `SesionVigente` | El cambio sube `version_sesion`; `SesionVigente` cierra las sesiones con una versión menor (D1) | `test_cambiar_la_contrasena_cierra_las_otras_sesiones` |
| TM3 | Spoofing (fijación de sesión): el identificador de la sesión actual, si alguien lo conocía, sigue valiendo tras el cambio | A07:2025 Fallos de autenticación | `PerfilController::updatePassword` | `session()->regenerate()` tras guardar (D2) | `test_la_sesion_actual_sigue_abierta_con_otro_identificador` |
| TM4 | Denial of service: un cambio rechazado cierra igualmente las sesiones de la cuenta | A07:2025 Fallos de autenticación | `PerfilController::updatePassword` | La versión sube solo en la misma escritura que guarda la contraseña nueva, después de todas las comprobaciones (D1) | `test_un_cambio_rechazado_no_cierra_ninguna_sesion` |
| TM6 | Spoofing / denegación de servicio: quien tiene una sesión robada de una cuenta que entra solo con Google crea una contraseña sin dar la actual (CA4) y, por D6, cierra las sesiones del dueño | A07:2025 Fallos de autenticación | `PUT /cliente/perfil/password` sin contraseña previa | **Fuera de esta spec** → spec 018 (vinculación segura de las cuentas de Google), decisión del usuario del 2026-11-20 (`/analyze` A4). El dueño recupera la cuenta con el restablecimiento por correo (041), que cierra también la sesión del intruso | — (fuera) |
| TM5 | Spoofing: una cookie de «Recordarme» vuelve a abrir una sesión cerrada, ya con la versión nueva | A07:2025 Fallos de autenticación | inicio de sesión | El inicio de sesión no emite cookie de recuerdo: `Auth::attempt` se llama sin `remember`, aunque la casilla exista (D5) | `test_recordarme_no_crea_una_cookie_de_recuerdo` (caracterización) |

## Trazabilidad
| Criterio de aceptación | Cambio(s) | Test(s) |
|---|---|---|
| CA1: el paciente ve sus datos y su dentista asignado | ninguno (ya se cumple) | `PerfilClienteTest::test_el_paciente_ve_sus_datos_y_su_dentista` (caracterización) |
| CA2: guardar actualiza `usuarios` y `pacientes` y crea la ficha si no existe | ninguno (ya se cumple) | `PerfilClienteTest::test_el_paciente_guarda_su_perfil`, `…_guardar_crea_la_ficha_si_no_existia` (caracterización) |
| CA3: con contraseña, cambiarla pide la actual; la nueva, mínimo 8 | ninguno (ya se cumple) | `ContrasenaDistintaTest::test_perfil_sin_la_actual_correcta_no_revela_si_es_igual`, `…_los_errores_de_siempre_van_antes_en_el_perfil` (existentes) |
| CA4: sin contraseña (Google), establecerla no pide la actual | ninguno (ya se cumple) | `ContrasenaDistintaTest::test_una_cuenta_sin_contrasena_crea_la_suya_en_el_perfil` (existente) |
| CA5 (abuso): cambiar el correo pide la contraseña actual | ninguno (041) | `PerfilCambioCorreoTest` (existente) |
| CA6 (abuso): cambiar la contraseña cierra las otras sesiones | `PerfilController::updatePassword` | `PerfilClienteTest::test_cambiar_la_contrasena_cierra_las_otras_sesiones`, `…_la_sesion_actual_sigue_abierta_con_otro_identificador`, `…_crear_la_contrasena_de_una_cuenta_de_google_tambien_cierra_las_otras`, `…_un_cambio_rechazado_no_cierra_ninguna_sesion` |
| CA7 (abuso): el personal no puede guardar el perfil de paciente | `routes/web.php` | `PerfilClienteTest::test_el_personal_no_puede_guardar_el_perfil_de_paciente` (admin, dentista, asistente) |
| TM1 | `routes/web.php` | `test_el_personal_no_puede_guardar_el_perfil_de_paciente` |
| TM2 | `PerfilController::updatePassword` | `test_cambiar_la_contrasena_cierra_las_otras_sesiones` |
| TM3 | `PerfilController::updatePassword` | `test_la_sesion_actual_sigue_abierta_con_otro_identificador` |
| TM4 | `PerfilController::updatePassword` | `test_un_cambio_rechazado_no_cierra_ninguna_sesion` |
| TM6 | fuera → spec 018 | — |
| TM5 | ninguno (ya se cumple) | `test_recordarme_no_crea_una_cookie_de_recuerdo` (caracterización) |

## Observabilidad
- Logs nuevos: ninguno.
- Eventos de auditoría: ninguno nuevo (excepción P7, decisión del usuario del 2026-11-20). El 403 del personal en
  `PUT /cliente/perfil` queda como `acceso_denegado` por el registro central de denegaciones de la 020 (test de CA7).
  Una sesión cerrada por `SesionVigente` no deja evento, igual que tras un restablecimiento: entra en la excepción P7
  (`/analyze` A5).
- Métricas y alertas: sin cambios. Los 403 nuevos no cuentan como fallos de acceso del canal `seguridad`.
- Cómo se verifica tras el deploy: con dos navegadores abiertos con la misma cuenta de paciente de prueba, cambiar la
  contraseña en uno y comprobar que el otro vuelve al inicio de sesión en su siguiente clic, y que el primero sigue
  dentro. Con una cuenta del personal, `PUT /cliente/perfil` da 403 y aparece en `/admin/auditoria` como
  `acceso_denegado`.

## Descubribilidad
No aplica: la spec no crea ni cambia rutas públicas; el perfil exige sesión y no es indexable (`docs/seo.md`).

## Rollout
- Feature flag: no aplica. Son dos controles de seguridad pequeños sobre rutas existentes, sin cambio visible para
  el paciente; volver atrás es el rollback.
- Orden de despliegue: `deploy/desplegar.sh vX.Y.Z` sin migraciones.
- Compatibilidad: las sesiones abiertas antes del despliegue siguen valiendo (su versión no cambia hasta que el
  paciente cambia la contraseña).
- Rollback: `bash deploy/volver.sh <tag anterior>`, sin la copia. Las versiones que hayan subido siguen en la base; la
  versión anterior las sigue respetando en `SesionVigente`.
- Métricas a vigilar tras el deploy: 5xx en el perfil (aviso automático `dentissa-vigilar`) y que la comprobación
  manual de Observabilidad sale bien.

## Decisiones (→ ADR si son arquitectónicas)
| ID | Pregunta | Opciones | Decisión |
|---|---|---|---|
| D1 | ¿Cómo se cierran las otras sesiones? | subir `version_sesion` (mecanismo de la 041) · `Auth::logoutOtherDevices()` · borrar filas de sesiones | **Subir `version_sesion`**: ya existe, funciona con el driver `file` de producción (no hay tabla de sesiones que borrar) y con las cuentas de Google sin contraseña, que `logoutOtherDevices()` no admite porque exige la contraseña. Se sube en el mismo `update` que guarda el hash, tras todas las comprobaciones (TM4) |
| D2 | ¿Qué pasa con la sesión desde la que se cambia? | sigue abierta · se cierra también | **Sigue abierta**, con el identificador renovado y la versión nueva en la sesión, para que `SesionVigente` no la cierre (decisión del usuario, 2026-11-20) |
| D3 | ¿Qué rutas del perfil exigen `role:cliente`? | solo `PUT /cliente/perfil` · también `GET /cliente/perfil` | **Solo `PUT /cliente/perfil`**, que es lo que pide CA7; `GET` queda para la spec 015 (decisión del usuario, 2026-11-20). La de la contraseña ya lo exige (043) |
| D4 | ¿Se avisa al paciente de que se cerraron sus otras sesiones? | mensaje nuevo · mensaje de siempre | **Mensaje de siempre**: la spec no lo pide y un texto nuevo es un cambio de contrato visible; queda como mejora posible (supuesto) |
| D5 | ¿Hay que invalidar cookies de «Recordarme»? | sí · no hace falta | **No hace falta**: el inicio de sesión nunca emite esa cookie (`Auth::attempt` sin `remember`); un test de caracterización lo fija (TM5) |
| D6 | ¿Crear la primera contraseña de una cuenta de Google también cierra las otras sesiones? | sí · solo al cambiar una existente | **Sí**: es la misma acción («cambio mi contraseña», CA6) y el mismo código; deja fuera a quien entró con la cuenta de Google en otro sitio. El caso de una sesión robada queda fuera (TM6 → 018) (decisión del usuario, 2026-11-20, `/analyze` A4) |
| D7 | Auditoría del cambio de contraseña, del guardado del perfil y de los cierres de sesión forzados | registrar el evento en esta spec · excepción P7 | **Excepción P7** → 021 (cambios de contraseña), 022 (cambios de pacientes) y el registro de entradas y salidas (cierres forzados) (decisión del usuario, 2026-11-20; cierres forzados: `/analyze` A5) |

Ninguna decisión es arquitectónica: sin ADR.

## Riesgos y mitigaciones
| Riesgo | Mitigación |
|---|---|
| La sesión actual se cierra también si no guarda la versión nueva | `session()->put('version_sesion', …)` tras el `update`; lo prueba `…_la_sesion_actual_sigue_abierta_con_otro_identificador` |
| Una versión que sube sin cambiar la contraseña (por un error de orden) cierra sesiones sin motivo | La versión va en el mismo `update` que el hash; `…_un_cambio_rechazado_no_cierra_ninguna_sesion` cubre los cuatro rechazos |
| Algún flujo del personal usaba `PUT /cliente/perfil` | Ninguna vista del personal enlaza al perfil del paciente (el menú lo muestra solo al cliente); la suite completa lo confirmaría |

## Impacto en arquitectura
- `docs/architecture.md`: en la sección del perfil o de sesiones, que el cambio de contraseña desde el perfil también
  sube `version_sesion` (como el restablecimiento) y que `PUT /cliente/perfil` exige `role:cliente`. Lo hace T090.
- `docs/security.md` (en `/release`), corrección por corrección según la «Cobertura de riesgos» de la spec
  (`/analyze` A3):
  - RS2.a sigue `pendiente`, atendida en parte: anota `PUT /cliente/perfil` con `role:cliente` (spec 011, CA7, T020);
    `GET /cliente/perfil` y el resto del grupo → spec 015.
  - RS2.b pasa a `mitigada` (CA7: T020, T013).
  - RS9.a pasa a `mitigada` (CA6: T021, T014–T016).
  - RS9.b sigue `mitigada` (041); su residuo de las cuentas de Google gana el cierre de las sesiones del dueño (TM6)
    → spec 018.
  - RS2 queda atendido parcialmente (RS2.a pendiente); RS9, con sus dos correcciones dentro, salvo el residuo.
- `docs/roadmap.md` (en `/release`): la excepción P7 de la 011 como deuda (→ 021 y 022), y los destinos de esas dos
  correcciones, que ya no son la 016 ni la 015.
