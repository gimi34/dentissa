---
lane: chore
status: done                 # open → done → released · escalated (→ escalated_to)
base: 0075b14                # commit de partida; lo registra la skill al empezar (lo usa lane-check)
relates: []                  # specs cuyo comportamiento toca ([] si ninguna)
covers: []
escalated_to: null           # NNN de la spec si el cambio se escaló al flujo completo
---

# Chore: convivencia del entorno de la maqueta con el clon de `main`

## Problema
En el mismo equipo conviven dos clones del repositorio: `dentissa` (rama `main`) y
`dentissamaqueta` (rama `maqueta`). Los dos levantan el mismo `docker-compose.yml`, que fija
`container_name` (`dentissa_app`, `dentissa_nginx`, `dentissa_db`, `dentissa_phpmyadmin`) y los
puertos locales `8000`, `3306` y `8080`. Con uno en marcha, `docker compose up -d` en el otro falla
por nombre de contenedor y por puerto ocupados. El `/analyze 101` r1 (A4) lo encontró: el entorno
que deja convivir los dos clones lo creaba la T004 de la 101, después de la 020, que ya lo
necesita. Decisión del usuario (2026-10-27): un `/chore` previo en `maqueta` crea un
`docker-compose.override.yml` con contenedores `maqueta_*` y puertos locales propios, sin exponer
nada a la red; la 101 añade después solo TLS y el puerto 8443.

## Cambio
Solo configuración del entorno local y documentación. `docker-compose.yml` (ruta protegida) no se
toca:
- `docker-compose.override.yml` (nuevo, solo en `maqueta`; Compose lo carga solo): contenedores
  `maqueta_app`, `maqueta_nginx`, `maqueta_db` y `maqueta_phpmyadmin`; puertos reemplazados con
  `!override` (Compose v5.3.0): `127.0.0.1:8001:80`, `127.0.0.1:3307:3306` y
  `127.0.0.1:8081:80`; y `SESSION_COOKIE=maqueta-session` en el entorno de `app` (hallazgo
  colateral, ver Decisiones). Volúmenes y red no necesitan cambio: Compose les antepone el nombre
  del proyecto, que es el de la carpeta (`dentissamaqueta_dentissa_db_data`, etc.).
- `AGENTS.md` → "Rama `maqueta`": viñeta con el entorno propio, sus puertos y que un servicio
  nuevo con `container_name` o `ports` necesita su entrada en el override.
- `docs/deployment.md` → "Rama `maqueta`": viñeta "Entorno local propio" (contenedores, puertos,
  cookie, `APP_URL` del `.env` del clon y callback de Google); `updated` a 2026-10-28.

Pendiente fuera de este chore: la nota en la T040 de la 020 (el `scheduler` también va en el
override) la añade `/tasks 101 --fix` (A4). `docs/architecture.md` → "Despliegue" lo actualiza la
T090 de la 101.

Commit `chore(chore-convivencia-maqueta): entorno propio de la maqueta` en la rama
`chore/maqueta-convivencia`, con PR a `maqueta`.

## Escalado
- Al empezar: `lane-check --files docker-compose.override.yml AGENTS.md docs/deployment.md
  docs/architecture.md` → 4 archivos, 1 de código; "Escalar: no".
- Al cerrar, sobre el diff real desde `0075b14`: 4 archivos, 1 de código (el override); "Escalar:
  no". La cookie de sesión cambia de nombre solo en el entorno local de la maqueta; no toca la
  autenticación ni llega a `main` ni a producción.

## Verificación
2026-10-28, con Docker Engine 29.6.1 y Compose v5.3.0 en este equipo:
- `docker compose config`: válido; un solo puerto por servicio, todos en `127.0.0.1` (8001, 3307,
  8081); `container_name` `maqueta_*`; volúmenes y red `dentissamaqueta_*`.
- `docker compose up -d`: levanta los cuatro `maqueta_*`. El volcado se carga en `maqueta_db`;
  `php artisan migrate` aplica 5 migraciones; el arnés de tests (`bootstrap-test-db.sh` y
  `migrate --force` en `dentissa_test`) funciona.
- Los `dentissa_*` del clon de `main` quedan igual: mismo ID, fecha de creación y estado antes y
  después (`dentissa_db` en marcha en `127.0.0.1:3306` a la vez que `maqueta_db` en 3307; el resto,
  parados desde antes).
- `curl http://127.0.0.1:8001/login` → 200 con `Set-Cookie: maqueta-session=…`; phpMyAdmin en 8081
  → 200; 3307 acepta conexiones.
- `php artisan test` en `maqueta_app`: **422 pasan** (7110 aserciones), la misma cifra que la
  línea base de la ficha `2026-10-27-chore-imagenes-031-r4`. Sin código PHP tocado: Pint, semgrep
  y build no aplican; sin dependencias tocadas: sin SCA.
- `gitleaks` (imagen `zricethezav/gitleaks:v8.21.2`, como la CI) sobre el commit: sin hallazgos.
- `python .ai/bin/aidd.py validate` sin errores.
- `.env` del clon creado desde `.env.example` (sin versionar): `APP_KEY` generada, `APP_URL` en
  8001 y credenciales locales `root/root` de `docker-compose.yml`.
- Sin review con subagente (`/chore`: la validación es la review).

## Decisiones
| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-10-27 | decisión | ¿Quién crea el entorno que deja convivir los dos clones? (A4 del `/analyze 101` r1) | Un `/chore` previo en `maqueta`: override con contenedores `maqueta_*` y puertos locales, sin red; la 101 añade solo TLS y 8443 | usuario |
| 2026-10-28 | decisión | Hallazgo colateral: los dos clones usan la cookie `dentissa-session` en `localhost` y el navegador no separa por puerto; iniciar sesión en uno cierra el otro | `SESSION_COOKIE=maqueta-session` en el entorno de `app` del override, en este mismo chore | usuario |
| 2026-10-28 | decisión | Validar con Docker apagado y el clon sin `.env` | Arrancar Docker Desktop, crear el `.env` local, levantar los `maqueta_*` y correr la suite | usuario |
