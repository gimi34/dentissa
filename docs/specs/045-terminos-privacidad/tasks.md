---
spec: 045-terminos-privacidad
plan: plan.md
impl_base: 4c67991
status: approved
---

# Tareas · 045 Términos de uso y aviso de privacidad

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

Medir la app (T027, tras los enlaces del pie de T024, que mide K5; A6): con la app en `http://localhost:8000`, desde la imagen de Playwright 1.63.0
en la red de Compose, `node docs/specs/045-terminos-privacidad/design/medir.mjs http://dentissa_nginx
--evaluate <evaluate.mjs> --out .ai/inspect/045-build` (la `url` de cada pantalla la añade T003).

## Constitution Check
Las tareas siguen el Constitution Check del plan (8 ✅, 2 ➖, ninguna violación); lo que el desglose
podía romper:

| Principio | Resultado | Cómo lo respetan las tareas |
|---|---|---|
| P1 Spec aprobada antes de código | ✅ | Spec, `design.md` y plan aprobados; estas tareas se aprueban antes de `/implement` |
| P2 Test que falla antes y pasa después | ✅ | Los tests (T010–T014) van antes de la implementación y cada tarea de implementación depende del suyo. Las caracterizaciones del plan (CA5 con Google, CA7 sin redirigir) anotan en T011 y T010 la rotura comprobada |
| P3 Autorización en el servidor | ✅ | T010 prueba las dos páginas con y sin sesión; no hay rutas protegidas nuevas |
| P4 Entrada validada y SQL con bindings | ✅ | T026 añade la regla `terminos` del registro normal tras su test (T011); T027 pasa `semgrep` sobre lo tocado |
| P5 Nada sensible en el repositorio | ✅ | T027 pasa `gitleaks` |
| P6 Sin vulnerabilidades críticas o altas | ✅ | T002 sube `phpseclib` antes de todo lo demás y deja `composer audit --locked` limpio; sin dependencias nuevas |
| P7 Auditoría | ➖ | Ninguna tarea toca datos sensibles, autenticación ni permisos |
| P8 Correlación y logs | ➖ | Ninguna tarea añade logs |
| P9 Sistema de diseño | ✅ | T001 aplica primero «Cambios a incorporar al sistema» (1.15.0) y las tareas de UI dependen de ella; T014 prueba solo tokens; T025 quita los `style=""` de los enlaces |
| P10 Rutas indexables descubribles | ✅ | T013 mete las dos rutas en los tests de metadatos y contenido; T027 incluye Lighthouse SEO ≥ 90 en local; T030 actualiza `docs/seo.md` |

## Preparación
- [ ] T001 Aplicar «Cambios a incorporar al sistema» de `design.md` como versión 1.15.0: `--font-display` con «Newsreader respaldo» y «Newsreader respaldo Times» (las seis caras con sus medidas), los componentes de la página legal (`.lb-legal` con su corte en 900 px, `.lb-texto`, `.lb-num`, título del índice, `.lb-resumen`, `scroll-margin-top`) y su fila en «Decisiones»; en `system.html`, el mismo valor del token y la sección de la página legal — `docs/design/system.md`, `docs/design/system.html` — hecho cuando: los dos documentan el token con el valor del plan y los cinco componentes con sus tokens, `version: 1.15.0`; se hace commit junto con T020 (D9)
- [ ] T002 Subir `phpseclib/phpseclib` de 3.0.57 a 3.0.58 con `composer update phpseclib/phpseclib` (solo ese paquete) — `composer.lock` — hecho cuando: la 3.0.58 tiene al menos 7 días (`composer show phpseclib/phpseclib 3.0.58 --all`: publicada el 2026-11-02, A4); el diff del lock solo cambia `phpseclib`; `composer audit --locked` y `composer audit --locked --ignore-severity=low --ignore-severity=medium` (el de la CI) sin avisos; los tests del inicio de sesión con Google (`GoogleVueltaFallidaTest` y los que usan Socialite) en verde
- [x] T003 [P] Añadir la `url` de cada pantalla en `criterios.json` (`terminos` → `terminos`, `privacidad` → `aviso-de-privacidad`) para medir la app servida (D8) — `docs/specs/045-terminos-privacidad/design/criterios.json` — hecho cuando: `medir.mjs` con una URL base pide `/terminos` y `/aviso-de-privacidad`, y con una carpeta sigue usando `file`; `python .ai/bin/aidd.py validate docs/specs/045-terminos-privacidad` sin errores

