---
id: 046
slug: navegacion-interna
status: draft
created: 2026-11-12
extends: [008, 009, 010, 028, 033, 034, 035]
---

# 046 · Navegación interna: migas de pan en todas las pantallas de segundo nivel

## Problema
Dentro del panel, saber dónde se está y cómo volver depende de la pantalla. Unas tienen migas de pan
con el componente del sistema (detalle de cita, agendar, ficha del paciente, expediente); otras las
tienen hechas a mano y con otro aspecto (nueva cita, personal, usuarios), y otras no tienen ninguna
salida visible: reprogramar una cita y el alta y la edición de un servicio, que además muestran
«Dashboard» como título. El expediente repite la salida con un botón «Volver al paciente», y «Agendar cita», que es una
entrada del menú del paciente, lleva migas cuando ninguna otra pantalla del menú las lleva. Las migas
tampoco se anuncian como navegación a los lectores de pantalla ni marcan la página actual, y el
enlace no se distingue del texto (deuda 033/R6). Quien trabaja en la clínica o el paciente tienen
que usar el botón Atrás del navegador o el menú y pierden el hilo de la tarea.

## Historias de usuario
- Como miembro del personal (administración, dentista o asistente) en un detalle, una ficha o un formulario, quiero ver en qué parte del sistema estoy y volver un nivel con un clic, para no depender del botón Atrás.
- Como paciente que consulta o reprograma una cita, quiero una salida visible hacia «Mis citas», para no quedarme atrapado en la pantalla.
- Como persona que usa un lector de pantalla o el teclado, quiero que las migas se anuncien como navegación y digan cuál es la página actual, para orientarme igual que quien las ve.
- Como administración, quiero que el alta y la edición de un servicio tengan su propio título, para saber en qué pantalla estoy.

## Criterios de aceptación
- [ ] CA1 · Dado un usuario con sesión, cuando abre a 320, 390 o 1440 px de ancho cualquiera de las pantallas de segundo nivel de la tabla «Pantallas y caminos», entonces ve sin desplazarse, entre la barra superior y el contenido de la pantalla (debajo del aviso del sistema, si lo hay, como hoy), un único bloque de migas de pan con el camino de esa tabla, con el mismo aspecto en todas; las pantallas a las que se llega desde el menú (paneles, listados, agendar cita, mis doctores, perfil, galería, reportes y auditoría) no llevan migas; «Agendar cita» pierde las que tiene hoy.
- [ ] CA2 · Dado un usuario en una pantalla con migas, cuando pulsa cualquier elemento del camino que no sea el último, entonces llega a la pantalla que ese elemento nombra, que es siempre la misma venga de donde venga; el último elemento nombra la pantalla actual y no es un enlace.
- [ ] CA3 · Dada una persona que usa un lector de pantalla o el teclado, cuando recorre una pantalla con migas, entonces las migas se anuncian como una navegación con el nombre «Migas de pan», el último elemento se anuncia como la página actual, los separadores no se leen y cada enlace recibe el foco con el indicador de foco del sistema.
- [ ] CA4 · Dado cualquier usuario, cuando mira las migas, entonces distingue los enlaces del elemento actual sin depender solo del color, y el texto de unos y otro cumple el contraste AA sobre el fondo.
- [ ] CA5 · Dado un usuario que abre «Reprogramar cita» (como personal o como paciente), cuando la cita se puede reprogramar y cuando no, entonces en los dos casos tiene las migas hasta el detalle de esa cita; salir por ellas no cambia la cita.
- [ ] CA6 · Dado un usuario en uno de los diez formularios de alta o de edición de la tabla (nueva cita; nuevo y editar paciente; nuevo expediente; nuevo y editar personal; nuevo y editar usuario; nuevo y editar servicio), cuando pulsa «Cancelar», entonces llega a la misma pantalla que el penúltimo elemento de las migas y no se guarda nada; en «Editar usuario» eso cambia el destino de hoy (el listado) por la ficha del usuario. «Cancelar la cita definitivamente» de «Reprogramar» es otra acción y no cambia.
- [ ] CA7 · Dado un miembro del personal en el expediente de un paciente, cuando lo abre, entonces vuelve a la ficha del paciente por las migas y ya no hay un botón «Volver al paciente» (reemplaza esa parte de 035 CA7).
- [ ] CA12 · Dadas las pantallas que las specs 033, 034 y 035 exigen ver en el escritorio «igual que antes» (033 CA8, 034 CA11, 035 CA10), cuando se comparan después de la 046, entonces lo único que cambia en ellas son las migas (aspecto, y su ausencia en «Agendar cita»), el botón «Volver al paciente» y la entrada marcada del menú en las pantallas de expedientes; la 046 reemplaza esa parte de los tres criterios y el resto de cada pantalla se ve igual.
- [ ] CA8 · Dada la administración en «Nuevo servicio» o «Editar servicio», cuando abre la pantalla, entonces la barra superior y la pestaña del navegador muestran ese título y no «Dashboard»; ninguna pantalla de la tabla muestra el título por defecto.
- [ ] CA9 · Dado un usuario en una pantalla de segundo nivel, cuando mira el menú (barra lateral en el escritorio, menú en el móvil), entonces la entrada de la sección a la que pertenece la pantalla (el primer elemento de sus migas) aparece marcada como la actual.
- [ ] CA10 · Dado un camino con un nombre largo (un paciente o un miembro del personal con nombre y apellidos largos), cuando se ve a 320 px de ancho, entonces las migas pasan a otra línea o acortan el nombre sin que la página se desplace en horizontal ni se corte texto que impida entender el camino, y cada enlace mide al menos 44 px de alto en el móvil.
- [ ] CA11 · (abuso) Dado un usuario que añade a la dirección un destino de vuelta, o cuyo nombre o el de un paciente contiene código, cuando se pintan las migas, entonces los enlaces llevan solo a las pantallas fijas de la tabla dentro del área de su propio rol, el nombre se muestra como texto y no se ejecuta, y una pantalla que su rol no puede abrir responde igual que hoy, sin mostrar migas ni nombres.

