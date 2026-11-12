---
spec: 045-terminos-privacidad
status: approved   # draft | approved | blocked
constitution_version: 1.3.0
created: 2026-11-12
---

# Plan · 045 Términos de uso y aviso de privacidad

## Enfoque técnico
Dos páginas públicas nuevas, `/terminos` y `/aviso-de-privacidad`, servidas por
`PaginasPublicasController` con `layouts/publico` (cabecera, pie y `publico.css`), como `/servicios` y
`/galeria`. Sus vistas llevan el texto aprobado del candidato `c4` (`design.md`) con la composición de la
página legal; los datos de la clínica (dirección, teléfono y horario) salen de `config('clinica.contacto')`.
Los estilos nuevos y el respaldo de Newsreader entran en `publico.css`, `app.css` y el sistema de diseño
(1.15.0) en la primera tarea. El pie público gana los dos enlaces, y la casilla del registro (normal y con
Google) pasa a «aviso de privacidad», y sus enlaces abren el texto en una ventana (`<dialog>`) sin salir del
registro; el texto de cada página vive en un parcial que usan la página y la ventana (D11). El registro normal,
que hoy solo exige la casilla en el navegador, la valida también en el servidor (CA5, P4). Además, la línea
base de dependencias obliga a subir `phpseclib/phpseclib` a 3.0.58 (decisión del usuario).

## Constitution Check
| Principio | Resultado | Justificación / ajuste | Cómo se verifica |
|---|---|---|---|
| P1 Spec aprobada antes de código | ✅ | Spec 045 y `design.md` aprobados; este plan y `tasks.md` se aprueban antes de `/implement` | manual: `python .ai/bin/aidd.py status` |
| P2 Test que falla antes y pasa después | ✅ | Cada CA con su test (Trazabilidad). Son de **caracterización** la parte de CA5 del registro con Google (ya valida la casilla: se comprueba rompiendo la regla `terminos` de `contactoStore`) y la parte de CA7 que dice que con sesión la página no redirige (se comprueba añadiendo un redirect al método). CA4: el texto, los enlaces y las ventanas con tests de PHPUnit, y el script de las ventanas con `test_el_script_de_las_ventanas_abre_y_cierra` (que abre con `showModal`, cierra con «Cerrar» y pulsando fuera, devuelve el foco y deja navegar si no hay `showModal`); que el navegador lo ejecute así (Escape, foco) se comprueba además a mano en T025 y T095, porque el proyecto no tiene tests de navegador (/analyze A17) | test: Trazabilidad; `/implement` anota el fallo previo o la rotura comprobada |
| P3 Autorización en el servidor | ✅ | Las dos rutas son públicas y sin datos de ninguna cuenta: no hay recurso que autorizar. Con y sin sesión responden igual | test: `TerminosPrivacidadTest::test_con_y_sin_sesion_se_ven_igual` |
| P4 Entrada validada y SQL con bindings | ✅ | Ninguna entrada nueva. El registro normal valida `terminos` en el servidor (`accepted`), como ya hace el de Google. En Blade solo la salida escapada (dobles llaves); el texto fijo de las vistas es HTML de la plantilla, no datos | test: `TerminosPrivacidadTest::test_el_registro_sin_la_casilla_se_rechaza` · `PaginasPublicasTest::test_las_vistas_publicas_no_usan_html_sin_escapar` con las dos vistas nuevas en su lista `VISTAS` (A1) · lint: `semgrep` sobre lo tocado |
| P5 Nada sensible en el repositorio | ✅ | Sin secretos ni datos: el texto usa los datos ficticios de la clínica de `config/clinica.php` | lint: `gitleaks detect --no-banner` |
| P6 Sin vulnerabilidades críticas o altas | ✅ | Línea base del 2026-11-12: `composer audit --locked` con 1 aviso sin severidad en `phpseclib/phpseclib` 3.0.57 (PKSA-h7cz-q7zq-mdkt, DoS por CPU; la CI ya falla). Se sube a 3.0.58 en esta spec (D10), publicada el 2026-11-02: 10 días, cumple la antigüedad mínima de 7 días de `docs/security.md` (A4). `npm audit`: 0. Sin dependencias nuevas | manual: `composer audit --locked` y el comando de la CI sin avisos en la tarea y en `/review` |
| P7 Auditoría | ➖ | No toca datos sensibles, autenticación ni permisos: las páginas son texto fijo. El alta de cuentas aún no se audita (pendiente en `docs/observability.md`, para la spec 021): es código previo que la 045 no cambia (A9) | — |
| P8 Correlación y logs | ➖ | Sin logs nuevos; las rutas nuevas pasan por el middleware global de correlación como las demás públicas | — |
| P9 Sistema de diseño | ✅ | Solo tokens de `system.md`; lo nuevo (`design.md` → «Cambios a incorporar al sistema») entra en `system.md` y `system.html` 1.15.0 en la primera tarea. En el registro se **quitan** los `style=""` de los dos enlaces (pasan a una regla de `auth.css` con `--p500`); el resto del registro es código previo (deuda DS). Las ventanas del registro solo con tokens en `.dialogo-legal` (los que faltan en `auth.css`, copiados de `app.css` con el mismo valor). WCAG 2.1 AA | test: `PaginasPublicasCssTest` (solo tokens en `publico.css`; respaldo de Newsreader; reglas de la página legal; `test_las_vistas_publicas_no_llevan_estilos_en_linea` con las dos vistas y las dos rutas nuevas, A2) · `TerminosPrivacidadTest::test_la_casilla_del_registro` (sin `style` en los enlaces) y `test_la_ventana_del_registro_solo_usa_tokens` · manual: `design/medir.mjs` sobre la app servida en local (puertas y KPIs de `criterios.json`) y verificación del usuario contra `candidatos/c4` a 320, 390 y 1440 px |
| P10 Rutas indexables descubribles | ✅ | Las dos rutas son indexables, con título, descripción y canónica propios, sin `noindex` y con el contenido en el HTML del servidor; `docs/seo.md` las añade a su inventario. `/registro`, la otra ruta indexable, lleva ahora en su HTML el texto de las dos páginas dentro de ventanas cerradas (D6): es contenido de la propia ruta que el visitante puede abrir, no texto oculto para buscadores, y sus metadatos no cambian (/analyze A20) | test: `PaginasPublicasTest::test_metadatos_de_cada_ruta_publica` con las dos rutas y `TerminosPrivacidadTest::test_el_texto_es_el_aprobado` (el texto completo en el HTML del servidor, A5) · manual: Lighthouse SEO ≥ 90 de las dos y de `/registro` en T027 y `/review` |

