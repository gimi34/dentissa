---
lane: chore
status: done                 # open → done → released · escalated (→ escalated_to)
base: 4b95310                # commit de partida; lo registra la skill al empezar (lo usa lane-check)
relates: []                  # specs cuyo comportamiento toca ([] si ninguna)
covers: []                   # fix: criterios de esas specs que el arreglo vuelve a cumplir
escalated_to: null           # NNN de la spec si el cambio se escaló al flujo completo
---

# Chore: Symfony 7.4.23 y retirada de la excepción EX4

## Problema
La excepción EX4 de `docs/security.md` ignora en `composer audit` seis avisos de Symfony, publicados el
2026-11-04, y vence el 2026-11-11 (ficha `2026-11-05-chore-symfony-mime-7.4.22.md`). Los avisos son:

- `symfony/http-foundation` 7.4.20:
  - PKSA-24ph-xkrh-y79j, CVE-2026-118514: las peticiones HEAD saltan `security.access_control`.
  - PKSA-pr6q-2w5s-kzzf: reglas de ruta delante de PHP.
  - PKSA-nxjs-s6hn-vb6m: el atajo `private_ranges` de los proxies.
  - PKSA-b2gg-vk1g-6d5q: URLs generadas con el host del cliente.
- `symfony/http-kernel` 7.4.19: PKSA-3gxv-7xhq-mgmz, fragmentos en línea con cabeceras reenviadas.
- `symfony/routing` 7.4.18: PKSA-y8c5-1b7f-r1gj, CVE-2026-118516, marcadores de host en el *matcher*
  compilado.

Todos se corrigen en la 7.4.23. Sin EX4, `composer audit` falla y `/release` no avanza; el siguiente
release es el de la 039. `symfony/mime` no cambia: ya está en la 7.4.22, la última publicada.

## Cambio
- `composer.lock`, con `composer update symfony/http-foundation symfony/http-kernel symfony/routing`
  (dentro del rango que ya tenía `composer.json`; nada más cambia):
  - `symfony/http-foundation` 7.4.20 → 7.4.23;
  - `symfony/http-kernel` 7.4.19 → 7.4.23;
  - `symfony/routing` 7.4.18 → 7.4.23.
- `composer.json`: se quita `config.audit.ignore`, con los seis IDs de EX4.
- `docs/security.md`: EX4 retirada (sale de «Vigentes» y de la tabla, con la fecha y esta ficha).
  `docs/roadmap.md`: la entrada del 2026-11-11, hecha.
- **Changelogs** (commits de GitHub entre la versión del lock y la 7.4.23): son correcciones de la
  misma versión menor, sin cambios incompatibles ni migraciones.
  - `http-foundation` (58 commits): las correcciones de los cuatro avisos son:
    - HEAD cuando la regla lista GET (CVE-2026-118514);
    - `Request::getBaseUrl()`, que ya no casa el nombre del script a medias ni empieza con un host
      del cliente;
    - la lista propia de rangos privados para los proxies de confianza.

    Además corrige cookies, `HeaderUtils::split()`, `BinaryFileResponse` (rangos y `Content-Length`),
    manejadores de sesión y `Content-Disposition`.
  - `http-kernel` (69 commits): sus avisos son:
    - el contexto del router de la petición padre se restaura tras una subpetición;
    - `#[MapRequestPayload]` solo decodifica CSV o YAML si `acceptFormat` los lista.

    Además, `unserialize()` con `allowed_classes => false`, correcciones de `HttpCache`, del perfilador
    y del cierre del kernel. Dentissa no usa `#[MapRequestPayload]`, `HttpCache` ni ESI.
  - `routing` (14 commits): los marcadores de host ya no casan la ruta en el *matcher* compilado
    (CVE-2026-118516). También la URL «0», las condiciones «0» y las rutas solo HEAD.
- **Verificación de los paquetes** (`shared/agent-security.md` §2):
  - son dependencias que ya existían, transitivas de `laravel/framework`;
  - el origen es `github.com/symfony/<paquete>`, con licencia MIT, como las versiones anteriores;
  - se publicaron el 2026-11-04, hace 6 días (excepción a los 7 días: ver Decisiones);
  - las referencias del lock (`7fb73cff…`, `94c57f4c…`, `0000ade6…`) son las que publica Packagist
    para la 7.4.23;
  - no traen scripts de instalación.

## Escalado
`lane-check` al empezar (4 archivos previstos, 1 de código: `composer.json`): «Escalar: no. El cambio cabe en el carril.» Al cerrar, sobre el diff real (5 archivos con la ficha): 1 de código, «Escalar: no».

## Verificación
- `composer audit --locked --ignore-severity=low --ignore-severity=medium` (el comando de la CI) y
  `composer audit --locked`: código 0, «No security vulnerability advisories found», ya sin nada
  ignorado. Antes, con EX4: «Found 6 ignored security vulnerability advisories affecting 3 packages».
- `composer validate --no-check-publish`: válido.
- `php artisan test` contra `dentissa_test`: 721 pasan (12 175 aserciones), igual que la línea base de
  `main` en `4b95310`.
  - Se ejecutó en un contenedor aparte, con la imagen `dentissa-app` y la red de Compose, y el `vendor`
    dentro de este worktree. Así no se tocó el volumen `vendor_data` del contenedor en marcha, que
    monta la rama de la 039.
- No aplican porque no cambia ningún PHP ni ningún paquete de npm: Pint, semgrep, `npm audit` y
  `npm run build`.

## Decisiones
| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-11-09 | contradicción | La 7.4.23 salió el 2026-11-04 (08:27–11:26 UTC) y el día de la decisión tenía unos 5 días (6 al aplicarla, el 2026-11-10); `security.agent.min_package_age_days` pide 7 (`.ai/project.yaml`), es decir, hasta el 2026-11-11, el mismo día que vence EX4 | Excepción a la regla de antigüedad solo para estos tres paquetes: se suben ya. Motivo: corrigen avisos de seguridad, EX4 está a punto de vencer y el origen es el oficial (`github.com/symfony/*`), con la referencia del lock comprobada contra Packagist | usuario |
