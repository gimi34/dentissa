# Preguntas — Análisis de Requisitos

**Etapa:** requirements-analysis
**Intención:** 260903-fix-admin-crud
**Profundidad:** Minimal
**Fecha:** 2026-09-03

Contexto: los cuatro fallos (TD-8, TD-11, TD-12, TD-16) están confirmados leyendo
el código real. TD-11 (orden de rutas) y TD-12 (ruta duplicada muerta) no admiten
más de una solución razonable, así que no generan preguntas. Las cuatro preguntas
de abajo son las decisiones donde tu criterio cambia lo que se construye.

---

## Q1 — ¿Cómo debe gestionarse el precio de un servicio?

Al revisar los formularios encontré algo que la descripción no menciona: ni
`resources/views/servicios/create.blade.php` ni `edit.blade.php` tienen campo de
precio. Es decir, el precio nunca se pudo capturar desde la interfaz; el
controlador lo fija a 0 y el formulario tampoco lo pide. Los 15 servicios con
precio real (de 400,00 a 18.000,00) vienen del volcado de base de datos. Lo mismo
pasa con el color de agenda (`color_agenda`), que `store()` fuerza a `#F4C0D1` y
que tampoco aparece en ningún formulario.

Esto cambia el alcance: no basta con quitar la línea `$data['precio'] = 0`.

- A. Añadir campo de precio a los formularios de alta y edición, validarlo
  (numérico, mayor o igual a 0) y guardar lo que el administrador escriba. El
  color de agenda se deja como está: valor por defecto al crear y sin tocarlo al
  editar.
- B. Añadir campo de precio **y** selector de color de agenda a ambos
  formularios, validando los dos. Es la solución completa pero toca más interfaz.
- C. No tocar los formularios. Solo dejar de sobrescribir: al crear, precio 0 por
  defecto; al editar, conservar el precio que ya tiene el servicio. El precio se
  seguiría gestionando fuera de la aplicación, pero deja de destruirse.
- D. Añadir el campo de precio solo al formulario de edición (para poder
  corregir), dejando el alta con precio 0 que se ajusta después.
- X. Other (please specify)

[Answer]: B

---

## Q2 — ¿Qué debe hacer el botón «Eliminar usuario» del panel de administración?

Hoy `UsuarioController::destroy()` hace un borrado físico. Por la clave foránea
`fk_pacientes_usuario ... ON DELETE CASCADE`, borrar un usuario borra en cascada
su ficha de paciente; y si ese paciente tiene citas, la clave `fk_cita_paciente`
(sin cascada) aborta la operación con una excepción SQL no capturada y el
administrador ve un error 500. Los módulos de Pacientes y Personal ya resuelven
esto con desactivación lógica (`toggleActivo`), que apaga la bandera `activo` sin
borrar nada. Usuarios es el único módulo que no la tiene.

Ten en cuenta que la vista `resources/views/usuarios/index.blade.php` tiene un
modal que dice literalmente «Esta acción no se puede deshacer», así que la opción
que elijas también decide qué texto y qué botón ve el administrador.

- A. Sustituir el borrado por desactivación lógica, igual que Pacientes y
  Personal: nueva ruta de alternancia, el botón pasa a «Desactivar / Activar» y
  el expediente clínico nunca se pierde. Se elimina la ruta y el método de
  borrado.
- B. Mantener el borrado, pero comprobar antes si el usuario tiene ficha de
  paciente, personal o citas asociadas. Si las tiene, se bloquea con un mensaje
  claro en vez de un error 500. Si no tiene nada asociado, se borra.
- C. Las dos cosas: desactivación como acción principal en la tabla, y borrado
  definitivo permitido solo cuando el usuario no tiene ningún registro asociado.
- D. Mantener el borrado tal cual pero envolverlo en una transacción con captura
  de la excepción, mostrando un mensaje de error entendible en vez del 500. El
  borrado en cascada del expediente se acepta como comportamiento deseado.
- X. Other (please specify)

[Answer]: A

---

## Q3 — ¿Qué roles puede asignar el administrador, y qué pasa con «asistente»?

La columna `rol` de la tabla `usuarios` acepta cuatro valores:
`cliente`, `asistente`, `dentista`, `admin`. Pero el selector del formulario
(`resources/views/usuarios/create.blade.php`, líneas 71-77) solo ofrece tres:
Administrador, Dentista y Paciente/Cliente. Como `rol` es campo asignable en masa
y el controlador no valida nada, hoy se puede mandar cualquier cadena por POST.

