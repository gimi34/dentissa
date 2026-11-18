---
lane: chore
status: released                 # open → done → released · escalated (→ escalated_to)
released: v0.20.1 (2026-11-17)
base: ab10377                # commit de partida; lo registra la skill al empezar (lo usa lane-check)
relates: [046]               # specs cuyo comportamiento toca ([] si ninguna)
covers: []
escalated_to: null           # NNN de la spec si el cambio se escaló al flujo completo
---

# Chore: capturar.mjs vuelve a comparar las pantallas de la 046

## Problema
La spec 046 (CA12, D12) sacó de la comparación del escritorio de `docs/design/capturar.mjs --medir`
cinco pantallas que recompone a propósito («Agendar cita» y «Reprogramar» del paciente y la ficha
del expediente de los tres roles), en la lista `SIN_COMPARAR_046`, mientras la línea base fuera de
una versión anterior a la 046. La 046 está en producción desde la v0.20.0 (2026-11-16): la línea
base ya la incluye y esas pantallas tienen que volver a compararse. Entrada del roadmap
«`capturar.mjs` tras la spec 046».

## Cambio
- `docs/design/capturar.mjs`: se retira la lista `SIN_COMPARAR_046` y el filtro de `COMPARADAS`, en
  lugar de dejar una lista vacía sin uso. `COMPARADAS` vuelve a incluir `cliente-agendar`,
  `citas-reprogramar-cliente` y `{admin,dentista,asistente}-ficha-expediente`. La cabecera (paso 7b)
  y el comentario de `COMPARADAS` lo explican. Siguen las otras exclusiones de la 046: el bloque de
  migas y, en expedientes, las entradas «Pacientes» y «Expedientes» de la barra, porque ocultan
  zonas y no pantallas.
- `docs/roadmap.md`: se quita la entrada «`capturar.mjs` tras la spec 046».
- Commit `chore(chore-capturar-tras-046): …` en la rama `chore/cierre-v0.20.0`, junto al cierre de
  la v0.20.0.

## Escalado
`lane-check` al empezar y al cerrar, sobre `docs/design/capturar.mjs` y `docs/roadmap.md`: dos
archivos, ninguno de código y no hay que escalar. Sin `--files`, el script ve además archivos de
`aidlc/` modificados o sin seguimiento en la copia de trabajo. No son de este chore ni van en su
commit: AI-DLC está en pausa y esos archivos no se editan (AGENTS.md).

## Verificación
- `node --check docs/design/capturar.mjs`: sintaxis correcta. No quedan usos de `SIN_COMPARAR_046`
  (solo la mención histórica de la cabecera).
- Suite de PHPUnit y Pint: no se ejecutan, porque el cambio no toca código de la aplicación (es el
  guion de capturas de `docs/` y el roadmap) y la CI de PHP no carga `capturar.mjs`.
- Comparación real: la primera `--medir` con línea base v0.20.0 o posterior (la siguiente spec que
  toque `layouts/app.blade.php`, `menu.js` o `movil.css`) compara las cinco pantallas. Aquí no se
  repite una `--medir` completa, porque la línea base y la rama tendrían el mismo código (v0.20.0) y
  la comparación saldría igual por construcción.
- `python .ai/bin/aidd.py validate`: sin errores.
- Liberada en v0.20.1: producción aprobada por el responsable (gimi34) el 2026-11-17 con «desplegar v0.20.1 a producción»; `desplegar.sh v0.20.1` en 138 s y `verificar.sh` interna y pública, todo OK.

## Decisiones
| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-11-17 | supuesto | ¿Vaciar la lista o retirarla? | Retirarla con su filtro: una lista vacía sin uso sería código muerto. El efecto es el mismo que vaciarla. | agente |
| 2026-11-17 | supuesto | ¿Rama propia o la del cierre? | La rama del cierre de la v0.20.0, que aún no se ha subido: un solo PR, solo `docs/`. | agente |
