---
lane: chore
status: done                 # open → done → released · escalated (→ escalated_to)
base: a2cd958                # commit de partida; lo registra la skill al empezar (lo usa lane-check)
relates: []                  # specs cuyo comportamiento toca ([] si ninguna)
covers: []                   # fix: criterios de esas specs que el arreglo vuelve a cumplir
escalated_to: null           # NNN de la spec si el cambio se escaló al flujo completo
---

# Chore: retirar la excepción EX5 (picomatch)

## Problema
La excepción EX5 (ficha `2026-11-17-chore-npm-audit-picomatch.md`, v0.20.1) ignoraba en `npm audit` el aviso
GHSA-fc5q-39xc-vjpf, ReDoS en `picomatch` ≤ 4.0.7. GitHub retiró ese aviso el 2026-11-18 («Withdrawn Advisory»,
`withdrawn_at: 2026-11-18T15:38:56Z`) y `npm audit` ya no lo da: hoy informa 0 vulnerabilidades con el mismo
lockfile (`picomatch` 4.0.7 y 2.3.2). Desde entonces `.github/scripts/auditar-npm.mjs` avisa en cada ejecución de que
la excepción sobra («retira EX5 de docs/security.md y de este script»), visto en el `/release 051`.

## Cambio
- Ninguna dependencia cambia: `package.json` y `package-lock.json` siguen igual.
- `.github/scripts/auditar-npm.mjs`: la lista `EXCEPCIONES` queda vacía, con un comentario que da la forma de una
  entrada para la próxima vez. La lógica no cambia: cualquier aviso alto o crítico vuelve a fallar.
- `docs/security.md`: la fila de EX5 sale de «Excepciones aceptadas»; vigentes quedan EX2 y EX3, y EX5 pasa al
  párrafo de las retiradas, con la fecha y el motivo.
- `.ai/project.yaml` y `.github/workflows/ai-dd.yml` no cambian: siguen llamando al script.
- Commit `chore(chore-retirar-ex5): retirar la excepción EX5 de picomatch`, con `Ficha:` en el cuerpo.

## Escalado
`lane-check` al empezar (archivos previstos) y sobre el diff real desde `a2cd958`: «Escalar: no. El cambio cabe en el
carril» (2 archivos, 1 de código). El script está bajo `.github/`; el usuario pidió este cambio de forma explícita
(«soluciona las 3», 2026-11-20).

## Verificación
- `npm audit --json`: 0 vulnerabilidades (`info`, `low`, `moderate`, `high` y `critical` en 0).
- `node .github/scripts/auditar-npm.mjs` tras el cambio: «ningún aviso alto o crítico sin excepción vigente», código 0,
  ya sin el aviso de excepción sobrante.
- `composer audit --locked --ignore-severity=low --ignore-severity=medium`: 0 altas o críticas (2026-11-19, en el
  `/release 051`, sobre el mismo `composer.lock`).
- Aviso consultado en la API de GitHub: `withdrawn_at: 2026-11-18T15:38:56Z`.
- **No se ejecutó** la suite de PHPUnit ni Pint: no cambia código PHP ni de la aplicación, y la base `a2cd958` es la
  de `main`, con la suite completa en verde en la CI del PR #97.

## Decisiones
| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-11-20 | implícita | ¿Retirar EX5 aunque no se haya publicado `picomatch` 4.0.8, que era la condición escrita? | Sí: la condición buscaba que el aviso dejara de aplicar, y el aviso se retiró | API de GitHub (GHSA-fc5q-39xc-vjpf) |
