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
- [x] T001 Aplicar «Cambios a incorporar al sistema» de `design.md` como versión 1.15.0: `--font-display` con «Newsreader respaldo» y «Newsreader respaldo Times» (las seis caras con sus medidas), los componentes de la página legal (`.lb-legal` con su corte en 900 px, `.lb-texto`, `.lb-num`, título del índice, `.lb-resumen`, `scroll-margin-top`) y su fila en «Decisiones»; en `system.html`, el mismo valor del token y la sección de la página legal — `docs/design/system.md`, `docs/design/system.html` — hecho cuando: los dos documentan el token con el valor del plan y los cinco componentes con sus tokens, `version: 1.15.0`; se hace commit junto con T020 (D9)
  - nota: 2026-11-12 — `system.md` y `system.html` 1.15.0: el token, la sección «Página legal (spec 045, 1.15.0)» y su fila en «Decisiones»; commit con T020 (D9)
- [x] T002 Subir `phpseclib/phpseclib` de 3.0.57 a 3.0.58 con `composer update phpseclib/phpseclib` (solo ese paquete) — `composer.lock` — hecho cuando: la 3.0.58 tiene al menos 7 días (`composer show phpseclib/phpseclib 3.0.58 --all`: publicada el 2026-11-02, A4); el diff del lock solo cambia `phpseclib`; `composer audit --locked` y `composer audit --locked --ignore-severity=low --ignore-severity=medium` (el de la CI) sin avisos; los tests del inicio de sesión con Google (`GoogleVueltaFallidaTest` y los que usan Socialite) en verde
  - nota: 2026-11-12 — `composer update phpseclib/phpseclib`: el lock solo cambia `phpseclib` (3.0.57 → 3.0.58, publicada el 2026-11-02T10:45:48Z: 10 días, cumple la regla de 7, A4); `composer audit --locked` y el comando de la CI sin avisos; 23 tests con «Google» en verde. Línea base de la suite (2026-11-12, antes de T002): 914 de `main`; la pasada de esta rama dio 913 + 1 fallo esperado (`test_los_tokens_038_coinciden_con_el_sistema`, porque T001 ya había cambiado `system.md`)
- [x] T003 [P] Añadir la `url` de cada pantalla en `criterios.json` (`terminos` → `terminos`, `privacidad` → `aviso-de-privacidad`) para medir la app servida (D8) — `docs/specs/045-terminos-privacidad/design/criterios.json` — hecho cuando: `medir.mjs` con una URL base pide `/terminos` y `/aviso-de-privacidad`, y con una carpeta sigue usando `file`; `python .ai/bin/aidd.py validate docs/specs/045-terminos-privacidad` sin errores

## Tests (antes de implementar)
- [x] T010 Escribir en `TerminosPrivacidadTest` los tests de las dos páginas: `test_terminos_tiene_sus_secciones_y_la_fecha` (CA1: título, «Última actualización: 12 de noviembre de 2026» y los siete títulos de sección en orden), `test_el_aviso_tiene_sus_secciones_y_la_fecha` (CA2: los ocho títulos, «datos sensibles» junto a los de salud, «cinco años» del expediente, los derechos de acceso, rectificación, cancelación y oposición, las cookies), `test_los_datos_de_la_clinica_salen_de_la_configuracion` (dirección, teléfono con su `tel:` y horario en texto de `config('clinica.contacto')`), `test_el_aviso_nombra_cada_dato_del_registro` (CA3: cada `name` de los formularios de `register.blade.php` y `registro-contacto.blade.php`, salvo `_token`, `password_confirmation` y `terminos`, y cada dato que guarda `GoogleAuthController`, mapeado a su texto del aviso; un campo sin entrada en el mapa hace fallar el test), `test_el_indice_va_antes_de_las_secciones` (K3: «En esta página» con un enlace `#id` por sección, antes de la primera `section`; y la estructura accesible de `design.md`: el `nav` con `aria-labelledby` a su título, cada `section` con `aria-labelledby` a su `h2`, el número de sección con `aria-hidden` y el resumen como `aside` con su título, A11), `test_con_y_sin_sesion_se_ven_igual` (CA7: `200` sin sesión con «Iniciar sesión» y «Crear cuenta», y con sesión de cada rol con «Ir a mi panel», sin redirigir) y `test_el_texto_es_el_aprobado` (el texto normalizado de `<main>` de cada página es el de `candidatos/c4/terminos.html` y `privacidad.html`) — `tests/Feature/TerminosPrivacidadTest.php` — hecho cuando: los siete fallan por la razón esperada (`404` en las rutas; el de CA3, porque el aviso no existe) — cubre: CA1, CA2, CA3, CA7, CA8
  - nota: 2026-11-12 — los siete fallan por el `404` de las dos rutas (el de CA3 pasa sus controles: lee los campos de los dos formularios y las claves de `google_registro`)
  - verificación de caracterización (CA7, «sin redirigir»): tras T023, se comprueba rompiendo el método con un `redirect()` para la sesión iniciada; debe fallar, se restaura y se anota aquí
  - nota: 2026-11-12 — comprobado rompiendo `terminos()` con un `redirect()` al panel para la sesión iniciada: `test_con_y_sin_sesion_se_ven_igual` falla; restaurado, pasa
