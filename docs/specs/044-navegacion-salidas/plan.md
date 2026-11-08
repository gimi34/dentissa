---
spec: 044-navegacion-salidas
status: draft   # draft | approved | blocked
constitution_version: 1.3.0
created: 2026-11-08
---

# Plan · 044 Salidas de las pantallas

## Enfoque técnico
Son cambios de navegación en vistas y en tres controladores; no cambian datos, rutas protegidas ni
permisos. Las salidas del acceso entran en los dos sitios que pintan esas pantallas:
`layouts/acceso.blade.php` (inicio de sesión y recuperación) y las dos vistas propias del registro.
Así, un solo cambio por layout cubre las siete pantallas. El panel recibe una entrada común al final
de la barra lateral, que en el móvil ya es el menú (spec 033). La página de inicio deja de redirigir
a quien tiene sesión (reemplaza 038 CA2); el cierre de sesión aterriza en ella con un aviso de un solo
uso, y el registro con sesión va al panel para que no haya bucle. Las páginas de error son vistas
`resources/views/errors/{404,403,419,429,500}.blade.php` sobre una plantilla común que reutiliza la
carcasa del «error genérico» de la spec 020 (`errores.css`): documento propio, sin layout autenticado,
sin consultas a la base y con `noindex`. Una ruta de reserva (`Route::fallback`) hace que la 404 pase
por el grupo `web`, así la página sabe si hay sesión. Un middleware global añade
`X-Robots-Tag: noindex` a `/up`.

## Constitution Check
| Principio | Resultado | Justificación / ajuste | Cómo se verifica |
|---|---|---|---|
| P1 Spec aprobada antes de código | ✅ | Spec 044 aprobada (de nuevo tras /analyze r1), `design.md` y este plan aprobados; `tasks.md` aprobado antes de `/implement` | manual: `python .ai/bin/aidd.py status` |
| P2 Test que falla antes y pasa después | ✅ | Cada CA con su test (Trazabilidad); de caracterización, marcados como tales, CA3 (enlace sin gastar), CA4 (sin cuenta), CA8 (auditoría) y la parte de CA11 sobre los códigos | test: Trazabilidad; `/implement` anota el fallo previo o la rotura comprobada |
| P3 Autorización en el servidor | ✅ | No hay endpoints nuevos con datos; la ruta de reserva solo responde 404. Los controles por rol no cambian: la página 403 solo cambia el aspecto, con el mismo código y el mismo mensaje del Gate | test: `PaginasErrorTest::test_la_denegacion_por_rol_sigue_siendo_403_con_su_mensaje` + `EstadosCitaRolesTest` y `DenegacionesAuditadasTest` sin cambios |
| P4 Entrada validada y SQL con bindings | ✅ | No hay entrada nueva. En Blade solo la salida escapada (dobles llaves), también para el mensaje del permiso en la 403; sin salida sin escapar | lint: `semgrep` sobre lo tocado; test: TM2 |
| P5 Nada sensible en el repositorio | ✅ | Sin secretos ni datos | lint: `gitleaks detect --no-banner` |
| P6 Sin vulnerabilidades críticas o altas | ✅ | Sin dependencias nuevas. Línea base del 2026-11-08: `composer audit` solo con los avisos de EX4 (vence el 2026-11-11, `/chore` a Symfony 7.4.23 ese día, **antes** del `/release` de la 044); `npm audit` y `trivy` sin hallazgos | manual: SCA en `/review` y `/release` |
| P7 Auditoría | ❌ aceptado: el cierre de sesión no se audita hoy y la 044 cambia su destino sin añadir el evento; llega con los listeners de autenticación de `docs/observability.md` (objetivo 3), junto con los de inicio de sesión, como la excepción P7 de la spec 030 (T091 la registra en el roadmap) — aprobado por el usuario el 2026-11-08 | Los 403 siguen auditados: la página nueva no toca `RegistroCentralDenegaciones`, que lee el código de la respuesta; la 044 no quita ningún evento | test: `PaginasErrorTest::test_el_acceso_denegado_sigue_en_la_auditoria` |
| P8 Correlación y logs | ✅ | Las cinco páginas de error siguen con la cabecera `X-Request-Id` (el middleware va delante); el identificador va en el cuerpo **solo en la 500**, como el error genérico, para que el cuerpo de la 404 sea el mismo en cada petición (TM3); sin logs nuevos | test: `PaginasErrorTest::test_las_paginas_de_error_llevan_el_identificador_de_peticion` |
| P9 Sistema de diseño | ✅ | Solo tokens de `system.md` 1.11.0; los componentes nuevos (`design.md` → Cambios) entran en `system.md`/`system.html` 1.12.0 en la primera tarea. Sin `style=""` nuevos: `register.blade.php` y `registro-contacto.blade.php` tienen estilos en línea y Google Fonts **previos** (código previo, deuda DS) que no se amplían. WCAG 2.1 AA | test: `NavegacionSalidasCssTest` (cada enlace nuevo declara el alto `--touch-min`; reglas nuevas solo con tokens; ningún `style=""` nuevo en las vistas tocadas) · manual: `/review` contra `system.html`, `node docs/design/capturar.mjs --medir` (se toca `layouts/app.blade.php`) y verificación del usuario a 320, 390 y 1440 px |
| P10 Rutas indexables descubribles | ✅ | `/` y `/registro` siguen indexables, con el mismo título, descripción y canónica, y sin `noindex`; `noindex` en las páginas de error y `X-Robots-Tag` en `/up` | test: `PaginasErrorTest` (CA7, CA12) + `PaginasPublicasTest` sin cambios; manual: Lighthouse SEO de `/` y `/registro` en `/review` |

