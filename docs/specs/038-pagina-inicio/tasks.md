---
spec: 038-pagina-inicio
plan: plan.md
status: draft
---

# Tareas · 038 Página de inicio pública

Formato:
`- [ ] T### [P] <verbo + qué> — <archivos> — hecho cuando: <criterio> — cubre: CA# — depende: T###`
- T001–T089: trabajo. T090–T099: reservadas.
- `[P]` = paralelizable con las demás `[P]` de su grupo una vez cumplidas sus dependencias (no
  comparte archivos con otra tarea abierta).
- Lo generado por un comando cuenta como un archivo: `android/ (generado por npx cap add android)`.
- `[-]` = obsoleta (`— obsoleta: <motivo> (AAAA-MM-DD)`): no cuenta como abierta ni como hecha.
- Bajo una tarea: `  - nota: …`, `  - bloqueo: AAAA-MM-DD — <qué falta> — <skill a ejecutar>`.
- La comprobación a mano va en la tarea que construye la pieza:
  `hecho cuando: T010 pasa · verificación manual: <qué, dónde, contra qué>`.
- Orden con la 031 (plan D13): T004 trae `main` con la 031 liberada. Las tareas de nginx, de
  `CabecerasSeguridadTest` y de `verificar.sh` (T015, T028–T030) dependen de ella. El resto puede
  avanzar antes.

## Constitution Check
Sobre el desglose (el análisis completo está en `plan.md`, contra la constitución 1.3.0):

| Principio | Resultado | Evidencia en las tareas |
|---|---|---|
| P1 Spec aprobada antes de código | ✅ | Spec, `design.md` y plan aprobados el 2026-10-20; estas tareas se aprueban antes de `/implement` |
| P2 Test que falla antes y pasa después | ✅ | T010–T016 se escriben antes que la implementación (T020–T030) y fallan hoy por la razón esperada; cada implementación `depende:` de su test. CA2 es de caracterización (T010): se comprueba rompiendo la redirección y se anota |
| P3 Autorización en el servidor | ✅ | T022 lleva la redirección al panel y «Ir a mi panel» al servidor (`panelDelRol()`); T010 y T012 lo prueban por rol |
| P4 Entrada validada y SQL con bindings | ✅ | Ninguna ruta recibe entrada; T021 consulta con Eloquent y T012 prueba que los parámetros de la URL no cambian nada |
| P5 Nada sensible en el repositorio | ✅ | Datos de contacto ficticios de la spec (T021); la fuente lleva su licencia (T002, comprobado en T013); gitleaks en T042 |
| P6 Sin vulnerabilidades críticas o altas | ✅ | Sin dependencias nuevas (Lexend se copia, T002); `composer audit` y `npm audit` en T042 |
| P7 Auditoría | ➖ | No toca datos sensibles, autenticación ni permisos |
| P8 Correlación y logs | ➖ | No añade logs |
| P9 Solo sistema de diseño, WCAG AA | ✅ | T001 incorpora el sistema 1.7.0 antes de la UI. T014 prueba tokens = sistema, CSS sin literales (también duraciones y curvas), sin `style=""`, contraste y toque; T013 prueba títulos en orden y nombres accesibles. T025/T026 llevan la verificación manual contra `opcion-b.html` y T031 `--medir` |
| P10 Rutas indexables descubribles | ❌ aceptado en el plan (JS de terceros del mapa, aprobado por el usuario el 2026-10-20) | T013 prueba metadatos, una canónica, sin `noindex`, un `h1` y ningún script propio; T042 mide lo transferido; T040 actualiza `docs/seo.md`; Lighthouse en `/review` |

