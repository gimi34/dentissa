---
spec: 054-paciente-sin-correo
plan: plan.md
impl_base: c97daaf
status: approved
---

# Tareas · 054 Alta de pacientes sin correo

Formato:
`- [ ] T### [P] <verbo + qué> — <archivos> — hecho cuando: <criterio> — cubre: CA# — depende: T###`
- T001–T089: trabajo. T090–T099: reservadas.
- `[P]` = paralelizable con las demás `[P]` de su grupo una vez cumplidas sus dependencias (no
  comparte archivos con otra tarea abierta).
- `[-]` = obsoleta (`— obsoleta: <motivo> (AAAA-MM-DD)`): no cuenta como abierta ni como hecha.
- Bajo una tarea: `  - nota: …`, `  - bloqueo: AAAA-MM-DD — <qué falta> — <skill a ejecutar>`.
- La comprobación a mano va en la tarea que construye la pieza.

Tests con `docker compose exec app php artisan test --filter <Clase>` (suite completa al cerrar
cada fase) y Pint sobre los archivos tocados, en un contenedor con el código del worktree
`dentissa-054`. Los tests de `PacienteSinCorreoTest` comparten archivo: sus tareas van en serie. Lo
mismo ocurre con las tareas de `PacientesController`.

## Constitution Check
Sobre el plan (constitución 1.3.0), lo que el desglose podría romper:
- ✅ P1: ninguna tarea de código antes de aprobar este `tasks.md` y pasar `/analyze 054`.
- ✅ P2: cada tarea de implementación depende de su test (T010–T017), que falla antes por la razón
  anotada. Las partes de caracterización (CA7, CA8 y el 403 del asistente) se comprueban rompiendo
  el control en T025 (`role:` de las rutas y `abort(403)` de la edición) y en T029 (contraseña
  conocida y guarda de Google), según D10, y se anota.
- ❌ aceptado P3 (plan, aprobado por el usuario el 2026-11-20): `store`, `edit` y `update`
  conservan su autorización actual (RS1.a → 014). Ninguna tarea cambia rutas ni middleware; los
  enlaces del aviso usan la ruta de ficha del rol (T022, D7).
- ✅ P4: las reglas nuevas van en los `validate()` de `store` (T021) y `update` (T024);
  `PosiblesDuplicados` (T020) filtra con `LIKE ?` y binding, sin `whereRaw`; los nombres del aviso
  se pintan con la salida escapada de Blade (T022). Semgrep en T092.
- ✅ P5: los tests crean sus pacientes sintéticos; gitleaks sobre el diff en T092.
- ✅ P6: sin dependencias nuevas; `composer audit` y `auditar-npm.mjs` en T092.
- ❌ aceptado P7 (plan, aprobado por el usuario el 2026-11-20): el resto de ediciones de pacientes
  sigue sin evento (OB1.e → 022). Los dos eventos nuevos tienen test (T016) antes de su código (T021
  y T024).
- ✅ P8: ninguna tarea añade `Log::`; T016 comprueba que `detalle` solo lleva booleanos.
- ✅ P9: T001 (sistema) va antes del CSS y las vistas (T022, T023); T017 exige solo tokens en el
  bloque `/* Spec 054 */` (también `.marca-sin-correo`) y los contrastes del aviso; el icono del
  aviso lleva su tamaño en atributos del SVG, como los demás iconos; ninguna tarea añade `style=""` ni toca
  `movil.css` ni `layouts/app.blade.php`.
- ➖ P10: sin rutas públicas; `PaginasPublicasTest` en la suite de T092.

## Preparación
- [x] T001 [P] Añadir al sistema de diseño el componente «Aviso de posible duplicado» (`.aviso-duplicado`: caja `--amber-bg` con borde `--amber`, título y texto en `--text`, acciones en `.acciones-formulario` con el botón principal sobre `--p500`, en columna por debajo de 900 px, `role="alert"`) en «Aviso, diálogo y mensajes», con sus contrastes (18.31:1, 6.73:1 y 5.42:1), subir la versión a 1.21.0 (o la siguiente libre) y añadir la fila de «Decisiones» — docs/design/system.md, docs/design/system.html — hecho cuando: el componente aparece en los dos documentos con los valores de `design.md` y la versión nueva — cubre: CA3
  - nota: componente en «Aviso, diálogo y mensajes» de `system.md` y en `system.html` (sección `aviso-054`), con sus contrastes y la regla del botón de envío por defecto (A10); versión 1.21.0 y fila en «Decisiones».
