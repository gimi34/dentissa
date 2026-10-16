---
spec: 035-movil-pacientes-expedientes
status: approved
constitution_version: 1.3.0
created: 2026-10-16
---

# Plan · 035 Móvil para pacientes y expedientes del personal

## Enfoque técnico
Mismo camino que la 034: solo HTML y CSS, sin rutas, controladores ni datos nuevos. Por debajo de
900 px, la ficha del paciente y la del expediente pasan a una columna. Sus dos rejillas de ancho
fijo (`320px 1fr` y `1fr 300px`, medidas a 390 px en 875 y 559 px de ancho) dejan de ir en el
`style=""` y pasan a clases del bloque `/* Spec 035 */` de `app.css`, con los mismos valores en el
escritorio. En `movil.css` se convierten en una columna, en el orden del HTML, que ya es el de la
spec (D1, D2).

El historial de citas de la ficha y la lista de expedientes reutilizan las tarjetas de la 034
(`tabla-tarjetas`, `data-label` y clases de celda; D3). Las acciones de la barra superior de la
ficha van en una fila a partes iguales bajo el título (D4, decisión del usuario). Las de los
formularios ocupan el ancho en el móvil, con el texto centrado (D5). Los textos largos parten con
`texto-partible` (D6).

El diseño de las fichas es el de [design.md](design.md) (`/design-spec 035`, AI-DD 1.13.0): la
composición A · «Hoja de lectura» con el perfil compacto del paciente de C. La tarjeta de identidad
pasa en el móvil a una fila de iniciales y nombre, y sus datos de contacto a filas etiqueta /
valor (D12).

En los formularios, cada etiqueta se asocia a su campo y cada error a su campo, sin cambiar nada
visible (D7, decisión del usuario). Los botones de abrir y cerrar el menú pierden el recuadro en el
móvil y conservan su área, su foco y su nombre (D8).

La medida previa con la v0.8.0 (2026-10-16) muestra:
- los formularios del paciente y del expediente ya caben a 320 y 390 px; solo cambian sus
  acciones, sus etiquetas y lo que comprueba `--medir`;
- la ficha del paciente mide 875 px y su «Ver detalle» del historial, 38 × 44 px;
- la ficha del expediente mide 559 px;
- la tabla de la lista de expedientes se sale de su tarjeta (506 px).

## Constitution Check
| Principio | Resultado | Justificación / ajuste | Cómo se verifica |
|---|---|---|---|
| P1 Spec aprobada antes de código | ✅ | Spec 035 aprobada por el usuario el 2026-10-16 | manual: `aidd.py status` antes de `/implement` |
| P2 Test que falla antes y pasa después | ✅ | El test nuevo `MovilPacientesExpedientesTest` y los cambios de `MovilPacienteTest` y `MovilPersonalTest` (botones del menú) se escriben antes que el código y fallan hoy. Las comprobaciones nuevas de `--medir` se prueban rompiendo | test: tareas de test de `/tasks`; cada implementación `depende:` de su test |
| P3 Autorización en el servidor | ➖ | No cambian rutas, controladores ni consultas. Cada vista muestra los mismos datos con el mismo alcance; el riesgo de alcance abierto lo corrige la 014 (spec, Seguridad) | — |
| P4 Entrada validada y SQL con bindings | ➖ | Sin entradas nuevas: los formularios envían los mismos campos y su validación no cambia. Los `id`, `for` y `aria-describedby` no son datos | — |
| P5 Nada sensible en el repo | ✅ | Capturas y medidas sobre la base desechable `dentissa_capturas`, borrada al terminar (cabecera de `capturar.mjs`) | test: gitleaks en la verificación final |
| P6 Sin vulnerabilidades críticas/altas | ✅ | Sin dependencias nuevas. Línea base del 2026-10-16 (`/release 034`): `composer audit --locked` sin avisos, `npm audit --audit-level=high` 0 vulnerabilidades; `trivy fs` solo el falso positivo de `vendor/mockery`; `trivy image` dentro de EX2 (vence 2026-10-31) | test: `composer audit`, `npm audit` y `trivy fs` en la verificación final |
| P7 Auditoría de accesos | ➖ | No toca autenticación ni permisos. Sigue la excepción P7 vigente (030/031) | — |
| P8 Correlación y logs sin datos sensibles | ➖ | No añade logs | — |
| P9 Solo el sistema de diseño · WCAG 2.1 AA | ✅ | Reglas nuevas solo con tokens: cuatro tokens nuevos (dos anchos de columna y los dos tamaños de las iniciales, D1, D2 y D12), el resto existentes. Los `style=""` que impiden el móvil pasan a clases con los mismos valores (D10b de la 034, D9). Las partes tocadas cumplen AA: etiquetas asociadas y errores enlazados (1.3.1, 3.3.1; D7), toque ≥ 44 px, foco visible con el anillo del sistema en lo cambiado, icono del menú ≥ 3:1 (`--p600` sobre `--white`, 7,00:1; D8). Los colores, focos y encabezados que no se tocan siguen como deuda DS (spec, Fuera de alcance) | test: `MovilPacientesExpedientesTest` (solo tokens con control positivo, etiquetas `for`/`id`, `aria-describedby`, sin los `style=""` imprescindibles), `MovilPacienteTest` (contraste y toque del menú); test en navegador: `--medir`; manual: capturas a 320, 390 y 1440 contra `design/opcion-a.html` y `opcion-c.html` y lector de pantalla en un formulario |
| P10 Las rutas indexables son descubribles | ➖ | La spec no crea ni cambia rutas indexables (`docs/seo.md`: solo `/registro`); todas las pantallas incluidas exigen sesión | — |