## Preparación
- [ ] T001 Incorporar al sistema de diseño la versión 1.7.0 de `design.md` («Cambios a incorporar al sistema» que necesita la 038): tokens públicos, `--green-texto`, `--duration-base`, `--ease-puerta`, componentes «Placa», «Puerta», «Cabecera pública» y «Pie público», corte de 560 px, reglas de uso y las pantallas `/` y `/servicios` en el inventario (capturas en T031) — `docs/design/system.md`, `docs/design/system.html` — hecho cuando: `system.md` declara `version: 1.7.0` con cada token y su valor de `design.md`, y `python .ai/bin/aidd.py validate` no da errores
- [ ] T002 [P] Copiar Lexend autoalojada (plan D4): `lexend-latin-400-normal.woff2`, `lexend-latin-600-normal.woff2` y su `LICENSE` como `OFL.txt`, de `@fontsource/lexend` 5.3.0 — `public/fonts/lexend/` — hecho cuando: los tres archivos existen y la nota de la tarea lleva su SHA-256 y el origen (paquete, versión y fecha)
- [ ] T004 Traer `main` a la rama con la 031 ya liberada (v0.10.0) y resolver los conflictos (plan D13) — `feature/038-pagina-inicio` (merge) — hecho cuando: el merge está hecho, la suite pasa con el `main` traído y la nota dice qué conflictos hubo; los de `routes/web.php` y `ExampleTest` (del `/fix` de `main`, plan D13) se resuelven a favor de la 038

## Tests (antes de implementar)
- [ ] T010 [P] Escribir `PaginaInicioTest`: sin sesión 200 y en el orden de CA1 con «Agendar mi primera cita» al registro, «Iniciar sesión» y crear cuenta; con sesión cada rol va a su panel (caracterización); texto exacto de «Sobre nosotros»; solo categorías activas sin servicios sueltos ni precios y «Ver todos los servicios»; sin activos no hay avance; contacto exacto con `tel:`, `iframe` (src con la dirección codificada, `loading="lazy"`, `title`, `referrerpolicy`), nota de Google y «Cómo llegar» con `rel="noopener noreferrer"` — `tests/Feature/PaginaInicioTest.php` — hecho cuando: falla por la razón esperada (la vista y la ruta nuevas no existen), salvo el caso de CA2, que pasa y se comprueba rompiendo la redirección — cubre: CA1, CA2, CA3, CA4, CA5
  - nota: A19 — hoy `/` sirve `landing.index` (o, tras el `/fix` de `main`, redirige al login): el test falla por eso. A12 — el caso «sin activos» desactiva dentro de la transacción todos los servicios, también los del volcado. A15 — el `h2` del aviso dice «Aviso de la puerta»; el enlace de la placa dice «Ver la ubicación» (→ `#ubicacion`) y solo el de la dirección es «Cómo llegar» (Google); el test de TM5 busca el enlace por su `href` de Google
- [ ] T012 [P] Escribir `ServiciosPublicosTest`: activos agrupados por categoría en orden alfabético con «Otros servicios» al final, sin inactivos, sin precio ni duración; vacío con «Pronto publicaremos nuestros servicios.»; «Volver al inicio»; con sesión se ve igual con «Ir a mi panel»; parámetros en la URL ignorados; texto del catálogo con `<script>` escapado (en `/servicios` y en `/`); una sola consulta al catálogo — `tests/Feature/ServiciosPublicosTest.php` — hecho cuando: falla por la razón esperada (`/servicios` no existe) — cubre: CA2, CA6
  - nota: A12 — los casos de vacío desactivan todos los servicios en la transacción; el orden se comprueba entre categorías propias del test. A13 — «Ortodoncia» y «ortodoncia» dan una sola puerta y una categoría real «Otros servicios» se funde con las vacías. A14 — el índice muestra el número de servicios de cada categoría. A7 — incluye un caso que llama a `Servicio::porCategoria()` directamente (lo cierra T021)
