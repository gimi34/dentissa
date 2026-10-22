# Crítica · opción C · ronda 2

Inspector: 0 FAIL · 2 WARN (avisos defendidos: `titular-largo` a 320 y 390 px — «Tu sonrisa, nuestra
prioridad.» a 40 px necesita tres líneas en 288 px de ancho; bajar más el tamaño rompe la escala de
portada, ≥ 4× el cuerpo). Capturas en `.ai/inspect/opcion-c/` (no versionadas): claro, oscuro,
reducido y sin JavaScript a 320, 390, 900 y 1440 px, y la tira del scroll a 320 y 1440.

| Pase | Nota 0–10 | Qué se ve (captura) |
|---|---|---|
| Idea: se reconoce el sujeto en la primera pantalla; pasa la prueba del competidor | 6 | `1440-claro`: la consulta en grande y «Abierto ahora · cierra a las 20:00» junto al botón. La foto es de banco: el sujeto se reconoce por el horario real y la marca, no por la imagen |
| Cinco segundos: qué es y qué hacer, sin leer el resto | 8 | Titular, «Agendar mi primera cita» y el estado de la consulta en la primera pantalla a 320 y 1440 |
| Jerarquía y escala: una cosa dominante por pantalla; titular ≥ ~4× el cuerpo | 8 | Titular de 96 px a 1440 en dos líneas; a 320, 40 px en tres (aviso defendido) |
| Plenitud: contenido completo; sin tramos vacíos ni secciones delgadas | 7 | Siete tarjetas con su número de tratamientos, ubicación completa, pie con el nombre a todo el ancho. «Sobre nosotros» es corto pero es el texto exacto de CA3 |
| Composición: ninguna sección vecina repite esqueleto; la idea llega al pie | 7 | Portada a sangre, hoja que sube, rejilla de tarjetas, marquesina, dos columnas y pie: cada sección cambia de forma; el horario vuelve en la marquesina, la ubicación y el pie |
| Color: la estrategia declarada se ve a escala de página | 7 | Comprometida: cabecera, velo, marquesina y pie rosas (`film-1440-*`) |
| Imágenes: reales o dibujadas desde el sujeto, grandes, de una familia | 5 | Una sola foto, grande, de banco (riesgo asumido por el usuario); los iconos están dibujados en código en --p600 |
| Movimiento: la jugada firma y las escenas se leen en la tira; entradas variadas, no un solo fundido | 7 | `film-1440-01/02`: la hoja sube sobre la foto; la marquesina en `film-1440-05`; títulos por línea, tarjetas una a una, mapa por máscara |
| Cosa viva: se entiende y se usa con el pulgar al ancho menor | 7 | La pastilla «Abierto ahora…» se lee a 320 en una línea; no requiere gesto (informa) |
| Teléfono: el ancho menor es su propia composición, no la de escritorio apretada | 7 | Velo uniforme, tarjetas en dos columnas, ubicación apilada, menú con hamburguesa |
| Reducido y sin JS: la composición terminada está diseñada y completa | 8 | `320-reducido` y `1440-sin-js`: todo visible; la hoja sube igual (es scroll nativo); la marquesina quieta |
| Accesibilidad: contraste, foco visible, orden de lectura | 8 | Velo al 82 % (blanco 6.41:1 en el peor caso); foco blanco sobre rosa; marquesina `aria-hidden` |

Tres problemas que más mueven la página: 1. el titular en tres líneas en el escritorio (corregido en
la ronda 2: el `h1` usa todo el ancho y queda en dos); 2. la pastilla de «Abierto ahora» partida en
dos líneas en el teléfono (corregido: `white-space: nowrap` y el estado en una columna); 3. la foto de
banco (no se corrige aquí: decisión del usuario, se cambia cuando haya una foto real).
