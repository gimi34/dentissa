# Requisitos — Corrección del CRUD de administración

**Intención**: `260903-fix-admin-crud`
**Etapa**: requirements-analysis (Inception)
**Alcance**: bugfix · **Profundidad**: Minimal · **Estrategia de pruebas**: Minimal
**Tipo de proyecto**: Brownfield (Laravel 12 + MariaDB)
**Fecha**: 2026-09-03

---

## 1. Análisis de la intención

El objetivo no es añadir capacidades nuevas, sino **devolver a la interfaz de
administración un comportamiento que hoy es incorrecto y silencioso**. Los cuatro
fallos comparten una característica que los hace prioritarios: ninguno avisa. El
administrador cree que ha guardado un servicio y ha borrado su precio; cree que
ha corregido un teléfono y ha convertido a un asistente en administrador; pulsa
«Crear usuario» y recibe un 404 sin explicación; pulsa «Eliminar» y destruye un
expediente clínico o recibe un error 500.

Lo que se persigue, en términos de negocio:

1. **Que el administrador pueda dar de alta usuarios**, que hoy es imposible.
2. **Que editar un servicio no destruya su precio**, y que el precio y el color de
   agenda dejen de ser datos inalcanzables desde la aplicación.
3. **Que ninguna operación de administración provoque una pérdida de datos
   clínicos irreversible ni una elevación de privilegios no solicitada.**
4. **Que el módulo de Usuarios se alinee con el patrón que el resto del sistema
   ya aplica** (desactivación lógica en lugar de borrado físico), en vez de ser la
   excepción.

El sistema es un gestor de clínica dental cuyo eje de autorización es la columna
`usuarios.rol`, y cuyo activo más sensible es el expediente clínico
(ver `business-overview.md` y `architecture.md` en el CodeKB del repositorio).
Ambas cosas están hoy expuestas por el módulo de administración de usuarios.

---

## 2. Requisitos funcionales

### FR1 — El alta de usuarios desde administración debe ser accesible (TD-11, TD-12)

En `routes/web.php`, la ruta `GET /admin/usuarios/{id}` (línea 92) se registra
antes que `GET /admin/usuarios/crear` (línea 94) y sin restricción sobre `{id}`.
Laravel resuelve por orden de registro, así que la petición a `/admin/usuarios/crear`
entra en `UsuarioController@show`, ejecuta `Usuario::findOrFail('crear')` y responde
404. Además, `GET /admin/usuarios` se registra dos veces: la línea 88 como closure
que devuelve la vista `placeholder` bajo el nombre `admin.usuarios`, y la línea 90
apuntando a `UsuarioController@index`.

- **FR1.1** — La ruta `GET /admin/usuarios/crear` debe registrarse **antes** que
  `GET /admin/usuarios/{id}`, de modo que la petición resuelva a
  `UsuarioController@create` y muestre el formulario de alta.
- **FR1.2** — La ruta `GET /admin/usuarios/{id}` debe restringir `{id}` al formato
  UUID que usa la tabla `usuarios` (`char(36)`), para que ningún segmento literal
  futuro vuelva a ser capturado por el parámetro.
- **FR1.3** — El closure muerto de la línea 88 debe eliminarse, dejando
  `UsuarioController@index` como único responsable de `GET /admin/usuarios`.
- **FR1.4** — Ninguna referencia existente a la ruta nombrada `admin.usuarios` debe
  quedar rota tras eliminar el closure. Si alguna vista o controlador la usa, debe
  reapuntarse a `admin.usuarios.index`.

**Criterio de aceptación**

```
Dado un administrador autenticado
Cuando navega a /admin/usuarios/crear
Entonces recibe el formulario de alta de usuario con respuesta 200
Y no se ejecuta UsuarioController@show
```

```
Dado un administrador autenticado
Cuando navega a /admin/usuarios
Entonces recibe el listado de usuarios de UsuarioController@index
Y no la vista placeholder
```

---

### FR2 — El precio y el color de agenda de un servicio deben poder gestionarse y no deben destruirse (TD-8)

`ServiciosController::store()` (líneas 33-34) fija `$data['precio'] = 0` y
`$data['color_agenda'] = '#F4C0D1'` de forma incondicional; `update()` (línea 58)
vuelve a fijar `$data['precio'] = 0`. Cualquier edición de un servicio destruye su
precio en silencio. Existen 15 servicios con precios reales de 200,00 a 18.000,00
(`database/dentissa.sql`, líneas 315-330) y la pérdida se propaga a las vistas SQL
`v_citas_hoy` y `v_historial_paciente`, que exponen `s.precio` (líneas 451 y 460).