## Tests (antes de implementar)
- [ ] T010 Escribir en `TerminosPrivacidadTest` los tests de las dos páginas: `test_terminos_tiene_sus_secciones_y_la_fecha` (CA1: título, «Última actualización: 12 de noviembre de 2026» y los siete títulos de sección en orden), `test_el_aviso_tiene_sus_secciones_y_la_fecha` (CA2: los ocho títulos, «datos sensibles» junto a los de salud, «cinco años» del expediente, los derechos de acceso, rectificación, cancelación y oposición, las cookies), `test_los_datos_de_la_clinica_salen_de_la_configuracion` (dirección, teléfono con su `tel:` y horario en texto de `config('clinica.contacto')`), `test_el_aviso_nombra_cada_dato_del_registro` (CA3: cada `name` de los formularios de `register.blade.php` y `registro-contacto.blade.php`, salvo `_token`, `password_confirmation` y `terminos`, y cada dato que guarda `GoogleAuthController`, mapeado a su texto del aviso; un campo sin entrada en el mapa hace fallar el test), `test_el_indice_va_antes_de_las_secciones` (K3: «En esta página» con un enlace `#id` por sección, antes de la primera `section`; y la estructura accesible de `design.md`: el `nav` con `aria-labelledby` a su título, cada `section` con `aria-labelledby` a su `h2`, el número de sección con `aria-hidden` y el resumen como `aside` con su título, A11), `test_con_y_sin_sesion_se_ven_igual` (CA7: `200` sin sesión con «Iniciar sesión» y «Crear cuenta», y con sesión de cada rol con «Ir a mi panel», sin redirigir) y `test_el_texto_es_el_aprobado` (el texto normalizado de `<main>` de cada página es el de `candidatos/c4/terminos.html` y `privacidad.html`) — `tests/Feature/TerminosPrivacidadTest.php` — hecho cuando: los siete fallan por la razón esperada (`404` en las rutas; el de CA3, porque el aviso no existe) — cubre: CA1, CA2, CA3, CA7, CA8
  - verificación de caracterización (CA7, «sin redirigir»): tras T023, se comprueba rompiendo el método con un `redirect()` para la sesión iniciada; debe fallar, se restaura y se anota aquí
- [ ] T011 Escribir en `TerminosPrivacidadTest` los tests del registro: `test_la_casilla_del_registro` (CA4 y TM1: en `/registro` y en `/registro/contacto` con la sesión de Google, el texto de la etiqueta sin las dos anotaciones «(se abre en otra pestaña)» es exactamente «Acepto los términos de uso y el aviso de privacidad de Dentissa» (A7), cada enlace a `route('terminos')` y `route('privacidad')` con `target="_blank"`, `rel` con `noopener` y «(se abre en otra pestaña)» dentro del enlace, sin atributo `style`), `test_el_registro_sin_la_casilla_se_rechaza` (CA5: `POST /registro` válido sin `terminos` → error en `terminos` con «Debes aceptar los términos de uso y el aviso de privacidad.» y ninguna cuenta nueva con ese correo) y `test_el_registro_con_google_sin_la_casilla_se_rechaza` (CA5 en `/registro/contacto`) — `tests/Feature/TerminosPrivacidadTest.php` — hecho cuando: fallan por la razón esperada (el texto y los enlaces; en `/registro`, la cuenta se crea; con Google, el mensaje antiguo) — cubre: CA4, CA5 — depende: T010
  - verificación de caracterización (CA5 con Google, el rechazo): tras T026, se comprueba quitando la regla `terminos` de `contactoStore`; debe fallar, se restaura y se anota aquí