Resultado: 10 ✅, ningún principio sin aplicar y ninguna violación.

## Cambios por módulo
| Módulo | Cambio | Riesgo |
|---|---|---|
| `resources/views/layouts/acceso.blade.php` | Primer elemento de `.form-container`: enlace `.enlace-inicio` a `route('inicio')` con flecha; el logo del panel de marca dentro de un enlace a `route('inicio')` con `aria-label="Ir a la página de inicio"` y la imagen con `alt=""` | bajo: lo heredan las cinco pantallas del acceso |
| `public/css/acceso.css` | `.enlace-inicio` y el foco del logo-enlace, solo con tokens | bajo |
| `resources/views/auth/register.blade.php`, `resources/views/auth/registro-contacto.blade.php` | El mismo `.enlace-inicio` al principio de `.form-container`; el logo de `.panel-illustration` como enlace (CA2) | bajo: HTML propio con deuda previa |
| `public/css/auth.css` | `.enlace-inicio` del registro; `auth.css` no tiene los tokens de espacio y toque: se añaden a su `:root` **con los mismos valores** de `app.css` (`--touch-min`, `--space-4`, `--icon-md`, `--text-meta`, `--weight-strong`, `--focus-ring*`), sin valores nuevos | bajo |
| `resources/views/auth/recuperar/nueva.blade.php` | «Volver a iniciar sesión» (`.enlace-volver`) bajo el talón; conserva `<meta name="referrer" content="no-referrer">` | bajo |
| `resources/views/layouts/app.blade.php` | Al final de `.sidebar-nav`, fuera de los `@if` de rol: `.nav-label` «Sitio» y `.nav-item` «Página de inicio» con icono de casa | medio: toca el menú del móvil (spec 033) → `capturar.mjs --medir` |
| `app/Http/Controllers/PaginasPublicasController.php` | `inicio()` deja de redirigir con sesión (reemplaza 038 CA2) | medio: cambia un contrato que asierta `PaginaInicioTest` |
| `resources/views/publico/inicio.blade.php` | Con sesión, `.pub-acceso` y los botones «Agendar mi primera cita» y «Crear mi cuenta» pasan a «Ir a mi panel» (`$panel` del layout) | bajo |
| `resources/views/layouts/publico.blade.php`, `public/css/publico.css` | `.pub-aviso` (`role="status"`) bajo la cabecera cuando la sesión trae `sesion_cerrada` | bajo |
| `app/Http/Controllers/AuthController.php` | `logout()`: tras `invalidate()` y `regenerateToken()`, `redirect()->route('inicio')->with('sesion_cerrada', true)` | bajo |
| `app/Http/Controllers/RegisterController.php` | `showForm()` y `contactoForm()` con sesión → `PaginasPublicasController::panelDelRol()` en lugar de `/` (sin bucle tras quitar la redirección de `/`) | bajo |
| `resources/views/errors/plantilla.blade.php` (nuevo) | Documento propio sobre la carcasa del error genérico: `noindex`, `app.css` + `errores.css`, logo-enlace, `.etiqueta`, `h1`, `.sub`, acciones y `.codigo` opcional; recibe todo por `@section`/variables, sin consultas | bajo |
| `resources/views/errors/{404,403,419,429,500}.blade.php` (nuevos) | Cada una con sus textos y salidas (`design.md` → «Salidas de cada página de error»); la sesión se lee con `auth()->hasUser()`, que no consulta la base | medio: son las páginas de Laravel para todo el sitio |
| `public/css/errores.css` | `.enlace-secundario` y la fila de enlaces secundarios, solo con tokens | bajo |
| `routes/web.php` | Al final, dos rutas de reserva (D3): GET/HEAD dentro del grupo `web` (la 404 ve la sesión) y el resto de verbos sin el grupo `web` (sin sesión ni CSRF), las dos con `abort(404)`, para que ningún verbo cambie de 404 a 405 o 419 | bajo: las rutas de reserva se resuelven las últimas |
| `app/Http/Middleware/NoIndexarSalud.php` (nuevo), `bootstrap/app.php` | Middleware global que añade `X-Robots-Tag: noindex` a la respuesta de `/up` | bajo |
| `tests/Feature/NavegacionSalidasTest.php` (nuevo) | CA1–CA6 | — |
| `tests/Feature/PaginasErrorTest.php` (nuevo) | CA7–CA12 y TM1–TM7 | — |
| `tests/Feature/PaginaInicioTest.php` | El test de la redirección con sesión (038 CA2) pasa a comprobar la página con «Ir a mi panel» | — |
| `tests/Feature/RecuperarContrasenaVistasTest.php` | `test_el_acceso_usa_el_estilo_anterior_del_login` exige el logo dentro del enlace con nombre accesible «Ir a la página de inicio» en lugar de una imagen con `alt` no vacío: cambio del contrato de la 041 (/analyze A3; T098 lo anota en su Historial) | — |
| `tests/Feature/NavegacionSalidasCssTest.php` (nuevo) | Comprobaciones automáticas de P9 (/analyze A6): alto `--touch-min` declarado en `.enlace-inicio` (acceso y registro), `.enlace-secundario` y la entrada de la barra lateral; reglas nuevas sin literales; ningún `style=""` nuevo en las vistas tocadas (las del registro conservan los que ya tenían) | — |
| `docs/design/system.md`, `docs/design/system.html` | 1.12.0 con los componentes de `design.md` → Cambios; DS7 parcialmente resuelta (páginas de error) | — |

