---
spec: 053-whatsapp-inicio
verdict: approved
round: 1
date: 2026-11-21
base: d933175
head: 48df521
human_signoff: no requerido
---

# Review · 053 Escríbenos por WhatsApp desde la página de inicio

## Resumen
`approved`: los seis criterios se cumplen con tests que fallarían si se rompe lo que protegen, los cuatro
controles del modelo de amenazas están en el código y la verificación automática está en verde. Solo hay
hallazgos menores, aceptados al roadmap por el usuario (2026-11-21).
Revisores: accesibilidad y fidelidad al diseño, constitución y calidad, y SEO con subagentes de contexto
limpio (perfil `standard`, sonnet). Los revisores de spec (A) y seguridad (S), perfil `critical`, se
cortaron por el límite de uso de la cuenta; por decisión del usuario los hizo el coordinador releyendo
desde cero el paquete de review, los tests, `publico.js` y las cabeceras de nginx (menos independiente:
el coordinador implementó la spec). Las skills `design:*` no están en `skills.enabled`: se aplicó la
lista de la skill.
Conteo: bloqueante 0 · importante 0 · menor 7

## Verificación automática
| Comando | Resultado |
|---|---|
| `vendor/bin/phpunit` (suite completa, `dentissa_test`, contenedor `dentissa-app` con el código del worktree) | ✅ 1510 tests, 19 743 aserciones, 0 fallos (línea base de `/implement`: 1502; 8 nuevos) |
| `vendor/bin/pint --test` sobre los 6 PHP tocados | ✅ |
| `python .ai/bin/aidd.py validate docs/specs/053-whatsapp-inicio` | ✅ 0 errores |
| `evaluate.mjs` con `design/criterios.json` sobre `/` servida en local (T022) | ✅ 11/11 puertas; K1, K2, K4, K7, K8; K6 y K9 con el mapa en blanco, 7/7 (`.ai/inspect/053-build*`) |
| Lighthouse 13.5.0 SEO (T092) | ✅ `/` y `/aviso-de-privacidad`: 100/100 en móvil y escritorio |
| Build | ➖ sin build de assets (Blade y CSS servidos tal cual) |

## Criterios de aceptación
| CA | Test | Estado | Nota |
|---|---|---|---|
| CA1 | `PaginaInicioTest::test_whatsapp_en_la_primera_pantalla_junto_al_telefono` | ✅ | Visitante y los cuatro roles; grupo teléfono → WhatsApp en la lista del horario. K1 en el navegador a los cuatro anchos |
| CA2 | `PaginaInicioTest::test_whatsapp_en_contacto_junto_al_telefono` · `test_contacto_exacto_con_mapa_y_como_llegar` (038) | ✅ | El grupo sigue al teléfono; WhatsApp antes de «Cómo llegar»; el contacto de la 038 intacto |
| CA3 | `PaginaInicioTest::test_el_enlace_abre_whatsapp_con_el_numero_y_el_mensaje` | ✅ | Enlace exacto, dígitos de `telefono_enlace`, solo `text`, `_blank` con `noopener noreferrer`; pestaña nueva con `window.opener` nulo en Playwright. La app de WhatsApp en un móvil real queda para T095 (plan) |
| CA4 | `PaginaInicioTest::test_el_boton_no_depende_de_javascript` | ✅ | `<a href>` del servidor sin `on*` ni `data-*`; `publico.js` no registra nada sobre `.pub-wa`; puerta `G-sin-js` |
| CA5 (abuso) | `PaginaInicioTest::test_el_enlace_es_el_mismo_para_todos` | ✅ | Cuatro roles con datos propios y una visita con parámetros y `Referer`: mismo `href`, sin esos datos (en claro ni codificados) |
| CA6 | `TerminosPrivacidadTest::test_el_aviso_explica_whatsapp` · `test_el_texto_es_el_aprobado` · `test_las_ventanas_del_registro_llevan_el_texto_de_la_pagina` | ✅ | Página y ventana de los dos registros, tras el punto de Google, solo en «02»; fecha nueva solo en el aviso |
| RNF accesibilidad | `PaginaInicioTest::test_el_boton_avisa_de_que_abre_whatsapp` | ✅ | Nombre «Escríbenos por WhatsApp (se abre WhatsApp)»; icono oculto; contraste, foco y toque por puertas |
| RNF privacidad y peso | `PaginaInicioTest::test_no_carga_nada_de_whatsapp_ni_de_meta` · `PaginasPublicasTest::test_sin_recursos_externos_salvo_el_mapa` | ✅ | Por host; comprobado rompiéndolo tres veces en T012 |
| RNF descubribilidad | `PaginasPublicasTest::test_metadatos_de_cada_ruta_publica` · `test_un_solo_h1` | ✅ | Lighthouse SEO 100 |

