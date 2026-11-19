---
spec: 051-ciudad-lista-cerrada
plan: plan.md
impl_base: 0667c58
status: approved
---

# Tareas · 051 Ciudad elegida de una lista cerrada

Formato:
`- [ ] T### [P] <verbo + qué> — <archivos> — hecho cuando: <criterio> — cubre: CA# — depende: T###`
- T001–T089: trabajo. T090–T099: reservadas.
- `[P]` = paralelizable con las demás `[P]` de su grupo una vez cumplidas sus dependencias (no
  comparte archivos con otra tarea abierta).
- `[-]` = obsoleta (`— obsoleta: <motivo> (AAAA-MM-DD)`): no cuenta como abierta ni como hecha.
- Bajo una tarea: `  - nota: …`, `  - bloqueo: AAAA-MM-DD — <qué falta> — <skill a ejecutar>`.
- La comprobación a mano va en la tarea que construye la pieza.

Tests con `docker compose exec app php artisan test --filter <Clase>` (suite completa al cerrar cada
fase) y Pint sobre los archivos tocados. Los tests de `CiudadListaCerradaTest` comparten archivo:
sus tareas van en serie.

## Constitution Check
Sobre el plan (constitución 1.3.0), lo que el desglose podría romper:
- ✅ P1: ninguna tarea de código antes de aprobar este `tasks.md`; T002 pone la rama al día tras la
  050 (D8) antes de tocar código.
- ✅ P2: cada tarea de implementación depende de su test (T010–T016), que falla antes. La parte
  de caracterización de T014 (CA11 y el 403 del asistente) se comprueba rompiendo el código que
  protege y se anota. El cotejo con INEGI se anota en T020.
- ❌ aceptado P3 (plan, aprobado por el usuario el 2026-11-18): los endpoints modificados conservan
  su autorización de hoy (RS1.a → 014, RS2.a → 015). Ninguna tarea cambia rutas ni middleware; T014
  fija el 403 del asistente en la edición; T024 y T025 leen la ciudad guardada del paciente de la
  sesión o de la ruta, nunca de la petición.
- ✅ P4: la regla (T021) va en los cinco `validate()` (T023–T025); T026 aplica `?ciudad=` con
  binding solo si está en la lista; T028 actualiza por id sin `whereRaw`; semgrep en T092.
- ✅ P5: gitleaks sobre el diff en T092; los tests crean sus pacientes sintéticos.
- ✅ P6: sin dependencias nuevas; `composer audit` y `auditar-npm.mjs` en T092.
- ❌ aceptado P7 (plan, aprobado por el usuario el 2026-11-18): los cambios desde los formularios
  siguen sin evento (OB1.e → 022, OB1.f → 021). El evento de la corrección (T028) tiene test (T016)
  antes de la migración (T029).
- ✅ P8: ninguna tarea añade `Log::`; T016 comprueba que el evento solo lleva enteros.
- ✅ P9: T001 (sistema) va antes que el CSS y las vistas (T022–T025); T015 exige solo tokens en los
  bloques `/* Spec 051 */` y foco y borde ≥ 3:1; ninguna tarea añade `style=""`.
- ➖ P10: `/registro` no cambia título, descripción ni canónica; `PaginasPublicasTest` en la suite
  de T092.

## Preparación
- [x] T001 Añadir al sistema de diseño la variante «Aviso de campo» de `.field-hint` (texto en `--text`, enlazado con `aria-describedby`, para avisos que no son errores) en «Campo de formulario», subir la versión a 1.20.0 (o la siguiente libre) y añadir la fila de «Decisiones» — docs/design/system.md, docs/design/system.html — hecho cuando: la variante aparece en los dos documentos con su contraste (21:1 sobre `--white`) y `python .ai/bin/aidd.py validate` no da errores
- [x] T002 Poner la rama al día con `main` cuando la 050 esté fusionada (D8), resolver los choques y fijar la línea base de la suite — rama `feature/051-ciudad-lista-cerrada` — hecho cuando: la rama contiene la 050, no quedan conflictos y `php artisan test` pasa completo (resultado anotado en la nota de la tarea)
  - nota: rebase sobre `main` (02f4c3b, con la 050 liberada en v0.22.0); único choque, la fila de la 050 en `docs/specs/README.md`. Línea base: 1370 tests, 0 fallos (534 s), en un contenedor propio con el código del worktree.

