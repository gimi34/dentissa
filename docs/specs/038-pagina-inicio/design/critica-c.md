# Crítica · opción C · ronda 4 (desde cero)

Inspector: 0 FAIL · 3 WARN (`titular-largo` a 320, 900 y 1440: titular apilado a propósito sobre la placa). Capturas en `.ai/inspect/r4-c/` (no versionadas): claro, oscuro, reducido y sin JavaScript a 320, 390, 900 y 1440 px, y la tira del scroll a 320 y 1440.

| Pase | Nota 0–10 | Qué se ve (captura) |
|---|---|---|
| Idea | 9 | `film-1440-00`: la panorámica aparece con el barrido y su línea de luz bajo el titular; ninguna otra clínica abre con su radiografía |
| Cinco segundos | 8 | Titular, «Agendar mi primera cita», horario y teléfono sobre la placa |
| Jerarquía y escala | 9 | Titular de 110 px; la placa a todo el ancho; una cosa dominante por pantalla |
| Plenitud | 8 | 32 piezas dibujadas, siete cartas con su número, el mapa a pantalla completa, el pie con la numeración de la arcada |
| Composición | 9 | Placa, sección clara, placa viva + reparto, ventana, pie numerado: la idea llega al pie |
| Color | 9 | Empapada: la noche teñida de rosa ocupa casi toda la página; «Sobre nosotros» en claro como descanso |
| Imágenes | 9 | Todo dibujado desde la anatomía dental (raíces, pulpa, numeración FDI) en un solo lenguaje de trazo |
| Movimiento | 9 | `film-1440-08`: las cartas llegan en montón y se reparten; `film-1440-14/16`: la ventana se abre al mapa con la ficha encima |
| Cosa viva | 8 | Cada pieza es un botón con su nombre; el panel dice qué la cuida; en el teléfono, la placa se desliza a 880 px (piezas ≥ 44 px) |
| Teléfono | 7 | `320-reducido`: placa recortada en la portada, cartas a dos columnas, ficha de la dirección sobre el mapa apilado |
| Reducido y sin JS | 8 | Placa visible, rejilla terminada, mapa grande; sin JavaScript la tabla por grupos de piezas |
| Accesibilidad | 8 | Blanco 19:1 y tenue ≥ 9:1 sobre la noche; botón noche sobre `--p400` ≥ 4.5:1; foco blanco; piezas con `role="button"` y `aria-pressed` |

Tres problemas corregidos en la ronda 2: la placa viva ilegible en el teléfono (franja deslizable a 880 px); las cartas con su propia entrada chocaban con la escena del reparto; desbordes de 4 px a 320 (placa y última carta).