El alcance es mayor que suprimir esas líneas: **ni `resources/views/servicios/create.blade.php`
ni `edit.blade.php` tienen campo de precio ni de color de agenda**, así que hoy el
precio no se puede capturar desde la interfaz aunque el controlador dejara de
sobrescribirlo.

- **FR2.1** — El formulario de alta de servicios debe incluir un campo de precio.
- **FR2.2** — El formulario de edición de servicios debe incluir un campo de precio
  precargado con el valor actual del servicio.
- **FR2.3** — Ambos formularios deben incluir un selector de color de agenda,
  precargado con el valor actual en edición y con el valor por defecto
  (`#F4C0D1`) en alta.
- **FR2.4** — `store()` y `update()` deben validar el precio como numérico mayor o
  igual a 0, con dos decimales como máximo, conforme a `servicios.precio decimal(10,2)`.
- **FR2.5** — `store()` y `update()` deben validar el color de agenda como cadena de
  7 caracteres en formato hexadecimal (`#RRGGBB`), conforme a
  `servicios.color_agenda varchar(7)`.
- **FR2.6** — `store()` y `update()` deben dejar de asignar `precio` y `color_agenda`
  de forma incondicional; el valor persistido debe ser el enviado en el formulario.
- **FR2.7** — Editar un servicio sin modificar su precio debe conservar el precio
  existente sin alterarlo.

**Criterio de aceptación**

```
Dado el servicio «Colocación de brackets» con precio 18000.00
Cuando el administrador abre su formulario de edición, cambia solo la duración y guarda
Entonces el precio persistido sigue siendo 18000.00
Y v_citas_hoy y v_historial_paciente siguen mostrando 18000.00 para ese servicio
```

```
Dado un administrador en el formulario de alta de servicios
Cuando envía el formulario con un precio de -50
Entonces la petición es rechazada con un mensaje de validación
Y no se crea ningún servicio
```

---

### FR3 — El módulo de Usuarios debe validar sus entradas (TD-16)

`UsuarioController` es el único controlador del sistema sin validación alguna en
`store()` y `update()`. `rol` es campo asignable en masa y llega directo desde la
petición, sin restricción. Además, `store()` ignora el campo `password` que el
formulario sí envía (`resources/views/usuarios/create.blade.php`, líneas 51-53,
cuyo texto promete «Si se deja vacío se genera una contraseña aleatoria») y asigna
en su lugar `bcrypt('password123')` fijo en código.

- **FR3.1** — `store()` debe validar: `nombre` y `apellido` obligatorios; `email`
  obligatorio, con formato de correo y **único** en la tabla `usuarios`; `telefono`
  según el formato ya aceptado por el resto del sistema; `rol` obligatorio.
- **FR3.2** — `update()` debe aplicar la misma validación, con la unicidad de `email`
  ignorando el registro que se está editando.
- **FR3.3** — El valor de `rol` debe validarse en el servidor contra los cuatro
  valores del `enum` de la columna: `cliente`, `asistente`, `dentista`, `admin`.
- **FR3.4** — El selector de rol de `resources/views/usuarios/create.blade.php`
  (líneas 71-77), que también sirve al formulario de edición porque
  `edit.blade.php` hace `@include('usuarios.create')`, debe incluir la opción
  **Asistente**.
- **FR3.5** — Editar un usuario cuyo rol es `asistente` debe conservar ese rol. Hoy
  ninguna de las tres opciones del selector queda marcada, el navegador envía la
  primera (`admin`) y el asistente se convierte en administrador sin intervención.
- **FR3.6** — `store()` debe usar la contraseña enviada en el campo `password` cuando
  venga informada, y generar una contraseña aleatoria cuando venga vacía, en lugar
  de asignar `password123` fijo en código.
- **FR3.7** — El comportamiento existente para el rol `cliente` debe conservarse: la
  vista oculta el selector y fija el campo oculto con valor `cliente`
  (líneas 80-82), impidiendo promocionar a un paciente desde esta pantalla.

**Criterio de aceptación**

```
Dado un usuario con rol «asistente»
Cuando el administrador abre su ficha de edición, cambia solo el teléfono y guarda
Entonces el rol persistido sigue siendo «asistente»
```

```
Dado un correo ya registrado en la tabla usuarios
Cuando el administrador intenta crear otro usuario con ese mismo correo
Entonces la petición es rechazada con un mensaje de validación
Y no se crea un segundo usuario con ese correo
```

