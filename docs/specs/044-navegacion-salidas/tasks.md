---
spec: 044-navegacion-salidas
plan: plan.md
impl_base: b7173e9
status: approved
---

# Tareas · 044 Salidas de las pantallas

Formato:
`- [ ] T### [P] <verbo + qué> — <archivos> — hecho cuando: <criterio> — cubre: CA# — depende: T###`
- T001–T089: trabajo. T090–T099: reservadas.
- `[P]` = paralelizable con las demás `[P]` de su grupo una vez cumplidas sus dependencias (no
  comparte archivos con otra tarea abierta).
- `[-]` = obsoleta (`— obsoleta: <motivo> (AAAA-MM-DD)`): no cuenta como abierta ni como hecha.
- Bajo una tarea: `  - nota: …`, `  - bloqueo: AAAA-MM-DD — <qué falta> — <skill a ejecutar>`.
- **Proceso ligero (spec y `design.md`, Decisiones):** sin ciclo de `/design-spec`; cada pantalla la valida el usuario a 320, 390 y 1440 px en la tarea que la construye. Sale como v0.15.0, antes que la 039 y después del `/chore` de Symfony 7.4.23 (EX4 vence el 2026-11-11).
- **Tests en este clon:** los contenedores en marcha montan `dentissa-041`; aquí, `MSYS_NO_PATHCONV=1 docker compose run --rm --no-deps -T app php artisan test …` (sin tocar los otros contenedores).
- **Línea base (2026-11-09, `impl_base` b7173e9):** suite completa 721 en verde (12 191 aserciones), sin fallos previos.
- **Corrección tras `/analyze` r1 (2026-11-08):** `/tasks --fix` editó en su lugar T010, T011, T013, T020, T021, T029, T031, T033, T040, T091 y T098 (A1–A4, A6, A7, A10–A14; A5 y A8 por decisión del usuario) y añadió T014 (A6). Tras `/analyze` r2: T014 se movió delante de las tareas de interfaz (A19) y se editaron T013, T021, T023, T027, T028, T029, T040, T095 y T097 (A9, A16–A20, A22). No se renumeró ninguna.
- **Rutas de prueba:** en `setUp()` con `Route::middleware('web')->get('/_pruebas/044/…')`, como `DenegacionesAuditadasTest`; la 500, con `config(['app.debug' => false])`, como `ErroresProduccionTest`.

## Constitution Check
Contra `plan.md` (constitución 1.3.0). Lo que el desglose podría romper:

| Principio | Resultado | Justificación / ajuste | Cómo se verifica |
|---|---|---|---|
| P1 Spec aprobada antes de código | ✅ | Spec, `design.md` y plan aprobados; ninguna tarea de código empieza antes de que `tasks.md` esté `approved` y `/analyze` en `pass` | manual: `aidd.py status` |
| P2 Test que falla antes y pasa después | ✅ | Cada historia empieza por su tarea de test (T010–T013) y cada tarea de implementación depende de ella. Las caracterizaciones (CA3 sin gastar el enlace, CA4 sin cuenta, CA8/TM7 auditoría, TM3 códigos) se comprueban rompiendo el control y se anotan | test: T010–T013 |
| P3 Autorización en servidor | ✅ | Ninguna tarea toca middleware de rol ni Gates; T030 conserva el 403 y su mensaje | test: T013 (`test_la_denegacion_por_rol_sigue_siendo_403_con_su_mensaje`) |
| P4 Entrada validada y SQL con bindings | ✅ | Sin entrada nueva ni SQL; T030 imprime el mensaje con salida escapada | lint: semgrep en T040 · test: T013 (TM2) |
| P5 Nada sensible en el repo | ✅ | Sin datos ni secretos; las cuentas de los tests salen del escenario | lint: gitleaks en T040 |
| P6 Sin vulnerabilidades críticas/altas | ✅ | Ninguna tarea toca dependencias | lint: `composer audit --locked`, `npm audit --audit-level=high` en T040 |
| P7 Auditoría | ❌ aceptado: el cierre de sesión no se audita hoy y la 044 cambia su destino sin añadir el evento; llega con los listeners de autenticación del objetivo 3 (T091 registra la excepción en el roadmap) — aprobado por el usuario el 2026-11-08 | T030 no toca `RegistroCentralDenegaciones`; T013 prueba que el 403 sigue auditado | test: T013 (`test_el_acceso_denegado_sigue_en_la_auditoria`) |
| P8 Correlación y logs limpios | ✅ | Ningún `Log::` nuevo; la 500 muestra el identificador de la petición | test: T013 (`test_las_paginas_de_error_llevan_el_identificador_de_peticion`) · manual: diff sin `Log::` en T040 |
| P9 Sistema de diseño | ✅ | T001 y T014 van primero y las tareas de interfaz dependen de ellas; solo tokens, sin `style=""` nuevos | test: T014 (`NavegacionSalidasCssTest`: toque `--touch-min`, solo tokens, sin `style=""` nuevos) · manual: verificación del usuario en cada tarea de interfaz y `capturar.mjs --medir` en T040 |
| P10 Rutas indexables | ✅ | `/` y `/registro` sin cambios de metadatos; `noindex` en errores y `/up` | test: T013 (CA7, CA12) y `PaginasPublicasTest` en la suite de T040 |

