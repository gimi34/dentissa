---
spec: 053-whatsapp-inicio
status: approved   # draft | approved | blocked
constitution_version: 1.3.0
created: 2026-11-20
---

# Plan · 053 Escríbenos por WhatsApp desde la página de inicio

## Enfoque técnico
Un enlace estático a `wa.me`, sin backend ni JavaScript, en dos sitios de `publico/inicio.blade.php`:
el grupo de contacto de la primera pantalla y las acciones de «Encuéntranos fácilmente», con el marcado,
el logotipo y las reglas del candidato aceptado `c3` (`design.md`). El enlace se construye una sola vez en
`PaginasPublicasController` a partir del teléfono de `config('clinica.contacto')` y de un mensaje nuevo de
esa misma configuración, así que el número del chat es siempre el del teléfono de la página (CA3) y el
enlace es igual para todos (CA5). El botón vive en un parcial que usan los dos sitios. El aviso de
privacidad gana un punto en «02 Qué datos recabamos» y su fecha de actualización, en la página y en la
ventana del registro (CA6). Sin rutas, datos, migraciones ni dependencias nuevas.

## Constitution Check
| Principio | Resultado | Justificación / ajuste | Cómo se verifica |
|---|---|---|---|
| P1 Spec aprobada antes de código | ✅ | Spec 053 y `design.md` (`c3`) aprobados; este plan y `tasks.md` se aprueban antes de `/implement` | manual: `python .ai/bin/aidd.py status` |
| P2 Test que falla antes y pasa después | ✅ | Cada CA con su test (Trazabilidad), escrito antes del cambio y visto en rojo. `test_el_texto_es_el_aprobado` de la 045 se ajusta para comparar con `c4` el texto sin los cambios de la 053 (D5) y el test nuevo de CA6 vigila esos cambios | test: Trazabilidad; `/implement` anota el fallo previo |
| P3 Autorización en el servidor | ➖ | Sin recursos ni acciones nuevas: el botón es un enlace público en una ruta pública que ya existe; con y sin sesión se ve igual | — |
| P4 Entrada validada y SQL con bindings | ✅ | Ninguna entrada nueva. El enlace se arma en el servidor desde la configuración con `rawurlencode()` para el mensaje y sale en Blade con dobles llaves; el parcial nuevo entra en la lista `VISTAS` de las vistas sin salida sin escapar | test: `PaginasPublicasTest::test_las_vistas_publicas_no_usan_html_sin_escapar` con `publico/_boton-whatsapp.blade.php` · `PaginaInicioTest::test_el_enlace_abre_whatsapp_con_el_numero_y_el_mensaje` · lint: `semgrep` sobre lo tocado |
| P5 Nada sensible en el repositorio | ✅ | Solo datos ficticios de `config/clinica.php` (el teléfono 753 000 0000) y un mensaje fijo | lint: `gitleaks detect --no-banner` |
| P6 Sin vulnerabilidades críticas o altas | ✅ | Línea base del 2026-11-20: `composer audit --locked` sin avisos altos ni críticos (uno leve de `laravel/framework`, ignorado por la regla de la CI); `auditar-npm.mjs` sin avisos (avisa de que EX5 ya no aparece: lo retiró `chore/retirar-ex5`, PR #99, ya en `main`). Sin dependencias nuevas: el logotipo es un SVG en línea (Simple Icons, CC0), no un paquete | manual: el comando SCA de la CI en la tarea final y en `/review` |
| P7 Auditoría | ➖ | No toca datos sensibles, autenticación ni permisos: la aplicación no envía ni recibe nada de WhatsApp | — |
| P8 Correlación y logs | ➖ | Sin logs nuevos; `/` ya pasa por el middleware de correlación | — |
| P9 Sistema de diseño | ✅ | `design.md` sin «Cambios a incorporar al sistema»: solo tokens, `c-btn c-btn--secondary` y el corte de 560 px que ya existe. Reglas en `publico.css` sin literales; sin estilos en línea en las vistas. WCAG 2.1 AA por las puertas de `criterios.json` | test: `PaginasPublicasCssTest` (solo tokens y sin estilos en línea en las vistas públicas, que ya recorre `inicio.blade.php`; el parcial nuevo en su lista) · `PaginaInicioTest::test_el_boton_avisa_de_que_abre_whatsapp` · manual: `evaluate.mjs` con `design/criterios.json` sobre `/` servida en local (D7) y verificación del usuario contra `candidatos/c3.html` a 320, 390, 900 y 1440 px |
| P10 Rutas indexables descubribles | ✅ | `/` sigue indexable con su título, descripción, canónica y un `h1`; el botón no cambia metadatos. `/aviso-de-privacidad` solo cambia texto | test: `PaginasPublicasTest::test_metadatos_de_cada_ruta_publica` (existente, sin cambios, en verde) |

Resultado: 7 ✅, 3 ➖ y ninguna violación.

## Cambios por módulo
| Módulo | Cambio | Riesgo |
|---|---|---|
| `config/clinica.php` | `contacto.whatsapp_mensaje` = «Hola, quiero información para agendar una cita.», con su comentario (spec 053) | Bajo |
| `app/Http/Controllers/PaginasPublicasController.php` | `whatsappEnlace()` (estático): `https://wa.me/` + los dígitos de `contacto.telefono_enlace` + `?text=` + `rawurlencode(contacto.whatsapp_mensaje)`; `inicio()` lo pasa a la vista como `$whatsapp` | Bajo |
| `resources/views/publico/_boton-whatsapp.blade.php` (nuevo) | El enlace de `c3`: `c-btn c-btn--secondary pub-wa`, `href` con `$whatsapp` en dobles llaves, `target="_blank"`, `rel="noopener noreferrer"`, `aria-label` «Escríbenos por WhatsApp (se abre WhatsApp en una pestaña nueva)», el logotipo de una tinta en SVG en línea (`fill="currentColor"`, `aria-hidden`, `focusable="false"`) y el texto | Bajo |
| `resources/views/publico/inicio.blade.php` | Primera pantalla: el `<li>` del teléfono pasa a `<li class="pub-hero__contacto">` con el teléfono y el parcial. Contacto: el `<div>` de «Cómo llegar» pasa a `<div class="pub-visit__acciones">` con el parcial delante | Medio: tests de la 038 que leen la primera pantalla y el contacto (`test_la_presentacion_lleva_el_horario_y_el_telefono`, `test_contacto_exacto_con_mapa_y_como_llegar`) deben seguir en verde |
| `public/css/publico.css` | Las reglas de `c3` (`<style id="cambios-053">`): `.pub-hero__contacto`, `.pub-hero__facts a.pub-wa` (tamaño de texto y sin subrayado), `.pub-visit__acciones` y su `.c-btn`; las de ancho completo dentro del `@media (max-width: 559px)` existente | Medio: hoja compartida con servicios, galería y páginas legales; clases nuevas, sin tocar las existentes |
| `resources/views/publico/legal/privacidad-texto.blade.php` | En «02 Qué datos recabamos», tras «Si te registras o entras con Google»: `<li><strong>Si nos escribes por WhatsApp:</strong> tu número y lo que escribas, solo para responderte. WhatsApp es un servicio de un tercero con su propio aviso de privacidad.</li>` | Bajo: lo usan la página y la ventana del registro |
| `resources/views/publico/aviso-privacidad.blade.php`, `resources/views/auth/_dialogos-legales.blade.php` | «Última actualización»: la fecha del cambio del texto (D6) | Bajo |
| `tests/Feature/PaginaInicioTest.php` | Tests de CA1–CA5 y de los RNF de privacidad y accesibilidad | — |
| `tests/Feature/TerminosPrivacidadTest.php` | Test de CA6 (página y ventana) y `test_el_texto_es_el_aprobado` con los cambios de la 053 descontados (D5) | Bajo |
| `tests/Feature/PaginasPublicasTest.php`, `tests/Feature/PaginasPublicasCssTest.php` | El parcial nuevo en sus listas de vistas | Bajo |
| `docs/architecture.md`, `AGENTS.md`, `docs/seo.md` | T090: el botón y el parcial en la descripción de las páginas públicas; `docs/seo.md` solo si su inventario describe el contenido de `/` | — |

## Contratos y datos
- **Sin rutas ni respuestas nuevas.** `GET /` sigue respondiendo `200` con y sin sesión; cambia solo su HTML.
- **Enlace** (igual para todos los visitantes): `https://wa.me/527530000000?text=Hola%2C%20quiero%20informaci%C3%B3n%20para%20agendar%20una%20cita.`
  Los dígitos salen de `telefono_enlace` (`tel:+527530000000` → `527530000000`); si cambia el teléfono,
  cambia el chat.
- **Configuración:** `clinica.contacto.whatsapp_mensaje` (texto). Sin variables de entorno.
- **Datos:** ninguno. Sin migraciones.
- **Pantallas y estados** (de `design.md`): la primera pantalla con y sin sesión (con sesión cambia solo
  «Agendar mi primera cita» por «Ir a mi panel», 044 CA5) y la sección de contacto, a 320, 390, 900 y
  1440 px, con y sin JavaScript. Accesibilidad medible: nombre accesible que empieza por el texto visible
  (WCAG 2.5.3) y avisa de la pestaña nueva; icono oculto a lectores de pantalla; 44 px de toque, contraste
  y foco por las puertas de `criterios.json`; orden de teclado teléfono → WhatsApp (→ «Cómo llegar»).

## Estrategia de pruebas
- **PHPUnit (`tests/Feature`, `DatabaseTransactions`)** sobre el HTML de `/` y de `/aviso-de-privacidad`
  con `DOMXPath`, como los tests de la 038 y la 045: estructura de los dos grupos, enlace exacto, atributos,
  igualdad del enlace entre visitante y los cuatro roles, ausencia de recursos de terceros nuevos y el texto
  del aviso en la página y en la ventana del registro.
- **Tests existentes que vigilan la regresión:** los de la 038 sobre la primera pantalla y el contacto, los
  de metadatos y salida escapada de `PaginasPublicasTest`, los de solo tokens y sin estilos en línea de
  `PaginasPublicasCssTest`, y `test_el_texto_es_el_aprobado` de la 045 (ajustado, D5).
- **Diseño en el navegador:** `evaluate.mjs` con `design/criterios.json` sobre `/` servida en local, por el
  procedimiento de `design.md` (D7). Es verificación manual de la tarea de la vista: la CI no ejecuta
  navegador.
- Sin JavaScript nuevo: no hay tests de `node --test` que añadir.

## Modelo de amenazas
| ID | Amenaza (STRIDE) | Categoría OWASP | Componente | Control | Test |
|---|---|---|---|---|---|
| TM1 | Manipulación: la pestaña de WhatsApp toma el control de la página de inicio por `window.opener` (*reverse tabnabbing*) | A06:2025 Insecure Design | `_boton-whatsapp` | `rel="noopener noreferrer"` en el enlace con `target="_blank"` | `PaginaInicioTest::test_el_enlace_abre_whatsapp_con_el_numero_y_el_mensaje` |
| TM2 | Divulgación de información: el enlace o el mensaje llevan datos de la sesión o de la visita (CA5) | A01:2025 Broken Access Control | `PaginasPublicasController::whatsappEnlace()` | Enlace construido solo desde la configuración, sin datos de la petición ni del usuario | `PaginaInicioTest::test_el_enlace_es_el_mismo_para_todos` |
| TM3 | Divulgación de información: la página carga scripts, imágenes o fuentes de WhatsApp o Meta y les avisa de cada visita | A02:2025 Security Misconfiguration | `inicio.blade.php`, `_boton-whatsapp` | Logotipo en SVG en línea; ningún `src` ni `link` a dominios de WhatsApp o Meta; la CSP no cambia | `PaginaInicioTest::test_no_carga_nada_de_whatsapp_ni_de_meta` |
| TM4 | Manipulación: un mensaje de la configuración con caracteres especiales rompe el atributo o inyecta HTML | A05:2025 Injection | `whatsappEnlace()`, parcial | `rawurlencode()` del mensaje y salida con dobles llaves | `PaginasPublicasTest::test_las_vistas_publicas_no_usan_html_sin_escapar` (parcial en `VISTAS`) · `PaginaInicioTest::test_el_enlace_abre_whatsapp_con_el_numero_y_el_mensaje` |

Los casos de abuso de la spec quedan cubiertos: CA5 por TM2; el de los recursos de terceros (requisitos no
funcionales) por TM3.

## Trazabilidad
| Criterio de aceptación | Cambio(s) | Test(s) |
|---|---|---|
| CA1: «Escríbenos por WhatsApp» en la primera pantalla, junto al horario y el teléfono, con y sin sesión | `inicio.blade.php`, parcial, `publico.css` | `PaginaInicioTest::test_whatsapp_en_la_primera_pantalla_junto_al_telefono` (visitante y los cuatro roles) · verificación manual: `evaluate.mjs` K1 y K2 a los cuatro anchos y la primera pantalla contra `candidatos/c3.html` |
| CA2: en contacto, junto al teléfono, sin cambiar el resto | `inicio.blade.php`, parcial, `publico.css` | `PaginaInicioTest::test_whatsapp_en_contacto_junto_al_telefono` (el grupo sigue al teléfono, WhatsApp antes de «Cómo llegar») · `test_contacto_exacto_con_mapa_y_como_llegar` (038, en verde) · verificación manual: `evaluate.mjs` K4 y la sección contra `c3` |
| CA3: abre WhatsApp al número del teléfono con el mensaje; la página sigue abierta | `whatsappEnlace()`, `config/clinica.php`, parcial | `PaginaInicioTest::test_el_enlace_abre_whatsapp_con_el_numero_y_el_mensaje` (enlace exacto, dígitos iguales a `telefono_enlace`, `target="_blank"`) · verificación manual: pulsar el botón en un móvil (abre la app) y en un ordenador (WhatsApp Web en otra pestaña) |
| CA4: funciona igual sin JavaScript | parcial (enlace `<a href>` sin JavaScript) | `PaginaInicioTest::test_el_boton_no_depende_de_javascript` (enlace con `href` en el HTML del servidor, sin `onclick` ni atributos de `publico.js`) · verificación manual: puerta `G-sin-js` de `evaluate.mjs` |
| CA5 (abuso): enlace igual para todos y sin datos de la sesión | `whatsappEnlace()` | `PaginaInicioTest::test_el_enlace_es_el_mismo_para_todos` (visitante y cuatro roles con datos propios: mismo `href`, sin su nombre, correo ni teléfono) |
| CA6: el aviso de privacidad explica WhatsApp en «Qué datos recabamos» | `privacidad-texto.blade.php`, fechas | `TerminosPrivacidadTest::test_el_aviso_explica_whatsapp` (página y ventana del registro, dentro de la sección `datos`) · `test_el_texto_es_el_aprobado` (ajustado, D5) |
| TM1 | parcial | `test_el_enlace_abre_whatsapp_con_el_numero_y_el_mensaje` |
| TM2 | `whatsappEnlace()` | `test_el_enlace_es_el_mismo_para_todos` |
| TM3 | parcial, `inicio.blade.php` | `test_no_carga_nada_de_whatsapp_ni_de_meta` |
| TM4 | `whatsappEnlace()`, parcial | `test_las_vistas_publicas_no_usan_html_sin_escapar` · `test_el_enlace_abre_whatsapp_con_el_numero_y_el_mensaje` |
| RNF: accesibilidad (nombre que anuncia WhatsApp, contraste AA, foco visible) | parcial, `publico.css` | `PaginaInicioTest::test_el_boton_avisa_de_que_abre_whatsapp` (`aria-label` empieza por el texto visible; icono con `aria-hidden`) · verificación manual: puertas `G-contraste`, `G-foco`, `G-toque` de `evaluate.mjs` |
| RNF: privacidad y peso (nada de WhatsApp ni Meta hasta pulsar) | parcial | `test_no_carga_nada_de_whatsapp_ni_de_meta` · verificación manual: `evaluate.mjs` K6 y K9 con el mapa en blanco |
| RNF: descubribilidad de `/` | — | `PaginasPublicasTest::test_metadatos_de_cada_ruta_publica` (existente) |

## Observabilidad
No aplica: la spec no toca datos sensibles, autenticación ni permisos y no añade logs, eventos ni métricas.
Tras el deploy basta con comprobar que `/` responde `200` y lleva el enlace (verificación de `docs/deployment.md`).

## Descubribilidad
| Ruta | Indexable | Título | Descripción | Canónica | Sitemap | Datos estructurados | Test |
|---|---|---|---|---|---|---|---|
| `/` (modificada: solo contenido) | sí (038) | sin cambios: «Dentissa · Tu sonrisa, nuestra prioridad» | sin cambios | `https://dentissa.app/` | sin cambios | — | `PaginasPublicasTest::test_metadatos_de_cada_ruta_publica` |
| `/aviso-de-privacidad` (modificada: solo texto) | sí (045) | sin cambios | sin cambios | sin cambios | sin cambios | — | `PaginasPublicasTest::test_metadatos_de_cada_ruta_publica` |

- Renderizado de las rutas nuevas: no hay rutas nuevas; `ssr` como hasta ahora.
- Imagen para compartir: sin cambios.
- Brechas `SE` que atiende: ninguna.

## Rollout
- Feature flag: no aplica. Es un enlace estático en una página pública, sin flujo existente que cambie
  ni riesgo medio o alto; se apaga con la vuelta atrás.
- Orden de despliegue: un solo paso (código y configuración en la misma imagen), por el procedimiento
  manual de `docs/deployment.md`.
- Compatibilidad: sin migraciones ni estado; la versión anterior y la nueva conviven sin problema.
- Rollback: `deploy/volver.sh <tag anterior>`; no hace falta copia de la base (no hay cambios de datos).
- Métricas a vigilar tras el deploy: `/` en `200`, el enlace presente en el HTML de producción y la
  verificación manual de CA3 en un móvil real (T095).

## Decisiones (→ ADR si son arquitectónicas)
- **D1 · De dónde sale el número del chat.** Opciones: (a) un `whatsapp_enlace` fijo en la configuración;
  (b) los dígitos de `telefono_enlace` más el mensaje de la configuración. **(b)**: CA3 exige el mismo
  número que el teléfono de la página, y así no pueden separarse.
- **D2 · Dónde se arma el enlace.** Opciones: (a) en la vista; (b) en `PaginasPublicasController`, como
  `horarioEnTexto()`. **(b)**: un solo sitio, probado, y la vista solo lo imprime.
- **D3 · Un parcial para el botón.** Los dos sitios llevan el mismo enlace, logotipo y nombre accesible;
  un parcial evita que se separen.
- **D4 · Logotipo.** El de WhatsApp de una tinta en SVG en línea (Simple Icons 13.21.0, CC0), sin
  alterar y en `currentColor` (`design.md`, decisión del usuario). No es una dependencia: el trazo va
  copiado en el parcial con su origen en un comentario.
- **D5 · El test del texto aprobado de la 045.** `test_el_texto_es_el_aprobado` compara el aviso con
  `045…/design/candidatos/c4/privacidad.html`. Opciones: (a) editar el candidato de una spec liberada;
  (b) que el test descuente los cambios de la 053 (el punto de WhatsApp y la fecha nueva) antes de
  comparar, y que un test nuevo los compruebe. **(b)**: los artefactos de una spec `released` no se tocan.
- **D6 · Fecha de actualización del aviso.** El aviso promete publicar la versión nueva «con su fecha de
  actualización arriba» (apartado 08): la fecha de la página y la de la ventana del registro pasan a la del
  día en que se cambia el texto en `/implement`, y el test de CA6 la comprueba.
- **D7 · Medir el diseño sobre la build.** Como en `design.md`: `evaluate.mjs` sobre `/` servida en local
  para las puertas y los KPIs, y sobre una copia guardada de ese HTML con el mapa en blanco para K6 y K9.
- **D8 · Dónde va la frase de CA6.** En «02 Qué datos recabamos», como un punto más de la lista
  (decisión del usuario, 2026-11-20); la spec se ajustó (Historial).
- Ninguna decisión es arquitectónica: sin ADR.

## Impacto en arquitectura
- `docs/architecture.md`: en las páginas públicas, el enlace de WhatsApp de la portada
  (`PaginasPublicasController::whatsappEnlace()` y `publico/_boton-whatsapp.blade.php`).
- `AGENTS.md`: nada que cambiar salvo que el mapa del repositorio describa los parciales públicos.

## Riesgos y mitigaciones
- **Romper tests de la 038 que leen la primera pantalla y el contacto** al cambiar el `<li>` y el `<div>`:
  se ejecutan en la tarea de la vista; si alguno fija el marcado antiguo, se ajusta sin cambiar lo que
  comprueba.
- **El número es ficticio**: el chat no llega a nadie. Es la decisión de la spec; la verificación manual de
  CA3 comprueba que WhatsApp se abre con el número y el mensaje, no que alguien responda.
- **WhatsApp cambia el formato de `wa.me`**: riesgo bajo; el formato es el documentado por WhatsApp para
  enlaces de chat y la verificación manual lo detectaría.
- **La rama parte de `a2cd958`** y `main` ya va por `16a110c` (EX5 retirada, fix del expediente, specs
  002/006/011): traer `main` antes de `/implement`; ninguno de esos cambios toca los archivos de este plan.
