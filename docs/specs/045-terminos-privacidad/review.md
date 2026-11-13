---
spec: 045-terminos-privacidad
verdict: changes_requested
round: 1
date: 2026-11-13
base: 4c67991
head: cbc2224
human_signoff: pending
---

# Review · 045 Términos de uso y aviso de privacidad

## Resumen
`changes_requested`: sin bloqueantes, pero dos tests no prueban del todo lo que dicen (R1: qué ventana abre
cada enlace; R2: que los datos de la clínica salgan de verdad de la configuración) y el usuario decide
corregirlos antes de liberar, junto con cinco menores (R3, R5, R8, R13, R14). El comportamiento es correcto:
suite completa en verde, herramientas de seguridad limpias, diseño medido y verificado por el usuario. Los otros
siete menores van al roadmap. Revisores independientes: spec (`critical`), constitución y calidad (`standard`),
seguridad (`critical`), accesibilidad (`standard`) y SEO (`standard`, sobre el HTML servido).
Conteo: bloqueante 0 · importante 2 · menor 12

## Verificación automática
| Comando | Resultado |
|---|---|
| `php artisan test` (suite completa, en `cbc2224`) | ✅ 930 en verde (14 744 aserciones); línea base 914 de `main`, sin fallos previos |
| Pint sobre los PHP tocados | ✅ PASS (7 archivos; T027) |
| `python .ai/bin/aidd.py validate docs/specs/045-terminos-privacidad` | ✅ 0 errores |
| `design/medir.mjs` sobre la app servida (`terminos`, `privacidad`) y sobre `candidatos/c4` (`registro-dialogo`) | ✅ 11/11 puertas y 5/5 KPIs en cada página; 11/11 y 3/3 (K8) en la ventana |
| Build | ➖ sin build de assets que cambie (`npm run build` solo afecta a `welcome.blade.php`) |

## Criterios de aceptación
| CA | Test | Estado | Nota |
|---|---|---|---|
| CA1 | `TerminosPrivacidadTest::test_terminos_tiene_sus_secciones_y_la_fecha`, `test_los_datos_de_la_clinica_salen_de_la_configuracion`, `test_el_texto_es_el_aprobado` | ✅ | R2: el de la configuración no la cambia |
| CA2 | `test_el_aviso_tiene_sus_secciones_y_la_fecha`, `test_el_texto_es_el_aprobado` | ✅ | R5: búsquedas en toda la sección |
| CA3 | `test_el_aviso_nombra_cada_dato_del_registro` + verificación manual (T023) | ✅ | R5: «nombre», «teléfono» y «dirección» aparecen también en otras viñetas |
| CA4 | `test_la_casilla_del_registro`, `test_las_ventanas_del_registro_llevan_el_texto_de_la_pagina`, `test_la_ventana_del_registro_solo_usa_tokens`, `test_el_script_de_las_ventanas_abre_y_cierra` + verificación manual (T025, usuario) | ⚠ | R1: no comprueba qué ventana abre cada enlace ni que las dos sean distintas |
| CA5 | `test_el_registro_sin_la_casilla_se_rechaza`, `test_el_registro_con_google_sin_la_casilla_se_rechaza` | ✅ | |
| CA6 | `test_el_pie_publico_enlaza_las_dos_paginas` + K5 | ✅ | |
| CA7 | `test_con_y_sin_sesion_se_ven_igual` (caracterización comprobada) | ✅ | |
| CA8 | `test_el_texto_es_el_aprobado`, `PaginasPublicasTest::test_todos_los_enlaces_internos_resuelven` + `G-sin-js` | ✅ | |

## Conformidad con el plan
- Cambios fuera del plan: ninguno (`scope.md`: 0 archivos sin tarea; T032 y T033, añadidas en `/implement` con aprobación del usuario).
- Partes del plan sin implementar: ninguna (T001 y T003 sin diff de código: son de documentación, `system.md` y `criterios.json`).