- [ ] T012 Escribir en `TerminosPrivacidadTest` `test_el_pie_publico_enlaza_las_dos_paginas` (CA6: en `/`, `/servicios`, `/galeria`, `/terminos` y `/aviso-de-privacidad`, con y sin sesión, «Términos de uso» y «Aviso de privacidad» dentro de `.pub-foot__links`, a `route('terminos')` y `route('privacidad')`, sin `target`) — `tests/Feature/TerminosPrivacidadTest.php` — hecho cuando: falla porque el pie no los tiene — cubre: CA6 — depende: T011
- [ ] T013 [P] Añadir `/terminos` y `/aviso-de-privacidad` a `METADATOS` de `PaginasPublicasTest` con el título, la descripción y la canónica de «Descubribilidad» del plan; así entran en los tests de un `h1`, títulos en orden, nombres accesibles, un solo script con `defer`, sin recursos externos y enlaces internos que resuelven — `tests/Feature/PaginasPublicasTest.php` ; además, `publico/terminos.blade.php` y `publico/aviso-privacidad.blade.php` en la lista `VISTAS` del test de salida sin escapar (TM2, A1). El contenido en el HTML del servidor (CA8) lo cubre `test_el_texto_es_el_aprobado` de T010 (A5) — hecho cuando: falla por el `404` de las dos rutas y por las vistas que aún no existen — cubre: CA8
- [ ] T014 [P] En `PaginasPublicasCssTest`: el valor nuevo de `--font-display` en `TOKENS` y `EN_EL_SISTEMA`; el filtro de `test_font_face_declara_las_dos_familias_autoalojadas` deja fuera también las caras de «Newsreader respaldo» y «Newsreader respaldo Times»; `test_newsreader_tiene_un_respaldo_con_sus_medidas` (las seis caras de `design.md` con sus `local()`, `size-adjust`, `ascent-override`, `descent-override` y `line-gap-override: 0%`, sin `url()`), y `test_la_pagina_legal_lee_a_la_medida` (K4: `.lb-legal .lb-texto` y sus párrafos y listas con `max-width: var(--measure)`; la rejilla con sus zonas y una columna dentro de `@media (max-width: 899px)`), y las dos vistas nuevas en su lista `VISTAS` y `/terminos` y `/aviso-de-privacidad` en las rutas de `test_las_vistas_publicas_no_llevan_estilos_en_linea` (P9, A2) — `tests/Feature/PaginasPublicasCssTest.php` — hecho cuando: los nuevos y el de tokens fallan por la razón esperada — cubre: CA1, CA2 — depende: T001
- [ ] T015 [P] Añadir `'terminos' => 'on'` a `altaValida()` de `LimiteIntentosTest`, que da de alta sin la casilla — `tests/Feature/LimiteIntentosTest.php` — hecho cuando: `LimiteIntentosTest` sigue en verde (antes y después de T026) — cubre: CA5

