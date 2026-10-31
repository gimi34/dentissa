---
spec: 038-pagina-inicio
status: approved   # draft | approved | blocked
constitution_version: 1.3.0
created: 2026-10-20
---

# Plan · 038 Página de inicio pública

## Enfoque técnico
La raíz y `/servicios` pasan a un controlador público nuevo (`PaginasPublicasController`) con un
layout público propio (`layouts/publico.blade.php`), separado del de la app. El layout lleva la
cabecera con el menú del móvil hecho con `<details>`, el pie, las fuentes autoalojadas
(Cormorant Garamond y Montserrat desde la ronda 5, D19) y los metadatos de cada ruta. Todo se renderiza en el servidor y se ve completo sin JavaScript. Un único script
propio, `public/js/publico.js` (~1.5 kB, con `defer`), añade las entradas al bajar y la pastilla
«¿Abierto ahora?»; sin él la página está terminada (D16).

Los servicios salen del catálogo (`servicios` activos, solo nombre, descripción y categoría,
agrupados por categoría en orden alfabético). Los textos fijos (presentación, «Sobre nosotros»,
contacto y horario) viven en la vista y en `config/clinica.php`.

El mapa de Google se admite cambiando la política de contenido solo para `/`. nginx elige el valor
de `frame-src` con un `map` sobre la ruta original de la petición. Hace falta así porque Laravel
atiende `/` por `index.php`, y un `location = /` no llegaría a las cabeceras de la respuesta de
PHP. Se comprobó con una prueba de concepto el 2026-10-20 (D5).

La vista del colaborador (`landing/index.blade.php`, Tailwind por CDN) se sustituye y se retiran
sus imágenes sin uso. La 038 no tiene migraciones.

**Rediseño de la ronda 5** (`design.md` aprobado en 9e30649, D19–D21), que sustituye al de la ronda 3
(D16 en lo visual, D17): «La ventana del diente», desde la referencia del usuario. La foto propia de
la marca se ve dentro del contorno del diente del logo y una escena la abre a pantalla completa al
bajar; siguen «Sobre nosotros» sobre el rosa, «Antes de cada tratamiento» con sus tres promesas, el
índice de categorías a todo el ancho, la tarjeta «Agenda sin llamar», la ubicación con la semana y
el mapa, y el nombre a todo el ancho en el pie. El sistema gana la superficie «Sitio público» (1.9.0:
Cormorant Garamond y Montserrat, tipos de cartel, `--shadow-soft`, píldoras y movimiento). Las rutas,
los datos, el controlador, la política de marcos, `publico.js` como único script y los textos de CA3 y
CA5 no cambian. Cambian las dos vistas, `publico.css`, el script (escena y cabecera), los tokens, las
fuentes, la foto y los tests que asertaban la ronda 3.

## Constitution Check
| Principio | Resultado | Justificación / ajuste | Cómo se verifica |
|---|---|---|---|
| P1 Spec aprobada antes de código | ✅ | Spec 038 `approved` y `design.md` aprobado. `tasks.md` se aprueba antes de `/implement` | manual: `python .ai/bin/aidd.py status` |
| P2 Test que falla antes y pasa después | ✅ | Cada criterio tiene su test en Trazabilidad. CA2 (con sesión, `/` lleva al panel) es de caracterización: ya se cumple y se comprueba rompiendo la redirección del controlador | test: los de Trazabilidad · manual: nota «comprobado rompiendo…» en la tarea de CA2 |
| P3 Autorización en el servidor | ✅ | `/` y `/servicios` son públicas por diseño, sin datos por rol ni por recurso. Lo único que depende del usuario es la redirección al panel (`/`) y el enlace «Ir a mi panel», ambos en el servidor. Las rutas de la app no cambian | test: `PaginaInicioTest::test_con_sesion_cada_rol_va_a_su_panel`, `ServiciosPublicosTest::test_con_sesion_se_ve_igual_y_ofrece_ir_al_panel` |
| P4 Entrada validada y SQL con bindings | ✅ | Las rutas no reciben entrada del visitante (sin parámetros ni formularios). La consulta del catálogo usa Eloquent sin SQL crudo | test: `ServiciosPublicosTest::test_parametros_en_la_url_no_cambian_la_lista` · lint: semgrep `p/php` en la CI |
| P5 Nada sensible en el repositorio | ✅ | Datos de contacto ficticios (spec CA5). Las fuentes van con su licencia OFL-1.1 (D19). La foto de la portada es propia de la clínica (D20, decisión del usuario). Sin secretos | lint: gitleaks en la CI · test: `PaginasPublicasTest::test_las_fuentes_van_con_su_licencia`, `test_la_foto_de_la_marca_es_la_propia` |
| P6 Sin vulnerabilidades críticas o altas | ✅ | Línea base del 2026-10-20: `composer audit` sin avisos y `npm audit` con 0. No hay dependencias nuevas | lint: `composer audit` y `npm audit` en la CI y en `/review` |
| P7 Auditoría de datos sensibles | ➖ | No toca datos sensibles, autenticación ni permisos: solo publica el catálogo activo y textos fijos | — |
| P8 Correlación y logs sin datos sensibles | ➖ | No añade logs ni procesa datos personales. Las respuestas siguen pasando por la correlación existente | — |
| P9 Solo sistema de diseño, WCAG 2.1 AA | ✅ | La primera tarea de cada ronda lleva al sistema los «Cambios a incorporar al sistema» de `design.md`; en la ronda 5, la superficie «Sitio público» de system.md 1.9.0 (D19) antes de tocar `publico.css`. `publico.css` usa solo tokens y las vistas no llevan `style=""`. Contraste calculado en `design.md` (`opcion-a.html#color`) | test: `PaginasPublicasCssTest` (tokens = sistema, sin literales, contraste de los pares de la ronda 5, sin `style=""`) · manual: capturas contra `design/opcion-a.html` a 320, 390, 900 y 1440 px (`/servicios`, contra `history/ronda-2/opcion-b.html` con la cabecera, el pie, la tipografía y las píldoras de la ronda 5), y teclado y NVDA en el menú |
| P10 Rutas indexables descubribles | ❌ aceptado: el JavaScript de terceros del `iframe` del mapa de Google no cuenta para el límite de 100 kB por ruta (y el peso del mapa no cuenta para el 1 MB de `/`), porque el navegador puede cargarlo en la primera visita aunque sea perezoso; el JavaScript propio sigue en 0 kB y lo transferido se mide y anota (`/analyze` A4) — aprobado por el usuario el 2026-10-20. Con D16 y D19 el JavaScript propio es `publico.js` (unos 5 kB), muy por debajo del límite; la excepción del mapa no cambia | `/` y `/servicios` pasan a indexables (decisión del usuario en la spec): título y descripción únicos, una canónica absoluta desde `APP_URL`, sin `noindex` y contenido en el HTML del servidor. El único JavaScript propio es `publico.js` con `defer` (≤ 100 kB), y sin él se ve todo (CA8). Se actualiza `docs/seo.md` | test: `PaginasPublicasTest::test_metadatos_de_cada_ruta_publica`, `test_solo_carga_el_script_publico_con_defer` (un solo `<script src>`, el propio, con `defer`, sin scripts en línea y ≤ 100 kB) · manual: Lighthouse SEO ≥ 90 y medición de JS y total transferido de `/` con nginx local, caché vacía, a 390 y 1440 px (T042) |