## Constitution Check (sobre el código)
| Principio | Resultado | Evidencia |
|---|---|---|
| P1 Spec aprobada | ✅ | spec, diseño, plan y tareas aprobados; análisis r5 `pass` |
| P2 Test que falla antes | ✅ | cada CA con test; caracterizaciones de CA5 (Google) y CA7 comprobadas rompiendo el código (notas de T010 y T011). R1 y R2: dos tests que pasarían con lo protegido roto |
| P3 Autorización | ✅ | rutas públicas sin datos de cuentas; `test_con_y_sin_sesion_se_ven_igual` |
| P4 Entrada y SQL | ✅ | `'terminos' => ['accepted']` en `RegisterController::register`; vistas y parciales en `VISTAS` del test de salida sin escapar; sin `{!!` |
| P5 Nada sensible | ✅ | gitleaks sin fugas |
| P6 Sin vulnerabilidades altas | ✅ | `composer audit`, `npm audit` y trivy limpios; `phpseclib` 3.0.58 |
| P7 Auditoría | ➖ | sin datos sensibles, autenticación ni permisos nuevos (el alta sigue sin auditarse, código previo) |
| P8 Correlación y logs | ➖ | sin logs nuevos |
| P9 Sistema de diseño | ✅ | `test_las_vistas_publicas_no_llevan_estilos_en_linea` (vistas, parciales y rutas), `test_la_ventana_del_registro_solo_usa_tokens`, `test_newsreader_tiene_un_respaldo_con_sus_medidas`; `system.md` 1.15.0 |
| P10 Descubribilidad | ✅ | `test_metadatos_de_cada_ruta_publica` con las dos rutas; Lighthouse SEO 100/100 en las dos y 91/92 en `/registro` (solo SE1.a, previa) |

## Seguridad
| Herramienta | Resultado |
|---|---|
| gitleaks 8.21.2 (32 archivos cambiados desde `4c67991`, modo archivos) | ✅ 0 hallazgos |
| semgrep 1.99.0 `p/php` (PHP y Blade tocados) | ✅ 0 hallazgos |
| `composer audit --locked` (y el de la CI) · `npm audit --audit-level=high` | crít: 0 · alta: 0 · media: 0 |
| trivy 0.58.1 `fs --severity HIGH,CRITICAL` | ✅ 0 hallazgos |

| Tema OWASP (2025) | Resultado | Evidencia |
|---|---|---|
| A01 Control de acceso | ✅ | `routes/web.php`: dos GET públicos de texto fijo; ninguna ruta protegida tocada; `throttle:registro` intacto |
| A02 Configuración | ✅ | sin secretos ni debug; la ventana funciona con la CSP actual (R8: amplía RS8.d) |
| A03 Cadena de suministro | ✅ | solo `phpseclib` 3.0.57 → 3.0.58, verificada en D10 y T002 |
| A04 Criptografía y datos sensibles | ➖ | no guarda ni pinta datos sensibles |
| A05 Inyección / XSS | ✅ | todo lo de la configuración con dobles llaves; el script no usa `innerHTML` ni `eval` |
| A06 Diseño inseguro | ✅ | TM2 con control y test; TM1 retirada con su test |
| A07 Autenticación | ➖ | no tocada |
| A08 Integridad | ✅ | `@csrf` intacto |
| A09 Registro y auditoría | ➖ | sin logs nuevos |
| A10 Manejo de errores | ✅ | el registro sin casilla se rechaza cerrado, sin crear la cuenta (R3: un horario mal escrito daría 500) |

## Observabilidad
- Eventos de auditoría de la spec emitidos y probados: no aplica (la spec no tiene sección de auditoría con eventos).
- Datos sensibles en logs del código tocado: ninguno (sin `Log::` nuevos).
- `request_id` presente en los logs nuevos: no hay logs nuevos.

## SEO
| Tema | Resultado | Evidencia |
|---|---|---|
| Indexación y canónica | ✅ | `/terminos` y `/aviso-de-privacidad` sin `noindex`, una canónica absoluta (en producción `https://dentissa.app/…`, que fija el test) |
| Título, descripción e idioma | ✅ | propios y únicos; `lang="es"`, viewport |
| Encabezados y contenido en el HTML | ✅ | un `h1`, `h2` sin saltos; texto completo en la respuesta del servidor |
| `robots.txt` y sitemap | ✅ / ➖ | `robots.txt` permite rastrear; sin sitemap (SE3, fuera de alcance) |
- Sección "Descubribilidad" del plan aplicada: sí, en las dos rutas, con `PaginasPublicasTest::test_metadatos_de_cada_ruta_publica`.

