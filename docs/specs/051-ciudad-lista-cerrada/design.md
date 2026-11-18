---
spec: 051-ciudad-lista-cerrada
status: approved            # draft | approved (solo el usuario aprueba)
exploration: none           # sin ciclo de candidatos (propuesta de la spec, aprobada con ella el 2026-11-18); motivo en "Resultado"
system_version: 1.19.0
created: 2026-11-18
updated: 2026-11-18
---

# Diseño · 051 Ciudad elegida de una lista cerrada

Hecho con `/design-spec 051` en modo sin exploración, sobre `docs/design/system.md` 1.19.0. Los
criterios están en la spec («Criterios de diseño», K1–K4). No hay `design/criterios.json` ni
candidatos.

## Resultado

**Sin diseño nuevo.** La 051 no crea pantallas ni cambia la composición de ninguna: el campo
«Ciudad» pasa de caja de texto a desplegable en el mismo sitio, con la misma etiqueta y el mismo
ancho. Cada uno de los cuatro formularios ya tiene un desplegable al lado, el de «Género», y la
ciudad copia su marcado y su estilo en cada formulario. Lo único nuevo es el aviso de la ciudad
antigua (CA10), que reutiliza el texto de ayuda del campo con un color que ya existe. Por eso no hay
ronda de candidatos: el usuario lo valida en pantalla durante `/implement`.

## Pantallas

| Pantalla | Estilos que ya usa el campo | Qué cambia en «Ciudad» |
|---|---|---|
| Registro con correo | `.page-register` de `auth.css`, con icono a la izquierda en `.field-wrap` | desplegable igual que «Género» (con su flecha y el icono de casa de hoy); sin placeholder «Ciudad de México» |
| Datos de contacto tras Google | los mismos de `auth.css` | igual que el registro con correo |
| Perfil del paciente | `.field` y `.field-hint` del layout de la app | desplegable igual que «Género»; aviso de CA10 debajo |
| Alta y edición de paciente | `.form-paciente` (foco del sistema, spec 035), `.field-error` y `.error-msg` | desplegable igual que «Género» y «Tipo de sangre», con su error; aviso de CA10 debajo, solo en la edición |
| Directorio de pacientes | filtro `select.field` de hoy | sin cambio visual: solo cambian las opciones (CA7) |

## Componentes

**Desplegable de ciudad** (el desplegable de cada formulario, con opciones nuevas):
- **Opciones:** «Sin indicar» primero (valor vacío); grupo «Michoacán» y grupo «Guerrero», cada uno
  con su rótulo de grupo nativo y sus municipios en orden alfabético; «Otro lugar» al final, fuera de
  los grupos.
- **Reposo y foco:** los del desplegable vecino en ese formulario; ningún valor nuevo.
- **Nombre largo:** el más largo, «Coahuayutla de José María Izazaga», se corta dentro del campo
  sin ensanchar el formulario a 320 px (K3); en la lista abierta se lee entero (la dibuja el
  navegador).
- **Error (CA5):** «Elige una ciudad de la lista», con el mismo patrón de error que el resto de los
  campos de ese formulario: en el alta y la edición, `field-error` + `.error-msg` con
  `aria-invalid` y `aria-describedby`; en el perfil, `.field-hint field-hint-error`; en el registro,
  `.error-msg` de `auth.css`.

**Aviso de ciudad fuera de la lista (CA10)** (perfil y edición de paciente):
- Al principio de la lista, antes de «Sin indicar», una opción elegida con el valor guardado y el
  sufijo « (no está en la lista)».
- Debajo del campo, el texto «La ciudad guardada «<valor>» no está en la lista: elige una o deja
  «Sin indicar»», con `.field-hint` en color `--text` (21:1 sobre `--white`), enlazado al campo con
  `aria-describedby`. No es rojo: no es un error y el formulario se puede guardar así.
- Si hay además un error del campo, el error va primero y los dos quedan en `aria-describedby`.

## Cambios a incorporar al sistema

| Cambio | Modo | Dónde |
|---|---|---|
| Variante «Aviso de campo» de `.field-hint`: texto en `--text` (no `--text-light`, que no llega a 4.5:1), enlazado con `aria-describedby`, para avisos que no son errores | fijo | `system.md` → «Campo de formulario» y `system.html` |

Versión: la siguiente a la vigente cuando se fusione (1.20.0 si nadie la toma antes; si la 050 sube
el sistema antes, se renumera).

## Movimiento
Ninguno.

## Accesibilidad
- La etiqueta «Ciudad» sigue asociada al campo (`for`/`id`); en el perfil hoy no lo está y se
  asocia al tocarlo.
- Con teclado: Tab llega al campo, las flechas recorren las opciones y teclear las primeras letras
  salta al municipio (K4); es el comportamiento del desplegable nativo, sin JavaScript.
- El error y el aviso son texto, no solo color, y el lector de pantalla los lee al llegar al campo.
- Foco visible: en el alta y la edición, el del sistema (`.form-paciente`); en el registro y el
  perfil, el de su formulario. Si `/plan` comprueba que el de esos dos no llega a 3:1, el campo de
  ciudad lo corrige al tocarlo (constitución P9, «Código previo»).
- Objetivo de toque ≥ 44 px a 320 y 390 px: el del desplegable vecino (`movil.css` ya lo fija para
  `.field` y `select`).

## Decisiones
| Fecha | Tipo | Pregunta | Decisión | Fuente |
|---|---|---|---|---|
| 2026-11-18 | diseño | Ciclo de candidatos | Sin exploración (`exploration: none`): el desplegable vecino de cada formulario y validación en pantalla del usuario | usuario (aprobó la spec 051 con esa propuesta, 2026-11-18) |
| 2026-11-18 | diseño | ¿Cómo se ve el aviso de la ciudad antigua (CA10)? | Texto de ayuda en `--text` debajo del campo, no en rojo, y la ciudad antigua como primera opción marcada «(no está en la lista)» | usuario (aprobó el acta, 2026-11-18) |