- [x] T011 Escribir en `TerminosPrivacidadTest` los tests del registro: `test_la_casilla_del_registro` (CA4 y TM1: en `/registro` y en `/registro/contacto` con la sesión de Google, el texto de la etiqueta sin las dos anotaciones «(se abre en otra pestaña)» es exactamente «Acepto los términos de uso y el aviso de privacidad de Dentissa» (A7), cada enlace a `route('terminos')` y `route('privacidad')` con `target="_blank"`, `rel` con `noopener` y «(se abre en otra pestaña)» dentro del enlace, sin atributo `style`), `test_el_registro_sin_la_casilla_se_rechaza` (CA5: `POST /registro` válido sin `terminos` → error en `terminos` con «Debes aceptar los términos de uso y el aviso de privacidad.» y ninguna cuenta nueva con ese correo) y `test_el_registro_con_google_sin_la_casilla_se_rechaza` (CA5 en `/registro/contacto`) — `tests/Feature/TerminosPrivacidadTest.php` — hecho cuando: fallan por la razón esperada (el texto y los enlaces; en `/registro`, la cuenta se crea; con Google, el mensaje antiguo) — cubre: CA4, CA5 — depende: T010
  - nota: 2026-11-12 — CA4 refinado (ventana en lugar de otra pestaña): `test_la_casilla_del_registro` lo actualiza T016, y TM1 queda retirada
  - nota: 2026-11-12 — fallan por la razón esperada: la casilla dice «política de privacidad» y sus enlaces van a `#`; `/registro` sin la casilla crea la cuenta y redirige a `/cliente`; con Google, el mensaje antiguo
  - verificación de caracterización (CA5 con Google, el rechazo): tras T026, se comprueba quitando la regla `terminos` de `contactoStore`; debe fallar, se restaura y se anota aquí
  - nota: 2026-11-12 — comprobado rompiendo la regla `terminos` de `contactoStore()`: `test_el_registro_con_google_sin_la_casilla_se_rechaza` falla; restaurada, pasa
- [x] T012 Escribir en `TerminosPrivacidadTest` `test_el_pie_publico_enlaza_las_dos_paginas` (CA6: en `/`, `/servicios`, `/galeria`, `/terminos` y `/aviso-de-privacidad`, con y sin sesión, «Términos de uso» y «Aviso de privacidad» dentro de `.pub-foot__links`, a `route('terminos')` y `route('privacidad')`, sin `target`) — `tests/Feature/TerminosPrivacidadTest.php` — hecho cuando: falla porque el pie no los tiene — cubre: CA6 — depende: T011
  - nota: 2026-11-12 — falla en `/` porque el pie no tiene «Términos de uso» (0 de 1)
- [x] T013 [P] Añadir `/terminos` y `/aviso-de-privacidad` a `METADATOS` de `PaginasPublicasTest` con el título, la descripción y la canónica de «Descubribilidad» del plan; así entran en los tests de un `h1`, títulos en orden, nombres accesibles, un solo script con `defer`, sin recursos externos y enlaces internos que resuelven — `tests/Feature/PaginasPublicasTest.php` ; además, `publico/terminos.blade.php` y `publico/aviso-privacidad.blade.php` en la lista `VISTAS` del test de salida sin escapar (TM2, A1). El contenido en el HTML del servidor (CA8) lo cubre `test_el_texto_es_el_aprobado` de T010 (A5) — hecho cuando: falla por el `404` de las dos rutas y por las vistas que aún no existen — cubre: CA8
  - nota: 2026-11-12 — fallan 5 por el `404` de las rutas y las vistas que faltan (metadatos, nombres accesibles, script, salida sin escapar, enlaces internos); los de un `h1` y títulos en orden pasan ahora con la página 404 y se vuelven a mirar en T027