### Pantallas y caminos
El primer elemento es la entrada del menú de la sección, con el mismo texto que tiene en el menú de cada rol: donde la tabla dice «Citas», la dentista ve «Mis Citas», como en su menú. Cada elemento menos el último es un enlace. No todos los roles abren todas las pantallas: valen las combinaciones que hoy existen (p. ej. la dentista no da de alta pacientes y la asistente no los edita ni da de alta expedientes).

| Quién | Pantalla | Camino | Hoy |
|---|---|---|---|
| Personal | Nueva cita | Citas › Nueva cita | migas hechas a mano |
| Personal | Detalle de cita | Citas › *paciente* | migas del sistema |
| Personal | Reprogramar cita | Citas › *paciente* › Reprogramar | sin migas |
| Personal | Nuevo paciente | Pacientes › Nuevo paciente | migas del sistema |
| Personal | Ficha del paciente | Pacientes › *paciente* | migas del sistema |
| Personal | Editar paciente | Pacientes › *paciente* › Editar | «Pacientes › Editar: *paciente*» |
| Personal | Nuevo expediente | Pacientes › *paciente* › Nuevo expediente | migas del sistema |
| Personal | Expediente | Pacientes › *paciente* › Expediente | migas del sistema y botón «Volver al paciente» |
| Administración | Nuevo miembro del personal | Personal › Nuevo miembro | migas hechas a mano |
| Administración | Ficha del personal | Personal › *miembro* | migas hechas a mano |
| Administración | Editar personal | Personal › *miembro* › Editar | «Personal › Editar: *miembro*», a mano |
| Administración | Nuevo usuario | Usuarios › Nuevo usuario | migas hechas a mano |
| Administración | Ficha del usuario | Usuarios › *usuario* | migas hechas a mano |
| Administración | Editar usuario | Usuarios › *usuario* › Editar | «Usuarios › Editar: *usuario*», a mano |
| Administración | Nuevo servicio | Servicios › Nuevo servicio | sin migas, título «Dashboard» |
| Administración | Editar servicio | Servicios › Editar servicio | sin migas, título «Dashboard» |
| Paciente | Detalle de cita | Mis Citas › Detalle | migas del sistema |
| Paciente | Reprogramar cita | Mis Citas › Detalle › Reprogramar | sin migas |

