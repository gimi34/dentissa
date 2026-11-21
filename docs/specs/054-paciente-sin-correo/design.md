---
spec: 054-paciente-sin-correo
status: approved            # aprobado por el usuario el 2026-11-20
exploration: none           # sin ciclo de candidatos (propuesta de la spec, aprobada con ella el 2026-11-20); motivo en "Resultado"
system_version: 1.20.0
created: 2026-11-20
updated: 2026-11-20
---

# Diseño · 054 Alta de pacientes sin correo

Hecho con `/design-spec 054` en modo sin exploración, sobre `docs/design/system.md` 1.20.0. Los
criterios están en la spec («Criterios de diseño», K1–K5). No hay `design/criterios.json` ni
candidatos.

## Resultado

**Sin diseño nuevo de pantallas.** La 054 no crea pantallas ni cambia su composición: el alta y la
edición siguen siendo el mismo formulario (`pacientes/create.blade.php`, que la edición incluye), y
el directorio y la ficha conservan su estructura. Cambian textos de campos, aparece un dato «Sin
correo» donde hoy iría el correo, y el alta gana un estado: el aviso de posible duplicado (CA3).
Ese aviso es la única pieza nueva y se arma con tokens y botones que ya existen. El usuario lo valida
en pantalla durante `/implement`.

## Pantallas

| Pantalla y estado | Qué cambia | Criterios |
|---|---|---|
| Alta · reposo | «Correo electrónico» sin asterisco y con «(opcional)» en la etiqueta; ayuda debajo del correo y del teléfono; ayuda de la contraseña reescrita | CA1, K1 |
| Alta · error de teléfono | error de CA2 bajo el teléfono, con el patrón de error del formulario | CA2, K2 |
| Alta · posible duplicado | aviso arriba del formulario, con el formulario relleno debajo | CA3, CA9, K2, K3 |
| Directorio | «Sin correo · sin acceso a la aplicación» bajo el nombre, en lugar del correo | CA4, K4 |
| Ficha del paciente | fila «Correo» con «Sin correo · sin acceso a la aplicación» | CA4, K4 |
| Edición · paciente sin correo | correo opcional con ayuda de cómo dar acceso; teléfono con su ayuda | CA5, CA6, K1 |
| Edición · paciente con correo | sin cambios: correo obligatorio con asterisco | CA5 |
| Listado tras el alta sin correo | mensaje de éxito del layout con el texto de CA1; no aparece el recuadro de contraseña generada | CA1 |

Anchos: 320, 390 y 1440 px (`design.widths`); por debajo de 900 px, `.grid-2` ya apila los campos y
`.acciones-formulario` iguala los botones (spec 035).

## Componentes

**Del sistema, sin cambios:**
- Campo de formulario en `.form-paciente` (foco del sistema, spec 035), con `.field-error` y
  `.error-msg` enlazados por `aria-invalid` y `aria-describedby`.
- **Aviso de campo** (`.field-hint field-hint-aviso`, spec 051) para todas las ayudas nuevas: texto
  en `--text` (21:1 sobre `--white`). No se usa `.field-hint` a secas porque su `--text-light` no
  llega a 4.5:1 (DS1).
- Botones `.btn-primary` y `.btn-outline`; `.acciones-formulario` sin cambios.
- Fila `.perfil-dato` de la ficha (spec 035), con su etiqueta «Correo» en el móvil.
- Mensaje de éxito del layout (`session('success')`), tal como está.

**Textos de los campos:**

| Campo | Dónde | Etiqueta | Ayuda debajo (`field-hint-aviso`, enlazada con `aria-describedby`) |
|---|---|---|---|
| Correo | alta | «Correo electrónico (opcional)» | «Sin correo, el paciente no tendrá acceso a la aplicación y el teléfono es obligatorio.» |
| Correo | edición sin correo | «Correo electrónico (opcional)» | «Si añades un correo, el paciente podrá entrar creando su contraseña con «¿Olvidaste tu contraseña?».» |
| Correo | edición con correo | «Correo electrónico *» (como hoy) | ninguna |
| Teléfono | alta y edición sin correo | «Teléfono» | «Obligatorio si el paciente no tiene correo.» |
| Contraseña temporal | alta | como hoy | «Solo se usa si hay correo. Si se deja vacía, se genera una contraseña aleatoria.» (sustituye a la ayuda actual) |

El correo conserva `type="email"` y su placeholder «correo@ejemplo.com», sin `required` en el alta
y en la edición sin correo. El error del teléfono (CA2) va antes que su ayuda y los dos quedan en `aria-describedby`.

**Aviso de posible duplicado (CA3, exclusivo del alta):**
- **Dónde:** dentro del formulario, antes de la primera tarjeta («Datos personales»), con
  `role="alert"` para que el lector de pantalla lo lea al cargar la página. El formulario debajo
  conserva todo lo escrito.
- **Caja:** fondo `--amber-bg`, borde `var(--border-width)` `--amber` (6.73:1 sobre `--white`), radio `--radius`, relleno
  `--space-5`; icono de advertencia de 18 px en `--amber` con `aria-hidden`.
- **Título** (`h2`, `--text-body`, `--weight-strong`, en `--text`: 18.31:1 sobre `--amber-bg`):
  «Ya existe un paciente con estos datos».
