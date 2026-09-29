<!-- INVARIANT: examples are single-line HTML comments so a fresh template parses to total=0 (MEMORY_EMPTY). Do NOT un-comment or split across lines. t100 guards this. -->
> This file is kept up to date automatically while the stage runs. Add observations at the review step, not by editing here directly.

## Interpretations
<!-- example: 2026-05-29T10:14:32Z — chose REST over GraphQL; the consuming team only needs CRUD, revisit if subscriptions land -->
- 2026-09-20T02:37:35Z — El humano eligio escaneo enfocado sobre un almacen STALE; interprete el area de la intencion como la capa PHP de dominio mas su esquema, pruebas y autoload: app/, database/, routes/, tests/, config/, bootstrap/, resources/views/, composer.json, composer.lock, phpunit.xml. Quedan fuera del foco docker/, public/, resources/js/, resources/css/, vite.config.js y package.json porque la reestructuracion modular no los toca.

- 2026-09-28T00:27:07Z — Reanudacion tras el aparcamiento: el escaneo del desarrollador no habia dejado archivo de traspaso y pipeline.completed seguia vacio, asi que se redespacho el eslabon 1 desde cero. La decision de amplitud ya registrada (escaneo enfocado, DECISION_RECORDED + QUESTION_ANSWERED del 2026-09-20) NO se volvio a preguntar. Instantanea de origen nueva tomada antes del despacho sobre el conjunto enfocado: store_generation sha256:0e8d6175dee0adc085995e3c839a19aea0809196a42904989bc98d2460175537, source_fingerprint git:84e58ec6d591f634282dcdf9750663c0ca43d88b. El verdicto del almacen sigue siendo STALE.

## Deviations
<!-- example: 2026-05-29T10:14:32Z — skipped the optional caching layer the stage prose suggested; the dataset is small enough that it adds risk -->

## Tradeoffs
<!-- example: 2026-05-29T10:14:32Z — picked TDD over BDD this run; the team is unit-first and the domain is well-understood -->
- 2026-09-20T02:37:35Z — Recomende reescaneo completo (la reestructuracion toca controladores, modelos, migraciones y pruebas) pero el humano eligio enfocado. Consecuencia aceptada: al ser el almacen STALE, la cobertura profunda previa de resources/js/, resources/css/, docker/ y los manifiestos de front-end se degrada a superficial porque no se puede reverificar en este pase.

## Open questions
<!-- example: 2026-05-29T10:14:32Z — confirm the retention window with compliance before the next stage hardens the schema -->
- 2026-09-20T02:45:12Z — Etapa aparcada a peticion del humano en mitad del paso 2, con el escaneo del desarrollador detenido y sin recibo de eslabon (pipeline.completed vacio). Al reanudar hay que volver a despachar al desarrollador y tomar una instantanea de origen nueva antes de hacerlo; la decision de alcance ya esta registrada (escaneo enfocado) y el conjunto de rutas del foco esta anotado arriba bajo Interpretations.