- [x] T002 [P] Crear la migración que hace nulable `usuarios.email` con `DB::statement('ALTER TABLE usuarios MODIFY email VARCHAR(255) NULL')` y un `down()` que vuelve a `NOT NULL` solo si no hay correos nulos y, si los hay, lanza una excepción con el motivo (Rollout del plan) — database/migrations/2026_11_20_000000_correo_opcional_en_usuarios.php — hecho cuando: `migrate --force` sobre `dentissa_test` la aplica, la columna admite nulos y conserva `uq_usuarios_email` y la intercalación; `migrate:rollback --step=1` sin nulos la revierte y con un nulo falla con el mensaje; otro `migrate` la deja aplicada (anotado en la nota) — cubre: CA1
  - nota: comprobado sobre `dentissa_054_test` (2026-11-21): tras `migrate --force`, `email` es `varchar(255) NULL` en `utf8mb4_unicode_ci` y `uq_usuarios_email` sigue único; `migrate:rollback --step=1` sin nulos la deja en `NOT NULL`; con una cuenta sin correo falla con «Hay 1 cuentas sin correo: no se puede volver a exigir el correo…»; otro `migrate` la deja aplicada. Pint limpio.
- [x] T003 Fijar la línea base de la suite en la rama con la migración aplicada, comprobar que sigue al día con `main` (D11) y si la 052 ya está en `main`; si lo está, añadir una tarea que haga que su recordatorio se salte las cuentas sin correo sin contarlo como fallo (/analyze A8) — rama `feature/054-paciente-sin-correo` — hecho cuando: `php artisan test` pasa completo y el número de tests, el commit de `main` y el estado de la 052 quedan en la nota — depende: T002
  - nota: línea base 2026-11-21 sobre `7c00de5` en un contenedor propio con el código del worktree (base `dentissa_054_test`, migración aplicada): 1522 tests en verde y 0 fallos previos; los 49 que fallan son los de `PacienteSinCorreoTest` y `PosiblesDuplicadosTest`, aún sin implementación (406 s). Después se rebasó sobre `main` `f317ac1` (v0.25.0, spec 053, sin choques salvo la fila del README): sus 79 tests (`PaginaInicioTest`, `PaginasPublicas*`, `TerminosPrivacidadTest`) pasan. El primer intento falló por el entorno (`.env` sin `APP_KEY` y sin el `local.ini` del proyecto), no por el código.
  - nota: la 052 no está en `main` (sin `docs/specs/052-*`): no hace falta tarea extra; su nota para `/plan` ya está en la spec 052 (/analyze A8).

## Tests (antes de implementar)
- [x] T010 [P] Escribir los tests unitarios de `PosiblesDuplicados`: coincide sin distinguir mayúsculas, tildes ni espacios sobrantes, con el teléfono en otro formato («55 1234 5678» frente a «5512345678») o con la fecha; no coincide si solo coincide el nombre, si el apellido es otro o si la entrada no trae teléfono ni fecha; incluye pacientes desactivados y fichas de paciente de cuentas del personal (A5); devuelve como mucho 3 ids, ordenados por nombre, con el total real — tests/Unit/PosiblesDuplicadosTest.php — hecho cuando: falla porque la clase no existe (anotado) — cubre: CA3
  - nota: los 8 fallan con «Sin implementar (T020).»: la tarea crea la firma vacía de `App\Pacientes\PosiblesDuplicados::buscar()` para que fallen en la llamada y no en la carga de la clase.