## Implementación
- [ ] T020 Aplicar el sistema 1.15.0 al CSS: en `app.css`, el valor nuevo de `--font-display`; en `publico.css`, las seis `@font-face` de respaldo de Newsreader tras las de Montserrat y las reglas de `candidatos/c4/legal.css` de la página legal (sin la redefinición del token), con su comentario — `public/css/app.css`, `public/css/publico.css` — hecho cuando: T014 pasa y `PaginasPublicasCssTest` entero en verde (solo tokens) — cubre: CA1, CA2 — depende: T001, T014
- [ ] T021 Añadir las rutas `GET /terminos` (`terminos`) y `GET /aviso-de-privacidad` (`privacidad`) junto a `/servicios`, y en `PaginasPublicasController` los métodos `terminos()` y `privacidad()` con `horarioEnTexto()` («de lunes a viernes de 9:00 a 20:00 y los sábados de 9:00 a 14:00» desde `config('clinica.contacto.horario')`) — `routes/web.php`, `app/Http/Controllers/PaginasPublicasController.php` — hecho cuando: `php artisan route:list` muestra las dos rutas con sus nombres y los tests de T010 dejan de fallar por `404` (pasan a fallar por la vista que falta, A12) — cubre: CA1, CA2, CA7 — depende: T010
- [ ] T022 Crear la vista de términos con `@extends('layouts.publico')`, el título, la descripción y la canónica del plan, y el marcado y el texto de `candidatos/c4/terminos.html` (dirección, teléfono, `tel:` y horario desde la configuración; solo dobles llaves) — `resources/views/publico/terminos.blade.php` — hecho cuando: pasan `test_terminos_tiene_sus_secciones_y_la_fecha`, `test_los_datos_de_la_clinica_salen_de_la_configuracion`, `test_el_indice_va_antes_de_las_secciones` y `test_el_texto_es_el_aprobado` (términos) · verificación manual: `/terminos` a 320, 390 y 1440 px contra `candidatos/c4/terminos.html` — cubre: CA1, CA7, CA8 — depende: T020, T021
- [ ] T023 Crear la vista del aviso de privacidad igual que T022, con el texto de `candidatos/c4/privacidad.html` — `resources/views/publico/aviso-privacidad.blade.php` — hecho cuando: pasan `test_el_aviso_tiene_sus_secciones_y_la_fecha`, `test_el_aviso_nombra_cada_dato_del_registro`, `test_el_texto_es_el_aprobado` (aviso) y T013 · verificación manual: `/aviso-de-privacidad` a 320, 390 y 1440 px contra `candidatos/c4/privacidad.html`, y que el aviso no menciona datos ni usos que el sitio no tiene (CA3) — cubre: CA2, CA3, CA7, CA8 — depende: T013, T022
- [ ] T024 Añadir al final de `.pub-foot__links` «Términos de uso» (`route('terminos')`) y «Aviso de privacidad» (`route('privacidad')`) — `resources/views/layouts/publico.blade.php` — hecho cuando: T012 pasa y `PaginasPublicasTest` y `NavegacionSalidasTest` siguen en verde — cubre: CA6 — depende: T012, T022, T023
- [ ] T025 Cambiar la casilla de los dos registros: «Acepto los términos de uso y el aviso de privacidad de Dentissa», con cada enlace a su ruta, `target="_blank"`, `rel="noopener"` y «(se abre en otra pestaña)» dentro del enlace, sin `style=""`; en `auth.css`, `.page-register .checkbox-label a { color: var(--p500); }` — `resources/views/auth/register.blade.php`, `resources/views/auth/registro-contacto.blade.php`, `public/css/auth.css` — hecho cuando: `test_la_casilla_del_registro` pasa · verificación manual: en el navegador, abrir «aviso de privacidad» desde el registro a medio rellenar y volver a su pestaña con lo escrito (CA4) — cubre: CA4 — depende: T011, T021
- [ ] T026 En `RegisterController`: regla `'terminos' => ['accepted']` en `register()` con el mensaje «Debes aceptar los términos de uso y el aviso de privacidad.», y el mismo mensaje en `contactoStore()` (D5) — `app/Http/Controllers/RegisterController.php` — hecho cuando: `test_el_registro_sin_la_casilla_se_rechaza` y `test_el_registro_con_google_sin_la_casilla_se_rechaza` pasan, y `LimiteIntentosTest` sigue en verde — cubre: CA5 — depende: T011, T015
- [ ] T027 Verificación final: suite completa (`php artisan test`), Pint sobre los PHP tocados, `semgrep` y `gitleaks` sobre lo tocado, `composer audit --locked` sin avisos, `medir.mjs` en las dos páginas de la app (puertas y KPIs; K3 y K4 por sus tests) y Lighthouse SEO de `/terminos` y `/aviso-de-privacidad` en local — sin archivos — hecho cuando: todo en verde, Lighthouse SEO ≥ 90 en las dos, y el resultado anotado en esta tarea — cubre: CA1, CA2, CA3, CA4, CA5, CA6, CA7, CA8 — depende: T002, T003, T013, T022, T023, T024, T025, T026