## Cambios por módulo
| Módulo | Cambio | Riesgo |
|---|---|---|
| `routes/web.php` | `/` deja el closure y pasa a `PaginasPublicasController@inicio` (nombre `inicio`). Nueva `GET /servicios` → `@servicios` (nombre `servicios.publicos`), fuera de los grupos por rol | Bajo: el nombre `servicios.publicos` no choca con `admin.servicios.*` |
| `app/Http/Controllers/PaginasPublicasController.php` (nuevo) | `inicio()`: con sesión, redirige al panel del rol (la lógica actual del closure, en un helper `panelDelRol()`); sin sesión, la vista con las categorías activas. `servicios()`: servicios activos agrupados por categoría. Ninguna de las dos recibe parámetros | Bajo |
| `app/Models/Servicio.php` | Scope `activos()` y método estático `porCategoria()`: categorías y servicios en orden alfabético, los que no tienen categoría al final en «Otros servicios» | Bajo |
| `config/clinica.php` | Bloque `contacto`: dirección, teléfono (texto y `tel:`) y horario de CA5, de donde salen el texto, el mapa y «Cómo llegar». `contacto.horario_semanal` (día ISO 1–7 → apertura y cierre, D16) para «¿Está abierto ahora?» y la fila de hoy de la semana (D19); un test lo mantiene igual que el horario en texto | Bajo |
| `resources/views/layouts/publico.blade.php` (nuevo) | `<html lang="es">`, `<head>` con título, descripción y canónica por sección (`@yield`), `app.css` (tokens), `publico.css` y `/js/publico.js` con `defer` (D16). Ronda 5 (D19): cabecera `.pub-bar` fija arriba en `--p50` (filete al bajar) con el logo, «Servicios», «Ubicación», «Iniciar sesión» o «Ir a mi panel» y «Crear cuenta» (botón de tinta) desde 900 px; por debajo, `<details class="pub-menu">` con «Menú» visible y su icono, que se cierra con Escape y al elegir un enlace; pie `.pub-foot` en `--p100` con los enlaces (también «Sobre nosotros»), el horario, el teléfono y la dirección, el nombre «Dentissa» a todo el ancho (`aria-hidden`) y el © | Medio: es el segundo layout de la app; AGENTS.md se actualiza (D2) |
| `resources/views/publico/inicio.blade.php` (nuevo) | Ronda 5 (D19), se rehace sobre `design/opcion-a-pagina.html`: presentación (titular de cartel en tres líneas, texto, «Agendar mi primera cita» → `route('register')`, «Ver servicios», pastilla `[data-live]` vacía con `data-horario` y `data-zona`, horario completo y teléfono `tel:`; la ventana del diente con la foto recortada, su trazo y «Antes de empezar, te explicamos cada paso.»); escena `ventana` (la foto de la marca con el velo y el hueco del diente); «Sobre nosotros» sobre el rosa (texto exacto de CA3); «Antes de cada tratamiento» con las tres promesas aprobadas; índice de las categorías activas con su número y su ancla de `/servicios` y «Ver todos los servicios»; tarjeta «Agenda sin llamar»; ubicación (dirección, semana con domingo cerrado y «hoy», teléfono, «Cómo llegar», `iframe` de Google y su nota). Entradas con `data-reveal`. Sin la portada a sangre, la hoja, las tarjetas ni la franja de la ronda 3 | Medio: cambia toda la vista y los tests que la asertan |
| `resources/views/publico/servicios.blade.php` (nuevo) | h1, índice de categorías (anclas), una sección por categoría con `<dl>` nombre / descripción, estado vacío y «Volver al inicio». Ronda 5 (D19): la tipografía (Cormorant en el título y las categorías, Montserrat en el texto), el índice en píldoras y los botones de la superficie «Sitio público»; la estructura y las anclas no cambian. Se descarta el cambio de T085 sin commit (D21) | Bajo |
| `resources/views/partials/icono-categoria.blade.php` (D16) | Se borra (D21): la ronda 5 no usa iconos de categoría | Bajo |
| `resources/views/landing/index.blade.php` | Se borra (la sustituye `publico/inicio`) | Bajo |
| `public/css/publico.css` (nuevo) | Ronda 5 (D19), se reescribe desde `opcion-a-pagina.html` con las clases de la maqueta prefijadas con `pub-` (no chocan con `.btn`, `.nav` ni `.menu` de `app.css`) y los botones en `.c-btn` (`--primary` rosa con tinta, `--ink`, `--secondary` fantasma, `--lg`): `@font-face` de Cormorant Garamond y Montserrat, `body.publico`, cabecera y menú, presentación y ventana del diente, escena `ventana` (máscara con `--p`), «Sobre nosotros», promesas con el título fijo, índice, tarjeta de la agenda, ubicación y mapa, pie y `/servicios`. Cortes de 900 y 560 px. Solo tokens; todo el movimiento dentro de `prefers-reduced-motion: no-preference` y lo que oculta, bajo `.js` | Medio: reescritura completa; la cubren `PaginasPublicasCssTest` y las capturas |
| `public/css/app.css` | Tokens en `:root` (system.md 1.7.0–1.7.2: `--font-publica`, `--text-publico-*`, `--leading-publico*`, `--space-seccion`, `--measure`, `--page-max`, `--duration-base`, `--ease-puerta`, `--green-texto`, `--focus-ring-inverso`, `--logo-ratio`). Ronda 5 (D19, system.md 1.9.0): `--font-publica` pasa a Montserrat; nuevos `--font-display`, `--text-display`, `--text-statement`, `--text-seccion`, `--text-indice`, `--text-firma`, `--text-lead`, `--shadow-soft`, `--ease-in-out`, `--page-ancho`, `--gutter` y `--space-seccion-publica`; se quedan `--radius-pill`, `--ease-out`, `--ease-expo`, `--duration-reveal` y `--stagger`; se retiran los 1.8.0 sin uso (`--text-portada`, `--text-marquesina`, `--leading-portada`, `--tracking-portada`, `--velo-portada`, `--velo-portada-suave`, `--sombra-tarjeta`, `--icono-tarjeta`, `--duration-marquesina`; D21). `--sombra-tarjeta-alta` y `--leading-publico` se quedan, con una sola declaración: tras fusionar `main` los usa la página de error de la 020 (`errores.css`; /analyze A35) | Bajo: solo variables; el resto de la app no las usa |
| `public/js/publico.js` (nuevo, D16) | Script clásico sin dependencias, con `defer`. Se queda (D16, D18): `estadoAhora(horario, zona, fecha)` expuesto para su test, la clase `js` al final y las entradas `[data-reveal]` con IntersectionObserver. Ronda 5 (D19): los textos de la pastilla en primera persona («Abierto ahora · cerramos a las 20:00», «Cerrado · abrimos hoy / mañana / el lunes a las 9:00»), escritos en un solo nodo con `textContent`, y el `data-open` del punto; la fila de hoy de la semana (`data-today`); el progreso `--p` de la escena `ventana` (solo con movimiento normal, sin capturar la rueda); el filete de la cabecera al bajar; el menú que se cierra con Escape (devuelve el foco a «Menú») y al elegir un enlace. Se quitan el botón de pausa de la franja y `escribirPastilla()` (D21) | Bajo: sin él la página está completa (CA8) |
| `public/fonts/` | Ronda 5 (D19): `cormorant-garamond/` (500 y 600) y `montserrat/` (400, 500, 600 y 700), woff2 latino de `@fontsource` 5.3.0, cada carpeta con su `OFL.txt`; se borra `lexend/` (D21) | Bajo |
| `public/images/` | Se borran `cover-bg.jpg` (1,2 MB, foto de banco) y `logo-rosa.png` (424 kB) (D11). `logo-icon1.png` se queda (DS15). Ronda 5: `brand/access.webp` (900 × 900) y `brand/access-recorte.webp` (520 × 600), la foto propia de la marca (D20); se borran `portada-800.webp` y `portada-1600.webp` de la ronda 3 (D21) | Bajo |
| `docker/nginx/seguridad/marcos.conf` (nuevo) | `map $request_uri $dentissa_frame_src { default "'self'"; "~^/(\?.*)?$" "'self' https://www.google.com"; }` | Medio: un error en la expresión abriría marcos en más rutas; lo cubre un test (TM3) |
| `docker/nginx/seguridad/comunes.conf` | La política añade `frame-src $dentissa_frame_src;`. El resto queda igual | Medio: conflicto con la 031 (D13) |
| `docker/nginx/dentissa.conf`, `docker/nginx/dentissa.prod.conf` | `include /etc/nginx/snippets/dentissa/marcos.conf;` antes del `server` (contexto `http`) | Bajo: el `Dockerfile` de producción ya copia `seguridad/` entera |
| `deploy/verificar.sh` | La comprobación pública «/ redirige a https» pasa a `/admin` (redirige al login en https). Se añaden «`/` responde 200» y «`/` admite el mapa y `/servicios` no» (`frame-src` en las cabeceras) | Medio: conflicto con la 031, que también cambia `verificar.sh` (D13) |
| `tests/Feature/ProxyHttpsTest.php` | Las dos pruebas de redirección usan `/admin` en lugar de `/` (misma intención: la redirección sale en https y sin el puerto ajeno). Tras el `/fix` de `main` (D13) pasan con `/`; con la 038, `/` deja de redirigir y pasan a ser caracterización de 030 CA2 (`/analyze` A8, A20) | Bajo |
| `tests/Feature/ExampleTest.php` | Espera la vista `publico.inicio` | Bajo |
| `tests/Feature/CabecerasSeguridadTest.php` | Comprueba `frame-src $dentissa_frame_src` en `comunes.conf`, el `map` de `marcos.conf` y su `include` en las dos configuraciones | Medio: conflicto con la 031 (D13) |
| `tests/Feature/PaginaInicioTest.php`, `ServiciosPublicosTest.php`, `PaginasPublicasTest.php`, `PaginasPublicasCssTest.php` (nuevos) | Ver Estrategia de pruebas. Ronda 5 (D19): los casos de la ronda 3 (portada a sangre, hoja, tarjetas, franja y su pausa, foto de Unsplash, Lexend) pasan a los de la ronda 5 (ventana, escena, promesas, índice, agenda, semana, cabecera y menú, fuentes y foto propia) | Medio: hay que cambiar aserciones existentes sin debilitarlas (cada una, a su caso nuevo) |
| `tests/js/publico.test.mjs` (nuevo, D18) | `node:test` sobre `estadoAhora()` cargado con `vm`; ronda 5: los textos en primera persona; sin el caso de `escribirPastilla()` | Bajo |
| `.github/workflows/ai-dd.yml` (D18) | Paso `node --test tests/js/` en el trabajo de tests. Ruta protegida, aprobada por el usuario el 2026-10-21 | Bajo |
| `docs/design/capturar.mjs` | Vistas públicas `publico-inicio` y `publico-servicios` (sin sesión) a 320, 390, 900 y 1440, y `--medir`: sin desbordamiento horizontal y toque ≥ 44 px, también sin JavaScript (D16). Ronda 5: la pastilla escrita y dentro de la pantalla en los cuatro anchos; el menú se cierra con Escape y devuelve el foco a «Menú»; sin la comprobación de la franja | Bajo |
| Documentación | `docs/design/system.md` y `system.html` (1.7.0; 1.7.1 con el refinado D15: `--focus-ring-inverso`, «Cabecera pública» y «Pie público»), `docs/seo.md` (filas de `/` y `/servicios`), objetivo 7 en `docs/roadmap.md`, `AGENTS.md` (dos layouts), `docs/architecture.md` (T090), `docs/specs/001-autenticacion/spec.md` (CA4 reemplazado), `docs/specs/030-despliegue-servidor-propio/spec.md` y `docs/specs/031-endurecer-produccion/spec.md` (Historial, D10), `docs/security.md` (entradas públicas, TM6 aceptado y visita a Google por el mapa; `/analyze` A10, A20), `CHANGELOG.md` en `/release`. Ronda 5 (D19–D21): `system.md` y `system.html` 1.9.0 (superficie «Sitio público»; en «Imágenes», la foto propia y la de Unsplash al historial), `docs/design/creative-direction.md` (concepto firma: la ventana del diente; Cormorant Garamond y Montserrat; registro firme), y `AGENTS.md` (`public/fonts/` con las dos familias en lugar de Lexend; ruta protegida, con aprobación en su tarea) | Bajo |

