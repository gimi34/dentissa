---
result: pass
round: 7
mode: delta
constitution_version: 1.3.0
date: 2026-11-03
spec_sha: 8989e97cff33
plan_sha: 1e843bb0e45a
tasks_sha: cd8dbac0f789
---

# Análisis · 041 Recuperar contraseña

## Resumen
Ronda delta sobre la ampliación de D20 a los correos (T043 y T044), a petición del usuario. Sin subagente (vía
ligera, decisión del usuario, 2026-11-03). El contenido de los correos, los criterios y el modelo de amenazas no
cambian. Resultado `pass`.
Conteo: CRÍTICA 0 · ALTA 0 · MEDIA 0 · BAJA 0

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 12 / 12 (sin cambios) |
| Amenazas con control y test | 14 / 14 (sin cambios) |
| Principios de la constitución evaluados | 10 / 10 (P2 y P9 tocados por el delta) |

## Lo comprobado (2026-11-03)
- P2: T043 escribe el test antes que T044, que depende de ella.
- P9 y D9: la hoja sigue con valores de tokens; lo exige `test_los_valores_de_los_correos_son_tokens`.
- TM12: el enlace y el logo siguen saliendo de `APP_URL`; T044 no toca las clases `Mailable`.
- T044 toca 2 archivos. Sin dependencias nuevas.

## Seguimiento de rondas anteriores
| ID | Severidad | Estado | Evidencia |
|---|---|---|---|
| A19, A22, A31–A37 | aceptados | siguen vigentes | Sus tareas no cambian de comportamiento |
| A23 | aceptado → spec | sigue vigente | Sin cambios en la spec |

## Hallazgos nuevos
Ninguno.

## Decisiones pendientes del usuario
Ninguna.

## Aceptados
Aceptados por el usuario el 2026-10-28 (ronda 1):
- **A19** → nota de T031, T032: fijar el orden de `SesionVigente` respecto a `ContextoActor` de la 020 en el grupo `web` y comprobar que los dos listeners de `Login` conviven; los tests de correlación de la 020 siguen en verde.
- **A22** → nota de T029: la contraseña nueva usa `Password::min(8)`, la misma regla que el registro y el perfil; lo comprueba `test_valida_cada_campo`.
- **A23** → spec: las incoherencias de redacción de la spec (presupuesto 5 frente a 7, «K1–K11» frente a K12) se anotan en su Historial en `/release`; las del plan van en `/plan 041 --fix A23`.

Aceptados por el usuario el 2026-10-28 (ronda 2):
- **A31** → nota de T042: medir también el estado «contraseña nueva en espera» con los criterios de K6 a 320, 390 y 1440 px; `/review` hace lo mismo.
- **A32** → nota de T010: el test del interruptor apagado lo apaga en el entorno antes de recrear la aplicación y lo restaura después, sin dejar la transacción en la conexión vieja; la suite completa pasa en cualquier orden.

Aceptados por el usuario el 2026-10-28 (ronda 3):
- **A33** → nota de T029, T011: si el `token` o la `cuenta` no tienen formato válido (faltan, array, cuenta no UUID), «Este enlace ya no sirve» sin redirigir a `contrasena.nueva`; un caso en `test_valida_cada_campo` lo comprueba.
- **A34** → nota de T028, T012: «pedir» conserva el correo solo si es texto de hasta 255 caracteres; `…_la_espera_con_entrada_malformada_no_falla` incluye `email[]` y un correo enorme.
- **A35** → nota de T017, T024: `…_acceso_css_sin_literales_fuera_de_root` excluye los descriptores del bloque `@font-face` (`font-family`, `font-weight`, `font-display`, `src`).

Aceptados (vía ligera, decisión del usuario del 2026-11-03 de no pedir otra vuelta de plan y tareas por lo BAJO):
- **A36** → nota de T001: la fila P9 del Constitution Check de `tasks.md` describe T001 como antes de D18; T001 incorpora solo lo que falta y anota los ocho tokens que ya están.
- **A37** → nota de T040: el mapa de `AGENTS.md` añade `acceso.js` a `public/js/` (spec 041, D19).
