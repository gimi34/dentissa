---
spec: 044-navegacion-salidas
verdict: changes_requested
round: 1
date: 2026-11-09
base: b7173e9
head: d321a10
human_signoff: pending
---

# Review · 044 Salidas de las pantallas

## Resumen
`changes_requested`. La implementación cumple los doce criterios: la suite y los escáneres están en
verde, y la medición del menú en el navegador pasa para los cuatro roles. Hay un bloqueante de
accesibilidad (P9): el foco del logo-enlace no se ve sobre el panel de marca oscuro del acceso y del
registro. Hay además cuatro importantes: el aviso de estado puede no anunciarse, dos tests no protegen
lo que dicen (las rutas de reserva sin `web` y la 404 con sesión real) y la 403 imprime cualquier
mensaje 403. Revisión hecha con cinco subagentes independientes (spec, constitución y calidad,
seguridad, accesibilidad y SEO); cada hallazgo se verificó en el código.
Conteo: bloqueante 1 · importante 4 · menor 17

## Verificación automática
| Comando | Resultado |
|---|---|
| `php artisan test` (suite completa) | ✅ 771 en verde (12 722 aserciones); línea base 721 |
| tests nuevos, segunda ejecución (`NavegacionSalidasTest`, `NavegacionSalidasCssTest`, `PaginasErrorTest`) | ✅ 50 en verde (441 aserciones) |
| `node --test tests/js/publico.test.mjs` | ✅ 15/15 |
| `vendor/bin/pint --test` (13 PHP tocados) | ✅ |
| `aidd.py validate` | ✅ 0 errores |
| Menú en el navegador (sustituto dirigido de `capturar.mjs --medir`, decisión del usuario en T040) | ✅ cuatro roles a 320, 390 y 1440: entrada «Ver sitio web» visible y alcanzable en el menú abierto, toque ≥ 44 × 44 (251–279 × 44), «Cerrar sesión» alcanzable, sin desplazamiento horizontal, Escape cierra y devuelve el foco a «Menú», y la entrada lleva a `/` con «Ir a mi panel». Playwright 1.63.0 contra una vista previa de la rama sobre `dentissa_test`, con cuentas temporales ya borradas. No se recreó `dentissa_capturas`: el `--medir` completo queda pendiente (ver R2 y Aceptados) |

## Criterios de aceptación
| CA | Test | Estado | Nota |
|---|---|---|---|
| CA1 | `NavegacionSalidasTest::test_las_pantallas_de_acceso_empiezan_por_su_salida` | ✅ | siete pantallas, con la salida de cada una (D11) |
| CA2 | `NavegacionSalidasTest::test_el_logo_del_acceso_lleva_al_inicio_con_nombre_accesible` | ⚠ | el enlace y su nombre cumplen; su foco no se ve sobre el panel oscuro (R1) |
| CA3 | `NavegacionSalidasTest::test_contrasena_nueva_ofrece_volver_a_iniciar_sesion`, `::test_salir_de_contrasena_nueva_no_gasta_el_enlace` | ✅ | |
| CA4 | `NavegacionSalidasTest::test_salir_del_registro_con_google_no_crea_cuenta`, `::test_volver_a_entrar_con_google_reemplaza_los_datos_anteriores` | ✅ | |
| CA5 | `NavegacionSalidasTest::test_el_panel_de_cada_rol_enlaza_la_pagina_de_inicio`, `::test_la_pagina_de_inicio_con_sesion_ofrece_ir_a_mi_panel`, `::test_el_registro_con_sesion_lleva_al_panel`, `MovilPacienteTest` | ✅ | medido además en el navegador |
| CA6 | `NavegacionSalidasTest::test_cerrar_sesion_lleva_al_inicio_con_el_aviso_una_sola_vez`, `tests/js/publico.test.mjs` | ⚠ | el aviso va marcado como estado, pero un `role="status"` que ya trae su texto al cargar no se anuncia de forma fiable (R2) |
| CA7 | `PaginasErrorTest::test_una_direccion_inexistente_*` | ⚠ | cumple; el caso con sesión usa `actingAs` y no prueba la resolución real de la sesión (R4) |
| CA8 | `PaginasErrorTest::test_la_denegacion_por_rol_sigue_siendo_403_con_su_mensaje`, `::test_el_acceso_denegado_sigue_en_la_auditoria` | ⚠ | cumple; la vista imprime cualquier mensaje 403, no solo el de `Response::deny()` (R5) |
| CA9 | `PaginasErrorTest::test_un_formulario_caducado_ofrece_volver_a_iniciar_sesion` | ✅ | |
| CA10 | `PaginasErrorTest::test_demasiados_intentos_explica_que_esperar`, `::test_los_limites_actuales_siguen_con_su_propio_aviso`, `::test_la_500_no_muestra_detalles_internos` | ✅ | |
| CA11 | `PaginasErrorTest::test_cualquier_verbo_a_una_direccion_inexistente_sigue_en_404`, `::test_los_enlaces_de_vuelta_ignoran_un_destino_externo`, `::test_la_404_*` | ⚠ | cumple; el test de verbos no protege `withoutMiddleware('web')` ni cubre PUT, PATCH, HEAD y OPTIONS (R3) |
| CA12 | `PaginasErrorTest::test_la_comprobacion_de_salud_no_se_indexa` | ✅ | |