- [ ] T013 [P] Escribir `PaginasPublicasTest` para `/` y `/servicios`: título, descripción, una sola canónica absoluta desde `APP_URL` y sin `noindex`, distintos entre sí y de `/registro`; un solo `h1`; ningún `<script>`; ningún recurso externo salvo el `iframe` de Google en `/` (`src`, hojas enlazadas y `url()`/`@import` de `publico.css`); contenido completo en el HTML; recursos propios de `/` ≤ 1 MB; fuente con `OFL.txt`; vistas públicas sin `{!! !!}`; `landing/index.blade.php`, `cover-bg.jpg` y `logo-rosa.png` no existen ni se referencian; todos los enlaces internos resuelven (cada `href="#id"` con su `id`, cada ruta interna 200 o 302, con y sin servicios activos; plan «Navegación de la 038»); títulos sin saltos de nivel; nombres accesibles (logo `role="img"` con nombre, `iframe` con `title`, decorativos con `aria-hidden` o `alt=""`); un caso del layout sobre una vista de prueba que lo extiende (para cerrar T023) — `tests/Feature/PaginasPublicasTest.php` — hecho cuando: falla por la razón esperada — cubre: CA7, CA8, CA10
  - nota: A19 — el test fija `config(['app.url' => 'https://dentissa.app'])`, comprueba la canónica exacta (`https://dentissa.app/` y `https://dentissa.app/servicios`) y que un `X-Forwarded-Host` ajeno no la cambia; «nada las referencia» busca solo en `resources/` y `public/`. A3 y A11 añadidos en `/analyze`
- [ ] T014 [P] Escribir `PaginasPublicasCssTest` con el trait `ReglasCss`: tokens nuevos de `app.css` = `system.md`; `publico.css` sin literales fuera de `@font-face`; vistas públicas sin `style=""`; contraste de los pares nuevos (`--green-texto` sobre `--white` y `--bg`, `--p600` sobre `--bg`); `--touch-min` en puertas, botones, enlaces de la cabecera y `summary` del menú; cortes de 900 y 560 px; foco con `--focus-ring`; transiciones solo bajo `prefers-reduced-motion: no-preference` — `tests/Feature/PaginasPublicasCssTest.php`, `tests/Feature/Concerns/ReglasCss.php` — hecho cuando: falla por la razón esperada (faltan los tokens y `publico.css`) y cada patrón de literales tiene su control positivo — cubre: CA9 — depende: T001
  - nota: A5 — los literales de `publico.css` admiten `var(--…)` en `font-family` y detectan también duraciones (`\d(ms|s)\b`), `rem`/`em` y `cubic-bezier(`, fuera de `@font-face` y de las condiciones de `@media`; si se amplía `literales()` del trait, los tests que ya lo usan siguen en verde. A6 — los selectores son los del plan («Clases de los componentes»)
- [ ] T015 Ampliar `CabecerasSeguridadTest`: `comunes.conf` declara `frame-src $dentissa_frame_src`; `marcos.conf` tiene `default "'self'"` y una sola regla anclada con `https://www.google.com`; la expresión acepta `/` y `/?utm=x` y rechaza `/servicios`, `/admin`, `//evil` y `/x/`; las dos configuraciones incluyen `marcos.conf` antes del `server`; `verificar.sh` comprueba la redirección con `/admin`, `/` 200 y el `frame-src` de `/` y de `/servicios` — `tests/Feature/CabecerasSeguridadTest.php` — hecho cuando: los casos nuevos fallan por la razón esperada y los de la 031 siguen en verde — cubre: CA7 — depende: T004
- [ ] T016 [P] Ajustar `ProxyHttpsTest` (las dos pruebas de redirección con `/admin`, plan D10) y `ExampleTest` (vista `publico.inicio`) — `tests/Feature/ProxyHttpsTest.php`, `tests/Feature/ExampleTest.php` — hecho cuando: `ProxyHttpsTest` pasa y está comprobado rompiendo `trustProxies` (las dos pruebas en `/admin` fallan; caracterización de 030 CA2), y `ExampleTest` falla por la razón esperada (la vista nueva no existe) — cubre: CA1
  - nota: A8 — anotar «comprobado rompiendo trustProxies». A2 — tras el `/fix` de `main`, `ProxyHttpsTest` ya pasa con `/`; el cambio a `/admin` hace falta porque la 038 hace que `/` deje de redirigir
