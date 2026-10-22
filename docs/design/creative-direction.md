# Dirección creativa de Dentissa — páginas públicas

Decisiones fijadas con el usuario a partir de `brief.md` en la ronda 4 de `/design-spec 038`
(2026-10-21), que empieza el diseño desde cero: no parte de la landing del colaborador ni de las
rondas 1–3 (archivadas en `history/creative-direction.ronda-3.md` y en
`docs/specs/038-pagina-inicio/design/history/`). Cambiarlas requiere petición explícita; las skills
la leen antes de diseñar cualquier pantalla pública. La app de gestión sigue el sistema extraído
(`system.md`).

## Estética
- **Profesional y seria:** confianza por rigor; lo que se muestra es preciso, ordenado y verificable
  (horario, dirección, servicios reales), como un documento clínico bien hecho.
- **Moderna y con carácter:** no puede parecer otra clínica más; una idea propia reconocible en la
  primera pantalla y en todas las secciones.
- **Tranquila y cercana:** pensada para quien llega con miedo o con dolor; nada empuja ni grita.
- Referencias de sensación: ninguna (decisión del usuario: sin ejemplos ni base); las opciones salen
  del inventario del sujeto.

## Identidad
- Territorio: **lo deciden las opciones**. Se desarrollan los tres territorios nuevos de la ronda 4:
  1 · La panorámica (audaz), 2 · De 20 a 32 (firme) y 3 · Te lo explicamos antes (sobrio).
- Materiales del oficio que lo sostienen:
  - la radiografía panorámica, que está en el catálogo;
  - el odontograma de 32 piezas con su numeración FDI;
  - el arco dental como forma;
  - los 20 dientes de leche frente a los 32 permanentes («toda la familia»);
  - el ritual de explicar antes de tratar («qué vamos a hacer, cuánto dura y qué opciones tienes»);
  - el horario real y la cita en línea, que casi ninguna clínica de la zona ofrece;
  - el nombre de la doctora y el logo «Melissa López N. · Odontología integral».
- Tono de textos e imagen: «tú» cercano y tranquilo, primera persona del plural para la clínica;
  frases cortas y precisas, sin promesas comerciales.

## Concepto firma
Lo deciden las opciones: cada una desarrolla su territorio con su idea y su momento firma.

## Color y tipografía
- **El rosa de la marca es la base** (`--p400` `#D4537E`, `--p500` `#B83C65`, `--p600` `#993556`,
  `--p800` `#72243E`, `--p100`, `--p50`); cada opción propone los neutros, el acento oscuro y cómo
  ocupa el rosa la página. El logo se pinta por máscara en el color que toque.
- Nada de azul o blanco clínico ni verde menta.
- Tipografía: cada opción propone una familia de marca con motivo (legible a tamaño grande en el
  móvil y con sol, cifras claras para el horario, acentos del español), con licencia abierta y
  servida por la app; Lexend no es obligatoria.

## Modo y registro
- Pantallas por modo: persuadir: `/` · leer: `/servicios` (y la galería de la 039 y las promociones
  de la 040 deciden el suyo en su `/design-spec`).
- Registro máximo permitido: **audaz** (decisión del usuario). Las opciones cubren los tres
  registros: sobrio, firme y audaz.

## Movimiento
- Intensidad: hasta protagonista en la opción audaz; escenas de scroll por página en Persuadir:
  hasta 3; una jugada firma por pantalla como máximo.
- Carácter: preciso y suave, como un gesto clínico seguro: nada rebota ni se sacude.
- Paleta: jugada firma y escenas las deciden las opciones; entradas que cambian con el contenido;
  respuesta inmediata en cada control; sin transiciones de página.

## Superficies y fondos
- Estrategia de color: la declara cada opción (de `contenida` a `empapada`).
- Escena física: la persona busca dentista desde el móvil, a menudo en la calle con el sol de Lázaro
  Cárdenas o de noche en casa con dolor; el claro predomina, y un campo oscuro solo si la idea lo
  pide (la panorámica).
- Imágenes disponibles (decisión del usuario: mezcla):
  - dibujadas en código desde el oficio (odontograma, arco, panorámica, instrumental), en los colores
    de la paleta;
  - fotos reales de la consulta cuando existan, en huecos marcados hasta entonces;
  - la foto libre de la ronda 3 (Unsplash, acreditada) solo si una opción la necesita, nunca como el
    equipo de Dentissa.
- Separadores y cambios entre secciones: los decide cada opción, desde su idea.
- Tratamiento de imagen: un solo lenguaje de dibujo por opción (trazo, grosor y colores fijos).

## Recursos propios
- El odontograma FDI de 32 piezas y el arco dental, dibujados en código.
- El horario real y la cita en línea como diferenciador.
- El logo de la consulta.

## Prioridades ante conflicto
- Accesibilidad y rendimiento ganan sobre cualquier efecto visual.
- Con `prefers-reduced-motion: reduce`, versión estática igual de cuidada.
- En táctil, los efectos que dependen del cursor tienen alternativa.
