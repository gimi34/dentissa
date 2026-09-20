# Project-Level Rules

> Project-specific specialisation and corrections. Loaded after `org.md` and
> `team.md` as strict-additive guidance; contradictions with broader policy
> are rejected. Populated by practices-discovery and the self-learning loop.
>
> Use sparingly: most teams don't need a project layer. Reach for it
> only when this specific project needs stable, durable guidance beyond the
> team practice (for example, package-specific release checks or an additional
> regression suite for a legacy component).

## Way of Working

<!-- Project-specific specialisation. Example: -->
<!-- This monorepo requires package-scoped branch names and a package owner -->
<!-- review in addition to the team's normal merge policy. -->

## Walking Skeleton

<!-- Project-specific specialisation. Example: -->
<!-- The walking skeleton must exercise the legacy service adapter as well -->
<!-- as the new service boundary. -->

## Testing Posture

<!-- Project-specific specialisation. -->

- NUNCA despachar al revisor pidiéndole que ejecute la suite de pruebas: PHPUnit reescribe `.phpunit.result.cache`, `storage/logs/laravel.log` y `storage/framework/sessions/`, esos archivos entran en la huella del código fuente a la que `REVIEW_REQUESTED` fija el recibo, y `REVIEW_COMPLETED` se rechaza por «workspace source changed». Con presupuesto advisory de una sola pasada eso obliga a reiniciar la etapa. El conductor ejecuta la suite ANTES de registrar la petición de revisión y le pasa la salida al revisor como evidencia ya recogida; el revisor trabaja en modo solo lectura (leer archivos, `git diff`, `php -l`). (learned 2026-09-14) <!-- cid:260903-fix-admin-crud:code-generation:08cf2e997ec556ab3fa1a02dd2170d03212f2f22f0d067ddf5b18a158a33c60b -->
- SIEMPRE ejecutar la suite de pruebas desde el conductor antes de pedir la revisión de una etapa que escribe en el workspace, en vez de dar por buenos los números que reporta el agente desarrollador. Da verificación independiente real y, de paso, deja el árbol de trabajo estable en el momento en que se fija la huella del código fuente, que es lo que permite cerrar el recibo de revisión. (learned 2026-09-14) <!-- cid:260903-fix-admin-crud:code-generation:38fe17a41d759584f62b27838607d23fd26da5046c4205efdc9c040e54514cfd -->
- SIEMPRE agotar el peldaño 1 de Build and Test («arreglo dentro de la etapa») antes de gastar un bucle de corrección hacia Code Generation: escribir la medición que falta para un objetivo —una prueba que produce su valor real— entra en el remit del peldaño 1 porque no toca el código medido, y no es lo mismo que arreglar ese código. Un bucle de corrección cuesta un replay completo de Code Generation con aprobación de plan y recibos de revisión nuevos; la medición que faltaba costó una hora. Esto no rebaja ningún objetivo: lo verifica. (learned 2026-09-20) <!-- cid:260903-fix-admin-crud:build-and-test:42d51ee084f0e8588c1989109e1d6899424b82cae5ff2379e9ab3b5ce80ecda4 -->
- SIEMPRE leer el condicional de la plantilla Blade y el controlador antes de escribir una aserción sobre una vista existente, en vez de deducir el contrato del nombre del campo. En este proyecto dos aserciones plausibles resultaron falsas: el selector de rol está oculto por diseño al editar a un cliente, y el estado activo es una casilla que solo viaja en la petición cuando está marcada, así que enviar `activo => '0'` la da por marcada. (learned 2026-09-20) <!-- cid:260903-fix-admin-crud:build-and-test:08dd106c15190f184a98e0110cba5adf34334ea2d0561ca74839c0f696469ebf -->
- SIEMPRE verificar con git que un fallo de prueba es preexistente cuando el predicado de fallo de la etapa depende de ese hecho, en vez de fiarse del informe de la etapa anterior: `git status` sobre el archivo de prueba, `git show HEAD:<ruta>` sobre el código que afirma, y `git diff -U0` para confirmar qué líneas cambian de verdad. (learned 2026-09-20) <!-- cid:260903-fix-admin-crud:build-and-test:3e34281fb18b921c54787f212c70b75efcde0fa11e3427608702ca1fa0eae2a7 -->
- SIEMPRE verificar una escritura consultando el almacén —la tabla, y la vista SQL cuando la haya— y NUNCA dando por buena la pantalla que el formulario vuelve a mostrar: un formulario que reexhibe el valor que le enviaste no demuestra que se haya guardado. Es la misma distinción que separa una prueba de regresión que discrimina de una complaciente. (learned 2026-09-20) <!-- cid:260903-fix-admin-crud:deployment-execution:8d9adbabed7e7ce42e15d4acae276674ed760e53ef96c71d81efdaf869116670 -->
## Deployment

