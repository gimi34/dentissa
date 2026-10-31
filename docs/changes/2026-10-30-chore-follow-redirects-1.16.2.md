---
lane: chore
status: done                 # open → done → released · escalated (→ escalated_to)
base: 232edda                # commit de partida; lo registra la skill al empezar (lo usa lane-check)
relates: []                  # specs cuyo comportamiento toca ([] si ninguna)
covers: []                   # fix: criterios de esas specs que el arreglo vuelve a cumplir
escalated_to: null           # NNN de la spec si el cambio se escaló al flujo completo
---

# Chore: `follow-redirects` 1.16.2

## Problema
`npm audit --audit-level=high` falla desde el 2026-10-30 por un aviso alto de `follow-redirects`
1.16.1, la versión que dejó la chore del 2026-10-20 (dependencia transitiva de `axios`, solo de
desarrollo):
- GHSA-mrwp-f3q8-3g9p (publicado el 2026-10-30): los arrays de `sensitiveHeaders` de otro *realm*
  se descartan y las credenciales propias pueden seguir las redirecciones. Afecta a `>= 1.16.0,
  <= 1.16.1`; corregido en 1.16.2.

Lo detectó T042 de la spec 020 (`/implement 020`), que pide `npm audit` con código 0; `main` tiene la
misma versión. La CI de los PR solo corre `npm audit` si el PR cambia un lockfile, y el job completo
de seguridad es informativo. La imagen de producción no instala dependencias de npm.

## Cambio
- `package-lock.json`: `follow-redirects` 1.16.1 → 1.16.2. Lo pide `axios` con `^1.16.0`;
  `package.json` no cambia (`npm update follow-redirects --package-lock-only --ignore-scripts`, con
  npm 11 de `node:24-alpine`: el npm 10 de `node:22` reescribía de más el lockfile, quitando los campos
  `libc` y el nombre del proyecto, y se descartó).
- Changelog 1.16.1 → 1.16.2 (3 commits): «Add funding emergency notice» (README y SECURITY.md),
  «Use Array.isArray» (`index.js`, +1 −5: corrige el aviso) y la publicación. Sin cambios
  incompatibles.
- Verificación del paquete (`shared/agent-security.md` §2):
  - existe en npm, mismos mantenedores (`rubenverborgh`, `olalonde`), licencia MIT;
  - sin scripts de instalación (solo `lint` y `test`);
  - publicado el 2026-10-25 (5 días, mínimo 7: excepción del usuario, ver Decisiones);
  - integridad `sha512-/kVP2U8z59…` igual a la del registro.

## Escalado
`lane-check` al empezar y al cerrar: 1 archivo, 0 de código. «Escalar: no. El cambio cabe en el
carril.»

## Verificación
- `npm audit --audit-level=high`: 0 vulnerabilidades (antes, 1 alta).
- `npm ci --ignore-scripts --dry-run`: código 0, el lockfile es coherente.
- `php artisan test` contra `dentissa_test`: 422 pasan (7110 aserciones), con
  `DependenciasSegurasTest`, que lee el lockfile.
- `composer audit --locked --ignore-severity=low --ignore-severity=medium`: código 0.
- `npm run build`: no aplica (solo lo usa `welcome.blade.php`; ni la CI ni la imagen de producción
  construyen assets).

## Decisiones
| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-10-30 | usuario | El aviso alto aparece en T042 de la 020, pero es de `main`: ¿se acepta en la 020 o se corrige aparte? | Un `/chore` aparte, en la rama `chore/follow-redirects-1.16.2` desde `main`; T042 se cierra citando esta ficha | usuario |
| 2026-10-30 | supuesto | 1.16.2, la única versión que corrige el aviso, se publicó el 2026-10-25: 5 días, por debajo de `security.agent.min_package_age_days` (7) | Excepción a la antigüedad mínima, tras la verificación de `agent-security.md` §2: mismos mantenedores (`rubenverborgh`, `olalonde`), MIT, sin scripts de instalación; de 1.16.1 a 1.16.2 el código cambia en una línea (`index.js`: `Array.isArray`, +1 −5), el resto es README, SECURITY.md y la versión | usuario |