Resultado: 8 ✅, 2 ➖ y ninguna violación.

## Cambios por módulo
| Módulo | Cambio | Riesgo |
|---|---|---|
| `composer.lock` | `phpseclib/phpseclib` 3.0.57 → 3.0.58 (`composer update phpseclib/phpseclib`, solo ese paquete) | Bajo: versión de parche; la usa Socialite. Los tests del login con Google y la suite completa |
| `docs/design/system.md`, `docs/design/system.html` | 1.15.0: `--font-display` con «Newsreader respaldo» y «Newsreader respaldo Times»; componentes de la página legal (`.lb-legal`, `.lb-texto`, `.lb-num`, título del índice, `.lb-resumen`), con su decisión | Bajo: el valor del token lo prueba `PaginasPublicasCssTest` |
| `public/css/app.css` | `--font-display: "Newsreader", "Newsreader respaldo", "Newsreader respaldo Times", ui-serif, Georgia, serif` | Bajo: afecta a todos los títulos públicos (inicio, servicios, galería) solo mientras carga Newsreader |
| `public/css/publico.css` | Seis `@font-face` de respaldo de Newsreader (tras los de Montserrat) y las reglas de `c4/legal.css` (rejilla de cuatro zonas con corte en 900 px, texto corrido a `--measure`, número de sección, título del índice, «En pocas palabras», `scroll-margin-top`) | Medio: comparte hoja con inicio, servicios y galería; las clases nuevas no tocan las existentes (`.lb-indice--legal`, `.lb-legal`) |
| `routes/web.php` | `GET /terminos` (`terminos`) y `GET /aviso-de-privacidad` (`privacidad`), junto a `/servicios` | Bajo |
| `app/Http/Controllers/PaginasPublicasController.php` | `terminos()` y `privacidad()`, que devuelven sus vistas con el horario en texto (`horarioEnTexto()`, de `config('clinica.contacto.horario')`) | Bajo |
| `resources/views/publico/terminos.blade.php`, `publico/aviso-privacidad.blade.php` (nuevos) | El marcado y el texto de `candidatos/c4`, con `@section('titulo')`, `descripcion` y `canonica`; dirección, teléfono, enlace `tel:` y horario desde la configuración | Medio: el texto aprobado podría desviarse al pasarlo a Blade (test que lo compara con `c4`) |
| `resources/views/layouts/publico.blade.php` | Dos `<li>` al final de `.pub-foot__links`: «Términos de uso» y «Aviso de privacidad» | Bajo: tests del pie existentes |
| `resources/views/auth/register.blade.php`, `auth/registro-contacto.blade.php` | Casilla: «Acepto los términos de uso y el aviso de privacidad de Dentissa»; cada enlace a su ruta, en la misma pestaña, con `data-dialogo` y `aria-haspopup="dialog"`; sin `style=""`; incluyen el parcial de las ventanas | Bajo |
| `resources/views/auth/_dialogos-legales.blade.php` (nuevo) | Las dos ventanas (`<dialog class="dialogo-legal">` con `aria-labelledby`, cabecera con título, fecha y «Cerrar»; el texto con los parciales de D11, `h3` e ids con prefijo) y el script en línea que las abre con `showModal()`, las cierra con «Cerrar» o pulsando fuera (Escape es nativo) y devuelve el foco al enlace; el enlace al otro texto abre su ventana (`candidatos/c4/registro-integracion.html`) | Medio: comportamiento en el navegador, sin test automático (verificación manual) |
| `resources/views/publico/legal/terminos-texto.blade.php`, `legal/privacidad-texto.blade.php` (nuevos) | Las secciones de cada página (del candidato `c4`), con el nivel del título (`h2` en la página, `h3` en la ventana), el prefijo de los ids y si los enlaces entre los dos textos abren la ventana | Bajo: `test_el_texto_es_el_aprobado` sigue vigilando las páginas |
| `public/css/auth.css` | `.page-register .checkbox-label a { color: var(--p500); }` (el color que llevaban en línea); los tokens que copia de `app.css` y las reglas de `.dialogo-legal` (`candidatos/c4/registro-dialogo.css`, sin la regla solo del candidato) | Bajo |
| `app/Http/Controllers/RegisterController.php` | `register()`: regla `'terminos' => ['accepted']` y su mensaje; `contactoStore()`: mensaje nuevo. Texto: «Debes aceptar los términos de uso y el aviso de privacidad.» | Medio: el registro normal pasa a rechazar en el servidor un envío sin la casilla (antes solo lo frenaba el navegador) |
| `tests/Feature/TerminosPrivacidadTest.php` (nuevo) | CA1–CA8, el texto aprobado y las ventanas del registro | — |
| `tests/Feature/PaginasPublicasTest.php` | Las dos rutas en `METADATOS` (y con ello en los tests que la recorren) y las dos vistas en `VISTAS` (salida sin escapar, A1) | Bajo |
| `tests/Feature/PaginasPublicasCssTest.php` | `--font-display` nuevo en `TOKENS` y `EN_EL_SISTEMA`; el filtro de caras de respaldo admite las de Newsreader; test de sus medidas; test de las reglas de la página legal; las dos vistas y las dos rutas en `test_las_vistas_publicas_no_llevan_estilos_en_linea` (A2) | Bajo |
| `tests/Feature/LimiteIntentosTest.php` | `altaValida()` envía `terminos` | Bajo: sin la casilla, el alta válida dejaría de serlo |
| `docs/specs/045-terminos-privacidad/design/criterios.json` | `url` de cada pantalla (`terminos`, `aviso-de-privacidad`) para medir la app servida | Bajo |
| `docs/seo.md`, `docs/architecture.md`, `AGENTS.md` | Inventario de rutas indexables; páginas públicas y validación del registro; layout público (T090) | — |
| `docs/security.md` | Las dos rutas en los puntos de entrada públicos; anota el aviso de `phpseclib` (PKSA-h7cz-q7zq-mdkt) y su corrección con la 3.0.58 (A10) | — |