## Contratos y datos
- **Sin migraciones ni datos nuevos.** La sesión gana una clave flash de un solo uso, `sesion_cerrada` (booleana).
- **Respuestas que cambian:**
  | Petición | Hoy | Con la 044 |
  |---|---|---|
  | `GET /` con sesión | 302 al panel del rol | 200, página de inicio con «Ir a mi panel» |
  | `POST /logout` | 302 a `/login` | 302 a `/` con el flash `sesion_cerrada` |
  | `GET /registro` con sesión | 302 a `/` (y de ahí al panel) | 302 directo al panel del rol |
| `GET /registro/contacto` con sesión | sin comprobación: con `google_registro` muestra el formulario; sin él, 302 a `/registro` | 302 directo al panel del rol, con o sin `google_registro` (control nuevo) |
  | Dirección inexistente, GET o HEAD | 404 «Not Found» de Laravel, sin sesión | 404 con `errors/404`, pasando por `web` |
| Dirección inexistente, POST, PUT, PATCH, DELETE u OPTIONS | 404 «Not Found» de Laravel | 404 con `errors/404`, sin el grupo `web` (sin sesión; sin la comprobación CSRF, que daría 419) |
  | 403, 419, 429, 500 | páginas de Laravel en inglés | las mismas respuestas con `errors/{403,419,429,500}` |
  | `GET /up` | 200 sin `X-Robots-Tag` | 200 con `X-Robots-Tag: noindex` |
- **Texto de la 403:** se muestra `$exception->getMessage()` solo si no está vacío y no es el genérico
  de Laravel («This action is unauthorized.»); así se conservan los mensajes de `Response::deny()` que
  asierta `EstadosCitaRolesTest`. La 404 nunca muestra el mensaje de la excepción (TM3); la 419, la 429
  y la 500 tampoco.
- **Pantallas y estados (accesibilidad):** «Volver al inicio» es el primer enlace del formulario
  (orden de tabulación tras el logo-enlace en el escritorio); el logo-enlace se llama «Ir a la página de
  inicio»; el aviso de sesión cerrada usa `role="status"` y no mueve el foco; cada página de error tiene
  un `h1`; todos los objetivos de toque ≥ `--touch-min` (44 px).
- **Qué ve cada rol** en la página de inicio con sesión y en las páginas de error: `design.md` →
  «Qué ve quien tiene sesión» y «Salidas de cada página de error». Cada fila tiene su test (Trazabilidad).

