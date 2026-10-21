---
spec: 039-galeria-trabajos
status: approved         # draft | approved (solo el usuario aprueba)
exploration: none
system_version: 1.6.0
created: 2026-10-20
updated: 2026-10-20
---

# Diseño · 039 Galería de trabajos

## Dirección elegida

Sin composiciones propias: la galería se diseñó junto con la página de inicio en `/design-spec 038`
(opción B, ronda 2), para que la página se pensara entera. Todo el detalle (composición, componentes,
cambios al sistema, movimiento, accesibilidad y anti-clichés) está en
[../038-pagina-inicio/design/history/ronda-2/design.md](../038-pagina-inicio/design/history/ronda-2/design.md) (archivado el 2026-10-21, cuando la 038 se rediseñó en la línea de la landing del colaborador, opción C de la ronda 3; esta spec se adaptará a esa línea en su propio `/design-spec`), y las pantallas en
`../038-pagina-inicio/design/opcion-b.html`.

- **Lo que aporta esta spec:**
  - el carrusel de trabajos de la página de inicio (foto 7 · panel 5, sin avance automático), con
    el momento firma **M-1 «La hoja de la puerta»**;
  - la fila deslizable sin JavaScript;
  - el mosaico 5:7 de `/galeria`;
  - la gestión del admin, con la pregunta del paciente, la casilla de consentimiento y la tarjeta
    de foto con su declaración.

## Pantallas

| Pantalla y estado | Ancho menor | Ancho mayor | Criterios |
|---|---|---|---|
| Carrusel en la página de inicio | `opcion-b.html#inicio-m` · `#inicio-320` | `#inicio-d` | CA1, CA5 |
| Sin fotos: la página de inicio no muestra el carrusel | `#inicio-solo038-m` · `#inicio-vacio-m` | — | CA3 |
| Carrusel sin JavaScript | `#inicio-sinjs-m` | — | CA4 |
| `/galeria` y vacía | `#galeria-m` · `#galeria-vacia-m` | `#galeria-d` | CA2 |
| Gestión, subida sin consentimiento y quitar | `#g-galeria-m` · `#g-galeria-error-m` · `#g-quitar-m` | `#g-galeria-d` | CA8–CA11 |

## Cambios a incorporar al sistema

Los de la 038 que esta spec necesita y aún no estén aplicados: `--foto-ratio`, `--thumb-w`,
`--duration-firma`, `--ease-puerta` y `--green-texto`, más los componentes «Carrusel de trabajos»,
«Mosaico de `/galeria`», «Pregunta del paciente» y «Tarjeta de foto en la gestión».

## Decisiones

| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-10-20 | diseño | ¿Composiciones propias? | No: diseñada en `/design-spec 038` (opción B, ronda 2) | usuario |
| 2026-10-20 | diseño | ¿Se aprueba este documento? | Aprobado | usuario |