## Contratos y datos

### Rutas
| Método y ruta | Nombre | Sin sesión | Con sesión |
|---|---|---|---|
| `GET /` | `inicio` | 200, vista `publico.inicio` | 302 al panel del rol (`admin.dashboard`, `dentista.dashboard`, `asistente.dashboard` o `cliente.dashboard`), como hoy |
| `GET /servicios` | `servicios.publicos` | 200, vista `publico.servicios` | 200, la misma vista; la cabecera ofrece «Ir a mi panel» |

Ninguna recibe parámetros: los que lleguen en la URL se ignoran. No hay escrituras ni migraciones.

### Datos que se leen
- `servicios` donde `activo = 1`, columnas `nombre`, `descripcion` y `categoria`. Nunca `precio`,
  `duracion_min` ni `color_agenda`.
- Se agrupan por `categoria` con `trim()`. Las vacías o nulas van a «Otros servicios», al final.
  Categorías y servicios en orden alfabético (colación `utf8mb4_unicode_ci`, que ordena bien los
  acentos).
- En `/`, solo los nombres de las categorías (CA4). En `/servicios`, todo agrupado (CA6).

### Pantallas y estados (según `design.md`, ronda 5: «La ventana del diente»)
| Pantalla | Estados | Referencia |
|---|---|---|
| Página de inicio | normal · sin servicios activos (sin la sección de servicios) · con sesión (redirige) · sin JavaScript (todo visible, la foto de la escena entera, sin pastilla) · movimiento reducido (sin entradas ni escena: la foto entera) | `design/opcion-a.html#opcion-a-pagina-320`, `-390`, `-900` y `-1440`; `opcion-a-pagina.html#estado-sin-servicios` |
| «¿Está abierto ahora?» | abierto («Abierto ahora · cerramos a las 20:00», punto `--green-texto`) · cerrado («Cerrado · abrimos hoy / mañana / el lunes a las 9:00», punto `--p400`) · sin JavaScript (no aparece; el horario completo, al lado) · fila de hoy en la semana («· hoy») | `opcion-a-pagina.html` (script común) |
| `/servicios` | normal · sin servicios activos («Pronto publicaremos nuestros servicios.») · con sesión («Ir a mi panel») | `history/ronda-2/opcion-b.html#servicios-m`, `#servicios-d`, `#servicios-vacio-m`, con la cabecera, el pie, la tipografía y las píldoras de la ronda 5 |
| Menú del móvil | cerrado · abierto (`<details open>`), se cierra con Escape | `opcion-a-pagina.html#estado-menu`; ronda 5: «Menú» visible con su icono, en píldora con borde `--border-control` |

### Navegación de la 038 (`/analyze` A3)
- Cabecera (ronda 5, D19): «Servicios» → `/servicios`, «Ubicación» → `/#ubicacion`, «Iniciar
  sesión» (o «Ir a mi panel» con sesión) y «Crear cuenta» → `/registro` (sin sesión). Menú del
  móvil: además «Sobre nosotros» → `/#nosotros`. «Promociones» y «Trabajos» llegan con la 040 y la
  039, que los añaden.
- Presentación: «Agendar mi primera cita» → `/registro` y «Ver servicios» → `#servicios` solo si hay
  servicios activos (si no, → `/servicios`); la tarjeta «Agenda sin llamar» → `/registro`.
- Pie: «Sobre nosotros», «Servicios», «Ubicación», «Iniciar sesión» (o «Ir a mi panel») y «Crear
  cuenta»; el teléfono `tel:`; debajo, el nombre a todo el ancho, decorativo.
- Índice (D19): cada fila enlaza a la sección de su categoría en `/servicios` (`/servicios#categoria-N`).
- Ningún enlace apunta a una sección que puede no existir: «Servicios» va siempre a `/servicios`
  (que tiene su estado vacío), no a `#servicios`.
- Test: `PaginasPublicasTest::test_todos_los_enlaces_internos_resuelven`. En `/` y `/servicios`,
  con y sin servicios activos, cada `href="#id"` tiene su `id` en la página y cada ruta interna
  responde 200 o 302.

### Clases de los componentes (`/analyze` A6; ronda 5, D19)
Las de `design/opcion-a-pagina.html` con el prefijo `pub-` (las de la maqueta van sin él: `.hero` →
`.pub-hero`, `.tooth` → `.pub-tooth`…), que `PaginasPublicasCssTest` usa para el toque y el foco:
- cabecera y menú: `.pub-bar`, `.pub-bar__in`, `.pub-brand`, `.pub-nav`, `.pub-menu`, `.pub-menu summary`,
  `.pub-menu__panel`;
- presentación: `.pub-hero`, `.pub-hero__grid`, `.pub-hero__lead`, `.pub-hero__actions`,
  `.pub-hero__facts`, `.pub-status`, `.pub-status__dot`, `.pub-status__text`, `.pub-tooth`,
  `.pub-tooth__img`, `.pub-tooth__line`, `.pub-tooth__tag`;
- escena: `.pub-open`, `.pub-open__photo`, `.pub-open__veil`;
- secciones: `.pub-sec`, `.pub-kicker-line`, `.pub-about`, `.pub-about__body`, `.pub-promise`,
  `.pub-promise__head`, `.pub-promise__item`, `.pub-index`, `.pub-index__name`, `.pub-index__count`,
  `.pub-index__go`, `.pub-book__card`, `.pub-book__mark`, `.pub-visit`, `.pub-visit__addr`,
  `.pub-visit__tel`, `.pub-map`;
- pie: `.pub-foot`, `.pub-foot__links`, `.pub-foot__info`, `.pub-foot__word`, `.pub-foot__small`;
- `/servicios`: `.lb-indice a` (píldoras), `.lb-servicios`, `.lb-lista`;
- botones: `.c-btn` con `.c-btn--primary` (rosa con tinta), `.c-btn--ink`, `.c-btn--secondary`
  (fantasma) y `.c-btn--lg`; `.pub-wrap`;
- atributos del script: `data-reveal` (`mask` y por defecto), `data-scene="ventana"`, `data-live`,
  `data-horario`, `data-zona`, `data-semana`, `data-day`.

Llevan `--touch-min` como alto mínimo los enlaces de `.pub-nav`, `.pub-menu summary`, los enlaces de
`.pub-menu__panel`, los de `.pub-hero__facts`, `.pub-index a`, `.pub-visit__tel`, los de
`.pub-foot__links` y `.pub-foot__info`, `.lb-indice a` y `.c-btn`. Usan el foco del sistema
(`--focus-ring`); sobre la tinta (`[data-surface="dark"]`), el inverso.

### Accesibilidad medible
- Un solo `h1` por página: el titular de la presentación o «Nuestros servicios». En el inicio, `h2`
  para «Sobre nosotros», «Antes de cada tratamiento», «Nuestros servicios», «Agenda sin llamar» y
  «Encuéntranos fácilmente», y `h3` en cada promesa; en `/servicios`, las categorías son `h2`.
- `nav` con `aria-label` («Secciones» en la cabecera y en el menú, «Pie», «Categorías»).
- Toque ≥ 44 px en todo enlace y botón, comprobado con `--medir`.
- Foco visible con `--focus-ring` (`--p600`, 3.00:1 o más sobre los fondos claros) y blanco sobre la
  tinta.
