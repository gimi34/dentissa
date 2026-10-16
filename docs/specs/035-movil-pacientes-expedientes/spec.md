---
id: 035
slug: movil-pacientes-expedientes
status: approved
created: 2026-10-16
extends: [033, 034]
---

# 035 · Móvil para pacientes y expedientes del personal

## Problema
La v0.8.0 (spec 034) adaptó al móvil las pantallas más usadas del personal, pero en producción el
usuario vio que la ficha del paciente sigue sin adaptar. Desde la administración hay que alejar la
vista para verla entera y algunos botones no quedan centrados. No se rompe nada, pero la ficha no
se puede usar en el móvil. Lo mismo pasa con el resto de las pantallas de pacientes y expedientes:
- el alta y la edición del paciente;
- la lista de expedientes de la dentista;
- la ficha de un expediente y el alta de uno nuevo.

Lo sufren la administración, las dentistas y las asistentes cuando consultan o atienden a un
paciente desde el móvil.

Además, el usuario prefiere que los botones para abrir y cerrar el menú del móvil muestren solo su
icono, sin el recuadro que los rodea hoy, en todas las pantallas y para todos los roles.

Esta spec atiende DS8 **parcialmente**: cubre las pantallas de pacientes y expedientes del
personal. Los formularios de agenda y servicios van en la spec 036, y la administración (personal,
usuarios, reportes y doctores de la asistente) en la 037 (decisión del usuario).

## Historias de usuario
- Como administración, dentista o asistente, quiero consultar la ficha de un paciente desde el
  móvil sin alejar la vista, para ver sus datos, sus expedientes y sus citas cuando no estoy en el
  ordenador.
- Como administración o asistente, quiero dar de alta o editar un paciente desde el móvil, para
  registrar a quien llega sin ir al ordenador.
- Como dentista, quiero ver la lista de expedientes, abrir uno y crear uno nuevo desde el móvil,
  para consultar y anotar la historia clínica durante la consulta.
- Como cualquier persona del personal, quiero que los botones de estas pantallas se vean
  ordenados y fáciles de pulsar con el dedo, para no equivocarme de acción.
- Como cualquier persona que usa la app en el móvil, quiero que los botones de abrir y cerrar el
  menú sean solo el icono, sin recuadro, para que la barra se vea más limpia.
- Como responsable del proyecto, quiero que el escritorio siga igual, para no romper lo que ya
  funciona.

## Criterios de aceptación
Las «pantallas incluidas» son, para cada rol que hoy las ve:
- la ficha del paciente (administración, dentista y asistente);
- el alta del paciente (administración y asistente) y su edición (administración y dentista);
- la lista de expedientes (dentista);
- la ficha de un expediente (administración, dentista y asistente);
- el alta de un expediente (administración y dentista).

«Móvil» es cualquier ancho por debajo de 900 px, como en la 033 y la 034.

- [ ] CA1 · Dado el personal en la ficha de un paciente, cuando la abre en el móvil (de 320 a
  899 px), entonces la página va en una sola columna, en este orden: el perfil del paciente con sus
  acciones, sus datos de contacto y médicos, sus expedientes y su historial de citas. No hace falta
  alejar la vista para ver nada, no se desplaza de lado y ningún bloque se monta sobre otro.
- [ ] CA2 · Dado el personal en la ficha de un paciente en el móvil, cuando ve su historial de
  citas, entonces cada cita aparece como una tarjeta con su fecha, servicio, dentista, estado y su
  acción «Ver detalle» a la vista, como las listas de la 034. Cada dato lleva su etiqueta, salvo la
  cabecera (la fecha) y el estado. Sin citas, se ve el mismo aviso que hoy.
- [ ] CA3 · Dada la dentista en la lista de expedientes, cuando la abre en el móvil, entonces cada
  expediente aparece como una tarjeta con su fecha, paciente, diagnóstico, próxima visita y la
  acción «Ver». El buscador y los botones para pasar de página se ven y se pueden usar sin
  desplazar nada de lado.
- [ ] CA4 · Dado el personal en la ficha de un expediente, cuando la abre en el móvil, entonces la
  página va en una sola columna: primero el contenido del expediente (con su formulario de edición
  cuando el rol puede editar) y después la próxima visita, la información y sus acciones. No se
  desplaza de lado y ningún bloque se monta sobre otro.
- [ ] CA5 · Dado el personal en el alta o la edición de un paciente, o en el alta de un expediente,
  cuando abre el formulario en el móvil, entonces los campos van en una sola columna y ocupan el
  ancho disponible, cada uno con su etiqueta visible, y «Guardar» y «Cancelar» se ven y se pueden
  pulsar sin desplazar nada de lado. Los campos de teléfono, correo y fecha abren el teclado o el
  selector del móvil que les corresponde (como en 033 CA5).
- [ ] CA6 · Dado un formulario de las pantallas incluidas enviado con errores en el móvil, cuando la
  página vuelve, entonces cada mensaje de error se ve junto a su campo, dentro del ancho de la
  pantalla, y los datos escritos se conservan como hoy.
