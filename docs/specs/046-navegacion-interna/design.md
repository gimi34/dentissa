---
spec: 046-navegacion-interna
status: approved            # draft | approved (solo el usuario aprueba)
exploration: none        # sin ciclo de candidatos (supuesto de la spec, aprobado por el usuario el 2026-11-12); motivo en "Resultado"
system_version: 1.15.0
created: 2026-11-12
updated: 2026-11-12
---

# Diseño · 046 Navegación interna

Hecho con `/design-spec 046` en modo sin exploración, sobre `docs/design/system.md` 1.15.0. Los
criterios están en la spec («Criterios de diseño», K1–K5). No hay `design/criterios.json` ni
candidatos.

## Resultado

**Sin pantalla nueva ni recomposición.** La spec lleva un componente que ya existe, las migas de pan
de la spec 033, a las pantallas de segundo nivel que no lo tienen o lo tienen hecho a mano, y lo
corrige en lo que pide la deuda 033/R6. La spec pide diseño directo (Decisiones, supuesto aprobado):
`/plan` coloca el componente y el usuario lo valida en pantalla durante `/implement` a 320, 390 y
1440 px.

| Puerta o KPI | Objetivo | Cómo se comprueba |
|---|---|---|
| K1 · Migas en las 18 pantallas de la tabla (CA1) | un solo bloque, visible sin desplazarse a 320, 390 y 1440 px | test sobre el HTML de cada pantalla + verificación del usuario a los tres anchos |
| K2 · Mismo aspecto (CA1) | el componente del sistema en las 18, sin `style=""` | test sobre el HTML |
| K3 · Enlace frente a elemento actual (CA4) | se distinguen sin color; contraste ≥ 4,5:1 los dos | test de las reglas y del contraste de los tokens + verificación del usuario |
| K4 · Camino largo a 320 px (CA10) | sin desplazamiento horizontal; enlaces ≥ 44 px de alto en el móvil | `capturar.mjs --medir` con un nombre largo + verificación del usuario |
| K5 · Títulos (CA8) | ninguno es «Dashboard» | test sobre el HTML |
| Puertas | contraste AA, foco visible, toque ≥ 44 px, sin desborde a 320 px | revisión contra `system.html` en `/review` y `capturar.mjs --medir` |

## Imágenes de muestra

Ninguna.

## Pantallas

Las 18 de «Pantallas y caminos» de la spec. En todas, las migas son el primer elemento del
contenido, bajo la barra superior y encima de avisos, tarjetas y formularios, como hoy en el detalle
de la cita.

| Pantalla y estado | Ancho menor (320/390) | Ancho mayor (1440) | Criterios |
|---|---|---|---|
| Caminos de dos elementos (p. ej. «Citas › Nueva cita») | una línea; el enlace con 44 px de alto | una línea | CA1, CA2 |
| Caminos de tres elementos (p. ej. «Pacientes › *paciente* › Expediente») | si no cabe, el camino pasa a la línea siguiente por el separador; el nombre largo parte por donde haga falta; nada se oculta ni se acorta con puntos suspensivos | una línea | CA1, CA10 |
| Reprogramar cita, se pueda o no reprogramar | migas encima de la tarjeta en los dos estados; «Volver al detalle de la cita» sigue en el aviso de la cita no reprogramable | igual | CA5 |
| Expediente | migas; desaparece «← Volver al paciente» de la cabecera, que conserva sus datos | igual | CA7 |
| Nuevo y editar servicio | migas y el título propio en la barra superior | igual | CA8 |
| Menú del panel en una pantalla de segundo nivel | la entrada de la sección, marcada como hoy la de la página actual, dentro del menú del móvil | igual en la barra lateral | CA9 |

## Componentes

- **Del sistema:** migas de pan (`.migas`, `.migas-actual`, spec 033), con su foco y su objetivo de
  toque en el móvil; barra superior con el título (`.topbar-title`); entrada marcada del menú
  (`.nav-item.active` con `aria-current="page"`); «Cancelar» de los formularios (`.btn-ghost`).
