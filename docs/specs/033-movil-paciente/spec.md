---
id: 033
slug: movil-paciente
status: released
released: v0.7.0 (2026-10-12)
created: 2026-10-11
extends: []
---

# 033 · Móvil para el paciente

## Problema
La app no se adapta a pantallas pequeñas. En un móvil de 390 px de ancho, la barra lateral fija
ocupa casi dos tercios de la pantalla y la página se desplaza de lado: el panel del paciente mide
892 px de ancho. Según `docs/design/system.md`, 54 de las 62 vistas se desbordan (DS8), y los
botones miden de 22 a 31 px de alto, por debajo de lo que se puede pulsar con el dedo (DS6). Lo
sufren sobre todo los pacientes, que son quienes más entran desde el móvil para ver, agendar,
reprogramar o cancelar sus citas. Sin esto, tienen que pellizcar y desplazarse de lado, y es fácil
pulsar lo que no querían.

Esta spec atiende DS8 **parcialmente**: el menú común a todos los roles y las 7 pantallas del
paciente. Las pantallas del personal van en una spec siguiente. DS6 se atiende solo en esas mismas
pantallas.

## Historias de usuario
- Como paciente, quiero usar la app desde el móvil sin pellizcar ni desplazarme de lado, para ver,
  agendar, reprogramar o cancelar mis citas y editar mi perfil cómodamente.
- Como paciente, quiero pulsar botones y enlaces con el dedo sin equivocarme, para no cancelar o
  abrir lo que no quería.
- Como miembro del personal (administración, dentista o asistente), quiero poder moverme por las
  secciones de la app desde el móvil, aunque sus pantallas aún no estén adaptadas, para consultar
  algo cuando no estoy en el ordenador.
- Como persona que usa teclado o lector de pantalla, quiero que el menú del móvil se pueda abrir,
  recorrer y cerrar igual de bien que el del escritorio.
- Como responsable del proyecto, quiero que el escritorio siga igual, para que adaptar el móvil no
  rompa lo que ya funciona.

## Criterios de aceptación
- [x] CA1 · Dado un paciente en cualquiera de sus 7 pantallas, cuando la abre con cualquier ancho
  entre 320 y 1440 px, entonces la página no se desplaza en horizontal y todo su contenido y sus
  acciones se ven y se pueden usar sin zoom. Las 7 pantallas son: panel, mis citas (próximas y
  pasadas), detalle de la cita, reprogramar, agendar y perfil. Se mide a 320, 390 y 1440 px. Si una
  tabla de datos necesita más ancho, solo se desplaza esa tabla dentro de su propio recuadro, nunca
  la página.
- [x] CA2 · Dado cualquier rol en una pantalla estrecha (a 320 y 390 px), cuando navega, entonces el
  menú no ocupa espacio fijo en la pantalla ni tapa contenido o acciones. Todas las secciones de su
  rol, su nombre y «Cerrar sesión» están a dos toques como máximo desde cualquier pantalla, y se
  indica en qué sección está. Las secciones son las mismas que en el escritorio.
- [x] CA3 · Dado el menú en una pantalla estrecha, cuando se usa con teclado o con lector de
  pantalla, entonces cada control tiene un nombre que se anuncia y se anuncia si el menú está
  abierto o cerrado. El foco se ve en todo momento. Si el menú se despliega sobre la página, al
  abrirlo el foco entra en él, Escape lo cierra y, al cerrarlo, el foco vuelve al control que lo
  abrió.
- [x] CA4 · Dados los botones, los enlaces de acción, los campos, las casillas y los elementos del
  menú de las 7 pantallas del paciente y del menú común, cuando se miden en el navegador a 390 px,
  entonces cada uno tiene una zona de toque de al menos 44 × 44 px. Quedan fuera los enlaces dentro
  de un texto corrido.
- [x] CA5 · Dados los formularios de agendar, reprogramar y perfil, cuando se usan a 320 px,
  entonces:
  - cada etiqueta queda junto a su campo;
  - cada mensaje de error se ve junto al campo al que se refiere, sin desplazarse de lado;
  - los botones de enviar se alcanzan y se pueden pulsar;
  - los campos de fecha, hora, teléfono y correo siguen abriendo en el móvil el selector o el
    teclado que les corresponde.
- [x] CA6 · Dado un texto largo (un nombre o un apellido largos, el nombre de un servicio o un correo
  sin espacios), cuando aparece en las pantallas del paciente o en el menú a 320 px, entonces pasa a
  la línea siguiente o se acorta con el texto completo disponible, y nunca ensancha la página.
- [x] CA7 · Dado un móvil en horizontal (p. ej. 844 × 390 px), cuando el paciente usa sus pantallas
  y el menú, entonces funcionan igual que en vertical. Nada obliga a girar el móvil, y el menú
  abierto se puede recorrer entero aunque no quepa en el alto de la pantalla.
- [x] CA8 · Dado el escritorio a 1440 px, cuando se abren las 7 pantallas del paciente y el menú de
  cada rol, entonces muestran el mismo contenido, en el mismo orden y con las mismas acciones que
  antes de esta spec. Solo cambia el tamaño de lo que CA4 obliga a agrandar. Se compara con las
  capturas actuales a 1440 px.

## Fuera de alcance
- El contenido de las pantallas del personal (agenda, pacientes, expedientes, personal, servicios,
  horarios, usuarios, reportes): sigue desbordándose en el móvil y va en una spec siguiente. Aquí
  solo se adapta su menú (CA2, CA3).
