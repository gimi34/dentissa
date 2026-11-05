---
id: 043
slug: contrasena-distinta
status: approved
created: 2026-11-03
extends: [011, 041]
---

# 043 · La contraseña nueva tiene que ser distinta de la actual

## Problema
Hoy, al restablecer la contraseña con el enlace del correo o al cambiarla desde el perfil, se acepta
como «nueva» la misma contraseña que ya tiene la cuenta. Quien restablece porque cree que su
contraseña está comprometida termina con la misma, recibe el aviso de «tu contraseña cambió» y se
cierran sus sesiones sin que nada haya cambiado de verdad. Afecta a cualquier cuenta con contraseña
que use la recuperación y a los pacientes que la cambian desde su perfil.

Atiende el riesgo RS2 **parcialmente**: solo un paciente puede usar el cambio de contraseña del perfil; el
resto de las pantallas del paciente sigue igual (→ spec 015).

## Historias de usuario
- Como persona que restablece su contraseña desde el correo, quiero que se me avise si escribo la que ya tenía, para no quedarme con una contraseña que quería cambiar.
- Como paciente que cambia su contraseña desde el perfil, quiero que se me avise si la nueva es igual a la actual, para saber que no cambió nada.

## Criterios de aceptación
- [x] CA1 · Dado un enlace de restablecimiento vigente de una cuenta con contraseña, cuando la persona escribe como nueva, con la misma confirmación, exactamente la contraseña actual, entonces se rechaza con «Elige una contraseña distinta de la actual» junto al campo de la contraseña nueva, el foco va a ese campo, la contraseña no cambia, no se cierra ninguna sesión, no llega el aviso de contraseña cambiada y el enlace sigue sirviendo mientras esté vigente (reemplaza en parte 041 CA3 y amplía 041 CA4).
- [x] CA2 · Dado un paciente con contraseña en su perfil, cuando escribe su contraseña actual correcta y como nueva, con la misma confirmación, esa misma contraseña, entonces se rechaza con «Elige una contraseña distinta de la actual» junto al campo de la contraseña nueva y la contraseña no cambia (amplía 011 CA3).
- [x] CA3 · Dada una contraseña nueva que difiere de la actual en cualquiera de sus primeros 72 caracteres, también solo en una mayúscula o en un espacio, cuando se envía por el enlace o desde el perfil y cumple las demás reglas, entonces se acepta como hasta ahora.
- [x] CA4 · Dada una cuenta sin contraseña (creada con Google), cuando fija una por el enlace o la crea desde el perfil, entonces nunca aparece «Elige una contraseña distinta de la actual» y se acepta como hasta ahora (041 CA6 y 011 CA4 sin cambios).
- [x] CA5 · Dada una contraseña nueva demasiado corta o una confirmación que no coincide, cuando se envía, entonces se muestran los errores de siempre y no el de «distinta de la actual»: la comparación solo se hace con una contraseña que cumple las demás reglas.
- [x] CA6 (abuso) · Como alguien que usa una sesión ajena en el perfil, escribo una contraseña actual equivocada y como nueva una que sospecho que es la actual, para averiguarlo → solo sale el error de la contraseña actual, con el límite de 5 fallos por minuto de la 041; el mensaje «distinta de la actual» solo aparece después de escribir bien la actual.
- [x] CA8 (abuso) · Como alguien del personal (admin, dentista o asistente), envío el cambio de contraseña del perfil del paciente escribiendo su dirección → se rechaza como acceso no permitido y mi contraseña no cambia; un paciente sigue pudiendo cambiar la suya (reemplaza en parte 011 CA7).
- [x] CA7 (abuso) · Como quien tiene un enlace de restablecimiento ajeno, pruebo contraseñas como nueva para averiguar la actual por el mensaje → cada intento cuenta para el límite de 5 envíos por minuto por dirección de origen al fijar la contraseña (041 CA9) y, al superarlo, la pantalla pide esperar.

## Fuera de alcance
- No reutilizar contraseñas anteriores (un historial de las últimas N): solo se compara con la actual.
- Rechazar contraseñas parecidas a la actual, comunes o filtradas.
- Cambiar contraseñas de otras cuentas: el alta de personal y de pacientes, las credenciales generadas (spec 019) y el comando de rotación de credenciales no se tocan.
- Cerrar las demás sesiones al cambiar la contraseña desde el perfil, del objetivo 2 de `docs/roadmap.md`.
- Registrar en auditoría el cambio de contraseña desde el perfil: lo hará la spec 021.