- [x] T014 [P] En `PaginasPublicasCssTest`: el valor nuevo de `--font-display` en `TOKENS` y `EN_EL_SISTEMA`; el filtro de `test_font_face_declara_las_dos_familias_autoalojadas` deja fuera también las caras de «Newsreader respaldo» y «Newsreader respaldo Times»; `test_newsreader_tiene_un_respaldo_con_sus_medidas` (las seis caras de `design.md` con sus `local()`, `size-adjust`, `ascent-override`, `descent-override` y `line-gap-override: 0%`, sin `url()`), y `test_la_pagina_legal_lee_a_la_medida` (K4: `.lb-legal .lb-texto` y sus párrafos y listas con `max-width: var(--measure)`; la rejilla con sus zonas y una columna dentro de `@media (max-width: 899px)`), y las dos vistas nuevas en su lista `VISTAS` y `/terminos` y `/aviso-de-privacidad` en las rutas de `test_las_vistas_publicas_no_llevan_estilos_en_linea` (P9, A2) — `tests/Feature/PaginasPublicasCssTest.php` — hecho cuando: los nuevos y el de tokens fallan por la razón esperada — cubre: CA1, CA2 — depende: T001
  - nota: 2026-11-12 — fallan los cuatro por la razón esperada: `app.css` aún no define el `--font-display` nuevo (`system.md` ya sí, T001), no hay caras de respaldo de Newsreader ni reglas de la página legal, y las vistas nuevas no existen; `test_font_face_declara_las_dos_familias_autoalojadas` sigue en verde con el filtro nuevo
- [x] T015 [P] Añadir `'terminos' => 'on'` a `altaValida()` de `LimiteIntentosTest`, que da de alta sin la casilla — `tests/Feature/LimiteIntentosTest.php` — hecho cuando: `LimiteIntentosTest` sigue en verde (antes y después de T026) — cubre: CA5
  - nota: 2026-11-12 — `LimiteIntentosTest` en verde con la casilla (antes de T026); se vuelve a correr en T026

- [x] T016 Actualizar `test_la_casilla_del_registro` al CA4 refinado (texto de la etiqueta exactamente el de CA4; cada enlace a `url('/terminos')` y `url('/aviso-de-privacidad')` sin `target`, con `aria-haspopup="dialog"` y `data-dialogo` hacia una ventana que existe en la página; sin `style`) y añadir `test_las_ventanas_del_registro_llevan_el_texto_de_la_pagina` (en `/registro` y en `/registro/contacto`: cada `dialog.dialogo-legal` con `aria-labelledby` a su título «Términos de uso» o «Aviso de privacidad», la fecha, un botón «Cerrar», y el texto normalizado de sus secciones igual al de las secciones de su página) y `test_la_ventana_del_registro_solo_usa_tokens` (las reglas de `.dialogo-legal` en `auth.css` solo con `var(--…)`, salvo `0`, porcentajes, `1.6` y `100dvh`) y `test_el_script_de_las_ventanas_abre_y_cierra` (el script del parcial de las ventanas abre con `showModal()` desde los enlaces `data-dialogo` y evita la navegación solo si existe `showModal`, cierra con el botón `data-cerrar` y al pulsar fuera (`target` igual a la ventana), y devuelve el foco con `.focus()` al cerrarse; /analyze A17) — `tests/Feature/TerminosPrivacidadTest.php` — hecho cuando: fallan por la razón esperada (los enlaces aún llevan `target`; no hay ventanas ni reglas) — cubre: CA4 — depende: T011
  - nota: añadida durante /implement: CA4 refinado por el usuario en la verificación manual de T025 (2026-11-12)
  - nota: 2026-11-12 — fallan los cuatro por la razón esperada: la casilla aún lleva «(se abre en otra pestaña)» y `target`; no hay ventanas, ni reglas de `.dialogo-legal`, ni el parcial con su script (A17)
## Implementación
- [x] T020 Aplicar el sistema 1.15.0 al CSS: en `app.css`, el valor nuevo de `--font-display`; en `publico.css`, las seis `@font-face` de respaldo de Newsreader tras las de Montserrat y las reglas de `candidatos/c4/legal.css` de la página legal (sin la redefinición del token), con su comentario — `public/css/app.css`, `public/css/publico.css` — hecho cuando: T014 pasa y `PaginasPublicasCssTest` entero en verde (solo tokens) — cubre: CA1, CA2 — depende: T001, T014
  - nota: 2026-11-12 — `app.css` con el `--font-display` nuevo; `publico.css` con las seis caras de respaldo tras las de Montserrat y las reglas de `c4/legal.css` tras `.lb-volver`. `PaginasPublicasCssTest`: 22 en verde; `test_las_vistas_publicas_no_llevan_estilos_en_linea` espera a las vistas de T022 y T023