## Contratos y datos
- **Rutas nuevas** (públicas, `GET`, sin sesión ni límite de intentos, como `/servicios`):
  - `/terminos` → `PaginasPublicasController@terminos` → `publico.terminos`, nombre `terminos`.
  - `/aviso-de-privacidad` → `PaginasPublicasController@privacidad` → `publico.aviso-privacidad`, nombre
    `privacidad`.
  - Respuesta `200` HTML en las dos, con y sin sesión.
- **Contrato que cambia:** `POST /registro` sin `terminos` responde ahora como cualquier error de
  validación (redirección atrás con el error en `terminos` y los datos escritos), sin crear la cuenta. El
  mensaje de `terminos.accepted`, en los dos registros: «Debes aceptar los términos de uso y el aviso de
  privacidad.» Lo afirmaba `LimiteIntentosTest` sin la casilla: se ajusta `altaValida()`.
- **Datos:** ninguno. Sin migraciones ni configuración nueva; la fecha de actualización es un texto fijo de
  cada vista («12 de noviembre de 2026»), que cambia cuando cambia el texto.
- **Pantallas y estados** (de `design.md`): las dos páginas sin sesión, con sesión («Ir a mi panel» en la
  cabecera y el pie) y sin JavaScript; el pie de las páginas públicas; la casilla del registro. Accesibilidad
  medible: un `h1`; un `h2` por sección (más «En pocas palabras»), cada `section` con `aria-labelledby`; el
  número de sección con `aria-hidden`; el índice como `nav` con `aria-labelledby` a «En esta página»; el
  resumen como `aside` con su título; contraste, foco y toque por las puertas de `criterios.json`.
