---
created: 2026-10-20
options: 2        # marca (logo) y paleta rosa cerradas
---

# Brief de diseño de Dentissa — página de inicio pública

Respuestas de la entrevista de diseño (Ronda 4b) de `/design-spec 038`. Es la primera pantalla
pública de presentación; el sistema extraído (`system.md`) solo cubre la app de gestión. Lo usan las
opciones y `creative-direction.md`; no se edita después de elegir (los cambios van al sistema).

## Objetivo
Que quien busca dentista en Lázaro Cárdenas vea **la consulta real** antes de llegar, entienda qué
servicios hay, dónde está y cuándo abre, y cree su cuenta para agendar en línea (algo que casi
ninguna clínica de la zona ofrece). Debe sentirse tranquila, cercana y fiable; sin presión
comercial, pensando también en quien llega con miedo o con dolor.

## Público y contexto de uso
Adultos de 25–55 años que agendan para sí o para su familia, padres de niños y personas mayores.
Llegan sobre todo desde el móvil (búsqueda local, mapas, redes, WhatsApp), a veces en la calle, con
sol y datos móviles variables: texto grande, contraste alto, la acción principal al alcance del
pulgar y una página ligera (`nicho.md` → Público; la proporción móvil/escritorio es un supuesto).

## Marca y color
Cerradas: logo de la consulta «Melissa López N. · Odontología integral» (`public/images/logo-main.png`
sobre claro; `logo-icon.png` es la versión blanca) y la paleta rosa de `system.md` (`--p400`
`#D4537E`, `--p500` `#B83C65`, `--p600` `#993556`, fondo `--bg` `#FDF5F8`). Se evitan el azul o
blanco clínico y el verde menta (cliché del sector).

## Tipografía y modo
Las opciones proponen una familia de marca con motivo (legibilidad a tamaño grande en móvil y con
sol, tono cercano, acentos del español), servida desde la propia app y no desde Google (DS9). Si se
elige, entra en el sistema. Solo modo claro, como la app.

## Nicho
Investigación en `nicho.md` (completa). Territorio elegido: **A · La consulta por dentro**.

## Referencias
- Gustan: ninguna en especial; las opciones salen del territorio (decisión del usuario).
- No gustan: los clichés del sector de `anti-cliches.md`.

## Concepto firma y clichés del sector
- Concepto firma: la página es un recorrido por la consulta en orden de llegada: portada con una
  foto real, servicios como placas de puertas, galería y la calle (ubicación). Viene del territorio A.
- Clichés del sector a evitar: los 7 de `anti-cliches.md` → sector.

## Movimiento y densidad
Sobrio, con un solo momento firma y microinteracciones discretas, sin entradas al hacer scroll en
cada sección. Densidad baja: texto de lectura cómoda, mucho aire y una idea por sección.

## Librería de componentes
Ninguna: CSS propio con los tokens de `system.md`, como el resto de la app (sin Tailwind ni CDN).

## Contenido real para las opciones
- Textos de la página del colaborador (`landing/index.blade.php`, `3c8202b`): «Tu sonrisa, nuestra
  prioridad.», «Gestiona tus citas, consulta tu historial y mantén tu salud dental al día…»,
  «Agendar mi primera cita», «Iniciar sesión», «Nuestra Clínica», «Encuéntranos fácilmente».
- Servicios activos del catálogo de prueba (nombre y descripción, sin precio).
- Contacto de la spec 038 CA4: Av. Insurgentes 450, Col. Centro, Lázaro Cárdenas, Mich., C.P. 60950 ·
  753 000 0000 · Lunes a viernes 9:00–20:00 · Sábados 9:00–14:00.
- Galería: en las opciones, recuadros de foto con su descripción real de ejemplo («Sala de espera»,
  «Consultorio 1»), marcados como muestra hasta que el administrador suba las fotos.

## Decisiones
| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-10-20 | implícita | Nivel de accesibilidad y toque mínimo | WCAG 2.1 AA, toque ≥ 44 px (`--touch-min`) | `docs/constitution.md` P9 · `system.md` |
| 2026-10-20 | diseño | Marca y paleta | Logo y rosa del sistema, cerrados → 2 opciones | usuario |
| 2026-10-20 | diseño | Tipografía | Proponer una familia de marca con motivo, servida por la app | usuario |
| 2026-10-20 | diseño | Modo | Solo claro | usuario |
| 2026-10-20 | diseño | Movimiento | Sobrio, un momento firma | usuario |
| 2026-10-20 | diseño | Territorio | A · La consulta por dentro | usuario |
| 2026-10-20 | diseño | Clichés del sector | Los 7 propuestos | usuario |
| 2026-10-20 | diseño | Tono | «Tú» cercano y tranquilo | usuario |
| 2026-10-20 | supuesto | `public/images/cover-bg.jpg` es una foto de banco (licencia desconocida, uniforme azul) | No se usa; la portada es la primera foto de la galería y, sin fotos, una portada tipográfica | `anti-cliches.md` (foto de stock) · territorio A |