- El `<summary>` del menú muestra «Menú» con su icono (`aria-hidden`); Escape lo cierra y devuelve el
  foco al `<summary>` (D19, sustituye al icono solo de D15).
- Contraste de la ronda 5 (D19, `design/opcion-a.html#color`): texto sobre `--p50` 19.59:1; tinta
  sobre `--p400` 5.34:1 («Sobre nosotros» y botón principal); tinta sobre `--p200` 13.31:1 (botón con
  el puntero); `--p600` sobre `--p50` 6.54:1 y sobre blanco 7.00:1; `--p100` sobre la tinta
  18.13:1; `--p600` sobre `--p100` 6.05:1 (pie); `--border-control` sobre `--p50` 3.67:1 (no texto).
- La pastilla lleva `aria-live="polite"`; la foto de la ventana y la de la escena llevan su texto
  alternativo (son de la clínica); el trazo, los filetes, la marca de agua, los iconos de las promesas,
  las flechas y el nombre del pie llevan `aria-hidden="true"`.
- Las filas del índice son enlaces enteros, con el nombre de la categoría y «N tratamientos».
- El `iframe` lleva `title="Mapa de Google con la ubicación de la consulta"`.
- El logo lleva `role="img"` con su nombre.
- El teléfono es un enlace `tel:` con el número completo.

## Estrategia de pruebas
PHPUnit (Feature), `DatabaseTransactions` contra `dentissa_test`. Los servicios se crean en cada
test con categorías y nombres propios del test (no se depende del volcado).

- **`PaginaInicioTest`:**
  - sin sesión, 200, sin redirección y en el orden de CA1 (posiciones de las secciones en el HTML);
  - con sesión, cada rol va a su panel (caracterización, CA2);
  - el texto exacto de «Sobre nosotros» (CA3);
  - las categorías activas sin servicios ni precios, y «Ver todos los servicios» (CA4);
  - sin servicios activos, sin la sección (CA4);
  - el contacto exacto, `tel:`, el `iframe` (src con la dirección codificada, `loading="lazy"`,
    `title`, `referrerpolicy`), la nota de Google y «Cómo llegar» con `rel="noopener noreferrer"`
    (CA5).
- **`ServiciosPublicosTest`:**
  - activos agrupados, inactivos ausentes, sin precio ni duración, vacío con su mensaje y «Volver
    al inicio» (CA6);
  - con sesión se ve igual y ofrece «Ir a mi panel» (CA2);
  - los parámetros en la URL no cambian nada (P4);
  - una descripción con `<script>` sale escapada (TM1).
- **`PaginasPublicasTest`** (las dos rutas):
  - metadatos (CA10): título, descripción, una sola canónica absoluta, sin `noindex`, y distintos
    de `/registro`;
  - un solo `h1`;
  - ningún `<script>` (CA8, RNF de 0 kB de JavaScript);
  - ningún recurso externo salvo el `iframe` de Google en `/` (CA7: `src`, `href` de hojas y
    `@import`/`url()` de `publico.css`);
  - el contenido completo en el HTML (CA8);
  - el peso de los recursos propios de `/` ≤ 1 MB (RNF);
  - la fuente con su licencia (P5);
  - las imágenes retiradas no existen y nada las referencia.
- **`PaginasPublicasCssTest`** (P9, con el trait `ReglasCss`):
  - tokens nuevos de `app.css` = `system.md`;
  - `publico.css` sin literales fuera de `@font-face`;
  - las vistas públicas sin `style=""`;
  - contraste de los pares nuevos (`--green-texto` sobre `--white` y `--bg`, `--p600` sobre `--bg`);
  - `--touch-min` en puertas, botones y el `summary` del menú;
  - los cortes de 900 y 560 px.
- **`CabecerasSeguridadTest`** (ampliado, CA7 y TM3):
  - `comunes.conf` declara `frame-src $dentissa_frame_src`;
  - `marcos.conf` tiene `default "'self'"` y una sola regla que acepta solo `/` (con o sin query)
    con `https://www.google.com`;
  - un test de la expresión del `map` contra `/`, `/?utm=x`, `/servicios`, `/admin`, `//evil` y
    `/x/`;
  - las dos configuraciones lo incluyen antes del `server`.
- **Refinado D15** (amplía los dos tests anteriores):
  - `PaginasPublicasCssTest`: `--focus-ring-inverso` = sistema 1.7.1; las reglas de foco dentro de
    `.pub-cabecera` y `.lb-pie` usan `--focus-ring-inverso` y el resto `--focus-ring`; `.pub-bar`
    (o la cabecera) con fondo `--p500` y `.lb-pie` con `--p600`; `.pub-menu summary` sin borde
    (`border: 0`) ni fondo; contraste de blanco sobre `--p500` y `--p600`;
  - `PaginasPublicasTest` (caso del layout): el `summary` sin texto visible, con
    `aria-label="Menú"` y un `svg` con `aria-hidden="true"`; el pie con «Horarios», las dos filas
    del horario, el teléfono `tel:` y los enlaces de la navegación.
- **Rediseño de la ronda 5** (D19–D21; sustituye las aserciones de la ronda 3, cada una a su caso
  nuevo, sin perder lo que comprobaba):
  - `PaginaInicioTest`: el orden de CA1 por las marcas del HTML (presentación → escena →
    `#nosotros` → «Antes de cada tratamiento» → `#servicios` → agenda → `#ubicacion` → pie); en la
    presentación, el titular, «Agendar mi primera cita», el horario completo y el teléfono `tel:`;
    la ventana del diente con la foto propia; «Sobre nosotros» con el texto de CA3 dentro de
    `.pub-about`; las tres promesas con sus títulos y sus textos aprobados; el índice con cada
    categoría activa, «N tratamientos» y su ancla, que existe en `/servicios`; la tarjeta de la
    agenda hacia el registro; la semana con lunes a viernes, sábados y domingo cerrado;
    `data-horario` y `data-zona` de la configuración; sin la placa, la portada a sangre, las tarjetas
    ni la franja;
  - `PaginasPublicasTest`: un solo `<script>`, el propio, con `defer` (D16); sin sumideros de HTML en
    el script (TM1); el peso de `/` ≤ 1 MB con las seis fuentes, el script y la foto;
    `test_las_fuentes_van_con_su_licencia` (Cormorant Garamond y Montserrat con su `OFL.txt`) y
    `test_la_foto_de_la_marca_es_la_propia` (existen `brand/access.webp` y su recorte, y `system.md`
    → «Imágenes» los da como propios); retiradas: `lexend/`, `portada-*.webp`,
    `partials/icono-categoria` y lo de D11, sin referencias; el caso del layout: «Menú» visible con
    su icono, la cabecera con «Crear cuenta» y el pie con sus enlaces, el horario, el teléfono y el
    nombre `aria-hidden`;
  - `PaginasPublicasCssTest`: tokens 1.9.0 = sistema y sin los 1.8.0 retirados; `@font-face` de las
    dos familias con sus woff2 propios y `font-display: swap`; contraste de los pares de la ronda 5;
    `--touch-min` en las clases de la lista; la escena (`.pub-open` sin la máscara cuando no está
    viva); las animaciones y las transiciones solo dentro de `prefers-reduced-motion: no-preference`
    y el estado oculto de las entradas solo bajo `.js`; los cortes de 900 y 560 px;
  - `tests/js/publico.test.mjs` (D18): `estadoAhora()` con los textos en primera persona y los mismos
    instantes (lunes 10:00, 8:00 y 21:00, el cierre exacto, la medianoche, el sábado, el domingo y un
    instante en UTC que en Lázaro Cárdenas es otro día) y datos que no valen (`null`); la clase `js`
    solo con IntersectionObserver.
- **`ProxyHttpsTest`** (ajustado) y **`ExampleTest`** (vista nueva).
- **En el navegador** (`capturar.mjs --medir`, procedimiento de su cabecera): `publico-inicio` y
  `publico-servicios` a 320, 390, 900 y 1440 sin desbordamiento horizontal y con toque ≥ 44 px
  (CA9), también con JavaScript desactivado (D16). Ronda 5: la pastilla escrita y dentro de la
  pantalla en los cuatro anchos, y el menú del móvil que se cierra con Escape y devuelve el foco a
  «Menú».
- **Manual** (anotada en su tarea):
  - capturas contra `design/opcion-a.html` (inicio) y `history/ronda-2/opcion-b.html` con la ronda
    5 (`/servicios`), en claro, sin JavaScript y con movimiento reducido, y la tira del scroll (la
    ventana que se abre, las entradas), con el inspector (`inspect.mjs`) sin FAIL;
  - menú del móvil con el teclado (Tab, Intro, Espacio);
  - consola sin bloqueos de la CSP y el mapa visible en `/` con nginx local (`curl -sI` de `/` y de
    `/servicios` para `frame-src`);
  - NVDA en la página de inicio;
  - Lighthouse SEO ≥ 90 en `/review`.