## Tests (antes de implementar)
- [x] T010 [P] Escribir los tests unitarios de la lista y de la regla. `CiudadesTest`: 113 de Michoacán y 4 de Guerrero, sin repetidos; orden alfabético por clave sin tildes en cada grupo; «Otro lugar» al final de `nombres()`; `normalizar()` con mayúsculas, tildes, espacios dobles y finales, cada alias de D5, `null` para «LC», «zihuayork» y «colima», y estable al aplicarlo dos veces. `CiudadDeLaListaTest`: acepta un nombre de la lista; rechaza «Ciudad de México», 101 caracteres y `<b>x</b>` con «Elige una ciudad de la lista»; rechaza un array con el mismo mensaje; acepta la guardada fuera de la lista idéntica, también si tenía espacios sobrantes, y rechaza otra ciudad fuera de la lista distinta de la guardada — tests/Unit/CiudadesTest.php, tests/Unit/CiudadDeLaListaTest.php — hecho cuando: fallan porque las clases no existen (anotado) — cubre: CA1, CA5, CA6, CA8, CA10
  - nota: fallan porque `App\Pacientes\Ciudades` y `App\Rules\CiudadDeLaLista` no existen (36 fallos).
- [x] T011 Escribir los tests de marcado de los cinco formularios en cuatro vistas (registro con correo, registro tras Google con la sesión `google_registro`, perfil, alta y edición): `select[name=ciudad]` con «Sin indicar» primero, `optgroup` «Michoacán» y «Guerrero», «Otro lugar» al final y ningún `input[name=ciudad]`; la ciudad guardada elegida en perfil y edición; etiqueta asociada por `for`/`id` (también en el perfil); clase `campo-ciudad` en los dos registros y el perfil — tests/Feature/CiudadListaCerradaTest.php — hecho cuando: fallan porque hoy el campo es un `input` de texto (anotado) — cubre: CA1, CA2, CA3
  - nota: fallan porque hoy el campo es un `input` de texto.
- [x] T012 Escribir los tests de guardar y rechazar en los cinco puntos de entrada: un nombre de la lista se guarda tal cual; «Sin indicar» deja `ciudad` en `null`; «Ciudad de México», 101 caracteres, `<b>x</b>` y `ciudad[]=x` se rechazan con el mensaje junto al campo, sin error 500, sin cambios en la BD y con `old()` del resto; en los dos registros el error va enlazado con `aria-invalid` y `aria-describedby="error-ciudad"`; en el registro con correo, un error de ciudad o solo de `terminos` abre el paso 2 (`goTo(2)` como código del script, no en un comentario, y sin `currentStep = 2`) y uno de `email` abre el paso 1 aunque también haya error de ciudad (sin `goTo(2)`); cada opción del HTML del alta se acepta al guardarla (RNF: misma lista) — tests/Feature/CiudadListaCerradaTest.php — hecho cuando: fallan porque hoy se acepta cualquier texto (anotado) — cubre: CA4, CA5 — depende: T011
  - nota: fallan porque hoy se acepta cualquier texto. Guardar un nombre de la lista y «Sin indicar» ya funcionan hoy: pasan desde el principio y los protege T021 al exigir la lista.
- [x] T013 Escribir los tests de la ciudad antigua: el perfil y la edición con una ciudad fuera de la lista muestran la opción «<valor> (no está en la lista)» elegida y el aviso con su `id` en `aria-describedby` (junto al error si lo hay); guardar sin cambiarla la conserva; elegir otra la sustituye; elegir «Sin indicar» con un error en otro campo la deja elegida al volver al formulario; un error en el formulario de contraseña del perfil deja elegida la ciudad guardada; enviar la ciudad antigua de otro paciente en la edición, el perfil o el alta se rechaza (CA6, TM2); una ciudad antigua `<script>alert(1)</script>` sale escapada en la opción y en el aviso (TM3) — tests/Feature/CiudadListaCerradaTest.php — hecho cuando: fallan porque hoy no hay opción, aviso ni rechazo (anotado) — cubre: CA6, CA10 — depende: T012
  - nota: fallan porque hoy no hay opción, aviso ni rechazo. «Guardar sin cambiar» y «elegir otra» pasan hoy porque se acepta cualquier texto.
