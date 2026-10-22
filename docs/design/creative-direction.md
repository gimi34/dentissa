# Dirección creativa de Dentissa — página de inicio pública

Decisiones fijadas con el usuario a partir de `brief.md` (spec 038). Cambiarlas requiere petición
explícita; las skills la leen antes de diseñar cualquier pantalla pública. La app de gestión sigue
el sistema extraído (`system.md`).

## Modo y registro
Fijados en la ronda 3 de `/design-spec 038` (2026-10-21, motor creativo de AI-DD 1.16.0;
[diseño](../specs/038-pagina-inicio/design.md)):
- **Registro máximo de las páginas públicas: audaz.** Ninguna página pública pasa de ahí.
- **Página de inicio (038): persuadir · firme**, opción C «La línea de abound» con la ubicación de D.
  `/servicios` es de leer y sigue en sobrio, con la cabecera, el pie y los botones de C.
- La 039 (galería) y la 040 (promociones) eligen su registro en su `/design-spec`, dentro de este
  máximo.

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
**Desde la ronda 3 (2026-10-21): «La línea de abound».** La portada a sangre con una foto de la
consulta bajo el velo de la marca, la hoja de «Sobre nosotros» que sube sobre ella y el letrero del
horario convertido en una franja viva que dice si la consulta está abierta ahora y vuelve en la
ubicación y el pie. Lo que sigue describe la ronda 2, que la 039 y la 040 adaptarán a esta línea.

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
- Ronda 3 (página de inicio): una escena (la hoja que sube), una jugada firma (la franja del
  horario, con pausa), entradas por sección que cambian con el contenido y respuesta bajo la mano;
  un solo script propio y todo completo sin él.

## Superficies y fondos
- Fondo `--bg` liso; secciones separadas por cambio de superficie (blanca / `--bg`) y líneas finas,
  no por degradados.
- Placas: relleno blanco, borde fino `--p600`, esquinas suaves con los radios del sistema.
- Tratamiento de imagen: fotos reales en proporción fija y recorte centrado, con borde fino, sin
  filtros; texto nunca encima de una foto sin una superficie que garantice el contraste. En la
  portada de la ronda 3, esa superficie es el velo de la marca (`--velo-portada`), con el texto
  pequeño siempre sobre su tramo fuerte.

## Recursos propios
- La placa de la consulta (logo, título y aviso de la puerta) como portada.
- El índice de puertas para las categorías de servicios.
- Las promociones como avisos de la puerta, con su vigencia.
- La placa de la dirección sobre el mapa.

## Imágenes disponibles
- La foto libre de la portada: dentistas con mascarilla atendiendo a una paciente, de Jonathan Borba
  en Unsplash (Unsplash License), acreditada en `system.md` → «Imágenes». Es de banco: va bajo el
  velo y nunca se presenta como el equipo de Dentissa.
- El logo «Melissa López N. · Odontología integral» (`public/images/logo-icon.png`).
- Ninguna foto real de la consulta todavía: cuando las haya (039), sustituyen a la de banco.

## Prioridades ante conflicto
- Accesibilidad y rendimiento ganan sobre cualquier efecto visual.
- Con `prefers-reduced-motion: reduce`, versión estática igual de cuidada.
- En táctil, los efectos que dependen del cursor tienen alternativa.
