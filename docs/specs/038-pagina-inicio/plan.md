---
spec: 038-pagina-inicio
status: draft   # draft | approved | blocked
constitution_version: 1.3.0
created: 2026-10-20
---

# Plan · 038 Página de inicio pública

## Enfoque técnico
La raíz y `/servicios` pasan a un controlador público nuevo (`PaginasPublicasController`) con un
layout público propio (`layouts/publico.blade.php`), separado del de la app. El layout lleva la
cabecera con el menú del móvil hecho con `<details>`, el pie, Lexend autoalojada y los metadatos
de cada ruta. Todo se renderiza en el servidor y sin JavaScript: la 038 no añade ningún script.

Los servicios salen del catálogo (`servicios` activos, solo nombre, descripción y categoría,
agrupados por categoría en orden alfabético). Los textos fijos (presentación, «Sobre nosotros»,
contacto y horario) viven en la vista y en `config/clinica.php`.

El mapa de Google se admite cambiando la política de contenido solo para `/`. nginx elige el valor
de `frame-src` con un `map` sobre la ruta original de la petición. Hace falta así porque Laravel
atiende `/` por `index.php`, y un `location = /` no llegaría a las cabeceras de la respuesta de
PHP. Se comprobó con una prueba de concepto el 2026-10-20 (D5).

La vista del colaborador (`landing/index.blade.php`, Tailwind por CDN) se sustituye y se retiran
sus imágenes sin uso. La 038 no tiene migraciones.

## Constitution Check
| Principio | Resultado | Justificación / ajuste | Cómo se verifica |
|---|---|---|---|
| P1 Spec aprobada antes de código | ✅ | Spec 038 `approved` y `design.md` aprobado. `tasks.md` se aprueba antes de `/implement` | manual: `python .ai/bin/aidd.py status` |
| P2 Test que falla antes y pasa después | ✅ | Cada criterio tiene su test en Trazabilidad. CA2 (con sesión, `/` lleva al panel) es de caracterización: ya se cumple y se comprueba rompiendo la redirección del controlador | test: los de Trazabilidad · manual: nota «comprobado rompiendo…» en la tarea de CA2 |
| P3 Autorización en el servidor | ✅ | `/` y `/servicios` son públicas por diseño, sin datos por rol ni por recurso. Lo único que depende del usuario es la redirección al panel (`/`) y el enlace «Ir a mi panel», ambos en el servidor. Las rutas de la app no cambian | test: `PaginaInicioTest::test_con_sesion_cada_rol_va_a_su_panel`, `ServiciosPublicosTest::test_con_sesion_se_ve_igual_y_ofrece_ir_al_panel` |
| P4 Entrada validada y SQL con bindings | ✅ | Las rutas no reciben entrada del visitante (sin parámetros ni formularios). La consulta del catálogo usa Eloquent sin SQL crudo | test: `ServiciosPublicosTest::test_parametros_en_la_url_no_cambian_la_lista` · lint: semgrep `p/php` en la CI |
| P5 Nada sensible en el repositorio | ✅ | Datos de contacto ficticios (spec CA5). La fuente va con su licencia OFL-1.1. Sin secretos | lint: gitleaks en la CI · test: `PaginasPublicasTest::test_la_fuente_va_con_su_licencia` |
| P6 Sin vulnerabilidades críticas o altas | ✅ | Línea base del 2026-10-20: `composer audit` sin avisos y `npm audit` con 0. No hay dependencias nuevas | lint: `composer audit` y `npm audit` en la CI y en `/review` |
| P7 Auditoría de datos sensibles | ➖ | No toca datos sensibles, autenticación ni permisos: solo publica el catálogo activo y textos fijos | — |
| P8 Correlación y logs sin datos sensibles | ➖ | No añade logs ni procesa datos personales. Las respuestas siguen pasando por la correlación existente | — |
| P9 Solo sistema de diseño, WCAG 2.1 AA | ✅ | La primera tarea incorpora a `system.md` 1.7.0 los «Cambios a incorporar al sistema» de `design.md`. `publico.css` usa solo tokens y la vista no lleva `style=""`. Contraste calculado en `design.md` | test: `PaginasPublicasCssTest` (tokens = sistema, sin literales, contraste de los pares nuevos, sin `style=""`) · manual: capturas contra `design/opcion-b.html` a 320, 390, 900 y 1440 px, y teclado y NVDA en el menú |
| P10 Rutas indexables descubribles | ❌ aceptado: el JavaScript de terceros del `iframe` del mapa de Google no cuenta para el límite de 100 kB por ruta (y el peso del mapa no cuenta para el 1 MB de `/`), porque el navegador puede cargarlo en la primera visita aunque sea perezoso; el JavaScript propio sigue en 0 kB y lo transferido se mide y anota (`/analyze` A4) — aprobado por el usuario el 2026-10-20 | `/` y `/servicios` pasan a indexables (decisión del usuario en la spec): título y descripción únicos, una canónica absoluta desde `APP_URL`, sin `noindex`, contenido en el HTML del servidor y 0 kB de JavaScript propio. Se actualiza `docs/seo.md` | test: `PaginasPublicasTest::test_metadatos_de_cada_ruta_publica`, `test_ninguna_pagina_publica_carga_scripts` · manual: Lighthouse SEO ≥ 90 y medición de JS y total transferido de `/` con nginx local, caché vacía, a 390 y 1440 px (T042) |