- [ ] T017 [P] Añadir a `capturar.mjs` las vistas públicas `publico-inicio` y `publico-servicios` (sin sesión) a 320, 390, 900 y 1440 px y sus comprobaciones de `--medir` (sin desbordamiento horizontal y toque ≥ 44 px), documentadas en la cabecera (plan D12) — `docs/design/capturar.mjs` — hecho cuando: `node docs/design/capturar.mjs --seco --medir` lista las comprobaciones nuevas — cubre: CA9

## Implementación
- [ ] T020 Añadir a `:root` de `app.css` los tokens de T001 (`--font-publica`, `--text-publico-h1`/`-h2` fluidos con `clamp()` y `vw` entre 320 y 1440, `--text-publico-h3`, `--text-publico`, `--text-publico-small`, `--space-seccion`, `--measure`, `--page-max`, `--duration-base`, `--ease-puerta` y `--green-texto`) — `public/css/app.css` — hecho cuando: la parte de tokens = sistema y la de contraste de T014 pasan y la suite no cambia en el resto — cubre: CA9 — depende: T001, T014
- [ ] T021 Añadir el bloque `contacto` (dirección, teléfono en texto y `tel:`, horario de CA5) y en `Servicio` el scope `activos()` y `porCategoria()` (solo `nombre`, `descripcion` y `categoria`, alfabético, «Otros servicios» al final, una consulta) — `config/clinica.php`, `app/Models/Servicio.php` — hecho cuando: el caso de T012 que llama a `Servicio::porCategoria()` directamente (agrupación, orden, columnas, «Otros servicios») pasa — cubre: CA4, CA5, CA6 — depende: T012
  - nota: A13 — agrupar sin distinguir mayúsculas ni acentos (clave como la colación, p. ej. `mb_strtolower(trim())` normalizado), mostrando la primera grafía; una categoría real «Otros servicios» se funde con las vacías
- [ ] T022 Crear `PaginasPublicasController` (`inicio()` con `panelDelRol()` para quien tiene sesión, `servicios()` sin parámetros) y sus rutas (`/` → `inicio`, `/servicios` → `servicios.publicos`, fuera de los grupos por rol, sin el closure actual) — `app/Http/Controllers/PaginasPublicasController.php`, `routes/web.php` — hecho cuando: los casos de redirección por rol de T010 (CA2) pasan y `php artisan route:list` muestra `inicio` y `servicios.publicos`; los casos de contenido pasan con T025 y T026 — cubre: CA1, CA2, CA6 — depende: T010, T012, T021
- [ ] T023 Crear el layout público: `<html lang="es">`, `<head>` con título, descripción y canónica por sección, `app.css` y `publico.css`, `body.publico`, cabecera (logo enmascarado, «Secciones», «Iniciar sesión» o «Ir a mi panel» según la sesión, menú `<details>`/`<summary>` por debajo de 900 px) y pie, sin scripts — `resources/views/layouts/publico.blade.php` — hecho cuando: el caso del layout de T013 sobre una vista de prueba (metadatos por sección, cabecera con y sin sesión, navegación de la 038, ningún script) pasa — cubre: CA1, CA2, CA8, CA10 — depende: T013, T022
- [ ] T024 Crear `publico.css`: `@font-face` de Lexend 400 y 600 (`../fonts/lexend/`), `body.publico` (anula el `display: flex` y la familia de `app.css`), «Placa», «Puerta», «Cabecera pública», «Pie público», «Sobre nosotros», «Mapa con placa» y listas de `/servicios` (índice fijo al desplazarse en el escritorio), cortes de 900 y 560 px, solo tokens — `public/css/publico.css` — hecho cuando: T014 pasa entero — cubre: CA9 — depende: T002, T014, T020
- [ ] T025 Crear la página de inicio: placa con logo, h1 «Tu sonrisa, nuestra prioridad.», texto, «Agendar mi primera cita» y aviso de la puerta (h2) con horario y «Llamar al …»; «Sobre nosotros» (texto exacto de CA3); puertas de categorías con «Ver todos los servicios»; ubicación con el `iframe` (D6), la placa de la dirección, «Cómo llegar» y la nota de Google; todo con salida escapada — `resources/views/publico/inicio.blade.php` — hecho cuando: T010 pasa entero y los casos de `/` de T013 pasan · verificación manual: `/` a 320, 390, 900 y 1440 px contra `opcion-b.html#inicio-solo038-m` y `#inicio-d` (sin la 039 ni la 040), y sin servicios activos contra `#inicio-vacio-m` — cubre: CA1, CA3, CA4, CA5, CA8 — depende: T022, T023, T024
  - nota: A15 — «Aviso de la puerta» como `h2`; «Ver la ubicación» en la placa y «Cómo llegar» solo en la dirección. A3 — la navegación es la del plan, sin «Promociones» ni «Trabajos»