- **Texto horario:** `horarioEnTexto()` convierte `['Lunes a viernes' => '9:00–20:00', 'Sábados' =>
  '9:00–14:00']` en «de lunes a viernes de 9:00 a 20:00 y los sábados de 9:00 a 14:00», el texto aprobado.

## Estrategia de pruebas
- **Feature (PHPUnit, `DatabaseTransactions`, HTML con `DOMXPath` como `PaginasPublicasTest`):**
  `TerminosPrivacidadTest` comprueba las secciones de cada página en orden, la fecha, los datos de la clínica
  sacados de la configuración, los datos de salud como sensibles (CA1, CA2); que cada campo de los dos
  formularios del registro y cada dato que se guarda de Google aparecen en el aviso (CA3: un mapa campo →
  texto; un campo nuevo sin entrada en el mapa hace fallar el test); la casilla, sus enlaces y las dos ventanas
  con el mismo texto que las páginas (CA4); el
  rechazo sin la casilla en los dos registros, sin cuenta creada (CA5); los dos enlaces del pie en las cinco
  páginas públicas (CA6); la cabecera y el pie con y sin sesión (CA7); el contenido en el HTML del servidor
  (CA8) y que el texto de `<main>` es el del candidato `c4` aprobado, normalizado. Abrir y cerrar la ventana
  (Escape, fuera, foco de vuelta) es comportamiento del navegador: verificación manual (D12).
- **`PaginasPublicasTest`**: las dos rutas en `METADATOS` las meten en los tests de metadatos, un `h1`,
  títulos en orden, nombres accesibles, un solo script con `defer`, sin recursos externos y enlaces internos
  que resuelven; las dos vistas en `VISTAS`, en el de salida sin escapar (A1). El contenido en el HTML del
  servidor (CA8) lo prueba `test_el_texto_es_el_aprobado`, que lee la respuesta sin JavaScript (A5).
- **CSS (`PaginasPublicasCssTest`)**: el token `--font-display` igual en `app.css` y `system.md`; las seis
  caras de respaldo de Newsreader con sus `local()` y medidas; ninguna con `url()`; la rejilla de la página
  legal con su corte en 899 px, el texto a `--measure` (K4) y solo tokens.
