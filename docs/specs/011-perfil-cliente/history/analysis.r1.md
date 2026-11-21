---
result: fail
round: 1
mode: full
constitution_version: 1.3.0
date: 2026-11-20
spec_sha: d4621dc75f02
plan_sha: a73af1ee30f3
tasks_sha: 0ddd85ac5470
---

# Análisis · 011 Perfil y contraseña del paciente

## Resumen
`fail`: los tests de CA6 descritos en el plan y en T014–T016 no pueden validar T021 tal como están escritos (A1, A2),
y la spec no declara los riesgos que corrige (A3). El diseño es correcto: lo que el plan da por hecho del código se
cumple (rutas, `SesionVigente`, listener de `Login`, `Auth::attempt` sin `remember`, registro central de denegaciones,
`insert` de la ficha y límite de intentos).
Análisis independiente con un subagente de contexto limpio (perfil `critical`, `opus`); cada hallazgo se comprobó
después en su archivo.
Conteo: CRÍTICA 0 · ALTA 2 · MEDIA 5 · BAJA 3

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 7 / 7 (CA1–CA5 «ya se cumple» con test de caracterización o existente; CA6 condicionado a A1 y A2) |
| Amenazas con control y test | 5 / 5 nominalmente (los tests de TM2 y TM3 no discriminan: A1, A2); falta una amenaza (A4) |
| Principios de la constitución evaluados | 10 / 10 |