## Modelo de amenazas
Las páginas son públicas y no reciben datos del visitante. Las amenazas vienen del contenido que
publica el catálogo y del cambio en la política de contenido.

| ID | Amenaza (STRIDE) | Categoría OWASP | Componente | Control | Test |
|---|---|---|---|---|---|
| TM1 | Tampering: código en el nombre, la descripción o la categoría de un servicio (lo escribe el admin) se ejecuta en una página pública (XSS almacenado) | A05:2025 Injection | `publico/inicio`, `publico/servicios` | Siempre la salida escapada de Blade (doble llave); nunca la salida sin escapar (`{!! !!}`) en las vistas públicas. `publico.js` escribe solo con `textContent` (D16) | `ServiciosPublicosTest::test_el_texto_del_catalogo_sale_escapado` (y en `/`) · `PaginasPublicasTest::test_las_vistas_publicas_no_usan_html_sin_escapar`, `test_el_script_publico_no_usa_innerhtml` |
| TM2 | Information disclosure: precios, duraciones, colores de agenda o servicios inactivos en una página pública | A01:2025 Broken Access Control | `PaginasPublicasController`, `Servicio::porCategoria` | Solo `activo = 1` y solo `nombre`, `descripcion` y `categoria` | `ServiciosPublicosTest::test_solo_activos_sin_precio_ni_duracion`, `PaginaInicioTest::test_solo_categorias_activas_sin_servicios_sueltos` |
| TM3 | Security misconfiguration: la excepción de `frame-src` se abre en más rutas que `/` o a más orígenes | A02:2025 Security Misconfiguration | `marcos.conf`, `comunes.conf` | `map` sobre `$request_uri` con una sola expresión anclada (`^/(\?.*)?$`) y un solo origen, y valor por defecto `'self'`. `frame-ancestors 'none'` y `X-Frame-Options` no cambian | `CabecerasSeguridadTest::test_solo_la_raiz_admite_el_mapa` (casos de la expresión) · `verificar.sh` en el servidor |
| TM4 | Information disclosure (privacidad): la visita y la página de origen llegan a Google | A02:2025 Security Misconfiguration | `iframe` del mapa | `loading="lazy"` (solo al acercarse), `referrerpolicy="strict-origin-when-cross-origin"` (solo el dominio) y la nota visible (CA5) | `PaginaInicioTest::test_el_mapa_es_perezoso_y_avisa_de_google` |
| TM5 | Tampering: «Cómo llegar» se abre en otra pestaña y la página nueva manipula la de origen (`window.opener`) | A01:2025 Broken Access Control | enlace «Cómo llegar» | `target="_blank"` con `rel="noopener noreferrer"` | `PaginaInicioTest::test_contacto_exacto_con_mapa_y_como_llegar` |
| TM6 | Denial of service: peticiones masivas a páginas públicas que consultan la base | A06:2025 Insecure Design | `PaginasPublicasController` | Una sola consulta pequeña por petición (columnas acotadas y sin relaciones). Cloudflare delante (030). Riesgo aceptado: sin límite propio en páginas públicas | `ServiciosPublicosTest::test_una_sola_consulta_al_catalogo` (cuenta consultas) |

## Trazabilidad
| Criterio de aceptación | Cambio(s) | Test(s) |
|---|---|---|
| CA1: `/` sin sesión, página en el orden indicado, «Agendar mi primera cita» al registro, «Iniciar sesión» y crear cuenta visibles (reemplaza 001 CA4) | rutas, controlador, layout, `publico/inicio` | `PaginaInicioTest::test_sin_sesion_muestra_la_pagina_en_orden`, `test_la_presentacion_lleva_el_horario_y_el_telefono` (D19) · `ExampleTest` · verificación manual: inicio a 320/390/900/1440 contra `design/opcion-a.html#opcion-a-pagina-320` … `-1440` (ronda 5) |
| CA2: con sesión, `/` al panel; `/servicios` igual | controlador (`panelDelRol`), layout | `PaginaInicioTest::test_con_sesion_cada_rol_va_a_su_panel` (caracterización: comprobado rompiendo la redirección) · `ServiciosPublicosTest::test_con_sesion_se_ve_igual_y_ofrece_ir_al_panel` |
| CA3: texto exacto de «Sobre nosotros» | `publico/inicio` | `PaginaInicioTest::test_sobre_nosotros_con_el_texto_aprobado` |
| CA4: categorías activas sin servicios ni precios, «Ver todos los servicios»; sin activos, sin avance | `Servicio::porCategoria`, `publico/inicio` | `PaginaInicioTest::test_solo_categorias_activas_sin_servicios_sueltos`, `test_sin_servicios_activos_no_hay_avance`, `test_el_indice_cuenta_los_tratamientos_de_cada_categoria` (D19) |
| CA5: contacto exacto, `tel:`, mapa incrustado de esa dirección, nota de Google, «Cómo llegar» | `config/clinica.php`, `publico/inicio` | `PaginaInicioTest::test_contacto_exacto_con_mapa_y_como_llegar`, `test_el_mapa_es_perezoso_y_avisa_de_google` · verificación manual: el mapa se ve en `/` con nginx local |
| CA6: `/servicios` activos agrupados, sin precio ni inactivos, volver al inicio; vacío con mensaje | `Servicio::porCategoria`, `publico/servicios` | `ServiciosPublicosTest::test_activos_agrupados_por_categoria_en_orden`, `test_solo_activos_sin_precio_ni_duracion`, `test_sin_servicios_activos_muestra_el_mensaje` · verificación manual: contra `history/ronda-2/opcion-b.html#servicios-m` y `#servicios-d` con la ronda 5 |
| CA7: solo recursos propios y el mapa de Google en `/`; sin bloqueos de CSP; el resto sin marcos externos (amplía 031 CA1 solo en `/`) | `marcos.conf`, `comunes.conf`, configuraciones de nginx, `verificar.sh` | `PaginasPublicasTest::test_sin_recursos_externos_salvo_el_mapa` · `CabecerasSeguridadTest::test_solo_la_raiz_admite_el_mapa` · verificación manual: consola sin bloqueos en `/` y `/servicios`, y `curl -sI` de las dos con nginx local |
| CA8: sin JavaScript, todo visible y los enlaces funcionan (salvo el mapa) | layout y vistas con todo en el HTML; `publico.js` solo añade (D16) | `PaginasPublicasTest::test_solo_carga_el_script_publico_con_defer`, `test_el_contenido_va_en_el_html_del_servidor` · `PaginasPublicasCssTest` (estado oculto de las entradas solo bajo `.js`) · `capturar.mjs --medir` sin JavaScript · verificación manual: capturas sin JavaScript a 320 y 1440 |
| CA9: 320–1440 sin desbordamiento, toque ≥ 44, foco visible, menú con teclado, sin animaciones con movimiento reducido | `publico.css`, layout (`<details>`), `publico.js` | `PaginasPublicasCssTest` (toque, cortes, foco con tokens, transiciones y animaciones solo con `prefers-reduced-motion: no-preference`, entradas solo bajo `.js`) · `PaginasPublicasTest::test_el_layout_publico_con_y_sin_sesion` («Menú» con su icono; pie con sus enlaces) · `capturar.mjs --medir` (publico-inicio, publico-servicios, con y sin JavaScript; el menú con Escape) · verificación manual: menú con teclado y NVDA |
| CA10: título, descripción, canónica, sin `noindex`, distintos entre sí y de `/registro` | layout (`@yield`), vistas | `PaginasPublicasTest::test_metadatos_de_cada_ruta_publica`, `test_un_solo_h1` |
| RNF: WCAG 2.1 AA (contraste, títulos en orden, decorativos ocultos) | `publico.css`, vistas | `PaginasPublicasCssTest::test_contraste_de_los_pares_nuevos` (los de la ronda 5) · `PaginasPublicasTest::test_un_solo_h1`, `test_titulos_en_orden`, `test_nombres_accesibles` · verificación manual: NVDA |
| RNF: indexables con Lighthouse SEO ≥ 90 y ≤ 100 kB de JS (sin contar el JS de terceros del mapa: excepción aceptada, P10) | vistas, `publico.js` | `PaginasPublicasTest::test_solo_carga_el_script_publico_con_defer` · verificación manual: Lighthouse y medición de JS propio, de terceros y total de `/` con nginx local (T042), y en `/review` |
| RNF: `/` ≤ 1 MB en la primera visita | vistas, fuentes (seis woff2), `publico.css`, `publico.js` y la foto de la marca (D19, D20) | `PaginasPublicasTest::test_los_recursos_propios_de_la_raiz_pesan_menos_de_un_mega` · verificación manual: medición en la verificación final |
| RNF: el mapa no se descarga hasta acercarse | `iframe` | `PaginaInicioTest::test_el_mapa_es_perezoso_y_avisa_de_google` · verificación manual: T042 anota si el `iframe` entra en la primera carga a 390 y 1440 px (el navegador carga los perezosos a 1250–2500 px del viewport; si entra, se aplica la excepción de P10) |
| Diseño M-1 a M-6 (`design.md`, ronda 5, D19) | `publico.css`, `publico.js`, `publico/inicio` | M-1 escena `ventana`: `PaginasPublicasCssTest` (sin máscara cuando no está viva) y `capturar.mjs --medir` sin JavaScript · M-2 trazo y M-3 titular: `PaginasPublicasCssTest` (solo bajo `.js` y sin movimiento reducido) · M-4 entradas: `PaginasPublicasCssTest` (solo bajo `.js`) · M-5 respuesta: `PaginasPublicasCssTest` (transiciones sin movimiento reducido) · M-6 «¿Está abierto ahora?»: `tests/js/publico.test.mjs` · verificación manual: la tira del scroll a 320 y 1440 contra `.ai/inspect/r5/film-*` |
| TM1: XSS almacenado desde el catálogo | `publico/inicio`, `publico/servicios`, `publico.js` | `ServiciosPublicosTest::test_el_texto_del_catalogo_sale_escapado` · `PaginasPublicasTest::test_las_vistas_publicas_no_usan_html_sin_escapar`, `test_el_script_publico_no_usa_innerhtml` |
| TM2: precios, duraciones o inactivos en público | `Servicio::porCategoria` | `ServiciosPublicosTest::test_solo_activos_sin_precio_ni_duracion` · `PaginaInicioTest::test_solo_categorias_activas_sin_servicios_sueltos` |
| TM3: la excepción de marcos se abre de más | `marcos.conf`, `comunes.conf` | `CabecerasSeguridadTest::test_solo_la_raiz_admite_el_mapa` |
| TM4: la visita llega a Google | `iframe` del mapa | `PaginaInicioTest::test_el_mapa_es_perezoso_y_avisa_de_google` |
| TM5: `window.opener` desde «Cómo llegar» | enlace «Cómo llegar» | `PaginaInicioTest::test_contacto_exacto_con_mapa_y_como_llegar` |
| TM6: peticiones masivas a páginas públicas | `PaginasPublicasController` | `ServiciosPublicosTest::test_una_sola_consulta_al_catalogo` |

