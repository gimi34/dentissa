---
lane: chore
status: released                 # open → done → released · escalated (→ escalated_to)
released: v0.20.1 (2026-11-17)
base: f801977                # commit de partida; lo registra la skill al empezar (lo usa lane-check)
relates: []                  # specs cuyo comportamiento toca ([] si ninguna)
covers: []                   # fix: criterios de esas specs que el arreglo vuelve a cumplir
escalated_to: null           # NNN de la spec si el cambio se escaló al flujo completo
---

# Chore: excepción EX5 para picomatch (ReDoS sin versión corregida)

## Problema
`npm audit --audit-level=high` encuentra 6 vulnerabilidades altas, todas del mismo aviso: GHSA-fc5q-39xc-vjpf,
«picomatch vulnerable to ReDoS through single-branch repeated extglobs», que afecta a `picomatch` hasta la 4.0.7.
Las otras cinco son de paquetes que dependen de él: `fdir` 6.5.0, `tinyglobby` 0.2.17, `vite` 8.3.4,
`vite-plugin-full-reload` 1.2.0 (con `picomatch` 2.3.2) y `laravel-vite-plugin` 3.0.0. Son todas
`devDependencies`, herramientas de build que no llegan a producción: la imagen de producción no ejecuta
`npm` y solo `welcome.blade.php` usa Vite.

No se puede corregir subiendo versiones: la 4.0.7 es la última de `picomatch` publicada en npm (`latest`,
2026-08-24). La única corrección que propone `npm audit fix --force` baja de versión mayor (`vite` 6.2.7 y
`laravel-vite-plugin` 0.2.4), y eso no es una corrección.

Sin excepción, el `/release` siguiente no avanza (P6). La CI de un PR solo audita si el PR cambia algún
lockfile; el job `seguridad-completa` de `main` es informativo.

Decisión del usuario (2026-11-17, en `/plan 050`, D8, y en este chore): excepción EX5 que ignora **solo** ese aviso,
con vencimiento el 2026-12-31. Cualquier otra vulnerabilidad alta o crítica sigue fallando.

## Cambio
- Ninguna dependencia cambia de versión: `package.json` y `package-lock.json` siguen igual (no hay corrección
  publicada).
- `.github/scripts/auditar-npm.mjs` (nuevo, solo Node): ejecuta `npm audit --json`, junta los avisos altos y
  críticos por GHSA y falla (código 1) si queda alguno sin excepción vigente. Lleva la lista de excepciones con su
  vencimiento (`GHSA-fc5q-39xc-vjpf` → EX5, 2026-12-31). Una excepción vencida vuelve a fallar sola, y una cuyo aviso
  ya no aparece se anuncia para retirarla. Si `npm audit` falla, sale con código 2.
- `.github/workflows/ai-dd.yml`: `npm audit --audit-level=high` → `node .github/scripts/auditar-npm.mjs` en los dos
  jobs. En `seguridad-pr` solo corre si el PR cambia algún lockfile, como antes; en `seguridad-completa` (`main`,
  informativo), siempre. No cambia nada más.
- `.ai/project.yaml`: el mismo cambio en `security.tools.sca.command`, que es lo que ejecuta `/release`.
- `docs/security.md`: excepción EX5 en «Excepciones aceptadas» y en la lista de vigentes.

## Escalado
`lane-check` al empezar (archivos previstos) y sobre el diff real desde `f801977`: «Escalar: sí — seguridad: rutas
protegidas, `.github/workflows/ai-dd.yml`». El usuario aprobó de forma explícita cambiar la CI al elegir la opción
«EX5 + ignorar ese aviso» (ver «Decisiones»). No hay dependencias nuevas, datos, contratos ni código de la aplicación.

## Verificación
- `node .github/scripts/auditar-npm.mjs` con el lockfile de hoy: «Ignorado por EX5 (vence 2026-12-31):
  GHSA-fc5q-39xc-vjpf high…» y «ningún aviso alto o crítico sin excepción vigente», código 0.
- Con el vencimiento movido al 2026-11-01 (copia temporal del script): «FALLO (la excepción EX5 venció el
  2026-11-01)…», código 1.
- Sin la excepción (copia temporal con otro GHSA): «FALLO (sin excepción)…» más el aviso de retirar la excepción
  sobrante, código 1.
- `composer audit --locked --ignore-severity=low --ignore-severity=medium`: 0 altas o críticas (1 baja ignorada).
- El diff de la CI y de `.ai/project.yaml` se limita a sustituir el comando en tres líneas.
- **No se ejecutó** la suite de PHPUnit ni Pint: no cambia código PHP ni de la aplicación, y la base `f801977` es la
  de `main`. Tampoco el build de Vite, porque no cambian las dependencias.
- La CI de este PR **no** ejecuta el paso de dependencias, porque no cambia ningún lockfile. El script se probará en
  CI con el job `seguridad-completa` al fusionar en `main`, o en el primer PR que cambie un lockfile.
- Sin review con subagente (carril chore).
- Liberada en v0.20.1: producción aprobada por el responsable (gimi34) el 2026-11-17 con «desplegar v0.20.1 a producción»; `desplegar.sh v0.20.1` en 138 s y `verificar.sh` interna y pública, todo OK.

## Decisiones
| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-11-17 | supuesto | ¿Atender las 6 altas dentro de la spec 050 o aparte? | Aparte, en `main`, antes del próximo `/release` | usuario (`/plan 050`, D8) |
| 2026-11-17 | supuesto | Sin versión corregida: ¿excepción solo para ese aviso, auditar solo producción o esperar? | EX5 solo para GHSA-fc5q-39xc-vjpf, vence el 2026-12-31; la CI y `.ai/project.yaml` usan un auditor que ignora únicamente ese aviso | usuario |
| 2026-11-17 | supuesto | `lane-check`: el cambio toca una ruta protegida (`.github/workflows/ai-dd.yml`) | Sigue en el carril: el usuario aceptó de forma explícita cambiar la CI | usuario |
| 2026-11-17 | supuesto | ¿Dónde vive el auditor? | `.github/scripts/`, junto a la CI que lo usa; no añade carpetas nuevas a la raíz ni toca `AGENTS.md` | — |
| 2026-11-17 | supuesto | En `/plan 050` se dijo que estas altas bloqueaban la CI de cualquier PR | Corregido: `seguridad-pr` solo audita si el PR cambia un lockfile, así que lo que bloqueaban era el `/release` (P6) y los PR que cambian dependencias | lectura de `.github/workflows/ai-dd.yml` |
