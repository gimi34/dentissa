# Crítica · opción B · ronda 4 (desde cero)

Inspector: 0 FAIL · 3 WARN (`titular-largo` a 320, 900 y 1440: el titular apilado en tres líneas es parte de la composición, junto al campo de las cifras). Capturas en `.ai/inspect/r4-b/` (no versionadas): claro, oscuro, reducido y sin JavaScript a 320, 390, 900 y 1440 px, y la tira del scroll a 320 y 1440.

| Pase | Nota 0–10 | Qué se ve (captura) |
|---|---|---|
| Idea | 8 | `1440-reducido`: «20 dientes de leche / 32 dientes permanentes» en el campo rosa junto al titular; en los servicios, la cifra y el arco cambian por etapa |
| Cinco segundos | 8 | Titular, «Agendar mi primera cita», horario y teléfono a la izquierda; el campo dice «toda la familia» |
| Jerarquía y escala | 9 | Cifras de hasta 260 px, titular de 104 px, una cosa dominante por tramo |
| Plenitud | 8 | Siete categorías repartidas por etapas con su número, dirección en grande, pie rosa con la marca |
| Composición | 8 | Campo, tinta, historia fijada, ubicación y pie: cada sección con su forma |
| Color | 8 | Comprometida: el campo `--p500` en portada y pie (~35 %) |
| Imágenes | 7 | El arco dibujado desde la numeración dental (20 temporales, 28 y 32 permanentes) |
| Movimiento | 8 | `film-1440-07`: la cifra pasa a 28 y el arco pierde las muelas del juicio; la tinta llena el primer párrafo palabra a palabra |
| Cosa viva | 8 | «¿Para quién es la cita?» lleva a su etapa y cambia cifra y arco; sin JavaScript, enlaces a cada etapa |
| Teléfono | 7 | La columna no se fija; filas de categoría en dos líneas a 320 |
| Reducido y sin JS | 8 | Arco completo y cifra 32; tinta plena |
| Accesibilidad | 8 | Blanco sobre `--p500` 5.42:1; etiquetas `--p100` sobre `--p500` ≥ 4.5:1; cifras decorativas ocultas; etapa en `aria-live` |

Tres problemas corregidos en la ronda 2: la tinta dejaba tenues las últimas palabras incluso llena (fórmula ×1.2); el arco se leía como un collar (dos arcadas separadas y coronas en lugar de óvalos); las filas de categoría apretadas a 320.
