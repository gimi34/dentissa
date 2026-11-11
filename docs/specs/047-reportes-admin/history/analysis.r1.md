---
result: fail
round: 1
mode: full
constitution_version: 1.3.0
date: 2026-11-11
spec_sha: cba486f01b94
plan_sha: 3c82ce91785b
tasks_sha: fedccf1e39fa
---

# Análisis · 047 Reportes de la clínica para la administración

## Resumen
`fail`. Análisis completo con un subagente independiente (perfil `critical`, `opus`). Ningún hallazgo
crítico; tres altos sobre cómo se cumple P2 y P9 (caracterizaciones comprobadas rompiendo una pieza
que no las protege, dos tests que pasarían antes del cambio y el contraste solo a mano), que el
usuario decide corregir. Los medios y bajos se corrigen con `--fix` o se aceptan como notas de tarea.
Conteo: CRÍTICA 0 · ALTA 3 · MEDIA 7 · BAJA 13

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 10 / 10 (CA3–CA5 solo en el dominio, A7) |
| Amenazas con control y test | 7 / 7 (TM3 sin la parte de peticiones repetidas, A21) |
| Principios de la constitución evaluados | 10 / 10 |

## Seguimiento de rondas anteriores
Primera ronda.

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A1 | Constitución (P2) | ALTA | `tasks.md` T010; `plan.md` P2 | T010 comprueba las cinco caracterizaciones quitando `role:admin`, que solo protege el 403 y su auditoría. El no-caché es de `PreventBackHistory` en el grupo `web` (`bootstrap/app.php:37-39`), el identificador de `IdentificadorPeticion` (`bootstrap/app.php:43`) y el menú de `layouts/app.blade.php` | `/tasks 047 --fix A1`: cada caracterización se rompe en la pieza que la protege y la nota la nombra |
| A2 | Constitución (P2) | ALTA | `tasks.md` T010, T013 | Un Gate sin definir ya deniega, así que `test_el_gate_deniega_a_quien_no_es_admin` pasa antes de T020; contra el placeholder, el test de CA10 también pasa | `/tasks 047 --fix A2`: el test del Gate incluye el caso permitido y el de CA10 un control positivo |
| A3 | Constitución (P9) | ALTA | `plan.md` P9 y Trazabilidad; `tasks.md` T014 | El contraste AA solo se verifica a mano, aunque `ReglasCss` ya trae `tokensDeColor()` y `contraste()` | Decisión del usuario; `/plan --fix` y `/tasks --fix` |
| A4 | Cobertura | MEDIA | `plan.md` Estrategia de pruebas; T011–T013 | `dentissa_test` se carga con el volcado, que trae citas (`database/dentissa.sql:74-76`); un periodo de prueba con citas del volcado descuadra las cifras y el estado vacío | `/plan --fix`, `/tasks --fix`: periodos de prueba sin citas previas, comprobado antes de sembrar |
| A5 | Inconsistencia | MEDIA | `spec.md` CA6; `design.md` Pantallas; `plan.md` | CA6 dice un mensaje por reporte; el diseño y el plan, uno solo | Decisión del usuario; `/specify --edit` |
| A6 | Seguridad (TM2, CA7) | MEDIA | `plan.md` Reglas del periodo; T012, T021 | La regla de 366 días lee las dos fechas; si se evalúa con una fecha inválida puede lanzar una excepción y dar 500 | `/plan --fix`, `/tasks --fix`: la regla no se evalúa con fechas inválidas; el proveedor cubre cada caso en cada campo |
| A7 | Cobertura | MEDIA | `plan.md` Trazabilidad CA3–CA5; T013 | Ningún test comprueba en el HTML las tasas destacadas, el formato «12,5 %», la fila de totales ni «Sin servicio» | `/plan --fix`, `/tasks --fix` |
| A8 | Inconsistencia | MEDIA | T027; `docs/design/capturar.mjs:314` | `--medir` exige que toda tabla visible de las pantallas incluidas sea `tabla-tarjetas`; el diseño solo convertía «por dentista» | `/design-spec --fix`, `/plan --fix`, `/tasks --fix` |
| A9 | Ambigüedad | MEDIA | `spec.md` CA8 y Diseño; `plan.md`; T024 | Sin definir el texto del nombre de la clínica ni el logo de la hoja; `logo-icon.png` no se ve sobre blanco | Decisión del usuario |
| A10 | Ambigüedad | MEDIA | `spec.md` K1; `design.md` | K1 no da altura de pantalla y a 320 px el filtro puede empujar el total fuera de la primera pantalla | Decisión del usuario |
| A11 | Inconsistencia | BAJA | `plan.md` D3 | El filtro de auditoría usa la vuelta por defecto; lo común es pintar la página sin resultados con errores en la sesión | `/plan --fix`, `/tasks --fix` (T023) |
| A12 | Inconsistencia | BAJA | `plan.md` TM7 | El no-caché es del grupo `web`, no del grupo del admin | `/plan --fix` |
| A13 | Inconsistencia | BAJA | `plan.md` Cambios por módulo | El método del trait es `cita()`, no `crearCita`, y no acepta servicio ni fecha de creación | `/plan --fix`, `/tasks --fix` (T011) |
| A14 | Inconsistencia | BAJA | `plan.md` Impacto en arquitectura; T091 | «15 controladores» no es la cuenta real | aceptado |
| A15 | Seguridad | BAJA | `plan.md` Impacto en arquitectura; T091 | Falta la fila de «Superficie de ataque» de `docs/security.md` para `GET /admin/reportes` | `/tasks --fix` (T091) |
| A16 | Ambigüedad | BAJA | `spec.md` CA7, CA4, CA5 | No dice si los 366 días incluyen los extremos, dónde va «Sin servicio» ni qué es «por nombre» | `/specify --edit` |
| A17 | Constitución (P4) | BAJA | `plan.md` P4 | Expresión cruda constante `COUNT(*)`: lectura razonable de P4, pero `semgrep` puede marcarla | aceptado |
| A18 | Conflicto | BAJA | `plan.md`; 014 y 040 | La 014 y la 040 tocarán `AppServiceProvider`, `security.md`, `architecture.md` y `system.md` | `/plan --fix` (Riesgos) |
| A19 | Inconsistencia | BAJA | `spec.md` `extends: []` | Correcto, pero la 012 dejará de decir la verdad sobre el placeholder | aceptado |
| A20 | Inconsistencia | BAJA | `spec.md` K2; `plan.md` | El objetivo de toque no dice ancho en la spec y el plan lo limita a menos de 900 px | `/plan --fix` |
| A21 | Amenazas | BAJA | `plan.md` TM3 | «Peticiones repetidas» sin control propio ni residuo aceptado | `/plan --fix` |
| A22 | Cobertura | BAJA | CA2, CA9; T010, T013 | Sin test del formulario `GET` con `desde` y `hasta`, ni del 403 con un periodo en la dirección | `/tasks --fix` |
| A23 | Cobertura de riesgos | BAJA | `plan.md` D7, Observabilidad | Riesgos de otros documentos mencionados sin identificador | aceptado |

## Decisiones pendientes del usuario
Ninguna. Respondidas el 2026-11-11:
- A5: un solo mensaje de estado vacío para toda la pantalla (se corrige CA6).
- A9: la cabecera impresa lleva solo texto, «Melissa López N. · Odontología integral», sin imagen.
- A10: en el móvil, la frase del periodo y el total van antes del selector; se mide a 320×568 px.
- A3: el contraste se verifica con un test automático.

## Aceptados
Aceptados por el usuario el 2026-11-11 (recomendación del análisis, sin objeción):
- **A14** → nota de T091: contar los controladores al editar `AGENTS.md`, no copiar la cifra del plan.
- **A17** → nota de T028: revisar la salida de `semgrep` sobre `ReporteCitas`; la expresión `COUNT(*)` es constante y sin entrada del usuario; ninguna supresión sin motivo.
- **A19** → nota de T098: añadir una línea al Historial de la 012 (el placeholder de «Reportes» ya no existe), sin añadirla a `extends`.
- **A23** → plan: los riesgos de otros documentos no se citan por identificador porque la spec no los atiende (el validador exige declarar sus correcciones en la spec); el plan los describe y remite a su documento.
