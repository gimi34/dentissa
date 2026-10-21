---
status: inferred
updated: 2026-10-20
---

# SEO técnico de Dentissa

Fuente de verdad de lo que un buscador puede rastrear, indexar e interpretar del sitio público y de
cómo se ve al compartirlo (`shared/contract.md` → "SEO técnico"). Los temas que se comprueban están
en `shared/seo-checklist.md`; aquí van las reglas del sitio, el estado de cada ruta y las brechas.
No incluye palabras clave, estrategia de contenido ni enlaces externos.

Dentissa es una app privada de gestión de la clínica. Lo público es la página de inicio (`/`) y la
lista de servicios (`/servicios`), desde la spec 038, más el acceso y el registro de pacientes. Se
indexan `/registro` (decisión del usuario, 2026-10-16), `/` y `/servicios` (decisión del usuario en la
spec 038, 2026-10-20), que presentan la clínica e invitan a crear la cuenta; el resto queda tras el
inicio de sesión.

## Reglas del sitio
- URL canónica: https://dentissa.app · variante única: sin `www` (`www.dentissa.app` no resuelve;
  comprobado el 2026-10-16) y sin barra final, salvo la raíz, cuya canónica es
  `https://dentissa.app/` (spec 038, `/analyze` A17). `http://` redirige a `https://` con `301` (nginx
  de producción; `deploy/verificar.sh` lo comprueba con `/admin` desde la spec 038).
- Idiomas: es (un solo idioma; `<html lang="es">` en todas las vistas).
- Renderizado: ssr — Laravel con Blade renderiza en el servidor el HTML completo de cada ruta; no
  depende de JavaScript.
- Se indexa: `/registro` (decisión del usuario, 2026-10-16), `/` y `/servicios` (spec 038,
  decisión del usuario, 2026-10-20).
- JavaScript: `/` y `/servicios` no cargan JavaScript propio. `/` incrusta el mapa de Google
  (`iframe` perezoso), cuyo JavaScript es de terceros y no cuenta para el límite de P10: excepción
  aceptada por el usuario en el plan de la 038 (2026-10-20).
- No se indexa: `/login` (inicio de sesión; decisión del usuario, 2026-10-16, conforme a
  `shared/seo-checklist.md` §2), `/registro/contacto` (paso con sesión), la 404, `/up` (salud) y
  todas las rutas tras inicio de sesión — mecanismo: `noindex` en meta (pendiente, SE2); las rutas
  privadas ya redirigen sin sesión.
- Páginas de error: 404 genérica de Laravel, en inglés (SE4); rutas privadas sin sesión →
  redirección `302` a `/login` (comprobado en local y en producción); fuera del sitemap.
- Archivos generados: `robots.txt` estático en `public/` (permite todo y no declara sitemap);
  `sitemap.xml` no existe (SE3).
- Compartir: sin Open Graph (SE6); sin imagen general.
- Íconos: `public/favicon.ico` vacío (0 bytes) y ningún ícono enlazado en el `<head>`; sin
  `theme-color` (SE5).
- Datos estructurados: ninguno (SE7).
- Presupuesto: la constitución no fija presupuesto de JavaScript ni de Lighthouse; no se midió
  (`shared/seo-checklist.md` §10).

## Inventario de rutas
Comprobado el 2026-10-16 con `curl` contra la v0.8.0 servida en local (base de prueba) y, con
permiso del usuario, con `GET` a https://dentissa.app (solo códigos de estado y cabeceras).

| Ruta | Indexable | Título | Descripción | Canónica | `h1` | Open Graph | Datos estructurados | Evidencia |
|---|---|---|---|---|---|---|---|---|
| `/` | sí (spec 038) | ✅ «Dentissa · Tu sonrisa, nuestra prioridad» | ✅ propia | ✅ `https://dentissa.app/` | ✅ uno («Tu sonrisa, nuestra prioridad.») | ➖ (fuera de alcance, SE6) | ➖ (SE7) | Spec 038, rama sin liberar: `200` sin sesión (con sesión, `302` al panel del rol) · `PaginasPublicasTest::test_metadatos_de_cada_ruta_publica` · `resources/views/publico/inicio.blade.php` |
| `/servicios` | sí (spec 038) | ✅ «Servicios · Dentissa» | ✅ propia | ✅ `https://dentissa.app/servicios` | ✅ uno («Nuestros servicios») | ➖ (SE6) | ➖ (SE7) | Spec 038, rama sin liberar: `200` con y sin sesión · `PaginasPublicasTest` · `resources/views/publico/servicios.blade.php` |
| `/registro` | sí | ✅ «Dentissa · Crear cuenta» | ❌ sin `<meta name="description">` | ❌ sin `<link rel="canonical">` | ✅ uno («Únete a nuestra comunidad») | ❌ | ❌ | `curl -s /registro` · `resources/views/auth/register.blade.php:6,33`; `200` en producción |
| `/login` | no (inicio de sesión) | ✅ «Dentissa · Iniciar sesión» | ➖ | ➖ | ✅ uno | ➖ | ➖ | ❌ sin `noindex` (`curl -s /login`; sin `X-Robots-Tag`) · `resources/views/auth/login.blade.php:6` |
| `/registro/contacto` | no (paso con sesión) | ➖ | ➖ | ➖ | ➖ | ➖ | ➖ | `302` a `/registro` sin sesión |
| rutas privadas (`/admin`, `/cliente`…) | no (tras inicio de sesión) | ➖ | ➖ | ➖ | ➖ | ➖ | ➖ | `302` a `/login` sin sesión, en local y en producción |
| 404 | no (página de error) | genérica «Not Found» (inglés) | ➖ | ➖ | ➖ | ➖ | ➖ | `404` real en local y en producción (`/pagina-que-no-existe`) |
| `/up` | no (salud) | ➖ | ➖ | ➖ | ➖ | ➖ | ➖ | `200` HTML de Laravel, sin `noindex` |
| `/robots.txt` | no (archivo de rastreo) | — | — | — | — | — | — | `200`: `User-agent: *` · `Disallow:` (sin `Sitemap:`) |
| `/sitemap.xml` | no (archivo de rastreo) | — | — | — | — | — | — | `404` en local y en producción |

