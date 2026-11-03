---
result: pass
round: 4
mode: delta
constitution_version: 1.3.0
date: 2026-11-03
spec_sha: 8456cad72e93
plan_sha: 59b68afcd107
tasks_sha: d9621fa63c44
---

# Análisis · 041 Recuperar contraseña

## Resumen
Ronda delta sobre `/plan 041 --fix` y `/tasks 041 --fix` (D18, cc66cc9): la puesta al día tras
liberar la 020 (v0.12.0) y la 038 (v0.13.0). Sin subagente (vía ligera, decisión del usuario,
2026-11-03): releídas desde cero las secciones cambiadas del plan y las tareas, y comprobado contra
`main` lo que D18 da por hecho. La spec, la constitución, el diseño `c7` y el modelo de amenazas no
cambiaron. Resultado `pass`, con un hallazgo BAJO aceptado como nota.
Conteo: CRÍTICA 0 · ALTA 0 · MEDIA 0 · BAJA 1

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 12 / 12 (CA7 sin T006: lo cubren T005, T020, T022, T029, T031 y T032) |
| Amenazas con control y test | 14 / 14 (sin cambios) |
| Principios de la constitución evaluados | 10 / 10 (P1, P6, P8 y P9 tocados por el delta) |

## Lo comprobado contra `main` (2026-11-03)
- D1, D2: `aidd.py status` da la 020 `released` (v0.12.0); `Auditoria::registrar()` acepta
  `?Usuario $actor` y saca `actor_id` y `actor_rol` del actor pasado (`app/Auditoria/Auditoria.php:47-66`);
  el TM10 de la 020 lo recoge (plan de la 020, TM10 y A51). T002 lo vuelve a comprobar.
- D10: `public/fonts/roboto/roboto-latin-wght-normal.woff2` y `OFL.txt` están, con su `@font-face`
  en `errores.css` (020 D18, «la Roboto que ya verificó la 041»). T003 obsoleta con razón.
- D13: en `app.css`, `--green-texto` `#137A5A`, `--text-publico` 17px, `--leading-publico` 1.5,
  `--radius-pill` 999px, `--sombra-tarjeta-alta` `0 14px 32px rgba(114, 36, 62, 0.16)`,
  `--text-acceso-titulo` 28px, `--text-acceso` 14px y `--acceso-form-max` 480px: los valores de
  `c7` (`design.md` → Cambios a incorporar). Faltan `--font-acceso`, `--text-acceso-small`,
  `--duration-respuesta` y `--ease-clinico`, los que pide T001. `system.md` está en 1.10.0.
- D14: el `map` de marcos de la 038 está en `docker/nginx/seguridad/marcos.conf`
  (`$dentissa_frame_src`, incluido al principio de `dentissa.conf` y `dentissa.prod.conf`); T035 ya
  pide nombres de variable distintos. `AGENTS.md` dice «dos layouts», como recoge T040.
- D16: `config('clinica.contacto')` existe con `753 000 0000`; la spec no fija el número (K8 pide
  «el teléfono de la clínica»), así que el cambio de teléfono no contradice ningún criterio. T006
  obsoleta con razón; T022 lee de esa clave.
- Dependencias: ninguna tarea abierta depende ya de T003 ni de T006; sin ciclos (`validate` sin errores).

## Seguimiento de rondas anteriores
| ID | Severidad | Estado | Evidencia |
|---|---|---|---|
| A20 | BAJA (aceptado, nota de T003) | resuelto por obsolescencia | T003 obsoleta (D18): la 020 copió el mismo archivo con la verificación de `agent-security.md` §2 (020 D18). Sin nota pendiente |
| A19, A22, A31–A35 | aceptados | siguen vigentes | Sus tareas (T010–T012, T017, T024, T028, T029, T031, T032, T042) no cambiaron salvo las dependencias de T024 y T029 |
| A23 | aceptado → spec | sigue vigente | Sin cambios en la spec |

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A36 | Inconsistencia | BAJA | `tasks.md` → Constitution Check, fila P9 | Sigue diciendo que T001 «incorpora a `main` … los cinco tokens de la 038», que ya están (T001 y D13 corregidos) | Nota de T001 (ver «Aceptados») |

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