## Integración y documentación
- [ ] T030 Añadir `/terminos` y `/aviso-de-privacidad` al inventario de `docs/seo.md` (indexables, con título, descripción, canónica, un `h1` y su evidencia), a la lista de páginas de `layouts/publico.blade.php` en `AGENTS.md` y a los puntos de entrada públicos de `docs/security.md`, donde también se anota el aviso de `phpseclib` (PKSA-h7cz-q7zq-mdkt) y su corrección con la 3.0.58 (A10) — `docs/seo.md`, `AGENTS.md`, `docs/security.md` — hecho cuando: las filas y la línea de `AGENTS.md` dicen lo que hace el código — depende: T027
- [ ] T090 Actualizar `docs/architecture.md` si cambió la estructura: la fila «Páginas públicas» con `/terminos` y `/aviso-de-privacidad`, el pie del layout público y la validación de la casilla en `POST /registro` — depende: T027
- [ ] T091 Actualizar `docs/deployment.md` y `docs/observability.md` si cambiaron variables, entornos, pasos de deploy, logs, eventos de auditoría o métricas: en `deployment.md` → «Verificación post-deploy», que `/terminos` y `/aviso-de-privacidad` respondan `200`; `observability.md` sin cambios (sin logs ni eventos nuevos) — depende: T027
- [ ] T092 Marcar spec como `implemented` — depende: T030, T090, T091

## Despliegue (lo ejecuta `/release`)
- [ ] T095 Desplegar a staging y verificar criterios de aceptación. No hay staging: la comprobación se hace en producción, dentro de T097
  - `/terminos` y `/aviso-de-privacidad` responden `200` con su texto, con el logo, y aparecen en el pie de `/`
  - desde `/registro`, los dos enlaces abren otra pestaña y la del registro conserva lo escrito
- [ ] T096 Aprobación humana para producción
- [ ] T097 Desplegar a producción y vigilar métricas del plan (Rollout): `deploy/desplegar.sh` con la versión nueva, `verificar.sh` y la comprobación de T095; vigilar `5xx` en `/registro` la primera hora
- [ ] T098 Marcar spec como `released`; en las specs 002 y 038 anotar en su Historial lo que cambia la 045 (casilla y validación del registro; pie público)

## Cobertura
| Criterio | Tarea(s) de test | Tarea(s) de implementación |
|---|---|---|
| CA1 | T010, T014 | T020, T021, T022 |
| CA2 | T010, T014 | T020, T021, T023 |
| CA3 | T010 | T023 |
| CA4 | T011 | T025 |
| CA5 | T011, T015 | T026 |
| CA6 | T012 | T024 |
| CA7 | T010 | T021, T022, T023 |
| CA8 | T010, T013 | T022, T023 |

| Amenaza (TM#) | Tarea(s) de control | Tarea(s) de test |
|---|---|---|
| TM1 | T025 | T011 |
| TM2 | T022, T023 | T013 |

| Cambio del plan (módulo) | Tarea(s) |
|---|---|
| `composer.lock` | T002 |
| `docs/design/system.md`, `system.html` | T001 |
| `public/css/app.css` | T020 |
| `public/css/publico.css` | T020 |
| `routes/web.php` | T021 |
| `app/Http/Controllers/PaginasPublicasController.php` | T021 |
| `resources/views/publico/terminos.blade.php`, `aviso-privacidad.blade.php` | T022, T023 |
| `resources/views/layouts/publico.blade.php` | T024 |
| `resources/views/auth/register.blade.php`, `registro-contacto.blade.php` | T025 |
| `public/css/auth.css` | T025 |
| `app/Http/Controllers/RegisterController.php` | T026 |
| `tests/Feature/TerminosPrivacidadTest.php` | T010, T011, T012 |
| `tests/Feature/PaginasPublicasTest.php` | T013 |
| `tests/Feature/PaginasPublicasCssTest.php` | T014 |
| `tests/Feature/LimiteIntentosTest.php` | T015 |
| `design/criterios.json` | T003 |
| `docs/seo.md`, `docs/architecture.md`, `AGENTS.md` | T030, T090 |
| `docs/security.md` | T030 |

| Requisito no funcional | Tarea(s) de test o verificación manual (T095) |
|---|---|
| Accesibilidad WCAG 2.1 AA | T013 (un `h1`, títulos en orden, nombres accesibles) · T010 (estructura accesible, A11) · T027 (`medir.mjs`: contraste, foco, toque) |
| Descubribilidad (indexables, metadatos propios, HTML del servidor) | T013 · T027 (Lighthouse SEO ≥ 90) · T030 |
| Peso: sin imágenes ni JavaScript nuevos | T013 (un solo script) · T027 (`medir.mjs`, K7) |
