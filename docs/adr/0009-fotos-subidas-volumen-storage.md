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
  2026-11-07. Cada subida se decodifica, se endereza según la orientación EXIF, se recorta al centro
  a 4:3, se reduce a 1600 × 1200 como mucho y se vuelve a codificar en WebP, bajando la calidad hasta
  que pesa 500 kB o menos. Nunca se guarda el archivo original: lo que se publica son píxeles
  recodificados, sin metadatos.
- **Almacenamiento:** un disco `galeria` de Laravel en `storage/app/galeria/`, dentro del volumen
  `storage`, que persiste entre despliegues. El nombre de cada archivo es el UUID de su fila; la
  ruta se construye siempre desde la base, nunca desde la petición.
- **Entrega:** la aplicación sirve cada foto por `GET /galeria/fotos/{foto}`, con el UUID como
  parámetro, `Content-Type: image/webp`, `X-Content-Type-Options: nosniff` y
  `Cache-Control: public, max-age=3600`. No se usa `storage:link` porque `web` no monta el volumen.
  La dirección no lleva extensión, así que Cloudflare no la guarda en caché por defecto. Al quitar
  la foto se borran la fila y el archivo, y la dirección responde 404.
- **Copias:** las fotos no entran en las copias por ahora (decisión del usuario, 2026-11-07). La base
  sí guarda las declaraciones de consentimiento. Queda como riesgo en `docs/deployment.md` y en el
  roadmap.

## Alternativas consideradas
- **Imagick:** más formatos, pero muchos más paquetes del sistema y más superficie de
  vulnerabilidades. Descartada por el usuario.
- **Disco `public` con `storage:link` servido por nginx:** obligaría a montar el volumen en `web` y a
  separar los archivos públicos de los logs y las sesiones que hay en el mismo volumen. Para 12 fotos
  no compensa.
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
