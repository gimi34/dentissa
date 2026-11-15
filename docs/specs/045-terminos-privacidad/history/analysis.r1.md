---
result: fail
round: 1
mode: full
constitution_version: 1.3.0
date: 2026-11-12
spec_sha: 3af307313c87
plan_sha: 65c0daf3679b
tasks_sha: 88857f47f480
---

# Análisis · 045 Términos de uso y aviso de privacidad

## Resumen
Sin hallazgos CRÍTICOS, pero con tres ALTOS que hay que corregir antes de `/implement`. Las dos vistas nuevas
quedan fuera de los tests que prohíben la salida sin escapar y los estilos en línea (A1, A2). Además, el texto
aprobado del aviso dice cosas que la app no hace hoy: la cookie de «Recordarme» y un registro de seguridad de
«entradas y cambios» (A3), que CA3 prohíbe. Análisis completo hecho por un subagente independiente
(perfil `critical`), con cada hallazgo comprobado en el código.
Conteo: CRÍTICA 0 · ALTA 4 · MEDIA 6 · BAJA 4

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 8 / 8 (CA8 citado con un test que no lo recorre: A5) |
| Amenazas con control y test | 1 / 2 efectivas (TM2 sin test que cubra las vistas nuevas: A1) |
| Principios de la constitución evaluados | 10 / 10 (hallazgos en P4, P7 y P9) |

