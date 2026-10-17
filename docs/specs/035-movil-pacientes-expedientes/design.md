---
spec: 035-movil-pacientes-expedientes
status: approved            # draft | approved (solo el usuario aprueba)
exploration: options
system_version: 1.5.0
created: 2026-10-16
updated: 2026-10-16
---

# Diseño · 035 Móvil para pacientes y expedientes del personal

Hecho con `/design-spec 035` sobre el sistema `docs/design/system.md`. Composiciones en
`design/opcion-<a|b|c>.html`, con estilos comunes en `design/comun.css`. Las descartadas se conservan.

## Dirección elegida

**Composición A · «Hoja de lectura»**, elegida entre A · «Hoja de lectura», B · «Índice de la
ficha» y C · «Perfil compacto», **combinada con el perfil del paciente de C** (decisión del
usuario, 2026-10-16).

La ficha del paciente y la del expediente se leen en el móvil como la hoja de un expediente en
papel, de arriba abajo y en el orden del escritorio. Arriba, el perfil es la carátula de C: una fila
con las iniciales y el nombre y, debajo, los datos de contacto en filas etiqueta / valor.

- **Motivo:** el orden de A es el que el personal ya conoce del escritorio, así que no hay que
  aprender nada nuevo. El perfil compacto de C acorta el bloque más largo de A: pasa de casi una
  pantalla a menos de la mitad, y los expedientes y las citas llegan antes.
- **Vecino más parecido:** el detalle de cita del personal (034, una columna) → se diferencia en que
  abre con la carátula del paciente (fila de identidad y datos etiquetados) y el historial usa las
  tarjetas de lista.
- **Riesgo asumido:**
  - la página sigue siendo larga (expedientes e historial al final) → el perfil compacto la acorta
    y el orden es el del escritorio;
  - en el móvil el perfil no se ve centrado como en el escritorio → los datos y su orden son los
    mismos, y las etiquetas los hacen explícitos (WCAG 1.3.1).

## Pantallas

| Pantalla y estado | Ancho menor | Intermedio | Ancho mayor | Criterios |
|---|---|---|---|---|
| Ficha del paciente · reposo (admin) | `opcion-a.html#ficha-m` (bloques) y `opcion-c.html#ficha-m` (perfil) | no aplica: a 900 px o más es el escritorio, sin cambios (CA10) | el escritorio de hoy (CA10) | CA1, CA2, CA7, CA8, CA11 |
| Ficha del expediente · reposo (dentista) | `opcion-a.html#expediente-m` | no aplica | el escritorio de hoy | CA4, CA5, CA7, CA8 |
| Lista de expedientes, formularios del paciente y del expediente | tarjetas y una columna de la 034 (sin composición propia) | no aplica | el escritorio de hoy | CA3, CA5, CA6 |

## Composición y jerarquía

- **Orden de lectura (ficha del paciente):**
  1. barra con el título y la fila de acciones a partes iguales;
  2. migas;
  3. perfil compacto: iniciales y nombre, edad y tipo de sangre, filas Correo, Teléfono, Ciudad y
     Doctor, y acciones «Editar» y «Desactivar»;
  4. información médica;
  5. contacto de emergencia;
  6. estadísticas (2 columnas);
  7. próxima cita;
  8. expedientes;
  9. historial de citas en tarjetas.

  Es el orden del HTML, igual en todos los anchos.
- **Orden de lectura (ficha del expediente):**
  1. cabecera con la fecha y el servicio, el título, el dentista y la cita, «Volver al paciente» y
     los avisos de alergias y medicamentos;
  2. formulario o lectura;
  3. «Guardar cambios»;
  4. próxima visita;
  5. información;
  6. «+ Nuevo expediente para este paciente».
- **Proporciones:**
  - cabecera del perfil 48 px : resto (las iniciales pequeñas y el nombre como protagonista);
  - filas etiqueta / valor con `--tarjeta-etiqueta-w` (88 px) : resto, como las tarjetas de la 034;
  - estadísticas: rejilla de iguales porque son dos y simétricas.
- **Puntos de corte:** los del sistema (900 px). Por encima, el escritorio de hoy.
- **Momento protagonista:** ninguno.

## Componentes

- **Del sistema:**
  - barra superior con «Menú» solo con el icono (CA11);
  - migas;
  - `.card`;
  - botones `btn`, `btn-primary`, `btn-ghost` y el de activar o desactivar del perfil;
  - `badge` (tipo de sangre y estados);
  - tarjetas de lista de la 034 (`tabla-tarjetas`) en el historial y en la lista de expedientes;
  - `field` y `field-label` en los formularios.
- **Exclusivos de esta pantalla:** **perfil compacto del paciente** (solo en el móvil):
  - tarjeta de identidad con una fila de 48 px (iniciales) y el nombre a su lado, con la edad y el tipo de sangre debajo;
  - los datos de contacto en filas `--tarjeta-etiqueta-w` / resto, con etiquetas visibles («Correo»,
    «Teléfono», «Ciudad», «Doctor») en lugar de los iconos del escritorio;
  - se usa solo en la ficha del paciente.
- **Adornos generados desde datos:** las iniciales del avatar, que salen del nombre y el apellido y
  van ocultas a lectores de pantalla.

## Cambios a incorporar al sistema