- [ ] CA7 · Dados los botones y enlaces de acción de las pantallas incluidas en el móvil (las
  acciones de la cabecera, como «Nuevo expediente» y «Nueva cita», «Editar» y activar o desactivar
  del perfil, «+ Nuevo», «Ver», «Guardar», «Cancelar», «Volver al paciente»), cuando se ven,
  entonces cada uno tiene su texto y su icono centrados dentro del botón y los botones de un mismo
  grupo quedan alineados entre sí, sin que ninguno se salga de su tarjeta ni tape a otro.
- [ ] CA8 · Dado un texto en las pantallas incluidas (nombres, correos, direcciones, alergias,
  diagnósticos, tratamientos, notas), cuando se ve en el móvil, entonces ninguna palabra que cabe en
  su línea se parte letra a letra. Una palabra más larga que el ancho disponible pasa a la línea
  siguiente y nunca ensancha la página (mantiene 033 CA6 y 034 CA7; atiende 034/R8 en estas
  pantallas).
- [ ] CA9 · Dadas las pantallas incluidas, cuando se abren a 320, 390 y 844 × 390 px, también sin
  JavaScript, entonces ninguna se desplaza en horizontal, ningún control queda tapado por otro ni se
  sale de su tarjeta, y los botones, enlaces de acción, campos y elementos que se pueden pulsar
  miden al menos 44 × 44 px.
- [ ] CA10 · Dadas las pantallas incluidas en el escritorio (1024 y 1440 px), cuando se abren,
  entonces se ven igual que antes, comprobado contra capturas de la versión anterior. La única
  diferencia admitida es el foco visible con los colores del sistema en lo que esta spec toque
  (como en la 034, decisión del usuario).
- [ ] CA11 · Dado cualquier rol en el móvil, en cualquier pantalla, cuando ve el botón que abre el
  menú y, con el menú abierto, el que lo cierra, entonces cada uno muestra solo su icono (las tres
  líneas y la cruz), sin recuadro ni borde alrededor. El icono se distingue del fondo con un
  contraste de al menos 3:1 (WCAG 1.4.11), el área que se puede pulsar sigue midiendo al menos
  44 × 44 px, el foco con el teclado sigue siendo visible y los lectores de pantalla los siguen
  anunciando como «Menú, botón, contraído» y «Cerrar menú, botón» (cambia el aspecto de 034 CA9 y
  del botón de cerrar de la 033; mantiene 033 CA3).

## Fuera de alcance
- Los formularios de agenda y servicios (nueva cita, reprogramar como personal, alta y edición de
  servicios y su diálogo de eliminar): spec 036.
- La administración: personal, usuarios, reportes y doctores de la asistente: spec 037.
- Cambiar qué datos muestra cada pantalla, añadir campos, acciones o validaciones nuevas, o
  cambiar quién puede ver o editar cada cosa.
- Esqueletos de carga mientras la página carga: spec propia (roadmap).
- La deuda de la 034 en el roadmap (034/R9 y R12–R22), salvo 034/R8, que entra en estas pantallas
  (CA8). Los encabezados del detalle de cita (034/R16) siguen en el roadmap: no es una pantalla
  incluida.
- El resto de la deuda de diseño (DS1–DS5, DS7, DS9–DS15): los colores, encabezados y focos que
  esta spec no toca se quedan como están.

## Seguridad y privacidad
- Datos sensibles involucrados: los mismos que ya muestran estas pantallas (datos personales y de
  contacto del paciente, alergias, tipo de sangre, notas médicas, diagnósticos y tratamientos de sus
  expedientes). No se añade ninguno.
- Quién puede hacer qué: sin cambios. Cada rol ve y edita lo mismo que hoy, con el mismo alcance.
  Hoy la ficha del paciente y sus expedientes no se limitan a los pacientes de cada dentista o
  asistente (riesgo de alcance abierto en `docs/security.md`, que corrige la spec 014): la 035 no
  cambia ese alcance ni lo amplía. Mostrar u ordenar los datos en el móvil es presentación, no un
  permiso.
- Casos de abuso: no aplica. La spec solo cambia la presentación de datos que el rol ya puede ver y
  de formularios que ya existen: no añade entradas, acciones, permisos ni datos.

## Diseño
Quien la usa debe sentir que la ficha del paciente y el expediente están hechos para el móvil: se
leen de arriba abajo sin alejar la vista, cada bloque en su sitio y los botones ordenados donde el
dedo los espera. Con los mismos componentes que la 034 (tarjetas de lista, una columna, botones a
todo el ancho cuando haga falta) y el mismo aspecto que el resto del sistema.

**Pantallas que toca:** las incluidas (ver Criterios), y los botones de abrir y cerrar el menú del
móvil en todas.

**Componentes:** reutiliza la tarjeta de lista y la columna única de la 034. `/plan` decide si hace
falta alguna opción nueva en HTML (p. ej. para el perfil del paciente o los formularios) y la
genera con el sistema.

