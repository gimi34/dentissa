---
name: reverse-engineering-only
depth: Comprehensive
keywords: []
description: Solo ingeniería inversa del código existente
skeleton: off
---

# reverse-engineering-only scope

Profundidad Comprehensive aplicada a una sola etapa: `reverse-engineering`.
El objetivo no es construir ni modificar nada, sino producir un mapa
documentado del código que ya existe — componentes, límites, flujos,
dependencias y acoplamiento — con el nivel de detalle más alto disponible.
No se genera código, no se diseña, no se despliega.

## Por qué estas etapas, por qué se omiten las demás

La intención capturada es entender el sistema tal como está hoy. Eso hace
que `reverse-engineering` sea la única etapa con valor esperado positivo:
es la que reduce la incertidumbre estructural (CSU), y se ejecuta a
profundidad Comprehensive porque el mapa es el entregable, no un insumo
intermedio.

Todo lo demás se omite por una de dos razones:

- **No hay intención de construir.** `code-generation`, `build-and-test`,
  `functional-design`, `domain-design`, `units-generation`,
  `contract-design` y `delivery-planning` sólo tienen sentido cuando existe
  un cambio que producir y verificar. Aquí no lo hay.
- **No hay producto ni superficie operativa nueva que enmarcar.** Toda la
  fase de ideación (`intent-capture`, `market-research`, `feasibility`,
  `scope-definition`, `team-formation`, `rough-mockups`,
  `approval-handoff`) y toda la fase de operación (pipelines, entornos,
  despliegue, observabilidad, respuesta a incidentes, validación de
  rendimiento, optimización por retroalimentación) quedan sin consumidor.

`practices-discovery` y `requirements-analysis` se omiten porque sus salidas
alimentan etapas de diseño y construcción que aquí no se ejecutan: sin
consumidor aguas abajo, su costo no se recupera. Las convenciones del
equipo, en la medida en que importan para este trabajo, quedan reflejadas en
el propio mapa del código.

La ceremonia del esqueleto (`skeleton: off`) no aplica: no se arranca ningún
camino de ejecución.

## Advertencia sobre la salida

El único artefacto de este scope es el almacén local de ingeniería inversa
en `aidlc/spaces/<active-space>/codekb/<repo>/`. Si más adelante se decide
construir sobre este análisis, hay que volver a componer (o elegir un scope
estándar como `feature`, `refactor` o `bugfix`); este scope no deja el
trabajo listo para implementar, sólo listo para decidir.

## Membership

Sin palabras clave (`keywords: []`): este scope no participa en la
inferencia automática y sólo se resuelve de forma explícita con
`--scope reverse-engineering-only`. Se ejecutan las tres etapas de
inicialización (`workspace-scaffold`, `workspace-detection`, `state-init`)
más `reverse-engineering`; las 29 etapas restantes son SKIP.