## Cambios por módulo
| Módulo | Cambio | Riesgo |
|---|---|---|
| `routes/web.php` | `/` deja el closure y pasa a `PaginasPublicasController@inicio` (nombre `inicio`). Nueva `GET /servicios` → `@servicios` (nombre `servicios.publicos`), fuera de los grupos por rol | Bajo: el nombre `servicios.publicos` no choca con `admin.servicios.*` |
| `app/Http/Controllers/PaginasPublicasController.php` (nuevo) | `inicio()`: con sesión, redirige al panel del rol (la lógica actual del closure, en un helper `panelDelRol()`); sin sesión, la vista con las categorías activas. `servicios()`: servicios activos agrupados por categoría. Ninguna de las dos recibe parámetros | Bajo |
| `app/Models/Servicio.php` | Scope `activos()` y método estático `porCategoria()`: categorías y servicios en orden alfabético, los que no tienen categoría al final en «Otros servicios» | Bajo |
| `config/clinica.php` | Bloque `contacto`: dirección, teléfono (texto y `tel:`) y horario de CA5, de donde salen el texto, el mapa y «Cómo llegar» | Bajo |
| `resources/views/layouts/publico.blade.php` (nuevo) | `<html lang="es">`, `<head>` con título, descripción y canónica por sección (`@yield`), `app.css` (tokens) y `publico.css`. Cabecera: logo enmascarado, navegación, «Iniciar sesión» o «Ir a mi panel», y menú `<details>` por debajo de 900 px. Pie. Sin scripts | Medio: es el segundo layout de la app; AGENTS.md se actualiza (D2) |
| `resources/views/publico/inicio.blade.php` (nuevo) | Placa (logo, h1, «Agendar mi primera cita» → `route('register')`, aviso de la puerta), «Sobre nosotros» (texto de CA3), puertas de categorías con «Ver todos los servicios», ubicación (mapa `iframe` y placa de dirección) | Bajo |
| `resources/views/publico/servicios.blade.php` (nuevo) | h1, índice de categorías (anclas), una sección por categoría con `<dl>` nombre / descripción, estado vacío y «Volver al inicio» | Bajo |
| `resources/views/landing/index.blade.php` | Se borra (la sustituye `publico/inicio`) | Bajo |
| `public/css/publico.css` (nuevo) | `@font-face` de Lexend, `body.publico` (anula el `display: flex` y Roboto de `app.css`), componentes «Placa», «Puerta», «Cabecera pública», «Pie público», «Sobre nosotros», «Mapa con placa» y listas de `/servicios`. Cortes de 900 y 560 px. Solo tokens | Bajo |
| `public/css/app.css` | Tokens nuevos en `:root` (system.md 1.7.0): `--font-publica`, `--text-publico-*`, `--space-seccion`, `--measure`, `--page-max`, `--duration-base`, `--ease-puerta` y `--green-texto`. Los de la 039 (`--foto-ratio`, `--thumb-w`, `--duration-firma`) entran con ella | Bajo: solo añade variables; el resto de la app no las usa |
| `public/fonts/lexend/` (nuevo) | `lexend-latin-400-normal.woff2`, `lexend-latin-600-normal.woff2` y `OFL.txt`, copiados de `@fontsource/lexend` 5.3.0 | Bajo |
| `public/images/` | Se borran `cover-bg.jpg` (1,2 MB, foto de banco) y `logo-rosa.png` (424 kB). Las añadió el merge y nada las usa. `logo-icon1.png` se queda (DS15, posible ícono del sitio) | Bajo |
| `docker/nginx/seguridad/marcos.conf` (nuevo) | `map $request_uri $dentissa_frame_src { default "'self'"; "~^/(\?.*)?$" "'self' https://www.google.com"; }` | Medio: un error en la expresión abriría marcos en más rutas; lo cubre un test (TM3) |
| `docker/nginx/seguridad/comunes.conf` | La política añade `frame-src $dentissa_frame_src;`. El resto queda igual | Medio: conflicto con la 031 (D13) |
| `docker/nginx/dentissa.conf`, `docker/nginx/dentissa.prod.conf` | `include /etc/nginx/snippets/dentissa/marcos.conf;` antes del `server` (contexto `http`) | Bajo: el `Dockerfile` de producción ya copia `seguridad/` entera |
| `deploy/verificar.sh` | La comprobación pública «/ redirige a https» pasa a `/admin` (redirige al login en https). Se añaden «`/` responde 200» y «`/` admite el mapa y `/servicios` no» (`frame-src` en las cabeceras) | Medio: conflicto con la 031, que también cambia `verificar.sh` (D13) |
| `tests/Feature/ProxyHttpsTest.php` | Las dos pruebas de redirección usan `/admin` en lugar de `/` (misma intención: la redirección sale en https y sin el puerto ajeno). Tras el `/fix` de `main` (D13) pasan con `/`; con la 038, `/` deja de redirigir y pasan a ser caracterización de 030 CA2 (`/analyze` A8, A20) | Bajo |
| `tests/Feature/ExampleTest.php` | Espera la vista `publico.inicio` | Bajo |
| `tests/Feature/CabecerasSeguridadTest.php` | Comprueba `frame-src $dentissa_frame_src` en `comunes.conf`, el `map` de `marcos.conf` y su `include` en las dos configuraciones | Medio: conflicto con la 031 (D13) |
| `tests/Feature/PaginaInicioTest.php`, `ServiciosPublicosTest.php`, `PaginasPublicasTest.php`, `PaginasPublicasCssTest.php` (nuevos) | Ver Estrategia de pruebas | — |
| `docs/design/capturar.mjs` | Vistas públicas `publico-inicio` y `publico-servicios` (sin sesión) a 320, 390, 900 y 1440, y comprobaciones de `--medir` para ellas: sin desbordamiento horizontal y toque ≥ 44 px | Bajo |
| Documentación | `docs/design/system.md` y `system.html` (1.7.0), `docs/seo.md` (filas de `/` y `/servicios`), objetivo 7 en `docs/roadmap.md`, `AGENTS.md` (dos layouts), `docs/architecture.md` (T090), `docs/specs/001-autenticacion/spec.md` (CA4 reemplazado), `docs/specs/030-despliegue-servidor-propio/spec.md` y `docs/specs/031-endurecer-produccion/spec.md` (Historial, D10), `docs/security.md` (entradas públicas, TM6 aceptado y visita a Google por el mapa; `/analyze` A10, A20), `CHANGELOG.md` en `/release` | Bajo |

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