- **Diseño:** `design/medir.mjs` sobre la app servida en local (`http://localhost:8000`, con la `url` de cada
  pantalla) pasa las puertas y KPIs de `criterios.json` en las dos páginas (K1, K2, K5–K7); K3 y K4 con test
  y a mano. Verificación manual del usuario contra `candidatos/c4` a 320, 390 y 1440 px.
- **Seguridad y dependencias:** `composer audit --locked` sin avisos tras subir `phpseclib`; `semgrep` y
  `gitleaks` sobre lo tocado.
- No se toca `layouts/app.blade.php`, `menu.js` ni `movil.css`: no hace falta `capturar.mjs --medir`.

## Modelo de amenazas
Las páginas son texto fijo y públicas, sin entradas ni datos de cuentas (la spec no tiene casos de abuso).
Quedan dos amenazas menores del cambio:

| ID | Amenaza (STRIDE) | Categoría OWASP | Componente | Control | Test |
|---|---|---|---|---|---|
| TM1 | **Retirada** (CA4 refinado): los enlaces ya no abren otra pestaña, así que no hay `window.opener` que proteger | — | — | — | `TerminosPrivacidadTest::test_la_casilla_del_registro` comprueba que no llevan `target` |
| TM2 | Information disclosure / inyección (contenido de configuración pintado sin escapar) | A03:2021 Injection | vistas nuevas | Solo dobles llaves para los datos de la configuración; sin salida sin escapar | `PaginasPublicasTest::test_las_vistas_publicas_no_usan_html_sin_escapar`, con las dos vistas en su lista `VISTAS` |

