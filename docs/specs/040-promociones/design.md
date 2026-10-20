---
spec: 040-promociones
status: draft            # draft | approved (solo el usuario aprueba)
exploration: none
system_version: 1.6.0
created: 2026-10-20
updated: 2026-10-20
---

# Diseño · 040 Promociones en la página de inicio

## Dirección elegida

Sin composiciones propias: las promociones se diseñaron junto con la página de inicio en
`/design-spec 038` (opción B, ronda 2). Todo el detalle está en
[../038-pagina-inicio/design.md](../038-pagina-inicio/design.md), y las pantallas en
`../038-pagina-inicio/design/opcion-b.html`.

- **Lo que aporta esta spec:**
  - el **aviso de promoción**: una lista sobria con la vigencia corta a la izquierda (decorativa) y
    a la derecha el título, la descripción, «Condiciones: …» y «Válida del … al …» en texto;
  - sin tarjetas, cintillos, contadores ni colores de oferta (`docs/design/anti-cliches.md`);
  - la gestión del admin, con el formulario y la lista con el estado de cada promoción en texto.

## Pantallas

| Pantalla y estado | Ancho menor | Ancho mayor | Criterios |
|---|---|---|---|
| Promociones en la página de inicio | `opcion-b.html#inicio-m` · `#inicio-320` | `#inicio-d` | CA1, CA7 |
| Sin promociones vigentes: la sección no aparece | `#inicio-solo038-m` · `#inicio-vacio-m` | — | CA3 |
| Gestión de promociones | `#g-promos-m` | `#g-promos-d` | CA4, CA5 |
| Gestión con errores | `#g-promos-error-m` | — | CA6 |

## Cambios a incorporar al sistema

Los de la 038 que esta spec necesita y aún no estén aplicados: `--green-texto` (estado «Se ve
ahora») y los componentes «Aviso de promoción» y «Estado de promoción», además de la regla de uso
«promociones sin cintillo, contadores ni colores de oferta».

## Decisiones

| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-10-20 | diseño | ¿Composiciones propias? | No: diseñada en `/design-spec 038` (opción B, ronda 2) | usuario |