- **Nuevos:** ninguno. Las migas cambian como dice la tabla siguiente.

## Cambios a incorporar al sistema

Ningún token nuevo: todo sale de los tokens de 1.15.0. La primera tarea actualiza «Migas de pan» en
`system.md` y `system.html` (versión siguiente a la vigente al fusionar `main`; la 045, en curso,
puede tomar la 1.16.0).

| Token, componente o regla | Modo | Valor (menor) | Valor (mayor) | Motivo |
|---|---|---|---|---|
| Migas: estructura | fijo | una navegación con el nombre «Migas de pan» y, dentro, una lista ordenada; el último elemento marcado como página actual; los separadores «›» son decorativos y no se leen | igual | CA3; cierra 033/R6 |
| Migas: enlace | fijo | `--p600`, subrayado; al pasar el puntero, `--p800`; alto y ancho mínimos `--touch-min` | `--p600`, subrayado; al pasar el puntero, `--p800` | CA4: hoy `--text-muted` y `--text` valen lo mismo y el enlace no se distingue; el subrayado lo distingue sin color |
| Migas: elemento actual | fijo | `--text`, sin subrayado, sin enlace | igual | CA2, CA4 |
| Migas: separador | fijo | «›» en `--text`, con `--space-3` a cada lado (el `gap` de hoy) | igual | sin cambio de aspecto |
| Migas: camino largo | fijo | el camino puede pasar de línea (hoy ya lo hace en el móvil) y los nombres parten por cualquier punto | puede pasar de línea también en el escritorio | CA10 |
| Migas: dónde se usan | regla | toda pantalla de segundo nivel del panel las lleva como primer elemento del contenido; las pantallas del menú, no; el primer elemento es la entrada del menú de la sección | igual | CA1 |
| Título de pantalla | regla | toda pantalla del panel define su título; ninguna usa el título por defecto | igual | CA8; cierra la parte de títulos de DS15 para servicios |

## Movimiento

Ninguno.

## Accesibilidad

- **Contraste:** `--p600` (#993556) sobre `--bg` (#FDF5F8) da alrededor de 6,5:1 (calculado a mano,
  pendiente de fijar con el test de contraste de `/implement`); `--text` sobre `--bg` ya está medido.
- **Sin depender del color:** el enlace va subrayado y el elemento actual no.
- **Foco y toque:** el anillo de foco del sistema que ya tienen las migas; en el móvil, cada enlace
  con `--touch-min` de alto y de ancho.
- **Estructura:** las migas son una navegación con nombre, distinta de la del menú; no añaden
  encabezados. El elemento actual lleva la marca de página actual.
- **Teclado:** las migas van después del menú y de la barra superior y antes del contenido.
- **Decorativos ocultos:** los separadores.

## Decisiones

| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-11-12 | diseño | Ciclo de candidatos | Sin exploración (`exploration: none`): el componente de migas del sistema y validación en pantalla del usuario | usuario (spec 046, supuesto aprobado) |
| 2026-11-12 | diseño | Cómo se distingue el enlace del elemento actual (CA4) | Enlace en `--p600` y subrayado; elemento actual en `--text` sin subrayar | razonable (033/R6; mismo par de color que los enlaces de la 044) |
| 2026-11-12 | diseño | Camino largo a 320 px (CA10) | Pasa de línea y parte el nombre; no se acorta con puntos suspensivos, para no ocultar de qué paciente se trata | razonable (regla de la 035 para textos largos) |
| 2026-11-12 | diseño | Versión del sistema | La siguiente a la vigente cuando se fusione `main`, porque la 045 también lo cambia | razonable |
| 2026-11-12 | diseño | Aprobación del acta | Aprobada | usuario |
| 2026-11-12 | refinado | «Agendar cita» es una entrada del menú (hallado en /plan 046) | Sale de las pantallas con migas: quedan 18; el componente no cambia | usuario |