<!-- Project-specific specialisation. -->

- Al elegir entre patrones de un manual —estrategias de despliegue, de ramificación, de pruebas—, documentar por qué se descartó cada alternativa y qué infraestructura presupone, no solo cuál se eligió. Un lector futuro con más infraestructura necesita saber qué le falta para graduarse, y un descarte sin razón escrita se relee como una omisión. (learned 2026-09-20) <!-- cid:260903-fix-admin-crud:deployment-pipeline:761fa37c56f7eafa5931c645398450ecc20212d0059953e338fdb0bf31d9f5cb -->
- SIEMPRE ejecutar cada orden que un runbook prescribe —el respaldo el primero— en la etapa que lo estrena, en vez de darlo por bueno por estar escrito. Un runbook sin ejecutar es una hipótesis, y el momento barato de refutarla no es durante una vuelta atrás real. En este proyecto esa práctica descubrió que la orden de respaldo fallaba por falta de contraseña y que el orden de pasos producía una hoja de estilos incompleta; ninguno de los dos defectos era visible leyendo el documento. (learned 2026-09-20) <!-- cid:260903-fix-admin-crud:deployment-execution:5bf9d900a04e05369d74afe86b0b7293625c1de9b6e9a1d0e3868b33d4f6f93d -->
## Code Style

<!-- Project-specific specialisation. -->

- Ante deriva de estilo preexistente y generalizada —en este repositorio `laravel/pint` falla también en cinco de seis archivos ajenos a la corrección, y nunca se ha ejecutado aquí: no hay `pint.json` ni línea base formateada—, registrarla como tal y NO ejecutar el formateador sobre el árbol dentro de un alcance `bugfix`: convertiría un diff de ocho archivos en uno de decenas. NUNCA desactivar una regla ni añadir configuración para silenciar las incidencias; la deriva queda como candidata a una intención propia. (learned 2026-09-20) <!-- cid:260903-fix-admin-crud:build-and-test:f76a8d07d378ba2841cd5f5df6394147ec31df5ba3b7ec5a11c186fc6d45bab5 -->
## Tech Stack

<!-- Technology choices locked for this project. -->

## Decided

<!-- Decisions made in earlier stages that should not be re-asked. -->
<!-- Format: DECIDED: [decision] (Stage [slug], [date]) -->

## Scope Overrides

<!-- Custom scope rules for this project. -->

## Forbidden

<!-- Populated by practices-discovery affirmation gate. -->
<!-- Format: NEVER [behavior] (affirmed [date]) -->
<!-- Example: NEVER throw exceptions across service layer boundaries (affirmed 2026-05-17) -->

## Mandated

<!-- Populated by practices-discovery affirmation gate. -->
<!-- Format: ALWAYS [behavior] (affirmed [date]) -->
<!-- Example: ALWAYS use Result<T,E> for fallible operations in service layer (affirmed 2026-05-17) -->

## Corrections

