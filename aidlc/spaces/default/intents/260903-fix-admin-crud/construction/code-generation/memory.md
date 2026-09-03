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

## Deviations

- 2026-09-03T23:14:59Z — La aprobación del plan hubo que repetirla tres veces por un bloqueo del propio marco, no por el contenido del plan; el enlace `public/storage` impedía calcular la huella del código fuente. `public/storage` es un symlink creado por `php artisan storage:link` DENTRO del contenedor Docker, apuntando a la ruta absoluta de Linux `/var/www/storage/app/public`. En Windows ese enlace usa el formato de reparse point de WSL, que Node no sabe leer, así que el recorrido del árbol que calcula `workspaceSourceFingerprint` abortaba y el motor anotaba `unbindable` como suelo de origen de la directiva. Con ese valor, `recordPlanApprovalReceipt` no puede emitir nunca el recibo (compara el suelo contra un sha256 real), de modo que la generación quedaba bloqueada de forma permanente. Evidencia: `Source Baseline: unbindable` en el registro tanto en WORKFLOW_STARTED (18:35) como en STAGE_STARTED (22:08) — dos cálculos independientes, fallo determinista.

## Interpretations

- 2026-09-03T23:14:59Z — El suelo de origen es pegajoso dentro de la etapa: `codeGenerationSourceFloorForPublication` solo lo recalcula si ya existe un recibo de aprobación, lo que es un círculo cerrado cuando el valor es `unbindable`. La salida fue borrar el marcador de runtime `.aidlc-active-directive.json` (local por clon, ignorado por git) para que el motor acuñara una directiva nueva y recalculara el suelo. Tras quitar el enlace, la huella salió como sha256 real y la aprobación se registró a la primera.

## Open questions

- 2026-09-03T23:14:59Z — OQ1 sigue sin resolver: qué debe pasar al desactivar un usuario con rol `dentista` que tiene citas futuras agendadas. FR4 conserva los datos pero no define si el administrador debe recibir una advertencia. Se resolverá durante la implementación solo si aparece; si no, queda para una intención posterior.
- 2026-09-03T23:14:59Z — `public/storage` hay que restaurarlo con `docker compose exec app php artisan storage:link` cuando termine la etapa. Hacerlo antes invalidaría de nuevo la huella del origen. Conviene registrar en las prácticas del proyecto que un enlace creado dentro del contenedor rompe el cálculo de huella en hosts Windows.