Hay un efecto secundario que conviene decidir ahora: si editas un usuario que hoy
es `asistente`, el selector no tiene esa opción, ninguna sale marcada, y el
navegador envía la primera de la lista — el usuario se convertiría en
administrador sin que nadie lo pida.

- A. Añadir «Asistente» al selector y validar el rol contra los cuatro valores
  del enum. Es lo que evita la conversión accidental y refleja lo que la base de
  datos ya permite.
- B. Dejar el selector con tres opciones y validar el rol solo contra
  `admin`, `dentista`, `cliente`. Si el usuario editado es `asistente`, se
  conserva su rol actual y el selector se muestra bloqueado.
- C. Dejar el selector con tres opciones y validar contra los cuatro valores del
  enum, permitiendo que un asistente exista aunque no se pueda crear desde esta
  pantalla. Se conserva su rol al editar.
- X. Other (please specify)

[Answer]: A

---

## Q4 — ¿Cómo verificamos que las correcciones funcionan?

Al preparar esto encontré un obstáculo real: el esquema de las tablas
(`usuarios`, `servicios`, `pacientes`, `citas`) existe únicamente en el volcado
`database/dentissa.sql`. En `database/migrations/` solo hay migraciones de
Laravel para tablas auxiliares y dos añadidos posteriores. `phpunit.xml` está
configurado para SQLite en memoria, así que hoy una prueba automática de estos
módulos no tendría contra qué correr. La suite actual son los dos ejemplos que
trae Laravel.

Esto importa porque la práctica de la casa para una corrección de fallos pide una
prueba de regresión dirigida al fallo concreto y que la suite existente siga en
verde.

- A. Verificación manual guiada: entrego una lista de comprobación paso a paso
  contra el entorno Docker existente (crear usuario, editar servicio y confirmar
  que el precio sobrevive, intentar borrar un paciente con citas, etc.). Sin
  pruebas automáticas nuevas.
- B. Escribir migraciones para las tablas afectadas y añadir pruebas de
  funcionalidad en PHPUnit sobre SQLite en memoria. Es la opción que deja
  regresión automática de verdad, pero añade trabajo de esquema que va más allá
  de la corrección.
- C. Pruebas automáticas contra una base MariaDB de pruebas cargada desde
  `database/dentissa.sql`, ajustando `phpunit.xml`. Sin duplicar el esquema en
  migraciones, pero requiere que la base esté levantada para correr las pruebas.
- D. Empezar por la lista de comprobación manual (opción A) y dejar registrada la
  cobertura automática como trabajo aparte, para no mezclarla con esta
  corrección.
- X. Other (please specify)

[Answer]: D

---

## Consolidated Summary Confirmation

- **Q1 — Precio de servicios: B.** Se añade campo de precio y selector de color de agenda a los formularios de alta y edición de servicios, validando ambos. `ServiciosController::store()` y `update()` dejan de fijar `precio = 0` y de forzar `color_agenda`.
- **Q2 — Borrado de usuarios: A.** Se sustituye el borrado físico por desactivación lógica, siguiendo el patrón que ya usan Pacientes y Personal (`toggleActivo`). El botón pasa a «Desactivar / Activar», se elimina la ruta y el método de borrado, y el expediente clínico nunca se pierde.
- **Q3 — Roles asignables: A.** Se añade «Asistente» al selector de rol y se valida el rol contra los cuatro valores del enum (`cliente`, `asistente`, `dentista`, `admin`). Esto cierra el fallo por el que editar un asistente lo convertía en administrador.
- **Q4 — Verificación: D.** Se entrega una lista de comprobación manual contra el entorno Docker existente. La cobertura automática (migraciones o base MariaDB de pruebas) queda registrada como trabajo aparte. La suite PHPUnit existente debe seguir en verde.
- **TD-11 y TD-12 (sin pregunta).** Se reordena la ruta `GET /admin/usuarios/crear` para que se registre antes que `GET /admin/usuarios/{id}`, y se elimina el closure muerto de la línea 88 que devolvía la vista placeholder.

Does this all look correct before I generate the requirements artifact?

- Looks correct
- Request changes

[Answer]: Looks correct