## Preparación
- [x] T001 Incorporar al sistema de diseño los componentes de `design.md` → «Cambios a incorporar al sistema» (enlace «Volver al inicio», logo-enlace, entrada «Página de inicio» de la barra lateral, `.pub-aviso`, plantilla de página de error y `.enlace-secundario`) y actualizar «Estados de pantalla → Error del servidor» y DS7 (parcial), con versión 1.12.0 y su entrada en «Decisiones» — `docs/design/system.md`, `docs/design/system.html` — hecho cuando: `system.md` 1.12.0 lista los seis componentes con sus tokens (ninguno nuevo) y `system.html` los muestra

## Tests transversales
- [x] T014 Escribir las comprobaciones automáticas de P9 (A6), con el patrón de `MovilPacienteTest::assertDeclara` (`Concerns/ReglasCss`): `.enlace-inicio` en `acceso.css` y `auth.css`, `.enlace-secundario` en `errores.css` y la entrada de la barra lateral declaran el alto `--touch-min`; `.enlace-inicio`, `.enlace-secundario` y `.pub-aviso` solo usan tokens (sin colores ni medidas literales); `layouts/acceso`, `auth/recuperar/nueva`, `layouts/app`, `layouts/publico`, `publico/inicio` y las vistas de `errors/` nuevas no tienen `style=""`, y `register` y `registro-contacto` conservan el mismo número de `style=""` que hoy — `tests/Feature/NavegacionSalidasCssTest.php` — hecho cuando: falla por la razón esperada (las reglas y la entrada aún no existen; los casos de `style=""` pasan desde el principio y se comprueban añadiendo uno un momento) y se ejecuta dos veces seguidas — cubre: CA1, CA5, CA6, CA7 — depende: T001
  - nota: 2026-11-09 — 10 casos en rojo por la razón esperada (reglas `.enlace-inicio`, `.enlace-secundario` y `.pub-aviso` sin definir, tokens ausentes en `auth.css`, sin entrada en la barra lateral, vistas de `errors/` sin crear), dos ejecuciones. La parte de `style=""` en las vistas existentes, comprobada rompiendo: con `style` añadido un momento a `layouts/acceso` falla en «ningún style="" nuevo». Pint en verde.