- [x] T021 Añadir las rutas `GET /terminos` (`terminos`) y `GET /aviso-de-privacidad` (`privacidad`) junto a `/servicios`, y en `PaginasPublicasController` los métodos `terminos()` y `privacidad()` con `horarioEnTexto()` («de lunes a viernes de 9:00 a 20:00 y los sábados de 9:00 a 14:00» desde `config('clinica.contacto.horario')`) — `routes/web.php`, `app/Http/Controllers/PaginasPublicasController.php` — hecho cuando: `php artisan route:list` muestra las dos rutas con sus nombres y los tests de T010 dejan de fallar por `404` (pasan a fallar por la vista que falta, A12) — cubre: CA1, CA2, CA7 — depende: T010
  - nota: 2026-11-12 — `route:list` muestra `terminos` y `privacidad`; los tests de T010 pasan de `404` a fallar por la vista que falta (A12); `horarioEnTexto()` da «de lunes a viernes de 9:00 a 20:00 y los sábados de 9:00 a 14:00»
- [x] T022 Crear la vista de términos con `@extends('layouts.publico')`, el título, la descripción y la canónica del plan, y el marcado y el texto de `candidatos/c4/terminos.html` (dirección, teléfono, `tel:` y horario desde la configuración; solo dobles llaves) — `resources/views/publico/terminos.blade.php` — hecho cuando: pasan `test_terminos_tiene_sus_secciones_y_la_fecha`, `test_los_datos_de_la_clinica_salen_de_la_configuracion`, `test_el_indice_va_antes_de_las_secciones` y `test_el_texto_es_el_aprobado` (términos) · verificación manual: `/terminos` a 320, 390 y 1440 px contra `candidatos/c4/terminos.html` — cubre: CA1, CA7, CA8 — depende: T020, T021
  - nota: 2026-11-12 — sus tests en verde; capturas de `/terminos` a 320, 390 y 1440 px iguales a `candidatos/c4/terminos.html`. verificación manual OK 2026-11-12 (usuario)
- [x] T023 Crear la vista del aviso de privacidad igual que T022, con el texto de `candidatos/c4/privacidad.html` — `resources/views/publico/aviso-privacidad.blade.php` — hecho cuando: pasan `test_el_aviso_tiene_sus_secciones_y_la_fecha`, `test_el_aviso_nombra_cada_dato_del_registro`, `test_el_texto_es_el_aprobado` (aviso) y T013 · verificación manual: `/aviso-de-privacidad` a 320, 390 y 1440 px contra `candidatos/c4/privacidad.html`, y que el aviso no menciona datos ni usos que el sitio no tiene (CA3) — cubre: CA2, CA3, CA7, CA8 — depende: T013, T022
  - nota: 2026-11-12 — sus tests y T013 en verde; capturas de `/aviso-de-privacidad` a 320, 390 y 1440 px iguales a `candidatos/c4/privacidad.html`; el aviso no menciona datos ni usos que el sitio no tiene (A3, A8, A15). verificación manual OK 2026-11-12 (usuario)
- [x] T024 Añadir al final de `.pub-foot__links` «Términos de uso» (`route('terminos')`) y «Aviso de privacidad» (`route('privacidad')`) — `resources/views/layouts/publico.blade.php` — hecho cuando: T012 pasa y `PaginasPublicasTest` y `NavegacionSalidasTest` siguen en verde — cubre: CA6 — depende: T012, T022, T023
  - nota: 2026-11-12 — T012 en verde; `PaginasPublicasTest`, `NavegacionSalidasTest` y `PaginaInicioTest` siguen en verde (58)