## Cambios por módulo
| Módulo | Cambio | Riesgo |
|---|---|---|
| Sistema de diseño (`docs/design/system.md`, `system.html`) | Versión 1.6.0 con «Cambios a incorporar al sistema». Capturas de las pantallas incluidas a 320, 390 y 1440 y del menú abierto de cada rol | Bajo |
| Estilos existentes (`public/css/app.css`) | Cuatro tokens en `:root` (D1, D2, D12). Clases del perfil (`.perfil-identidad`, `.perfil-avatar`, `.perfil-dato`) con los valores de hoy (D12). Bloque `/* Spec 035 */ … /* Fin spec 035 */` tras el de la 034, sin media queries: `.ficha-paciente`, `.ficha-expediente`, `.expediente-cabecera`, `.acciones-ficha`, `.acciones-formulario`, `.dato-recortado` y `.paginacion`, con los valores de hoy (D9) | Medio: el escritorio debe quedar igual (CA10) |
| Estilos del móvil (`public/css/movil.css`) | Bloque «Spec 035» dentro de la media query: las dos fichas en una columna (D1, D2), cabecera del expediente (D2), acciones de la barra superior (D4), acciones de formulario (D5), dato recortado que parte (D6), paginación (D3) y perfil compacto (D12). En la regla de `.menu-btn, .menu-cerrar`: sin borde y con fondo transparente (D8) | Medio: los botones del menú cambian en todas las pantallas del móvil |
| Ficha del paciente (`resources/views/pacientes/show.blade.php`) | Rejilla a `.ficha-paciente`. Perfil compacto en el móvil (D12): la tarjeta de identidad y sus filas de contacto pasan a clases, con etiquetas `.solo-movil` e iconos con `aria-hidden`. Acciones de la barra en `<div class="acciones-ficha">` (D4). Historial de citas en tarjetas: `tabla-tarjetas`, `data-label`, `celda-cab` (fecha), `celda-estado` y `celda-acciones`; «Ver detalle» con `aria-label` y su texto en `.solo-movil`, y `aria-hidden` en su icono (D3). `texto-partible` en el nombre, el contacto, la próxima cita y la cabecera de cada expediente; el correo, de `style=""` a `.dato-recortado` (D6) | Medio |
| Formulario del paciente (`resources/views/pacientes/create.blade.php`, también la edición) | `for`/`id` en cada campo; `aria-describedby` y `aria-invalid` cuando hay error (D7). Fila de acciones a `.acciones-formulario` (D5) | Bajo |
| Lista de expedientes (`resources/views/expedientes/index.blade.php`) | Tabla en tarjetas: `celda-cab` (fecha) y `celda-acciones` («Ver»). Etiqueta del buscador asociada (D7). Paginación a `.paginacion` (D3) | Bajo |
| Ficha del expediente (`resources/views/expedientes/show.blade.php`) | Rejilla a `.ficha-expediente` y cabecera a `.expediente-cabecera`, con `texto-partible` en el título (D2, D6). Los títulos de los cuatro campos del formulario pasan de `<div>` a `<label for>`, con los mismos estilos; la fecha sugerida, con `for`/`id` (D7). Fila de «Guardar cambios» a `.acciones-formulario` (D5) | Medio: la 014 cambiará después esta vista (D11) |
| Alta de expediente (`resources/views/expedientes/create.blade.php`) | `for`/`id` y `aria-describedby` (D7). Fila de acciones a `.acciones-formulario` (D5) | Bajo |
| Tests (`tests/Feature/MovilPacientesExpedientesTest.php` **nuevo**; `MovilPacienteTest`, `MovilPersonalTest`) | Ver Estrategia. En `MovilPacienteTest`, el borde y el fondo de `.menu-btn` y `.menu-cerrar` pasan de `--border-control` y `--white` a `0` y `transparent` (033 CA2 y 034 CA9; extends) | Bajo |
| Capturas y medidas (`docs/design/capturar.mjs`) | `--medir` añade las pantallas incluidas (reflujo, toque, solapes, palabras largas, sin JS, escritorio contra la línea base) y los botones del menú sin borde. La línea base pasa a ser la versión en producción, v0.8.0 (cabecera, paso 7b) | Medio: es la red de seguridad de la spec |