## Historia 1: salidas del acceso y del registro
- [x] T010 Escribir los tests de las salidas del acceso: `test_las_pantallas_de_acceso_empiezan_por_volver_al_inicio` (data provider con inicio de sesión, registro, completar registro con Google, pedir enlace, enlace enviado, enlace caducado y contraseña nueva: el primer `<a>` de `.form-container` va a `route('inicio')` y dice «Volver al inicio»), `test_el_logo_del_acceso_lleva_al_inicio_con_nombre_accesible` (en las siete pantallas: el nombre accesible del enlace es exactamente «Ir a la página de inicio» y la imagen lleva `alt=""`, A14), `test_contrasena_nueva_ofrece_volver_a_iniciar_sesion`, `test_salir_de_contrasena_nueva_no_gasta_el_enlace` (caracterización), `test_contrasena_nueva_conserva_no_referrer_y_sus_salidas_no_llevan_el_token`, `test_salir_del_registro_con_google_no_crea_cuenta` (caracterización) y `test_volver_a_entrar_con_google_reemplaza_los_datos_anteriores` (caracterización) — `tests/Feature/NavegacionSalidasTest.php` — hecho cuando: los de CA1, CA2 y CA3 fallan por falta del enlace; las caracterizaciones pasan y se comprueban rompiendo su control (validez del token; creación de cuenta en `contactoStore`; el `put` de `google_registro` en el callback), con su nota — cubre: CA1, CA2, CA3, CA4
  - nota: 2026-11-09 — 15 casos en rojo por la razón esperada (CA1: el primer enlace del formulario no va a la página de inicio en las siete pantallas; CA2: el logo no es enlace en las siete; CA3: «Contraseña nueva» sin «Volver a iniciar sesión»), dos ejecuciones. Caracterizaciones en verde, comprobadas rompiendo: borrar el token al abrir «Contraseña nueva» (falla el de no gastar el enlace); quitar `referrer: no-referrer` (falla TM4); crear la cuenta al abrir la pantalla de contacto (falla CA4, sin cuenta); guardar el callback de Google en otra clave (falla el de reemplazar los datos). Corregido un helper del test que tomaba `href="#"` por la raíz. Pint en verde.
- [ ] T020 Añadir a `layouts/acceso` el enlace `.enlace-inicio` como primer elemento de `.form-container` y el logo del panel de marca como enlace a `route('inicio')` con `aria-label="Ir a la página de inicio"` e imagen con `alt=""`, con sus estilos solo de tokens, y cambiar en `RecuperarContrasenaVistasTest::test_el_acceso_usa_el_estilo_anterior_del_login` la comprobación del logo (imagen con `alt` no vacío) por el enlace con ese nombre accesible (cambio del contrato de la 041, A3) — `resources/views/layouts/acceso.blade.php`, `public/css/acceso.css`, `tests/Feature/RecuperarContrasenaVistasTest.php` — hecho cuando: los casos de T010 de las cinco pantallas del acceso y el del logo pasan, y `RecuperarContrasenaVistasTest` y los casos de `acceso.css` de T014 pasan · verificación manual: inicio de sesión, pedir enlace, enlace enviado, enlace caducado y contraseña nueva a 320, 390 y 1440 px, con «Volver al inicio» sin desplazarse y el foco visible en el logo, contra `design.md` → Pantallas — cubre: CA1, CA2 — depende: T001, T010, T014
- [ ] T021 Añadir `.enlace-inicio` al principio de `.form-container` del registro y de completar el registro, el logo de `.panel-illustration` como enlace con `aria-label="Ir a la página de inicio"` y la imagen con `alt=""` (A14), y en `auth.css` la regla `.enlace-inicio` con los tokens copiados de `app.css` a su `:root` con los mismos valores (D8: `--touch-min`, `--space-4`, `--icon-md`, `--text-meta`, `--weight-strong` y `--focus-ring*`, con los valores de `app.css`, A19) — `resources/views/auth/register.blade.php`, `resources/views/auth/registro-contacto.blade.php`, `public/css/auth.css` — hecho cuando: los casos de T010 del registro, completar el registro y el de Google, y los de `auth.css` y del registro de T014, pasan · verificación manual: las dos pantallas a 320, 390 y 1440 px contra `design.md` — cubre: CA1, CA2, CA4 — depende: T001, T010, T014
- [ ] T022 Añadir a «Contraseña nueva» «Volver a iniciar sesión» (`.enlace-volver` a `route('login')`) bajo el talón, conservando `referrer: no-referrer` — `resources/views/auth/recuperar/nueva.blade.php` — hecho cuando: los casos de CA3 y de TM4 de T010 pasan · verificación manual: la pantalla a 320 y 1440 px — cubre: CA3 — depende: T010, T020