- [x] T025 Cambiar la casilla de los dos registros: «Acepto los términos de uso y el aviso de privacidad de Dentissa», cada enlace a su ruta en la misma pestaña, con `data-dialogo` y `aria-haspopup="dialog"`, sin `style=""`; e incluir en los dos el parcial nuevo de las ventanas (`<dialog class="dialogo-legal">` de términos y de aviso con los parciales de texto de T028 y T029 en modo ventana, y el script en línea de `candidatos/c4/registro-integracion.html`) — `resources/views/auth/register.blade.php`, `resources/views/auth/registro-contacto.blade.php`, `resources/views/auth/_dialogos-legales.blade.php` — hecho cuando: los tests de T016 pasan · verificación manual: en `/registro` y en `/registro/contacto`, cada enlace abre su ventana; se cierra con «Cerrar», con Escape y pulsando fuera; el foco vuelve al enlace; lo escrito sigue ahí; el enlace al otro texto dentro de la ventana abre su ventana; sin JavaScript, el enlace lleva a la página (CA4) — cubre: CA4 — depende: T016, T028, T029, T031, T032
  - nota: 2026-11-12 — los tests de T016 y T032 en verde. En el navegador (Playwright, 390 px): abrir desde la casilla, cerrar con «Cerrar», Escape y pulsando fuera, foco de vuelta al enlace, el enlace al aviso dentro de los términos abre su ventana, lo escrito sigue; sin JavaScript, la página en la misma pestaña. Corregido el centrado: el reset de `auth.css` (`* { margin: 0 }`) quitaba el `margin: auto` nativo del dialog modal (archivo de T031, `margin: auto` en `.dialogo-legal`, también en el candidato). verificación manual OK 2026-11-12 (usuario)
- [x] T026 En `RegisterController`: regla `'terminos' => ['accepted']` en `register()` con el mensaje «Debes aceptar los términos de uso y el aviso de privacidad.», y el mismo mensaje en `contactoStore()` (D5) — `app/Http/Controllers/RegisterController.php` — hecho cuando: `test_el_registro_sin_la_casilla_se_rechaza` y `test_el_registro_con_google_sin_la_casilla_se_rechaza` pasan, y `LimiteIntentosTest` sigue en verde — cubre: CA5 — depende: T011, T015
  - nota: 2026-11-12 — los dos tests de CA5 y `LimiteIntentosTest` en verde (11)
- [x] T028 Pasar las secciones de los términos a un parcial con el nivel del título (`h2` o `h3`), el prefijo de los ids y si los enlaces al otro texto abren su ventana (`data-dialogo`), e incluirlo en la vista de la página (D11) — `resources/views/publico/legal/terminos-texto.blade.php`, `resources/views/publico/terminos.blade.php` — hecho cuando: los tests de la página de términos siguen en verde (secciones, datos de la configuración, índice, texto aprobado) — cubre: CA1, CA4 — depende: T022
  - nota: añadida durante /implement: CA4 refinado (2026-11-12)
  - nota: 2026-11-12 — `publico/legal/terminos-texto` con `$nivel`, `$prefijo` y `$enVentana`; los cinco tests de la página en verde, con el mismo texto aprobado
- [x] T029 Lo mismo con el aviso de privacidad — `resources/views/publico/legal/privacidad-texto.blade.php`, `resources/views/publico/aviso-privacidad.blade.php` — hecho cuando: los tests del aviso siguen en verde (secciones, datos del registro, texto aprobado) — cubre: CA2, CA3, CA4 — depende: T023
  - nota: añadida durante /implement: CA4 refinado (2026-11-12)
  - nota: 2026-11-12 — `publico/legal/privacidad-texto`, igual que T028; los cinco tests del aviso en verde (no tiene enlace al otro texto)
- [x] T032 Añadir `publico/legal/terminos-texto.blade.php`, `publico/legal/privacidad-texto.blade.php` y `auth/_dialogos-legales.blade.php` a las listas `VISTAS` de los tests de salida sin escapar (TM2, P4) y de estilos en línea (P9) — `tests/Feature/PaginasPublicasTest.php`, `tests/Feature/PaginasPublicasCssTest.php` — hecho cuando: los dos tests fallan solo porque falta `auth/_dialogos-legales.blade.php` (T025) — cubre: CA4, CA8 — depende: T028, T029
  - nota: añadida durante /implement: los parciales de D11 quedaban fuera de las listas de archivos de esos tests (hallazgo de T029; aprobada por el usuario el 2026-11-12)
- [x] T031 Añadir a `auth.css` los tokens que copia de `app.css` con el mismo valor y las reglas de `.dialogo-legal` de `candidatos/c4/registro-dialogo.css` (sin la regla solo del candidato) — `public/css/auth.css` — hecho cuando: `test_la_ventana_del_registro_solo_usa_tokens` pasa — cubre: CA4 — depende: T016
  - nota: añadida durante /implement: CA4 refinado (2026-11-12)
  - nota: 2026-11-12 — tokens en el `:root` de `auth.css` (mismos valores que `app.css`) y las reglas de `.dialogo-legal` de `registro-dialogo.css`, sin la regla solo del candidato. `test_la_ventana_del_registro_solo_usa_tokens` en verde; se ajustó (era más estricto que `system.md`: rechazaba el factor `* 2` de `calc()`, que el sistema admite) a «sin medidas con unidad ni colores literales», y sigue fallando con un `12px` de control