- [x] T014 Escribir los tests del directorio y las caracterizaciones: el filtro solo ofrece ciudades de la lista con pacientes activos más «Otro lugar» si lo hay, en el orden de la lista con «Otro lugar» al final, y nunca «zihuayork»; «Morelia» sigue en el filtro aunque otro paciente tenga «morelia»; `?ciudad=zihuayork` se ignora y lista todos (TM4); «Ciudades distintas» no cuenta «Otro lugar»; caracterización de CA11 (la ficha y el directorio muestran la ciudad guardada, también una antigua) y del 403 del asistente al editar un paciente — tests/Feature/CiudadListaCerradaTest.php — hecho cuando: los de CA7 y TM4 fallan hoy (anotado); los de caracterización pasan y fallan al romper un momento la celda «Ciudad» de la ficha y el `abort(403)` de `PacientesController::update` (anotado `comprobado rompiendo …`) — cubre: CA7, CA11 — depende: T013
  - nota: los de CA7 y TM4 fallan hoy. Caracterización: comprobado rompiendo la ciudad de la ficha (`pacientes/show`) y la celda del directorio (`pacientes/index`). El asistente no tiene ruta de edición: lo rechaza el rol de las rutas de admin y dentista, y además el `abort(403)`; comprobado rompiendo los dos a la vez, porque cada uno cubre al otro.
- [x] T015 Escribir `test_el_bloque_051_solo_usa_tokens` y `test_el_foco_y_el_borde_de_la_ciudad_llegan_a_3_1` con el trait `ReglasCss`: los bloques `/* Spec 051 */` de `app.css` y `auth.css` existen y no tienen literales (con control positivo); `app.css` define `.field-hint-aviso` con `color: var(--text)`; los dos dan a `select.campo-ciudad` y a su `:focus` borde `--border-control` y, en `:focus-visible`, anillo `--focus-ring`; `auth.css` declara `--border-control` con el valor de `app.css`; contraste ≥ 3:1 de esos dos tokens sobre `--white` y `--p50` — tests/Feature/CiudadListaCerradaTest.php — hecho cuando: fallan porque los bloques no existen (anotado) — depende: T014
  - nota: fallan porque los bloques no existen.
- [x] T016 [P] Escribir los tests de la corrección: con pacientes creados con «lazaro cardenas», «MORELIA », «Zihuatanejo», «La Unión», «LC» y «Petatlán», `CorreccionCiudades::ejecutar()` corrige las cuatro primeras al nombre de la lista, deja «LC» y «Petatlán» intactas y devuelve los totales; una segunda ejecución no cambia nada (`corregidos` 0); queda un evento `pacientes_ciudades_corregidas` con recurso `pacientes`, `campos` `['ciudad']`, actor nulo y `detalle` con solo `corregidos` y `fuera_de_lista` enteros (relativos a la línea base de la BD de tests), sin ciudades ni nombres; con un almacén de auditoría que falla, no se corrige ningún paciente y se lanza `AuditoriaNoDisponible` — tests/Feature/CorreccionCiudadesTest.php — hecho cuando: fallan porque el servicio no existe (anotado) — cubre: CA8, CA9, CA12
  - nota: fallan porque el servicio no existe.

## Implementación
- [x] T020 Crear `App\Pacientes\Ciudades` con los 113 municipios de Michoacán y los 4 de Guerrero (nombres oficiales y orden de D1), `OTRO_LUGAR`, `ALIAS` (D5), `grupos()`, `nombres()`, `contiene()` y `normalizar()` — app/Pacientes/Ciudades.php — hecho cuando: `CiudadesTest` pasa y la lista está cotejada con el catálogo de INEGI (claves 16 y 12; cotejo anotado en la nota, P2) — cubre: CA1, CA8 — depende: T002, T010
  - nota: lista generada del catálogo oficial de INEGI (servicio `gaia.inegi.org.mx/wscatgeo/v2/mgem`, claves 16 y 12), cotejo 2026-11-18: 113 municipios de Michoacán y Guerrero 12016, 12038, 12048 y 12068. Añade `antigua()`, que usan la regla, el parcial y las vistas para no repetir esa comprobación.
