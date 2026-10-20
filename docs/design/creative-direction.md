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
- Materiales del oficio que lo sostienen: fotografía real del consultorio (sillón, lámpara,
  recepción), señalética de puertas («Consultorio 1», aviso de horario), placa de la consulta.
- Tono de textos e imagen: sereno, en «tú», primera persona del plural para la clínica; fotos con
  luz natural, sin filtros ni retoque excesivo.

## Concepto firma
La página se recorre como una visita a la consulta en orden de llegada: la **entrada** (portada con
la primera foto de la galería o, sin fotos, la placa tipográfica de la consulta), el **directorio de
puertas** (los servicios como placas), la **sala** (galería) y la **calle** (ubicación, mapa y
horario como el aviso de la puerta). El momento firma se ve en el directorio de puertas.

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
- La placa de la consulta (logo + «Odontología integral») como portada sin fotos.
- El directorio de puertas para los servicios.
- El aviso de horario de la puerta para el bloque de contacto.

## Prioridades ante conflicto
- Accesibilidad y rendimiento ganan sobre cualquier efecto visual.
- Con `prefers-reduced-motion: reduce`, versión estática igual de cuidada.
- En táctil, los efectos que dependen del cursor tienen alternativa.