## Fuera de alcance
- Un botón «← Volver» además de las migas: la salida de cada pantalla son las migas y, en los formularios, «Cancelar».
- Recordar de qué pantalla venía alguien para devolverlo allí (p. ej. volver a la ficha del paciente desde una cita abierta desde ella): los caminos son fijos.
- Migas en las pantallas del menú (paneles, listados, agendar cita, mis doctores, perfil, galería, reportes, auditoría).
- Las pantallas de acceso, las páginas públicas y las de error: sus salidas son de la spec 044.
- Cambiar qué puede ver o hacer cada rol, los textos de los botones de acción o el contenido de las pantallas.
- Rehacer el diseño de las pantallas de personal, usuarios y servicios más allá de sus migas y títulos.
- El isotipo sin uso de DS15 (el favicon de esa deuda ya lo corrigió la ficha `2026-11-12-chore-favicon`).

## Seguridad y privacidad
- Datos sensibles involucrados: ninguno nuevo. Las migas muestran el nombre del paciente, del miembro del personal o del usuario, que la misma pantalla ya muestra a quien puede abrirla.
- Quién puede hacer qué: no cambia ningún permiso; cada rol ve migas solo en las pantallas que ya puede abrir y sus enlaces llevan a pantallas de su propia área.
- Casos de abuso:
  - Como atacante, añado a la dirección un destino de vuelta hacia otro sitio o hacia el área de otro rol → los caminos son fijos y no leen nada de la dirección (CA11).
  - Como usuario malintencionado, registro un nombre con código para que se ejecute en la pantalla de quien abra mi ficha → el nombre se pinta como texto (CA11).
  - Como usuario de un rol, abro la dirección de una pantalla de otro rol para leer en las migas el nombre de un paciente → la respuesta es la de hoy, sin migas ni nombres (CA11).

## Auditoría
No aplica: la spec no añade accesos a datos, no cambia permisos ni autenticación y no crea acciones
nuevas; solo cambia enlaces de navegación entre pantallas que ya existen. Lo que hoy se registra al
abrir o cambiar un dato sensible se sigue registrando igual.

## Diseño
Quien está tres clics dentro de una tarea tiene que entender en un vistazo dónde está y tener el
paso atrás a mano, sin que las migas compitan con el título ni con la acción principal de la
pantalla. Sistema: [docs/design/system.md](../../design/system.md) («Migas de pan», spec 033).

Pantallas que toca: las 18 de la tabla «Pantallas y caminos», en el escritorio y en el móvil, y el
menú del panel (entrada marcada). Recursos que existen: el componente de migas del sistema. Qué debe
diseñarse: cómo se distingue el enlace del elemento actual sin depender del color (CA4), cómo se
comporta un camino de tres elementos con un nombre largo a 320 px (CA10) y la actualización de
«Migas de pan» en el sistema de diseño.

Sin ciclo de candidatos de `/design-spec` (supuesto, como en la 044): `/plan` extiende el componente
que ya existe y el usuario lo valida en pantalla durante `/implement`. Acta: [design.md](design.md).

## Criterios de diseño
Modo: operar · sin ciclo de candidatos (supuesto) · la verificación es la del usuario en pantalla y los tests de `/implement`.

| ID | Qué se mide | Objetivo | Sale de |
|---|---|---|---|
| K1 | Migas en las pantallas de la tabla | presentes, un solo bloque, visibles sin desplazarse a 320, 390 y 1440 px | CA1 |
| K2 | Aspecto de las migas | el mismo componente en las 18 pantallas, sin estilos propios de una pantalla | CA1 |
| K3 | Enlace frente a elemento actual | se distinguen sin color; contraste ≥ 4,5:1 los dos | CA4 |
| K4 | Camino largo a 320 px | sin desplazamiento horizontal; enlaces ≥ 44 px de alto en el móvil | CA10 |
| K5 | Títulos de las pantallas de la tabla | ninguno es «Dashboard» | CA8 |
| K6 | Escritorio de las pantallas de la 033–035 | fuera de las migas, el botón quitado y la entrada marcada, igual que antes | CA12 |