- [x] T021 Crear la regla `App\Rules\CiudadDeLaLista` (`__construct(?string $guardada = null)`, falla si el valor no es una cadena, compara con `trim($guardada)`, mensaje «Elige una ciudad de la lista») — app/Rules/CiudadDeLaLista.php — hecho cuando: `CiudadDeLaListaTest` pasa — cubre: CA5, CA6, CA10 — depende: T020
- [x] T022 Crear el parcial de opciones (opción elegida según D14, opción antigua marcada si `$guardada` no está en la lista, «Sin indicar», grupos y «Otro lugar», todo con salida escapada) y los bloques `/* Spec 051 */` de `app.css` (`.field-hint-aviso` y `select.campo-ciudad`) y de `auth.css` (token `--border-control` y `.page-register select.campo-ciudad`) — resources/views/partials/opciones-ciudad.blade.php, public/css/app.css, public/css/auth.css — hecho cuando: T015 pasa — cubre: CA1, CA10 — depende: T001, T015, T020
  - nota: el registro no carga `movil.css`: el toque de 44 px va en el bloque de `auth.css` (lo encontró la medición en el navegador). El aviso usa `.field-hint.field-hint-aviso` porque `pacientes/create` redefine `.field-hint` en su propio `<style>`.
- [x] T023 Cambiar el campo de ciudad de los dos registros a desplegable con el parcial, como «Género» (mismo `.field-wrap` e icono, clase `campo-ciudad`, sin placeholder, `@error('ciudad')` con `.error-msg` e `id="error-ciudad"`, `aria-invalid` y `aria-describedby`), abrir en `register` el paso que tiene el error (D13: `currentStep` arranca siempre en 1, se quita el bloque `@if` y el `DOMContentLoaded` solo llama a `goTo(2)` si hay errores y ninguno es del paso 1) y poner `['nullable', new CiudadDeLaLista]` en `register` y `contactoStore` — app/Http/Controllers/RegisterController.php, resources/views/auth/register.blade.php, resources/views/auth/registro-contacto.blade.php — hecho cuando: los casos de registro de T011 y T012 pasan · verificación manual: los dos registros a 320, 390 y 1440 px contra `design.md` (misma forma que «Género», con el borde algo más oscuro de D12; nombre largo cortado sin desbordar), foco visible, un error de ciudad abre el paso 2 en el navegador con un único paso visible, y con teclado «Laz» salta a Lázaro Cárdenas — cubre: CA1, CA4, CA5 — depende: T011, T012, T021, T022
  - nota: verificación manual OK 2026-11-18 (usuario): registro con correo en http://localhost:8051; además, medido en el navegador a 320, 390 y 1440 px (toque, foco, sin desbordes, un error de ciudad abre solo el paso 2 y «Laz» salta a Lázaro Cárdenas). El registro con Google no se abrió en el navegador porque necesita una cuenta real: usa el mismo campo y lo cubren T011–T013.
- [x] T024 Cambiar el campo de ciudad del perfil (`label for`/`id`, desplegable con el parcial y clase `campo-ciudad`, error `.field-hint field-hint-error`, aviso `.field-hint field-hint-aviso` en `aria-describedby`) y en `PerfilController::update` leer el paciente antes de validar y pasar `new CiudadDeLaLista($paciente?->ciudad)` — app/Http/Controllers/PerfilController.php, resources/views/cliente/perfil.blade.php — hecho cuando: los casos de perfil de T011–T013 pasan · verificación manual: perfil a 320, 390 y 1440 px con y sin ciudad antigua (aviso no rojo, opción marcada; a 320 px, una ciudad antigua de 100 caracteres no desborda), foco visible y lector de pantalla que lee el aviso; con teclado «Laz» salta a Lázaro Cárdenas — cubre: CA2, CA4, CA5, CA6, CA10 — depende: T013, T021, T022
  - nota: verificación manual OK 2026-11-18 (usuario): perfil con y sin ciudad antigua; además, medido en el navegador a 320, 390 y 1440 px.