## Seguimiento de rondas anteriores
Primera ronda.

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A1 | Cobertura / Constitución P4 | ALTA | `plan.md` P4, TM2 y Trazabilidad; `tasks.md` T013 | `test_las_vistas_publicas_no_usan_html_sin_escapar` recorre la constante `VISTAS` (`layouts/publico`, `publico/inicio`, `publico/servicios`), no `METADATOS`: las vistas nuevas no entran y TM2 queda sin test | Las dos vistas nuevas en el test de salida sin escapar (test: falla si una lleva salida sin escapar); el plan deja de decir «cubre `publico/*`» → `/plan --fix`, `/tasks --fix` |
| A2 | Constitución P9 | ALTA | `plan.md` P9; `tasks.md` T014 | `PaginasPublicasCssTest::test_las_vistas_publicas_no_llevan_estilos_en_linea` usa su propia `VISTAS` y rutas fijas: ninguna tarea añade las vistas ni las rutas nuevas | Vistas y rutas nuevas en ese test (test: falla con un `style=""` en ellas) → `/tasks --fix` |
| A3 | Inconsistencia (CA3 / texto aprobado) | ALTA | `design/candidatos/c4/privacidad.html` (Cookies, Cuánto los conservamos); `plan.md` CA3; T023 | El aviso habla de una cookie de «Recordarme» que no existe (`AuthController` llama a `Auth::attempt` sin `remember`) y de un registro de seguridad de «entradas, cambios y accesos denegados», cuando hoy solo se auditan las denegaciones y algunos cambios (galería, restablecimiento de contraseña, consulta del registro); las entradas y los cambios de datos están pendientes (OB1.b, OB1.e, OB1.f → 021). CA3 prohíbe usos que el sitio no tiene, y el test del texto aprobado lo fijaría | El aviso describe solo lo que la app hace hoy (verificación: relectura frente al código en `/design-spec 045 --fix`; `test_el_texto_es_el_aprobado` fija el texto corregido) → decisión del usuario y `/design-spec 045 --fix` |
| A4 | Seguridad (dependencias) | ALTA | `plan.md` D10; T002 | La subida de `phpseclib` no deja constancia de la regla de antigüedad mínima de 7 días de `docs/security.md` | Comprobado en la consolidación: 3.0.58 publicada el 2026-11-02 (10 días el 2026-11-12), cumple. Anotarlo en D10 y en T002 → `/plan --fix`, nota de T002 |
| A5 | Inconsistencia (Trazabilidad) | MEDIA | `plan.md` CA8; T013 | `test_el_contenido_va_en_el_html_del_servidor` solo comprueba `/` y `/servicios` con textos fijos; meter las rutas en `METADATOS` no lo amplía | CA8 con un test que lea el HTML del servidor de las dos rutas (p. ej. `test_el_texto_es_el_aprobado`, que ya lo hace) → `/plan --fix`, `/tasks --fix` |
| A6 | Inconsistencia (dependencias) | MEDIA | T010 (nota), T022, T023, T024 | T022 y T023 exigen `medir.mjs`, que necesita T003 y los enlaces del pie (T024, K5); T024 termina con T012, que pide las vistas; la caracterización de CA7 no puede hacerse «tras T021» | Dependencias completas (T022/T023 → T003, T024; T024 → T022, T023 o la medición en T027) y la nota de CA7 tras T023 → `/tasks --fix` |
| A7 | Ambigüedad (CA4) | MEDIA | `spec.md` CA4; T011 | Con «(se abre en otra pestaña)» dentro de cada enlace, el texto visible de la casilla no es literalmente el de CA4 | T011 fija cómo compara: el texto sin las anotaciones es el de CA4 y cada enlace lleva su anotación → `/tasks --fix` |
| A8 | Cobertura (CA2 / datos reales) | MEDIA | `design/candidatos/c4/privacidad.html` «Qué datos recabamos» | No menciona el contacto de emergencia que anota el personal (`pacientes`) ni el navegador de las sesiones (`docs/security.md` → Sesiones) | Decisión del usuario: añadirlos al texto con A3 → `/design-spec 045 --fix` |
| A9 | Inconsistencia (Auditoría, P7) | MEDIA | `spec.md` Auditoría («el alta, que ya se registra como hoy»); `plan.md` P7 | El alta de cuentas no se audita hoy (OB1.f pendiente → 021; la spec 002 lo dice) | Spec y plan citan OB1.f como código previo → `/specify --edit` (texto), `/plan --fix` |
| A10 | Seguridad (documentación) | MEDIA | `plan.md` Impacto; T030, T090 | `docs/security.md` no se actualiza: faltan las dos rutas en sus puntos de entrada y el aviso nuevo de `phpseclib` (RS17 daba por buena la 3.0.57) | `docs/security.md` en el plan y en una tarea de documentación → `/plan --fix`, `/tasks --fix` |
| A11 | Cobertura (accesibilidad) | BAJA | `plan.md` Contratos («accesibilidad medible») | `aria-labelledby` de secciones e índice, `aria-hidden` del número y el `aside` del resumen sin test | Comprobarlos en `test_el_indice_va_antes_de_las_secciones` → `/tasks --fix` |
| A12 | Ambigüedad | BAJA | T021 | «Las dos rutas responden aunque falten las vistas»: sin vista responden 500 | Criterio observable (p. ej. `route:list` y los tests de T010 dejan de dar 404) → `/tasks --fix` |
| A13 | Inconsistencia (spec / código) | BAJA | `spec.md` CA5 («como hoy») | El registro normal hoy crea la cuenta si se salta el `required` del navegador | Quitar «como hoy» para el registro normal → `/specify --edit` (con A9) |
| A14 | Duplicación y conflicto | BAJA | `plan.md` Riesgos (solape con la 040) | La 040 también tocará `PaginasPublicasController`, `routes/web.php`, `PaginasPublicasTest` y `docs/architecture.md` | Listarlos en el riesgo → `/plan --fix` |

## Decisiones pendientes del usuario
- **A3:** ¿el aviso describe lo que la app hace hoy (quitar «Recordarme» y decir que el registro de
  seguridad guarda los accesos denegados y algunos cambios, sin «entradas»)? Recomendado. La otra opción,
  implementar «Recordarme» o la auditoría de entradas, queda fuera del alcance de la 045.
- **A8:** ¿se añaden al aviso el contacto de emergencia y el navegador de las sesiones? Recomendado.
- **Bloque de menores:** A4 (ya verificado), A7, A11 y A12 se pueden resolver en `/tasks --fix` y `/plan --fix`
  junto con los demás, sin otra ronda propia.

## Aceptados
Ninguno todavía.