## Contratos y datos
Sin endpoints, migraciones ni datos nuevos. Las vistas reciben las mismas variables y los
formularios envían los mismos campos.

### Pantallas y componentes
| Pantalla | Rol | Componentes | Estados |
|---|---|---|---|
| Ficha del paciente | admin, dentista, asistente | migas, tarjeta de identidad con acciones («Editar», «Desactivar/Reactivar» según el rol), información médica, contacto de emergencia, estadísticas, próxima cita, expedientes (tarjetas con «Ver →»), historial de citas en tarjetas | sin expedientes (aviso y «Crear primer expediente»), sin citas («Sin citas registradas»), sin antecedentes |
| Alta y edición del paciente | admin y asistente (alta); admin y dentista (edición) | formulario en `grid-2`, que ya pasa a una columna en el móvil (033); acciones | con errores (borde rojo y mensaje bajo el campo) |
| Lista de expedientes | dentista | buscador con «Buscar» y «Limpiar»; tarjetas; paginación | sin resultados, con búsqueda y sin ella |
| Ficha del expediente | admin, dentista (editan); asistente (solo lectura) | cabecera con «Volver al paciente», avisos de alergias y medicamentos, formulario de cuatro campos o su lectura, próxima visita, información, «+ Nuevo expediente para este paciente» | con fecha vencida o próxima; asistente sin formulario |
| Alta de expediente | admin, dentista | formulario y acciones | con errores |
| Barra superior y menú | todos | botón «Menú» y «Cerrar menú» solo con el icono | abierto y cerrado |

### D1 · Ficha del paciente en una columna (CA1)
- `<div class="ficha-paciente">` en lugar de `style="display:grid;grid-template-columns:320px 1fr;gap:20px;align-items:start"`.
- `app.css`: `.ficha-paciente { display: grid; grid-template-columns: var(--ficha-lateral-w) 1fr; gap: var(--space-7); align-items: start; }`, con el token nuevo `--ficha-lateral-w: 320px`.
- `movil.css`: `.ficha-paciente { grid-template-columns: minmax(0, 1fr); }`. Los hijos ya van en el orden de la spec: la columna del perfil (identidad con acciones, información médica, emergencia, estadísticas y próxima cita) y después la de expedientes e historial. Las columnas internas no cambian: sus `gap` (16 y 20 px) son ya los del escritorio.
- La rejilla de estadísticas interna (`1fr 1fr`) cabe a 320 px (medido) y no cambia.

### D2 · Ficha del expediente en una columna (CA4)
- `<div class="ficha-expediente">` en lugar de `style="display:grid;grid-template-columns:1fr 300px;gap:20px"`; `app.css`: `grid-template-columns: 1fr var(--expediente-lateral-w); gap: var(--space-7)`, con el token nuevo `--expediente-lateral-w: 300px`; `movil.css`: `minmax(0, 1fr)`. El orden del HTML (contenido y después la columna lateral) es el de la spec.
- Cabecera: `<div class="expediente-cabecera">` en lugar de `style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:16px"` (`app.css`, mismos valores con `--space-6`). En `movil.css`, `flex-direction: column; gap: var(--space-3)`: «Volver al paciente» baja bajo el título. El `div` del título lleva `texto-partible`.

### D3 · Tarjetas del historial de citas y de la lista de expedientes (CA2, CA3)
- Mismo marcado que la 034 (D1, D2 y D5 de su plan): `tabla-tarjetas` en la `<table>`, `data-label` igual al texto del `<th>` y las mismas clases en `<th>` y `<td>`.
- **Historial de citas:**
  - celdas: `celda-cab` = Fecha, `celda-estado` = Estado, `celda-acciones` = Acciones; Servicio y Dentista con su etiqueta;
  - «Ver detalle» con `aria-label="Ver detalle"`, `aria-hidden="true"` en su icono y el texto en `<span class="solo-movil">`;
  - sin citas: el mismo `empty-state`.
- **Lista de expedientes:**
  - celdas: `celda-cab` = Fecha, `celda-acciones` = Acciones («Ver», ya con texto); Paciente, Diagnóstico y Próxima visita con su etiqueta;
  - el `max-width:360px` del diagnóstico no impide la tarjeta (la celda es una rejilla) y se queda;
  - paginación: `<div class="paginacion">` en lugar de su `style=""` (`app.css`, mismos valores); en `movil.css`, `flex-wrap: wrap; gap: var(--space-3)`;
  - el buscador ya cabe (`min-width: 220px` en 248 px útiles a 320).
