---
result: fail
round: 1
mode: full
constitution_version: 1.3.0
date: 2026-11-20
spec_sha: 9e5839b08640
plan_sha: 210ebde58a12
tasks_sha: 248c5ac31903
---

# Análisis · 053 Escríbenos por WhatsApp desde la página de inicio

## Resumen
`fail` por un hallazgo ALTO que hay que corregir: la fecha nueva del aviso (D6) rompe tests de la 045 que
el plan no prevé, y la ventana del registro comparte una sola fecha para los términos y el aviso (A1).
Todo lo demás se corrige con `--fix` localizados (decisiones del usuario del 2026-11-20). Revisión
independiente con un subagente de contexto limpio (perfil `critical`, opus); cada hallazgo se comprobó en
su archivo. El A2 se rebaja de ALTA a MEDIA: la constitución pone Lighthouse en `/review` (P10, «Cómo se
verifica»), pero la 045 lo midió ya en su tarea final y el plan no lo cita.
Conteo: CRÍTICA 0 · ALTA 1 · MEDIA 4 · BAJA 8

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 6 / 6 |
| Amenazas con control y test | 4 / 4 |
| Principios de la constitución evaluados | 10 / 10 |

## Seguimiento de rondas anteriores
Primera ronda.

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A1 | Inconsistencia / cobertura | ALTA | `plan.md` D5, D6, Cambios por módulo; T013, T023; `TerminosPrivacidadTest.php:35`, `:122`, `:134`, `:368`; `_dialogos-legales.blade.php:16` | La constante `FECHA` («12 de noviembre de 2026») la usan `test_terminos_tiene_sus_secciones_y_la_fecha`, `test_el_aviso_tiene_sus_secciones_y_la_fecha` y `test_las_ventanas_del_registro_llevan_el_texto_de_la_pagina` (las dos ventanas); D5 y T013 solo ajustan `test_el_texto_es_el_aprobado`. Además, la fecha de `_dialogos-legales.blade.php:16` está dentro del `@foreach` y la comparten la ventana de los términos y la del aviso: cambiarla tal cual pone a los términos una fecha distinta de la de su página (045 D-fecha fija por página) | `/plan 053 --fix A1` y `/tasks 053 --fix A1`: la ventana de los términos conserva «12 de noviembre de 2026» y la del aviso lleva la fecha nueva, igual que sus páginas (test: `test_las_ventanas_del_registro_llevan_el_texto_de_la_pagina` con la fecha de cada página; los dos tests de secciones y fecha con la suya); la fecha literal del cambio se fija en T013 |
| A2 | Constitución (P10) | MEDIA | `plan.md` Constitution Check P10; T092 | P10 fija Lighthouse SEO ≥ 90 en las rutas públicas que cambian (`/` y `/aviso-de-privacidad`); el plan solo cita tests de HTML y ninguna tarea lo mide (la 045 lo hizo en su T027) | `/plan 053 --fix A2` y `/tasks 053 --fix A2`: Lighthouse SEO ≥ 90 de `/` y `/aviso-de-privacidad` en local (móvil y escritorio), con el resultado anotado en T092 |
| A3 | Inconsistencia (spec extendida) | MEDIA | `spec.md` CA6; 045 `spec.md:29` (CA3) | 045 CA3: «el aviso no menciona datos que el sitio no pide ni usos que no tiene». El número y el mensaje de WhatsApp no los pide el sitio; la 053 no declara la excepción | `/specify 053 --edit`: fila en Decisiones (excepción a 045 CA3 solo para el punto de WhatsApp en «02», decisión del usuario); T098 lo anota en el Historial de la 045 |
| A4 | Duplicación y conflicto | MEDIA | `plan.md` Riesgos; 052 `spec.md` | La 052 (recordatorio por correo, aprobada) puede necesitar tocar el aviso de privacidad (un uso nuevo del correo): el mismo parcial, la misma fecha y el mismo descuento de D5 | `/plan 053 --fix A4`: riesgo anotado; quien llegue segunda acumula sus cambios en el descuento de D5 y en la fecha del aviso |
| A5 | Ambigüedad | MEDIA | T012; `plan.md` TM3 | `test_no_carga_nada_de_whatsapp_ni_de_meta` compara por subcadenas (`meta` da falsos positivos); `PaginasPublicasTest::test_sin_recursos_externos_salvo_el_mapa` ya cubre que el único recurso externo de `/` sea el mapa | `/plan 053 --fix A5` y `/tasks 053 --fix A5`: TM3 cita también el test existente; el nuevo comprueba por host (`wa.me`, `whatsapp.com`, `whatsapp.net`, `facebook.com`, `fbcdn.net`, `meta.com` y sus subdominios) y que solo los `a.pub-wa` apuntan a `wa.me` |
| A6 | Cobertura | BAJA | CA5; T012 | CA5 habla de datos «de la sesión o de la visita», pero el test solo varía el usuario | `/tasks 053 --fix A6`: el test también visita `/?utm_source=x&nombre=y` con otro `Referer` y comprueba el mismo `href` |
| A7 | Inconsistencia | BAJA | `plan.md` P10, RNF de descubribilidad | `test_metadatos_de_cada_ruta_publica` no mira el `h1`; lo hace `test_un_solo_h1` (`PaginasPublicasTest.php:167`) | `/plan 053 --fix A7`: citar `test_un_solo_h1` |
| A8 | Ambigüedad | BAJA | `plan.md` Cambios por módulo (`publico.css`); T022 | Hay tres `@media (max-width: 559px)` en `publico.css` (`:842`, `:998`, `:1049`) | `/plan 053 --fix A8` y `/tasks 053 --fix A8`: el de `:842` (el de `.pub-hero__actions .c-btn`) |
| A9 | Accesibilidad | BAJA | `plan.md` parcial; `design.md` Accesibilidad; T012, T021 | El `aria-label` dice «se abre WhatsApp en una pestaña nueva», pero en el móvil se abre la aplicación | Decisión del usuario: «Escríbenos por WhatsApp (se abre WhatsApp)»; `design.md` (decisión `refinado`), `/plan 053 --fix A9` y `/tasks 053 --fix A9` |
| A10 | Seguridad (documentación) | BAJA | `docs/security.md:68`; T090 | La superficie `GET /` no menciona el enlace saliente a `wa.me` y dice que con sesión `/` redirige al panel (ya no, desde la 044) | `/tasks 053 --fix A10`: T090 actualiza esa fila de `docs/security.md` |
| A11 | Cobertura (Definición de terminado) | BAJA | T092; `constitution.md` Definición de terminado | T092 no ejecuta `python .ai/bin/aidd.py validate` | `/tasks 053 --fix A11` |
| A12 | Duplicación y conflicto | BAJA | 040 `spec.md:31-34` | La 040 (promociones, aprobada, sin plan) tocará `inicio.blade.php`, `PaginasPublicasController::inicio()` y `publico.css` en otra sección: solo conflicto de fusión | Nada en la 053 |
| A13 | Deriva de documentos | BAJA | `docs/seo.md:51` | Dice que la constitución no fija presupuesto de JavaScript ni de Lighthouse; P10 lo fija desde la 1.3.0 | Fuera de la 053 (`/sync`) |

## Decisiones pendientes del usuario
Resueltas el 2026-11-20:
- A3: excepción a 045 CA3 registrada en la 053; el punto de WhatsApp solo en «02 Qué datos recabamos».
- A9: `aria-label` «Escríbenos por WhatsApp (se abre WhatsApp)».
- A4–A8, A10, A11: se incorporan en el `--fix` del plan y las tareas, sin otra pregunta.

## Aceptados
Aceptados por el usuario el 2026-11-20:
- **A12** → roadmap: anotar el solapamiento con la 053 cuando se planee la 040.
- **A13** → roadmap: corregir `docs/seo.md:51` con `/sync`, fuera de la 053.
