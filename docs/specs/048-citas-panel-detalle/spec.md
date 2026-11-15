---
id: 048
slug: citas-panel-detalle
status: implemented
created: 2026-11-14
extends: [012, 034]
---

# 048 · Abrir las citas del panel de inicio

## Problema
El panel de inicio del personal enseña las citas de hoy, pero en el de la administración y en el de
la asistente no se pueden abrir: para ver el detalle de una cita hay que ir a «Citas», buscarla y
abrirla desde ahí. En el panel de la dentista solo se abre con un botón pequeño con un ojo, y en el
panel del paciente las citas del «Historial» tampoco se abren. Quien atiende la recepción mira el
panel todo el día y pierde tiempo dando ese rodeo para algo tan frecuente como confirmar o
consultar la cita de alguien que acaba de llegar.

## Historias de usuario
- Como administración o asistente, quiero pulsar una cita de hoy en el panel de inicio y llegar a su detalle, para atenderla sin ir antes a «Citas».
- Como dentista, quiero pulsar cualquier parte de una cita de mi agenda de hoy para abrirla, sin tener que atinar al botón del ojo.
- Como paciente, quiero abrir una cita de mi historial desde el panel, para ver cómo quedó sin ir a «Mis citas».

## Criterios de aceptación
- [x] CA1 · Dado un administrador en su panel con citas hoy, cuando pulsa en cualquier parte de la fila de una cita de «Citas de hoy» (en el móvil, de la tarjeta), entonces llega al detalle de esa cita, el mismo que abre desde «Citas».
- [x] CA2 · Dada una asistente en su panel con citas hoy, cuando pulsa en cualquier parte de la fila de una cita de «Agenda de hoy» (en el móvil, de la tarjeta), entonces llega al detalle de esa cita.
- [x] CA3 · Dada una dentista en su panel con citas hoy, cuando pulsa en cualquier parte de una cita de «Agenda de hoy» (hora, nombre, servicio o el botón «Ver detalle»), entonces llega al detalle de esa cita; la tarjeta «Próximo paciente» sigue abriendo la misma cita, como hoy.
- [x] CA4 · Dado un paciente en su panel con citas pasadas, cuando pulsa en cualquier parte de una fila del «Historial» (en el móvil, de la tarjeta), entonces llega al detalle de esa cita, el mismo que abre desde «Mis citas»; «Tu próxima cita» sigue con su botón «Ver detalles», «Reprogramar» y «Cancelar» como hoy.
- [x] CA5 · Dada cualquiera de esas listas, cuando se recorre con el teclado, entonces cada cita es una sola parada de foco, con el foco visible alrededor de toda la fila o tarjeta, y un lector de pantalla la anuncia como enlace con la hora (o la fecha, en el historial) y el nombre del paciente (en el historial, el servicio); Intro la abre.
- [x] CA6 · Dada una fila o tarjeta de cita, cuando el puntero pasa por encima, entonces el cursor es el de un enlace y, en el escritorio, la fila cambia de fondo para indicar que se puede abrir; en el móvil (por debajo de 900 px) la tarjeta conserva su fondo blanco, como decidió la 034 (A57); el resto del panel no cambia.
- [x] CA7 · Dado un panel sin citas hoy (o un paciente sin historial), cuando se abre, entonces se ve el mismo estado vacío de hoy, sin nada que pulsar de más.
- [x] CA8 (abuso) · Como usuario con sesión, cambio a mano la dirección del detalle al que lleva una cita del panel por la de una cita que no puedo ver (de otro paciente, o de un dentista que no es mío como asistente o dentista) → la respuesta es la misma de hoy para esa cita (no encontrada o acceso denegado) y no muestra nada de ella.
- [x] CA9 · Dado el panel de cada rol en el escritorio y en el móvil (320, 390 y 1440 px), cuando se compara con el de antes de la 048, entonces solo cambian el aspecto al pasar el puntero y el foco de las citas; ni las columnas ni el contenido de cada cita cambian, y en el móvil cada cita sigue siendo una tarjeta sin desplazamiento lateral.