### Pantallas y estados (según `design.md`, opción B ronda 2)
| Pantalla | Estados | Referencia |
|---|---|---|
| Página de inicio | normal · sin servicios activos (sin la sección de servicios) · con sesión (redirige) | `opcion-b.html#inicio-solo038-m`, `#inicio-vacio-m`, `#inicio-d` |
| `/servicios` | normal · sin servicios activos («Pronto publicaremos nuestros servicios.») · con sesión («Ir a mi panel») | `#servicios-m`, `#servicios-d`, `#servicios-vacio-m` |
| Menú del móvil | cerrado · abierto (`<details open>`) | `#inicio-m` |

### Navegación de la 038 (`/analyze` A3)
- Cabecera y menú del móvil: «Servicios» → `/servicios`, «Ubicación» → `#ubicacion`,
  «Iniciar sesión» (o «Ir a mi panel» con sesión) y, solo en el menú y el pie, «Crear cuenta» →
  `/registro`. «Promociones» y «Trabajos» llegan con la 040 y la 039, que los añaden.
- Pie: «Servicios», «Crear cuenta» e «Iniciar sesión».
- Ningún enlace apunta a una sección que puede no existir: «Servicios» va siempre a `/servicios`
  (que tiene su estado vacío), no a `#servicios`.
- Test: `PaginasPublicasTest::test_todos_los_enlaces_internos_resuelven`. En `/` y `/servicios`,
  con y sin servicios activos, cada `href="#id"` tiene su `id` en la página y cada ruta interna
  responde 200 o 302.