- Las reglas de tarjeta de la 034 ya están en `movil.css`; esta spec no añade ninguna.

### D4 · Acciones de la barra superior de la ficha (CA7; decisión del usuario)
- En `@section('topbar-actions')` de `pacientes/show`, los enlaces van dentro de `<div class="acciones-ficha">`. En `app.css`, `.acciones-ficha { display: flex; align-items: center; gap: var(--space-4); }` reproduce el `gap: 10px` de `.topbar-actions`.
- En `movil.css`:
  - `.topbar-actions:has(.acciones-ficha) { flex-basis: 100%; margin-left: 0; }` lleva la fila bajo el título;
  - `.acciones-ficha { width: 100%; gap: var(--space-3); }`;
  - `.acciones-ficha .btn { flex: 1; justify-content: center; }` reparte la fila a partes iguales: dos botones con admin y dentista, uno con la asistente.
- `:has()` no cambia las demás pantallas: solo la ficha tiene `.acciones-ficha`. Sin `:has()` (navegadores anteriores a 2022-2023), la fila queda como hoy, a la derecha, y la página no se desborda (riesgo R1).

### D5 · Acciones de los formularios y botones centrados (CA5, CA7)
- Las filas de acciones de `pacientes/create`, `expedientes/create` y `expedientes/show` («Cancelar», «Guardar…» o «Registrar paciente») pasan de `style="display:flex;gap:10px;justify-content:flex-end"` a `<div class="acciones-formulario">` (`app.css`, mismos valores con `--space-4`).
- En `movil.css`, `.acciones-formulario .btn { flex: 1; justify-content: center; }`: botones del mismo ancho, con el texto y el icono centrados.
- El resto de grupos de la ficha ya centra su texto: «Editar» y «Desactivar» con `justify-content:center` y «+ Nuevo»/«+ Nueva» y «Ver →» de su tamaño. Lo comprueba `--medir` (texto e icono centrados, grupos alineados) y no lo cambia el marcado.

### D6 · Textos largos (CA8, 034/R8)
- `texto-partible` (de la 034: `overflow-wrap: anywhere` en el móvil) en los textos de filas y rejillas flexibles:
  - en la ficha: el nombre de la tarjeta de identidad, las líneas de contacto, el nombre del contacto de emergencia, el servicio de la próxima cita y el `div` de texto de la cabecera de cada expediente;
  - en la ficha del expediente: el título.
- El correo pasa de `style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap"` a `.dato-recortado`, con los mismos valores en `app.css`. En `movil.css`, `.dato-recortado { white-space: normal; overflow: visible; }`, junto con `texto-partible`: en el móvil se ve entero y parte de línea.
- Los textos de párrafo (diagnóstico, tratamiento, receta, observaciones, alergias) ya parten con `break-word` en `.content` (034, D8).

### D7 · Etiquetas y errores asociados a su campo (RNF 1.3.1 y 3.3.1; decisión del usuario)
- Cada `<label class="field-label">` lleva `for="campo-<name>"` y su campo, `id="campo-<name>"`. El prefijo `campo-` evita repetir nombres de clase (034/R20).
- En la ficha del expediente, los títulos «Diagnóstico», «Tratamiento realizado», «Receta médica» y «Observaciones» pasan de `<div>` a `<label for>`, con el mismo `style=""`; «Fecha sugerida», con `for`/`id`. Solo en la vista con formulario: la lectura de la asistente no cambia.
- Con error, el mensaje lleva `id="error-<name>"` y su campo, `aria-describedby="error-<name>"` y `aria-invalid="true"`. El texto y el lugar no cambian: ya va bajo su campo y se conservan los datos (`old()`).
- Visualmente, nada cambia.

### D8 · Botones del menú solo con el icono (CA11; decisión del usuario)
- En la regla de `movil.css` `.menu-btn, .menu-cerrar`, `background: var(--white)` pasa a `transparent` y `border: var(--border-width) solid var(--border-control)` a `border: 0`. Se mantienen:
  - el resto de la regla, como `color: var(--p600)` y `border-radius`, que da forma al anillo de foco;
  - `width`, `height` y el `min-height` de toque de 44 px;
  - el `:focus-visible` con el anillo del sistema.
- El icono es `--p600` sobre `--white` (7,00:1) en la barra y en el panel: cumple 1.4.11. Los nombres accesibles y `aria-expanded` no cambian.
- `system.md` («Menú del móvil») se actualiza en la primera tarea.