- [x] T025 Cambiar el campo de ciudad del alta y la edición al patrón de «Género» (`field-error`, `.error-msg`, `aria-invalid`, `aria-describedby` con error y aviso), con el aviso solo en la edición con ciudad antigua, y poner `new CiudadDeLaLista` en `store` y `new CiudadDeLaLista($paciente->ciudad)` en `update` con el paciente cargado antes de `validate()` — app/Http/Controllers/PacientesController.php, resources/views/pacientes/create.blade.php — hecho cuando: los casos de alta y edición de T011–T013 pasan · verificación manual: alta y edición a 320, 390 y 1440 px contra `design.md`, la edición con ciudad antigua (aviso y opción marcada; a 320 px, una de 100 caracteres no desborda) y con error más aviso; con teclado «Laz» salta a Lázaro Cárdenas — cubre: CA3, CA4, CA5, CA6, CA10 — depende: T013, T021, T022
  - nota: verificación manual OK 2026-11-18 (usuario): alta, edición con ciudad antigua y directorio; además, medido en el navegador a 320, 390 y 1440 px, también con una ciudad antigua de 100 caracteres.
- [x] T026 Sacar las ciudades del filtro intersecando `Ciudades::nombres()` en PHP con las guardadas de los pacientes activos (sin `DISTINCT`, en el orden de la lista), calcular `$totalCiudades` sin «Otro lugar», aplicar `?ciudad=` solo si está en la lista y mostrar «Ciudades distintas» con `$totalCiudades` — app/Http/Controllers/PacientesController.php, resources/views/pacientes/index.blade.php — hecho cuando: los casos de CA7 y TM4 de T014 pasan y sus caracterizaciones siguen en verde — cubre: CA7, CA11 — depende: T014, T020, T025
- [x] T027 Ajustar `MovilPacientesExpedientesTest`: en `setUp`, p1 pasa a «Morelia» para que su edición no muestre el aviso; la comprobación de «se conserva lo escrito» del alta y la edición pasa de `input[name=ciudad]` a `contacto_emergencia`; el caso de `ciudad` con 101 caracteres sigue esperando error — tests/Feature/MovilPacientesExpedientesTest.php — hecho cuando: `MovilPacientesExpedientesTest` pasa completo — cubre: CA5 — depende: T025
- [x] T028 Crear `App\Pacientes\CorreccionCiudades::ejecutar()` (recorrido por id con `chunkById`, comparación en PHP, `update` por id, todo en `DB::transaction` con el evento crítico `pacientes_ciudades_corregidas` al final) — app/Pacientes/CorreccionCiudades.php — hecho cuando: `CorreccionCiudadesTest` pasa — cubre: CA8, CA9, CA12 — depende: T016, T020
- [x] T029 Crear la migración de datos que llama al servicio, con `down()` vacío y el comentario de por qué no se deshace y de que usa la lista vigente — database/migrations/2026_11_18_000000_corregir_ciudades_de_pacientes.php — hecho cuando: `migrate --force` sobre `dentissa_test` la ejecuta y deja su evento; `migrate:rollback --step=1` y otro `migrate` no corrigen nada (`corregidos` 0); la suite completa pasa — cubre: CA8, CA12 — depende: T028
  - nota: comprobado en una base de datos ficticia y en una copia local: 7 corregidas y 7 fuera de la lista; tras `migrate:rollback` y otro `migrate`, 0 corregidas. Controles de TM1–TM6 comprobados rompiéndolos uno por uno.
- [x] T030 Hacer que `RegistroAuditoriaTest` e `IdentificadorPeticionTest` miren solo los eventos escritos durante cada test, con el trait nuevo `EventosDelTest` — tests/Feature/Concerns/EventosDelTest.php, tests/Feature/Auditoria/RegistroAuditoriaTest.php, tests/Feature/Auditoria/IdentificadorPeticionTest.php — hecho cuando: los dos pasan con el evento de la migración ya en la base de tests y las comprobaciones no cambian — cubre: CA12 — depende: T029
  - nota: añadida durante /implement: la migración deja su evento antes de la suite (la CI migra antes de los tests) y la auditoría no admite borrados; 19 tests de la 020 suponían la tabla vacía (decisión del usuario, 2026-11-18).
