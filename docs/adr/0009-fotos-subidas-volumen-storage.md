---
id: 0009
status: accepted
date: 2026-11-07
---

# ADR 0009 · Fotos subidas: recodificadas con GD, guardadas en el volumen `storage` y servidas por la aplicación

## Contexto
La spec 039 (galería de trabajos) es la primera que guarda archivos que sube un usuario: fotos JPG,
PNG o WebP de hasta 5 MB, como mucho 12, que se publican en `/` y `/galeria`. Algunas muestran la
boca de pacientes (datos de salud publicados con consentimiento, CA9). La versión que descarga un
visitante no puede conservar los datos de la cámara (CA14), cada foto publicada pesa como mucho
500 kB (RNF), y una foto quitada deja de poder abrirse por su dirección (CA10).

En producción, `app` y `scheduler` montan el volumen `storage` (`/var/www/storage`) y `web` (nginx)
solo sirve los archivos públicos de la imagen. El PHP de las imágenes tiene GD, pero compilado sin
JPEG ni WebP. Las copias cifradas (`deploy/respaldar.sh`, ADR 0007) copian solo la base de datos.
Proyecto académico, sin presupuesto.

## Decisión
- **Procesado:** GD, compilado con JPEG y WebP en los dos Dockerfiles (`libjpeg62-turbo-dev`,
  `libwebp-dev`, `docker-php-ext-configure gd --with-jpeg --with-webp`). Decisión del usuario,
  2026-11-07. Cada subida se decodifica. Después, con un solo remuestreo, se recorta al centro a 4:3
  y se reduce a 1600 × 1200 como mucho; luego se endereza según la orientación EXIF, para no tener
  nunca dos copias de la imagen completa en memoria. Se vuelve a codificar en dos WebP:
  - el grande, que baja la calidad hasta pesar 500 kB o menos y, si con calidad 52 no llega, reduce
    las medidas;
  - el pequeño, de 800 px de ancho como mucho, con 60 kB o menos como objetivo, que es lo que piden
    las tarjetas y el carrusel (decisión del usuario, 2026-11-08, /analyze 039 A6).

  Nunca se guarda el archivo original: lo que se publica son píxeles recodificados, sin metadatos.
- **Almacenamiento:** un disco `galeria` de Laravel en `storage/app/galeria/`, dentro del volumen
  `storage`, que persiste entre despliegues. Los archivos se llaman `<uuid>-1600.webp` y
  `<uuid>-800.webp`, con el UUID de su fila. La ruta se construye siempre desde la base y desde un
  tamaño de una lista cerrada, nunca desde la petición.
- **Entrega:** la aplicación sirve cada foto por `GET /galeria/fotos/{foto}/{ancho}`, con el UUID y
  `800` o `1600` como parámetros. Cabeceras:
  - `Content-Type: image/webp` y `X-Content-Type-Options: nosniff`;
  - `Cache-Control: no-cache` con `ETag`: se revalida en cada visita y responde 304 si no cambió
    (decisión del usuario, 2026-11-08, /analyze 039 A1). La ruta va sin el middleware
    `PreventBackHistory`, que pisaría esa cabecera;
  - `X-Robots-Tag: noindex` en las fotos con paciente (decisión del usuario, 2026-11-08, A10).

  No se usa `storage:link` porque `web` no monta el volumen. La dirección no lleva extensión y la
  respuesta lleva `no-cache`, así que Cloudflare no la guarda en caché. Al quitar la foto se borran la
  fila y los dos archivos, y la dirección responde 404 desde la siguiente petición.
- **Copias:** las fotos no entran en las copias por ahora (decisión del usuario, 2026-11-07). La base
  sí guarda las declaraciones de consentimiento. Queda como riesgo en `docs/deployment.md` y en el
  roadmap.

## Alternativas consideradas
- **Imagick:** más formatos, pero muchos más paquetes del sistema y más superficie de
  vulnerabilidades. Descartada por el usuario.
- **Disco `public` con `storage:link` servido por nginx:** obligaría a montar el volumen en `web` y a
  separar los archivos públicos de los logs y las sesiones que hay en el mismo volumen. Para 12 fotos
  no compensa.
- **`Cache-Control: public, max-age=3600`:** una foto quitada se seguiría viendo hasta una hora en el
  navegador de quien ya la abrió, en contra de CA10 de la 039 (/analyze A1).
- **Una sola versión de 250 kB como mucho:** menos trabajo, pero con más pérdida de calidad en todas
  las pantallas (/analyze A6).
- **Guardar el original y recodificar al servir:** conservaría en disco los datos de la cámara
  (ubicación) y gastaría CPU en cada visita.

## Consecuencias
- Las imágenes de `app` llevan dos bibliotecas del sistema más. `trivy` las revisa en `/review` y en
  `/release`; si aparece una vulnerabilidad alta sin corrección, se trata como en EX2.
- Es necesario reconstruir el contenedor de desarrollo (`docker compose build app`) para que los tests
  con imágenes JPG falsas funcionen en local; la CI ya usa `setup-php` con GD completo.
- nginx tiene que aceptar cuerpos de hasta 6 MB (`client_max_body_size`; hoy rige el valor por
  defecto, 1 MB) en las dos configuraciones.
- Si se pierde el volumen `storage`, se pierden las fotos (no las declaraciones) hasta que el
  administrador las vuelva a subir.