## Estrategia de pruebas
- **PHPUnit Feature** contra `dentissa_test` con `DatabaseTransactions` y el escenario de
  `Concerns/EscenarioEstadosCita` (los cuatro roles):
  - `NavegacionSalidasTest`: HTML de cada pantalla de acceso (enlace `route('inicio')` como primer `<a>`
    de `.form-container` y logo-enlace con su `aria-label`); «Contraseña nueva» con sus dos salidas y el
    token todavía válido tras abrirla; registro con Google abandonado sin cuenta nueva; la entrada del
    panel por rol; `/` con sesión; cierre de sesión.
  - `PaginasErrorTest`: cada código desde rutas de prueba registradas en `setUp()` (patrón de
    `DenegacionesAuditadasTest`: `Route::middleware('web')->get('/_pruebas/044/…')`), más los casos reales
    (dirección inexistente, `/admin` como dentista, el límite de `throttle:registro`), con
    `config(['app.debug' => false])` en la 500. Sin consultas al pintar la 500 (`DB::listen`).
  - Se recorre el DOM con `DOMXPath`, como `MovilPacientesExpedientesTest`.
- **Tests de JS:** no aplica, no hay JavaScript nuevo.
- **Navegador:** `node docs/design/capturar.mjs --medir` en la verificación final de `/implement` y en
  `/review`, porque se toca `layouts/app.blade.php` (AGENTS.md).
- **Verificación manual del usuario** (diseño directo): cada pantalla a 320, 390 y 1440 px, en la tarea
  que la construye.
- Los tests nuevos de interfaz se ejecutan dos veces (`shared/lanes.md` §2, `/implement`).

## Modelo de amenazas
La spec toca la autenticación (cierre de sesión) y las respuestas de error y de acceso denegado.

| ID | Amenaza (STRIDE) | Categoría OWASP | Componente | Control | Test |
|---|---|---|---|---|---|
| TM1 | Tampering/Spoofing: un parámetro de vuelta en la dirección (`?volver=https://…`) convierte los enlaces nuevos en redirección abierta | A01:2025 | enlaces de vuelta, `logout()`, `RegisterController` | Destinos fijos con `route()`; ningún código lee un destino de la petición ni del `Referer` | `PaginasErrorTest::test_los_enlaces_de_vuelta_ignoran_un_destino_externo` (CA11) |
| TM2 | Information disclosure: la página de error muestra trazas, rutas, consultas, versiones o el texto de la excepción | A10:2025 | `errors/*.blade.php` | La 500, la 404, la 419 y la 429 no imprimen el mensaje de la excepción; la 403 solo el de `Response::deny()` y nunca el genérico; `APP_DEBUG=false` en producción (`verificar.sh`) | `PaginasErrorTest::test_la_500_no_muestra_detalles_internos` (amplía `ErroresProduccionTest`) y `::test_la_404_no_muestra_el_mensaje_de_la_excepcion` |
| TM3 | Information disclosure: distinguir «no existe» de «existe pero es de otro» (403 frente a 404) | A01:2025 | `errors/404`, `errors/403` | Los códigos no cambian (un Gate con `denyAsNotFound` sigue en 404); la 404 es la misma página, con o sin `AuthorizationException` previa, y su cuerpo no lleva el identificador de la petición (es el mismo en cada petición; `DenegacionesAuditadasTest::test_la_cita_ajena_y_la_inexistente_responden_igual` compara el contenido) | `PaginasErrorTest::test_la_404_de_un_recurso_ajeno_es_igual_a_la_de_uno_inexistente` (CA11, caracterización del código) |
| TM4 | Information disclosure: el enlace nuevo de «Contraseña nueva» filtra el token por el `Referer` | A07:2025 | `auth/recuperar/nueva.blade.php` | La página conserva `referrer: no-referrer`; los enlaces nuevos van al propio sitio y no llevan el token | `NavegacionSalidasTest::test_contrasena_nueva_conserva_no_referrer_y_sus_salidas_no_llevan_el_token` (CA3) |
| TM5 | Information disclosure: tras cerrar sesión, Atrás o una caché muestran el panel, o el aviso de una persona le sale a otra | A07:2025 | `logout()`, `PreventBackHistory` | El flash vive en la sesión nueva de esa persona y se consume al pintarse; las respuestas del grupo `web` siguen con `Cache-Control: no-store` | `NavegacionSalidasTest::test_cerrar_sesion_lleva_al_inicio_con_el_aviso_una_sola_vez` y `::test_tras_cerrar_sesion_el_panel_no_se_sirve_de_cache` (CA6) |
| TM6 | Denial of service: la página de error falla por la base de datos caída y Laravel cae en su página genérica | A10:2025 | `errors/500`, plantilla | La plantilla no consulta la base: sesión con `auth()->hasUser()`, solo `config()`, `asset()` y `Context` | `PaginasErrorTest::test_pintar_la_500_no_consulta_la_base` |
| TM7 | Repudiation: la página 403 nueva deja de registrar el acceso denegado | A09:2025 | `bootstrap/app.php` → `RegistroCentralDenegaciones` | Sin cambios en el registro central, que lee el código de la respuesta | `PaginasErrorTest::test_el_acceso_denegado_sigue_en_la_auditoria` (CA8, caracterización) |