- [x] T033 Bajar en `NavegacionSalidasCssTest::ESTILOS_PREVIOS` los `style=""` previos de `auth/register` (17 → 15) y `auth/registro-contacto` (6 → 4), porque T025 quitó los de los dos enlaces de la casilla (D7) — `tests/Feature/NavegacionSalidasCssTest.php` — hecho cuando: `test_las_vistas_tocadas_no_ganan_estilos_en_linea` pasa con los recuentos nuevos (más estricto) — cubre: CA4 — depende: T025
  - nota: añadida durante /implement: la suite completa de T027 falló en este test de la 044 (17 ≠ 15); aprobada por el usuario el 2026-11-12. T098 lo anota en el Historial de la 044
- [ ] T034 Reforzar los tests de CA4: en `test_la_casilla_del_registro`, el título de la ventana que abre cada enlace (el elemento de su `aria-labelledby`) es «Términos de uso» para «términos de uso» y «Aviso de privacidad» para «aviso de privacidad»; en `test_las_ventanas_del_registro_llevan_el_texto_de_la_pagina`, los títulos de las dos ventanas son exactamente esos dos, sin repetirse — `tests/Feature/TerminosPrivacidadTest.php` — hecho cuando: pasan, y fallan si se intercambian un momento los `data-dialogo` de la casilla en `register.blade.php` (caracterización: el comportamiento ya es correcto; se restaura y se anota) — cubre: CA4
  - nota: añadida por /review: R1
- [ ] T035 Hacer que `test_los_datos_de_la_clinica_salen_de_la_configuracion` cambie la configuración (`clinica.contacto.direccion`, `telefono`, `telefono_enlace` y `horario` con un solo tramo, `['Martes' => '10:00–11:00']`) y compruebe esos valores en las secciones `quien`, `contacto`, `responsable` y `derechos` de las dos páginas y en las dos ventanas de `/registro` — `tests/Feature/TerminosPrivacidadTest.php` — hecho cuando: pasa, y falla si se escribe a mano un momento la dirección en `publico/legal/terminos-texto.blade.php` (caracterización; se restaura y se anota) — cubre: CA1, CA2, CA4 — depende: T034
  - nota: añadida por /review: R2
- [ ] T036 Escribir `test_el_horario_en_texto_admite_un_tramo_sin_guion`: con `clinica.contacto.horario` = `['Lunes a viernes' => '9:00–20:00', 'Domingos' => 'Cerrado']`, `PaginasPublicasController::horarioEnTexto()` devuelve «de lunes a viernes de 9:00 a 20:00 y los domingos cerrado» y `/terminos` y `/registro` responden `200` — `tests/Feature/TerminosPrivacidadTest.php` — hecho cuando: falla hoy por el error de la desestructuración — cubre: CA1 — depende: T035
  - nota: añadida por /review: R3
- [ ] T037 En `horarioEnTexto()`, un tramo sin guion largo se escribe con su valor en minúsculas tras los días («los domingos cerrado») en lugar de partirlo — `app/Http/Controllers/PaginasPublicasController.php` — hecho cuando: T036 pasa y los demás tests de la 045 siguen en verde — cubre: CA1 — depende: T036
  - nota: añadida por /review: R3
- [ ] T038 Acotar las búsquedas de CA3 y CA2 a su viñeta: cada dato del registro, en el `<li>` que empieza por «Al crear tu cuenta»; los de Google, en el que empieza por «Si te registras o entras con Google»; «datos sensibles», en el de «Datos de salud» — `tests/Feature/TerminosPrivacidadTest.php` — hecho cuando: pasan, y `test_el_aviso_nombra_cada_dato_del_registro` falla si se quita un momento «teléfono» de la viñeta de la cuenta en `publico/legal/privacidad-texto.blade.php` (caracterización; se restaura y se anota) — cubre: CA2, CA3 — depende: T036
  - nota: añadida por /review: R5
- [ ] T039 Añadir a `test_las_ventanas_del_registro_llevan_el_texto_de_la_pagina` que todo enlace con `data-dialogo` dentro de una ventana lleva `aria-haspopup="dialog"` — `tests/Feature/TerminosPrivacidadTest.php` — hecho cuando: falla hoy por el enlace al aviso dentro de la ventana de términos — cubre: CA4 — depende: T038
  - nota: añadida por /review: R13
