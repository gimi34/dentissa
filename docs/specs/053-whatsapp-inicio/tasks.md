---
spec: 053-whatsapp-inicio
plan: plan.md
impl_base: d933175
status: approved
---

# Tareas · 053 Escríbenos por WhatsApp desde la página de inicio

Formato:
`- [ ] T### [P] <verbo + qué> — <archivos> — hecho cuando: <criterio> — cubre: CA# — depende: T###`
- T001–T089: trabajo. T090–T099: reservadas.
- `[P]` = paralelizable con las demás `[P]` de su grupo una vez cumplidas sus dependencias (no
  comparte archivos con otra tarea abierta).
- `[-]` = obsoleta (`— obsoleta: <motivo> (AAAA-MM-DD)`): no cuenta como abierta ni como hecha.
- Bajo una tarea: `  - nota: …`, `  - bloqueo: AAAA-MM-DD — <qué falta> — <skill a ejecutar>`.
- La comprobación a mano va en la tarea que construye la pieza.

Tests con `docker compose exec app php artisan test --filter <Clase>` (suite completa al cerrar cada
fase) y Pint sobre los archivos tocados. Los tests de `PaginaInicioTest` comparten archivo: sus
tareas (T010–T012) van en serie.

## Constitution Check
Sobre el plan (constitución 1.3.0), lo que el desglose podría romper:
- ✅ P1: ninguna tarea de código antes de aprobar este `tasks.md`; T001 pone la rama al día con
  `main` antes de tocar código.
- ✅ P2: cada tarea de implementación depende de su test (T010–T014), que falla antes y se anota.
  El ajuste de `test_el_texto_es_el_aprobado` (D5) va en T013, junto al test nuevo de CA6, antes de
  cambiar el aviso (T023).
- ➖ P3: ninguna tarea cambia rutas, middleware ni autorización.
- ✅ P4: el mensaje con `rawurlencode()` en T020; el parcial en `VISTAS` (T014) antes de crearlo
  (T021); semgrep en T092.
- ✅ P5: solo datos ficticios de la configuración; gitleaks sobre el diff en T092.
- ✅ P6: ninguna tarea añade dependencias (el logotipo es un SVG en línea, D4); `composer audit` y
  `auditar-npm.mjs` en T092.
- ➖ P7: sin eventos de auditoría.
- ➖ P8: ninguna tarea añade `Log::`.
- ✅ P9: sin «Cambios a incorporar al sistema»; T022 añade solo reglas con tokens a `publico.css` y
  ningún `style=""`; el parcial entra en la lista de `PaginasPublicasCssTest` (T014); `evaluate.mjs`
  con `design/criterios.json` en T022.
- ✅ P10: `/` y `/aviso-de-privacidad` no cambian metadatos; `PaginasPublicasTest` en la suite de T092
  y Lighthouse SEO ≥ 90 de las dos en T092 (A2).

## Preparación
- [x] T001 Poner la rama al día con `origin/main` (16a110c: EX5 retirada, fix del expediente, specs 002/006/011), resolver los choques y fijar la línea base de la suite — rama `feature/053-whatsapp-inicio` — hecho cuando: la rama contiene `origin/main`, no quedan conflictos y `php artisan test` pasa completo (resultado anotado en la nota de la tarea)
  - nota: rebase sobre `origin/main` (16a110c) sin choques. Línea base: 1502 tests, 0 fallos (5 min 50 s), con `vendor/bin/phpunit` en un contenedor `dentissa-app` propio con el código del worktree (`artisan test` sin `.env` avisa al leerlo; `phpunit` directo no).

## Tests (antes de implementar)
- [x] T010 Escribir los tests de los dos sitios del botón: `test_whatsapp_en_la_primera_pantalla_junto_al_telefono` (en la lista de datos de la primera pantalla, un `li.pub-hero__contacto` con el enlace `tel:` del teléfono y, después, `a.pub-wa` con el texto «Escríbenos por WhatsApp»; como visitante y con sesión de los cuatro roles) y `test_whatsapp_en_contacto_junto_al_telefono` (en la sección de contacto, `.pub-visit__acciones` justo después de `a.pub-visit__tel`, con `a.pub-wa` antes de «Cómo llegar») — tests/Feature/PaginaInicioTest.php — hecho cuando: fallan porque el botón no existe (anotado) — cubre: CA1, CA2
  - nota: fallan por la razón esperada: no hay `li.pub-hero__contacto` (0 en vez de 1) y lo que sigue al teléfono de contacto es un `div` sin `pub-visit__acciones`.