- [ ] T026 Crear `/servicios`: h1 «Nuestros servicios», índice de categorías (anclas), una sección por categoría (h2) con `<dl>` nombre / descripción, estado vacío y «Volver al inicio»; salida escapada — `resources/views/publico/servicios.blade.php` — hecho cuando: T012 pasa entero y los casos de `/servicios` de T013 pasan · verificación manual: a 390 y 1440 px contra `opcion-b.html#servicios-m`, `#servicios-d` y `#servicios-vacio-m` — cubre: CA6, CA8 — depende: T022, T023, T024
  - nota: A14 — el índice muestra el número de servicios de cada categoría, como en `design.md`
- [ ] T027 Retirar la página del colaborador y sus imágenes sin uso (plan D11) — `resources/views/landing/index.blade.php`, `public/images/cover-bg.jpg`, `public/images/logo-rosa.png` — hecho cuando: los casos de retirada de T013 y `ExampleTest` (T016) pasan — cubre: CA1 — depende: T016, T025
- [ ] T028 Crear `marcos.conf` con el `map $request_uri $dentissa_frame_src` (D5) y añadir `frame-src $dentissa_frame_src;` a la política de `comunes.conf` — `docker/nginx/seguridad/marcos.conf`, `docker/nginx/seguridad/comunes.conf` — hecho cuando: los casos de `comunes.conf` y `marcos.conf` de T015 pasan — cubre: CA7 — depende: T004, T015
- [ ] T029 Incluir `marcos.conf` antes del `server` en las dos configuraciones de nginx — `docker/nginx/dentissa.conf`, `docker/nginx/dentissa.prod.conf` — hecho cuando: el caso de `include` de T015 pasa y `docker compose exec nginx nginx -t` acepta la configuración · verificación manual: con nginx local recargado, `curl -sI http://localhost:8000/` lleva `frame-src 'self' https://www.google.com` y `/servicios` `frame-src 'self'`; en el navegador, el mapa se ve en `/` y la consola no muestra bloqueos de la política en `/` ni en `/servicios` — cubre: CA5, CA7 — depende: T028
- [ ] T030 Cambiar `verificar.sh`: la comprobación pública de redirección a https con `/admin`, y nuevas «`/` responde 200», «`/` admite el mapa» y «`/servicios` no admite marcos externos» (cabecera `Content-Security-Policy`) — `deploy/verificar.sh` — hecho cuando: el caso de `verificar.sh` de T015 pasa y `bash -n deploy/verificar.sh` no da errores — cubre: CA7 — depende: T004, T015, T029
- [ ] T031 Medir y capturar las páginas públicas: `--medir` con `publico-inicio` y `publico-servicios` (procedimiento de la cabecera de `capturar.mjs`), y capturas a 320, 390, 900 y 1440 px para `system.html` (pantallas de T001) — `docs/design/capturas/` (generado por node docs/design/capturar.mjs publico-inicio publico-servicios) — hecho cuando: `--medir` da «todo en orden» para las vistas públicas · verificación manual: menú del móvil con el teclado (Tab, Intro, Espacio) y la página de inicio con NVDA (encabezados, nombre del logo, título del mapa) — cubre: CA9 — depende: T017, T025, T026

