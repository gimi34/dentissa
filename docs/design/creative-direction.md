# Dirección creativa de Dentissa — página de inicio pública

Decisiones fijadas con el usuario a partir de `brief.md` (spec 038). Cambiarlas requiere petición
explícita; las skills la leen antes de diseñar cualquier pantalla pública. La app de gestión sigue
el sistema extraído (`system.md`).

## Estética
- Serena y concreta: lo que se ve es la consulta real, sin decorado.
- Cálida dentro del rosa de la marca; aire generoso y lectura cómoda en el móvil.
- Ordenada como la señalética de una consulta: rótulos claros, formas rectangulares de esquinas
  suaves, líneas finas.
- Referencias de sensación: ninguna con nombre (decisión del usuario); manda el territorio de
  `nicho.md`.

## Identidad
- Territorio: **A · La consulta por dentro** — «Así es la consulta antes de que llegues».
- Materiales del oficio que lo sostienen: la placa de la consulta y el aviso de la puerta (horario,
  teléfono), la señalética de puertas y las fotos reales de los trabajos hechos dentro (specs 039 y
  040; ronda 2 de `/design-spec 038`).
- Tono de textos e imagen: sereno, en «tú», primera persona del plural para la clínica; fotos con
  luz natural, sin filtros ni retoque excesivo.

## Concepto firma
**La puerta de la consulta** (opción B, elegida en la ronda 1 y ajustada en la ronda 2): la página
empieza en la placa de la consulta, con el logo, el título, «Agendar mi primera cita» y el aviso de la
puerta (horario y «Llamar»). Dentro se recorre lo que hay detrás de la puerta: quiénes somos, las
puertas de los servicios, las promociones como avisos y los trabajos. El momento firma es el
carrusel de trabajos: cada foto entra como una hoja de puerta que se desliza, solo cuando el
visitante cambia de foto.

## Color y tipografía
- El rosa es color de uniforme y de señalética: acentos y placas, no fondos enormes. Fondo `--bg`,
  superficies blancas, texto oscuro; `--p600` para bordes de placa y foco.
- Una familia de marca con motivo para títulos (la eligen las opciones) y una de lectura; servidas
  por la app. Nada de fuentes de moda sin motivo.

## Movimiento
- Intensidad: sobria; un único momento protagonista por pantalla como máximo.
- Carácter: preciso y suave, como una puerta que se abre: nada rebota.
- Paleta: momento firma en el directorio de puertas (lo concretan las opciones);
  microinteracciones de color y hundimiento de 1 px en botones y enlaces; sin entradas al hacer
  scroll; sin transiciones de página.

## Superficies y fondos
- Fondo `--bg` liso; secciones separadas por cambio de superficie (blanca / `--bg`) y líneas finas,
  no por degradados.
- Placas: relleno blanco, borde fino `--p600`, esquinas suaves con los radios del sistema.
- Tratamiento de imagen: fotos reales en proporción fija y recorte centrado, con borde fino, sin
  filtros; texto nunca encima de una foto sin una superficie que garantice el contraste.

## Recursos propios
- La placa de la consulta (logo, título y aviso de la puerta) como portada.
- El índice de puertas para las categorías de servicios.
- Las promociones como avisos de la puerta, con su vigencia.
- La placa de la dirección sobre el mapa.

## Prioridades ante conflicto
- Accesibilidad y rendimiento ganan sobre cualquier efecto visual.
- Con `prefers-reduced-motion: reduce`, versión estática igual de cuidada.
- En táctil, los efectos que dependen del cursor tienen alternativa.