### Clases de los componentes (`/analyze` A6)
Las de `design/opcion-b.html`, que T014 usa para comprobar el toque y el foco:
- cabecera: `.pub-bar`, `.pub-nav-d` (enlaces y `.c-btn`), `.pub-menu` y `.pub-menu summary`;
- placa: `.lb-placa`, `.lb-placa-texto`, `.lb-aviso`, `.lb-llamar`;
- «Sobre nosotros»: `.lb-nosotros`;
- puertas: `.lb-puertas a`; índice de `/servicios`: `.lb-indice a`;
- listas de `/servicios`: `.lb-servicios`, `.lb-lista`;
- mapa con placa: `.lb-calle`, `.mapa`, `.lb-placa-dir`, `.nota-mapa`;
- pie: `.lb-pie`;
- comunes: `.c-btn`, `.c-btn--primary`, `.c-btn--secondary`, `.c-link`, `.lb-wrap`, `.lb-seccion`, `.lb-cabeza`.

Llevan `--touch-min` como alto mínimo los enlaces y botones de `.pub-nav-d`, `.pub-menu summary`,
los enlaces de `.pub-menu`, `.lb-puertas a`, `.lb-indice a`, `.c-btn` y `.c-link`. Todos usan el
foco del sistema (`--focus-ring`).

### Accesibilidad medible
- Un solo `h1` por página: el título de la placa o «Nuestros servicios». En el inicio, el aviso de
  la puerta, «Sobre nosotros», «Nuestros servicios» y «Encuéntranos fácilmente» son `h2`; en
  `/servicios`, las categorías son `h2`.
- `nav` con `aria-label` («Secciones», «Pie», «Categorías»).
- Toque ≥ 44 px en todo enlace y botón, comprobado con `--medir`.
- Foco visible con `--focus-ring`.
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
- **`ProxyHttpsTest`** (ajustado) y **`ExampleTest`** (vista nueva).
- **En el navegador** (`capturar.mjs --medir`, procedimiento de su cabecera): `publico-inicio` y
  `publico-servicios` a 320, 390, 900 y 1440 sin desbordamiento horizontal y con toque ≥ 44 px
  (CA9).