## Brechas
### SE1 · media — `/registro`, la única ruta indexable, sin descripción ni canónica
Evidencia: `curl -s http://…/registro` sin `<meta name="description">` ni `<link rel="canonical">`;
`resources/views/auth/register.blade.php:1-12`.
Correcciones:
- SE1.a `<meta name="description">` propia de `/registro` (70–160 caracteres, texto aprobado por el usuario) — estado: pendiente
- SE1.b `<link rel="canonical" href="https://dentissa.app/registro">`, construida desde `APP_URL` (`seo.site_url`) — estado: pendiente

### SE2 · media — Las páginas no indexables se pueden indexar
Evidencia: `/login` responde `200` sin `<meta name="robots">` ni cabecera `X-Robots-Tag`; `/up`
responde `200` con HTML y tampoco lo lleva.
Correcciones:
- SE2.a `noindex` en `/login` (y en `/registro/contacto`, si alguna vez se sirve sin redirigir) — estado: pendiente
- SE2.b `noindex` en la 404 propia (SE4) y `X-Robots-Tag: noindex` en `/up` — estado: pendiente

### SE3 · media — Sin `sitemap.xml` y `robots.txt` sin sitemap
Evidencia: `/sitemap.xml` → `404` en local y en producción; `public/robots.txt` sin línea `Sitemap:`.
Correcciones:
- SE3.a `sitemap.xml` con las rutas indexables (`https://dentissa.app/registro`, `https://dentissa.app/` y `https://dentissa.app/servicios`, spec 038) — estado: pendiente
- SE3.b `Sitemap: https://dentissa.app/sitemap.xml` en `robots.txt` — estado: pendiente

### SE4 · baja — Sin página 404 propia
Evidencia: `/pagina-que-no-existe` → `404` con la página genérica de Laravel «Not Found», en inglés y
sin navegación (no hay `resources/views/errors/`).
Correcciones:
- SE4.a Página 404 propia, en español, con enlace al inicio de sesión y al registro y `noindex` (con SE2.b) — estado: pendiente

### SE5 · baja — Sin íconos del sitio ni `theme-color`
Evidencia: `public/favicon.ico` vacío (0 bytes, el de la plantilla de Laravel) y ningún
`<link rel="icon">` ni `apple-touch-icon` en el `<head>` de las vistas; sin
`<meta name="theme-color">`.
Correcciones:
- SE5.a `favicon.svg` (sin `<text>`), PNG de 32×32 y `apple-touch-icon` de 180×180 a partir del logo actual (`public/images/logo-icon.png`), enlazados en todas las rutas — estado: pendiente
- SE5.b `<meta name="theme-color">` con el fondo del sistema (`--bg`, `#FDF5F8`) — estado: pendiente

### SE6 · baja — `/registro` sin Open Graph
Evidencia: `curl -s /registro` sin etiquetas `og:*`.
Correcciones:
- SE6.a `og:title`, `og:description`, `og:type`, `og:url` (= canónica), `og:locale` e imagen general de 1200×630 con su `alt`, desde la «Tarjeta para compartir» del sistema de diseño (que hoy no la define) — estado: pendiente

### SE7 · baja — Sin datos estructurados
Evidencia: ninguna vista emite JSON-LD.
Correcciones:
- SE7.a JSON-LD de la clínica (`Dentist`) en `/registro`, solo con datos reales confirmados por el usuario (nombre, dirección, teléfono) — estado: pendiente

## Brechas por confirmar
- Rendimiento y Lighthouse SEO de `/registro` — depende de: un presupuesto en la constitución o
  que el usuario pida medirlo — cómo confirmarla: Lighthouse sobre la build servida en local.

## Verificación en producción
| Fecha | Comprobación | Resultado | Evidencia |
|---|---|---|---|
| 2026-10-16 | `robots.txt` responde `200` | ✅ | `curl https://dentissa.app/robots.txt` (permite todo, sin `Sitemap:`) |
| 2026-10-16 | `sitemap.xml` responde `200` | ❌ | `404` (SE3) |
| 2026-10-16 | Producción sin `noindex` en la ruta indexable | ✅ | `/registro` `200` sin `X-Robots-Tag` ni meta robots |
| 2026-10-16 | Ruta privada sin sesión redirige | ✅ | `/admin` → `302` a `/login` |
| 2026-10-16 | URL inexistente responde `404` | ✅ | `/pagina-que-no-existe` → `404` (genérica, SE4) |
| 2026-10-16 | Variante única del dominio | ✅ | `www.dentissa.app` no resuelve; `http` → `301` a `https` (`verificar.sh`) |

## Exclusiones deliberadas
- Sin etiquetas `twitter:*`: si se añade Open Graph (SE6), basta para las redes que se usan — propuesta, pendiente de confirmar por el usuario.

## Pendientes operativos
- Alta de https://dentissa.app en las consolas de los buscadores y envío del sitemap, cuando exista (SE3).

## Historial
| Fecha | Cambio | Origen |
|---|---|---|
| 2026-10-16 | Documento creado: auditoría en local (v0.8.0) y `GET` a producción | /init --upgrade (AI-DD 1.13.0) |