### D9 · Qué `style=""` se toca (como D10b de la 034)
Solo pasan a clases los que impiden el móvil: las dos rejillas de ficha, la cabecera del expediente, el correo recortado, la paginación y las filas de acciones. El resto se queda: tamaños de letra, colores, rellenos de tarjeta y avisos de alergias (sus literales son deuda DS4). Añadir `id`, `for`, `aria-*`, `data-label` o una clase no cuenta como tocar el estilo. El `<style>` propio de `expedientes/show` (y su foco `--p300`) no se toca: deuda DS2 (spec, Fuera de alcance).

### D10 · Medida en el navegador (`capturar.mjs --medir`)
- Pantallas incluidas de la 035, con los ids del paso 4 (`paciente`, `expediente`):
  - `pacientes/show` de los tres roles;
  - `pacientes/crear` de admin y asistente;
  - `pacientes/{id}/edit` de admin y dentista;
  - `expedientes` de la dentista;
  - `expedientes/{id}` de los tres roles;
  - `expedientes/crear` de admin y dentista.
- A 320, 390 y 844 × 390, con JS y sin él:
  - sin desplazamiento horizontal;
  - toque ≥ 44 × 44;
  - sin solapes ni controles fuera de su tarjeta;
  - etiquetas de las tarjetas;
  - palabras de 30 letras sin partir lo que cabe;
  - orden de los bloques de las dos fichas;
  - acciones de la barra en una fila bajo el título;
  - texto e icono centrados en los botones de las pantallas incluidas;
  - errores del formulario del paciente dentro del ancho, tras un envío vacío.
- Botones del menú, en todas las pantallas medidas: borde de 0 y fondo transparente calculados, 44 × 44 y foco visible.
- A 1024 y 1440, las pantallas incluidas iguales a la línea base (≤ 0,1 % de píxeles distintos, como la 034). La línea base es la v0.8.0 (en producción) servida aparte, como el paso 7b de la cabecera. Se compara en reposo: el foco es la única diferencia admitida (CA10).
- Datos de prueba (paso 3 de la cabecera): cliente1 ya tiene citas en todos los estados y un expediente; se le añaden alergias y medicamentos (hoy «Sin antecedentes registrados») para medir los avisos de la ficha y del expediente, y más de 20 expedientes de la dentista para medir la paginación.

### D11 · Orden con la spec 014 (decisión del usuario)
La 035 va antes. La 014 cambia después la ficha del expediente (formularios solo si `@can('editar-expediente')`) sobre el marcado de la 035: conserva `.ficha-expediente`, `.expediente-cabecera`, `.acciones-formulario` y los `for`/`id`, y `MovilPacientesExpedientesTest` falla si no. Se anota en el roadmap junto a la 014.

### D12 · Perfil compacto del paciente en el móvil (CA1, CA7, CA8; design.md)
- **Tarjeta de identidad:**
  - `<div class="card perfil-identidad">` en lugar del `text-align:center` de su `style=""`, que ya no podría cambiar en el móvil; el `padding` en línea se queda;
  - las iniciales, el nombre, la edad y el tipo de sangre van dentro de `<div class="perfil-cab">` (en el escritorio es un bloque y no cambia nada);
  - el avatar pasa de su `style=""` a `.perfil-avatar` con los mismos valores; su ancho y alto, al token nuevo `--perfil-avatar-w: 64px`.
- **Filas de contacto:**
  - cada una pasa de `style="display:flex;align-items:center;gap:8px;font-size:13px;color:var(--text-muted)"` a `.perfil-dato` (`app.css`, mismos valores con `--space-3` y `--text-meta`);
  - cada una lleva su etiqueta `<span class="solo-movil perfil-etiqueta">` («Correo», «Teléfono», «Ciudad», «Doctor») y su icono `aria-hidden="true"`.
- **En `movil.css`:**
  - `.perfil-identidad { text-align: start; }`;
  - `.perfil-cab { display: grid; grid-template-columns: var(--perfil-avatar-movil) minmax(0, 1fr); gap: var(--space-4); align-items: center; }`;
  - `.perfil-avatar { width: var(--perfil-avatar-movil); height: var(--perfil-avatar-movil); margin: 0; }`, con el token nuevo `--perfil-avatar-movil: 48px`;
  - `.perfil-dato { display: grid; grid-template-columns: var(--tarjeta-etiqueta-w) minmax(0, 1fr); }` y `.perfil-dato svg { display: none; }`;
  - `.perfil-etiqueta` con el estilo de la etiqueta de las tarjetas de la 034 (`--text-small`, `--weight-strong`, mayúsculas, `--p600`).
- La información médica y el contacto de emergencia quedan como en la opción A (design.md, supuesto).
- Lo comprueba `MovilPacientesExpedientesTest` (marcado y reglas) y `--medir` (iniciales de 48 px y etiquetas visibles a 390, ninguna a 1440). Verificación manual contra `design/opcion-c.html#ficha-m`.