Puertas: las de `design-spec/references/criterios.md` §2 que apliquen (contraste AA, foco visible, toque ≥ 44 px, sin desbordes a 320 px).

## Requisitos no funcionales
- Accesibilidad (WCAG 2.1 AA, constitución P9): migas como navegación con nombre, página actual marcada, foco visible, contraste AA y objetivo de toque ≥ 44 px en el móvil, a 320, 390 y 1440 px.
- La interfaz usa solo el sistema de diseño (P9): ninguna miga con estilos escritos en la propia pantalla.
- Descubribilidad: no aplica; todas las pantallas están detrás del inicio de sesión y ninguna ruta pública cambia.

## Preguntas abiertas

## Decisiones
| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-11-08 | implícita | Origen y alcance de la spec | Migas y «Volver» de las pantallas internas, con una auditoría previa | `docs/specs/044-navegacion-salidas/spec.md` (Fuera de alcance, Decisiones) |
| 2026-11-12 | brecha | Qué pantallas llevan migas | Las de segundo nivel; las del menú, no | usuario |
| 2026-11-12 | brecha | Botón «Volver» además de las migas | No: solo migas y «Cancelar»; se quita «Volver al paciente» del expediente | usuario |
| 2026-11-12 | brecha | Destino de las migas | Siempre el padre fijo, venga de donde venga | usuario |
| 2026-11-12 | brecha | Deudas vecinas | Entran la 033/R6 (accesibilidad de las migas) y los títulos «Dashboard» de servicios (parte de DS15) | usuario |
| 2026-11-12 | brecha | Extiende 7 specs (008, 009, 010, 028, 033, 034, 035), por encima de 4 | Se mantiene junta: es el mismo cambio pequeño de navegación en cada una y solo tiene sentido igual en todas | usuario (división 044/045/046 del 2026-11-08; confirmada al aprobar, 2026-11-12) |
| 2026-11-12 | contradicción | La spec ponía migas «Mis citas › Agendar cita», pero «Agendar cita» es una entrada del menú del paciente y las pantallas del menú no llevan migas (hallado en /plan 046) | «Agendar cita» queda sin migas, como las demás pantallas del menú; su «Cancelar» sigue llevando al panel | usuario (2026-11-12, /plan 046) |
| 2026-11-12 | supuesto | Camino de las pantallas de edición | Con ficha: «sección › nombre › Editar» (hoy «sección › Editar: nombre»); servicios, que no tiene ficha: «Servicios › Editar servicio». Coincide con el destino de «Cancelar» salvo en «Editar usuario», donde «Cancelar» pasa del listado a la ficha (CA6; /analyze A1) | razonable |
| 2026-11-12 | brecha | El menú de la dentista y el del paciente dicen «Mis Citas» y la tabla ponía «Citas» y «Mis citas» (/analyze A4) | El primer elemento lleva el mismo texto que la entrada del menú de cada rol | usuario |
| 2026-11-12 | brecha | Posición de las migas cuando el sistema muestra un aviso arriba (/analyze A8) | Debajo del aviso, como hoy; no se cambia cómo se pintan los avisos | usuario |
| 2026-11-12 | contradicción | 033 CA8, 034 CA11 y 035 CA10 exigen que el escritorio se vea «igual que antes», y la 046 cambia a propósito las migas, un botón y la entrada marcada (/analyze A2) | CA12 reemplaza esa parte de los tres criterios; la comparación en el navegador usa una línea base nueva, tomada después de la 046 y validada por el usuario | usuario |
| 2026-11-12 | supuesto | Padre del expediente | La ficha del paciente, como hoy, aunque se abra desde el listado de expedientes | razonable (destino fijo) |
| 2026-11-12 | supuesto | «Volver al detalle de la cita» cuando la cita no se puede reprogramar | Se conserva: es la única acción de ese aviso, no un «Volver» añadido | razonable |
| 2026-11-12 | supuesto | Ciclo de candidatos de `/design-spec` | Diseño directo con el componente existente; validación del usuario en pantalla en `/implement` | razonable (como la 044; prioridad de la entrega) |
| 2026-11-12 | diseño | Acta de diseño | [design.md](design.md), sin exploración: enlace de las migas en `--p600` subrayado, camino que pasa de línea, sin tokens nuevos | `/design-spec 046` |
| 2026-11-12 | implícita | Permisos y respuestas de acceso denegado o no encontrada | No cambian | `docs/security.md`, spec 044 CA11 |
| 2026-11-12 | implícita | Auditoría | No aplica: sin accesos, permisos ni acciones nuevas | `docs/constitution.md` P7, `docs/observability.md` |