## Trazabilidad
| Criterio de aceptación | Cambio(s) | Test(s) |
|---|---|---|
| CA1: «Volver al inicio» en las siete pantallas de acceso | `layouts/acceso`, `acceso.css`, `register`, `registro-contacto`, `auth.css` | Feature `NavegacionSalidasTest::test_las_pantallas_de_acceso_empiezan_por_volver_al_inicio` (data provider con las siete; el enlace es el primer `<a>` de `.form-container`) · verificación manual: sin desplazarse a 320, 390 y 1440 px en cada pantalla, contra `design.md` → Pantallas |
| CA2: el logo es enlace con «Ir a la página de inicio» | `layouts/acceso`, `register`, `registro-contacto` | Feature `NavegacionSalidasTest::test_el_logo_del_acceso_lleva_al_inicio_con_nombre_accesible` · verificación manual: foco visible en el escritorio |
| CA3: «Contraseña nueva» con «Volver a iniciar sesión»; salir no gasta el enlace | `auth/recuperar/nueva` | Feature `NavegacionSalidasTest::test_contrasena_nueva_ofrece_volver_a_iniciar_sesion` y `::test_salir_de_contrasena_nueva_no_gasta_el_enlace` (caracterización: el token sigue abriendo el formulario y la contraseña no cambia; comprobado rompiendo la validez del token) |
| CA4: salir del registro con Google no crea cuenta; volver a empezar no arrastra datos | `registro-contacto` | Feature `NavegacionSalidasTest::test_salir_del_registro_con_google_no_crea_cuenta` (caracterización: sin fila nueva en `usuarios`) y `::test_volver_a_entrar_con_google_reemplaza_los_datos_anteriores` |
| CA5: «Página de inicio» en el panel de los cuatro roles; `/` con sesión muestra la página con «Ir a mi panel» | `layouts/app`, `PaginasPublicasController`, `publico/inicio`, `RegisterController` | Feature `NavegacionSalidasTest::test_el_panel_de_cada_rol_enlaza_la_pagina_de_inicio` (cuatro roles), `::test_la_pagina_de_inicio_con_sesion_ofrece_ir_a_mi_panel` (cabecera, `.pub-acceso` y los dos botones), `::test_el_registro_con_sesion_lleva_al_panel`; `PaginaInicioTest` (antes 038 CA2) actualizado · verificación manual: la entrada dentro del menú del móvil a 320 y 390 px, y `capturar.mjs --medir` |
| CA6: cerrar sesión lleva al inicio con «Has cerrado sesión»; Atrás no muestra el panel | `AuthController`, `layouts/publico`, `publico.css` | Feature `NavegacionSalidasTest::test_cerrar_sesion_lleva_al_inicio_con_el_aviso_una_sola_vez` (302 a `/`, aviso con `role="status"`, cabecera con «Iniciar sesión» y «Crear cuenta», sin aviso al recargar) y `::test_tras_cerrar_sesion_el_panel_no_se_sirve_de_cache` · verificación manual: aviso en la primera pantalla a los tres anchos |
| CA7: 404 propia con salidas según la sesión y `noindex` | `errors/404`, `errors/plantilla`, `routes/web.php` | Feature `PaginasErrorTest::test_una_direccion_inexistente_sin_sesion_ofrece_inicio_login_y_registro` y `::test_una_direccion_inexistente_con_sesion_ofrece_ir_a_mi_panel` (404, `h1`, enlaces, `<meta name="robots" content="noindex">`) · verificación manual: a los tres anchos |
| CA8: 403 propia con la explicación, panel e inicio; sigue auditada | `errors/403` | Feature `PaginasErrorTest::test_la_denegacion_por_rol_sigue_siendo_403_con_su_mensaje` (`/admin` como dentista y la cancelación de `EstadosCitaRolesTest` con su mensaje) y `::test_el_acceso_denegado_sigue_en_la_auditoria` (caracterización) |
| CA9: 419 «La página caducó» con sus dos salidas | `errors/419` | Feature `PaginasErrorTest::test_un_formulario_caducado_ofrece_volver_a_iniciar_sesion` (ruta de prueba que lanza `TokenMismatchException`) |
| CA10: 429 y 500 en español con enlace al inicio y la misma respuesta | `errors/429`, `errors/500` | Feature `PaginasErrorTest::test_demasiados_intentos_explica_que_esperar` (ruta de prueba que lanza la excepción de límite: la 429 es preventiva, /analyze A2) y `::test_los_limites_actuales_siguen_con_su_propio_aviso` (`throttle:registro` sigue volviendo al formulario con su mensaje y `abrir-enlace` con su propia vista) y `::test_la_500_no_muestra_detalles_internos` |
| CA11 (abuso): destinos fijos; sin detalles internos; mismos códigos | todos los enlaces nuevos, `errors/*`, `routes/web.php` | Feature `PaginasErrorTest::test_cualquier_verbo_a_una_direccion_inexistente_sigue_en_404` (GET, POST y DELETE; `RecuperarContrasenaTest` sin cambios), `::test_los_enlaces_de_vuelta_ignoran_un_destino_externo`, `::test_la_404_no_muestra_el_mensaje_de_la_excepcion`, `::test_la_404_de_un_recurso_ajeno_es_igual_a_la_de_uno_inexistente` |
| CA12: `/up` con `X-Robots-Tag: noindex` y 200 | `NoIndexarSalud`, `bootstrap/app.php` | Feature `PaginasErrorTest::test_la_comprobacion_de_salud_no_se_indexa` |
| TM1: redirección abierta por un destino de vuelta | enlaces de vuelta, `logout()`, `RegisterController` | Feature `PaginasErrorTest::test_los_enlaces_de_vuelta_ignoran_un_destino_externo` |
| TM2: detalles internos en las páginas de error | `errors/*` | Feature `PaginasErrorTest::test_la_500_no_muestra_detalles_internos` y `::test_la_404_no_muestra_el_mensaje_de_la_excepcion` |
| TM3: distinguir recurso ajeno de inexistente | `errors/404` | Feature `PaginasErrorTest::test_la_404_de_un_recurso_ajeno_es_igual_a_la_de_uno_inexistente` |
| TM4: el token de «Contraseña nueva» por el `Referer` | `auth/recuperar/nueva` | Feature `NavegacionSalidasTest::test_contrasena_nueva_conserva_no_referrer_y_sus_salidas_no_llevan_el_token` |
| TM5: el panel o el aviso desde Atrás o una caché | `logout()`, `PreventBackHistory` | Feature `NavegacionSalidasTest::test_cerrar_sesion_lleva_al_inicio_con_el_aviso_una_sola_vez` y `::test_tras_cerrar_sesion_el_panel_no_se_sirve_de_cache` |
| TM6: la página de error depende de la base | `errors/plantilla`, `errors/500` | Feature `PaginasErrorTest::test_pintar_la_500_no_consulta_la_base` |
| TM7: el 403 deja de auditarse | `RegistroCentralDenegaciones` (sin cambios) | Feature `PaginasErrorTest::test_el_acceso_denegado_sigue_en_la_auditoria` |
| RNF: WCAG 2.1 AA (foco, contraste, toque ≥ 44 px, aviso sin mover el foco, un `h1`) | todas las vistas tocadas | Feature: atributos (`role="status"`, `aria-label`, un solo `h1`) en los tests de arriba · verificación manual: `/review` contra `system.html` y `capturar.mjs --medir` |
| RNF: descubribilidad (`/` y `/registro` indexables y sin cambios; errores y salud no) | `errors/plantilla`, `NoIndexarSalud` | Feature `PaginasPublicasTest` y los tests de `/registro` de la 041 sin cambios; `PaginasErrorTest` (CA7, CA12) · manual: Lighthouse SEO ≥ 90 de `/` y `/registro` en `/review` |
| RNF: las páginas de error no dependen de la base (acotado a la plantilla, /analyze A8) | `errors/plantilla` | Feature `PaginasErrorTest::test_pintar_la_500_no_consulta_la_base` (TM6); el camino de `RegistroCentralDenegaciones` con la base caída va al roadmap (T091) |
| RNF: P9 comprobable (toque ≥ 44 px, sin literales, sin `style=""` nuevos; /analyze A6) | CSS y vistas tocadas | Feature `NavegacionSalidasCssTest` |