## Integración y documentación
- [ ] T040 Actualizar `docs/seo.md` (filas de `/` y `/servicios`, «Se indexa»: `/registro`, `/` y `/servicios`; `/` deja de redirigir) y el criterio del objetivo 7 en `docs/roadmap.md` (el sitemap tendrá que incluir `/` y `/servicios`) — `docs/seo.md`, `docs/roadmap.md` — hecho cuando: los dos documentos dicen lo del plan (Descubribilidad) y `validate` no da errores — cubre: CA10
  - nota: A17 — la corrección del sitemap en `docs/seo.md` deja de decir «la única ruta indexable» y pasa a `/registro`, `/` y `/servicios`; la regla «sin barra final» aclara que la raíz es `https://dentissa.app/`. La enmienda PATCH de P10 («hoy solo `/registro`») queda en el roadmap
- [ ] T041 Actualizar `AGENTS.md` (dos layouts: app y público, plan D2; ruta protegida aprobada por el usuario el 2026-10-20, `/analyze` A18) y añadir filas de Historial en la 001 («CA4 reemplazado por la 038 CA1») y en la 030 («la ruta de prueba de CA2 pasa a `/admin`, 038 D10») — `AGENTS.md`, `docs/specs/001-autenticacion/spec.md`, `docs/specs/030-despliegue-servidor-propio/spec.md` — hecho cuando: la regla de layouts y los Historiales lo dicen y `validate` no da errores
- [ ] T043 Anotar en la 031 que su CA1 queda ampliado por 038 CA7 (Historial) y registrar en `docs/security.md` las entradas públicas `GET /` y `GET /servicios`, el riesgo aceptado TM6 (sin límite propio) y el envío de la visita a Google por el mapa (`/analyze` A9, A10) — `docs/specs/031-endurecer-produccion/spec.md`, `docs/security.md` — hecho cuando: los dos documentos lo dicen y `validate` no da errores
- [ ] T042 Verificación final: suite completa, `pint --test` sobre lo tocado, gitleaks, semgrep `p/php`, `composer audit`, `npm audit` y `python .ai/bin/aidd.py validate`; Lighthouse SEO sobre `/` y `/servicios` servidos por nginx local (se repite en `/review`); medición de `/` con caché vacía a 390 y 1440 px: JS propio, JS de terceros, total transferido y si el `iframe` del mapa entra en la primera carga (excepción de P10, `/analyze` A4) — sin archivos — hecho cuando: todo en verde o con la excepción anotada en la nota, Lighthouse SEO ≥ 90 en las dos rutas, JS propio 0 kB y la medición anotada
- [ ] T090 Actualizar `docs/architecture.md` (Frontend: dos layouts, `publico.css`, fuentes autoalojadas y `frame-src` en `/`; Módulos: páginas públicas; Integraciones externas: mapa de Google y «Cómo llegar»; Despliegue: `marcos.conf`)
- [ ] T091 Actualizar `docs/deployment.md` (comprobaciones nuevas de `verificar.sh` y `marcos.conf` en la imagen `web`) y `docs/observability.md` si hace falta (la 038 no añade logs ni eventos)
- [ ] T092 Marcar spec como `implemented`