## Seguimiento de rondas anteriores
Primera ronda.

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A1 | Inconsistencia (código) | ALTA | `plan.md` Estrategia de pruebas (CA6); `tasks.md` T014, T015, T016 | La segunda sesión se simula solo con `withSession(['version_sesion' => <anterior>])`. En los tests el guard y el almacén de sesión son compartidos: el `Usuario` en memoria del guard conserva la versión antigua, `SesionVigente` compara con él (`SesionVigente.php`, `$usuario->version_sesion`) y T021 sube la versión con el query builder, así que la «otra sesión» no se cierra y T014 no puede pasar ni con T021. En T015, la petición siguiente vuelve a escribir en la sesión la versión del modelo en memoria, y la rotura «quitar el `put`» de T021 no pone nada en rojo. `SesionVigenteTest` (041) resuelve esto con dos sesiones reales (`POST /login`, guardar los datos de la sesión y `forgetGuards()` entre medias). | `/plan 011 --fix A1` (Estrategia de pruebas) y `/tasks 011 --fix A1` (T014–T016, rotura de T021): los tests de CA6 reproducen dos sesiones reales. Se cumple cuando T014 falla sin T021 y pasa con él, T014 falla al quitar la subida de la versión y T015 falla al quitar el `put` de la versión en la sesión. |
| A2 | Inconsistencia (factibilidad de TM3) | ALTA | `tasks.md` T015 y rotura «quitar el `regenerate()`» de T021; `plan.md` Estrategia, TM3 | Sin la cookie de sesión en la petición, `StartSession` asigna un identificador nuevo en cada petición del test (`Store::setId` con un id vacío). «Su identificador es distinto del de antes» pasa hoy y seguiría pasando sin `regenerate()`: el motivo de fallo de T015 es falso en el arnés y la rotura de TM3 no se observa. | `/plan 011 --fix A2` y `/tasks 011 --fix A2` (T015, T021): el test de TM3 parte de un identificador conocido enviado en la cookie de sesión y comprueba que la respuesta trae otro. Se cumple cuando está en rojo sin `regenerate()` y en verde con él. |
| A3 | Cobertura de riesgos | MEDIA | `spec.md` (sin «Cobertura de riesgos»); `plan.md` Impacto en arquitectura; `docs/security.md` RS2, RS9 | La 011 es la spec de evidencia de RS2 y RS9, pero ni la spec ni el plan declaran sus correcciones. El plan dice que RS2 queda «solo en parte» sin separar RS2.a (en parte: `PUT /cliente/perfil` dentro; `GET` y el resto del grupo → 015) de RS2.b (no crear ficha a quien no es cliente: dentro del todo, CA7). RS9.a queda dentro (CA6). RS9.b ya está mitigada (041), con el residuo de Google (A4). Las deudas de la excepción P7 tampoco se nombran (OB1.e → 022, OB1.f → 021, OB1.b, A5). | `/specify --edit 011` (decisión del usuario, 2026-11-20): sección «Cobertura de riesgos» con RS2.a (en parte), RS2.b (dentro, CA7), RS9.a (dentro, CA6) y RS9.b (fuera: mitigada por la 041; residuo → 018), y «parcialmente» en el Problema. Después `/plan 011 --fix A3` en Impacto. Se cumple cuando `aidd.py validate` pasa con la sección. |
| A4 | Seguridad | MEDIA | `plan.md` D6 (supuesto), Modelo de amenazas; `docs/security.md` RS9.b (residuo), RS5.c | Con una sesión robada de una cuenta solo de Google, quien la tiene crea una contraseña sin dar la actual (CA4) y, por D6, cierra las sesiones del dueño. Ningún `TM#` lo trata y D6 era un supuesto. | Decisión del usuario (2026-11-20): se mantiene D6 y el caso se registra como amenaza fuera de alcance con destino la spec 018 (vinculación segura con Google, RS5.c). `/plan 011 --fix A4`: D6 pasa a decisión del usuario y una fila `TM6` con «fuera → 018» en el Modelo de amenazas. |
| A5 | Constitución (P7) | MEDIA | `plan.md` Constitution Check P7, Observabilidad, D7; `tasks.md` Constitution Check P7 | P7 pide auditar los cierres de sesión. La 011 provoca cierres forzados nuevos de las otras sesiones, que no dejan evento, y la excepción aceptada solo nombra el cambio de contraseña y el guardado del perfil. | Decisión del usuario (2026-11-20): la excepción P7 se amplía a los cierres de sesión forzados, con la auditoría de entradas y salidas pendiente (OB1.b), como los de la recuperación (041). `/plan 011 --fix A5` (P7, Observabilidad, D7) y `/tasks 011 --fix A5` (Constitution Check). |
| A6 | Constitución (P4) | BAJA | `plan.md` P4; `tasks.md` T021, T022 | P4 prohíbe concatenar entrada del usuario en SQL o en `DB::raw`. Subir la versión no concatena nada, pero «calculado con la columna» suele escribirse con `DB::raw`, y semgrep no distingue un texto fijo. La 041 lo hace sin `DB::raw` (`version_sesion` leída + 1). | Aceptado → nota de T021 y T022. |
| A7 | Duplicación y conflicto | MEDIA | `plan.md` Impacto en arquitectura; `docs/specs/README.md` (016 sesiones-seguras); `014/spec.md` Fuera de alcance; `docs/roadmap.md` | La 011 absorbe trabajo asignado a la 016 (cierre de sesiones al cambiar la contraseña) y a la 015 (`role:cliente` en `PUT /cliente/perfil`). El plan solo nombra el roadmap y `security.md`. Sin conflicto de código con la 014, la 052 ni la 054. | Aceptado → nota de T091. |
| A8 | Cobertura (TM5) | BAJA | `plan.md` TM5, D5; `tasks.md` T012 | TM5 dice que «el inicio de sesión» nunca emite la cookie de recuerdo, pero T012 solo prueba `POST /login`; el registro (`Auth::attempt`) y Google (`Auth::login`) también inician sesión, sin `remember` hoy. | Aceptado → nota de T012. |
| A9 | Cobertura (CA3) | BAJA | `spec.md` CA3; `plan.md` Trazabilidad | Ningún test cubre la contraseña actual **omitida** («Ingresa tu contraseña actual.»): los existentes prueban la incorrecta y el límite. | Aceptado → nota de T016. |
| A10 | Inconsistencia (redacción) | BAJA | `spec.md` CA3, CA4, Evidencia | CA3 y CA4 siguen `[ ]` aunque están cubiertos por `ContrasenaDistintaTest`; las líneas de evidencia de `routes/web.php` están desfasadas. | Aceptado → spec: se corrige en el `/specify --edit` de A3. |

## Decisiones pendientes del usuario
Resueltas el 2026-11-20:
- A3: añadir «Cobertura de riesgos» a la spec con `/specify --edit` (recomendada).
- A4: mantener D6 y registrar la amenaza fuera de alcance → 018 (recomendada).
- A5: ampliar la excepción P7 a los cierres de sesión forzados (recomendada).
- A6–A10: aceptarlos como notas (recomendada).

## Aceptados
Aceptados por el usuario el 2026-11-20:
- **A6** → nota de T021, T022: sin ningún `DB::raw` nuevo (la versión nueva se calcula como en la 041); T022 lo comprueba con un grep del diff
- **A7** → nota de T091: lista todos los documentos que cambian de destino (README de specs, Fuera de alcance de la 014, `docs/roadmap.md`, `docs/security.md`) para que `/release` no deje ninguna referencia a la 016 ni a la 015 para lo que resuelve la 011
- **A8** → nota de T012: el test cubre también el registro; TM5 se acota a los caminos con test, y si el arnés de Google lo permite, también ese
- **A9** → nota de T016: añadir el rechazo sin `password_actual`, con su mensaje y la contraseña sin cambiar (caracteriza CA3; en rojo al quitar la regla)
- **A10** → spec: CA3 y CA4 `[x]` y evidencia de `routes/web.php` actualizada, en el `/specify --edit` de A3