## Historia 2: del panel a la página de inicio
- [ ] T011 Escribir los tests de la navegación con sesión: `test_el_panel_de_cada_rol_enlaza_la_pagina_de_inicio` (los cuatro roles: `.nav-item` «Página de inicio» a `route('inicio')` dentro de `.sidebar-nav`), `test_la_pagina_de_inicio_con_sesion_ofrece_ir_a_mi_panel` (200; cabecera, `.pub-acceso` y los dos botones de cuenta con «Ir a mi panel» al panel de cada rol, sin enlaces al registro) y `test_el_registro_con_sesion_lleva_al_panel` (`/registro`, y `/registro/contacto` con y sin `google_registro` en la sesión, A10); cambiar en `PaginaInicioTest` el caso de la redirección con sesión (038 CA2) por la página con «Ir a mi panel» — `tests/Feature/NavegacionSalidasTest.php`, `tests/Feature/PaginaInicioTest.php` — hecho cuando: fallan por la razón esperada (sin entrada en el panel; `/` y `/registro` redirigen) — cubre: CA5 — depende: T010
- [ ] T023 Añadir al final de `.sidebar-nav`, fuera de los `@if` de rol, `.nav-label` «Sitio» y `.nav-item` «Página de inicio» con icono de casa (`aria-hidden`) a `route('inicio')` — `resources/views/layouts/app.blade.php` — hecho cuando: `test_el_panel_de_cada_rol_enlaza_la_pagina_de_inicio` y el caso de la barra lateral de T014 pasan · verificación manual: la entrada en la barra lateral a 1440 px y dentro del menú del móvil a 320 y 390 px, sin cortar el menú, contra `design.md` — cubre: CA5 — depende: T001, T011, T014
- [ ] T024 Quitar la redirección con sesión de `inicio()` y, con sesión, cambiar `.pub-acceso` y los botones «Agendar mi primera cita» y «Crear mi cuenta» por «Ir a mi panel» (`$panel` del layout público) — `app/Http/Controllers/PaginasPublicasController.php`, `resources/views/publico/inicio.blade.php` — hecho cuando: `test_la_pagina_de_inicio_con_sesion_ofrece_ir_a_mi_panel` y `PaginaInicioTest` pasan, y `PaginasPublicasTest` sigue en verde · verificación manual: la página de inicio con sesión a 390 y 1440 px — cubre: CA5 — depende: T011
- [ ] T025 Redirigir `showForm()` y `contactoForm()` con sesión a `PaginasPublicasController::panelDelRol()` en lugar de a `/` (D7), con destinos fijos (TM1) — `app/Http/Controllers/RegisterController.php` — hecho cuando: `test_el_registro_con_sesion_lleva_al_panel` pasa y los tests del registro siguen en verde — cubre: CA5, CA11 — depende: T011

