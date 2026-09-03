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

<!-- entrada 2026-09-03 -->
### Interpretation
- 2026-09-03: Los cuatro fallos se verificaron leyendo el codigo real, no solo la descripcion. TD-11 (routes/web.php lineas 92 y 94), TD-12 (lineas 88 y 90), TD-8 (ServiciosController lineas 33-34 y 58) y TD-16 (UsuarioController completo) quedan confirmados tal como se describen.
- 2026-09-03: TD-11 y TD-12 no generan preguntas: cada uno admite una sola solucion razonable (reordenar la ruta y borrar el closure muerto). Las preguntas se reservan para las decisiones donde el criterio humano cambia el trabajo.

### Tradeoff
- 2026-09-03: Profundidad Minimal sugiere 2-4 preguntas y habia 5 decisiones candidatas. Se dejo la contrasena de alta como supuesto documentado en lugar de pregunta, porque el propio formulario ya declara la intencion ("Si se deja vacio se genera una contrasena aleatoria"); las otras cuatro si se preguntan.

### Deviation
- 2026-09-03: El alcance de TD-8 es mayor de lo que dice la descripcion. Los formularios de servicios no tienen campo de precio ni de color_agenda, asi que quitar `$data['precio'] = 0` no basta por si solo. Se refleja en Q1 en vez de asumir la correccion minima.
- 2026-09-03: Se detecto un fallo adyacente no listado en la descripcion: el selector de rol solo ofrece tres de los cuatro valores del enum, de modo que editar un usuario con rol `asistente` lo convertiria en `admin` al enviar el formulario. Se incorpora a Q3 en vez de ampliar el alcance en silencio.

### Open question
- 2026-09-03: El esquema de las tablas del dominio vive solo en `database/dentissa.sql`, sin migraciones. phpunit.xml apunta a SQLite en memoria, asi que hoy no hay contra que correr una prueba de regresion. Q4 pone la decision en manos del usuario.
- 2026-09-03: Sesion pausada con las 4 preguntas presentadas y sin responder. El usuario pidio aclararlas antes de decidir; ninguna respuesta quedo registrada. Al reanudar hay que retomar esa conversacion de aclaracion antes de volver a presentar el lote.

<!-- entrada 2026-09-03 reanudacion -->
### Interpretation
- 2026-09-03T21:40:00Z — Al reanudar, la sesion anterior tenia el lote de 4 preguntas presentado y registrado como DECISION_RECORDED pero sin QUESTION_ANSWERED emparejado, asi que las cuatro contaban como no respondidas. Se volvio a presentar el lote completo en vez de inferir respuestas de los mensajes sueltos del chat anterior.
- 2026-09-03T21:40:00Z — El usuario habia adelantado en chat "el rol asistente si se usa" y "quiero precio y color". Se citaron esas señales dentro de las opciones para que no tuviera que reconstruir el contexto, pero se le pidio confirmacion explicita: una frase de chat no es una respuesta registrada.

### Deviation
- 2026-09-03T21:45:00Z — Q3 se respondio con la escapatoria "Other" pidiendo mas explicacion. Se abrio el codigo real (create.blade.php:71-77, edit.blade.php que solo hace @include de create, el ternario por opcion que deja el select sin ninguna marcada, y los cuatro puntos donde CitasController/ExpedientesController/DashboardController/AuthController dependen del rol asistente) antes de volver a presentar la pregunta, en vez de reformularla en abstracto.

### Tradeoff
- 2026-09-03T21:50:00Z — Q4 quedo en D (verificacion manual ahora, cobertura automatica como trabajo aparte). El piso de org.md para el alcance bugfix pide una regresion dirigida al fallo concreto; aqui no hay contra que correrla porque el esquema del dominio vive solo en database/dentissa.sql y phpunit.xml apunta a SQLite en memoria. Se documenta como restriccion explicita en requirements.md y se conserva la mitad del piso que si aplica: la suite existente debe seguir en verde en Build and Test.
- 2026-09-03T21:50:00Z — Q1 quedo en B (precio y color en ambos formularios), que amplia TD-8 mas alla de quitar la sobrescritura. Es una ampliacion consciente pedida por el usuario: sin campo en el formulario el precio seguiria sin poder capturarse desde la interfaz, asi que la correccion minima dejaria el fallo a medias.