## Conformidad con el plan
- Cambios fuera del plan: ninguno (`scope.md`: 0 archivos sin tarea; T001 solo toca `docs/design/`).
- Partes del plan sin implementar: ninguna. El `capturar.mjs --medir` completo se sustituyó por la medición dirigida del menú (decisión del usuario).

## Constitution Check (sobre el código)
| Principio | Resultado | Evidencia |
|---|---|---|
| P1 Spec aprobada | ✅ | spec, plan y tareas aprobados; `/analyze` r6 `pass` |
| P2 Test que falla antes | ⚠ | cada CA con test y las roturas anotadas; R3 y R4: dos tests pasarían con lo protegido roto |
| P3 Autorización | ✅ | sin cambios de permisos; 403 por rol con su código (`EstadosCitaRolesTest`, `DenegacionesAuditadasTest`) |
| P4 Entrada y salida | ✅ | semgrep 0; salida escapada (`@section` con `e()`); sin `{!! !!}` |
| P5 Nada sensible | ✅ | gitleaks sin secretos |
| P6 Vulnerabilidades | ✅ | composer audit solo con EX4 (vigente hasta el 2026-11-11); npm audit y trivy sin hallazgos |
| P7 Auditoría | ✅ (con excepción aceptada) | 403 auditado y probado; el logout, en la excepción P7 aprobada por el usuario |
| P8 Correlación | ✅ | `X-Request-Id` en las cinco páginas de error; sin `Log::` nuevos |
| P9 Sistema de diseño y WCAG AA | ❌ | R1: foco del logo-enlace por debajo de 3:1 sobre el panel oscuro |
| P10 Descubribilidad | ✅ | revisor de SEO: `/` y `/registro` indexables sin cambios; 404 y `/up` con `noindex` |

## Seguridad
| Herramienta | Resultado |
|---|---|
| gitleaks v8.21.2 (`b7173e9..HEAD` y `origin/main..HEAD`) | ✅ sin secretos |
| semgrep 1.99.0 `p/php` (PHP tocados) | ✅ 0 hallazgos |
| composer audit / npm audit | crít: 0 · alta: 0 (6 avisos de Symfony sin gravedad, ignorados por EX4, vigente) · npm 0 |
| trivy 0.57.1 `fs` | ✅ sin HIGH ni CRITICAL |