## Historia 3: cerrar sesión
- [ ] T012 Escribir los tests del cierre de sesión: `test_cerrar_sesion_lleva_al_inicio_con_el_aviso_una_sola_vez` (302 a `/`; el aviso «Has cerrado sesión» con `role="status"`; la cabecera con «Iniciar sesión» y «Crear cuenta»; sin aviso al recargar) y `test_tras_cerrar_sesion_el_panel_no_se_sirve_de_cache` (la respuesta del panel y la de `/` con `Cache-Control: no-store`; tras cerrar sesión, el panel redirige al inicio de sesión) — `tests/Feature/NavegacionSalidasTest.php` — hecho cuando: el primero falla (hoy va a `/login`); el segundo pasa (caracterización de `PreventBackHistory`, comprobada quitándolo un momento del grupo `web`) — cubre: CA6 — depende: T011
- [ ] T026 Cambiar `logout()` para que, tras `invalidate()` y `regenerateToken()`, vaya a `route('inicio')` con el flash `sesion_cerrada` (destino fijo, TM1) — `app/Http/Controllers/AuthController.php` — hecho cuando: la parte de la redirección de `test_cerrar_sesion_lleva_al_inicio_con_el_aviso_una_sola_vez` pasa — cubre: CA6, CA11 — depende: T012
- [ ] T027 Mostrar `.pub-aviso` con `role="status"`, la palomita (`aria-hidden`) y «Has cerrado sesión» bajo la cabecera del layout público cuando la sesión trae `sesion_cerrada`, con sus estilos solo de tokens — `resources/views/layouts/publico.blade.php`, `public/css/publico.css` — hecho cuando: `test_cerrar_sesion_lleva_al_inicio_con_el_aviso_una_sola_vez` y el caso de `.pub-aviso` de T014 pasan · verificación manual: el aviso en la primera pantalla a 320, 390 y 1440 px, y que NVDA lo lee al llegar a la página tras cerrar sesión (A9) — cubre: CA6 — depende: T001, T012, T014, T026

