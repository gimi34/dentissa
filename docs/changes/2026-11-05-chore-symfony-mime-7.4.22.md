---
lane: chore
status: done                 # open → done → released · escalated (→ escalated_to)
base: b9fb733                # commit de partida; lo registra la skill al empezar (lo usa lane-check)
relates: []                  # specs cuyo comportamiento toca ([] si ninguna)
covers: []                   # fix: criterios de esas specs que el arreglo vuelve a cumplir
escalated_to: null           # NNN de la spec si el cambio se escaló al flujo completo
---

# Chore: `symfony/mime` 7.4.22 y excepción EX4 para los avisos de Symfony del 2026-11-04

## Problema
`composer audit --locked` falla desde el 2026-11-04. Lo detectó T023 de la spec 043; `main` tiene el
mismo `composer.lock`.

- **`symfony/mime` 7.4.19.** Tiene CVE-2026-45070 (PKSA-wtxr-p26d-nn42, media: inyección de
  cabeceras en el correo con nombres de parámetro que no son *token*). Ya era la línea base de la 041
  (041/R28, «un `/chore`» en el roadmap). Corregido en la 7.4.22.
- **Seis avisos publicados el 2026-11-04, sin gravedad asignada.** Como no tienen gravedad, el
  comando de la CI, que solo ignora `low` y `medium`, falla:
  - `symfony/http-foundation` 7.4.20:
    - PKSA-24ph-xkrh-y79j, CVE-2026-118514: las peticiones HEAD saltan la opción `methods` de
      `security.access_control`.
    - PKSA-pr6q-2w5s-kzzf: reglas de ruta delante de PHP que se saltan añadiendo la ruta al nombre
      del script.
    - PKSA-nxjs-s6hn-vb6m: el atajo `private_ranges` de los proxies de confianza.
    - PKSA-b2gg-vk1g-6d5q: URLs generadas con un host elegido por el cliente.
  - `symfony/http-kernel` 7.4.19: PKSA-3gxv-7xhq-mgmz, fragmentos en línea con cabeceras reenviadas.
  - `symfony/routing` 7.4.18: PKSA-y8c5-1b7f-r1gj, CVE-2026-118516, marcadores de host en el
    *matcher* compilado.

  Todos se corrigen en la 7.4.23, publicada el 2026-11-04. Hoy tiene 1 día y no cumple
  `security.agent.min_package_age_days` (7) hasta el 2026-11-11.

La CI de los PR solo ejecuta `composer audit` si el PR cambia un lockfile, como este. El job
completo de seguridad (`push` a `main`) es informativo. `/release` sí exige el audit limpio.

## Cambio
- `composer.lock`: `symfony/mime` 7.4.19 → 7.4.22, con `composer update symfony/mime`. La versión
  sale del rango que ya tiene el lock y `composer.json` no cambia por ella.
- `composer.json` → `config.audit.ignore`: ignora los seis avisos de la 7.4.23 por su ID de
  Packagist, con el motivo y la referencia a EX4, hasta el 2026-11-11.
- `docs/security.md`: excepción EX4 con el motivo, la aprobación y el vencimiento. `docs/roadmap.md`:
  041/R28 resuelto y el `/chore` del 2026-11-11.
- **Changelog de `symfony/mime` de la 7.4.19 a la 7.4.22:** 18 commits, todos correcciones y ninguno
  incompatible:
  - rechazo de nombres de parámetro que no son *token* (la corrección del CVE);
  - escapado de comillas y barras invertidas en valores entre comillas y en `Address::getEncodedName()`;
  - correcciones al serializar y deserializar las partes (`DataPart`, `TextPart`, `SMimePart`,
    multipart), que rechazan los valores que no son cadenas.
- **Verificación del paquete** (`shared/agent-security.md` §2):
  - es una dependencia que ya existía, transitiva de `laravel/framework`;
  - del mismo origen (`github.com/symfony/mime`) y con licencia MIT;
  - publicada el 2026-10-28, hace 8 días;
  - la referencia del lock, `94a4c017…`, es la que publica Packagist;
  - sin scripts de instalación.

## Escalado
`lane-check` al empezar (3 archivos previstos) y al cerrar (5, con la ficha y el roadmap): 1 archivo de código (`composer.json`). «Escalar: no. El cambio cabe en el carril.»

## Verificación
- `composer audit --locked --ignore-severity=low --ignore-severity=medium` (el comando de la CI) y `composer audit --locked`:
  código 0, con «Found 6 ignored security vulnerability advisories affecting 3 packages»; antes, 7 avisos y código 1.
- `npm audit --audit-level=high`: 0 vulnerabilidades.
- `composer validate --no-check-publish`: válido; el `content-hash` del lock no cambia con `config.audit`.
- `php artisan test` contra `dentissa_test`: 702 pasan (12 009 aserciones), los mismos de la línea base de `main`, con `DependenciasSegurasTest`.
- Pint y semgrep: no aplican, porque no cambia ningún PHP. `npm run build`: no aplica.

## Decisiones
| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-11-05 | usuario | Los avisos aparecen en T023 de la 043 pero son de `main`: ¿se corrigen dentro de la 043 o aparte? | Un `/chore` aparte desde `main`; la 043 incorpora `main` antes de su PR | usuario |
| 2026-11-05 | contradicción | La 7.4.23, que corrige los seis avisos, tiene 1 día y `security.agent.min_package_age_days` pide 7 (`.ai/project.yaml`) | Se respeta la regla. Hoy solo sube `symfony/mime`, a la 7.4.22, que ya tiene 8 días. Los seis avisos quedan en la excepción EX4, ignorados por ID en `composer audit` hasta el 2026-11-11; ese día, un `/chore` sube los tres paquetes a la 7.4.23 y retira EX4 | usuario |