- [x] T011 Escribir los tests del alta: sin correo y con teléfono, como admin y como asistente, crea la cuenta con `email` y `password_hash` nulos y su ficha, sin `password_generada` y con el mensaje de CA1; al paciente creado se le agenda una cita y se le crea un expediente; un correo solo con espacios cuenta como vacío (TM9); con correo, el alta sigue como hoy (contraseña generada y mostrada); sin correo ni teléfono, error de CA2 junto al teléfono, `old()` y sin filas nuevas; una contraseña escrita sin correo no se guarda, tampoco si tiene menos de 6 caracteres (D4, A6); las altas como asistente llevan un dentista asignado (A11); `email` no válido o `email[]` se rechaza sin error 500 (TM9) — tests/Feature/PacienteSinCorreoTest.php — hecho cuando: los de CA1 y CA2 fallan porque hoy el correo es obligatorio (anotado); «con correo como hoy» y «no válido» pasan desde el principio y los protege T021 — cubre: CA1, CA2
  - nota: fallan porque hoy el correo es obligatorio («The email field is required.»); «con correo, como hoy» y «correo no válido o array» pasan desde el principio y los protege T021. Un solo commit para T011–T017: comparten `PacienteSinCorreoTest`.
- [x] T012 Escribir los tests del aviso de duplicado: con nombre, apellido y teléfono o fecha de un paciente existente, el alta vuelve al formulario con el aviso y lo escrito, sin crear nada; «Registrar de todos modos» (`confirmar_duplicado=1`) crea el paciente con los datos escritos; tras el aviso la sesión no contiene `password` y el campo vuelve vacío, y al confirmar con correo y sin contraseña hay `password_generada` (A2, TM10); el primer botón de envío del formulario no lleva `confirmar_duplicado` (A10); sin coincidencias, no hay aviso; con más de 3, «y N más»; el aviso no contiene el teléfono, la fecha, el correo ni los datos clínicos del existente (TM3); los enlaces van a `admin.pacientes.show` o `asistente.pacientes.show` según quien registra; un nombre `<script>` se muestra escapado (TM8) — tests/Feature/PacienteSinCorreoTest.php — hecho cuando: fallan porque hoy no hay aviso (anotado) — cubre: CA3, CA9 — depende: T011
  - nota: fallan porque hoy no hay aviso (sin `.aviso-duplicado` ni `posibles_duplicados`).
- [x] T013 Escribir los tests del directorio y la ficha: un paciente sin correo muestra «Sin correo · sin acceso a la aplicación» en el directorio, también en el marcado del móvil (sin `solo-escritorio`), y en la fila «Correo» de la ficha; la búsqueda del directorio por su nombre y por su teléfono lo encuentra; un paciente con correo se ve como hoy — tests/Feature/PacienteSinCorreoTest.php — hecho cuando: los de «Sin correo» fallan porque hoy no aparece el texto (anotado) — cubre: CA4 — depende: T012
  - nota: fallan los de «Sin correo» (no hay `.marca-sin-correo`); «la búsqueda lo encuentra» y «con correo como hoy» pasan hoy (caracterización).
- [x] T014 Escribir los tests de la edición: sin correo, guardar sin añadirlo funciona y sin teléfono se rechaza con el mensaje de CA2; con correo, dejarlo vacío se rechaza con su mensaje y sin cambios; añadir un correo libre lo guarda y no envía ningún correo (`Mail::fake`); después, pedir el restablecimiento con ese correo envía el enlace y, al usarlo, el paciente entra con su contraseña nueva; añadir un correo ya registrado se rechaza con «Este correo ya está registrado en el sistema.» sin cambios (TM4); el asistente sigue sin poder editar (caracterización; con la 014 pasará a 404, D11) — tests/Feature/PacienteSinCorreoTest.php — hecho cuando: los de editar sin correo y añadirlo fallan porque hoy el correo es obligatorio (anotado) — cubre: CA5, CA6 — depende: T013
  - nota: fallan editar sin correo, el mensaje propio del correo obligatorio y el de correo repetido (hoy sale el de Laravel); «añadir un correo lo guarda», «tras añadirlo entra con la 041» y «el asistente no edita» pasan hoy: el primero y el segundo los protege T024; el tercero se comprueba rompiéndolo en T025.