## Despliegue (lo ejecuta `/release`)
- [ ] T095 Desplegar a staging y verificar criterios de aceptación — no aplica: el proyecto no tiene staging (`docs/deployment.md`); las comprobaciones de producción van en T097
- [ ] T096 Aprobación humana para producción
- [ ] T097 Desplegar a producción y vigilar métricas del plan (Rollout)
  - `deploy/desplegar.sh v0.11.0` (o la versión que toque) y `deploy/verificar.sh` sin fallos nuevos: `/` 200 con el mapa admitido, `/servicios` sin marcos externos y `/admin` redirige a `https://…/login`
  - en https://dentissa.app/: la página de inicio contra `opcion-b.html#inicio-d` y en un móvil, el mapa visible, la consola sin bloqueos de la política, «Llamar» y «Cómo llegar» funcionan
  - sin 5xx nuevos en `dentissa-access.log` en los 15 minutos siguientes
- [ ] T098 Marcar spec como `released`

## Cobertura
| Criterio | Tarea(s) de test | Tarea(s) de implementación |
|---|---|---|
| CA1 | T010, T016 | T022, T023, T025, T027 |
| CA2 | T010, T012 | T022, T023 |
| CA3 | T010 | T025 |
| CA4 | T010 | T021, T025 |
| CA5 | T010 | T021, T025, T029 |
| CA6 | T012 | T021, T022, T026 |
| CA7 | T013, T015 | T028, T029, T030 |
| CA8 | T013 | T023, T025, T026 |
| CA9 | T014, T017 | T020, T024, T031 |
| CA10 | T013 | T023, T040 |

| Amenaza (TM#) | Tarea(s) de control | Tarea(s) de test |
|---|---|---|
| TM1 | T025, T026 | T012, T013 |
| TM2 | T021 | T010, T012 |
| TM3 | T028, T030 | T015 |
| TM4 | T025 | T010 |
| TM5 | T025 | T010 |
| TM6 | T021 | T012 |

| Cambio del plan (módulo) | Tarea(s) |
|---|---|
| `routes/web.php` | T022 |
| `app/Http/Controllers/PaginasPublicasController.php` | T022 |
| `app/Models/Servicio.php` | T021 |
| `config/clinica.php` | T021 |
| `resources/views/layouts/publico.blade.php` | T023 |
| `resources/views/publico/inicio.blade.php` | T025 |
| `resources/views/publico/servicios.blade.php` | T026 |
| `resources/views/landing/index.blade.php` | T027 |
| `public/css/publico.css` | T024 |
| `public/css/app.css` | T020 |
| `public/fonts/lexend/` | T002 |
| `public/images/` | T027 |
| `docker/nginx/seguridad/marcos.conf` | T028 |
| `docker/nginx/seguridad/comunes.conf` | T028 |
| `docker/nginx/dentissa.conf`, `docker/nginx/dentissa.prod.conf` | T029 |
| `deploy/verificar.sh` | T030 |
| `tests/Feature/ProxyHttpsTest.php`, `tests/Feature/ExampleTest.php` | T016 |
| `tests/Feature/CabecerasSeguridadTest.php` | T015 |
| Tests nuevos | T010, T012, T013, T014 |
| `docs/design/capturar.mjs` | T017, T031 |
| Documentación | T001, T040, T041, T043, T090, T091 |

| Requisito no funcional | Tarea(s) de test o verificación manual (T095) |
|---|---|
| WCAG 2.1 AA (contraste, títulos en orden, decorativos ocultos) | T014, T013 (un `h1`, títulos en orden, nombres accesibles) · verificación manual en T031 (NVDA) |
| Indexables con Lighthouse SEO ≥ 90 y ≤ 100 kB de JS (excepción de P10 para el JS de terceros del mapa) | T013 (sin scripts) · T042 (Lighthouse y medición) |
| `/` ≤ 1 MB en la primera visita | T013 |
| El mapa no se descarga hasta acercarse | T010 (`loading="lazy"`) · T042 (si entra en la primera carga) |
