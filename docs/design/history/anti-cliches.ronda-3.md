# Clichés prohibidos en Dentissa

Si una opción o una pantalla incluye alguno, se sustituye por la alternativa antes de aprobarla.
Lista del tipo `fullstack` (web) más los clichés del sector dental (`nicho.md`, confirmados por el
usuario el 2026-10-20 en `/design-spec 038`). Revisada en la ronda 3 de `/design-spec 038`
(2026-10-21, motor creativo de AI-DD 1.16.0): seis filas nuevas al final y tres alternativas
ajustadas a la opción C «La línea de abound», que el usuario eligió.

| Cliché | Alternativa en este proyecto |
|---|---|
| Lorem ipsum, cifras, testimonios o logos inventados | Solo contenido real (spec, roadmap, datos de prueba realistas) |
| Gradiente morado-rosa de plantilla | Gradientes dentro de la paleta del proyecto, o ninguno |
| Componentes de la librería sin personalizar (aspecto de plantilla) | Tokens propios aplicados a la librería |
| Estado vacío en blanco o solo "No hay datos" | Estado vacío que explica qué falta y ofrece la acción |
| Error sin salida ("Algo salió mal") | Qué pasó, que no se perdió nada y cómo reintentar |
| Animaciones en todo sin jerarquía | Un momento protagonista por pantalla; el resto sobrio |
| Estado transmitido solo por color | Color + texto o icono con nombre accesible |
| Tríada de tarjetas icono + título + texto para presentar la empresa o las ventajas | Una forma propia de la idea: texto corrido, una pieza destacada o datos reales |
| Fuente del sistema como tipografía de marca | Familia elegida e incrustada |
| Mismo esqueleto con otra piel en cada opción | Composición distinta de la pantalla principal |
| Fondos de moda sin relación con la idea (manchas difusas, degradado de malla, vidrio esmerilado, grano en toda la página) | Superficies que salen del territorio, o color sólido elegido a propósito |
| Todo entra con fundido al hacer scroll, parallax, contadores que suben | Paleta de movimiento con un protagonista y microinteracciones sobrias |
| Copiar la estética de un competidor concreto | Estudiar el patrón del sector (`nicho.md`) y resolverlo con el territorio propio |
| Hero genérico con ilustración stock y "Bienvenido a X" | La tarea principal del usuario visible desde el inicio |
| Grid de cards idénticas sin jerarquía | Elemento destacado y variaciones de tamaño |
| Dashboard de métricas decorativas | Solo indicadores que llevan a una decisión |
| Foto de stock de sonrisa perfecta o persona sonriendo en la portada | Una foto real de la consulta cuando exista. Mientras tanto (ronda 3, riesgo asumido por el usuario): una foto libre acreditada de dentistas trabajando, sin rostros reconocibles ni sonrisas de anuncio, bajo el velo de la marca y nunca presentada como el equipo |
| Azul o blanco clínico y verde menta | El rosa propio como color de uniforme y señalética, sobre `--bg` y blanco, con `--p600` de acento oscuro |
| Iconos de muela y de brackets genéricos | Un icono dibujado en código para cada categoría real del catálogo, con trazo fino desde el instrumental o la pieza que trata (ronda 3); la muela solo para las categorías sin icono propio |
| Cuadrícula de tarjetas icono + título + texto para los servicios | Ronda 3: una tarjeta por categoría real con su número de tratamientos («N tratamientos»), no ventajas inventadas ni descripciones de relleno; la tarjeta es un enlace entero a su sección de `/servicios` |
| Promociones, descuentos y precios en cintillo, contadores de tiempo o colores de oferta | Sin precios en el catálogo (spec 038); las promociones van en una sección sobria con título, condiciones y vigencia visibles (spec 040, decisión del usuario del 2026-10-20) |
| Carrusel de reseñas y logos de aseguradoras o patrocinadores | Nada inventado: sin reseñas mientras no existan; la confianza la dan las fotos reales, la dirección y el horario |
| Lenguaje de cadena («+60 clínicas», mapa del país) | Una sola dirección, un mapa, «Cómo llegar» y el horario como el aviso de la puerta |
| Página quieta (AI-DD 1.16.0) | Algo vive en la página: la hoja de «Sobre nosotros» que sube sobre la portada, la franja del horario, las entradas por sección y la pastilla «¿Abierto ahora?» |
| Idea de categoría (cualquier clínica podría firmarla) | La idea sale de lo que solo tiene esta consulta: su horario real, su dirección, su nombre y la agenda en línea (prueba del competidor) |
| Secciones delgadas (un título y dos líneas por pantalla) | Cada sección con su contenido completo: siete tarjetas con su número, ubicación con mapa grande y datos, pie con columnas y el nombre |
| Primera pantalla distinta seguida de plantilla | Cada sección cambia de forma (portada a sangre, hoja, rejilla, franja, 3:2, pie con la marca) y el horario vuelve hasta el pie |
| Pantalla de carga o portada que espera a una animación | La portada se ve terminada al cargar; las entradas solo afectan a lo que está por debajo y, sin JavaScript, todo está visible |
| Scroll secuestrado (la rueda mueve otra cosa) | El desplazamiento es nativo: la escena de la hoja es `position: sticky` y ninguna animación captura la rueda ni el gesto |
| Marquesina decorativa sin idea | Una sola franja, con el horario real; se detiene con el puntero, con su botón de pausa (WCAG 2.2.2) y con movimiento reducido |