## Trazabilidad
| Criterio de aceptación | Cambio(s) | Test(s) |
|---|---|---|
| CA1: página «Términos de uso» con sus secciones y la fecha | rutas, controlador, `publico/terminos` | Feature: `TerminosPrivacidadTest::test_terminos_tiene_sus_secciones_y_la_fecha` y `test_los_datos_de_la_clinica_salen_de_la_configuracion` · `design/medir.mjs` (K1, K2) |
| CA2: página «Aviso de privacidad» con sus secciones y la fecha | rutas, controlador, `publico/aviso-privacidad` | Feature: `TerminosPrivacidadTest::test_el_aviso_tiene_sus_secciones_y_la_fecha` (incluye los datos de salud como sensibles y los cinco años del expediente) · `design/medir.mjs` (K1-privacidad, K2-privacidad) |
| CA3: el aviso cuadra con lo que pide el registro | `publico/aviso-privacidad` | Feature: `TerminosPrivacidadTest::test_el_aviso_nombra_cada_dato_del_registro` (campos de los dos formularios y de Google) · verificación manual: en `/review`, que el aviso no menciona datos ni usos que el sitio no tiene |
| CA4: casilla con «aviso de privacidad» y su texto en una ventana sin salir del registro | `register`, `registro-contacto`, `_dialogos-legales`, parciales de texto, `auth.css` | Feature: `TerminosPrivacidadTest::test_la_casilla_del_registro` (texto, `href` a cada página sin `target`, `data-dialogo` a una ventana que existe, `aria-haspopup`, sin `style`) y `test_las_ventanas_del_registro_llevan_el_texto_de_la_pagina` (en los dos registros: cada ventana con su título, la fecha, «Cerrar» y el mismo texto que la sección de su página) · CSS: `TerminosPrivacidadTest::test_la_ventana_del_registro_solo_usa_tokens` · script: `test_el_script_de_las_ventanas_abre_y_cierra` (A17) · `design/medir.mjs` sobre `candidatos/c4/registro-dialogo.html` (K8) · verificación manual: en el navegador, abrir cada ventana desde la casilla, cerrarla con «Cerrar», Escape y pulsando fuera, el foco de vuelta en el enlace, lo escrito sigue ahí, y sin JavaScript el enlace lleva a la página |
| CA5: sin la casilla se rechaza con el mensaje y sin cuenta | `RegisterController` | Feature: `TerminosPrivacidadTest::test_el_registro_sin_la_casilla_se_rechaza` (falla antes en `/registro`) y `test_el_registro_con_google_sin_la_casilla_se_rechaza` (caracterización del rechazo; el mensaje nuevo falla antes) |
| CA6: los dos enlaces en el pie de las páginas públicas | `layouts/publico` | Feature: `TerminosPrivacidadTest::test_el_pie_publico_enlaza_las_dos_paginas` (`/`, `/servicios`, `/galeria`, `/terminos`, `/aviso-de-privacidad`) · `design/medir.mjs` (K5) a 320, 390 y 1440 px |
| CA7: cabecera y pie públicos, con sesión sin redirigir | `layouts/publico`, controlador | Feature: `TerminosPrivacidadTest::test_con_y_sin_sesion_se_ven_igual` (caracterización de la parte «sin redirigir») |
| CA8: sin JavaScript, texto completo y enlaces | vistas nuevas | Feature: `TerminosPrivacidadTest::test_el_texto_es_el_aprobado` (el texto completo en el HTML del servidor) y `PaginasPublicasTest::test_todos_los_enlaces_internos_resuelven` con las dos rutas · `design/medir.mjs` (puerta `G-sin-js`) (A5) |
| TM1 | retirada (CA4 refinado) | `TerminosPrivacidadTest::test_la_casilla_del_registro` (sin `target`) |
| TM2 | vistas nuevas | `PaginasPublicasTest::test_las_vistas_publicas_no_usan_html_sin_escapar` con las dos vistas en `VISTAS` (A1) |
| Texto aprobado (`design.md`, decisión del usuario) | vistas nuevas | Feature: `TerminosPrivacidadTest::test_el_texto_es_el_aprobado` (texto de `<main>` igual al de `candidatos/c4`) |
| Diseño: K3 lista de secciones antes de la primera | vistas nuevas | Feature: `TerminosPrivacidadTest::test_el_indice_va_antes_de_las_secciones` |
| Diseño: K4 largo de línea | `publico.css` | CSS: `PaginasPublicasCssTest::test_la_pagina_legal_lee_a_la_medida` · verificación manual: ~62 caracteres a 1440 px en la app |
| Diseño: respaldo de Newsreader (1.15.0) | `app.css`, `publico.css`, `system.md` | CSS: `PaginasPublicasCssTest::test_newsreader_tiene_un_respaldo_con_sus_medidas` y `test_los_tokens_038_coinciden_con_el_sistema` · `design/medir.mjs` (K6 ≤ 0,1) |
| RNF: accesibilidad WCAG 2.1 AA | vistas, `publico.css` | `PaginasPublicasTest` (un `h1`, títulos en orden, nombres accesibles) · `design/medir.mjs` (`G-contraste`, `G-foco`, `G-toque`) · verificación manual: recorrido con teclado de una página en `/review` |
| RNF: descubribilidad | rutas, vistas, `docs/seo.md` | `PaginasPublicasTest::test_metadatos_de_cada_ruta_publica` · verificación manual: Lighthouse SEO ≥ 90 de las dos en `/review` |
| RNF: peso, sin imágenes ni JavaScript nuevos | vistas | `PaginasPublicasTest::test_solo_carga_el_script_publico_con_defer` · `design/medir.mjs` (K7) |
| P6: `phpseclib` 3.0.58 | `composer.lock` | verificación manual: `composer audit --locked` sin avisos y la suite completa en verde (tarea) |

## Observabilidad
No aplica: la spec no toca datos sensibles, autenticación ni permisos. Sin logs, eventos de auditoría ni
métricas nuevos; el alta sigue igual. Tras el deploy basta con que las dos rutas respondan `200` (T095).

