---
lane: chore
status: done                 # open → done → released · escalated (→ escalated_to)
base: 56d659a                # commit de partida; lo registra la skill al empezar (lo usa lane-check)
relates: []                  # specs cuyo comportamiento toca ([] si ninguna)
covers: []                   # fix: criterios de esas specs que el arreglo vuelve a cumplir
escalated_to: null           # NNN de la spec si el cambio se escaló al flujo completo
---

# Chore: `follow-redirects` 1.16.1

## Problema
`npm audit --audit-level=high` falla desde el 2026-10-20 por dos avisos altos de `follow-redirects`
1.16.0, una dependencia transitiva de `axios` 1.20.0 (desarrollo):
- GHSA-q3jh-wrc6-jgq8: las cabeceras en forma de array saltan el borrado de credenciales al
  redirigir a otro origen.
- GHSA-8r9p-f939-6h3c: el escape de la expresión de `sensitiveHeaders` puede exponer cabeceras de
  credenciales.

Están corregidos en 1.16.1. La CI corre `npm audit` en cada PR, así que mientras tanto ningún PR
pasa. Además bloquea `/release 031` (`/review 031` r2, R21). La imagen de producción no instala
dependencias de npm.

## Cambio
- `package-lock.json`: `follow-redirects` 1.16.0 → 1.16.1. Lo pide `axios` con `^1.16.0`;
  `package.json` no cambia (`npm update follow-redirects --package-lock-only --ignore-scripts`).
  Se devolvió a mano el nombre del proyecto, que npm había cambiado por el de la carpeta del
  worktree.
- Changelog 1.16.0 → 1.16.1 (3 commits): «Escape all RegExp modifiers», «Support key/value header
  arrays» y la publicación. Corrige los dos avisos y no trae cambios incompatibles.
- Verificación del paquete (`shared/agent-security.md` §2):
  - existe en npm, mismos mantenedores (`rubenverborgh`, `olalonde`), licencia MIT;
  - sin scripts de instalación (solo `lint` y `test`);
  - publicado el 2026-10-01 (19 días, mínimo 7);
  - integridad `sha512-FNvFGzoMLW…` igual a la del registro.

## Escalado
`lane-check`: 1 archivo, 0 de código. «Escalar: no. El cambio cabe en el carril.»

## Verificación
- `npm audit --audit-level=high`: 0 vulnerabilidades (antes, 1 alta).
- `npm ci --ignore-scripts --dry-run`: el lockfile es coherente.
- `php artisan test`: 344 pasan (incluye `DependenciasSegurasTest`, que lee el lockfile).
- `composer audit`: sin avisos.
- `npm run build`: no aplica (solo lo usa `welcome.blade.php`; ni la CI ni la imagen de producción
  construyen assets).

## Decisiones
| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-10-20 | usuario | ¿Cómo se corrige el aviso alto de `follow-redirects` que bloquea `/release 031`? | Un `/chore` en `main` que lo suba a 1.16.1 solo en el lockfile | usuario (`/review 031` r2, R21) |
