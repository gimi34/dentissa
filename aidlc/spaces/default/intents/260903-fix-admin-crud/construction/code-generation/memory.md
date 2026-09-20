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

## Deviations

- 2026-09-14T02:40:00Z — La revision de arquitectura hubo que repetirla y, para poder repetirla, reiniciar el intento de la etapa con un salto `redo`. La primera pasada termino con veredicto READY y hallazgos validos, pero el recibo no se pudo cerrar: el revisor ejecuto la suite de pruebas para fundamentar su juicio —que es exactamente lo que el contrato de revision adversarial/advisory le pide— y al hacerlo Laravel reescribio `.phpunit.result.cache`, `storage/logs/laravel.log` y creo archivos en `storage/framework/sessions/`. Esos archivos entran en la huella del codigo fuente a la que `REVIEW_REQUESTED` fija el recibo, de modo que `REVIEW_COMPLETED` se rechazo por «workspace source changed». El presupuesto advisory es de una sola pasada, asi que no habia forma de pedir otra sin reiniciar el intento. La segunda pasada se despacho en modo solo lectura, con la salida de las pruebas recogida de antemano por el conductor y citada como evidencia.
- 2026-09-14T02:35:00Z — El suelo de origen del codigo volvio a ser pegajoso tras el salto `redo`: la directiva nueva heredo `code_generation_source_sha256` de la anterior, capturado antes de que el desarrollador escribiera el codigo, y la aprobacion del plan se rechazo por no coincidir. Se aplico el remedio ya registrado en `project.md`: borrar `.aidlc-active-directive.json` para que la siguiente directiva lo recalculara. Funciono a la primera. Es el mismo mecanismo que en septiembre con `public/storage`, pero con otra causa: alli el calculo fallaba, aqui el valor simplemente era viejo.

## Tradeoffs

- 2026-09-14T02:40:00Z — Ante el bloqueo del recibo de revision habia dos salidas: reiniciar el intento de la etapa (coste: una aprobacion de plan humana mas y una revision mas) o buscar un bypass del guardarrail de frescura de origen. Se eligio reiniciar. `AIDLC_SKIP_SOURCE_FRESHNESS=1` no cubre la ruta de grabacion del veredicto —se comprobo leyendo `aidlc-log.ts`— y aunque la cubriera, saltarse una verificacion para ahorrar un turno humano cambia la naturaleza de la evidencia. El reinicio no debilita ningun guardarrail y conserva todo el trabajo: ni una linea de codigo se regenero.
- 2026-09-14T02:40:00Z — El conductor ejecuto la suite por su cuenta antes de pedir la segunda revision, en vez de fiarse de los numeros del desarrollador. Doble beneficio: verificacion independiente real (15/65 y 13/62, coincidentes) y un arbol de trabajo estable en el momento de fijar la huella, que es lo que permitio cerrar el recibo.

## Open questions

- 2026-09-14T02:40:00Z — `requirements.md` sigue afirmando en la restriccion C2 y en el § 6 «Fuera de alcance» que esta correccion no anade pruebas automaticas (decision Q4 = D), cuando si las anade. El humano reabrio esa decision de forma explicita y el plan lo declara abiertamente, pero el documento fuente queda desactualizado. Hallazgo R-01 de la revision. Queda por decidir si se vuelve a Requirements Analysis a corregirlo o si basta con el rastro que dejan el plan, `code-summary.md` y el registro de auditoria.
- 2026-09-14T02:40:00Z — La capa de vista sigue sin cobertura automatica (FR2.1, FR2.2, FR2.3, FR3.7, FR4.3, FR4.4): el proyecto no tiene ejecutor de pruebas de interfaz. La lista manual de 19 puntos la cubre, pero sigue sin ejecutarse contra el entorno.