| Tema OWASP | Resultado | Evidencia |
|---|---|---|
| A01:2025 Control de acceso (redirecciones, 403/404, verbos) | ✅ | destinos fijos con `route()`; códigos sin cambios; TM1 y TM3 probados |
| A02:2025 Configuración (cabeceras, `noindex`, debug) | ✅ | cabeceras de nginx con `always`; `NoIndexarSalud`; `APP_DEBUG=false` en `verificar.sh` |
| A04:2025 Datos en URL / Referer | ✅ | «Contraseña nueva» conserva `no-referrer`; TM4 probado |
| A05:2025 Inyección / XSS | ✅ | salida escapada; semgrep 0 |
| A07:2025 Sesiones y caché | ✅ | `invalidate()` antes del flash; `no-store`; TM5 probado |
| A08:2025 CSRF | ✅ | la reserva sin `web` solo responde 404, sin estado; R3 pide que un test lo fije |
| A09:2025 Registro | ✅ | `RegistroCentralDenegaciones` sin cambios; TM7 probado |
| A10:2025 Manejo de errores | ⚠ | R5 (403 con cualquier mensaje); R21 (sesiones de las 404) |

## Observabilidad
- Eventos de auditoría de la spec emitidos y probados: sí. El `acceso_denegado` de los 403 se sigue emitiendo (`PaginasErrorTest::test_el_acceso_denegado_sigue_en_la_auditoria`, comprobado rompiendo). El logout queda en la excepción P7.
- Datos sensibles en logs del código tocado: ninguno (sin `Log::` nuevos).
- `request_id` presente en los logs nuevos: no hay logs nuevos; `X-Request-Id` en todas las respuestas de error.

## SEO
| Tema | Resultado | Evidencia |
|---|---|---|
| 2. Páginas de error y códigos | ✅ | `/no-existe-044` → 404 propia en español; `/admin` sin sesión → 302 a `/login` |
| 3. Indexación y canónica | ✅ | `noindex` en `/login` y la 404, `X-Robots-Tag` en `/up`; `/`, `/registro` y `/servicios` sin `noindex` y con sus metadatos de antes |
| 4. Metadatos y estructura | ✅ | títulos y descripciones sin cambios; un `h1` por página; `lang="es"` |
| 1. Rastreo (`robots.txt`, sitemap, enlaces) | ✅ / ➖ | enlaces `<a href>` rastreables; sin sitemap (SE3, fuera de la spec) |
- Sección "Descubribilidad" del plan aplicada: sí (`/`, `/registro`, la 404 y `/up`). Respuestas guardadas con `curl` desde la vista previa de la rama.

