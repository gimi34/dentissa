---
id: 034
slug: movil-personal
status: draft
created: 2026-10-12
extends: [033]
---

# 034 · Móvil para el personal y listas en tarjetas

## Problema
La v0.7.0 (spec 033) adaptó el menú y las pantallas del paciente al móvil, pero en producción el
usuario vio que las listas siguen siendo tablas. En «Mis citas», la tabla mide 477 px en 356 px
visibles, y los botones «Ver», «Reprogramar» y «Cancelar» quedan escondidos a la derecha y
apilados. En el personal es peor:
- Las listas de citas, pacientes y servicios y las citas del panel de inicio no caben, y sus
  columnas se estrechan hasta partir palabras y números letra a letra.
- El detalle de una cita mantiene dos columnas y mide 583 px a 390 px de ancho.
- El filtro de citas ocupa media pantalla antes de llegar a las citas.
- Algunos elementos se salen de su sitio.

Además, el usuario prefiere que el botón del menú muestre solo el icono y que el menú lleve el
logo de la clínica en lugar del nombre en texto. Lo sufren la administración, las dentistas y las
asistentes cuando consultan la agenda desde el móvil, y los pacientes en sus citas.

Esta spec atiende DS8 **parcialmente**. Cubre las pantallas más usadas del personal y las listas del
paciente que quedaron como tablas. El resto de las pantallas del personal (formularios,
expedientes, ficha del paciente, personal, usuarios, reportes y horario) va en una spec siguiente.
DS6 se atiende en estas mismas pantallas.

## Historias de usuario
- Como paciente, quiero ver cada cita de mis listas con sus botones a la vista, para ver,
  reprogramar o cancelar sin desplazar la tabla de lado.
- Como administración, dentista o asistente, quiero consultar desde el móvil las citas de hoy, la
  lista de citas y su detalle, para atender la agenda cuando no estoy en el ordenador.
- Como personal de la clínica, quiero que la lista de pacientes del móvil muestre solo lo esencial
  (nombre, teléfono y alergias), para encontrar y llamar a un paciente rápido.
- Como personal de la clínica, quiero un filtro de citas compacto en el móvil, para llegar a las
  citas sin desplazarme por todo el formulario.
- Como administración, quiero ver y gestionar los servicios desde el móvil sin que se rompa la
  pantalla.
- Como cualquier persona que usa la app en el móvil, quiero un botón de menú discreto con el icono y
  ver el logo de la clínica en el menú, para que la app se vea más limpia y con la marca.
- Como responsable del proyecto, quiero que el escritorio siga igual, para no romper lo que ya
  funciona.

## Criterios de aceptación
Las «pantallas incluidas» son:
- **del paciente:** «Mis citas» (próximas y pasadas) y su panel de inicio;
- **del personal (admin, dentista y asistente):** el panel de inicio de cada rol, la lista de citas
  con su filtro, el detalle de una cita y la lista de pacientes;
- **de la administración:** la lista de servicios.

«Móvil» es cualquier ancho por debajo de 900 px, como en la 033.

- [ ] CA1 · Dado un paciente en «Mis citas» (próximas o pasadas) o en su panel de inicio, cuando lo
  abre en el móvil (de 320 a 899 px), entonces cada cita aparece como una tarjeta con su fecha (y su
  hora en «Mis citas»), servicio, dentista y estado. Cada dato lleva su etiqueta, salvo la cabecera
  (la fecha y hora) y el estado, que se entienden solos. Sus acciones («Ver», «Reprogramar» y
  «Cancelar», cuando correspondan) se ven dentro de la tarjeta sin desplazar nada de lado y sin que
  ninguna tape a otra.
- [ ] CA2 · Dado el personal en la lista de citas, o la administración o una asistente en las citas
  de hoy de su panel de inicio, cuando lo abre en el móvil, entonces cada cita aparece como una
  tarjeta con su hora y fecha (solo la hora en las citas de hoy del panel, como hoy), paciente,
  dentista, servicio y estado, y con sus acciones a la vista. Cada dato lleva su etiqueta, salvo la
  cabecera (la hora, o la hora y fecha) y el estado. La cita de hoy se sigue distinguiendo de las
  demás, y no solo por el color. La agenda de hoy del panel de la dentista conserva su línea de
  tiempo (no pasa a tarjetas): en el móvil, el estado y «Ver detalle» van debajo del nombre del
  paciente, cumple CA7 y CA8, y «Ver detalle» tiene nombre accesible.