- [x] T015 Escribir los tests de abuso: a una cuenta sin correo no se entra con su nombre, su teléfono ni una contraseña cualquiera; la recuperación con un correo inventado no envía nada; un inicio con Google con un correo nuevo crea su propia cuenta y no se vincula con la cuenta sin correo; un inicio con Google **sin correo** (usuario de Socialite con `getEmail()` nulo) frente a una cuenta sin correo no deja sesión, ni `google_id` guardado, ni `google_registro`, ni filas nuevas (A1, TM1); un dentista contra `POST /admin/pacientes` y `POST /asistente/pacientes` recibe 403 y queda `acceso_denegado`, y contra `POST /dentista/pacientes` recibe 405 (existe el `GET`, A13); un paciente recibe 403; un visitante va al inicio de sesión; en ningún caso hay filas nuevas (TM2) — tests/Feature/PacienteSinCorreoTest.php — hecho cuando: los tests existen; los que dependen de crear una cuenta sin correo fallan hasta T002 y T021, el de Google sin correo falla hasta T028 (anotado), y los de roles pasan hoy (caracterización, comprobada en T025) — cubre: CA7, CA8, CA10 — depende: T014
  - nota: fallan los de Google sin correo (hoy vincula o abre el registro); «no hay forma de entrar» pasa hoy y se comprueba rompiéndolo en T029; los de roles pasan hoy y se comprueban en T025. `POST /dentista/pacientes` responde 404 y no 405 como decía /analyze A13 (la app trata el método sin ruta como 404); el test exige 404 y que no se cree nada.
- [x] T016 Escribir los tests de auditoría: `paciente_alta` con `con_acceso` y `posible_duplicado` correctos en los cuatro casos (con o sin correo, con o sin confirmación tras el aviso); una confirmación falsa sin coincidencias deja `posible_duplicado: false` (TM5); `paciente_correo_anadido` con `campos: ['email']` solo al pasar de nulo a un correo, no al cambiar uno existente ni al guardar sin correo; con un almacén de auditoría que falla no se crea el paciente ni se guarda el correo (TM6); `detalle` solo con las claves previstas y booleanas, sin nombre, teléfono ni correo (TM7) — tests/Feature/PacienteSinCorreoTest.php — hecho cuando: fallan porque hoy no hay eventos (anotado) — cubre: CA10, CA11 — depende: T015
  - nota: fallan porque hoy no hay eventos `paciente_alta` ni `paciente_correo_anadido` (y el alta sin correo aún se rechaza); «el alta denegada deja acceso_denegado» pasa hoy (registro central de la 020).
- [x] T017 Escribir los tests de marcado, CSS y rendimiento: etiquetas del correo según el caso (`(opcional)` sin `required`, o `*` con `required`); ayudas y errores en `aria-describedby`, con el error primero, y `aria-invalid`; el aviso con `role="alert"` y un `h2` con `tabindex="-1"` y `autofocus` (A14); `test_el_bloque_054_solo_usa_tokens` (con `ReglasCss` y control positivo); `test_el_aviso_de_duplicado_llega_a_aa` (`--text`/`--amber-bg`, `--amber`/`--white`, `--white`/`--p500`); `test_la_busqueda_de_duplicados_hace_una_sola_consulta` (con `DB::enableQueryLog()`) — tests/Feature/PacienteSinCorreoTest.php — hecho cuando: fallan porque no existen el bloque, el aviso ni la clase (anotado) — cubre: CA1, CA3, CA5 — depende: T016
  - nota: fallan porque no existen el bloque `/* Spec 054 */`, las ayudas enlazadas, el aviso ni la implementación de `PosiblesDuplicados`.
- [x] T027 Adaptar las aserciones de `aria-describedby` de `MovilPacientesExpedientesTest` (líneas 372 y 385) al patrón de la 054, antes de cambiar las vistas: sin errores, el atributo solo puede llevar ids de ayudas, nunca de errores; con error, es una lista que empieza por `error-<campo>` — tests/Feature/MovilPacientesExpedientesTest.php — hecho cuando: pasa con el marcado actual, vuelve a fallar si se quita el id del error del `aria-describedby` de un campo (comprobado y anotado, /analyze A3, A25) y sigue pasando tras T022 — cubre: CA1, CA5 — depende: T017
  - nota: pasa con el marcado actual (18 tests); comprobado rompiéndolo: con `aria-describedby="ayuda-nombre error-nombre"` en el alta falla «enlazado a su mensaje, en primer lugar»; vista restaurada (/analyze A3, A25).