- [ ] T040 Añadir `aria-haspopup="dialog"` al enlace al otro texto dentro del mismo `@if ($enVentana)` — `resources/views/publico/legal/terminos-texto.blade.php` — hecho cuando: T039 pasa y `test_el_texto_es_el_aprobado` sigue en verde — cubre: CA4 — depende: T039
  - nota: añadida por /review: R13
- [ ] T041 Anotar en `docs/security.md` → RS8.d que el registro gana el script en línea de las ventanas de la 045 (`auth/_dialogos-legales.blade.php`, a mover a `public/js/` cuando se pague RS8.d) y cambiar en el Modelo de amenazas del plan la categoría de TM2 a «A05:2025 Injection» — `docs/security.md`, `docs/specs/045-terminos-privacidad/plan.md` — hecho cuando: los dos documentos lo dicen y `python .ai/bin/aidd.py validate docs/specs/045-terminos-privacidad` no da errores
  - nota: añadida por /review: R8, R14
- [x] T027 Verificación final: suite completa (`php artisan test`), Pint sobre los PHP tocados, `semgrep` y `gitleaks` sobre lo tocado, `composer audit --locked` sin avisos, `medir.mjs --solo terminos,privacidad` en las dos páginas de la app y `--solo registro-dialogo` sobre `candidatos/c4` (K8; la ventana de la app solo se abre con JavaScript) (puertas y KPIs; K3 y K4 por sus tests) y Lighthouse SEO de `/terminos`, `/aviso-de-privacidad` y `/registro` en local (A20) — sin archivos — hecho cuando: todo en verde, Lighthouse SEO ≥ 90 en las tres, y el resultado anotado en esta tarea — cubre: CA1, CA2, CA3, CA4, CA5, CA6, CA7, CA8 — depende: T002, T003, T013, T022, T023, T024, T025, T026, T028, T029, T031, T033
  - nota: 2026-11-12 — suite completa 930 en verde (14 744 aserciones; línea base 914 de `main`: 16 nuevos, ninguno roto tras T033); Pint PASS en los 7 PHP tocados; semgrep `p/php` (imagen 1.99.0) sin hallazgos en lo tocado; gitleaks 8.21.2 sin fugas en los 32 archivos cambiados desde `impl_base` (modo archivos: dentro del contenedor no lee el historial de git); `composer audit --locked` (también el de la CI) y `npm audit` sin avisos. `medir.mjs` sobre la app (`http://dentissa_nginx`): `terminos` y `privacidad` 11/11 puertas y 5/5 KPIs; `registro-dialogo` sobre el candidato 11/11 y 3/3 (K8). Lighthouse 13.5.0 SEO: `/terminos` 100/100 y `/aviso-de-privacidad` 100/100 (móvil/escritorio), `/registro` 91/92 (solo resta la meta descripción que le falta desde antes, SE1.a)

## Integración y documentación
- [x] T030 Añadir `/terminos` y `/aviso-de-privacidad` al inventario de `docs/seo.md` (indexables, con título, descripción, canónica, un `h1` y su evidencia), a la lista de páginas de `layouts/publico.blade.php` en `AGENTS.md` y a los puntos de entrada públicos de `docs/security.md`, donde también se anota el aviso de `phpseclib` (PKSA-h7cz-q7zq-mdkt) y su corrección con la 3.0.58 (A10) — `docs/seo.md`, `AGENTS.md`, `docs/security.md` — hecho cuando: las filas y la línea de `AGENTS.md` dicen lo que hace el código — depende: T027
  - nota: 2026-11-12 — `docs/seo.md` con las dos filas y `/registro` con el texto de las ventanas; `AGENTS.md` con las dos páginas del layout público; `docs/security.md` con los puntos de entrada y RS17.b (PKSA-h7cz-q7zq-mdkt, 3.0.58, pendiente de liberar)
- [x] T090 Actualizar `docs/architecture.md` si cambió la estructura: la fila «Páginas públicas» con `/terminos` y `/aviso-de-privacidad`, el pie del layout público y la validación de la casilla en `POST /registro` — depende: T027
  - nota: 2026-11-12 — fila «Páginas públicas» (rutas, `horarioEnTexto()`, parciales de `publico/legal/`), fila «Registro» (casilla validada en el servidor y ventanas) y la lista del layout público con sus enlaces del pie