## Observabilidad
- Logs nuevos: ninguno.
- Eventos de auditoría: el `acceso_denegado` de los 403 sigue saliendo de `RegistroCentralDenegaciones`,
  sin cambios (spec 020) → test `PaginasErrorTest::test_el_acceso_denegado_sigue_en_la_auditoria`. El
  cierre de sesión sigue sin auditarse (pendiente en `docs/observability.md`, objetivo 3): excepción P7 aprobada por el usuario (Constitution Check).
- Métricas y alertas: sin cambios. La alerta de 5xx de la 032 sigue contando las 500 igual, porque el
  código no cambia.
- Cómo se verifica tras el deploy: `deploy/verificar.sh`, cuya comprobación «cabeceras de
  /no-existe-verificacion» ya recorre la 404 nueva, y en producción: `/no-existe` muestra la página
  propia; `curl -I https://dentissa.app/up` lleva `X-Robots-Tag: noindex`; entrar con una cuenta de
  prueba, abrir «Página de inicio» y cerrar sesión.

## Descubribilidad
| Ruta | Indexable | Título | Descripción | Canónica | Sitemap | Datos estructurados | Test |
|---|---|---|---|---|---|---|---|
| `/` | sí, sin cambios (los buscadores llegan sin sesión; el aviso solo sale tras cerrar sesión) | el de la 038, sin cambios | la de la 038 | `https://dentissa.app/` | — (SE3, fuera) | los de la 038, sin cambios | `PaginasPublicasTest` sin cambios |
| `/registro` | sí, sin cambios (solo se añade el enlace) | el actual | la actual | la actual | — | — | tests de `/registro` existentes |
| páginas de acceso de la 041 | no (`noindex` del layout, sin cambios) | sin cambios | — | — | no | — | tests de la 041 sin cambios, salvo el del logo del panel (`RecuperarContrasenaVistasTest`, A3) |
| 404, 403, 419, 429, 500 | no (`<meta name="robots" content="noindex">`) | «No encontramos esta página · Dentissa», etc. | — | — | no | — | `PaginasErrorTest` (CA7) |
| `/up` | no (`X-Robots-Tag: noindex`) | — | — | — | no | — | `PaginasErrorTest::test_la_comprobacion_de_salud_no_se_indexa` |