- El acceso, el registro y las páginas de error: ya se adaptan (`docs/design/system.md`).
- Un rediseño visual: colores, tipografía y aspecto siguen siendo los del sistema de diseño.
- App nativa, instalación como app, modo sin conexión, notificaciones y modo oscuro.
- La deuda de diseño de las partes de estas pantallas que no se toquen (DS3, DS4, DS5, DS9–DS15) y
  el resto de DS6 y DS7 fuera de estas pantallas.
- Funciones nuevas: el paciente hace en el móvil lo mismo que ya hace en el escritorio.

## Seguridad y privacidad
No aplica: la spec cambia cómo se presentan las pantallas y la navegación, no qué datos se ven ni
quién puede hacer qué. No añade datos, permisos ni entradas nuevas. El menú de cada rol muestra las
mismas secciones que hoy (CA2), así que no expone opciones de otros roles.

## Auditoría
No aplica: no toca datos sensibles, autenticación ni permisos.

## Diseño
En el móvil tiene que sentirse la misma app, cómoda de usar con una mano: nada se sale por los
lados, todo se lee sin pellizcar y se pulsa sin miedo a equivocarse. En el escritorio no cambia
nada que se note (CA8).

Pantallas que toca: el menú común a todos los roles y las 7 pantallas del paciente: panel, mis citas
(próximas y pasadas), detalle, reprogramar, agendar y perfil. Sistema:
[docs/design/system.md](../../design/system.md).

La forma de navegar en el móvil la elige el usuario entre 2 o 3 opciones que propone `/plan` con
el sistema de diseño (decisión del usuario, 2026-10-11).

## Requisitos no funcionales
- WCAG 2.1 AA en lo que se toque (constitución P9), en particular 1.4.10 Reflow (sin desplazamiento
  horizontal desde 320 px), 1.3.4 Orientación, 2.4.7 Foco visible y 4.1.2 Nombre, función y valor
  en el menú.
- Zona de toque de al menos 44 × 44 px (CA4, DS6).
- Comprobado en el navegador a 320, 390 y 1440 px de ancho, y a 844 × 390 px en horizontal.

## Preguntas abiertas
Ninguna.

## Decisiones
| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-10-11 | brecha | ¿Spec propia o al tocar cada pantalla? | Spec propia, empezando por el paciente. Sustituye la decisión del 2026-10-02 («escritorio; que no se adapte a móvil es deuda») | usuario |
| 2026-10-11 | brecha | ¿Qué entra: solo el paciente, el paciente y el menú común, o toda la app? | El menú común a todos los roles y las 7 pantallas del paciente; el contenido del personal, en una spec siguiente | usuario |
| 2026-10-11 | contradicción | Los anchos de captura del proyecto son 390 y 1440 px, pero WCAG 2.1 AA (1.4.10 Reflow) pide 320 px | 320 px como ancho mínimo, que respeta la fuente de más rango; se mide también a 390 y 1440 | usuario; `docs/constitution.md` P9 |
| 2026-10-11 | brecha | ¿Menú con botón, barra inferior u otra forma de navegar? | La propone `/plan` con 2 o 3 opciones y el usuario elige | usuario |
| 2026-10-11 | brecha | ¿Se incluye el tamaño de toque (DS6) en estas pantallas? | Sí, 44 × 44 px en lo que se toque | usuario |
| 2026-10-11 | implícita | ¿Qué deuda de diseño se corrige en las pantallas tocadas? | Las partes que se modifiquen cumplen P9 (solo valores del sistema de diseño y WCAG AA); la deuda del resto de la pantalla sigue en `system.md`, como se hizo en la 028 (R21, bloque tocado) | `docs/constitution.md` P9; `docs/specs/028-reprogramar-citas/history/review.r2.md` R21 |
| 2026-10-11 | implícita | ¿Hay que adaptar el acceso, el registro y las páginas de error? | No: ya se adaptan | `docs/design/system.md` (DS8) |
| 2026-10-11 | supuesto | ¿Puede una tabla desplazarse de lado? | Solo dentro de su propio recuadro, nunca la página (excepción de WCAG 1.4.10 para contenido que necesita dos dimensiones) | WCAG 2.1, 1.4.10 |
| 2026-10-11 | supuesto | ¿Cuentan los enlaces dentro de un texto para el tamaño de toque? | No, como en la excepción «en línea» de WCAG | WCAG 2.1, 2.5.5 |
| 2026-10-11 | supuesto | ¿Se comprueba la orientación horizontal? | Sí, a 844 × 390 px (un móvil común girado) | `docs/constitution.md` P9 (WCAG 1.3.4) |

## Notas para /plan
- Generar 2 o 3 opciones de navegación para el móvil con el sistema de diseño (p. ej. menú
  desplegable con un botón o barra inferior) y que el usuario elija viendo las maquetas.
- Hoy `public/css/app.css` no tiene ninguna `@media`; la barra lateral mide 242 px (`:60-70`).
- Añadir 320 px a los anchos de captura de `.ai/project.yaml` (hoy [390, 1440]) y repetir las
  capturas de las pantallas tocadas con `docs/design/capturar.mjs`.
- DS6 propone un token `--touch-min`.
- Tras liberar, crear la spec siguiente para el contenido de las pantallas del personal (resto de
  DS8).

## Historial
| Fecha | Cambio | Motivo |
|---|---|---|
| 2026-10-16 | Comportamiento modificado por 034 en v0.8.0 (tarjetas en «Mis citas» y el panel, 033/R20; botón «Menú» con icono y logo en el menú, 033/R9 y R21) | /release |