- [ ] CA3 · Dado el personal en la lista de pacientes, cuando la abre en el móvil, entonces cada
  paciente aparece como una tarjeta con solo su nombre, su teléfono, sus alergias (con el aviso que
  ya tiene cuando las hay) y sus acciones. El correo, el doctor, la ciudad, el tipo de sangre y la
  fecha de registro no se muestran en el móvil, pero siguen en el escritorio y en la ficha del
  paciente.
- [ ] CA4 · Dada la administración en la lista de servicios, cuando la abre en el móvil, entonces
  cada servicio aparece como una tarjeta con su nombre, su duración, su estado y sus acciones.
- [ ] CA5 · Dado el personal en la lista de citas en el móvil, cuando la abre, entonces ve el
  buscador y los atajos «Hoy» y «Esta semana», y un botón «Filtros» que despliega y repliega las
  fechas, el dentista y el estado. Si hay filtros aplicados, el botón lo indica y, al desplegarlo,
  se ven con sus valores. Sin JavaScript, todos los filtros se ven desplegados.
- [ ] CA6 · Dado el personal en el detalle de una cita, cuando lo abre en el móvil, entonces la
  página va en una sola columna: los datos de la cita, cambiar estado, información y acciones,
  control interno e historial. No se desplaza de lado y ningún bloque se monta sobre otro.
- [ ] CA7 · Dado un texto en cualquiera de las pantallas incluidas o en las 7 del paciente de la 033
  (nombres, correos, servicios, cabeceras, números de teléfono), cuando se ve en el móvil, entonces
  ninguna palabra que cabe en su línea se parte letra a letra. Una palabra más larga que el ancho
  disponible pasa a la línea siguiente o se acorta con el texto completo disponible, y nunca
  ensancha la página (mantiene 033 CA6).
- [ ] CA8 · Dadas las pantallas incluidas, cuando se abren a 320, 390 y 844 × 390 px, entonces
  ninguna se desplaza en horizontal, ningún control queda tapado por otro ni se sale de su tarjeta,
  y los botones, enlaces de acción, campos y elementos que se pueden pulsar miden al menos
  44 × 44 px.
- [ ] CA9 · Dado cualquier rol en el móvil, cuando ve la barra superior, entonces el botón del menú
  muestra solo el icono de tres líneas, sin texto, mide al menos 44 × 44 px y los lectores de
  pantalla lo siguen anunciando como «Menú, botón, contraído» (cambia el aspecto del botón de
  033 CA2 y mantiene 033 CA3). Al abrirse el menú, el foco entra en la región «Menú» y los lectores
  la anuncian, sin que el foco vaya a nada tapado por el panel (033/R9). Al cruzar los 900 px, el
  foco solo cambia de sitio si estaba en el menú o en sus botones (033/R21).
- [ ] CA10 · Dado el menú del móvil abierto, cuando se ve su cabecera, entonces muestra el logo de la
  clínica en lugar del nombre en texto. El logo se distingue del fondo del panel (contraste de al
  menos 3:1, WCAG 1.4.11) y los lectores lo anuncian como «Melissa López N.».
- [ ] CA11 · Dadas las pantallas incluidas en el escritorio (1024 y 1440 px), cuando se abren,
  entonces se ven igual que antes: tablas con todas sus columnas, el filtro de citas completo y la
  barra lateral con su logo. La única diferencia es el borde de los campos del filtro de citas, que
  pasa a distinguirse del fondo con un contraste de al menos 3:1 (WCAG 1.4.11, decisión del
  usuario).

## Fuera de alcance
- El resto de las pantallas del personal: crear y editar cita, paciente o servicio, reprogramar
  como personal, expedientes, la ficha del paciente, personal, usuarios, reportes y horario. Van en
  una spec siguiente (resto de DS8).
- Cambiar qué datos se muestran o cómo se ordenan en el escritorio, añadir datos nuevos a las listas
  o nuevas opciones de filtro, paginación o búsqueda.
- Pantallas de acceso, registro y errores.
- Redibujar el logo o crear una identidad visual nueva: se usa el logo actual.
- Respetar la preferencia de reducir el movimiento fuera del menú (resto de DS10) y el resto de la deuda de
  diseño (DS1–DS5, DS7, DS9 y DS11–DS15).
- La deuda de la 033 en el roadmap (033/R6, R10–R19 y R22). Dentro: 033/R20 (CA1), y 033/R9 y R21
  (CA9, decisión del usuario en /plan, 2026-10-13).
- 033/R14 entra solo en parte: las comprobaciones de la 033 dejan de leer hasta el final de la hoja de
  estilos, porque la 034 añade su propio bloque detrás. Que fallen ante reglas repetidas que se
  pisan sigue en el roadmap.

