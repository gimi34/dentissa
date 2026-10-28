---
lane: chore
status: done                 # open → done → released · escalated (→ escalated_to)
base: 835caaf                # commit de partida; lo registra la skill al empezar (lo usa lane-check)
relates: []                  # specs cuyo comportamiento toca ([] si ninguna)
covers: []
escalated_to: null           # NNN de la spec si el cambio se escaló al flujo completo
---

# Chore: reglas de la rama `maqueta`

## Problema
La rama larga `maqueta` (creada desde `main` en `835caaf` y subida a `origin` el 2026-10-27) aloja
un proyecto aparte: una maqueta física de consultorio sobre Dentissa (QR de la cita que abre la
puerta, estado de cita `llego`, LED por silla de la sala de espera, control de luces, ventiladores
y puertas, ESP32 + MQTT y app Android con Ionic). **Nunca debe llegar a producción**
(decisión del usuario, 2026-10-27). Los documentos de la rama siguen describiendo el flujo de
`main`: ramas con PR a `main`, tags `vX.Y.Z` y `/release` hasta producción. Una sesión de Claude o
un integrante que los siga podría abrir un PR de la maqueta a `main` o crear un tag `vX.Y.Z` que
`deploy/desplegar.sh` despliega (acepta cualquier tag o commit). Hay que dejar escritas las reglas
de la rama en `AGENTS.md`, `docs/deployment.md` y `.ai/project.yaml`.

## Cambio
Solo documentación y configuración del flujo, sin código:
- `AGENTS.md`: sección "Rama `maqueta` (proyecto aparte, nunca a producción)" con el alcance
  previsto y las reglas (PR a `maqueta`, specs desde la 101, tags `maqueta-vX.Y.Z`, sin
  `deploy/desplegar.sh`, merge periódico de `main`, cherry-pick para llevar algo a producción) y
  una línea en "Ramas".
- `docs/deployment.md`: sección "Rama `maqueta`: nunca a producción"; `updated` a 2026-10-27.
- `.ai/project.yaml`: `deploy.strategy` de `manual` a `none` (`/release` solo versiona y etiqueta)
  y `deploy.environments` de `[dev, prod]` a `[dev]`.

Commit `chore(chore-reglas-rama-maqueta): reglas de la rama maqueta` en la rama
`chore/maqueta-reglas`, con PR a `maqueta`.

## Escalado
- Al empezar: `lane-check --files AGENTS.md docs/deployment.md .ai/project.yaml` → 3 archivos, 0 de
  código; "Escalar: no".
- Al cerrar, sobre el diff real desde `835caaf`: 4 archivos, 0 de código; "Escalar: no".

## Verificación
- Sin código tocado: la suite, Pint y el build no cambian de resultado. No se ejecutaron en local
  porque Docker Desktop no estaba arrancado (2026-10-27); la CI del PR los corre.
- `gitleaks` no está instalado en local; corre en la CI del PR. Los cambios no contienen secretos.
- `python .ai/bin/aidd.py validate` sin errores.
- Sin review con subagente (`/chore`: la validación es la review).

## Decisiones
| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-10-27 | decisión | ¿Dónde vive la maqueta? | En el mismo repo, en la rama larga `maqueta`, que nunca se fusiona a `main` | usuario |
| 2026-10-27 | decisión | ¿Cómo se evita que llegue a producción? | Ramas de trabajo desde `maqueta` con PR a `maqueta`; tags `maqueta-vX.Y.Z`; `/release` solo versiona y etiqueta; entorno solo dev | usuario |
| 2026-10-27 | decisión | Numeración de specs de la maqueta | Desde la 101 (036–038 ocupadas o reservadas en `main`) | usuario |
