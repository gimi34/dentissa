<!-- INVARIANT: examples are single-line HTML comments so a fresh template parses to total=0 (MEMORY_EMPTY). Do NOT un-comment or split across lines. t100 guards this. -->
> This file is kept up to date automatically while the stage runs. Add observations at the review step, not by editing here directly.

## Interpretations
<!-- example: 2026-05-29T10:14:32Z — chose REST over GraphQL; the consuming team only needs CRUD, revisit if subscriptions land -->

## Deviations
<!-- example: 2026-05-29T10:14:32Z — skipped the optional caching layer the stage prose suggested; the dataset is small enough that it adds risk -->

## Tradeoffs
<!-- example: 2026-05-29T10:14:32Z — picked TDD over BDD this run; the team is unit-first and the domain is well-understood -->

## Open questions
<!-- example: 2026-05-29T10:14:32Z — confirm the retention window with compliance before the next stage hardens the schema -->

## Interpretations

- 2026-09-19T00:00:00Z — Las cuatro entradas declaradas de la etapa (ci-config, quality-gates, infrastructure-specification, cicd-pipeline) llegaron ausentes con expected=true, por diseño del alcance bugfix. Se diseñó contra la evidencia que sí existe —docker-compose.yml, Dockerfile, build-instructions.md, source-manifest.json— en vez de inventar una configuración de CI que nadie ejecutaría. El cuerpo de la etapa lo pide explícitamente: «Design only against evidence that exists».

## Deviations

- 2026-09-19T00:00:00Z — El artefacto cd-config.md documenta un procedimiento manual, no una configuración de pipeline automatizado, que es lo que su nombre sugiere. Con destino local, sin CI y con una sola persona operando, un archivo de pipeline sería ficción. Se dejó escrito en el propio documento qué es y qué no es, para que nadie lo lea esperando otra cosa.

## Tradeoffs

- 2026-09-19T00:00:00Z — Se eligió recreate y se descartaron azul/verde, canario, actualización progresiva y banderas de funcionalidad. No por simplicidad sino porque cada una presupone infraestructura ausente: un segundo entorno, métricas automáticas, varias réplicas, un servicio de configuración. Se documentó el porqué de cada descarte en vez de listar solo la elegida: un lector futuro con un servidor de verdad necesita saber qué le falta, no solo qué se hizo.
- 2026-09-19T00:00:00Z — El humano pidió vuelta atrás de código y datos aunque esta corrección no toca el esquema. La elección es correcta por una razón que no es la obvia: el riesgo no viene de la corrección sino de que ahora el alta y la edición de usuarios funcionan, así que se pueden crear registros que antes era imposible crear. Se explicó así en la estrategia para que la decisión no parezca cautela genérica.

## Open questions

- 2026-09-19T00:00:00Z — docker-compose.yml lleva MYSQL_ROOT_PASSWORD en claro y permite contraseña vacía, y publica 3306 y 8080 al host. Es aceptable en local e inaceptable en cuanto la máquina deje de serlo. Queda registrado como riesgo no resuelto; corregirlo es una intención propia, no parte de esta.