## Descubribilidad
| Ruta | Indexable | Título | Descripción | Canónica | Sitemap | Datos estructurados | Test |
|---|---|---|---|---|---|---|---|
| `/terminos` | sí | «Términos de uso · Dentissa» | «Las reglas para usar el sitio de la clínica Dentissa y tu cuenta de paciente: citas en línea, lo que no está permitido y cómo contactarnos.» | `https://dentissa.app/terminos` | no (aún no hay sitemap) | — | `PaginasPublicasTest::test_metadatos_de_cada_ruta_publica` |
| `/aviso-de-privacidad` | sí | «Aviso de privacidad · Dentissa» | «Qué datos personales guarda la clínica Dentissa, para qué, con quién los comparte y cómo ejercer tus derechos de acceso, rectificación, cancelación y oposición.» | `https://dentissa.app/aviso-de-privacidad` | no (aún no hay sitemap) | — | ídem |

- Renderizado de las rutas nuevas: igual que `seo.rendering` (HTML del servidor con Blade).
- Imagen para compartir: ninguna, como las demás públicas.
- Brechas de `docs/seo.md` que atiende: ninguna.

## Rollout
- Feature flag: no aplica. Son dos páginas nuevas de solo lectura y un cambio de texto; el único cambio de
  flujo (la validación de la casilla en el servidor) coincide con lo que ya exige el navegador.
- Orden de despliegue: uno solo, con `desplegar.sh` (incluye `composer install` con el lock nuevo y la copia
  previa). Sin migraciones.
- Compatibilidad: convive con la versión anterior; ningún cliente envía el registro sin el formulario.
- Rollback: el de `docs/deployment.md` al tag anterior; no hay datos que revertir.
- Métricas a vigilar tras el deploy: `200` en `/terminos` y `/aviso-de-privacidad`, ningún `5xx` en
  `/registro`, y que un alta de prueba con la casilla sigue funcionando (T095).

## Decisiones (→ ADR si son arquitectónicas)
- **D1 · Rutas en `PaginasPublicasController`.** Opciones: `Route::view` sin controlador, o dos métodos del
  controlador de las páginas públicas. Se eligen los métodos: siguen el patrón de `/servicios` y `/galeria` y
  dan sitio a `horarioEnTexto()`.
- **D2 · Direcciones.** `/terminos` y `/aviso-de-privacidad` (Notas para /plan de la spec), con los nombres
  de ruta `terminos` y `privacidad`.
- **D3 · El texto vive en las vistas.** Opciones: Blade con el texto, archivos Markdown o la base de datos.
  Blade, como el resto del contenido público: sin dependencias, escapado y revisable en el diff. El test
  `test_el_texto_es_el_aprobado` lo ata a `candidatos/c4`; si el texto cambia, se cambia primero el
  candidato con `/design-spec 045 --fix`.
- **D4 · Datos de la clínica desde la configuración.** Dirección, teléfono, enlace `tel:` y horario salen
  de `config('clinica.contacto')`, la única fuente del contacto (D16 de la 041); el nombre de la clínica es
  el del layout público.
- **D5 · Validación de la casilla en el registro normal.** Hoy solo la exige el navegador (`required`). CA5
  pide que el envío sin ella se rechace sin crear la cuenta; con P4, la regla va en el servidor
  (`accepted`), igual que en el registro con Google. Implícita: spec 045 CA5 y `docs/constitution.md` P4.
- **D6 · El texto en una ventana del registro** (sustituye a «en otra pestaña», decisión del usuario del
  2026-11-12 en la verificación manual de T025). Opciones: `<dialog>` nativo con el texto en el HTML, un
  `iframe` con la página o traer la página con JavaScript. Se elige `<dialog>`: es accesible y cierra con
  Escape de serie, no necesita red ni escribir HTML desde el script, y sin JavaScript el enlace sigue llevando
  a la página, en la misma pestaña (decisión del usuario).
- **D7 · Fuera los `style=""` de los dos enlaces.** Pasan a `.page-register .checkbox-label a` en
  `auth.css` con el mismo token (`--p500`). El resto del registro sigue como código previo (P9).
- **D11 · El texto de cada página en un parcial.** `publico/legal/terminos-texto` y `privacidad-texto` con
  las secciones; la página los incluye con `h2` y sus ids, y la ventana con `h3`, ids con prefijo (`terminos-`,
  `privacidad-`) y los enlaces al otro texto con `data-dialogo`. Una sola fuente: la ventana no puede
  desfasarse de la página (`test_las_ventanas_del_registro_llevan_el_texto_de_la_pagina`).