## Hallazgos
| ID | Severidad | Archivo:línea | Hallazgo | Sugerencia |
|---|---|---|---|---|
| R1 | importante | `tests/Feature/TerminosPrivacidadTest.php:262-263`, `:301` | `test_la_casilla_del_registro` solo comprueba que existe un `dialog` con el id de `data-dialogo`, no cuál; `test_las_ventanas_…` acepta dos títulos iguales. Seguirían en verde si se intercambian los `data-dialogo` o si las dos ventanas fueran la misma | El título de la ventana de cada enlace es el suyo («Términos de uso» / «Aviso de privacidad») y las dos ventanas tienen títulos distintos |
| R2 | importante | `tests/Feature/TerminosPrivacidadTest.php:141-157` | `test_los_datos_de_la_clinica_salen_de_la_configuracion` usa la configuración actual, igual al texto aprobado: pasaría con los datos escritos a mano | Cambiar la configuración en el test y comprobar que los valores nuevos salen en las dos páginas y en las ventanas |
| R3 | menor | `app/Http/Controllers/PaginasPublicasController.php:65` | `explode('–', $horas)` con desestructuración: un tramo sin guion largo daría error en las dos páginas y en los dos registros | Un tramo sin guion largo se usa tal cual; test con ese caso |
| R4 | menor | `publico/terminos.blade.php:17`, `aviso-privacidad.blade.php:17`, `auth/_dialogos-legales.blade.php:16`, test (`FECHA`) | La fecha de actualización está repetida y es una sola para las dos ventanas | Una fecha por texto, en una sola fuente |
| R5 | menor | `tests/Feature/TerminosPrivacidadTest.php:130`, `:179-183` | Las búsquedas de CA3 y CA2 recorren toda la sección: «nombre», «teléfono» y «dirección» aparecen en otras viñetas | Buscar cada dato en su viñeta |
| R6 | menor | `tests/Feature/TerminosPrivacidadTest.php:~326` | `test_el_script_de_las_ventanas_abre_y_cierra` solo mira la estructura del script (aceptado en el análisis, A17) | Documentarlo en el test; un runner JS si se mueve a `public/js/` |
| R7 | menor | `resources/views/auth/_dialogos-legales.blade.php:5-8` | El parcial llama a un método estático del controlador desde `@php` | Llevar `horarioEnTexto()` a una clase de dominio o pasarlo desde `RegisterController` |
| R8 | menor | `resources/views/auth/_dialogos-legales.blade.php:25`, `plan.md` D12 | El script en línea nuevo amplía la deuda de la CSP estricta (RS8.d) sin anotarlo | Anotar en RS8.d que el registro gana este script |
| R9 | menor | `auth/register.blade.php:291-292`, `registro-contacto.blade.php:135-136` | `aria-haspopup="dialog"` también sin JavaScript, cuando el enlace navega | Ponerlo desde el script, solo si existe `showModal` |
| R10 | menor | `publico/legal/terminos-texto.blade.php:6…`, `privacidad-texto.blade.php` | Cada `section` con `aria-labelledby` crea una región: 7 y 8 por página, más las de las ventanas | Quitar `aria-labelledby` de las secciones |
| R11 | menor | `auth/_dialogos-legales.blade.php:20`, `public/css/auth.css` (`.dialogo-legal__texto`) | El texto desplazable de la ventana no es enfocable con el teclado en navegadores antiguos (Safari, Chromium < 130) | `tabindex="0"` con su nombre |
| R12 | menor | `auth/_dialogos-legales.blade.php:43` | Seleccionar texto y soltar el ratón sobre el velo cierra la ventana | Cerrar solo si el `pointerdown` empezó en el velo |
| R13 | menor | `publico/legal/terminos-texto.blade.php:5` | El enlace al aviso dentro de la ventana de términos abre otra ventana sin `aria-haspopup="dialog"` | Añadirlo dentro del mismo `@if ($enVentana)` |
| R14 | menor | `plan.md` → Modelo de amenazas (TM2) | TM2 con «A03:2021 Injection»; `docs/security.md` usa la edición 2025 (A05:2025) | Cambiar la etiqueta |

## Preparación para release
- Rollback factible: sí, el de `docs/deployment.md` al tag anterior; sin migraciones ni datos.
- Migraciones compatibles con la versión anterior: no hay migraciones.
- Feature flags: ninguno (el plan no pide).
- Docs actualizadas (architecture, deployment): sí (T090, T091); también `seo.md`, `security.md` y `AGENTS.md` (T030).
- SCA: sin críticas ni altas. Cobertura de riesgos: RS17.b anotada como corregida en la rama, pendiente de liberar (no mitigada); la spec no declara otras correcciones.

## Tareas añadidas
- T034 ← R1
- T035 ← R2
- T036, T037 ← R3
- T038 ← R5
- T039, T040 ← R13
- T041 ← R8, R14

## Aceptados sin tarea
- **R4** → roadmap: la fecha de actualización de los términos y del aviso, en una sola fuente y una por texto
- **R6** → roadmap: el comportamiento de las ventanas con un runner JS si su script pasa a `public/js/`
- **R7** → roadmap: `horarioEnTexto()` fuera del controlador (clase de dominio) o pasado desde `RegisterController`
- **R9** → roadmap: `aria-haspopup` de los enlaces de la casilla solo cuando hay `showModal`
- **R10** → roadmap: las secciones de los textos legales sin `aria-labelledby` (menos regiones para el lector)
- **R11** → roadmap: el texto desplazable de la ventana enfocable con el teclado en navegadores antiguos
- **R12** → roadmap: la ventana no se cierra al soltar sobre el velo una selección empezada dentro