## Hallazgos
| ID | Severidad | Archivo:línea | Hallazgo | Sugerencia |
|---|---|---|---|---|
| R1 | bloqueante | `public/css/acceso.css:155,172`; `public/css/auth.css:355` | El logo-enlace del panel de marca (acceso y registro, desde 769 px) usa el foco del sistema, `--focus-ring` = `--p600`, sobre un degradado que empieza en ese mismo `#993556` y pasa por `#7A1F3A`: contraste ~1–1,4:1, por debajo de 3:1 (WCAG 1.4.11, P9). `errores.css` sí usa `--focus-ring-inverso` para el mismo componente. Ningún test lo cubre | `.logo-enlace:focus-visible` con `--focus-ring-inverso` (blanco, el valor de `app.css`) en `acceso.css` y `auth.css`; un test en `NavegacionSalidasCssTest` comprueba que el foco del logo-enlace de las tres hojas usa `--focus-ring-inverso`; `system.md` lo precisa |
| R2 | importante | `resources/views/layouts/publico.blade.php:62` | `<p role="status">` llega con su texto en el HTML inicial: los lectores de pantalla anuncian cambios en una región viva, no su contenido al cargar, y a los 6 s sale del árbol de accesibilidad. CA6 se cumple en la marca, pero no necesariamente en el efecto (la comprobación con NVDA estaba pendiente) | La región se monta vacía y `publico.js` escribe «Has cerrado sesión» tras la carga (un único cambio que se anuncia); sin JavaScript, el texto del servidor. Test de JS que comprueba que el texto se escribe en la región y verificación con NVDA |
| R3 | importante | `tests/Feature/PaginasErrorTest.php:75-80`; `routes/web.php:200` | `test_cualquier_verbo_a_una_direccion_inexistente_sigue_en_404` solo prueba GET, POST y DELETE, y en los tests la comprobación CSRF está desactivada: quitar `withoutMiddleware('web')` o un verbo del `match` lo deja en verde (en producción, un POST daría 419) | Recorrer los siete verbos y comprobar que la ruta resuelta para POST no lleva `StartSession` ni la comprobación CSRF en su middleware (`gatherRouteMiddleware`) |
| R4 | importante | `tests/Feature/PaginasErrorTest.php:64-71` | El caso con sesión de la 404 usa `actingAs`, que pone al usuario en el guard directamente: seguiría en verde aunque la reserva GET dejara de pasar por `web` (premisa de D3 y D4) | Iniciar sesión de verdad (`post('/login')`) y pedir después la dirección inexistente |
| R5 | importante | `resources/views/errors/403.blade.php:5-8` | La 403 imprime el mensaje de cualquier excepción 403 salvo el genérico, no solo el de `Response::deny()` como dice TM2: un `abort(403, '…dato…')` futuro o una excepción 403 de un paquete saldría en la página. Hoy ningún `abort(403)` lleva mensaje | Mostrar la explicación solo si la excepción previa es una `AuthorizationException` (y no la genérica); test con `abort(403, DETALLE)` que no muestra el detalle |
| R6 | menor | `public/js/publico.js:142` | Solo `transitionend` pone `hidden`; si la transición no llega a ocurrir, el aviso queda invisible pero ocupa sitio y se lee | Temporizador de respaldo que lo oculta pasado `--duration-base` |
| R7 | menor | `public/css/publico.css:137` | `.pub-aviso` usa `padding: var(--space-3) var(--gutter)` y `system.md`/`design.md` documentan `--space-3`/`--space-4` | Alinear código o documento |
| R8 | menor | `resources/views/layouts/publico.blade.php:62` | El aviso queda entre `</header>` y `<main>`, fuera de toda región de referencia | Moverlo al principio de `<main>` |
| R9 | menor | `public/css/publico.css:141` | Al pasar a `hidden`, la franja desaparece de golpe y el contenido sube a los 6 s (salto de diseño tardío) | Animar también la altura o documentarlo en M-1 |
| R10 | menor | `tests/Feature/NavegacionSalidasTest.php:251-252` | CA6 dice «la cabecera ofrece…», pero `enlacesA` busca en toda la página (el pie y la portada tienen los mismos enlaces) | Limitar el XPath a `header.pub-bar` |
| R11 | menor | `tests/Feature/PaginasErrorTest.php:113` | La explicación de CA8 solo se prueba con una ruta sintética, sin recorrer un `Gate::authorize` real hasta la 403 | Caso con la ruta real de reprogramar como dentista |
| R12 | menor | `tests/Feature/PaginaInicioTest.php:246-247` | `assertSee('Ir a mi panel')` busca en toda la página, y la cabecera ya lo pinta | Restringir a `.pub-acceso` o anotar que lo cubre `NavegacionSalidasTest` |
| R13 | menor | `tests/Feature/NavegacionSalidasTest.php:114,141` | Las caracterizaciones de CA3 y CA4 piden `/` y `/login` a mano en lugar de seguir el `href` de la salida | Seguir el `href` del primer enlace de `.form-container` |
| R14 | menor | `tests/Feature/NavegacionSalidasCssTest.php:60` | `test_las_reglas_nuevas_solo_usan_tokens` solo mira 4 selectores (fuera quedan `.logo-enlace`, `.enlaces-secundarios`, `:hover`, `:focus-visible`, `svg`) | Ampliar `reglasNuevas()` |
| R15 | menor | `tests/Feature/NavegacionSalidasCssTest.php:93` | Compara con `app.css` 5 de los 10 tokens copiados en `auth.css` | Comparar la lista completa |
| R16 | menor | `tests/Feature/NavegacionSalidasCssTest.php:22` | `ESTILOS_PREVIOS` no incluye `auth/recuperar/solicitud`, `enviada` ni `caducado` | Añadirlas con su recuento |
| R17 | menor | `tests/Feature/NavegacionSalidasTest.php:286-317`, `tests/Feature/PaginasErrorTest.php:251-303` | `xpath()`, `ruta()` y `enlacesA()` duplicados, y `enlacesA` con semántica distinta (contiene frente a igual) | Trait en `tests/Feature/Concerns/` |
| R18 | menor | `tests/Feature/MovilPacienteTest.php:36-37` | Dos docblocks seguidos sobre la misma constante | Unirlos |
| R19 | menor | `public/js/publico.js:135,148` | `avisoSinMovimiento` repite la consulta `matchMedia` de `reducido` | Calcularla una vez |
| R20 | menor | `app/Http/Controllers/PaginasPublicasController.php:24` | `$panel` se calcula en el controlador y en el `@php` de `layouts/publico` | Un solo origen o anotar el motivo |
| R21 | menor | `routes/web.php:199` | La reserva GET en `web` abre sesión (archivo y `Set-Cookie`) en cada 404 anónima; el plan lo acepta pero no lo registra como consumo de recursos (A10:2025) | Registrarlo como riesgo aceptado en el roadmap |
| R22 | menor | `tests/Feature/PaginasErrorTest.php:192-201` | El test de TM6 solo pinta la 500; no la 404 ni la 403, que usan `auth()` | Pintar también la 404 y la 403, con y sin usuario |