- **Manual** (anotada en su tarea):
  - capturas contra `opcion-b.html`;
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
| TM1 | Tampering: código en el nombre, la descripción o la categoría de un servicio (lo escribe el admin) se ejecuta en una página pública (XSS almacenado) | A05:2025 Injection | `publico/inicio`, `publico/servicios` | Siempre la salida escapada de Blade (doble llave); nunca la salida sin escapar (`{!! !!}`) en las vistas públicas | `ServiciosPublicosTest::test_el_texto_del_catalogo_sale_escapado` (y en `/`) · `PaginasPublicasTest::test_las_vistas_publicas_no_usan_html_sin_escapar` |
| TM2 | Information disclosure: precios, duraciones, colores de agenda o servicios inactivos en una página pública | A01:2025 Broken Access Control | `PaginasPublicasController`, `Servicio::porCategoria` | Solo `activo = 1` y solo `nombre`, `descripcion` y `categoria` | `ServiciosPublicosTest::test_solo_activos_sin_precio_ni_duracion`, `PaginaInicioTest::test_solo_categorias_activas_sin_servicios_sueltos` |
| TM3 | Security misconfiguration: la excepción de `frame-src` se abre en más rutas que `/` o a más orígenes | A02:2025 Security Misconfiguration | `marcos.conf`, `comunes.conf` | `map` sobre `$request_uri` con una sola expresión anclada (`^/(\?.*)?$`) y un solo origen, y valor por defecto `'self'`. `frame-ancestors 'none'` y `X-Frame-Options` no cambian | `CabecerasSeguridadTest::test_solo_la_raiz_admite_el_mapa` (casos de la expresión) · `verificar.sh` en el servidor |
| TM4 | Information disclosure (privacidad): la visita y la página de origen llegan a Google | A02:2025 Security Misconfiguration | `iframe` del mapa | `loading="lazy"` (solo al acercarse), `referrerpolicy="strict-origin-when-cross-origin"` (solo el dominio) y la nota visible (CA5) | `PaginaInicioTest::test_el_mapa_es_perezoso_y_avisa_de_google` |
| TM5 | Tampering: «Cómo llegar» se abre en otra pestaña y la página nueva manipula la de origen (`window.opener`) | A01:2025 Broken Access Control | enlace «Cómo llegar» | `target="_blank"` con `rel="noopener noreferrer"` | `PaginaInicioTest::test_contacto_exacto_con_mapa_y_como_llegar` |
| TM6 | Denial of service: peticiones masivas a páginas públicas que consultan la base | A06:2025 Insecure Design | `PaginasPublicasController` | Una sola consulta pequeña por petición (columnas acotadas y sin relaciones). Cloudflare delante (030). Riesgo aceptado: sin límite propio en páginas públicas | `ServiciosPublicosTest::test_una_sola_consulta_al_catalogo` (cuenta consultas) |