## Fuera de alcance
- El calendario y la lista compacta del apartado «Citas» (spec 049).
- Acciones nuevas en el panel (confirmar, completar o cancelar desde la propia fila): el detalle ya las tiene.
- Volver del detalle al panel con un enlace propio: el detalle conserva sus migas hacia «Citas» (spec 046); al panel se vuelve con «Inicio» del menú o con el botón Atrás del navegador.
- Cambiar qué citas muestra cada panel o cuántas (spec 012).
- Las cifras del panel («Citas hoy», «Próxima cita»…): no se vuelven enlaces.
- «Mis doctores» de la asistente y las demás listas del panel que no son citas.

## Seguridad y privacidad
- Datos sensibles involucrados: los de la cita que ya muestra el panel (nombre y teléfono del paciente, servicio, estado), según `docs/security.md`; el detalle muestra lo mismo que hoy desde «Citas».
- Quién puede hacer qué: no cambia. Cada rol abre solo las citas que hoy puede abrir desde su apartado «Citas»; la 048 solo añade el camino desde el panel.
- Casos de abuso:
  - Como usuario con sesión, cambio el identificador de la cita en la dirección a la que lleva el panel → se responde como hoy para esa cita (CA8).

## Auditoría
No aplica: la 048 no añade accesos, permisos ni acciones; abrir el detalle de una cita ya existe y
sus denegaciones se registran como hoy (spec 020).

## Diseño
Quien está en recepción tiene que ver de un vistazo que las citas se pueden abrir y abrirlas con un
solo toque, sin que el panel cambie de aspecto ni gane ruido. Sistema:
[docs/design/system.md](../../design/system.md).

Pantallas que toca: el panel de inicio de la administración («Citas de hoy»), de la asistente
(«Agenda de hoy»), de la dentista («Agenda de hoy», en línea de tiempo) y del paciente
(«Historial»), en escritorio y en móvil. Qué debe diseñarse: el aspecto de la fila o tarjeta al
pasar el puntero y con el foco, con los componentes que ya existen.

Sin ciclo de candidatos de `/design-spec`: `/plan` usa los componentes del sistema y el usuario lo
valida en pantalla durante `/implement`. Acta: [design.md](design.md).

## Criterios de diseño
Modo: operar · sin ciclo de candidatos · la verificación es la del usuario en pantalla y los tests de `/implement`.

| ID | Qué se mide | Objetivo | Sale de |
|---|---|---|---|
| K1 | Zona que abre la cita | toda la fila o tarjeta; en el móvil (320 y 390 px), objetivo de toque ≥ 44 px de alto; en el escritorio, la fila entera con su altura de hoy (el sistema no aplica el toque mínimo en el escritorio, D8 de la 033) | CA1–CA4 |
| K2 | Foco de cada cita | una sola parada por cita, foco visible con contraste ≥ 3:1 alrededor de la fila o tarjeta | CA5 |
| K3 | Nombre accesible del enlace | hora (o fecha) y paciente (o servicio) | CA5 |
| K4 | Paneles a 320, 390 y 1440 px | sin desplazamiento lateral; columnas y contenido iguales a antes | CA9 |

Puertas: las de `design-spec/references/criterios.md` §2 que apliquen (contraste AA, foco visible, toque ≥ 44 px, sin desbordes a 320 px).

## Requisitos no funcionales
- Accesibilidad (WCAG 2.1 AA, constitución P9): enlaces con nombre accesible, foco visible y contraste AA a 320, 390 y 1440 px; objetivo de toque ≥ 44 px en el móvil (320 y 390 px; en el escritorio la zona es la fila entera); sin enlaces anidados ni dos paradas de foco por cita.
- Funciona sin JavaScript: pulsar la cita la abre igual.
- El panel tarda en abrirse lo mismo que hoy: la 048 no añade consultas.

## Preguntas abiertas
Ninguna.