## Observabilidad
No toca datos sensibles, autenticación ni permisos (P7 y P8 ➖): no añade logs ni eventos de
auditoría.
- Métricas y alertas: sin cambios. Los 5xx de `/` y `/servicios` ya cuentan en el registro de
  acceso de nginx.
- Tras el deploy, `deploy/verificar.sh` comprueba desde fuera:
  - `/` 200 con `frame-src 'self' https://www.google.com`;
  - `/servicios` 200 con `frame-src 'self'`;
  - `/admin` redirige a `https://…/login`;
  - sin 5xx nuevos en `dentissa-access.log` en los 15 minutos siguientes (T097).

## Descubribilidad
| Ruta | Indexable | Título | Descripción | Canónica | Sitemap | Datos estructurados | Test |
|---|---|---|---|---|---|---|---|
| `/` | sí (decisión del usuario, spec 038) | «Dentissa · Tu sonrisa, nuestra prioridad» | «Clínica dental Dentissa en Lázaro Cárdenas: conoce nuestros servicios, crea tu cuenta y agenda tu cita en línea.» | `https://dentissa.app/` (`rtrim(config('app.url'), '/').'/'`) | no: el sitemap es del objetivo 7, que tendrá que incluirla | — (datos estructurados fuera de alcance) | `PaginasPublicasTest::test_metadatos_de_cada_ruta_publica` |
| `/servicios` | sí | «Servicios · Dentissa» | «Todos los tratamientos de la consulta Dentissa en Lázaro Cárdenas, de la revisión general a la ortodoncia y las prótesis.» | `https://dentissa.app/servicios` | no (objetivo 7) | — | `PaginasPublicasTest::test_metadatos_de_cada_ruta_publica` |

- Renderizado: el del proyecto (`ssr`, Blade en el servidor), con todo el contenido en el HTML;
  `publico.js` solo añade las entradas, la escena, la pastilla y la fila de hoy (D16, D19).
- Imagen para compartir: ninguna (la vista previa al compartir queda fuera de alcance). Sin `og:*`
  ni `twitter:*` (`docs/seo.md` → Exclusiones).
- `<html lang="es">`. Sin `theme-color` ni íconos nuevos (los íconos del sitio quedan fuera).
- Brechas `SE` que atiende: ninguna; las de `docs/seo.md` siguen en el objetivo 7 y en «Pendientes y deuda».
  `docs/seo.md` gana las filas de `/` y `/servicios`, y la regla «Se indexa» pasa a `/registro`,
  `/` y `/servicios`.

## Rollout
- **Feature flag:** no aplica. Es una página pública nueva en lugar de una redirección; la vuelta
  atrás es el despliegue anterior.
- **Orden de despliegue** (D13):
  1. Se libera la 031 (v0.10.0).
  2. La rama de la 038 trae `main` antes de las tareas de nginx y `verificar.sh`.
  3. `/release 038` con su versión: v0.11.0, salvo que otra salga antes.
  4. `deploy/desplegar.sh v0.11.0` reconstruye `app` y `web`. La imagen `web` lleva las
     configuraciones nuevas de nginx, `publico.css`, `publico.js`, las fuentes (Cormorant
     Garamond y Montserrat) y la foto de la marca.
  5. Sin migraciones.
- **Compatibilidad:** sin cambios de datos, así que el despliegue y la vuelta atrás no tocan la base.
- **Rollback:** `deploy/volver.sh v0.10.0` (sin copia de la base; no hace falta).
- **Métricas tras el deploy:**
  - `verificar.sh` público;
  - 5xx en `/` y `/servicios`;
  - consola del navegador sin bloqueos de la CSP en `/`;
  - el mapa visible.

## Decisiones (→ ADR si son arquitectónicas)
- **D1 · Controlador y rutas públicas.** `PaginasPublicasController` (plural, como pide AGENTS.md)
  con `inicio()` y `servicios()`. La lógica del closure de `/` pasa a `panelDelRol()`, reutilizado
  por «Ir a mi panel». Descartado: closures en `routes/web.php` (sin test aislado ni reutilización).
- **D2 · Layout público** `layouts/publico.blade.php` (decisión del usuario, 2026-10-20), que
  usarán `/`, `/servicios` y `/galeria` (039). AGENTS.md pasa de «un solo layout» a dos (app y
  público); el acceso sigue con su propio HTML. Descartado: vistas sueltas con parciales (`<head>`
  repetido).
- **D3 · CSS.** Las páginas públicas cargan `app.css` (por los tokens de `:root`) y `publico.css`.
  `body.publico` anula `display: flex` y la familia de `app.css`. Los tokens nuevos van en `:root`
  de `app.css`, como los de las specs 027–035 (la fuente de los tokens es una sola). Descartado:
  separar los tokens en un archivo nuevo, que cambia el layout de la app y obliga a `--medir` en
  toda la app.
- **D4 · Lexend autoalojada copiando los archivos** (decisión del usuario, 2026-10-20). Van
  `lexend-latin-400-normal.woff2` y `lexend-latin-600-normal.woff2` de `@fontsource/lexend` 5.3.0,
  publicado hace más de 7 días (2026-07-19), y su `LICENSE` como `OFL.txt` en
  `public/fonts/lexend/`. No es una dependencia del gestor de paquetes. La verificación de
  `shared/agent-security.md` §2 se adapta así:
  - el paquete existe en npm y lo publica la organización Fontsource;
  - la licencia es OFL-1.1;
  - no hay scripts: son dos archivos de fuente.

  No requiere ADR. Descartado: `@fontsource/lexend` como dependencia, con un paso de build que la
  imagen de producción no tiene. **Sustituida por D19** (ronda 5): las fuentes son Cormorant Garamond
  y Montserrat, copiadas igual; Lexend se retira (D21).
- **D5 · Excepción de `frame-src` por ruta con un `map` de nginx**
  (`docker/nginx/seguridad/marcos.conf`), incluido en el contexto `http` de las dos
  configuraciones. Comprobado con una prueba de concepto el 2026-10-20, con nginx 1.30.5 y
  Chromium de Playwright 1.63.0:
  - `/` y `/?utm=x` cargan el embed (`www.google.com/maps/embed…`) sin bloqueos;
  - `/servicios` lo bloquea con `frame-src 'self'`.

  Descartados:
  - `location = /`: la respuesta de PHP sale del `location ~ \.php$` tras la redirección interna;
  - una cabecera desde Laravel: con dos políticas, el navegador aplica la intersección y el mapa
    seguiría bloqueado;
  - abrir `frame-src` en todas las rutas: va contra CA7.

  Es un ajuste de la configuración de la 031 sin cambio de arquitectura, así que no lleva ADR.
