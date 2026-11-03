---
result: pass
round: 5
mode: delta
constitution_version: 1.3.0
date: 2026-11-03
spec_sha: 8456cad72e93
plan_sha: a45387038603
tasks_sha: 0c5657c11092
---

# Análisis · 041 Recuperar contraseña

## Resumen
Ronda delta sobre `/design-spec 041 --fix`, `/plan 041 --fix` y `/tasks 041 --fix` (D19): el usuario
conserva el botón del ojo en el inicio de sesión. Sin subagente (vía ligera, decisión del usuario,
2026-11-03): releídas D19, T036 y T037, y comprobado contra el código lo que dan por hecho. La spec,
los criterios y el modelo de amenazas no cambian. Resultado `pass`, con un hallazgo BAJO aceptado como
nota.
Conteo: CRÍTICA 0 · ALTA 0 · MEDIA 0 · BAJA 1

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 12 / 12 (sin cambios) |
| Amenazas con control y test | 14 / 14 (sin cambios) |
| Principios de la constitución evaluados | 10 / 10 (P2, P9 y P10 tocados por el delta) |

## Lo comprobado (2026-11-03)
- TM7: el script solo se carga en el inicio de sesión. `nueva` y `caducado`, las de la URL del token,
  siguen sin JavaScript, y T036 lo exige con `test_sin_javascript`.
- P2: T036 escribe los tests antes que T037, que depende de ella.
- P9: los estilos van en `acceso.css`, cubiertos por los tests de tokens de T017, sin valores nuevos.
- P10: archivo propio y CSP vigente (`script-src 'self'`), sin recursos de otros sitios.
- K9 y `G-sin-js`: `c7/exito.html` remedido con el ojo, puertas 12/12 y KPIs 5/5. Sin JavaScript el botón
  no aparece y el contenido está completo.
- Sin dependencias nuevas. T037 toca 3 archivos.

## Seguimiento de rondas anteriores
| ID | Severidad | Estado | Evidencia |
|---|---|---|---|
| A19, A22, A31–A36 | aceptados | siguen vigentes | Sus tareas no cambiaron por D19 |
| A23 | aceptado → spec | sigue vigente | Sin cambios en la spec |

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A37 | Hueco | BAJA | `tasks.md` → T040 | El mapa de `AGENTS.md` lista los scripts de `public/js/` y T040 no menciona `acceso.js` | Nota de T040 (ver «Aceptados») |

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