- **D12 · El script de las ventanas va en línea en su parcial**, como el resto del registro, que ya lleva
  su `<script>` en línea y no carga `publico.js`. Abrir y cerrar se comprueba a mano (no hay tests de
  navegador del registro); si el navegador no tiene `showModal`, el enlace navega a la página.
- **D8 · Medir la app con `design/medir.mjs`.** `criterios.json` gana `url` por pantalla para medir
  `http://localhost:8000/terminos` y `/aviso-de-privacidad`; por HTTP no se comprueba `G-red`, que cubre
  `PaginasPublicasTest::test_sin_recursos_externos_salvo_el_mapa` con las dos rutas.
- **D9 · Sistema 1.15.0 en la primera tarea.** «Cambios a incorporar al sistema» de `design.md` (respaldo
  de Newsreader y componentes de la página legal) en `system.md` y `system.html`, en el mismo commit que el
  CSS que los usa.
- **D10 · `phpseclib/phpseclib` 3.0.58 dentro de la 045.** La línea base del 2026-11-12 da un aviso sin
  severidad (PKSA-h7cz-q7zq-mdkt, DoS por CPU al leer claves cifradas) en la 3.0.57 del lock, que ya pone en
  rojo el `composer audit` de la CI y bloquearía el `/release`. Se sube solo ese paquete
  (decisión del usuario, 2026-11-12). La 3.0.58 se publicó el 2026-11-02: cumple la antigüedad mínima de
  7 días de `docs/security.md` (comprobado con `composer show phpseclib/phpseclib 3.0.58 --all`, A4).
- Sin dependencias nuevas ni ADR.

## Impacto en arquitectura
- `docs/architecture.md`: la fila «Páginas públicas» con `/terminos` y `/aviso-de-privacidad`; el layout
  público y su pie; la validación de la casilla en `POST /registro`.
- `AGENTS.md`: la lista de páginas de `layouts/publico.blade.php`.
- `docs/seo.md`: las dos rutas en el inventario (indexables, con su evidencia).
- `docs/design/system.md` y `system.html`: 1.15.0 (D9).
- `docs/security.md`: las dos rutas en los puntos de entrada públicos y el aviso de `phpseclib` con su corrección (A10).

## Riesgos y mitigaciones
- **El comportamiento de la ventana no tiene test automático** (abrir, cerrar, foco): verificación manual en
  la tarea que la construye y en T095; el texto y la estructura sí los prueban los tests de CA4.
- **El texto no tiene revisión legal.** Es una base para un proyecto académico (spec, «Fuera de alcance»);
  los puntos que no salen de documentos del proyecto están en `design.md` → «Contenido para revisar», y el
  usuario los aprobó.
- **El texto puede quedar desfasado de la app** (un campo nuevo en el registro, un proveedor nuevo).
  `test_el_aviso_nombra_cada_dato_del_registro` falla si aparece un campo sin su entrada en el aviso.
- **Las medidas del respaldo de Newsreader** se calcularon con las fuentes de Windows; en macOS Georgia
  coincide, y en Linux Liberation Serif es equivalente métrico de Times. Si una medida no cuadra, solo
  aumenta el salto al cargar (K6 lo vigila en la tarea).
- **`publico.css` es compartida** con inicio, servicios y galería: las reglas nuevas usan clases propias
  (`.lb-legal`, `.lb-indice--legal`, `.lb-resumen`) y los tests de esas páginas siguen en la suite.
- **El registro normal ahora valida la casilla:** un test que daba de alta sin ella deja de crear la cuenta;
  se ajusta `LimiteIntentosTest::altaValida()` y la suite completa lo confirma.
- **Solape con specs abiertas:** la 040 (promociones, `approved` sin plan) añadirá una sección al inicio y
  tocará `publico.css`, `PaginasPublicasController`, `routes/web.php`, `PaginasPublicasTest` y
  `docs/architecture.md`, que la 045 también cambia (A14); la 046 (navegación interna, sin spec) tocará la navegación. Ninguna está en curso; la que
  llegue después fusiona `main` con la 045 ya liberada.