| Token, componente o regla | Modo | Valor (menor) | Valor (mayor) | Motivo |
|---|---|---|---|---|
| `--ficha-lateral-w` | fijo | 320px | — | Ancho de la columna del perfil de la ficha del paciente en el escritorio (el de hoy, D1 del plan) |
| `--expediente-lateral-w` | fijo | 300px | — | Ancho de la columna lateral de la ficha del expediente en el escritorio (D2 del plan) |
| `--perfil-avatar-w` / `--perfil-avatar-movil` | corte 900 px | 48px | 64px (el de hoy) | Tamaño de las iniciales del perfil |
| `--perfil-iniciales-text` / `--perfil-iniciales-movil` | corte 900 px | 17px | 22px (el de hoy) | Letra de las iniciales (`opcion-c.html`) |
| `--perfil-nombre-text` / `--perfil-nombre-movil` | corte 900 px | 16px | 20px (el de hoy) | Letra del nombre del perfil (`opcion-c.html`) |
| `--weight-bold` | fijo | 600 | — | Peso de las iniciales (el de hoy, sin token) |
| Menú del móvil: «Menú» y «Cerrar menú» solo con el icono | corte 900 px | sin borde ni fondo, 44 × 44, foco del sistema | — (barra lateral) | CA11 |
| Componente «Ficha en una columna» | corte 900 px | una columna en el orden del HTML | la rejilla de hoy | CA1, CA4 |
| Componente «Perfil compacto del paciente» | corte 900 px | fila de identidad y filas etiqueta / valor | la tarjeta centrada de hoy | Elección del usuario (C) |
| Componente «Acciones de la barra en el móvil» | corte 900 px | fila bajo el título a partes iguales | a la derecha del título | D4 del plan |
| Componente «Acciones de formulario» | corte 900 px | botones a todo el ancho, texto centrado | a la derecha | D5 del plan |
| Campo de formulario: foco del sistema en los formularios del paciente y del expediente | fijo | anillo `--focus-ring`, borde `--border-control`, sin halo | — | D13 del plan (/analyze A6, A24) |

## Movimiento

Ninguno: la spec no añade animaciones y el menú conserva las suyas (033), con su versión reducida.

## Accesibilidad

- **Contraste** (calculado desde los tokens):
  - etiquetas `--p600` sobre `--white`: 7,00:1;
  - icono del menú `--p600` sobre `--white`: 7,00:1;
  - texto `--text` sobre `--white`: 21:1;
  - aviso `--red` sobre `--red-bg`: 6,13:1;
  - tipo de sangre `--p600` sobre `--p100`: 6,05:1.
- **Encabezados y estructura:**
  - el nombre del perfil se mantiene como texto destacado, sin crear un `h2` nuevo (el resto de la
    deuda de encabezados es 034/R16);
  - los datos de contacto llevan su etiqueta visible en el móvil, también para lectores de pantalla.
- **Teclado:** el orden del foco es el del HTML (acciones de la barra, migas, «Editar», «Desactivar»,
  «+ Nuevo», «Ver →», «+ Nueva», «Ver detalle»…), con el anillo del sistema.
- **Decorativos ocultos:** las iniciales y los iconos de contacto llevan `aria-hidden`.
- **Sin animación:** todo el contenido está en el HTML y se ve sin JavaScript.

## Anti-clichés

| Cliché que esta pantalla podría caer | Alternativa usada |
|---|---|
| Avatar circular grande centrado sobre el nombre («tarjeta de perfil de red social») | En el móvil, iniciales de 48 px en una fila con el nombre (perfil compacto de C) |
| Iconos sueltos sin texto para los datos de contacto | Etiquetas visibles («Correo», «Teléfono», «Ciudad», «Doctor») en el móvil |
| Tarjetas idénticas en rejilla para todo | Una columna con bloques de distinto peso: el perfil, los avisos médicos, las estadísticas en 2 columnas y las listas en tarjetas |

## Decisiones

| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-10-16 | diseño | ¿Qué composición para las fichas en el móvil? | A · «Hoja de lectura», con el perfil del paciente de C · «Perfil compacto»; B (índice de anclas) descartada | usuario |
| 2026-10-16 | supuesto | ¿Qué incluye «el perfil del cliente de la opción C»? | La tarjeta de identidad: fila de iniciales y nombre, edad y tipo de sangre, y los datos de contacto en filas etiqueta / valor, con sus acciones. La información médica y el contacto de emergencia quedan como en A | agente; confirmado por el usuario al aprobar el diseño (2026-10-16, /analyze A14) |
| 2026-10-16 | refinado | Las iniciales y el nombre del perfil, sin token y con otro tamaño que la maqueta (/analyze A1, A20) | Tokens para su tamaño y peso; en el móvil, 17 px y 16 px como `opcion-c.html`; el escritorio, como hoy | usuario |
| 2026-10-16 | implícita | ¿Composición propia para la lista de expedientes y los formularios? | No: tarjetas de lista y una columna de la 034, y botones de formulario a todo el ancho (plan D3, D5) | `docs/specs/035-movil-pacientes-expedientes/plan.md` |
| 2026-10-16 | implícita | ¿Íconos o tarjeta para compartir? | No aplica: la spec no toca rutas públicas ni la marca en el navegador (`docs/seo.md`: solo `/registro`) | `docs/seo.md` |