## Implementación
- [ ] T020 Crear `App\Pacientes\PosiblesDuplicados::buscar()` según el plan (D5): filtro SQL por la primera palabra del apellido normalizado con `LIKE ?`, sin filtrar por rol ni por `activo` (A5), comparación en PHP de `clave()` de nombre y apellido más los dígitos del teléfono o la fecha, hasta 3 ids ordenados y el total — app/Pacientes/PosiblesDuplicados.php — hecho cuando: T010 y `test_la_busqueda_de_duplicados_hace_una_sola_consulta` pasan — cubre: CA3 — depende: T010, T017
- [ ] T021 Cambiar `store`: reglas `email` `nullable|email|max:255|unique`, `telefono` `required_without:email` con el mensaje de CA2 y `confirmar_duplicado` `nullable|boolean` y `exclude_without:email` delante de las reglas de `password` (A6); tras validar, `PosiblesDuplicados::buscar()` y, con coincidencias y sin confirmación, `back()->withInput($request->except('password'))->with('posibles_duplicados', …)` (D6, A2, TM10); sin correo, `email` y `password_hash` nulos, contraseña escrita ignorada (D4), sin `password_generada` y con el `success` de CA1; dentro de la transacción, `paciente_alta` crítico con `con_acceso` y `posible_duplicado` calculado por el servidor (TM5, TM6, TM7) — app/Http/Controllers/PacientesController.php — hecho cuando: pasan T011, los de la sesión sin contraseña de T012, los de CA7 de T015 (salvo el de Google sin correo) y los de `paciente_alta` de T016 — cubre: CA1, CA2, CA3, CA7, CA10 — depende: T002, T011, T012, T015, T016, T020
- [ ] T022 Mostrar el aviso y las ayudas: en `create`, leer `posibles_duplicados` de la sesión, cargar los nombres de esos ids y la ruta de ficha del rol (D7); en `pacientes/create`, la etiqueta y la ayuda del correo según el caso, las ayudas del teléfono y de la contraseña (`.field-hint.field-hint-aviso` con `id`), `aria-describedby` con el error primero y el aviso `.aviso-duplicado` con `role="alert"`, icono SVG de línea con `aria-hidden` y tamaño en sus atributos (A17), `h2` con `tabindex="-1"` y `autofocus` (A14), texto genérico, nombres escapados, «y N más», «Ver ficha de <nombre>» y el botón «Registrar de todos modos» (`name="confirmar_duplicado" value="1"`); con el aviso, un botón de envío normal como primer botón del formulario, fuera de la vista y del orden de tabulación (D13, A10); en `app.css`, el bloque `/* Spec 054 */` según el plan, con `.marca-sin-correo` (A9) y, si hace falta, `.envio-por-defecto` — app/Http/Controllers/PacientesController.php, resources/views/pacientes/create.blade.php, public/css/app.css — hecho cuando: pasan T012 y T017 · verificación manual: alta en reposo, con error de teléfono y con el aviso (1, 3 y más de 3 coincidencias) a 320, 390 y 1440 px contra `design.md`; foco visible en los botones del aviso, lector de pantalla que lee el aviso al cargar, Enter en un campo con el aviso visible que no crea el paciente en Chrome y en Firefox (D13), y el aviso en menos de 1 s en local con el volumen del volcado — cubre: CA1, CA2, CA3, CA9 — depende: T001, T012, T017, T021, T027
- [ ] T023 [P] Mostrar «Sin correo · sin acceso a la aplicación» en el directorio (en la línea bajo el nombre, con `.marca-sin-correo` y sin `style=""`, en todos los anchos y partible; con correo, como hoy) y en la fila «Correo» de la ficha, que pasa a aparecer siempre — resources/views/pacientes/index.blade.php, resources/views/pacientes/show.blade.php — hecho cuando: pasa T013 · verificación manual: directorio y ficha de un paciente sin correo a 320, 390 y 1440 px contra `design.md` — cubre: CA4 — depende: T001, T002, T013, T022
- [ ] T024 Cambiar `update`: `email` `required` con mensaje propio («El correo es obligatorio: es el acceso del paciente a la aplicación.») si el paciente tiene correo y `nullable` si no, siempre `email|max:255|unique` ignorando la propia cuenta; `telefono` `required_without:email`; si el correo pasa de nulo a un valor, `paciente_correo_anadido` crítico dentro de la transacción (TM4, TM6, TM7) — app/Http/Controllers/PacientesController.php — hecho cuando: pasan T014 y los de `paciente_correo_anadido` de T016 · verificación manual: edición de un paciente sin correo y de uno con correo a 390 y 1440 px contra `design.md` — cubre: CA5, CA6, CA11 — depende: T014, T016, T022
- [ ] T025 Comprobar los controles de caracterización de CA8 y del asistente rompiéndolos (D10): quitar temporalmente `role:admin`/`role:asistente` del grupo de rutas hace fallar los de CA8; quitar el `abort(403)` de `edit`/`update` hace fallar el del asistente (A7); se deshace todo y se anota en la nota — routes/web.php, app/Http/Controllers/PacientesController.php — hecho cuando: los de roles de T015 y el del asistente de T014 pasan, `git diff` de los dos archivos no tiene restos de la prueba y la comprobación queda anotada — cubre: CA8 — depende: T021, T024
- [ ] T029 Comprobar los controles de CA7 rompiéndolos (D10): dar a una cuenta sin correo un `password_hash` conocido y un correo hace fallar el test de acceso; quitar la guarda de T028 hace fallar el de Google sin correo; se deshace todo y se anota en la nota — app/Http/Controllers/GoogleAuthController.php, tests/Feature/PacienteSinCorreoTest.php — hecho cuando: T015 pasa entero, `git diff` de `GoogleAuthController.php` solo tiene la guarda de T028 y la comprobación queda anotada — cubre: CA7 — depende: T021, T028
- [ ] T026 Ejecutar la suite completa y, si algún test existente recibe el aviso de duplicado o depende de que el correo sea obligatorio, ajustar su dato sin cambiar lo que comprueba (Riesgos del plan) — tests/Feature/ (solo los que fallen por la 054) — hecho cuando: `php artisan test` pasa completo (resultado y tests ajustados, si hubo, en la nota) — depende: T020, T021, T022, T023, T024, T025, T027, T028, T029