- Renderizado de las rutas nuevas: igual que `seo.rendering` (SSR, Blade).
- Imagen para compartir: no aplica (sin rutas indexables nuevas).
- Brechas `SE` que atiende: SE4.a dentro; SE2.b dentro; SE2.a ya mitigada (v0.14.0); SE3.a y SE3.b fuera
  → objetivo 7.

## Rollout
- Feature flag: no aplica (decisión del usuario, 2026-11-08): son cambios de navegación de bajo riesgo,
  sin datos ni migraciones, y la vuelta atrás es inmediata.
- Orden de despliegue: un solo paso, `deploy/desplegar.sh vX.Y.Z` (no hay migraciones). Versión
  **minor** (comportamiento nuevo): v0.15.0 si la 044 sale antes que la 039 (decisión del usuario: la
  044 va primero).
- Antes del `/release`: el `/chore` de Symfony 7.4.23 (EX4 vence el 2026-11-11).
- Compatibilidad: sin estado compartido entre versiones; durante el despliegue, una petición puede ver la
  página anterior o la nueva. La clave flash `sesion_cerrada` la ignora la versión anterior.
- Rollback: `bash deploy/volver.sh <tag anterior>` **sin copia** (no hay migraciones), unos 22 s.
- Métricas a vigilar tras el deploy: la alerta de 5xx (spec 032), `verificar.sh` sin fallos, el log de
  nginx sin un aumento de 404/500 en la primera hora, y la comprobación manual de arriba.

## Decisiones (→ ADR si son arquitectónicas)
- **D1 · Dónde van las salidas del acceso.** (a) En cada vista; (b) en `layouts/acceso` y en las dos
  vistas del registro. → **(b)**: tres sitios en lugar de siete y el mismo orden en todas.
- **D2 · Plantilla de las páginas de error.** (a) El layout del acceso; (b) la carcasa del error genérico
  de la 020 (`errores.css`); (c) el layout público. → **(b)**: ya es la página de error de la marca, con
  `noindex`, solo recursos propios y sin sesión ni base (`design.md`). La página 503 de la 020 no se
  migra a la plantilla nueva: sería un refactor de una spec liberada (queda como posible `/refactor`).
- **D3 · 404 con sesión.** Una dirección que no coincide con ninguna ruta no pasa por el grupo `web`, así
  que la vista no ve la sesión. `Route::fallback` solo registra GET, y Laravel cuenta la ruta de reserva al
  buscar otros verbos: con ella sola, un POST a una dirección inexistente pasaría a 405 (/analyze A1;
  `RecuperarContrasenaTest` lo asierta con 404). → **Dos rutas de reserva:** GET/HEAD dentro de `web`
  (la 404 sabe si hay sesión) y POST, PUT, PATCH, DELETE y OPTIONS sin el grupo `web` (como la ruta de
  diseño de la 020, con `withoutMiddleware`), para que no pase por la comprobación CSRF y no responda
  419. Las rutas de reserva se resuelven siempre las últimas, así que las rutas de prueba de los tests
  siguen funcionando. Comprobación: tests con GET, POST y DELETE a una dirección inexistente (404 y la
  página propia), `RecuperarContrasenaTest` sin cambios y, tras el despliegue, `curl -X POST` a una
  dirección inexistente (404), porque en los tests la comprobación CSRF está desactivada.