## Conformidad con el plan
- Cambios fuera del plan: ninguno (`scope.md`: 12 archivos de código, todos citados por una tarea).
- Partes del plan sin implementar: ninguna. T001 sin diff de código: fue el rebase sobre `origin/main`.

## Constitution Check (sobre el código)
| Principio | Resultado | Evidencia |
|---|---|---|
| P1 Spec aprobada antes de código | ✅ | Spec, diseño, plan y tareas aprobados y `/analyze` r2 `pass` antes del primer commit de código (`impl_base` d933175) |
| P2 Test que falla antes y pasa después | ✅ | Notas de T010–T014 con el fallo previo; los que pasaban antes, comprobados rompiendo lo protegido (T012, T021, T022) |
| P3 Autorización en el servidor | ➖ | Sin recursos ni acciones nuevas; `/` es pública |
| P4 Entrada validada y salida escapada | ✅ | Sin entradas; `rawurlencode()` en `PaginasPublicasController.php` y `{{ }}` en el parcial; parcial en `VISTAS` (sin `{!!`) |
| P5 Nada sensible en el repositorio | ✅ | Teléfono ficticio de la configuración; gitleaks limpio |
| P6 Sin vulnerabilidades críticas o altas | ✅ | Sin dependencias nuevas; SCA y trivy limpios |
| P7 Auditoría | ➖ | No toca datos sensibles, autenticación ni permisos |
| P8 Correlación y logs | ➖ | Sin logs nuevos |
| P9 Sistema de diseño y WCAG AA | ✅ | Reglas nuevas solo con tokens (`PaginasPublicasCssTest`), sin estilos en línea (parcial en su lista); CSS idéntico al de `c3`; puertas de `evaluate.mjs` |
| P10 Rutas indexables descubribles | ✅ | Tests de metadatos y `h1`; Lighthouse SEO 100 en las dos rutas |

## Seguridad
| Herramienta | Resultado |
|---|---|
| gitleaks 8.21.2 (15 archivos cambiados desde `impl_base`, modo archivos) | ✅ 0 hallazgos |
| semgrep `p/php` 1.99.0 (6 PHP tocados) | ✅ 0 hallazgos, 0 errores |
| `composer audit --locked` (regla de la CI) + `auditar-npm.mjs` | ✅ crít: 0 · alta: 0 · media: 0 (un aviso leve de `laravel/framework`, ya en la línea base del plan) |
| trivy 0.57.1 `fs --severity HIGH,CRITICAL` (versión de la CI) | ✅ 0 vulnerabilidades (`composer.lock`) |

| Tema OWASP | Resultado | Evidencia |
|---|---|---|
| A01:2025 Control de acceso (TM2) | ✅ | `whatsappEnlace()` solo lee `config('clinica.contacto')`; CA5 probado con cuatro roles y una visita con parámetros |
| A02:2025 Configuración (TM3) | ✅ | Logotipo en SVG en línea; ningún recurso de WhatsApp ni Meta; la CSP (`docker/nginx/seguridad/comunes.conf:13`, `img-src 'self' data:`) no cambia y ya bloquearía imágenes de terceros |
| A05:2025 Inyección (TM4) | ✅ | `rawurlencode()` y `{{ }}`; romperlo con `{!! !!}` hace fallar el test (T021) |
| A06:2025 Diseño inseguro (TM1) | ✅ | `rel="noopener noreferrer"` con `target="_blank"`; quitarlo hace fallar el test (T022); `Referrer-Policy` y `noreferrer`: WhatsApp no recibe la página de origen |
| Secretos y rutas protegidas | ✅ | Sin secretos; `docker-compose.yml`, `Dockerfile` y `database/dentissa.sql` sin tocar |
| Dependencias | ✅ | Ningún lockfile cambiado (`deps.stat` vacío) |
| Instrucciones dirigidas a agentes en el código | ✅ | Ninguna en los comentarios del diff |

## Observabilidad
- Eventos de auditoría de la spec emitidos y probados: no aplica (plan: sin datos sensibles, autenticación ni permisos).
- Datos sensibles en logs del código tocado: ninguno (sin logs nuevos).
- `request_id` presente en los logs nuevos: no aplica.

## SEO
| Tema | Resultado | Evidencia |
|---|---|---|
| 1. Rastreo | ✅ | `robots.txt` no bloquea; los enlaces a `wa.me` son `<a href>`. Sin `sitemap.xml` ni línea `Sitemap:`: brecha SE3, anterior a la 053 |
| 3. Indexación y canónica | ✅ | Sin `noindex`; una canónica absoluta por ruta (local: origen de `APP_URL`, R7) |
| 4. Metadatos | ✅ | Título y descripción propios, `lang="es"`, un `h1` en `/` y en el aviso (HTML guardado de la build) |
| 5. Contenido sin JavaScript | ✅ | Botones y texto del aviso en el HTML del servidor |
| 6. Imágenes | ✅ | El icono es un SVG decorativo en línea; sin imágenes nuevas |
| 7, 8, 9 | ➖ | Sin cambios (SE6, SE7; un idioma) |
| 10. Rendimiento | ✅ | Lighthouse SEO 100; la 053 no añade JavaScript |
| 2, 11. Códigos HTTP y producción | no determinado | Se comprueba en `/release` |
- Sección «Descubribilidad» del plan aplicada: sí (`/` y `/aviso-de-privacidad`, solo contenido; metadatos sin cambios).