### Cambios a incorporar al sistema (system.md 1.5.0 → 1.6.0)
- **Tokens:** `--ficha-lateral-w: 320px` y `--expediente-lateral-w: 300px` (anchos de la columna lateral de las fichas en el escritorio, los de hoy), `--perfil-avatar-w: 64px` (iniciales del perfil en el escritorio, el de hoy) y `--perfil-avatar-movil: 48px` (iniciales del perfil compacto en el móvil).
- **Menú del móvil:** el botón «Menú» y «Cerrar menú», solo con el icono, sin recuadro ni fondo; el anillo de foco y el área de `--touch-min` se mantienen (CA11). Cambia la línea «Aspecto de `.btn-outline`, pero con borde `--border-control`».
- **Componentes:**
  - «Ficha en una columna» (perfil y expediente) por debajo de 900 px;
  - «Perfil compacto del paciente» (exclusivo de la ficha del paciente, design.md): fila de iniciales y nombre y filas etiqueta / valor en el móvil;
  - «Acciones de la barra en el móvil» (`.acciones-ficha`: fila bajo el título a partes iguales);
  - «Acciones de formulario» (`.acciones-formulario`: a la derecha en el escritorio, a todo el ancho en el móvil).
- **Pantallas e inventario:** filas de la ficha del paciente, la ficha y la lista de expedientes y los formularios a 320, 390 y 1440; DS8 «parcial (033, 034, 035)».
- **Decisiones del sistema:** la fila 2026-10-16 con estos cambios (spec 035).

## Estrategia de pruebas
- **`tests/Feature/MovilPacientesExpedientesTest.php`** (nuevo; `DatabaseTransactions`, `EscenarioEstadosCita` y `ReglasCss`, con un expediente y citas creados en el test):
  - **Marcado:**
    - historial de citas y lista de expedientes con `tabla-tarjetas`, `data-label` igual a su `<th>` y las clases de D3;
    - «Ver detalle» con `aria-label`, `.solo-movil` e icono con `aria-hidden`;
    - las dos fichas con su clase y sin `grid-template-columns` en línea (con control positivo);
    - los hijos de `.ficha-paciente` en el orden de CA1 (perfil antes que expedientes) y los de `.ficha-expediente` en el de CA4;
    - `.acciones-ficha` en la barra (dos enlaces para admin y dentista, uno para la asistente);
    - `.acciones-formulario` en los tres formularios;
    - `texto-partible` en los textos de D6 y `.dato-recortado` en el correo, sin `white-space` en línea;
    - `.paginacion` sin `style=""` (con más de 20 expedientes, el tamaño de página);
    - perfil compacto (D12): `.perfil-identidad`, `.perfil-cab`, `.perfil-avatar` y una `.perfil-dato` por cada dato de contacto, con su `.perfil-etiqueta` en `.solo-movil` e icono con `aria-hidden`, sin `text-align`, `display` ni tamaño del avatar en línea (con control positivo).
  - **Etiquetas (D7):**
    - en cada formulario, cada `label.field-label` con `for` que apunta a un `input`, `select` o `textarea` del mismo formulario;
    - los cuatro títulos de la ficha del expediente son `<label for>` para admin y dentista, y la asistente no tiene formulario;
    - tras un envío con errores, el campo con error tiene `aria-invalid="true"` y `aria-describedby` que apunta a su mensaje (con el `old()` conservado).
  - **CSS:**
    - bloque `/* Spec 035 */ … /* Fin spec 035 */` solo con tokens y sin media queries (con control positivo);
    - reglas de D1–D6 dentro de la media query del móvil;
    - `.menu-btn, .menu-cerrar` con `border: 0` y `background: transparent` en la media query;
    - reglas del perfil compacto de D12 en la media query, y `.perfil-identidad`, `.perfil-avatar` y `.perfil-dato` con los valores de hoy en el bloque 035;
    - tokens nuevos iguales a la tabla de `system.md`.
  - **Tipos de campo** (CA5): `tel`, `email` y `date` donde corresponde.
- **`MovilPacienteTest`** (033): cambian las aserciones del borde y el fondo de `.menu-btn` y `.menu-cerrar` (D8). El contraste del icono (`--p600` sobre `--white`) y el toque se mantienen.
- **`MovilPersonalTest`** (034): sigue en verde. Las pantallas de la 034 no cambian, salvo los botones del menú, que esa prueba no mira por su borde.
- **`--medir`** (D10): es el único test en el navegador. Es obligatorio por tocar `movil.css` (`AGENTS.md`) y cada comprobación nueva se prueba rompiendo.
- **Manual:**
  - capturas a 320, 390 y 1440 de cada pantalla incluida y del menú abierto, contra las tarjetas y la columna única de la 034 (`034/design/opcion-a.html`) y el escritorio de antes;
  - con NVDA, un campo del formulario del paciente se anuncia con su etiqueta y, con error, con su mensaje;
  - «Menú» se sigue anunciando como «Menú, botón, contraído».