- **D4 · Sesión en las páginas de error.** `auth()->hasUser()` (el usuario ya resuelto por `auth` o por
  `ContextoActor`) en lugar de `auth()->check()`, que podría consultar la base durante un fallo de esta
  (TM6).
- **D5 · Mensaje de la 403.** Se conserva el mensaje de `Response::deny()` (lo asierta
  `EstadosCitaRolesTest`) y se descarta el vacío y el genérico de Laravel (implícita de la spec, CA8).
- **D6 · `X-Robots-Tag` de `/up`.** (a) En nginx (`docker/nginx/`, dos archivos y ruta de infraestructura);
  (b) un middleware global de Laravel. → **(b)**: se prueba con PHPUnit y no toca la configuración de
  nginx.
- **D7 · Registro con sesión.** Redirige al panel del rol con `panelDelRol()`, en lugar de a `/`, para
  evitar el bucle registro → inicio una vez que `/` deja de redirigir.
- **D8 · Tokens en `auth.css`.** El registro no carga `app.css`; los tokens que usa `.enlace-inicio` se
  copian a su `:root` con los mismos valores (sin valores nuevos, P9). Unificar el registro con el acceso
  es deuda aparte (DS).
- **D9 · Tamaño de la spec.** 12 criterios y 5 specs extendidas, por encima del aviso; se mantiene junta
  porque el usuario ya la dividió en 044/045/046 (decisión del usuario, 2026-11-08). Con los archivos
  leídos: unos 20 archivos de código y vistas en 4 zonas (acceso, panel, sitio público, errores).
- **D10 · Orden frente a la 039.** La 044 va primero; la 039 se planea después sobre `main` con la 044
  fusionada (comparten `layouts/app.blade.php` y `layouts/publico.blade.php`) (decisión del usuario, 2026-11-08).
- Sin ADR: no hay dependencias nuevas ni cambio de arquitectura.

## Impacto en arquitectura
Al implementar (T090), en `docs/architecture.md`:
- vistas: `resources/views/errors/` con la plantilla común y las cinco páginas; `Route::fallback`;
- middleware global `NoIndexarSalud`;
- flujo de sesión: el cierre lleva a `/` y `/` ya no redirige con sesión.

En `docs/seo.md`: SE2.b y SE4.a como `mitigada (vX.Y.Z)` al liberar; filas de la tabla de rutas para
`/up` y la 404. En `docs/design/system.md`: «Estados de pantalla → Error del servidor» y DS7.

## Riesgos y mitigaciones
- **Las páginas de error valen para todo el sitio:** un fallo en la plantilla rompería todas. Mitigación:
  test por código y la 500 pintada sin base ni sesión (TM6).
- **Quitar la redirección de `/` cambia una costumbre:** quien tenga `/` en favoritos verá la página de
  inicio en lugar de su panel. Mitigación: «Ir a mi panel» en la cabecera y en la portada; el inicio de
  sesión sigue llevando al panel.
- **Menú del móvil (spec 033):** una entrada más en la barra lateral puede romper el alto del menú a
  320 px. Mitigación: `capturar.mjs --medir` y la verificación del usuario.
- **La 039 toca los mismos layouts:** conflicto de fusión si se adelanta. Mitigación: D10.
- **Sesiones abiertas por direcciones inexistentes (/analyze A13):** con la reserva GET dentro de `web`, cada
  404 abre sesión (archivo y `Set-Cookie`), como ya hacen `/`, `/login` o `/registro` con cualquier visita.
  Aceptado: no añade un camino nuevo de consumo y la limpieza de sesiones de Laravel los borra; los
  verbos distintos de GET no abren sesión (D3).
- **Base caída en una denegación (/analyze A8):** `RegistroCentralDenegaciones` (spec 020) lee el usuario de
  la sesión en cada respuesta de error y, con la base caída, podría lanzar antes de pintar la página. Es
  previo a la 044 y queda fuera (decisión del usuario): T091 lo lleva al roadmap.
- **EX4 vence el 2026-11-11:** un `/release` posterior sin el `/chore` de Symfony queda bloqueado
  (P6). Mitigación: el `/chore` va antes (Rollout).
