---
lane: fix
status: released                 # open → done → released · escalated (→ escalated_to)
released: v0.20.1 (2026-11-17)
base: f801977                # commit de partida; lo registra la skill al empezar (lo usa lane-check)
relates: [009, 010]          # specs cuyo comportamiento toca ([] si ninguna)
covers: []                   # son observaciones de las specs inferidas, no CA
escalated_to: null           # NNN de la spec si el cambio se escaló al flujo completo
---

# Fix: detalles menores de Servicios y Usuarios

## Problema
Las specs inferidas 009 (servicios) y 010 (usuarios) anotaron en «Observaciones» varios defectos
visibles en las pantallas del admin, que siguen así en la v0.20.0:

1. **Servicios, buscador.** La pantalla tiene un buscador («Nombre, descripción...»), pero
   `ServiciosController::index` ignora `buscar` y siempre lista todos. Las dos tarjetas («Servicios
   registrados» y «Resultados encontrados») muestran el mismo número.
2. **Servicios, ruta sin pantalla.** `Route::resource` registra `GET /admin/servicios/{id}`, pero el
   controlador no tiene `show`. Pedir esa dirección da un error del servidor en lugar de un 404.
3. **Servicios, avisos.** Dar de alta, editar o eliminar un servicio no muestra ningún aviso de
   «guardado», a diferencia del resto de pantallas del admin.
4. **Usuarios, resumen por rol.** Las tarjetas del listado cuentan administradores, dentistas y
   pacientes, pero no asistentes.
5. **Usuarios, avisos.** Crear o editar un usuario no muestra ningún aviso de «guardado».

El usuario pidió arreglarlos (2026-11-17), antes de validar las dos specs con `/specify --edit`.

## Reproducción
`tests/Feature/DetallesServiciosUsuariosTest.php`, con un test por defecto (dos para el buscador).
Antes del arreglo fallan 6 de 7:

```
⨯ el buscador de servicios filtra por nombre descripcion y categoria   (lista todos)
⨯ una busqueda sin resultados lo dice y ofrece limpiarla               (lista todos)
⨯ la ficha de un servicio no existe y responde 404
  Expected response status code [404] but received 500.
  BadMethodCallException: Method App\Http\Controllers\ServiciosController::show does not exist.
⨯ alta edicion y borrado de un servicio avisan
  Failed asserting that null matches expected 'Servicio registrado correctamente.'.
⨯ el resumen de usuarios cuenta tambien a los asistentes               (no hay tarjeta «Asistentes»)
⨯ alta y edicion de un usuario avisan
  Failed asserting that null matches expected 'Usuario registrado correctamente.'.
Tests:    6 failed, 1 passed (22 assertions)
```

Pasa el de control, que comprueba que sin búsqueda se listan todos los servicios.

## Causa raíz
Son restos del código anterior al flujo, que el `/init` anotó en las specs inferidas sin
corregirlos:
- **Buscador:** la vista se hizo con buscador, pero `index` se quedó en `Servicio::all()`, y las dos
  tarjetas leían el mismo `$servicios->count()`.
- **Ruta sin pantalla:** `Route::resource` registra las siete acciones y el controlador nunca tuvo
  `show`. Laravel lanza `BadMethodCallException` (500) en vez de dejar que la ruta no exista
  (404).
- **Avisos:** `ServiciosController` (`store`, `update` y `destroy`) y `UsuarioController` (`store`
  y `update`) redirigen sin `->with('success', …)`, a diferencia de personal y pacientes.
- **Resumen por rol:** `$totales` se escribió con tres roles y la vista con tres tarjetas en una
  rejilla de tres columnas en línea. El rol `asistente` llegó después.

## Cambio
- `app/Http/Controllers/ServiciosController.php`:
  - `index` filtra por `buscar` (nombre, descripción o categoría, con `LIKE` y bindings) y pasa
    `$total` sin filtrar.
  - Avisos «Servicio registrado correctamente.», «Servicio actualizado correctamente.» y
    «Servicio eliminado.».
- `resources/views/servicios/index.blade.php`:
  - La tarjeta «Servicios registrados» muestra `$total`.
  - Con una búsqueda sin resultados, en lugar de «No se encontraron servicios registrados» y
    «Registrar primer servicio», dice «Ningún servicio coincide con la búsqueda.» y ofrece «Ver
    todos los servicios».
- `routes/web.php`: `Route::resource('servicios', …)->except('show')`. Ya nada enlazaba a
  `admin.servicios.show`.
- `app/Http/Controllers/UsuarioController.php`:
  - `$totales['asistente']`.
  - Avisos «Usuario registrado correctamente.» y «Datos del usuario actualizados.».
- `resources/views/usuarios/index.blade.php`:
  - Tarjeta «Asistentes», con el icono de persona sobre `--amber-bg` y `--amber`, tokens ya
    existentes.
  - La rejilla en línea de tres columnas pasa a la clase `.stats-grid` del sistema: 4 columnas, y
    2 en el móvil por `movil.css`.
  - Por la review ligera (menor 1), los asistentes también aparecen en el filtro por rol
    («Asistentes») y su grupo del listado lleva título y color ámbar, en vez de «Asistente» en gris.
  - Por la review ligera (menor 3), un filtro sin resultados dice «Ningún usuario coincide con el
    filtro.» y ofrece «Ver todos los usuarios», en lugar de «No hay usuarios registrados aún». El
    botón «Agregar primer usuario» ya no apunta a `#`, sino al alta.