- Tests nuevos de UI dos veces; línea base de la suite antes de empezar.

## Modelo de amenazas
No aplica: la spec no añade entradas, acciones, permisos ni datos. Solo cambia la presentación de
pantallas y formularios que ya existen, con el mismo alcance (spec, Seguridad).

## Trazabilidad
| Criterio de aceptación | Cambio(s) | Test(s) |
|---|---|---|
| CA1: ficha del paciente en una columna, en el orden de la spec | D1, D6 y D12 en `pacientes/show`, `app.css`, `movil.css` | `MovilPacientesExpedientesTest` (clase, orden, sin rejilla en línea, perfil compacto) · `--medir` (reflujo, orden, solapes, iniciales de 48 px y etiquetas) · verificación manual: la ficha a 320 y 390 de los tres roles contra `design/opcion-a.html#ficha-m` (bloques) y `opcion-c.html#ficha-m` (perfil), y a 1440 igual que antes |
| CA2: historial de citas en tarjetas | D3 en `pacientes/show` | `MovilPacientesExpedientesTest` (tarjetas, `data-label`, «Ver detalle», aviso sin citas) · `--medir` (etiquetas, toque) |
| CA3: lista de expedientes en tarjetas, buscador y paginación | D3 en `expedientes/index` | `MovilPacientesExpedientesTest` (tarjetas, `.paginacion`) · `--medir` (reflujo, toque) · verificación manual: la lista a 390 contra `034/design/opcion-a.html` |
| CA4: ficha del expediente en una columna | D2, D6 en `expedientes/show` | `MovilPacientesExpedientesTest` (clase, orden, cabecera) · `--medir` · verificación manual: la ficha a 390 de admin y asistente contra `design/opcion-a.html#expediente-m` |
| CA5: formularios en una columna, etiquetas, acciones y teclados | D5, D7 en `pacientes/create`, `expedientes/create`, `expedientes/show` | `MovilPacientesExpedientesTest` (`for`/`id`, tipos de campo, `.acciones-formulario`) · `--medir` (reflujo, toque) |
| CA6: errores junto a su campo y datos conservados | D7 | `MovilPacientesExpedientesTest` (envío con errores: `aria-invalid`, `aria-describedby`, `old()`) · `--medir` (errores dentro del ancho) · verificación manual: NVDA anuncia el error del campo |
| CA7: texto e icono centrados y grupos alineados | D4, D5 | `MovilPacientesExpedientesTest` (`.acciones-ficha`, `.acciones-formulario`) · `--medir` (centrado, alineación, fila bajo el título) · verificación manual: la ficha a 390 con sus acciones bajo el título |
| CA8: textos largos sin partir letra a letra (034/R8) | D6 | `MovilPacientesExpedientesTest` (`texto-partible`, `.dato-recortado`) · `--medir` (palabras de 30 letras) |
| CA9: sin desplazamiento, sin solapes y toque ≥ 44 a 320, 390 y 844 × 390, también sin JS | D1–D6 | `--medir` (anchos, solapes, toque, sin JS) |
| CA10: escritorio igual que antes salvo el foco | D1–D7 y D9 (mismos valores en `app.css`) | `MovilPacientesExpedientesTest` (valores del bloque 035) · `--medir` (1024 y 1440 contra la línea base v0.8.0) · verificación manual: capturas a 1440 |
| CA11: botones del menú solo con el icono | D8 en `movil.css` | `MovilPacientesExpedientesTest` (`border: 0`, `transparent`) · `MovilPacienteTest` (contraste ≥ 3:1, toque) · `--medir` (borde calculado 0, 44 × 44, foco) · verificación manual: el menú abierto y cerrado a 390 de los cuatro roles y NVDA «Menú, botón, contraído» |
| RNF: WCAG 1.4.10, 1.3.1, 2.4.7, 3.3.1, 1.4.11 | D1–D8 | `MovilPacientesExpedientesTest` · `--medir` · verificación manual con NVDA (CA6, CA11) |
| RNF: toque ≥ 44 × 44 | D3, D4, D5, D8 | `--medir` (toque) |
| RNF: medido a 320, 390, 844 × 390 y 1440, sin JS | D10 | `--medir` |
| RNF: escritorio sin cambios salvo el foco | D9 | `--medir` (línea base) |

## Descubribilidad
No aplica: las pantallas incluidas exigen sesión y ninguna ruta indexable de `docs/seo.md` (solo `/registro`) cambia.

## Observabilidad
No aplica: la spec no añade logs, eventos ni métricas, y no toca autenticación ni permisos.