- [ ] T028 Añadir la guarda de Google sin correo: en el callback, si `$googleUser->getEmail()` es nulo o vacío, `volverAlLoginPorFallo()` antes de buscar por correo y antes de guardar `google_registro` (D14, TM1) — app/Http/Controllers/GoogleAuthController.php — hecho cuando: pasa `test_google_sin_correo_ni_vincula_ni_crea_cuenta` de T015 y los tests de Google existentes siguen en verde — cubre: CA7 — depende: T015

## Integración y documentación
- [ ] T090 Actualizar `docs/architecture.md` si cambió la estructura: `usuarios.email` nulable y qué significa (cuenta de paciente sin acceso), `PosiblesDuplicados` en `app/Pacientes/` y la migración en la lista; en `AGENTS.md` → «Mapa del repositorio», `PosiblesDuplicados` en la fila de `app/Pacientes/`; en `docs/security.md` → autenticación, que una cuenta sin correo no puede entrar hasta que el personal le añade uno y crea su contraseña con la 041
- [ ] T091 Actualizar `docs/deployment.md` y `docs/observability.md` si cambiaron variables, entornos, pasos de deploy, logs, eventos de auditoría o métricas: filas de `paciente_alta` (alta de cuenta de paciente por el personal) y `paciente_correo_anadido` (cambio de cuenta: el paciente pasa a poder tener acceso), ambos con recurso `pacientes`, en la tabla de eventos de `observability.md`, y su correspondencia con OB1.f, que sigue pendiente para el resto de altas, roles y activaciones (→ 021) (/analyze A18); en `deployment.md`, que la migración de la 054 no se deshace con `volver.sh` y que su `down()` se niega si hay cuentas sin correo
- [ ] T092 Marcar spec como `implemented`: suite completa, Pint sobre lo tocado, gitleaks sobre el diff de la spec, semgrep `p/php` sobre los PHP tocados, `composer audit` y `auditar-npm.mjs` sin altas ni críticas, criterios `[x]` y `docs/specs/README.md`