## Historia 4: páginas de error y salud
- [ ] T013 Escribir los tests de las páginas de error: `test_una_direccion_inexistente_sin_sesion_ofrece_inicio_login_y_registro`, `test_una_direccion_inexistente_con_sesion_ofrece_ir_a_mi_panel` (404, un `h1`, enlaces y `<meta name="robots" content="noindex">`), `test_la_denegacion_por_rol_sigue_siendo_403_con_su_mensaje` (`/admin` como dentista y la cancelación del dentista con el mensaje de `EstadosCitaRolesTest`; sin «This action is unauthorized.»), `test_el_acceso_denegado_sigue_en_la_auditoria` (caracterización), `test_un_formulario_caducado_ofrece_volver_a_iniciar_sesion` (ruta de prueba que lanza `TokenMismatchException`), `test_demasiados_intentos_explica_que_esperar` (ruta de prueba que lanza la excepción de límite: la 429 es preventiva, A2), `test_los_limites_actuales_siguen_con_su_propio_aviso` (`throttle:registro` vuelve al formulario con su mensaje y `abrir-enlace` con su propia vista, sin la 429 nueva; caracterización), `test_cualquier_verbo_a_una_direccion_inexistente_sigue_en_404` (GET, POST y DELETE con la página propia, A1), `test_un_verbo_equivocado_responde_404` (`GET /logout`, A17), `test_la_500_no_muestra_detalles_internos`, `test_pintar_la_500_no_consulta_la_base` (`DB::listen` sin consultas al pintar), `test_las_paginas_de_error_llevan_el_identificador_de_peticion` (cabecera `X-Request-Id` en las cinco; identificador en el cuerpo solo en la 500, A7), `test_los_enlaces_de_vuelta_ignoran_un_destino_externo` (`?volver=https://…` en las pantallas de acceso, el cierre de sesión y las páginas de error), `test_la_404_no_muestra_el_mensaje_de_la_excepcion`, `test_la_404_de_un_recurso_ajeno_es_igual_a_la_de_uno_inexistente` (caracterización de los códigos; mismo cuerpo, A7) y `test_la_comprobacion_de_salud_no_se_indexa` — `tests/Feature/PaginasErrorTest.php` — hecho cuando: fallan por la razón esperada (páginas de Laravel en inglés, sin `noindex`, `/up` sin cabecera); las caracterizaciones pasan y se comprueban rompiendo su control (quitar `RegistroCentralDenegaciones` del `respond`; el `denyAsNotFound`; el `->response()` de `registro`, A22), con su nota — cubre: CA7, CA8, CA9, CA10, CA11, CA12
- [ ] T028 Crear la plantilla común sobre la carcasa del error genérico de la 020 (documento propio, `noindex`, `app.css` + `errores.css`, logo-enlace en la franja y el panel, `.etiqueta`, `h1`, `.sub`, acción principal, enlaces secundarios y `.codigo` opcional; sesión con `auth()->hasUser()`, sin consultas: D4, TM6), la página 500 («Algo salió mal», identificador de la petición, «Ir a la página de inicio») y `.enlace-secundario` en `errores.css` solo con tokens — `resources/views/errors/plantilla.blade.php`, `resources/views/errors/500.blade.php`, `public/css/errores.css` — hecho cuando: `test_la_500_no_muestra_detalles_internos`, `test_pintar_la_500_no_consulta_la_base`, el caso 500 de `test_las_paginas_de_error_llevan_el_identificador_de_peticion` y `ErroresProduccionTest` y el caso de `errores.css` de T014 pasan · verificación manual: la 500 a 320, 390 y 1440 px contra `design.md` → Salidas — cubre: CA10, CA11 — depende: T001, T013, T014
- [ ] T029 Crear la página 404 («No encontramos esta página», «Ir a la página de inicio» y, según `auth()->hasUser()`, «Iniciar sesión» · «Crear cuenta» o «Ir a mi panel»; sin el mensaje de la excepción, TM3) y añadir al final de `routes/web.php` las dos rutas de reserva de D3 (GET/HEAD dentro de `web`; POST, PUT, PATCH, DELETE y OPTIONS sin el grupo `web`), las dos con `abort(404)` (A1) — `resources/views/errors/404.blade.php`, `routes/web.php`, `tests/Feature/AdminUsuariosRutasTest.php` (su test de identificador sin forma de UUID pasa a comprobar que no resuelve a `UsuarioController@show` y responde 404: D3, A16) — hecho cuando: los dos tests de dirección inexistente, `test_cualquier_verbo_a_una_direccion_inexistente_sigue_en_404`, `test_un_verbo_equivocado_responde_404`, `RecuperarContrasenaTest` sin cambios, `AdminUsuariosRutasTest`, `test_la_404_no_muestra_el_mensaje_de_la_excepcion` y `test_la_404_de_un_recurso_ajeno_es_igual_a_la_de_uno_inexistente` pasan · verificación manual: la 404 con y sin sesión a 320 y 1440 px — cubre: CA7, CA11 — depende: T028
- [ ] T030 Crear la página 403 («No tienes acceso a esta página», el mensaje de `Response::deny()` si no está vacío ni es el genérico de Laravel, escapado; «Ir a mi panel» y «Ir a la página de inicio»: D5), sin tocar `bootstrap/app.php` ni `RegistroCentralDenegaciones` (TM7) — `resources/views/errors/403.blade.php` — hecho cuando: `test_la_denegacion_por_rol_sigue_siendo_403_con_su_mensaje`, `test_el_acceso_denegado_sigue_en_la_auditoria`, `EstadosCitaRolesTest` y `DenegacionesAuditadasTest` pasan · verificación manual: la 403 de `/admin` como dentista a 390 y 1440 px — cubre: CA8 — depende: T028
- [ ] T031 [P] Crear las páginas 419 («La página caducó», «Volver a iniciar sesión» y «Ir a la página de inicio») y 429 («Demasiados intentos», «Espera unos minutos y vuelve a probar», «Ir a la página de inicio») — `resources/views/errors/419.blade.php`, `resources/views/errors/429.blade.php` — hecho cuando: `test_un_formulario_caducado_ofrece_volver_a_iniciar_sesion`, `test_demasiados_intentos_explica_que_esperar` y `test_los_limites_actuales_siguen_con_su_propio_aviso` pasan · verificación manual: las dos a 390 px — cubre: CA9, CA10 — depende: T028
- [ ] T032 [P] Crear el middleware `NoIndexarSalud` (añade `X-Robots-Tag: noindex` a la respuesta de `/up`) y registrarlo como global (D6) — `app/Http/Middleware/NoIndexarSalud.php`, `bootstrap/app.php` — hecho cuando: `test_la_comprobacion_de_salud_no_se_indexa` pasa y `/up` sigue respondiendo 200 — cubre: CA12 — depende: T013
- [ ] T033 Comprobar los casos de `PaginasErrorTest` que cruzan varias tareas (la cabecera `X-Request-Id` en las cinco páginas de error y el identificador en el cuerpo solo en la 500, A7, y `test_los_enlaces_de_vuelta_ignoran_un_destino_externo` en todas las salidas); si uno falla, se corrige en la tarea de la vista que lo cubre (T020–T032), que se reabre con una nota — sin archivos propios — hecho cuando: `PaginasErrorTest` pasa entero dos veces seguidas — cubre: CA7, CA8, CA9, CA10, CA11 — depende: T020, T021, T022, T026, T029, T030, T031, T032