## Hallazgos
| ID | Severidad | Archivo:línea | Hallazgo | Sugerencia |
|---|---|---|---|---|
| R1 | menor | `tests/Feature/TerminosPrivacidadTest.php` (`test_el_aviso_explica_whatsapp`, bucle «solo en 02») | El bucle que comprueba que WhatsApp no sale fuera de «02» no asegura cuántas secciones revisó: si el filtro dejara de coincidir, pasaría sin comprobar nada | Afirmar el número de secciones revisadas (7 por sitio) antes o después del bucle |
| R2 | menor | `tests/Feature/TerminosPrivacidadTest.php` (`test_el_texto_es_el_aprobado`) | El descuento de los cambios de la 053 (`str_replace`) no falla si el punto o la fecha no están; lo cubre otro test (`test_el_aviso_explica_whatsapp`) | Afirmar que el texto contiene lo que se descuenta antes de quitarlo |
| R3 | menor | `resources/views/publico/aviso-privacidad.blade.php:17`, `resources/views/auth/_dialogos-legales.blade.php:10-11`, `TerminosPrivacidadTest.php` (`FECHA_*`) | Las fechas de los textos legales están escritas a mano en la página, en la ventana y en el test (patrón heredado de la 045); `test_las_ventanas_del_registro_llevan_el_texto_de_la_pagina` las cruza | Una sola fuente para las fechas legales (p. ej. en `config/clinica.php`) |
| R4 | menor | `app/Http/Controllers/PaginasPublicasController.php` (`whatsappEnlace()`) | Da por hecho que `telefono_enlace` es `tel:+<dígitos>` y que existe `whatsapp_mensaje`; un teléfono con extensión daría otro número | Comprobar en un test que los dígitos tienen entre 10 y 15 cifras, o documentarlo en la configuración |
| R5 | menor | `resources/views/publico/_boton-whatsapp.blade.php` | El trazo del logotipo (~1,5 kB) va dos veces en el HTML de `/` | Aceptable (K9 holgado); o un `<symbol>` con `<use>` |
| R6 | menor | `tests/Feature/PaginaInicioTest.php` (`private const WHATSAPP`) | La constante está entre métodos, no con las demás constantes de la clase | Moverla al principio de la clase |
| R7 | menor | `resources/views/publico/inicio.blade.php:9`, `aviso-privacidad.blade.php:6` (anterior a la 053) | La canónica se arma con `config('app.url')` y `docs/seo.md` dice `seo.site_url`; en local sale el origen de `APP_URL` | En `/release`, comprobar que producción emite `https://dentissa.app/`; alinear el código o `docs/seo.md` |

Descartados: «pestaña nueva» ausente del nombre accesible (decisión del usuario, `design.md` refinado A9);
enlace saliente sin `nofollow` (no afecta a la indexación).

## Preparación para release
- Rollback factible: sí, `deploy/volver.sh <tag anterior>`; sin migraciones ni datos (plan, Rollout).
- Migraciones compatibles con la versión anterior: no hay migraciones.
- Feature flags: el plan no pide ninguno.
- Docs actualizadas: `docs/architecture.md` y `docs/security.md` (T090); `docs/deployment.md` y `docs/observability.md` sin cambios (T091).
- SCA sin críticas ni altas: sí.
- Cobertura de riesgos: la spec no cita riesgos `RS`, `OB`, `RD` ni `SE`.

## Tareas añadidas
- Ninguna.

## Aceptados sin tarea
Aceptados por el usuario el 2026-11-21:
- **R1** → roadmap: contar las secciones revisadas en el bucle «solo en 02» de `test_el_aviso_explica_whatsapp`.
- **R2** → roadmap: que el descuento de `test_el_texto_es_el_aprobado` falle si falta lo que quita.
- **R3** → roadmap: una sola fuente para las fechas de los textos legales.
- **R4** → roadmap: validar o documentar el formato de `telefono_enlace` que usa `whatsappEnlace()`.
- **R5** → roadmap: logotipo de WhatsApp repetido en `/` (`<symbol>` si algún día pesa).
- **R6** → roadmap: mover `WHATSAPP` con las constantes de `PaginaInicioTest`.
- **R7** → roadmap: canónica desde `APP_URL` frente a `seo.site_url`; comprobarla en producción en `/release 053`.