## Despliegue (lo ejecuta `/release`)
- [ ] T095 Desplegar a staging y verificar criterios de aceptación. No hay staging: la comprobación se hace en producción, dentro de T097 (alta de un paciente de prueba sin correo, su «Sin correo» en el directorio y la ficha, su evento `paciente_alta` con `con_acceso: false` y el `request_id`; después se desactiva)
- [ ] T096 Aprobación humana para producción
- [ ] T097 Desplegar a producción y vigilar métricas del plan (Rollout): copia de la base, `deploy/desplegar.sh vX.Y.Z` (incluye la migración *expand* y la guarda de Google), `verificar.sh`, la comprobación de T095 y, la primera hora, 5xx del alta y la edición de pacientes y `auditoria.fallo` en el canal `seguridad`. Rollback: si ya hay cuentas sin correo, desactivarlas antes anotando sus ids (A1); después `bash deploy/volver.sh <tag anterior>`, sin deshacer la migración
- [ ] T098 Marcar spec como `released`; en la spec 006 anotar en su Historial que el alta y la edición admiten pacientes sin correo (054, modifica CA2 y CA4); avisar en el plan de la 052, si ya existe, de que hay cuentas sin correo (D11)

## Cobertura
| Criterio | Tarea(s) de test | Tarea(s) de implementación |
|---|---|---|
| CA1 | T011, T017 | T002, T021, T022, T027 |
| CA2 | T011 | T021, T022 |
| CA3 | T010, T012, T017 | T001, T020, T021, T022 |
| CA4 | T013 | T023 |
| CA5 | T014, T017 | T024, T027 |
| CA6 | T014 | T024 |
| CA7 | T015 | T021, T028, T029 |
| CA8 | T015 | T025 |
| CA9 | T012 | T022 |
| CA10 | T015, T016 | T021 |
| CA11 | T016 | T024 |

| Amenaza (TM#) | Tarea(s) de control | Tarea(s) de test |
|---|---|---|
| TM1 | T021, T028 | T015 |
| TM2 | T025 | T015 |
| TM3 | T022 | T012 |
| TM4 | T024 | T014 |
| TM5 | T021 | T016 |
| TM6 | T021, T024 | T016 |
| TM7 | T021, T024 | T016 |
| TM8 | T022 | T012 |
| TM9 | T021, T024 | T011 |
| TM10 | T021, T022 | T012 |

| Cambio del plan (módulo) | Tarea(s) |
|---|---|
| database/migrations/2026_11_20_000000_correo_opcional_en_usuarios.php | T002 |
| app/Pacientes/PosiblesDuplicados.php | T020 |
| app/Http/Controllers/PacientesController.php | T021, T022, T024 |
| app/Http/Controllers/GoogleAuthController.php | T028 |
| tests/Feature/MovilPacientesExpedientesTest.php | T027 |
| resources/views/pacientes/create.blade.php | T022 |
| resources/views/pacientes/index.blade.php | T023 |
| resources/views/pacientes/show.blade.php | T023 |
| public/css/app.css | T022 |
| docs/design/system.md, docs/design/system.html | T001 |
| tests/Unit/PosiblesDuplicadosTest.php | T010 |
| tests/Feature/PacienteSinCorreoTest.php | T011, T012, T013, T014, T015, T016, T017 |

| Requisito no funcional | Tarea(s) de test o verificación manual (T095) |
|---|---|
| WCAG 2.1 AA en las pantallas tocadas | T017, T027 · verificación manual en T022, T023, T024 |
| Aviso de duplicado en < 1 s al 95 % | T017 (una sola consulta) · verificación manual en T022 (alta con aviso en local con el volumen del volcado) |