- [x] T011 Escribir los tests del enlace: `test_el_enlace_abre_whatsapp_con_el_numero_y_el_mensaje` (los dos `a.pub-wa` con el `href` exacto del plan, los dígitos iguales a los de `contacto.telefono_enlace`, `target="_blank"` y `rel` con `noopener` y `noreferrer`) y `test_el_boton_no_depende_de_javascript` (el `href` está en el HTML del servidor; sin `onclick` ni atributos `data-` que lea `publico.js`) — tests/Feature/PaginaInicioTest.php — hecho cuando: fallan porque el botón no existe (anotado) — cubre: CA3, CA4 — depende: T010
  - nota: fallan por la razón esperada: no hay ningún `a.pub-wa` (0 en vez de 2). TM1 y TM4 se comprueban rompiendo el control en T021.
- [x] T012 Escribir los tests de privacidad y accesibilidad: `test_el_enlace_es_el_mismo_para_todos` (visitante y los cuatro roles con nombre, correo y teléfono propios, y una visita a `/?utm_source=x&nombre=y` con otro `Referer`: mismo `href` y sin ninguno de esos datos en él) (A6), `test_no_carga_nada_de_whatsapp_ni_de_meta` (por host, no por subcadena: ningún `src`, `srcset` ni `link href` de `/` con host `wa.me`, `whatsapp.com`, `whatsapp.net`, `facebook.com`, `fbcdn.net` o `meta.com`, ni subdominios suyos; solo los `href` de `a.pub-wa` apuntan a `wa.me`) (A5) y `test_el_boton_avisa_de_que_abre_whatsapp` (`aria-label` exacto «Escríbenos por WhatsApp (se abre WhatsApp)», que empieza por el texto visible; el `svg` con `aria-hidden="true"` y `focusable="false"`) (A9) — tests/Feature/PaginaInicioTest.php — hecho cuando: el de igualdad y el de accesibilidad fallan porque el botón no existe; el de terceros, si pasa hoy, se comprueba rompiéndolo con un `<img src="https://wa.me/x">` temporal (anotado) — cubre: CA5 — depende: T011
  - nota: `test_el_enlace_es_el_mismo_para_todos` y `test_el_boton_avisa_de_que_abre_whatsapp` fallan por la razón esperada (no hay botones). `test_no_carga_nada_de_whatsapp_ni_de_meta` pasa hoy: comprobado rompiendo la página tres veces (un `<img src="https://wa.me/x">`, un `srcset` con `static.whatsapp.net` y un `<a href="https://web.whatsapp.com/x">` sin `pub-wa`): falla con cada uno y se restauró la vista (A5, A6, A9 aplicados).
- [ ] T013 [P] Escribir `test_el_aviso_explica_whatsapp` (en `/aviso-de-privacidad` y en la ventana del aviso de `/registro`, dentro de la sección «Qué datos recabamos», el punto «Si nos escribes por WhatsApp:» con su texto exacto del plan, después del de Google; «Última actualización» con la fecha del cambio, D6) y ajustar `test_el_texto_es_el_aprobado` para que descuente ese punto y la fecha nueva antes de comparar con `045…/design/candidatos/c4/privacidad.html` (D5). Partir la constante `FECHA` en la de los términos («12 de noviembre de 2026», sin cambios) y la del aviso (la fecha literal del día del cambio, fijada aquí), y usar la suya en `test_terminos_tiene_sus_secciones_y_la_fecha`, `test_el_aviso_tiene_sus_secciones_y_la_fecha` y `test_las_ventanas_del_registro_llevan_el_texto_de_la_pagina` (cada ventana con la fecha de su página) (A1) — tests/Feature/TerminosPrivacidadTest.php — hecho cuando: el test nuevo, el de la fecha del aviso y el de las ventanas (en la del aviso) fallan porque el aviso aún no cambió; el ajustado y el de los términos siguen en verde (anotado) — cubre: CA6
- [ ] T014 [P] Añadir `resources/views/publico/_boton-whatsapp.blade.php` a las listas `VISTAS` de salida escapada y de sin estilos en línea — tests/Feature/PaginasPublicasTest.php, tests/Feature/PaginasPublicasCssTest.php — hecho cuando: `test_las_vistas_publicas_no_usan_html_sin_escapar` y el de sin `style=""` fallan porque el parcial no existe (anotado)