```
Dado un administrador que envía rol = «superadmin» por POST
Cuando se procesa la petición
Entonces es rechazada por validación
Y ningún usuario queda con un rol fuera del enum
```

---

### FR4 — La baja de usuarios debe ser lógica, no física (TD-16)

`UsuarioController::destroy()` hace borrado físico. Por la clave foránea
`fk_pacientes_usuario ... ON DELETE CASCADE` (línea 660 del volcado), borrar un
usuario borra en cascada su ficha de paciente; si ese paciente tiene citas, la clave
`fk_cita_paciente` (línea 632, sin cascada) aborta la operación con una excepción SQL
no capturada y el administrador ve un error 500. Lo mismo ocurre con `personal` vía
`fk_personal_usuario` (línea 666). Pacientes y Personal ya resuelven esto con
desactivación lógica (`PacientesController::toggleActivo`, línea 310; la ruta
`admin.personal.toggle`, línea 83 de `routes/web.php`).

- **FR4.1** — El módulo de Usuarios debe ofrecer una acción de alternancia de estado
  que conmute la bandera `usuarios.activo` entre 1 y 0, siguiendo el patrón de
  `toggleActivo` ya presente en Pacientes y Personal.
- **FR4.2** — Debe registrarse la ruta correspondiente
  (`PATCH /admin/usuarios/{id}/toggle`), coherente en forma y nombre con
  `admin.personal.toggle`.
- **FR4.3** — El botón de la tabla en `resources/views/usuarios/index.blade.php`
  (línea 163) debe pasar a «Desactivar / Activar», reflejando el estado actual del
  usuario.
- **FR4.4** — El modal de confirmación (líneas 202-226), cuyo texto dice hoy «Esta
  acción no se puede deshacer», debe reescribirse para describir la desactivación y
  su reversibilidad.
- **FR4.5** — La ruta `DELETE /admin/usuarios/{id}` y el método
  `UsuarioController::destroy()` deben eliminarse.
- **FR4.6** — La acción de alternancia debe actualizar `usuarios.actualizado_en`,
  igual que hace `PacientesController::toggleActivo` (líneas 317-320).

**Criterio de aceptación**

```
Dado un usuario con rol «cliente» que tiene ficha de paciente y citas registradas
Cuando el administrador lo desactiva desde el listado
Entonces usuarios.activo pasa a 0
Y su ficha de paciente, sus citas y su expediente siguen existiendo
Y no se produce ningún error 500
```

```
Dado un usuario desactivado
Cuando el administrador lo reactiva desde el listado
Entonces usuarios.activo vuelve a 1
```

---

### FR5 — Las correcciones deben verificarse antes de darse por buenas

- **FR5.1** — Se debe entregar una lista de comprobación manual, ejecutable contra el
  entorno Docker existente, que cubra al menos: alta de usuario desde
  `/admin/usuarios/crear`; edición de un servicio confirmando que su precio
  sobrevive; alta de servicio con precio y color; edición de un usuario con rol
  `asistente` confirmando que conserva el rol; intento de crear un usuario con
  correo duplicado; desactivación y reactivación de un usuario con citas asociadas.
- **FR5.2** — La suite PHPUnit existente debe seguir en verde tras los cambios.

---

## 3. Requisitos no funcionales

- **NFR1 — Sin elevación de privilegios accidental.** Ninguna operación del módulo de
  administración puede cambiar el rol de un usuario sin que el administrador lo haya
  seleccionado explícitamente. La validación de `rol` es de servidor, no de interfaz:
  una petición manipulada con un rol fuera del `enum` debe ser rechazada.
- **NFR2 — Sin pérdida de datos clínicos.** Ninguna operación disponible en la
  interfaz de administración puede provocar la eliminación irreversible de una ficha
  de paciente, una cita o un expediente. Verificable ejecutando cada acción de
  administración contra un usuario con expediente y comprobando que los registros
  persisten.
- **NFR3 — Sin errores no controlados.** Ninguna acción del módulo de administración
  de usuarios o de servicios debe responder con un error 500. Los fallos previsibles
  (validación, restricción de integridad) se resuelven con un mensaje entendible en
  la propia pantalla.
- **NFR4 — Consistencia con el patrón existente.** Las correcciones deben reutilizar
  los patrones ya presentes en el repositorio — `toggleActivo` para bajas,
  `$request->validate()` para validación, rutas nombradas bajo el prefijo `admin.` —
  en lugar de introducir mecanismos nuevos. Verificable por comparación directa con
  `PacientesController` y `PersonalController`.