- **D6 · Mapa sin clave de API.**
  - El mapa es `https://www.google.com/maps?q=<dirección>&output=embed`; la prueba de concepto
    mostró que se queda en `www.google.com`.
  - «Cómo llegar» va a `https://www.google.com/maps/dir/?api=1&destination=<dirección>`.
  - La dirección sale de `config('clinica.contacto.direccion')`, codificada con `rawurlencode`.

  Descartados: la API de Maps Embed con clave (un secreto más y facturación) y el `pb=` copiado
  del colaborador (apunta a otra dirección).
- **D7 · Categorías en orden alfabético** (decisión del usuario, 2026-10-20). Los servicios sin
  categoría van al final en «Otros servicios». Lo ordena la base (colación `unicode_ci`), y
  «Otros servicios» se coloca en PHP.
- **D8 · Textos fijos.** La presentación y «Sobre nosotros» van en la vista. Dirección, teléfono y
  horario van en `config/clinica.php`, porque los usan el texto, el `tel:`, el mapa y «Cómo
  llegar». No son variables de entorno: son contenido, no configuración del despliegue.
- **D9 · Sin JavaScript en la 038.** El menú del móvil es `<details>/<summary>` (teclado nativo y
  sin JS); el mapa es un `iframe` perezoso. El primer script público llega con el carrusel (039). **Sustituida por D16** (ronda 3, 2026-10-21).
- **D10 · `ProxyHttpsTest` y `verificar.sh` comprueban la redirección con `/admin`.** Mantienen la
  intención de la 030 (la redirección sale en https, con el host y el puerto propios). `/` ya no
  redirige sin sesión (CA1).
  - 030 CA2 no cambia de comportamiento; solo cambia la ruta con la que se prueba, y por eso la
    030 no va en `extends` (`/analyze` A9).
  - T041 anota en el Historial de la 030 el cambio de ruta de prueba, y en el de la 031 que su
    CA1 queda ampliado por 038 CA7 (`frame-src` del mapa en `/`).
- **D11 · Retirada.** Se retiran:
  - `resources/views/landing/index.blade.php`;
  - `public/images/cover-bg.jpg` (foto de banco con licencia desconocida, `brief.md`);
  - `public/images/logo-rosa.png`, sin uso.

  Un test comprueba que nada los referencia.
- **D12 · `capturar.mjs` gana las vistas públicas** para `--medir` (CA9) y para las capturas del
  sistema. AGENTS.md no cambia la regla de `--medir`, porque la 038 no toca el layout de la app,
  `menu.js` ni `movil.css`.
- **D13 · Orden con la 031** (decisión del usuario, 2026-10-20). La 031 sale antes. La 038 trae
  `main` antes de sus tareas de nginx, de `CabecerasSeguridadTest` y de `verificar.sh`, y
  concilia esos archivos.
  - **Prerrequisito de la 031** (`/analyze` A1, decisión del usuario, 2026-10-20): un `/fix` en
    `main` devuelve `/` al login y `ExampleTest` vuelve a esperar esa redirección. Con eso
    `ProxyHttpsTest` y `verificar.sh` pasan sin cambios, y la v0.10.0 no sirve la landing del
    colaborador. La vista `landing/index.blade.php` y sus imágenes quedan en `main` sin uso hasta
    que la 038 las retira (T027).
  - Al traer `main` (T004), `routes/web.php` y `ExampleTest` llegan con el `/fix`. Los conflictos
    se resuelven a favor de la 038 (T016, T022). Las pruebas de `ProxyHttpsTest` con `/admin`
    pasan a ser de caracterización (`/analyze` A8).
- **D14 · Tokens nuevos de esta spec** (de `design.md`, «Cambios a incorporar al sistema»):
  `--font-publica`, `--text-publico-h1` y `--text-publico-h2` (fluidos, con `clamp()` y `vw` entre
  320 y 1440), `--text-publico-h3`, `--text-publico`, `--text-publico-small`, `--space-seccion`
  (fluido), `--measure`, `--page-max`, `--duration-base`, `--ease-puerta` y `--green-texto`. La
  primera tarea los lleva a `system.md` 1.7.0, `system.html` y `app.css` en el mismo commit que el
  test de tokens.

- **D15 · Refinado de la cabecera, el menú y el pie** (`/design-spec 038 --fix`, decisión del usuario,
  2026-10-20, tras la verificación manual de T025). Cabecera en `--p500` con el logo y los enlaces
  en blanco y «Iniciar sesión» como botón blanco; menú del móvil con solo el icono de hamburguesa
  (SVG en línea `aria-hidden`, de `--icon-md`, con `aria-label="Menú"` en el `<summary>`, sin borde
  ni fondo), como el botón «Menú» de la app (034); pie en `--p600` con tres columnas (logo y frase,
  Horarios, Contacto) y el © centrado, siguiendo la landing del colaborador. Token nuevo
  `--focus-ring-inverso: var(--white)` (system.md 1.7.1) para el foco sobre el rosa. El SVG del
  icono va en el HTML (no es JavaScript; P10 sigue en 0 kB de JS propio). Descartados: una imagen
  aparte para el icono (una petición más) y el carácter «☰» (cambia con la fuente y el lector lo
  anuncia).

- **D16 · Rediseño de la ronda 3** (`/design-spec 038`, `design.md` aprobado en 651e316; decisión del
  usuario, 2026-10-21: la opción B es «muy básica», seguir la línea de abound). Lo que cambia:
  - Las dos vistas, `publico.css`, los tokens (system.md 1.8.0) y los tests que asertaban la opción B.
  - Un solo script propio, `public/js/publico.js` (~1.5 kB, sin dependencias), cargado con `defer`
    desde el layout en las dos rutas (se guarda en caché y la 039 lo ampliará). No hay script en
    línea. El script:
    - pone la clase `js` en `<html>`; sin ella no se oculta nada;
    - con movimiento normal, pone `is-in` a cada `[data-reveal]` al llegar al 88 % de la ventana;
    - rellena `[data-live]` con `estadoAhora(horario, zona, fecha)` usando `Intl.DateTimeFormat`, y
      escribe solo con `textContent`.
  - El horario estructurado `contacto.horario_semanal` vive junto al texto en `config/clinica.php`;
    la vista lo pasa al script en `data-horario` (escapado por Blade) y la zona en `data-zona`.
  - La hoja es CSS (`position: sticky`) y la franja, `@keyframes`. La franja solo se mueve bajo
    `.js`, porque se tiene que poder detener (WCAG 2.2.2, `/analyze` A23, decisión del usuario,
    2026-10-21). El script muestra su botón de pausa, que lleva `hidden` en el HTML, y lo maneja
    con `aria-pressed` y la clase `is-pausada`. Sin JavaScript, la franja queda quieta.
  - Los iconos de categoría son un parcial Blade con SVG en línea (`partials/icono-categoria`).
  - Lo que no cambia: rutas, controlador, consulta, política de marcos (D5), textos de CA3 y CA5.

  Descartados:
  - calcular el estado en el servidor: el diseño lo fija como cosa viva del navegador (sin
    JavaScript no hay pastilla), y en una respuesta en caché quedaría viejo;
  - una librería de animación: dependencia y peso para lo que hacen CSS e IntersectionObserver.

  Sin ADR: la app ya carga scripts propios (`menu.js`, `filtros.js`) y no cambia la arquitectura.
  **En lo visual, sustituida por D19** (ronda 5): sin franja ni pausa, sin hoja sticky ni iconos de
  categoría. Se quedan el script único con `defer`, la clase `js`, las entradas, `estadoAhora()` y
  `contacto.horario_semanal`.
- **D17 · Foto de la portada** (**sustituida por D20**, ronda 5) (decisión del usuario, 2026-10-21, `/plan 038 --fix`). Es una foto
  de un banco abierto con licencia de uso comercial (Unsplash o Pexels) que cumple la descripción de
  `design.md` → «Imágenes». La elige `/implement` y el usuario la aprueba antes de su commit.
  - Formato: dos WebP, de 800 y 1600 px de ancho, comprimidas para que `/` quede ≤ 1 MB. Van en una
    `<img alt="">` con `srcset` y `sizes`, `fetchpriority="high"` y sin `loading="lazy"`, porque es
    la primera pantalla.
  - Crédito (autor, fuente, licencia, URL y fecha de la descarga) en `system.md` → «Imágenes» y en
    el pie de `design.md`.
  - No es una dependencia de código. La licencia se comprueba en la página de la foto y la
    comprobación se anota en la tarea.
  - `cover-bg.jpg` de abound sigue retirada (D11) y solo vive en las maquetas.
- **D18 · Pruebas del script con `node:test`.** `publico.js` expone `estadoAhora()` en
  `globalThis.DentissaPublico`, y `tests/js/publico.test.mjs` lo carga con `node:vm`. Usa Node 24,
  sin dependencia ni `package.json` nuevos. Comprobado con una prueba de concepto el 2026-10-21:
  función con `Intl` en `America/Mexico_City`, cargada con `vm`, dos casos en verde.
  - Se ejecuta con `node --test tests/js/*.test.mjs` en cada tarea que toque el script y en `/review`
    (Node 24 no acepta un directorio en `--test`; nota de T077).
  - En la CI: un paso en `.github/workflows/ai-dd.yml` (ruta protegida; decisión del usuario,
    2026-10-21).

  Descartados:
  - Jest o Vitest: una dependencia nueva;
  - probarlo solo a mano: la salida depende de la hora, y los casos del sábado y del domingo no se
    ven en una sesión.