## Implementación
- [ ] T020 Añadir `contacto.whatsapp_mensaje` («Hola, quiero información para agendar una cita.», con su comentario de la spec 053) y `PaginasPublicasController::whatsappEnlace()` (estático: `https://wa.me/` + los dígitos de `contacto.telefono_enlace` + `?text=` + `rawurlencode()` del mensaje, sin datos de la petición ni del usuario), y pasar `$whatsapp` a la vista en `inicio()` — config/clinica.php, app/Http/Controllers/PaginasPublicasController.php — hecho cuando: `whatsappEnlace()` devuelve exactamente el enlace del plan (comprobado con `php artisan tinker`, anotado) y Pint limpio — cubre: CA3, CA5 — depende: T012
- [ ] T021 Crear el parcial del botón con el marcado de `c3`: `a.c-btn.c-btn--secondary.pub-wa` con `href` de `$whatsapp` en dobles llaves, `target="_blank"`, `rel="noopener noreferrer"`, el `aria-label` «Escríbenos por WhatsApp (se abre WhatsApp)» (A9), el logotipo de una tinta (Simple Icons 13.21.0, CC0, origen en un comentario Blade) en SVG en línea con `fill="currentColor"`, `aria-hidden="true"` y `focusable="false"`, y el texto «Escríbenos por WhatsApp» — resources/views/publico/_boton-whatsapp.blade.php — hecho cuando: T014 pasa — cubre: CA3, CA4 — depende: T014, T020
- [ ] T022 Poner el botón en la página de inicio y sus estilos: en la primera pantalla, el `<li>` del teléfono pasa a `li.pub-hero__contacto` con el teléfono y el parcial; en contacto, el `<div>` de «Cómo llegar» pasa a `div.pub-visit__acciones` con el parcial delante; en `publico.css`, las reglas de `c3` (`.pub-hero__contacto`, `.pub-hero__facts a.pub-wa` y su `:hover`, `.pub-visit__acciones` y su `.c-btn`) con comentario de la spec 053 y las de ancho completo dentro del `@media (max-width: 559px)` existente que contiene `.pub-hero__actions .c-btn` (`publico.css:842`) (A8) — resources/views/publico/inicio.blade.php, public/css/publico.css — hecho cuando: T010–T012 pasan, los tests de la 038 de `PaginaInicioTest` (`test_la_presentacion_lleva_el_horario_y_el_telefono`, `test_contacto_exacto_con_mapa_y_como_llegar`) y `PaginasPublicasCssTest` siguen en verde, y `evaluate.mjs` con `design/criterios.json` sobre `/` servida en local pasa todas las puertas (`G-sin-js`, `G-toque`, `G-contraste`, `G-foco`, `G-red`) y los KPIs K1, K2, K4, K7 y K8, con K6 y K9 sobre la copia con el mapa en blanco (D7; `report.json` en `.ai/inspect/`, anotado) · verificación manual: primera pantalla y sección de contacto a 320, 390, 900 y 1440 px contra `docs/specs/053-whatsapp-inicio/design/candidatos/c3.html`, con y sin sesión; K3 (WhatsApp junto al teléfono en contacto a los cuatro anchos) en el DOM; foco con el teclado en el orden teléfono → WhatsApp (→ «Cómo llegar»); en un ordenador, pulsar el botón abre WhatsApp Web en otra pestaña y la página de inicio sigue abierta — cubre: CA1, CA2, CA3, CA4 — depende: T021
- [ ] T023 [P] Añadir a «02 Qué datos recabamos», tras «Si te registras o entras con Google», el punto «<strong>Si nos escribes por WhatsApp:</strong> tu número y lo que escribas, solo para responderte. WhatsApp es un servicio de un tercero con su propio aviso de privacidad.», y poner la fecha fijada en T013 en «Última actualización» de la página del aviso y de su ventana del registro (D6); en `_dialogos-legales.blade.php`, la fecha pasa a una clave `fecha` de cada entrada del `@foreach`, así que la ventana de los términos conserva «12 de noviembre de 2026» (A1) — resources/views/publico/legal/privacidad-texto.blade.php, resources/views/publico/aviso-privacidad.blade.php, resources/views/auth/_dialogos-legales.blade.php — hecho cuando: T013 pasa entero y `TerminosPrivacidadTest` completo en verde — cubre: CA6 — depende: T013