- `tests/Feature/DetallesServiciosUsuariosTest.php`: 9 tests nuevos, dos de ellos por la review.

## Escalado
`lane-check` al empezar y al cerrar: 6 archivos, 5 de código (el límite es 5). No hay que escalar.
No toca seguridad, datos, contratos ni el sistema de diseño: usa clases y tokens existentes. Quitar
la ruta `show` no rompe ningún contrato, porque respondía con un 500.

## Verificación
- **Test nuevo:** 9 en verde.
  - Sin el arreglo fallaron 6 de los 7 primeros en la pasada de la review ligera
    (`6 failed, 1 passed`), cada uno por su defecto.
  - Los dos añadidos por la review fallan sin el cambio de la vista en una pasada propia
    (`2 failed`).
- **Tests del área:** el filtro `DetallesServiciosUsuariosTest|AdminVistasSmokeTest|UsuariosAdminTest`
  da 21 en verde. La review pasó además 561 tests relacionados.
- **Suite completa:** 1279 en verde (17574 aserciones) antes de las correcciones de la review.
  - La repetición final, con las correcciones (solo `usuarios/index.blade.php` y dos tests), la
    paró Claude Code por falta de memoria del equipo, y se paró también su proceso en el contenedor.
  - Tras las correcciones pasan los tests del área (21) y los dos nuevos. La suite completa la
    vuelve a ejecutar la CI del PR.
- **Pint `--test`** sobre los controladores, `routes/web.php` y el test: PASS.
- **Escáneres:** semgrep `p/php` sobre lo tocado, 0 hallazgos; gitleaks en modo carpeta, sin
  fugas. No hay dependencias, así que no aplica la SCA.
- `capturar.mjs --medir` no aplica: no se tocan `layouts/app.blade.php`, `menu.js` ni `movil.css`.
  «Servicios» del admin se compara con la línea base a 1024 y 1440. Sin búsqueda la pantalla no
  cambia: las dos tarjetas siguen mostrando el mismo número.
- **Review ligera** (un subagente, solo el diff y la ficha): aprobada, sin bloqueantes.
  - Las cinco causas explican su síntoma y el arreglo actúa sobre cada una.
  - Nada enlaza a `admin.servicios.show`.
  - `.stats-grid` mejora el móvil, porque el estilo en línea de tres columnas ganaba a `movil.css`.
  - Los avisos salen una sola vez.
  - Hallazgos y qué se hizo:
    - Menor 1 (asistentes en el filtro y en los grupos): corregido.
    - Menor 3 (estado vacío de usuarios con filtro y enlace `#`): corregido.
    - Laguna del test del enlace «Ver todos los servicios»: corregida.
    - Menor 2 (los comodines `%` y `_` del buscador no se escapan): aceptado en Decisiones.
    - El 404 de la ficha de servicio depende de la ruta de reserva de la 044 (sin ella sería un
      405): anotado, el test lo vigila.
- `python .ai/bin/aidd.py validate`: sin errores.
- Liberada en v0.20.1: producción aprobada por el responsable (gimi34) el 2026-11-17 con «desplegar v0.20.1 a producción»; `desplegar.sh v0.20.1` en 138 s y `verificar.sh` interna y pública, todo OK.

## Decisiones
| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-11-17 | supuesto | Cinco defectos pequeños de dos specs; ¿una ficha o cinco? | Una sola ficha: son de las mismas dos pantallas del admin, el usuario los pidió juntos y caben en el límite del carril | usuario |
| 2026-11-17 | implícita | ¿Qué es lo correcto en cada uno? Ninguna spec los recoge como CA | Lo que ya anuncia la propia pantalla (el buscador dice «Nombre, descripción...»), lo que hacen las demás pantallas del admin (aviso de «guardado», tarjeta por rol) y un 404 para una dirección que no tiene pantalla | el síntoma |
| 2026-11-17 | supuesto | Review ligera, menor 2: `%` y `_` escritos en el buscador hacen de comodín | Se acepta: van con bindings (sin riesgo) y el buscador de usuarios hace lo mismo. Escaparlo solo en uno los haría incoherentes | agente |
| 2026-11-17 | supuesto | Review ligera, menores 1 y 3: asistentes en el filtro y estado vacío con filtro, que no anotaba la 010 | En esta ficha: son el mismo defecto («falta el asistente») y el mismo caso que el buscador de servicios, en un archivo ya tocado | agente |
| 2026-11-17 | supuesto | Borrar un servicio lo elimina de verdad (009 CA6), mientras el resto del sistema solo desactiva | Fuera de esta ficha: cambiarlo es decidir otro comportamiento (`/specify --edit 009`). El servicio ya se puede desactivar desde su edición | agente |
