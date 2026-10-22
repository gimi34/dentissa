# Crítica · opción A · ronda 4 (desde cero)

Inspector: 0 FAIL · 2 WARN (`titular-largo` a 320 y 390: la escala mínima del titular no cabe en dos líneas en 288 px). Capturas en `.ai/inspect/r4-a/` (no versionadas): claro, oscuro, reducido y sin JavaScript a 320, 390, 900 y 1440 px, y la tira del scroll a 320 y 1440.

| Pase | Nota 0–10 | Qué se ve (captura) |
|---|---|---|
| Idea: se reconoce el sujeto en la primera pantalla; pasa la prueba del competidor | 7 | `1440-reducido`: el titular y, al lado, la hoja del plan con «Qué vamos a hacer · Cuánto dura · Qué opciones tienes», el texto aprobado de CA3 |
| Cinco segundos | 8 | Titular, «Agendar mi primera cita», horario y teléfono en la primera pantalla |
| Jerarquía y escala | 7 | Titular de 76 px en serif; números de apartado de 64 px; una sola cosa dominante por apartado |
| Plenitud | 6 | Siete fichas completas y ficha de datos con mapa; el margen izquierdo de cada apartado queda vacío a propósito (documento), lo que alarga la página |
| Composición | 7 | Portada en dos columnas, apartados numerados, fila de fichas, ficha de datos: la idea llega al pie (la firma) |
| Color | 7 | Contenida: rosa oscuro solo en filetes, números y botón |
| Imágenes | 5 | Sin imágenes: el dibujo es el propio documento (casillas, filetes); la más sobria |
| Movimiento | 6 | Casillas que se marcan, entradas por línea y la fila arrastrable; sin escenas (registro sobrio) |
| Cosa viva | 8 | «¿Qué te trae?» lleva a la ficha que toca y dice «Te orientamos a…»; sin JavaScript, la tabla |
| Teléfono | 8 | `320-reducido`: una columna, el plan bajo el titular, la fila con el dedo |
| Reducido y sin JS | 9 | Todo visible; las casillas ya marcadas |
| Accesibilidad | 9 | Contraste ≥ 7:1 en botones y textos; foco del sistema; motivos con `aria-pressed` y resultado en `aria-live` |

Tres problemas corregidos en la ronda 2: el titular en tres líneas en el escritorio (columna 8fr y 76 px: ahora dos); el horario de la ficha de datos heredaba el estilo de etiqueta; el aire vertical entre apartados (de 120 a 96 px).
