---
result: pass
round: 2
mode: delta
constitution_version: 1.3.0
date: 2026-11-21
spec_sha: 41b59e1e7b85
plan_sha: 145f6a2e57ed
tasks_sha: 7f6717541fc4
---

# Análisis · 002 Autorregistro de pacientes

## Resumen
`pass`: las correcciones de la ronda 1 resuelven A1–A7. La de A2 trajo un efecto nuevo (N1): el trait solo leía los
tokens de `app.css`. Quedó corregido en T015 antes de cerrar la ronda, igual que dos erratas (N3, N4). N2 queda como
nota.
Delta con un subagente de contexto limpio (perfil `standard`, `sonnet`; diff de unos 34 KB). Cada hallazgo se
comprobó en su archivo (`ReglasCss::tokensDeColor()` lee `public_path('css/app.css')`, línea 102).
Conteo (nuevos): CRÍTICA 0 · ALTA 0 · MEDIA 1 (corregido) · BAJA 3 (2 corregidos, 1 aceptado)

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 8 / 8 (CA1–CA7 ya se cumplen, con caracterización o test existente; CA8 con test que falla antes) |
| Amenazas con control y test | 3 / 3 dentro; TM2 fuera → RS13.c, spec futura de confirmación del correo |
| Principios de la constitución evaluados | 10 / 10 |

## Seguimiento de rondas anteriores
| ID | Severidad | Estado | Evidencia |
|---|---|---|---|
| A1 | ALTA | resuelto | T011 fija los `id` y los `creado_en` de D1–D3 y anota tres roturas. Con la consulta de `asignarDentistaAutomatico`: sin el orden por `COUNT` gana D1 (el más antiguo de los activos) y falla el primero; sin `activo` gana D3 y fallan el primero y el tercero; sin el orden por antigüedad, el desempate cae en el UUID y gana el más joven (N2) |
| A2 | ALTA | resuelto con efecto nuevo (→ N1, corregido) | Plan P9 y D3; T015 y T021: regla `:focus-visible` con los tres tokens de foco (como `.enlace-inicio`, `auth.css:372`) y test de foco y contraste. `--red` #A32D2D sobre `--white` da unos 7:1 |
| A3 | ALTA | resuelto | La spec parte RS13 en RS13.b (dentro, CA8) y RS13.c (fuera), con su Decisión. `docs/security.md` crea RS13.c y el objetivo 2 de `docs/roadmap.md` lo cita. TM2 del plan lo referencia |
| A4 | ALTA | resuelto | Las Decisiones de la spec tienen la excepción P7 (`contradicción`, fuente `usuario`), coherente con el plan y las tareas |
| A5 | MEDIA | resuelto | CA8 dice que la casilla vuelve sin marcar. El plan y T014 comprueban todos los campos de texto, la fecha, el género y la ciudad |
| A6 | MEDIA | resuelto | Plan P10 nombra la deuda previa de `/registro` (descripción y canónica, `docs/seo.md`) |
| A7 | MEDIA | resuelto | CA2 incluye los dentistas inactivos y el caso sin dentistas activos; T011 tiene su test. `pacientes.dentista_id` admite nulos |
| A8–A16 | — | aceptados | sin cambios; ver «Aceptados» |

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| N1 | Inconsistencia | MEDIA | `tasks.md` T015; `tests/Feature/Concerns/ReglasCss.php:100-102` | `tokensDeColor()` siempre lee `css/app.css`. El contraste de `--red` sobre `--white` se mediría con los valores de `app.css`, no con los de `auth.css`: hoy coinciden, pero no detectaría si divergen. | **Corregido** en esta ronda (`/tasks --fix N1`): T015 añade a `tokensDeColor()` el parámetro `$archivo = 'css/app.css'`, compatible con sus usos, y el trait figura entre los archivos de T015 y en la Cobertura. |
| N2 | Ambigüedad | BAJA | `tasks.md` T011 | La rotura «sin el orden por antigüedad» depende del orden implícito del `GROUP BY` en MariaDB, que no está garantizado. | Aceptado → nota de T011. |
| N3 | Ambigüedad | BAJA | `tasks.md` T015 | Faltaba un separador entre dos nombres de test. | **Corregido** en esta ronda. |
| N4 | Ambigüedad | BAJA | `spec.md` Supuestos | La sección quedaba vacía tras mover el supuesto a CA2. | **Corregido** en esta ronda («Ninguno»). |

## Decisiones pendientes del usuario
Ninguna. Spec, plan y tareas están en `draft` y hay que aprobarlos otra vez.

## Aceptados
Aceptados para resolver en `/implement` el 2026-11-21 (ronda 1, salvo N2):
- **A8** → nota de T010: el test de la transacción aserta el 500 y que no queda ningún `usuarios` con ese correo (`assertDatabaseMissing`); el callback compara el SQL con ``insert into `pacientes` ``
- **A9** → nota de T010: un payload base compartido, con `password_confirmation` y `terminos`, y sin ciudad o con una de la lista; cada test aserta la clave de error concreta
- **A10** → nota de T014, T015: los enlaces y el `aria-*` se buscan dentro del grupo del campo del correo (xpath), no en toda la página
- **A11** → nota de T015: con el límite agotado (sexto intento), el error del correo es el del límite, sin los enlaces, y vuelven los datos
- **A12** → nota de T013: una contraseña de 8 caracteres se acepta (umbral exacto)
- **A13** → nota de T014: un correo con `<` vuelve escapado en el campo
- **A14** → nota de T011, T012: reutilizar los creadores de `tests/Feature/Concerns` o los del archivo; `google_registro` con `google_id`, `nombre`, `apellido`, `email` (nuevo, `@example.test`) y `foto_url` opcional, como `CiudadListaCerradaTest`
- **A15** → plan: línea de coordinación con la 054 y la 014, ya escrita en «Riesgos y mitigaciones»
- **A16** → nota de T095: una cuenta de prueba ya existente, nombrada sin escribir su correo en ningún documento
- **N2** → nota de T011: la rotura del orden por antigüedad se comprueba ejecutándola de verdad; si no muerde de forma estable, se añade un tercer dentista para que el resultado no dependa del orden de MariaDB