## Verificación final
- [ ] T040 Pasar la verificación final: suite completa (los tests nuevos dos veces), Pint sobre los PHP tocados, gitleaks sobre el diff, semgrep `p/php`, `composer audit --locked`, `npm audit --audit-level=high`, revisión del diff (sin `Log::` ni `style=""` nuevos, sin salida sin escapar) y `node docs/design/capturar.mjs --medir` (se toca `layouts/app.blade.php`) — sin archivos — hecho cuando: todo en verde (incluido `NavegacionSalidasCssTest`, T014) y los resultados anotados en la tarea · verificación manual: el usuario confirma las pantallas pendientes de T020–T031 — depende: T014, T033

## Integración y documentación
- [ ] T090 Actualizar `docs/architecture.md` si cambió la estructura: `resources/views/errors/` (plantilla y cinco páginas), `Route::fallback`, middleware global `NoIndexarSalud` y el flujo de sesión (el cierre va a `/`; `/` no redirige con sesión) — depende: T040
- [ ] T091 Actualizar `docs/deployment.md` y `docs/observability.md` si cambiaron variables, entornos, pasos de deploy, logs, eventos de auditoría o métricas: en `deployment.md` → «Verificación post-deploy», la página 404 propia y `X-Robots-Tag: noindex` en `/up`; `observability.md` sin cambios (plan, Observabilidad). En `docs/seo.md`, las filas de la tabla de rutas para la 404 y `/up`, la fila de `/` (ya no redirige con sesión) y la línea de la 404 genérica (SE2.b y SE4.a pasan a `mitigada` en `/release`, por corrección) (A12). En `docs/roadmap.md`: SE4.a sale de Pendientes al liberar (A12); la **excepción P7 de la spec 044** (el logout sin auditar, destino los listeners de autenticación del objetivo 3; decisión del usuario, 2026-11-08, A5) y el riesgo de `RegistroCentralDenegaciones` con la base caída (spec 020; decisión del usuario, A8) en «Pendientes y deuda» — depende: T040
- [ ] T092 Marcar spec como `implemented` — depende: T090, T091

## Despliegue (lo ejecuta `/release`)
- [ ] T095 Desplegar a staging y verificar criterios de aceptación. No hay staging (objetivo 5): la comprobación se hace en producción, dentro de T097: `/no-existe` muestra la 404 propia, `curl -I https://dentissa.app/up` lleva `X-Robots-Tag: noindex`, `curl -X POST https://dentissa.app/no-existe` responde 404 y no 419 (D3: en los tests la comprobación CSRF está desactivada, A18), y con una cuenta de prueba, «Página de inicio» desde el panel y cerrar sesión con su aviso
- [ ] T096 Aprobación humana para producción
- [ ] T097 Desplegar a producción y vigilar métricas del plan (Rollout): `deploy/desplegar.sh v0.15.0`, `verificar.sh` y la comprobación de T095 (incluido el `curl -X POST`); vigilar 5xx, 404 y 403 en el log de nginx la primera hora. Rollback: `deploy/volver.sh <tag anterior>` sin copia
- [ ] T098 Marcar spec como `released`; en las specs 001, 002, 012, 038 y 041 anotar el cambio en su Historial, y marcar 038 CA2 y 001 CA5 `→ reemplazado por 044 CA5` (A4); en la 041, anotar el cambio del test del logo (A3), y en la 010 el del test de rutas de usuarios (A16)