- **NFR5 — Sin regresión funcional.** Los flujos que hoy funcionan (listado,
  búsqueda y filtro de usuarios, ficha de detalle, alta y edición de servicios en sus
  campos actuales) deben seguir comportándose igual tras los cambios.

---

## 4. Restricciones

- **C1** — El esquema de las tablas del dominio (`usuarios`, `servicios`, `pacientes`,
  `citas`, `personal`, `expedientes`) existe únicamente en el volcado
  `database/dentissa.sql`. En `database/migrations/` solo hay migraciones de Laravel
  para tablas auxiliares. Las correcciones **no** deben requerir cambios de esquema.
- **C2** — `phpunit.xml` está configurado para SQLite en memoria y la suite actual son
  los dos ejemplos que trae Laravel. Por decisión registrada (Q4 = D), esta corrección
  **no** añade pruebas automáticas: la verificación es la lista manual de FR5.1. La
  cobertura automática (migraciones del dominio o base MariaDB de pruebas) queda como
  trabajo aparte. La mitad del piso de pruebas de la casa que sí aplica —que la suite
  existente siga en verde— se conserva en FR5.2 y se comprueba en Build and Test.
- **C3** — `usuarios.rol` es un `enum` de base de datos con exactamente cuatro valores.
  Añadir un rol nuevo exigiría un `ALTER TABLE`, excluido por C1.
- **C4** — `resources/views/usuarios/edit.blade.php` son cinco líneas que hacen
  `@include('usuarios.create')`. Cualquier cambio en el formulario de alta afecta
  simultáneamente al de edición y debe comprobarse en ambos.
- **C5** — El dominio está escrito en español (tablas, columnas, rutas, mensajes) y el
  andamiaje del framework en inglés (ver `architecture.md`). Los cambios deben
  respetar esa frontera.
- **C6** — Las vistas SQL `v_citas_hoy` y `v_historial_paciente` leen `servicios.precio`
  directamente. Corregir el precio en el controlador basta; las vistas no se tocan.

---

## 5. Supuestos

- **A1** — El administrador es el único actor que opera estas pantallas; no se
  contempla concurrencia de dos administradores sobre el mismo registro.
- **A2** — Los 15 servicios del volcado con precios de 200,00 a 18.000,00 son datos
  válidos de producción y deben preservarse. No se ha ejecutado ninguna edición
  destructiva sobre ellos que haya que revertir.
- **A3** — El texto del formulario de alta de usuarios («Si se deja vacío se genera una
  contraseña aleatoria») declara la intención original del autor; FR3.6 la
  implementa en lugar de eliminar el campo.
- **A4** — No existen hoy usuarios con rol `asistente` en el volcado
  (`database/dentissa.sql`, líneas 395-403), pero el rol está cableado en
  `AuthController`, `DashboardController`, `CitasController` y `ExpedientesController`
  y el usuario ha confirmado que se usa. FR3.4 y FR3.5 se justifican por ese uso, no
  por los datos actuales.
- **A5** — El entorno Docker del repositorio está operativo y sirve para ejecutar la
  lista de comprobación de FR5.1.

---

## 6. Fuera de alcance

- Migraciones de Laravel para las tablas del dominio (`usuarios`, `servicios`,
  `pacientes`, `citas`) — decisión Q4 = D.
- Pruebas automáticas de funcionalidad sobre estos módulos — decisión Q4 = D.
- Rediseño de la interfaz de administración más allá de los campos y botones que
  exigen FR2, FR3 y FR4.
- Auditoría o registro histórico de las acciones de administración.
- Revisión de los demás fallos del informe de ingeniería inversa que no sean
  TD-8, TD-11, TD-12 y TD-16.
- Corrección del módulo de Servicios en aspectos distintos del precio y el color de
  agenda (por ejemplo, su método `destroy()`, que también hace borrado físico).
- Cambios en el `enum` de `usuarios.rol` o en cualquier otra estructura de base de
  datos.

---

## 7. Preguntas abiertas

- **OQ1** — ¿Debe la desactivación de un usuario `dentista` con citas futuras
  agendadas provocar alguna advertencia al administrador? FR4 conserva los datos, pero
  no define qué pasa con la agenda de ese dentista. A resolver en Code Generation si
  aparece durante la implementación.
- **OQ2** — La cobertura automática diferida por Q4 = D necesita una decisión propia
  (migraciones del dominio frente a base MariaDB de pruebas) cuando se aborde como
  trabajo aparte.