- [x] T091 Actualizar `docs/deployment.md` y `docs/observability.md` si cambiaron variables, entornos, pasos de deploy, logs, eventos de auditoría o métricas: en `deployment.md` → «Verificación post-deploy», que `/terminos` y `/aviso-de-privacidad` respondan `200`; `observability.md` sin cambios (sin logs ni eventos nuevos) — depende: T027
  - nota: 2026-11-12 — `deployment.md` → «Verificación post-deploy»: `/terminos` y `/aviso-de-privacidad` 200 y la ventana del registro; `observability.md` sin cambios (sin logs ni eventos nuevos)
- [ ] T092 Marcar spec como `implemented` — depende: T030, T090, T091
  - nota: 2026-11-12 — CA1–CA8 marcados (sus tests en verde), spec en `implemented` y `docs/specs/README.md` al día

## Despliegue (lo ejecuta `/release`)
- [ ] T095 Desplegar a staging y verificar criterios de aceptación. No hay staging: la comprobación se hace en producción, dentro de T097
  - `/terminos` y `/aviso-de-privacidad` responden `200` con su texto, con el logo, y aparecen en el pie de `/`
  - desde `/registro`, los dos enlaces abren su ventana sin salir de la página, que se cierra con «Cerrar», Escape o pulsando fuera, y lo escrito sigue ahí
- [ ] T096 Aprobación humana para producción
- [ ] T097 Desplegar a producción y vigilar métricas del plan (Rollout): `deploy/desplegar.sh` con la versión nueva, `verificar.sh` y la comprobación de T095; vigilar `5xx` en `/registro` la primera hora
- [ ] T098 Marcar spec como `released`; en las specs 002, 038 y 044 anotar en su Historial lo que cambia la 045 (casilla y validación del registro; pie público; recuento de `style=""` del registro en `NavegacionSalidasCssTest`)

## Cobertura
| Criterio | Tarea(s) de test | Tarea(s) de implementación |
|---|---|---|
| CA1 | T010, T014, T035, T036 | T020, T021, T022, T037 |
| CA2 | T010, T014, T035, T038 | T020, T021, T023 |
| CA3 | T010, T038 | T023 |
| CA4 | T011, T016, T034, T035, T039 | T025, T028, T029, T031, T040 |
| CA5 | T011, T015 | T026 |
| CA6 | T012 | T024 |
| CA7 | T010 | T021, T022, T023 |
| CA8 | T010, T013 | T022, T023 |

| Amenaza (TM#) | Tarea(s) de control | Tarea(s) de test |
|---|---|---|
| TM1 (retirada: CA4 refinado) | — | T016 (los enlaces sin `target`) |
| TM2 | T022, T023 | T013 |

| Cambio del plan (módulo) | Tarea(s) |
|---|---|
| `composer.lock` | T002 |
| `docs/design/system.md`, `system.html` | T001 |
| `public/css/app.css` | T020 |
| `public/css/publico.css` | T020 |
| `routes/web.php` | T021 |
| `app/Http/Controllers/PaginasPublicasController.php` | T021, T037 |
| `resources/views/publico/terminos.blade.php`, `aviso-privacidad.blade.php` | T022, T023 |
| `resources/views/layouts/publico.blade.php` | T024 |
| `resources/views/auth/register.blade.php`, `registro-contacto.blade.php`, `_dialogos-legales.blade.php` | T025 |
| `resources/views/publico/legal/terminos-texto.blade.php`, `privacidad-texto.blade.php` | T028, T029, T040 |
| `public/css/auth.css` | T025, T031 |
| `app/Http/Controllers/RegisterController.php` | T026 |
| `tests/Feature/TerminosPrivacidadTest.php` | T010, T011, T012, T016, T034, T035, T036, T038, T039 |
| `tests/Feature/PaginasPublicasTest.php` | T013, T032 |
| `tests/Feature/PaginasPublicasCssTest.php` | T014, T032 |
| `tests/Feature/LimiteIntentosTest.php` | T015 |
| `tests/Feature/NavegacionSalidasCssTest.php` | T033 |
| `design/criterios.json` | T003 |
| `docs/seo.md`, `docs/architecture.md`, `AGENTS.md` | T030, T090 |
| `docs/security.md` | T030, T041 |

| Requisito no funcional | Tarea(s) de test o verificación manual (T095) |
|---|---|
| Accesibilidad WCAG 2.1 AA | T013 (un `h1`, títulos en orden, nombres accesibles) · T010 (estructura accesible, A11) · T027 (`medir.mjs`: contraste, foco, toque) |
| Descubribilidad (indexables, metadatos propios, HTML del servidor) | T013 · T027 (Lighthouse SEO ≥ 90) · T030 |
| Peso: sin imágenes ni JavaScript nuevos | T013 (un solo script) · T027 (`medir.mjs`, K7) |