- **Texto** (en `--text`): «Coinciden el nombre y el teléfono o la fecha de nacimiento que
  escribiste.» Debajo, la lista de coincidencias (hasta 3, cada una con el nombre completo; si hay
  más, «y N más»). No dice cuál de los dos datos coincide ni muestra ningún dato del existente salvo
  su nombre (CA9).
- **Acciones,** en `.acciones-formulario` dentro de la caja:
  - «Ver ficha de <nombre>» (`.btn-primary` con los valores de `.formulario-accesible`: blanco sobre
    `--p500`, 5.42:1; el `.btn-primary` global sobre `--p400` no llega, DS1), un enlace por coincidencia, solo si quien registra
    puede ver esa ficha; es la acción recomendada, para no duplicar.
  - «Registrar de todos modos» (`.btn-outline`), botón de envío del mismo formulario que confirma el
    alta con los datos escritos.
  - Sin ficha visible para quien registra, solo el texto y «Registrar de todos modos».
- **Móvil:** a 320 y 390 px, la caja ocupa el ancho del contenido, el texto se parte
  (`texto-partible`) y los botones van uno debajo del otro a ancho completo, con ≥ `--touch-min` de
  alto; las dos acciones quedan visibles sin desplazar desde el inicio del aviso (K3).

**«Sin correo» en el directorio y la ficha (CA4):**
- **Directorio:** en la línea bajo el nombre donde hoy va el correo, el texto «Sin correo · sin
  acceso a la aplicación» con el mismo tamaño y color que el correo (`--text-muted`), visible también
  en el móvil (hoy el correo solo se ve en el escritorio) y partible a 320 px.
- **Ficha:** la fila «Correo» aparece siempre; sin correo, con su icono de sobre y el texto «Sin
  correo · sin acceso a la aplicación» en `--text`. Hoy la fila se oculta si no hay correo, lo que no
  distingue «sin correo» de «dato vacío».

## Cambios a incorporar al sistema

| Cambio | Modo | Dónde |
|---|---|---|
| **Aviso de posible duplicado** (`.aviso-duplicado`): caja `--amber-bg` con borde `--amber`, título y texto en `--text`, acciones en `.acciones-formulario`; `role="alert"`. Se documenta como componente porque es el primer aviso ámbar de la app y otros formularios podrían reutilizarlo | fijo | `system.md` → «Aviso, diálogo y mensajes» y `system.html` |

Versión: la siguiente a la vigente cuando se fusione (1.21.0 si nadie la toma antes; si la 052 o la
053 suben el sistema antes, se renumera). Contraste a comprobar en el test: `--text` sobre
`--amber-bg` (18.31:1), `--amber` (borde, ≥ 3:1) sobre `--white` (6.73:1) y el botón principal
blanco sobre `--p500` (5.42:1).

## Movimiento
Ninguno.

## Accesibilidad
- Etiquetas asociadas a sus campos (`for`/`id`, como hoy); ayudas y errores enlazados con
  `aria-describedby`; el error lleva `aria-invalid`.
- El aviso de duplicado es texto con título, no solo color, y `role="alert"`; con teclado, Tab llega
  primero a sus acciones y después al formulario.
- Foco visible: el del sistema en `.form-paciente`, también en los botones del aviso.
- Toque ≥ 44 px a 320 y 390 px en campos y botones (`movil.css` ya lo fija para `.field` y `.btn`).
- **Deuda que no se toca:** el mensaje de éxito del layout (CA1) usa `--green` sobre `--green-bg`
  (2.98:1, DS1), se oculta a los 4 s y no tiene `aria-live` (DS7). Corregirlo cambia todas las
  pantallas y es trabajo del objetivo 6 del roadmap; la confirmación de CA1 lo usa tal como está.

## Decisiones
| Fecha | Tipo | Pregunta | Decisión | Fuente |
|---|---|---|---|---|
| 2026-11-20 | diseño | Ciclo de candidatos | Sin exploración (`exploration: none`): componentes del formulario de paciente y validación en pantalla del usuario | usuario (aprobó la spec 054 con esa propuesta, 2026-11-20) |
| 2026-11-20 | diseño | Acción principal del aviso de duplicado | «Ver ficha» como principal y «Registrar de todos modos» como secundaria: lo recomendable es no duplicar | agente (supuesto, para validar al aprobar el acta) |
| 2026-11-20 | diseño | ¿El aviso dice qué dato coincide? | No: texto genérico («el teléfono o la fecha de nacimiento») para no confirmar datos del otro paciente (CA9) | agente (supuesto) |
| 2026-11-20 | diseño | Varias coincidencias | Hasta 3 nombres con su enlace; el resto como «y N más» | agente (supuesto) |
| 2026-11-20 | diseño | Mensaje de éxito con contraste insuficiente (DS1, DS7) | Se usa tal como está; su corrección es del objetivo 6 | agente (supuesto) |
| 2026-11-20 | diseño | Contraseña escrita en un alta sin correo | La ayuda dice que solo se usa con correo; si se escribe sin correo no se crea (CA1). Cómo se trata al guardar lo decide `/plan` | agente (supuesto) |
| 2026-11-20 | diseño | Aprobación del acta | Aprobada, con los supuestos de este cuadro confirmados («Ver ficha» como acción principal, texto genérico, hasta 3 coincidencias, mensaje de éxito sin tocar) | usuario |