<!-- Project-specific corrections from human feedback. -->
<!-- Format: NEVER/ALWAYS [behavior] (learned [date]) -->
- En hosts Windows, NUNCA crear `public/storage` con `php artisan storage:link` dentro del contenedor Docker: el enlace queda apuntando a la ruta de Linux `/var/www/storage/app/public` con formato de reparse point de WSL, Windows no lo resuelve, y eso aborta el cálculo de la huella del código fuente y bloquea de forma permanente la aprobación del plan en Code Generation. Crearlo desde Windows como unión: `cmd /c mklink /J public\storage storage\app\public`. (learned 2026-09-08) <!-- cid:260903-fix-admin-crud:code-generation:862ead904845b5dc0d767dffa62395d8040d541d5ca59b2c9440da9517f981f2 -->
- Si la huella del código fuente queda registrada como `unbindable` en una etapa de Code Generation, el valor es pegajoso: cada directiva nueva lo hereda de la anterior y ni reiniciar la etapa ni reescribir el plan lo recalculan. Tras corregir la causa del fallo de cálculo, borrar el marcador de runtime `aidlc/spaces/<space>/intents/<intent>/.aidlc-active-directive.json` (local por clon, ignorado por git) para que la siguiente directiva vuelva a calcularlo. (learned 2026-09-08) <!-- cid:260903-fix-admin-crud:code-generation:5c5517b624be2370cb4d8de1b2eb38aa2162b4ea9f62ff10b7b0936a057b3a82 -->
- El suelo de origen `code_generation_source_sha256` del marcador de runtime es pegajoso tras CUALQUIER salto, no solo cuando queda como `unbindable`: la directiva nueva hereda el valor de la anterior aunque sea un sha256 válido pero capturado antes de que se escribiera el código, y la aprobación del plan se rechaza con «Plan Approval requires workspace source to match the Code Generation directive's pre-planning source floor». El remedio es el mismo: borrar `aidlc/spaces/<space>/intents/<intent>/.aidlc-active-directive.json` y volver a pedir la directiva para que lo recalcule. (learned 2026-09-14) <!-- cid:260903-fix-admin-crud:code-generation:3320bedebcfd24ed2a5bb3e51095b4add01bb67dba7dd2265123b6c5af403d5f -->
- Ante un recibo de verificación bloqueado (revisión, aprobación de plan, frescura de origen), reiniciar el intento de la etapa con un salto `redo` es preferible a buscar un bypass por variable de entorno. El reinicio devuelve el presupuesto de revisión y la autoridad de aprobación sin debilitar ningún guardarraíl, y no cuesta trabajo: los artefactos se conservan y se reentra con `keep`. El coste real es un turno humano de aprobación, que es más barato que degradar la evidencia. (learned 2026-09-14) <!-- cid:260903-fix-admin-crud:code-generation:7ced1205824dddf10850102f01307b5c6df358b2bdafa766c9bbcff3d5bb655a -->
- SIEMPRE diseñar contra la evidencia que existe en el repositorio cuando una etapa recibe entradas ausentes marcadas como previstas por el alcance, en vez de inventar el artefacto que falta. En este proyecto el alcance `bugfix` salta CI Pipeline e Infrastructure Design, así que el Pipeline de Despliegue se apoyó en `docker-compose.yml`, `Dockerfile`, las instrucciones de construcción y el manifiesto de origen. NUNCA redactar una configuración de CI que nadie va a ejecutar solo porque la etapa la declare como entrada. (learned 2026-09-20) <!-- cid:260903-fix-admin-crud:deployment-pipeline:ac3f1855fafaa3d8d1aaeec09d93206826cfadb837b615c59c3afd5d51c04f61 -->
- Cuando el contenido honesto de un artefacto no coincide con lo que su nombre sugiere, decirlo en su primera sección en vez de forzar el contenido al nombre. `cd-config.md` documenta aquí un procedimiento manual y no una configuración de pipeline, porque el destino es local y no hay integración continua; el documento lo declara para que nadie lo lea esperando otra cosa. (learned 2026-09-20) <!-- cid:260903-fix-admin-crud:deployment-pipeline:f23d8b2a6f270a6b97b90aa710921150e4c5ad4b099edf2a07487653c9d43749 -->
- NUNCA ofrecer al humano una opción que yo no puedo ejecutar. Las restricciones deben filtrar la lista de opciones ANTES de presentarla, no corregirse después de que el humano haya elegido. En esta intención se ofreció que el humano facilitara una contraseña de administrador para conducir la verificación de navegador, cuando introducir contraseñas en formularios está fuera de lo permitido con autorización o sin ella; la opción correcta —que inicie él la sesión y yo continúe desde la pestaña autenticada— ya estaba en la lista y debió ser la única. (learned 2026-09-20) <!-- cid:260903-fix-admin-crud:deployment-execution:ee0db78820a58b588ce633db01604c8835763eefbfe31889c0b9f6abe590c0a6 -->