- **D19 · Rediseño de la ronda 5: «La ventana del diente»** (`/design-spec 038`, `design.md` aprobado en
  9e30649; decisión del usuario, 2026-10-21: las rondas anteriores se ven genéricas, una sola opción
  sobre su referencia). Lo que cambia:
  - Las dos vistas, el layout, `publico.css`, `publico.js` (escena, cabecera, menú, fila de hoy,
    textos en primera persona), los tokens (system.md 1.9.0, superficie «Sitio público»), las
    fuentes y los tests que asertaban la ronda 3.
  - **Clases:** las de la maqueta con el prefijo `pub-`. Así no chocan con `.btn`, `.nav` y
    `.menu` de `app.css`, que las páginas públicas cargan por los tokens (D3). Los botones siguen en
    `.c-btn`, con las variantes de la superficie.
  - **Fuentes:** Cormorant Garamond 500 y 600 y Montserrat 400–700, woff2 latino de `@fontsource`
    5.3.0. Las publica la organización Fontsource, con licencia OFL-1.1, el 2026-07-19 (más de 7
    días) y sin scripts: son archivos de fuente. Se copian a `public/fonts/` con su `OFL.txt`, como
    D4 (sin dependencia del gestor ni paso de build).
  - **Escena `ventana`:** el progreso `--p` lo calcula el script común (un solo bucle con
    `requestAnimationFrame`, sin capturar la rueda, solo con movimiento normal). Sin JavaScript y
    con movimiento reducido, la foto se ve entera.
  - **Cosa viva:** la de D16 con los textos en primera persona, en un solo nodo con `textContent`
    (decisión del usuario, 2026-10-21; sin la partición de T045), y la fila de hoy de la semana.
  - **Menú del móvil:** «Menú» visible con su icono (sustituye al icono solo de D15); Escape lo
    cierra y devuelve el foco.
  - **Lo que no cambia:** rutas, controlador, consulta, política de marcos (D5), mapa (D6) y los
    textos de CA3 y CA5.

  Descartado: copiar las clases de la maqueta tal cual (chocan con `app.css`). Sin ADR: no cambia la
  arquitectura.
- **D20 · Foto propia de la marca** (decisión del usuario, 2026-10-21). Sustituye a la de Unsplash de
  D17. Es la de la referencia: una revisión con espejo teñida de rosa, con el logo, a 900 × 900, y su
  recorte sin logo, a 520 × 600, en `design/referencia/`.
  - Se guarda en `public/images/brand/` como `access.webp` y `access-recorte.webp`.
  - `system.md` → «Imágenes» la da como propia; la fila de Unsplash pasa al historial.
  - Si aparece un original mayor, se sustituye por él.
- **D21 · Retiradas de la ronda 5** (decisión del usuario, 2026-10-21):
  - Lexend (`public/fonts/lexend/`);
  - la foto de Unsplash (`public/images/portada-800.webp` y `-1600.webp`);
  - el parcial `partials/icono-categoria.blade.php`;
  - los tokens 1.8.0 sin uso, salvo `--sombra-tarjeta-alta` y `--leading-publico`, que usa la página
    de error de la 020 en `main` (/analyze A35, 2026-10-31);
  - el botón de pausa de la franja y `escribirPastilla()` de `publico.js`;
  - el cambio de T085 sin commit en `servicios.blade.php` (T085 pasa a obsoleta y `/servicios` se
    rehace en su tarea de la ronda 5).

  Un test comprueba que nada cita lo retirado.
- **D22 · Fusionar `main` antes de la ronda 5** (/analyze A34–A36; decisión del usuario,
  2026-10-31): la rama va por detrás de `main`, donde la 020 (v0.12.0) cambió `config/clinica.php`,
  `system.md`/`system.html`, `app.css`, `routes/web.php`, `capturar.mjs`, `desplegar.sh` y
  `verificar.sh`. Una tarea fusiona `origin/main` antes de T046 y reconcilia:
  - `contacto` único con la forma de la 038 (`direccion`, `telefono`, `telefono_enlace`, `horario`,
    `horario_semanal`) y el teléfono ficticio `753 000 0000` (CA5, P5), que también leen la 503 de
    la 020 y los correos de la 041;
  - el historial de `system.md` y `system.html` con las entradas 1.7.0 y 1.8.0 de las dos specs
    (la 038 pasa después a 1.9.0, T046);
  - las rutas `/`, `/servicios`, `/admin/auditoria` y la local `/_disenio/020`, y en `capturar.mjs`
    los `excluidos` de la 020 junto a las comprobaciones de la 038.

## Impacto en arquitectura
Lo actualiza T090 al implementar:
- **Frontend:** dos layouts (app y público), `publico.css`, `publico.js` (D16, D19), fuentes
  autoalojadas (Cormorant Garamond y Montserrat, D19), la foto de la marca (D20) y la excepción de
  `frame-src` en `/`.
- **Módulos:** las páginas públicas (`PaginasPublicasController`).
- **Integraciones externas:** el mapa de Google incrustado en `/` (sin clave) y el enlace «Cómo
  llegar».
- **Despliegue:** `marcos.conf`, y los cambios de `verificar.sh`.
- **SEO** (en `architecture.md` → Frontend): `/`, `/servicios` y `/registro` indexables.

## Riesgos y mitigaciones
- **La 031 se libera desde un `main` con la landing del colaborador** (D13). Decidido: un `/fix`
  en `main` antes de `/release 031` devuelve `/` al login y deja la CI en verde (`/analyze` A1).
- **Conflictos con la rama de la 031** en `comunes.conf`, `CabecerasSeguridadTest` y
  `verificar.sh`. Mitigación: D13 (traer `main` con la 031 liberada antes de esas tareas) y tests
  de configuración que detectan una conciliación mal hecha.
- **La expresión del `map` abre marcos donde no debe** (TM3). Mitigación: el test de la expresión
  con casos límite y la comprobación pública de `verificar.sh` en `/servicios`.
- **Google cambia el comportamiento del embed sin clave** (`output=embed`) o lo redirige a otro
  dominio. Mitigación: si la consola muestra un bloqueo, el embed se cambia por `pb=` del mismo
  origen o se añade el origen exacto a `marcos.conf`, con la misma prueba. «Cómo llegar» y la
  dirección en texto siguen funcionando aunque el mapa falle (CA8).
- **`app.css` en las páginas públicas trae reglas de la app** (`body` en flex, Roboto).
  Mitigación: `body.publico` y las capturas contra `opcion-b.html`. Es un riesgo bajo, porque las
  clases de la app no aparecen en las vistas públicas.
- **El orden alfabético mueve las categorías** respecto al diseño (que mostraba otro orden).
  Mitigación: es decisión del usuario (D7); el diseño no fija el orden.
- **Riesgo aceptado:** las páginas públicas no tienen límite de peticiones propio (TM6). Las
  protegen Cloudflare y una consulta mínima.
- **El mapa entra en la primera carga** y su JavaScript de terceros supera lo que pide P10 para
  la ruta. Se acepta como excepción de P10 (`/analyze` A4, decisión del usuario, 2026-10-20) y se
  mide en T042. El JavaScript propio sigue en 0 kB.
- **La 039 relajará invariantes de esta spec**: «ningún `<script>`» y «ningún recurso externo
  salvo el mapa» de `PaginasPublicasTest` (`/analyze` A16). Su plan modifica esos casos en lugar
  de duplicarlos. Con D16 el primero ya es «solo `publico.js` con `defer`», y la 039 amplía ese
  script en lugar de añadir otro.
- **La foto de la marca solo existe a 900 px** (D20). En pantallas grandes la escena la amplía.
  Mitigación: `object-fit: cover` con el centro en el logo y su sustitución por el original si
  aparece; la verificación manual mira la escena a 1440.
- **Seis archivos de fuente** (≈ 140 kB) en la primera visita. Mitigación: woff2 latino,
  `font-display: swap` y el test del 1 MB de `/`.
- **El script falla o no carga** (red, error, navegador antiguo). Mitigación: el contenido no
  depende de él (las entradas solo ocultan bajo `.js`, que pone el propio script al terminar; sin
  escena viva, la foto se ve entera) y `--medir` se pasa también sin JavaScript.
- **El rediseño rompe tests aprobados en `/review`** (r3, 7cebde7) y los de la ronda 3. Mitigación:
  cada caso que cambia se reescribe con su equivalente de la ronda 5, en la misma tarea que el cambio,
  y la `/review --rerun` cubre el diff desde 7cebde7.
- **Clases de la maqueta que chocan con `app.css`** (`.btn`, `.nav`, `.menu`). Mitigación: el prefijo
  `pub-` (D19) y las capturas contra `opcion-a.html`.
