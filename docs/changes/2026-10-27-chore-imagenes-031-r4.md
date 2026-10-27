---
lane: chore
status: released             # open → done → released · escalated (→ escalated_to)
released: v0.11.1 (2026-10-27)
base: 9fad2af                # commit de partida; lo registra la skill al empezar (lo usa lane-check)
relates: []                  # specs cuyo comportamiento toca ([] si ninguna)
covers: []                   # fix: criterios de esas specs que el arreglo vuelve a cumplir
escalated_to: null           # NNN de la spec si el cambio se escaló al flujo completo
---

# Chore: imágenes de `cloudflared` y `mariadb` al día (031/R4)

## Problema
La excepción EX2 (avisos de `trivy image` en las imágenes de producción, RS18) vence el
2026-11-30. La spec 031 la renovó con un residuo pendiente (roadmap, 031/R4; `docs/security.md`,
RS18.a): refrescar `cloudflare/cloudflared` (en producción, 2026.10.0) a la versión más nueva con
al menos 7 días, refrescar `mariadb:10.11.19` a la reconstrucción de la misma etiqueta que corrige
sus avisos, volver a escanear y renovar o retirar EX2 según el resultado.

Al empezar (2026-10-27), en Docker Hub:
- `cloudflare/cloudflared`: 2026.10.1 (2026-10-15), 2026.10.2 (2026-10-20 09:27 UTC), 2026.10.3
  (10-21), 2026.10.4 (10-26) y 2026.10.5 (10-27). La más nueva con 7 días o más es la 2026.10.2.
- `mariadb:10.11.19`: la etiqueta se reconstruyó el 2026-10-17 (índice
  `sha256:0d0945c57f75b3a3b9ebe71aedaaad005fa5305054e8fca3d2e42a8c92b43b5e`) y otra vez el
  2026-10-27 a las 08:35 UTC (`sha256:d769e3796082…`, 0 días). Bajarla por etiqueta hoy trae la de
  0 días (historial en `docker-library/repo-info`, `repos/mariadb/remote/10.11.19.md`).

## Cambio
- `docker-compose.prod.yml` (ruta protegida, aprobada por el usuario el 2026-10-27):
  `cloudflare/cloudflared` 2026.10.0 → 2026.10.2. Notas de versión (`RELEASE_NOTES` del
  repositorio): 2026.10.1 sube Go a 1.27.2 (GO-2026-6600) y añade un endpoint de diagnóstico;
  2026.10.2 sube Go a 1.27.3 y la imagen distroless, y corrige que se perdiera el final de los
  mensajes más grandes que el búfer (TUN-10994). Sin cambios de configuración ni de opciones.
  Origen: `cloudflare/cloudflared` en Docker Hub (el mismo repositorio que ya se usaba), publicada
  el 2026-10-20 09:27 UTC (7 días; mínimo, 7). La 2026.10.3–10.5 no cumplen los 7 días.
  `desplegar.sh` no arranca ni para `cloudflared` (`docs/deployment.md`): al liberar, después del
  despliegue, se recrea a mano con `docker compose -f docker-compose.prod.yml up -d cloudflared`
  (corte del túnel de unos segundos).
- `mariadb:10.11.19`: sin cambios (decisión del usuario). Las reconstrucciones del 2026-10-17
  (`sha256:0d0945c5…`) y del 2026-10-27 (`sha256:d769e379…`) tienen los mismos 25 avisos altos y 1
  crítico, todos en el Go `stdlib` con que se compiló `gosu`; la del 2026-10-27 tiene 0 días.
- `docs/deployment.md`: versiones de «Plataforma e infraestructura» al día (`php:8.4.26-fpm`,
  `composer:2.10.3`, `mariadb:10.11.19`, `phpmyadmin:5.2.3` y `cloudflared` 2026.10.2), que se
  habían quedado en las de la spec 030.