- [x] T031 Hacer lo mismo en `ConsultaAuditoriaTest` y `DenegacionesAuditadasTest` — tests/Feature/Auditoria/ConsultaAuditoriaTest.php, tests/Feature/Auditoria/DenegacionesAuditadasTest.php — hecho cuando: `tests/Feature/Auditoria` pasa completo con el evento de la migración en la base de tests — cubre: CA12 — depende: T030
  - nota: `tests/Feature/Auditoria` pasa completo (111 tests) con el evento de la migración en la base de tests.
  - nota: añadida durante /implement: mismo motivo que T030.

- [x] T032 Escribir el test de R1: el botón «Crear mi cuenta» del registro con correo, antes del envío nativo, comprueba el paso 1 y, si no es válido, vuelve a él (`validateStep(1)` y `goTo(1)` en el manejador del botón, como código del script y no en un comentario) — tests/Feature/CiudadListaCerradaTest.php — hecho cuando: falla hoy porque el botón no tiene ese manejador (anotado) — cubre: CA5 — depende: T031
  - nota: falla antes del arreglo: el botón no tiene el manejador.
  - nota: añadida por /review: R1
- [ ] T033 Hacer que «Crear mi cuenta» vuelva al paso 1 y marque el campo cuando el paso 1 no es válido (tras un error del servidor la contraseña vuelve vacía), y dejar una sola línea en blanco donde estaba el bloque de `currentStep` — resources/views/auth/register.blade.php — hecho cuando: T032 pasa · verificación manual: en el navegador, con un error de ciudad devuelto por el servidor, «Crear mi cuenta» abre el paso 1 con la contraseña marcada, y al escribirla y volver al paso 2 la cuenta se crea — cubre: CA5 — depende: T032
  - nota: añadida por /review: R1, R6
- [ ] T034 Ajustar los tests de la corrección y de los formularios: `CorreccionCiudadesTest` usa `EventosDelTest` y exige un único evento escrito durante el test (R7) y añade un test que ejecuta `up()` de la migración y comprueba su evento (R5); `CiudadListaCerradaTest` usa `formularios` en lugar de `formulariosSinEsaCiudad` (R4) — tests/Feature/CorreccionCiudadesTest.php, tests/Feature/CiudadListaCerradaTest.php — hecho cuando: los dos pasan; el test nuevo de la migración falla si `up()` no llama al servicio (comprobado rompiéndolo) — cubre: CA8, CA12 — depende: T033
  - nota: añadida por /review: R4, R5, R7
## Integración y documentación
- [x] T090 Actualizar `docs/architecture.md` si cambió la estructura: módulo `app/Pacientes/` (`Ciudades`, `CorreccionCiudades`), la regla `CiudadDeLaLista` y la migración de datos; y en `AGENTS.md` → «Mapa del repositorio», la fila de `app/Pacientes/` y la regla en `app/Rules/`
- [x] T091 Actualizar `docs/deployment.md` y `docs/observability.md` si cambiaron variables, entornos, pasos de deploy, logs, eventos de auditoría o métricas: fila del evento `pacientes_ciudades_corregidas` en la tabla de eventos de `observability.md`; en `deployment.md`, que la migración de la 051 no se deshace con `volver.sh`, que el valor original está en la copia previa y que, si la verificación falla, se usa `volver.sh` aunque `desplegar.sh` sugiera `restaurar.sh` (la corrección es válida para la versión anterior y restaurar perdería lo guardado desde la copia)
- [ ] T092 Marcar spec como `implemented`: suite completa, Pint sobre lo tocado, gitleaks sobre el diff de la spec, semgrep `p/php` sobre los PHP tocados, `composer audit` y `auditar-npm.mjs` sin altas ni críticas, criterios `[x]` y `docs/specs/README.md`
  - nota: suite completa 1497 tests en verde (con la migración aplicada, como en la CI); Pint sin cambios en los 17 PHP tocados; gitleaks y semgrep `p/php` sin hallazgos sobre los archivos del diff; `composer audit` y `auditar-npm.mjs` sin altas ni críticas.