Sistema: [docs/design/system.md](../../design/system.md).

## Requisitos no funcionales
- WCAG 2.1 AA en las pantallas incluidas:
  - 1.4.10, reflujo a 320 px;
  - 1.3.1, cada dato de las tarjetas con su etiqueta (salvo la cabecera y el estado), también para
    lectores de pantalla, y cada campo de formulario con su etiqueta;
  - 2.4.7, foco visible en lo que esta spec añade o cambia;
  - 3.3.1, los errores de los formularios se identifican junto a su campo;
  - 1.4.11, los iconos de abrir y cerrar el menú sin recuadro (CA11).
- Toque de al menos 44 × 44 px en los controles de las pantallas incluidas.
- Medido a 320, 390, 844 × 390 y 1440 px, también sin JavaScript.
- Escritorio sin cambios salvo el foco (CA10), comprobado contra capturas de antes a 1024 y 1440 px.

## Preguntas abiertas
- Ninguna.

## Decisiones
| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-10-16 | brecha | Quedan unas 30 pantallas del personal en 5 módulos (más de 10 criterios y más de 3 módulos): ¿una spec o varias? | Tres specs que se crean y liberan en orden: 035 pacientes y expedientes (lo visto roto en producción), 036 formularios de agenda y servicios, 037 administración | usuario |
| 2026-10-16 | brecha | ¿El escritorio puede cambiar? | No: igual que hoy, comprobado contra capturas de antes; solo se admite el foco con los colores del sistema en lo tocado (como la 034, A72) | usuario |
| 2026-10-16 | brecha | ¿Qué botones están «descentrados»? | Los de la ficha del paciente, vistos por el usuario en producción (T097 de la 034); CA7 pide texto e icono centrados y grupos alineados en todas las pantallas incluidas | usuario |
| 2026-10-16 | diseño | Botones de abrir y cerrar el menú del móvil | Solo el icono, sin el recuadro que los rodea, en todas las pantallas (CA11; cambia el aspecto de 034 CA9 y del botón de cerrar de la 033) | usuario |
| 2026-10-16 | implícita | Sin recuadro, ¿cómo se ve el foco y dónde se pulsa? | El área de toque sigue en 44 × 44 px y el foco sigue visible con el anillo del sistema; el icono, con contraste ≥ 3:1 | `docs/constitution.md` P9 (WCAG 2.1 AA) |
| 2026-10-16 | implícita | ¿Qué es «móvil»? | Por debajo de 900 px, como la 033 y la 034 | `docs/specs/034-movil-personal/spec.md` |
| 2026-10-16 | implícita | ¿Se corrige el reparto de palabras largas (034/R8) en estas pantallas? | Sí, en las pantallas incluidas (CA8); en el resto, con sus specs | `docs/roadmap.md` (034/R8) |
| 2026-10-16 | supuesto | ¿En qué orden van los bloques de la ficha del paciente en el móvil? | Perfil con sus acciones, datos de contacto y médicos, expedientes e historial de citas: el de lectura del escritorio (columna izquierda y luego la derecha) | agente (revisar) |
| 2026-10-16 | supuesto | ¿En qué orden va la ficha del expediente? | Primero el contenido del expediente y después su columna lateral (próxima visita, información y acciones) | agente (revisar) |
| 2026-10-16 | supuesto | ¿Los anchos de medida? | 320, 390, 844 × 390 y 1440 px, también sin JavaScript, como en la 034 | `docs/specs/034-movil-personal/spec.md` (RNF) |
| 2026-10-16 | implícita | ¿Qué rol ve cada pantalla? | El que la ve hoy (rutas por rol del inventario de vistas de `docs/design/system.md`); la spec no cambia permisos | `docs/design/system.md` (Inventario de vistas) |

## Notas para /plan
- Reutilizar los componentes de la 034 (`tabla-tarjetas`, `data-label`, clases de celda, columna
  única) y su `capturar.mjs --medir` ampliado a estas pantallas, con línea base del escritorio de la
  versión anterior (D13 de la 034).
- La ficha del paciente usa hoy una rejilla de dos columnas con ancho fijo (320 px + resto), una
  rejilla interna de dos columnas y la tabla del historial de citas; la ficha del expediente, una
  rejilla de dos columnas (resto + 300 px). Los botones del perfil tienen `style=""` con `flex` y
  `width:100%`.
- Coordinar con la spec 014 (aprobada), que cambia la autorización de pacientes y expedientes: la
  que se fusione después respeta el marcado de la otra.
- CA11 cambia el aspecto de `.menu-btn` y `.menu-cerrar` (hoy con borde `--border-control` de la
  034/033); sus tests de `MovilPacienteTest` y `MovilPersonalTest` y `--medir` (toque y foco del
  menú) se actualizan con él.
- Medir antes en el navegador cuáles de estas pantallas se desbordan hoy a 390 px con la v0.8.0
  (en la medida de DS8, del 2026-10-04, la edición del paciente por la dentista no se desbordaba).