- `docs/security.md`: EX2 con el escaneo del 2026-10-27 y renovada hasta el 2026-12-31 (decisión del
  usuario); RS18 y RS18.a con el resultado. `docs/roadmap.md`: 031/R4 hecho, con el pendiente
  siguiente (`cloudflared` 2026.10.4 o posterior y nuevo escaneo antes del 2026-12-31).

## Escalado
- Al empezar, con los archivos previstos: «Escalar: sí» por `docker-compose.yml` (ruta protegida).
  Al final no se toca: `mariadb` se queda igual.
- Sobre el diff real desde `9fad2af`: 5 archivos, 1 de código (`docker-compose.prod.yml`);
  «Escalar: no. El cambio cabe en el carril.» El cambio en la ruta protegida lo aprobó el usuario.

## Verificación
- `trivy image` 0.57.1 (`--severity HIGH,CRITICAL`, base de avisos del 2026-10-27):
  - `cloudflare/cloudflared:2026.10.0` (la de producción): 7 altas (libssl3, `klauspost/compress`,
    `golang.org/x/net` y 4 del Go `stdlib`). `2026.10.2`: 1 alta (`klauspost/compress`, corregida
    en la 1.18.7, que trae la 2026.10.4).
  - `mariadb:10.11.19` (las dos reconstrucciones): 25 altas y 1 crítica en `gosu` (Go `stdlib`).
  - `app` de `docker/prod/Dockerfile` construida en local sin caché y con `--pull`, como
    `desplegar.sh`: 122 altas y 4 críticas, ninguna con corrección publicada (118 y las 4 críticas
    «affected», 4 «fix_deferred»).
- `php artisan test` (contenedor efímero con la base `dentissa_db` local): 422 pasan, entre ellos
  `test_las_imagenes_de_produccion_tienen_version_exacta_e_iguales_a_local`.
- Sin cambios en lockfiles: `composer audit` y `npm audit` siguen como en la v0.11.0 (0 avisos).
- En el servidor (2026-10-27, el usuario): `mariadb:10.11.19` es
  `mariadb@sha256:0d0945c57f75b3a3b9ebe71aedaaad005fa5305054e8fca3d2e42a8c92b43b5e`, la
  reconstrucción del 2026-10-17 (10 días). `desplegar.sh` solo hace `--pull` de las bases de `app`
  y `web`; `up -d db` usa la imagen local, así que no la cambia por la de 0 días.
- `docker compose -f docker-compose.prod.yml config` no se puede resolver en el PC (los archivos de
  entorno viven en `/opt/dentissa/entorno`); la comprobación real es el despliegue en el servidor.
- Liberación v0.11.1 (2026-10-27, aprobada por el usuario con «desplegar v0.11.1 a producción»
  tras el CI en verde del PR #48; el usuario en el servidor): `desplegar.sh v0.11.1` en 355 s, copia
  cifrada `dentissa-20261027-162637.sql.gz.gpg`, sin migraciones y verificación interna en OK;
  `cloudflared` recreado a mano (`up -d cloudflared`) con la 2026.10.2 y `verificar.sh` en OK. A
  los 15 minutos: 0 líneas `ERR` en los logs de `cloudflared` y ningún aviso en el journal de
  `dentissa-vigilar`.

## Decisiones
| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-10-27 | usuario | ¿`cloudflared` 2026.10.2 ahora (ruta protegida `docker-compose.prod.yml`) o esperar a la 2026.10.4? | 2026.10.2 ahora | usuario |
| 2026-10-27 | usuario | `mariadb:10.11.19`: sus dos reconstrucciones tienen los mismos avisos y la de hoy no cumple 7 días | No tocarla; sus avisos siguen en EX2 | usuario |
| 2026-10-27 | usuario | EX2 no se puede retirar (`app` sin corrección, `gosu`, `klauspost/compress`) | Renovada hasta el 2026-12-31, con un pendiente nuevo en el roadmap | usuario |
| 2026-10-27 | supuesto | El texto anterior de EX2 daba por corregidos en la reconstrucción de `mariadb` del 2026-10-17 sus avisos de `gosu` | No lo estaban: se corrige el texto con el escaneo de hoy | `trivy image` del 2026-10-27 |
