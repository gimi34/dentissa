---
result: pass
round: 2
mode: delta
constitution_version: 1.3.0
date: 2026-11-04
spec_sha: e4c915ad059a
plan_sha: ecae459f6173
tasks_sha: 111dcbe1dee4
---

# Análisis · 043 La contraseña nueva tiene que ser distinta de la actual

## Resumen
`pass`. Los once hallazgos de la ronda 1 ([history/analysis.r1.md](history/analysis.r1.md)) quedan
resueltos. A4 y A7 se resolvieron con efecto nuevo: A12 y A13. No hay hallazgos críticos ni altos.
Los cinco hallazgos nuevos son de precisión y el usuario los acepta como notas de tarea.

La ronda la hizo en modo delta un subagente de contexto limpio, porque el diff de `aidd.py changes`
ocupa 42 KB. Verifiqué cada hallazgo contra el archivo que cita.

Conteo: CRÍTICA 0 · ALTA 0 · MEDIA 1 · BAJA 4

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 8 / 8 |
| Amenazas con control y test | 5 / 5 |
| Principios de la constitución evaluados | 10 / 10 (P1, P2, P3, P4, P8 y P9 revisados en el delta) |

## Seguimiento de rondas anteriores
| ID | Severidad | Estado | Evidencia |
|---|---|---|---|
| A1 | CRÍTICA | resuelto | La spec gana CA8 y una fila en «Cobertura de riesgos» (RS2.a fuera, parcialmente atendida, como en la 026) y en «Decisiones», con fuente `usuario`. El plan gana D8, la fila de `routes/web.php`, TM5 y P3 con el caso permitido y el denegado. Las tareas ganan T024 (con la rotura de quitar `role:cliente`) y el test del personal en T011. `RoleMiddleware` responde con `abort(403)`. |
| A2 | ALTA | resuelto | TM3: el test usa el token de A con `cuenta` = B. La rotura, comparar antes de validar el enlace, cambia el resultado porque `restablecer` valida el enlace antes del validador. CA4: la tarea declara que `Hash::check` con `null` devuelve `false`, y la rotura que hace fallar el test es tratar la falta de contraseña como «igual». |
| A3 | MEDIA | resuelto | TM4: «sin `withInput()`», con la rotura `->withInput()` en T020 y T021 y el test en los dos flujos. Precisión en A16. |
| A4 | MEDIA | resuelto con efecto nuevo (→ A12) | T021 incluye las roturas de CA3, CA4 y CA5, y los datos de CA3 están fijados. Falta fijar los datos de CA5 en el perfil. |
| A5 | MEDIA | resuelto | `for` en las tres etiquetas en D6, T022, T011 y P9. |
| A6 | BAJA | resuelto | P4 coincide con `PerfilController::updatePassword`. |
| A7 | BAJA | resuelto con efecto nuevo (→ A13) | CA3 y D4 declaran el límite, con unidades distintas. |
| A8 | BAJA | resuelto | Está en D4, en la nota «Datos de prueba» de `tasks.md` y en T091 (roadmap). |
| A9 | BAJA | resuelto | T010: test de caracterización, con 5 rechazos por contraseña corta; la rotura del `throttle` está en T020. |
| A10 | BAJA | resuelto | La columna «Riesgo» del plan y T011 exigen un solo `autofocus`. |
| A11 | BAJA | resuelto | RS2 en «Cobertura de riesgos» y «parcialmente» en el Problema; nada lo declara mitigado y T091 lo deja `pendiente`. |

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A12 | Cobertura (P2) | MEDIA | `tasks.md` T011 (`…_los_errores_de_siempre_van_antes_en_el_perfil`), T021 | Sin datos fijados, la rotura «comparar antes de `validate()`» no puede hacer fallar el test de CA5 del perfil. | Ese test usa una nueva igual a la actual con una confirmación distinta. Con la rotura de T021 debe salir «distinta» en lugar del error de confirmación. → nota de T011 y T021. |
| A13 | Inconsistencia | BAJA | `spec.md` CA3 («72 caracteres»); `plan.md` D4 («72 bytes») | bcrypt cuenta bytes; con caracteres multibyte, «72 caracteres» no es exacto. | El comentario del código y los tests hablan de 72 bytes. CA3 se lee así; los tests usan caracteres simples. → nota de T020. |
| A14 | Inconsistencia (011) | BAJA | `spec.md` CA8 («reemplaza en parte 011 CA7») | 011 CA7 trata `PUT /cliente/perfil`, que sigue abierto. CA8 cubre otra ruta. | El Historial de la 011 dice que CA8 de la 043 aplica el rol solo a `PUT /cliente/perfil/password` y que 011 CA7 sigue pendiente (RS2.a, RS2.b → spec 015). → nota de T098. |
| A15 | Inconsistencia | BAJA | `plan.md` Rollout («Compatibilidad: total») | El personal pasa de 302 a 403 en esa ruta. | En la verificación tras el despliegue, la compatibilidad es total para pacientes y el personal recibe 403. → nota de T097. |
| A16 | Ambigüedad | BAJA | `plan.md` P8, TM4; `tasks.md` T020 | En la recuperación no hay una respuesta nueva: el error sale por la redirección `->withErrors($validador)` que ya existe. | La rotura de TM4 en T020 es `->withInput()` en esa redirección, y el test comprueba el caso de «distinta». → nota de T020. |

## Decisiones pendientes del usuario
- Ninguna.

## Aceptados
Aceptados por el usuario el 2026-11-04:
- **A12** → nota de T011, T021: el test de CA5 del perfil usa una nueva igual a la actual con una confirmación distinta, y la rotura de T021 lo hace fallar.
- **A13** → nota de T020: el límite son 72 bytes; los tests usan caracteres simples.
- **A14** → nota de T098: el Historial de la 011 dice que solo `PUT /cliente/perfil/password` exige el rol y que 011 CA7 sigue pendiente (→ spec 015).
- **A15** → nota de T097: la compatibilidad es total para pacientes y el personal recibe 403 en esa ruta.
- **A16** → nota de T020: la rotura de TM4 es `->withInput()` en la redirección existente de `restablecer`.
- De la ronda 1: **A8** → roadmap (en T091) y **A11** → spec (hecho).