## Rollout
- Feature flag: no aplica (solo presentación, como la 034).
- Orden de despliegue: uno solo, con `deploy/desplegar.sh vX.Y.Z` (sin migraciones).
- Compatibilidad: sin datos ni contratos nuevos; durante el despliegue, la versión anterior y la nueva sirven las mismas rutas.
- Rollback: `deploy/volver.sh v0.8.0`, sin restaurar la copia (no hay migración).
- Métricas a vigilar tras el deploy: errores 500 en `laravel.log` y 5xx en nginx durante 15 minutos; desde el móvil real, la ficha del paciente de un rol del personal, la lista y la ficha del expediente de la dentista, un formulario y el menú abierto y cerrado.
- Orden respecto a otras specs:
  - la 014 va después (D11);
  - la 020 (rama `feature/020-auditoria-base`) toca el layout y `citas.css`, pero no estas vistas;
  - la 031 no toca vistas ni estilos;
  - la versión se fija en `/release` (v0.9.0 si sale antes que la 031; si no, v0.10.0).

## Decisiones (→ ADR si son arquitectónicas)
| Fecha | Decisión | Motivo |
|---|---|---|
| 2026-10-16 | La 035 va antes que la 014, que se aplica sobre su marcado (D11) | decisión del usuario, 2026-10-16 |
| 2026-10-16 | Etiquetas asociadas a su campo y errores enlazados en los formularios incluidos (D7) | decisión del usuario, 2026-10-16 (WCAG 1.3.1, P9) |
| 2026-10-16 | Acciones de la barra de la ficha en una fila bajo el título, a partes iguales (D4) | decisión del usuario, 2026-10-16 |
| 2026-10-16 | Sin maquetas HTML nuevas: se reutilizan los componentes de la 034 y se revisan capturas en cada tarea | decisión del usuario, 2026-10-16; sustituida por la siguiente tras el upgrade a AI-DD 1.13.0 |
| 2026-10-16 | Diseño de las fichas: composición A · «Hoja de lectura» con el perfil compacto de C, en [design.md](design.md) (D12) | `/design-spec 035` tras el upgrade a AI-DD 1.13.0 (decisión del usuario, 2026-10-16) |
| 2026-10-16 | Constitution Check contra la 1.3.0: P10 no aplica | constitución 1.3.0 (upgrade a AI-DD 1.13.0) |
| 2026-10-16 | `:has()` para llevar las acciones de la ficha bajo el título, sin tocar el layout; sin `:has()`, quedan como hoy | El layout tiene una sola sección de acciones; una sección o clase nueva en el layout cambiaría todas las pantallas. Soporte desde Safari 15.4, Chrome 105 y Firefox 121 |
| 2026-10-16 | Línea base del escritorio = v0.8.0, la versión en producción | Las pantallas incluidas no cambiaron entre v0.7.0 y v0.8.0 salvo los botones del menú, que el escritorio no muestra |
| 2026-10-16 | Medida previa con la v0.8.0: ficha del paciente 875 px y «Ver detalle» 38 × 44; ficha del expediente 559 px; tabla de expedientes 506 px dentro de su tarjeta; formularios dentro del ancho | comprobado en el navegador el 2026-10-16 (script desechable fuera del repo, base de prueba) |
| 2026-10-16 | Sin ADR: no hay dependencias nuevas ni cambios de arquitectura | — |

## Impacto en arquitectura
- `docs/architecture.md`, Frontend: las fichas en una columna y `.acciones-ficha` con `:has()` en
  `movil.css` (T090). Sin cambios en Backend ni en datos.
- `docs/deployment.md` y `docs/observability.md`: sin cambios (T091, no aplica).

## Riesgos y mitigaciones
| Riesgo | Mitigación |
|---|---|
| R1 · `:has()` no disponible en navegadores antiguos | Degradación segura: las acciones quedan a la derecha como hoy y la página no se desborda; `--medir` lo mide en Chromium y la comprobación WebKit de la 034 se repite en la ficha |
| R2 · Regresiones del escritorio al pasar `style=""` a clases | Mismos valores con tokens que los reproducen (test del bloque 035) y comparación con la línea base a 1024 y 1440 |
| R3 · Los botones del menú sin recuadro pierden su forma pulsable | Área de 44 × 44 medida, icono 7,00:1 y anillo de foco del sistema; verificación manual del usuario |
| R4 · El `<style>` propio de `expedientes/show` pisa `.field-label` y `.field` | Las etiquetas nuevas conservan su clase y su estilo actual; `--medir` compara el escritorio. Su foco `--p300` sigue como deuda DS2 |
| R5 · Conflicto con la 014 en `expedientes/show` | D11 y aviso en el roadmap; `MovilPacientesExpedientesTest` falla si la 014 rompe el marcado |
| R6 · `aria-describedby` apunta a un error que no existe | Se pone solo con `@error`; el test lo comprueba con y sin error |