## Decisiones
| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-11-14 | brecha | ¿Las citas del panel y el calendario de «Citas» van en una spec o en dos? | Dos: esta (048), que sale primero, y la 049 con el calendario y la lista compacta | usuario (2026-11-14) |
| 2026-11-14 | supuesto | ¿Qué parte de la cita se pulsa? | Toda la fila o tarjeta, con un único enlace por cita | razonable (petición del usuario: «pulsando directamente en la cita») |
| 2026-11-14 | supuesto | ¿Se incluye el «Historial» del panel del paciente? | Sí: es la misma carencia en otro panel; «Tu próxima cita» ya tiene «Ver detalles» | razonable |
| 2026-11-14 | supuesto | ¿Qué pasa con el botón del ojo de la agenda de la dentista? | La cita entera se abre; el ojo se queda como señal visual sin ser una segunda parada de foco | razonable (CA5) |
| 2026-11-14 | implícita | Adónde vuelve el detalle abierto desde el panel | Sus migas siguen el camino fijo hacia «Citas»; no se recuerda la pantalla de origen | `docs/specs/046-navegacion-interna/spec.md` (Fuera de alcance) |
| 2026-11-14 | implícita | Permisos sobre el detalle de cada cita | No cambian: los de cada rol en su apartado «Citas» | `docs/security.md` (modelo de autorización) |
| 2026-11-14 | implícita | Auditoría | No aplica: no hay accesos, permisos ni acciones nuevas; abrir un detalle ya existe | `docs/constitution.md` P7, `docs/observability.md` |
| 2026-11-14 | diseño | Ciclo de candidatos | Sin exploración; acta en [design.md](design.md) | usuario (aprobó la spec con esa propuesta, 2026-11-14) |
| 2026-11-14 | contradicción | El fondo al pasar el puntero en la tarjeta del móvil revertía A57 de la 034 (`--p50` es el fondo de la lista) (/analyze A1) | Se mantiene A57: en el móvil la tarjeta no cambia de fondo; el cursor y el foco sí | usuario (2026-11-14) |
| 2026-11-14 | contradicción | 44 px de alto en el escritorio chocaba con CA9 y con el sistema, que no aplica el toque mínimo en el escritorio (/analyze A4) | 44 px solo en el móvil; en el escritorio la zona pulsable es la fila entera | usuario (2026-11-14) |
| 2026-11-14 | brecha | Con la fila entera como enlace no se puede seleccionar el teléfono ni el nombre (/analyze A14) | Se acepta: se copian desde el detalle de la cita | usuario (2026-11-14) |
| 2026-11-14 | implícita | Descubribilidad | No aplica: los paneles son privados y no se indexan | `docs/seo.md` |

## Notas para /plan
- Paneles: `resources/views/dashboard/admin.blade.php` y `asistente.blade.php` (tabla `tabla-tarjetas` sin enlace), `dentista.blade.php` (línea de tiempo con `btn-icono` «Ver detalle»; «Próximo paciente» ya enlaza) y `cliente.blade.php` («Historial», tabla sin enlace).
- Detalle: `admin.citas.show`, `asistente.citas.show`, `dentista.citas.show`, `cliente.citas.show`.
- Toca criterios de la 012 (CA1–CA4, inferida) y de la 034 (CA1 y CA2, tarjetas del móvil): solo añade cómo se abren las citas, no lo que muestran.
- La 046 (otra sesión, sin liberar) toca `citas/show` y `layouts/app`, no los paneles.

## Historial
| Fecha | Cambio | Motivo |
|---|---|---|
| 2026-11-14 | CA6 (sin fondo al pasar el puntero en la tarjeta del móvil), K1 y RNF (44 px solo en el móvil), Decisiones (A1, A4, A14); vuelve a `draft` | `/analyze` r1: A1, A4, A14 |
| 2026-11-14 | Implementada (T001, T010–T014, T019–T024, T090–T092); suite 1011 en verde; verificación manual del usuario | /implement 048 |