## Cobertura
| Criterio | Tarea(s) de test | Tarea(s) de implementación |
|---|---|---|
| CA1 | T010, T014 | T020, T021 |
| CA2 | T010 | T020, T021 |
| CA3 | T010 | T022 |
| CA4 | T010 | T021 |
| CA5 | T011, T014 | T023, T024, T025 |
| CA6 | T012, T014 | T026, T027 |
| CA7 | T013, T014 | T029, T033 |
| CA8 | T013 | T030, T033 |
| CA9 | T013 | T031, T033 |
| CA10 | T013 | T028, T031, T033 |
| CA11 | T013 | T025, T026, T028, T029, T033 |
| CA12 | T013 | T032 |

| Amenaza (TM#) | Tarea(s) de control | Tarea(s) de test |
|---|---|---|
| TM1 | T020, T021, T022, T025, T026 | T013 |
| TM2 | T028, T029, T030 | T013 |
| TM3 | T029 | T013 |
| TM4 | T022 | T010 |
| TM5 | T026 | T012 |
| TM6 | T028 | T013 |
| TM7 | T030 | T013 |

| Cambio del plan (módulo) | Tarea(s) |
|---|---|
| `resources/views/layouts/acceso.blade.php`, `public/css/acceso.css` | T020 |
| `resources/views/auth/register.blade.php`, `resources/views/auth/registro-contacto.blade.php`, `public/css/auth.css` | T021 |
| `resources/views/auth/recuperar/nueva.blade.php` | T022 |
| `resources/views/layouts/app.blade.php` | T023 |
| `app/Http/Controllers/PaginasPublicasController.php`, `resources/views/publico/inicio.blade.php` | T024 |
| `app/Http/Controllers/RegisterController.php` | T025 |
| `app/Http/Controllers/AuthController.php` | T026 |
| `resources/views/layouts/publico.blade.php`, `public/css/publico.css` | T027 |
| `resources/views/errors/plantilla.blade.php`, `resources/views/errors/500.blade.php`, `public/css/errores.css` | T028 |
| `resources/views/errors/404.blade.php`, `routes/web.php` | T029 |
| `resources/views/errors/403.blade.php` | T030 |
| `resources/views/errors/419.blade.php`, `resources/views/errors/429.blade.php` | T031 |
| `app/Http/Middleware/NoIndexarSalud.php`, `bootstrap/app.php` | T032 |
| `tests/Feature/NavegacionSalidasTest.php` | T010, T011, T012 |
| `tests/Feature/PaginasErrorTest.php` | T013 (T033 lo ejecuta entero) |
| `tests/Feature/PaginaInicioTest.php` | T011 |
| `tests/Feature/RecuperarContrasenaVistasTest.php` | T020 |
| `tests/Feature/AdminUsuariosRutasTest.php` | T029 |
| `tests/Feature/NavegacionSalidasCssTest.php` | T014 |
| `docs/design/system.md`, `docs/design/system.html` | T001 |

| Requisito no funcional | Tarea(s) de test o verificación manual (T095) |
|---|---|
| WCAG 2.1 AA (foco, contraste, toque ≥ 44 px, aviso sin mover el foco, un `h1`) | T010–T013 (atributos), T014 (toque, tokens, sin `style=""`) · verificación manual en T020–T031 · `capturar.mjs --medir` en T040 |
| Descubribilidad (`/` y `/registro` sin cambios; errores y salud con `noindex`) | T013 · `PaginasPublicasTest` en T040 · Lighthouse en `/review` |
| Páginas de error sin depender de la base (acotado a la plantilla, A8) | T013 (`test_pintar_la_500_no_consulta_la_base`), T028; riesgo de la 020 al roadmap en T091 |