## Notas para /plan
- Auditoría del 2026-11-12 sobre `main` (ebd1025):
  - con `.migas`: `citas/show`, `citas/cliente-show`, `citas/cliente-agendar`, `pacientes/show`, `pacientes/create` (también la edición, vía `pacientes/edit`), `expedientes/create`, `expedientes/show`;
  - con migas en `style=""`: `citas/create`, `personal/create` (y `personal/edit`), `personal/show`, `usuarios/create` (y `usuarios/edit`), `usuarios/show`;
  - sin migas: `citas/partials/reprogramar` (lo usan `citas/reprogramar` y `citas/cliente-reprogramar`), `servicios/create`, `servicios/edit` (las dos sin `@section('title')`);
  - `expedientes/show`: botón «← Volver al paciente» junto a las migas; `citas/cliente-agendar`: migas «Inicio › Agendar cita» que se quitan (es una entrada del menú).
- El menú de `layouts/app.blade.php` marca la sección con `request()->is('…/citas*')`; las pantallas de expedientes (`…/expedientes/…`) no marcan «Pacientes», que es el primer elemento de sus migas (CA9).
- Tests que fijan el marcado actual de las migas: `MovilPacienteTest` (`#migas`, `.migas`, `.migas-actual`, reglas de `.migas a`), `MovilPacientesExpedientesTest` (`div#migas` y las reglas de `.migas`, `.migas a` y `.migas-actual` en `movil.css`), `MovilPersonalTest`. Cambiar el elemento o las reglas obliga a actualizarlos.
- `docs/design/capturar.mjs --medir` compara a 1024 y 1440 px con una línea base (`--linea-base`) las pantallas de la 033–035, que la 046 cambia (CA12).
- `usuarios/create` en edición: «Cancelar» lleva hoy a `admin.usuarios.index` (CA6).
- «Migas de pan» de `docs/design/system.md` cambia (cierra 033/R6): versión nueva del sistema.
- Tocar `layouts/app.blade.php` obliga a `node docs/design/capturar.mjs --medir` en la verificación final de `/implement` y en `/review` (AGENTS.md).
- La rama de la 045 (`feature/045-terminos-privacidad`) también toca `docs/architecture.md` y el pie público; fusionar `main` después de liberarla.

## Historial
| Fecha | Cambio | Motivo |
|---|---|---|
| 2026-11-12 | Creada en `draft` | Petición del usuario: «arranca con la 046» (reservada en la 044) |
| 2026-11-12 | Aprobada, con sus supuestos y sin dividir | Aprobación del usuario |
| 2026-11-12 | «Agendar cita» sale de la tabla y queda sin migas (CA1, CA9, Problema); 18 pantallas | Contradicción hallada en /plan 046 (es una entrada del menú), resuelta y aprobada por el usuario |
| 2026-11-12 | CA1 (posición bajo el aviso), CA6 (diez formularios; «Editar usuario» vuelve a la ficha), CA12 nuevo (reemplaza parte de 033 CA8, 034 CA11 y 035 CA10), texto del primer elemento igual al del menú, fuera de alcance de DS15 precisado; vuelve a `draft` | /analyze r1: A1, A2, A4, A8, A12, A13, A15, A16 (decisiones del usuario) |