## Despliegue (lo ejecuta `/release`)
- [ ] T095 Desplegar a staging y verificar criterios de aceptación. No hay staging: la comprobación se hace en producción, dentro de T097 (registro, perfil, alta y edición con el desplegable; filtro del directorio limpio)
- [ ] T096 Aprobación humana para producción
- [ ] T097 Desplegar a producción y vigilar métricas del plan (Rollout): copia de la base, `deploy/desplegar.sh vX.Y.Z` (incluye la migración de datos), `verificar.sh`, el evento `pacientes_ciudades_corregidas` en la pantalla de auditoría con sus totales y la comprobación de T095; `5xx` de registro, perfil y pacientes la primera hora. Rollback: `bash deploy/volver.sh <tag anterior>`, no `restaurar.sh` (la corrección se queda; el original, en la copia)
- [ ] T098 Marcar spec como `released`; en las specs 002, 006 y 011 anotar en su Historial que la ciudad se elige de una lista cerrada (051), recordando que son `inferred`

## Cobertura
| Criterio | Tarea(s) de test | Tarea(s) de implementación |
|---|---|---|
| CA1 | T010, T011 | T020, T022, T023 |
| CA2 | T011 | T024 |
| CA3 | T011 | T025 |
| CA4 | T012 | T023, T024, T025 |
| CA5 | T010, T012, T032 | T021, T023, T024, T025, T027, T033 |
| CA6 | T010, T013 | T021, T024, T025 |
| CA7 | T014 | T026 |
| CA8 | T010, T016, T034 | T020, T028, T029 |
| CA9 | T016 | T028 |
| CA10 | T010, T013 | T021, T022, T024, T025 |
| CA11 | T014 | T026 |
| CA12 | T016, T034 | T028, T029, T030, T031 |

| Amenaza (TM#) | Tarea(s) de control | Tarea(s) de test |
|---|---|---|
| TM1 | T021, T023, T024, T025 | T010, T012 |
| TM2 | T021, T024, T025 | T010, T013 |
| TM3 | T022 | T013 |
| TM4 | T026 | T014 |
| TM5 | T028 | T016 |
| TM6 | T028 | T016 |

| Cambio del plan (módulo) | Tarea(s) |
|---|---|
| app/Pacientes/Ciudades.php | T020 |
| app/Rules/CiudadDeLaLista.php | T021 |
| app/Pacientes/CorreccionCiudades.php | T028 |
| database/migrations/2026_11_18_000000_corregir_ciudades_de_pacientes.php | T029 |
| resources/views/partials/opciones-ciudad.blade.php | T022 |
| app/Http/Controllers/RegisterController.php | T023 |
| app/Http/Controllers/PerfilController.php | T024 |
| app/Http/Controllers/PacientesController.php | T025, T026 |
| resources/views/auth/register.blade.php, auth/registro-contacto.blade.php | T023 |
| resources/views/cliente/perfil.blade.php | T024 |
| resources/views/pacientes/create.blade.php | T025 |
| resources/views/pacientes/index.blade.php | T026 |
| public/css/app.css | T022 |
| public/css/auth.css | T022 |
| docs/design/system.md, docs/design/system.html | T001 |
| tests/Feature/MovilPacientesExpedientesTest.php | T027 |
| tests/Feature/Concerns/EventosDelTest.php, tests/Feature/Auditoria/ (añadido durante /implement) | T030, T031 |

| Requisito no funcional | Tarea(s) de test o verificación manual (T095) |
|---|---|
| WCAG 2.1 AA en los campos tocados | T011, T013 (etiqueta y `aria-describedby`) · verificación manual en T023, T024, T025 |
| Elegir tecleando las primeras letras | verificación manual en T023, T024, T025 |
| Descubribilidad del registro sin cambios | T092 (`PaginasPublicasTest` en la suite) |
| La misma lista en formularios y validación | T012 |