## Seguridad y privacidad
- Datos sensibles involucrados: los mismos que ya muestran estas pantallas (nombre, teléfono y
  alergias del paciente, y datos de sus citas). No se añade ninguno: el móvil muestra menos.
- Quién puede hacer qué: sin cambios. Cada rol ve las mismas listas, con el mismo alcance que hoy.
  Hoy la lista de pacientes de la dentista y de la asistente no se limita a sus pacientes (riesgo de
  alcance abierto en `docs/security.md`, que corrige la spec 014): la 034 no cambia ese alcance ni lo amplía. Ocultar
  columnas en el móvil es presentación, no un permiso.
- Casos de abuso: no aplica. La spec solo cambia la presentación de datos que el rol ya puede ver:
  no añade entradas, acciones, permisos ni datos.

## Diseño
Quien la usa debe sentir que la app está hecha para el móvil. Cada cita, paciente o servicio cabe
en una tarjeta que se lee de un vistazo, las acciones están donde el dedo las espera y el filtro
no estorba. Todo con el mismo aspecto que el resto del sistema.

**Pantallas que toca:** las incluidas (ver Criterios), y el botón y la cabecera del menú del móvil
en todas.

**Componentes nuevos:** la tarjeta de lista en el móvil (cita, paciente y servicio) y el filtro
plegable. `/plan` genera sus opciones en HTML (como `design/opcion-a.html` de la 033) y obtiene la
versión del logo para fondo claro a partir del logo actual, sin redibujarlo.

Sistema: [docs/design/system.md](../../design/system.md).

## Requisitos no funcionales
- WCAG 2.1 AA en las pantallas incluidas:
  - 1.4.10, reflujo a 320 px;
  - 1.3.1, cada dato de la tarjeta con su etiqueta (salvo la cabecera y el estado), también para
    lectores de pantalla;
  - 1.4.11, el logo y el borde de los campos del filtro de citas, también en el escritorio (el borde
    de la tarjeta es decorativo: la separan su fondo y su espacio);
  - 2.4.7, foco visible;
  - 4.1.2, «Filtros» anuncia si está desplegado;
  - 1.4.1, la cita de hoy no se distingue solo por el color.
- Toque de al menos 44 × 44 px en los controles de las pantallas incluidas.
- Medido a 320, 390, 844 × 390 y 1440 px (los anchos del proyecto y de la 033), también sin JavaScript.
- Escritorio sin cambios salvo el borde de los campos del filtro (CA11), comprobado contra capturas
  de antes a 1024 y 1440 px.

## Preguntas abiertas
- Ninguna.

