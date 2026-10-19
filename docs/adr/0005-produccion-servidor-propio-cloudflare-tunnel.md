---
id: 0005
status: accepted
date: 2026-10-03
---

# ADR 0005 · Producción en un servidor propio con Docker Compose y Cloudflare Tunnel

## Contexto
El proyecto no tenía ningún entorno publicado (RD1, RD3): solo existía el Compose de local, con la
base de datos y phpMyAdmin expuestos, imágenes sin fijar y el código montado desde el repo. La spec
030 pide un primer entorno de producción accesible desde internet, sin presupuesto. El usuario
tiene un equipo con Ubuntu Server 24.04 en su red local y un dominio gestionado en Cloudflare.

## Decisión
- Producción corre en el servidor del usuario con un Compose propio (`docker-compose.prod.yml`):
  `app` (php-fpm), `web` (nginx), `db` (MariaDB) y `cloudflared`.
- La entrada es solo el túnel saliente de Cloudflare (HTTPS en el borde). No se abren puertos en
  el router y ningún servicio publica puertos en el servidor. phpMyAdmin no existe en producción.
- Las imágenes se construyen con un Dockerfile de producción multietapa, con el código dentro de
  la imagen, sin dependencias de desarrollo ni secretos. Todas las imágenes base van fijadas por
  versión exacta, iguales en local y producción.
- La IP del cliente se toma de `CF-Connecting-IP` en nginx (`real_ip`, solo desde la red de
  Docker). Laravel confía en el proxy solo para el protocolo (`X-Forwarded-Proto`), no para la
  IP, el host ni el puerto (spec 030, D3).
- El servidor se prepara y se opera con scripts versionados en `deploy/`, que el usuario ejecuta
  por SSH. El código llega por un clon de GitHub con deploy key de solo lectura y `checkout` del
  tag. Los secretos viven solo en el servidor, en un archivo por servicio (`app.env`, `db.env` y
  `tunnel.env`) en `/opt/dentissa/entorno`, fuera del clon `/opt/dentissa/app` (spec 030, D15).
- Antes de cada despliegue se hace una copia de la base en `/var/backups/dentissa`. La vuelta atrás
  es el tag anterior más, si hace falta, la copia.

## Alternativas consideradas
- PaaS gratuito (Render, Railway, Fly.io): sin mantenimiento del servidor, pero con límites de
  uso, bases de datos de pago o temporales y datos fuera del control del usuario. Se descarta
  (decisión del usuario, 2026-10-03).
- VPS de pago: más disponible, pero con coste mensual. Se descarta por presupuesto.
- Abrir puertos del router con certificado propio (Let's Encrypt): expone la IP de casa y obliga a
  gestionar certificados y el router. Se descarta en favor del túnel.
- Montar el clon en los contenedores como en local: no reproducible y deja `.git` y `.env` al
  alcance de la app. Se descarta.

## Consecuencias
- Disponibilidad limitada a la del equipo y la conexión de casa; se acepta para un proyecto
  académico (`restart: unless-stopped`, reinicio ensayado).
- Un único entorno (sin staging, decisión del usuario): las specs se verifican en local y en
  producción tras su aprobación. Staging queda en el roadmap (RD1.c).
- El usuario mantiene el sistema operativo (actualizaciones automáticas de seguridad) y las
  versiones fijadas de las imágenes, que deben actualizarse de forma periódica.
- Cabeceras de seguridad quedan para la spec 031; alertas (OB6.b) y cifrado de copias (RS15.b), para la 032 (la 031 se dividió en dos; /analyze 031 A17).