- **OQ3** — `ServiciosController::destroy()` también hace borrado físico y comparte la
  naturaleza de TD-16, pero queda fuera de alcance en esta corrección. Conviene
  registrarlo para una intención posterior.

---

## Sources

- `[desc]` Initial description: descripción inicial de la intención (TD-8, TD-11, TD-12, TD-16), decodificada de `project-description.json`.
- `[scope]` Workflow-selected scope: `bugfix` (Profundidad Minimal, Estrategia de pruebas Minimal).
- `[Q1]` Precio y color de servicios → opción B (campo de precio y selector de color en ambos formularios, ambos validados).
- `[Q2]` Baja de usuarios → opción A (desactivación lógica siguiendo el patrón de Pacientes y Personal).
- `[Q3]` Roles asignables → opción A (añadir «Asistente» al selector y validar contra los cuatro valores del enum).
- `[Q4]` Verificación → opción D (lista de comprobación manual ahora, cobertura automática como trabajo aparte).
- CodeKB del repositorio `dentissa`, producido por la etapa `reverse-engineering/`: `business-overview.md` (actores del dominio y valor de negocio), `architecture.md` (capas, frontera lingüística, modelo de autorización por rol) y `code-structure.md` (organización de controladores, rutas y vistas).
- Código verificado directamente en este análisis: `routes/web.php` (líneas 87-100), `app/Http/Controllers/UsuarioController.php`, `app/Http/Controllers/ServiciosController.php` (líneas 23-64), `app/Http/Controllers/PacientesController.php` (línea 310), `resources/views/usuarios/create.blade.php` (líneas 51-53, 71-82), `resources/views/usuarios/edit.blade.php`, `resources/views/usuarios/index.blade.php` (líneas 154-226), `resources/views/servicios/create.blade.php`, `resources/views/servicios/edit.blade.php`, `database/dentissa.sql` (líneas 299-310, 315-330, 383, 395-403, 451, 460, 630-666).

## Assumptions & Open Questions

Los supuestos vigentes son A1 a A5 de la sección 5; las preguntas abiertas son OQ1 a
OQ3 de la sección 7. Ninguna de ellas bloquea la generación de código: OQ1 se resuelve
durante la implementación si aparece, y OQ2 y OQ3 quedan registradas como trabajo
posterior, fuera de esta corrección.

## Review

**Verdict:** READY
**Reviewer:** aidlc-product-lead-agent
**Date:** 2026-09-03T21:55:02Z
**Iteration:** 1

### Findings

| ID | Severity | Finding | Evidence | Status |
|---|---|---|---|---|
| R-01 | Minor | FR3.1 remite a "el formato ya aceptado por el resto del sistema" para `telefono` sin citar la regla concreta | Verificado en el repositorio: el patrón real y único es `nullable\|string\|max:20`, usado de forma consistente en `PacientesController.php` (líneas 90 y 262), `PersonalController.php` (líneas 76 y 269), `PerfilController.php` (línea 35) y `RegisterController.php` (líneas 34 y 115). El requisito es correcto y sin ambigüedad práctica (solo existe un patrón en todo el código), pero un desarrollador debe salir del documento para localizarlo; citar la regla habría cerrado el gap por completo | New |
| R-02 | Minor | Pequeña imprecisión de línea en la cita de FR3 sobre el campo de contraseña | El texto dice "líneas 51-53" para el campo `password` de `resources/views/usuarios/create.blade.php`; el bloque real (etiqueta, input y pista) ocupa las líneas 50-54. El contenido y el comportamiento descritos son correctos; solo el rango de línea está desplazado en un renglón | New |

### Summary

El artefacto es excepcionalmente sólido para un bugfix de alcance Minimal: cada requisito funcional cita código real verificado línea por línea (rutas, controladores, vistas, volcado SQL), y en la revisión cruzada contra el repositorio todas las citas de comportamiento, claves foráneas, rangos de precio y líneas de ruta resultaron exactas o casi exactas. Las cuatro decisiones registradas en la Q&A (Q1=B, Q2=A, Q3=A, Q4=D) están implementadas fielmente en FR2, FR4, FR3 y FR5/C2 respectivamente, sin expansión de alcance no justificada (la exclusión explícita de `ServiciosController::destroy()` en la sección 6 y OQ3 es un buen ejemplo de disciplina de alcance). Todos los criterios de aceptación son ejecutables en Given/When/Then y verificables sin ambigüedad. Los dos hallazgos son menores — una remisión a un patrón externo sin citar la regla literal, y un desplazamiento de una línea en una cita — y no impiden que ingeniería empiece a construir.