## Decisiones
| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-10-12 | brecha | ¿Qué pantallas del personal entran? | Las más usadas: el panel de inicio de los tres roles, la lista de citas con su filtro, el detalle de cita y la lista de pacientes de los tres roles, y la lista de servicios del admin. El resto, en una spec siguiente | usuario |
| 2026-10-12 | brecha | ¿Qué muestra la lista de pacientes en el móvil? | Solo nombre, teléfono, alergias y acciones | usuario |
| 2026-10-12 | brecha | ¿Cómo es el filtro de citas en el móvil? | Buscador y atajos «Hoy» y «Esta semana» a la vista; «Filtros» despliega fechas, dentista y estado | usuario |
| 2026-10-12 | diseño | Botón del menú y marca del menú del móvil | Botón solo con el icono de hamburguesa y el logo en lugar del nombre en texto | usuario |
| 2026-10-12 | brecha | Los tres logos son blancos y no se ven sobre el panel blanco | Versión para fondo claro obtenida del logo actual (sin redibujarlo); se mantiene el panel blanco de la 033 (la barra rosa no llega a 4,5:1, DS1) | usuario |
| 2026-10-12 | brecha | ¿Dividir la spec (11 criterios)? | Una sola spec: el menú es pequeño y comparte pantallas y pruebas | usuario |
| 2026-10-12 | cierre | ¿Hotfix o vuelta atrás de v0.7.0 por mis citas y el detalle del personal? | Mantener v0.7.0 y corregirlo en esta spec | usuario |
| 2026-10-12 | implícita | Punto de corte del móvil | Por debajo de 900 px, como en la 033 | docs/specs/033-movil-paciente/plan.md D6 |
| 2026-10-12 | implícita | Anchos de medida | 320, 390 y 1440 px, más 844 × 390 en horizontal | .ai/project.yaml → design.widths; 033 CA7 |
| 2026-10-12 | implícita | Solo tokens del sistema y WCAG 2.1 AA en pantallas modificadas | Se aplica a las tarjetas, el filtro y el logo | docs/constitution.md P9 |
| 2026-10-12 | supuesto | ¿Qué muestra la tarjeta de servicio? | Nombre, duración, estado y acciones (sin descripción ni categoría en el móvil) | /specify |
| 2026-10-12 | supuesto | ¿Qué muestra la tarjeta de las citas de hoy del panel del personal? | Lo mismo que la lista de citas (hora, paciente, dentista, servicio y estado), sin acciones si hoy no las tiene | /specify |
| 2026-10-12 | supuesto | Orden del detalle de cita del personal en una columna | Datos de la cita, cambiar estado, información y acciones, control interno e historial (hoy el HTML va en otro orden: datos, control interno, historial, cambiar estado e información; se reordena) | /specify |
| 2026-10-13 | cierre | ¿Se aprueba la spec, con sus tres supuestos? | Aprobada | usuario |
| 2026-10-13 | brecha | ¿Se incluyen 033/R9 y R21 al cambiar el botón del menú? (la spec pedía proponerlo en /plan) | Sí, dentro de CA9 | usuario |
| 2026-10-13 | brecha | ¿Llevan etiqueta la cabecera (hora o nombre) y el estado de la tarjeta? (/analyze A7) | No: se entienden solos, como en la opción A | usuario |
| 2026-10-13 | brecha | ¿Entra el panel de la dentista (línea de tiempo, no tabla) en las tarjetas de CA2? (/analyze A8) | No: conserva su línea de tiempo y solo debe cumplir CA8, con «Ver detalle» con nombre | usuario |
| 2026-10-13 | brecha | ¿Necesita el borde de la tarjeta 3:1? (/analyze A13) | No: es decorativo, como en la opción A | usuario |
| 2026-10-13 | contradicción | El borde de los campos del filtro de citas (1,31:1) no llega al 3:1 de P9 y CA11 pide el escritorio igual (/analyze A36) | Borde de al menos 3:1 también en el escritorio; es la única diferencia admitida en CA11 | usuario |
| 2026-10-13 | brecha | La línea de tiempo de la dentista deja muy poco ancho al nombre a 320 px (/analyze A35) | Se adapta en el móvil: el estado y «Ver detalle» van debajo del nombre, sin pasar a tarjetas; cumple CA7 y CA8 | usuario |
| 2026-10-13 | implícita | Hora de las citas de hoy del panel del personal (/analyze A48) | Solo la hora, como hoy en el escritorio | /analyze A48 |

## Notas para /plan
- Origen técnico de CA7: `overflow-wrap: anywhere` en `.content` (033 T061) parte letra a letra el
  texto de los hijos de filas y rejillas estrechas del personal. Las tablas ya tienen `break-word`
  (033 T069). Acotar `anywhere` a lo que lo necesita (R1 de la 033: rejilla del detalle y tarjeta
  de la próxima cita del paciente).
- Las tablas están dentro de `.table-wrap` (`overflow-x: auto`). Para las tarjetas, valorar CSS con
  etiquetas por celda (`data-label`) frente a un marcado aparte para el móvil, y cuidar 1.3.1 con
  lectores.
- `--medir` (`docs/design/capturar.mjs`) no comprueba solapes («ningún control tapado»): ampliarlo
  con esa comprobación y con las pantallas incluidas del personal. Es obligatorio al tocar el layout,
  `menu.js` o `movil.css` (`AGENTS.md`).
- Logo: `public/images/logo-icon.png`, `logo-icon1.png` y `logo-main.png` son blancos sobre
  transparente; en la barra rosa se ven gracias a `mix-blend-mode: screen` (A9 de la 033). Valorar
  un filtro CSS o un recurso nuevo derivado.
- Deuda relacionada de la 033 en el roadmap: R20 (CA1), R9 (`aria-expanded` del botón dentro de
  `<main>` inerte) y R21 (`ultimoFoco` de `menu.js`) están dentro (CA9).

## Historial
| Fecha | Cambio | Motivo |
|---|---|---|
| 2026-10-13 | CA9 y «Fuera de alcance»: 033/R9 y R21 pasan a estar dentro | Decisión del usuario en /plan 034 |
| 2026-10-13 | Aprobada de nuevo junto con el plan | Usuario |
| 2026-10-13 | CA1, CA2, CA9, RNF 1.3.1 y 1.4.11, supuesto del orden del detalle | /analyze 034 r1: A7, A8, A13, A14, A24, A32 (decisiones del usuario) |
| 2026-10-13 | CA2 (hora del panel y línea de tiempo de la dentista), CA11 y RNF (borde del filtro), Seguridad (alcance de pacientes), Fuera de alcance (033/R14 en parte), fuente del punto de corte | /analyze 034 r2: A35, A36 (decisiones del usuario), A43, A47, A48, A53 |