## Seguridad y privacidad
- Datos sensibles involucrados: credenciales (contraseña actual y nueva), según `docs/security.md`. Ninguna se muestra, se guarda en la sesión ni se escribe en logs por esta comprobación.
- Quién puede hacer qué: quien tiene un enlace vigente fija la contraseña de esa cuenta; solo el paciente cambia la suya desde su perfil, después de acreditar la actual; el personal cambia la suya con la recuperación por correo.
- Casos de abuso:
  - Como alguien con una sesión ajena, uso el mensaje del perfil para comprobar si adivino la contraseña actual → no lo veo sin escribir antes la actual correcta (CA6).
  - Como quien tiene un enlace ajeno, uso el mensaje de la recuperación para adivinar la contraseña actual → se limita como cualquier envío (CA7).
  - Como alguien del personal, uso el cambio de contraseña del perfil del paciente → se rechaza (CA8).

## Cobertura de riesgos
| Corrección | Alcance | Criterios / motivo y destino |
|---|---|---|
| RS2.a — exigir el rol de paciente en todas sus pantallas | fuera, parcialmente atendida | Solo el cambio de contraseña del perfil lo exige (CA8); el resto de las pantallas del paciente → spec 015 (decisión del usuario, 2026-11-04, `/analyze` A1). |
| RS2.b — no crear ficha de paciente desde el perfil de quien no es paciente | fuera | no es del cambio de contraseña → spec 015 |

## Auditoría
No hay eventos nuevos. Un rechazo no cambia nada y no se registra como restablecimiento: el evento
`contrasena_restablecida` (041 CA12) solo queda cuando la contraseña cambia de verdad, y CA1 lo
comprueba. El cambio desde el perfil se registrará con la 021 (`docs/observability.md`).

## Diseño
No aplica: no hay pantallas nuevas ni recomposición. El mensaje sale junto al campo con el estilo de
error que ya usan la pantalla de contraseña nueva de la 041 y el perfil (sistema:
[docs/design/system.md](../../design/system.md)).

## Requisitos no funcionales
- Accesibilidad (WCAG 2.1 AA, constitución): el error queda asociado al campo y se anuncia como los demás errores de esas pantallas; el foco va al primer campo con error.

## Preguntas abiertas

## Decisiones
| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-11-03 | brecha | ¿Dónde aplica la regla? | En la recuperación por correo y en el perfil | usuario |
| 2026-11-03 | brecha | Texto del mensaje | «Elige una contraseña distinta de la actual» | usuario |
| 2026-11-03 | implícita | Cuentas de Google sin contraseña | No hay con qué comparar: se aceptan como hasta ahora | `docs/specs/041-recuperar-contrasena/spec.md` CA6, `docs/specs/011-perfil-cliente/spec.md` CA4 |
| 2026-11-03 | supuesto | ¿«Igual» distingue mayúsculas y espacios? | Sí: igual es exactamente la que deja entrar, como en el inicio de sesión | — |
| 2026-11-03 | supuesto | ¿El enlace sigue sirviendo tras el rechazo? | Sí, como en cualquier error de validación | `docs/specs/041-recuperar-contrasena/spec.md` CA4 |
| 2026-11-03 | supuesto | Orden de los errores | Primero las reglas de siempre; en el perfil, además, la contraseña actual correcta; la comparación va al final | — |
| 2026-11-03 | supuesto | Residuo de CA7: con el enlace se pueden probar hasta 5 contraseñas por minuto durante los 60 minutos del enlace | Se acepta sin límite propio: quien tiene el enlace ya controla el buzón y puede fijar cualquier contraseña; solo aprendería la anterior | — |
| 2026-11-04 | contradicción | La ruta del cambio de contraseña del perfil solo exige haber iniciado sesión y P3 pide el rol en toda ruta modificada (`docs/constitution.md` P3, 011 CA7) | Exigir el rol de paciente en esa ruta (CA8), como la 026 y la 028 | usuario (`/analyze` A1) |
| 2026-11-04 | supuesto | El cifrado de contraseñas solo distingue los primeros 72 caracteres | Dos contraseñas que solo difieren después del carácter 72 cuentan como iguales, igual que al entrar | `/analyze` A7 |
| 2026-11-03 | brecha | Tamaño y proceso | Spec pequeña, sin `/design-spec`, una sola ronda de `/review`; sale en v0.14.1 | usuario |

## Notas para /plan
- La regla de la 041 y la del perfil ya comprueban la contraseña actual en el servidor; la comparación tiene que ser contra la contraseña guardada de la cuenta del enlace o de la sesión, nunca contra un dato de la petición.

## Historial
| Fecha | Cambio | Motivo |
|---|---|---|
| 2026-11-03 | Spec creada | /specify |
| 2026-11-03 | Aprobada | usuario |
| 2026-11-04 | CA8 (rol de paciente en el cambio de contraseña del perfil), «Cobertura de riesgos» con RS2, CA3 con el límite de 72 caracteres | /analyze ronda 1: A1, A7, A11 |
| 2026-11-04 | Aprobada de nuevo, con spec, plan y tareas | usuario |
| 2026-11-05 | Implementada: CA1–CA8 con sus tests en verde | /implement |