## Preparación para release
- Rollback factible: sí. Sin migraciones ni interruptor; `deploy/volver.sh <tag anterior>` sin copia.
- Migraciones compatibles con la versión anterior: no hay migraciones.
- Docs actualizadas (architecture, deployment): sí (T090, T091), y `seo.md` y `roadmap.md`.
- SCA: sin críticas ni altas fuera de EX4, que vence el 2026-11-11. El `/chore` de Symfony 7.4.23 va antes del `/release`.
- Cobertura de riesgos: SE2.b y SE4.a implementadas y probadas; su estado lo cambia `/release`.

## Tareas añadidas
Decisión del usuario (2026-11-09): corregir R1–R5 y los menores R6, R7, R8, R10, R14, R15, R16, R18, R19 y R22.
- T037 ← R1, R14, R15, R16
- T038 ← R1
- T039 ← R2, R6, R8, R10
- T041 ← R2, R6, R7, R8, R19
- T042 ← R3, R4, R5, R22
- T043 ← R5
- T044 ← R18
- T045 ← R1, R2

## Aceptados sin tarea
Aceptados por el usuario el 2026-11-09:
- **R9** → roadmap: la franja del aviso desaparece de golpe a los 6 s y el contenido sube (salto de diseño tardío); animar la altura o reservar el hueco
- **R11** → roadmap: la explicación de la 403 se prueba con una ruta sintética; falta un caso con un `Gate::authorize` real (reprogramar como dentista)
- **R12** → roadmap: `PaginaInicioTest` busca «Ir a mi panel» en toda la página; lo cubre con precisión `NavegacionSalidasTest`
- **R13** → roadmap: las caracterizaciones de CA3 y CA4 no siguen el `href` de la salida
- **R17** → roadmap: `xpath()`, `ruta()` y `enlacesA()` duplicados en dos tests, con semántica distinta; llevarlos a un trait
- **R20** → roadmap: `$panel` se calcula en `PaginasPublicasController` y en `layouts/publico`
- **R21** → roadmap: la reserva GET en `web` abre sesión en cada 404 anónima (consumo de recursos, A10:2025); vigilar `storage/framework/sessions` o limitar las 404 en Cloudflare