## Integración y documentación
- [ ] T090 Actualizar `docs/architecture.md` si cambió la estructura: en las páginas públicas, el enlace de WhatsApp de la portada (`PaginasPublicasController::whatsappEnlace()`, `contacto.whatsapp_mensaje` y `publico/_boton-whatsapp.blade.php`); `AGENTS.md` solo si su mapa del repositorio describe los parciales públicos, y `docs/seo.md` solo si su inventario describe el contenido de `/`; en `docs/security.md`, la fila de la superficie `GET /`: el enlace saliente a `wa.me` con `noopener noreferrer`, sin recursos de Meta, y sin «con sesión, `/` redirige al panel», que retiró la 044 (A10)
- [ ] T091 Actualizar `docs/deployment.md` y `docs/observability.md` si cambiaron variables, entornos, pasos de deploy, logs, eventos de auditoría o métricas: no cambia nada (sin variables de entorno, logs, eventos ni métricas); anotarlo en la nota de la tarea
- [ ] T092 Marcar spec como `implemented`: suite completa, Pint sobre lo tocado, gitleaks sobre el diff de la spec, semgrep `p/php` sobre los PHP tocados, `composer audit` y `auditar-npm.mjs` sin altas ni críticas, Lighthouse SEO ≥ 90 de `/` y `/aviso-de-privacidad` sobre la app en local, en móvil y escritorio (A2), `python .ai/bin/aidd.py validate` sin errores (A11), criterios `[x]` y `docs/specs/README.md`; resultados anotados en la tarea

## Despliegue (lo ejecuta `/release`)
- [ ] T095 Desplegar a staging y verificar criterios de aceptación. No hay staging: la comprobación se hace en producción, dentro de T097
  - verificación manual de CA3 en un móvil real: en `https://dentissa.app/`, pulsar «Escríbenos por WhatsApp» en la primera pantalla y en contacto → se abre la aplicación de WhatsApp con un chat al +52 753 000 0000 y el mensaje «Hola, quiero información para agendar una cita.» ya escrito y editable (el número es ficticio: basta con que se abra así)
- [ ] T096 Aprobación humana para producción
- [ ] T097 Desplegar a producción y vigilar métricas del plan (Rollout): `deploy/desplegar.sh vX.Y.Z`, `verificar.sh`, `/` en `200` con el enlace de WhatsApp en su HTML y la comprobación de T095. Rollback: `bash deploy/volver.sh <tag anterior>`, sin copia de la base (no hay cambios de datos)
- [ ] T098 Marcar spec como `released`; en las specs 038 y 045 anotar en su Historial que la 053 amplía 038 CA1 y CA5 y el aviso de privacidad de la 045, con la excepción a 045 CA3 del punto de WhatsApp (A3)

## Cobertura
| Criterio | Tarea(s) de test | Tarea(s) de implementación |
|---|---|---|
| CA1 | T010 | T021, T022 |
| CA2 | T010 | T021, T022 |
| CA3 | T011 | T020, T021, T022 |
| CA4 | T011 | T021, T022 |
| CA5 | T012 | T020 |
| CA6 | T013 | T023 |

| Amenaza (TM#) | Tarea(s) de control | Tarea(s) de test |
|---|---|---|
| TM1 | T021 | T011 |
| TM2 | T020 | T012 |
| TM3 | T021, T022 | T012 (y `test_sin_recursos_externos_salvo_el_mapa`, existente, en la suite de T092) |
| TM4 | T020, T021 | T011, T014 |

| Cambio del plan (módulo) | Tarea(s) |
|---|---|
| config/clinica.php | T020 |
| app/Http/Controllers/PaginasPublicasController.php | T020 |
| resources/views/publico/_boton-whatsapp.blade.php | T021 |
| resources/views/publico/inicio.blade.php | T022 |
| public/css/publico.css | T022 |
| resources/views/publico/legal/privacidad-texto.blade.php | T023 |
| resources/views/publico/aviso-privacidad.blade.php, resources/views/auth/_dialogos-legales.blade.php | T023 |
| tests/Feature/PaginaInicioTest.php | T010, T011, T012 |
| tests/Feature/TerminosPrivacidadTest.php | T013 |
| tests/Feature/PaginasPublicasTest.php, tests/Feature/PaginasPublicasCssTest.php | T014 |
| docs/architecture.md, AGENTS.md, docs/seo.md, docs/security.md | T090 |

| Requisito no funcional | Tarea(s) de test o verificación manual (T095) |
|---|---|
| Accesibilidad (nombre que anuncia WhatsApp, contraste AA, foco visible) | T012 · T022 (puertas `G-contraste`, `G-foco`, `G-toque` y foco con el teclado) |
| Privacidad y peso (nada de WhatsApp ni Meta hasta pulsar) | T012 · T022 (K6 y K9 con el mapa en blanco, `G-red`) |
| Descubribilidad de `/` | T092 (`test_metadatos_de_cada_ruta_publica` y `test_un_solo_h1` en la suite; Lighthouse SEO ≥ 90 de `/` y `/aviso-de-privacidad`) |
