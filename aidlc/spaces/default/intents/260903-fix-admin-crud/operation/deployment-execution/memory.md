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

- 2026-09-19T00:00:00Z — El cuerpo de la etapa pide preguntar por las comprobaciones previas (¿pasan las pruebas? ¿hacen falta migraciones? ¿están sanos los servicios dependientes?). Se ejecutaron en vez de preguntarlas, porque las cuatro son verificables por comando. Solo se llevó al humano lo que de verdad era suyo: qué hacer con los 29 cambios sin confirmar, si ejecutar el despliegue, y cómo verificar los puntos de navegador.

## Deviations

- 2026-09-19T00:00:00Z — Se ofreció al humano la opción de facilitar la contraseña de administrador para conducir el humo, y era una opción que yo no podía aceptar: introducir contraseñas en formularios está fuera de lo que puedo hacer, con permiso o sin él. Se retiró antes de que la escribiera y se reconduzco a que iniciara él la sesión. El error fue de planteamiento: la restricción debió filtrar las opciones ANTES de presentarlas, no después de que el humano eligiera.
- 2026-09-19T00:00:00Z — El punto 3 del humo escribe sobre datos reales (editar un servicio y guardar). Se ejecutó porque el humano lo autorizó por su nombre al elegir conducir el humo en el navegador, y se dejó el dato como estaba restaurando la duración por el mismo camino. Las escrituras NO autorizadas explícitamente —crear o desactivar un usuario— se omitieron aunque figuran en la lista manual, y se dijo en el informe.

## Tradeoffs

- 2026-09-19T00:00:00Z — Ejecutar el procedimiento encontró dos defectos en artefactos que yo mismo había escrito una etapa antes: la orden de respaldo no funcionaba (faltaba la contraseña y usaba --all-databases) y el orden de pasos producía un CSS al que le faltaba el 28%. Ninguno de los dos era visible leyendo el documento. La lección es sobre el método, no sobre los errores: un runbook sin ejecutar es una hipótesis, y el momento barato de refutarla es este, no durante una vuelta atrás real.
- 2026-09-19T00:00:00Z — La verificación del precio se hizo contra la base y contra la vista SQL, no contra lo que el formulario volvía a mostrar. Un formulario que reexhibe el valor que le enviaste no demuestra que se haya guardado; la consulta directa sí. Es la misma distinción que hacía falta en las pruebas de regresión, y por eso mismo merecía repetirse aquí.

## Open questions

- 2026-09-19T00:00:00Z — Dos servicios (Consulta general, Blanqueamiento dental) tienen precio 0,00 frente a 400,00 y 2.800,00 en el volcado original: daño preexistente de TD-8 que refuta el supuesto A2 de los requisitos. Queda pendiente la decisión humana de restaurarlos, y una comprobación análoga sobre color_agenda='#F4C0D1', que TD-8 también forzaba y que no deja rastro visible.