## Trazabilidad
| Criterio de aceptación | Cambio(s) | Test(s) |
|---|---|---|
| CA1: `/` sin sesión, página en el orden indicado, «Agendar mi primera cita» al registro, «Iniciar sesión» y crear cuenta visibles (reemplaza 001 CA4) | rutas, controlador, layout, `publico/inicio` | `PaginaInicioTest::test_sin_sesion_muestra_la_pagina_en_orden` · `ExampleTest` · verificación manual: inicio a 320/390/900/1440 contra `opcion-b.html#inicio-solo038-m` y `#inicio-d` |
| CA2: con sesión, `/` al panel; `/servicios` igual | controlador (`panelDelRol`), layout | `PaginaInicioTest::test_con_sesion_cada_rol_va_a_su_panel` (caracterización: comprobado rompiendo la redirección) · `ServiciosPublicosTest::test_con_sesion_se_ve_igual_y_ofrece_ir_al_panel` |
| CA3: texto exacto de «Sobre nosotros» | `publico/inicio` | `PaginaInicioTest::test_sobre_nosotros_con_el_texto_aprobado` |
| CA4: categorías activas sin servicios ni precios, «Ver todos los servicios»; sin activos, sin avance | `Servicio::porCategoria`, `publico/inicio` | `PaginaInicioTest::test_solo_categorias_activas_sin_servicios_sueltos`, `test_sin_servicios_activos_no_hay_avance` |
| CA5: contacto exacto, `tel:`, mapa incrustado de esa dirección, nota de Google, «Cómo llegar» | `config/clinica.php`, `publico/inicio` | `PaginaInicioTest::test_contacto_exacto_con_mapa_y_como_llegar`, `test_el_mapa_es_perezoso_y_avisa_de_google` · verificación manual: el mapa se ve en `/` con nginx local |
| CA6: `/servicios` activos agrupados, sin precio ni inactivos, volver al inicio; vacío con mensaje | `Servicio::porCategoria`, `publico/servicios` | `ServiciosPublicosTest::test_activos_agrupados_por_categoria_en_orden`, `test_solo_activos_sin_precio_ni_duracion`, `test_sin_servicios_activos_muestra_el_mensaje` · verificación manual: contra `opcion-b.html#servicios-m` y `#servicios-d` |
| CA7: solo recursos propios y el mapa de Google en `/`; sin bloqueos de CSP; el resto sin marcos externos (amplía 031 CA1 solo en `/`) | `marcos.conf`, `comunes.conf`, configuraciones de nginx, `verificar.sh` | `PaginasPublicasTest::test_sin_recursos_externos_salvo_el_mapa` · `CabecerasSeguridadTest::test_solo_la_raiz_admite_el_mapa` · verificación manual: consola sin bloqueos en `/` y `/servicios`, y `curl -sI` de las dos con nginx local |
| CA8: sin JavaScript, todo visible y los enlaces funcionan (salvo el mapa) | layout y vistas sin scripts | `PaginasPublicasTest::test_ninguna_pagina_publica_carga_scripts`, `test_el_contenido_va_en_el_html_del_servidor` |
| CA9: 320–1440 sin desbordamiento, toque ≥ 44, foco visible, menú con teclado, sin animaciones con movimiento reducido | `publico.css`, layout (`<details>`) | `PaginasPublicasCssTest` (toque, cortes, foco con tokens, transiciones solo con `prefers-reduced-motion: no-preference`) · `capturar.mjs --medir` (publico-inicio, publico-servicios) · verificación manual: menú con teclado y NVDA |
| CA10: título, descripción, canónica, sin `noindex`, distintos entre sí y de `/registro` | layout (`@yield`), vistas | `PaginasPublicasTest::test_metadatos_de_cada_ruta_publica`, `test_un_solo_h1` |
| RNF: WCAG 2.1 AA (contraste, títulos en orden, decorativos ocultos) | `publico.css`, vistas | `PaginasPublicasCssTest::test_contraste_de_los_pares_nuevos` · `PaginasPublicasTest::test_un_solo_h1` · verificación manual: NVDA |
| RNF: indexables con Lighthouse SEO ≥ 90 y ≤ 100 kB de JS (sin contar el JS de terceros del mapa: excepción aceptada, P10) | vistas | `PaginasPublicasTest::test_ninguna_pagina_publica_carga_scripts` · verificación manual: Lighthouse y medición de JS propio, de terceros y total de `/` con nginx local (T042), y en `/review` |
| RNF: `/` ≤ 1 MB en la primera visita | vistas, fuentes, `publico.css` | `PaginasPublicasTest::test_los_recursos_propios_de_la_raiz_pesan_menos_de_un_mega` |
| RNF: el mapa no se descarga hasta acercarse | `iframe` | `PaginaInicioTest::test_el_mapa_es_perezoso_y_avisa_de_google` · verificación manual: T042 anota si el `iframe` entra en la primera carga a 390 y 1440 px (el navegador carga los perezosos a 1250–2500 px del viewport; si entra, se aplica la excepción de P10) |
| TM1: XSS almacenado desde el catálogo | `publico/inicio`, `publico/servicios` | `ServiciosPublicosTest::test_el_texto_del_catalogo_sale_escapado` · `PaginasPublicasTest::test_las_vistas_publicas_no_usan_html_sin_escapar` |
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

- Renderizado: el del proyecto (`ssr`, Blade en el servidor), sin JavaScript.
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
     configuraciones nuevas de nginx, `publico.css` y las fuentes.
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
  imagen de producción no tiene.
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
  sin JS); el mapa es un `iframe` perezoso. El primer script público llega con el carrusel (039).
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

## Impacto en arquitectura
Lo actualiza T090 al implementar:
- **Frontend:** dos layouts (app y público), `publico.css`, fuentes autoalojadas y la excepción de
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
  de duplicarlos.
