# Panorama de negocio — Dentissa

**Repositorio**: `dentissa` (repositorio único en la raíz del proyecto)
**Commit analizado**: `9fcff4c635d5134d9f252b6158812c4dd15e2657` (`main`, 2026-09-02)
**Profundidad del análisis**: Comprehensive

---

## 1. Qué es este sistema

Dentissa es un **sistema de gestión de clínica dental** desarrollado como aplicación web multipágina renderizada en servidor. No es un producto genérico de agenda: el modelo de datos, el vocabulario y las reglas implementadas son específicos de la práctica odontológica — expediente clínico con diagnóstico, tratamiento y receta; catálogo de servicios por especialidad; historia clínica del paciente con tipo de sangre, alergias, enfermedades crónicas y medicamentos actuales.

El sistema resuelve cuatro problemas de negocio de una clínica pequeña o mediana:

1. **Agendar citas sin solapes y dentro del horario real de cada dentista.**
2. **Mantener un expediente clínico por consulta**, vinculado a la cita que lo originó.
3. **Separar lo que cada actor puede ver y hacer** — el paciente, el dentista, el asistente y el administrador operan sobre el mismo conjunto de datos con alcances distintos.
4. **Permitir que el propio paciente se registre y agende**, incluyendo alta con cuenta de Google.

Todo el dominio está escrito en español (tablas, columnas, rutas, mensajes de validación, vistas). El andamiaje del framework permanece en inglés. Esa frontera lingüística es también una frontera arquitectónica y se documenta en `architecture.md`.

---

## 2. Actores del dominio

El sistema reconoce cuatro roles, codificados en el `enum` de la columna `usuarios.rol`. El rol es el eje de autorización de todo el sistema: determina a qué grupo de rutas se accede, qué consultas se filtran y qué elementos de la interfaz se renderizan.

| Rol | Quién es | Qué hace en el sistema |
| --- | --- | --- |
| `cliente` | El paciente de la clínica | Se registra, consulta y cancela sus citas, agenda nuevas citas, edita su perfil y su contraseña |
| `dentista` | El profesional que atiende | Ve y gestiona **sólo sus propias citas**, consulta y edita a sus pacientes, y es el único que **crea y edita expedientes clínicos** |
| `asistente` | Personal de apoyo asignado a uno o varios dentistas | Ve las citas **sólo de los dentistas que tiene asignados**, da de alta pacientes y consulta expedientes en modo lectura |
| `admin` | Administración de la clínica | Ve todo sin filtro, gestiona personal, servicios, usuarios y horarios, y accede al bloque de notas internas de cada cita |

**Punto crítico del modelo**: existen dos tablas de identidad complementarias. `usuarios` guarda la identidad y las credenciales de **todos** los actores; `pacientes` y `personal` guardan el perfil especializado de cada uno, en relación 1:1 con `usuarios`. Todas las relaciones de agenda apuntan a `personal.id`, **no** a `usuarios.id`. Además, el vocabulario se desdobla: `usuarios.rol` usa el valor `admin` mientras que `personal.tipo` usa `administrador`, y `PersonalController` traduce entre ambos con un `match` en dos lugares distintos. Es la fuente de confusión más probable para quien llegue nuevo al código.

---

## 3. Capacidades de negocio implementadas

### 3.1 Identidad y acceso

- **Registro directo del paciente** (`RegisterController`): formulario de 9 campos validados en español, dentro de una transacción que crea la fila de `usuarios` con rol `cliente` y la de `pacientes`. Inicia sesión automáticamente al terminar.
- **Registro y acceso con Google** (`GoogleAuthController` mediante `laravel/socialite`): flujo OAuth 2.0 de dos pasos. Si la cuenta de Google ya existe se entra directamente; si el correo coincide con una cuenta local, se vincula; si no existe ninguna cuenta, **no se crea el usuario en ese momento** — se guardan los datos en sesión y se exige un segundo paso de contacto (teléfono, datos demográficos y aceptación de términos) antes de crear nada.
- **Asignación automática de dentista**: en ambos caminos de alta, el sistema no pide elegir dentista. `RegisterController::asignarDentistaAutomatico()` selecciona el dentista activo con **menos pacientes a su cargo**, con desempate por antigüedad. Es una regla de balanceo de carga deliberada y es la única lógica de asignación del sistema.
- **Cuenta desactivada**: `usuarios.activo` es una baja lógica. El acceso local añade `'activo' => 1` como condición del intento de autenticación, y el acceso por Google lo comprueba explícitamente con un mensaje dedicado.

### 3.2 Agenda de citas

Es la capacidad más elaborada del sistema y la que concentra las reglas de negocio reales. Una cita sólo puede crearse si supera **tres comprobaciones encadenadas**:

1. **El dentista atiende ese día de la semana.** Se calcula el día ISO (`1` = lunes … `7` = domingo) de la fecha solicitada y se busca la fila correspondiente en `horarios_dentista`. Si no existe, o si su marca `disponible` es falsa, la cita se rechaza.
2. **La cita completa cabe dentro del horario de atención.** El intervalo entre `fecha_hora` y `fecha_hora` más `duracion_min` debe quedar contenido entre `hora_inicio` y `hora_fin` de ese día. El mensaje de error devuelve el horario real del dentista.
3. **No hay solape con otra cita del mismo dentista.** Se excluyen del cálculo los estados `cancelada` y `no_asistio`, de modo que una cita cancelada libera su hueco.

Esta regla está implementada **dos veces**, una para el personal y otra para el cliente, con diferencias deliberadas: el cliente **debe** elegir un servicio y hereda su duración, y no puede agendar en el pasado; el personal puede omitir el servicio e introducir la duración a mano (entre 10 y 300 minutos) y no tiene fecha mínima.

### 3.3 Ciclo de vida de la cita

Seis estados forman la máquina del negocio:

`agendada` (inicial) a `confirmada` a `en_curso` a `completada`, con `cancelada` y `no_asistio` como salidas terminales.

- El personal autorizado puede llevar una cita a cualquier estado; **no existe un grafo de transiciones válidas** que impida, por ejemplo, pasar de `completada` a `agendada`.
- El cliente sólo puede **cancelar**, y sólo desde `agendada` o `confirmada`.
- «Eliminar» una cita desde la administración **no borra la fila**: la lleva al estado `cancelada`. El borrado lógico es el patrón dominante del sistema.
- Cada cambio de estado deja rastro en `historial_citas`. Ese rastro se escribe hoy por dos caminos descoordinados (un trigger de base de datos y una inserción del controlador), lo que produce filas incompletas; se documenta como deuda en `code-quality-assessment.md`.

### 3.4 Alcance de visibilidad por rol

La regla que decide **qué citas ve cada quien** está centralizada en dos ayudantes de `CitasController` y es el control de acceso más maduro del proyecto:

- El `dentista` ve únicamente las citas cuyo `dentista_id` coincide con su propia fila de `personal`.
- El `asistente` ve únicamente las citas de los dentistas registrados para él en la tabla puente `asistente_dentista`. **Esa tabla es lo único que acota su agenda**: sin filas, no ve nada.
- El `admin` no se filtra.

La misma regla se reaplica en las acciones puntuales, devolviendo `403` cuando no se cumple.

### 3.5 Expediente clínico

- Un expediente se crea siempre **desde una cita**, y sólo se ofrecen las citas del paciente en estado `completada`, `en_curso` o `confirmada` que **aún no tienen expediente**. Es decir, la relación efectiva es de un expediente por cita.
- Contiene `diagnostico` (obligatorio), `tratamiento`, `receta`, `observaciones` y `proxima_visita`.
- `proxima_visita` no es informativa: alimenta una sugerencia real. Al abrir el formulario de nueva cita, el sistema precarga la fecha del expediente más reciente del paciente si aún está en el futuro.
- El **asistente queda excluido** de crear, guardar y editar expedientes, tanto en el servidor (respuesta `403`) como en la interfaz (bloques condicionados por rol).
- El **dentista sólo ve en su índice los expedientes que él mismo firmó.**

### 3.6 Catálogo de servicios

`servicios` es el catálogo de tratamientos de la clínica: nombre, descripción, categoría, duración por defecto, precio, color de agenda y marca de activo. La línea base versionada trae 15 servicios repartidos en categorías de tipo Preventivo, Cirugía, Restauración, Endodoncia, Estética, Ortodoncia, Diagnóstico y Prostodoncia.

**Advertencia de negocio**: el precio existe en el modelo de datos y en las dos vistas SQL, pero la interfaz de administración **lo borra en cada escritura**. Ver `code-quality-assessment.md`, hallazgo `DEF-2`.

### 3.7 Gestión de personal y horarios

- Alta y edición del personal con su tipo (`dentista`, `asistente`, `administrador`), especialidad, cédula, universidad, años de experiencia y biografía.
- **Definición del horario semanal** de cada dentista, que es la entrada de la comprobación 1 de la agenda. El guardado reescribe el horario completo (borra e inserta) y siempre marca `disponible = 1`.
- **Asignación de dentistas a un asistente**, que mantiene la tabla puente `asistente_dentista` con el mismo patrón de borrar e insertar.
- Baja lógica mediante `toggleActivo`, nunca borrado físico.

### 3.8 Paneles por rol

Cuatro paneles, uno por rol, con la misma forma: agregados del día más la lista de citas de hoy. El del `dentista` añade pacientes atendidos en el mes en curso y la próxima cita pendiente; el del `asistente` resuelve primero sus dentistas asignados y devuelve listas vacías si no tiene ninguno; el del `cliente` muestra su próxima cita y sus cinco últimas citas pasadas.

---

## 4. Reglas de negocio identificadas

Las siguientes reglas son verificables en el código y tienen un criterio de aprobación binario.

| ID | Regla | Dónde se implementa |
| --- | --- | --- |
| `BRN-1` | Una cuenta con `activo = 0` no puede iniciar sesión por ningún camino | `AuthController::login`, `GoogleAuthController::callback` |
| `BRN-2` | Un paciente nuevo recibe automáticamente el dentista activo con menos pacientes asignados | `RegisterController::asignarDentistaAutomatico` |
| `BRN-3` | Una cuenta creada por Google no tiene contraseña local hasta que el usuario la establezca | migración `2026_07_18_195924`, `RegisterController::contactoStore` |
| `BRN-4` | Una cita sólo se crea si el dentista tiene horario declarado y disponible ese día de la semana | `CitasController::store`, `ClienteCitasController::agendarStore` |
| `BRN-5` | La cita completa, incluida su duración, debe caber dentro del horario de atención de ese día | idem `BRN-4` |
| `BRN-6` | Una cita no puede solaparse con otra del mismo dentista, salvo que la existente esté `cancelada` o `no_asistio` | idem `BRN-4` |
| `BRN-7` | Un cliente sólo puede agendar eligiendo un servicio, y la duración la fija el servicio | `ClienteCitasController::agendarStore` |
| `BRN-8` | Un cliente sólo puede cancelar sus propias citas, y sólo si están `agendada` o `confirmada` | `ClienteCitasController::cancelar` |
| `BRN-9` | Un dentista sólo ve y opera sobre las citas de las que es titular | `CitasController::aplicarAlcanceRol`, `autorizadoParaCita` |
| `BRN-10` | Un asistente sólo ve las citas de los dentistas asignados en `asistente_dentista` | `CitasController::dentistaIdsAsistente` |
| `BRN-11` | Un expediente se crea sólo sobre una cita del paciente sin expediente previo y en estado `completada`, `en_curso` o `confirmada` | `ExpedientesController::create` |
| `BRN-12` | El asistente no puede crear ni modificar expedientes | `ExpedientesController` (respuesta `403` explícita) |
| `BRN-13` | Las `notas_internas` de una cita son visibles sólo para el rol `admin`; las `notas_paciente` son visibles para el cliente | `resources/views/citas/show.blade.php` |
| `BRN-14` | Dar de baja a un paciente o a un miembro del personal es una desactivación lógica, no un borrado | `PacientesController::toggleActivo`, `PersonalController::toggleActivo` |
| `BRN-15` | Cancelar una cita desde administración cambia su estado; no elimina la fila | `CitasController::destroy` |
| `BRN-16` | Al dar de alta personal o pacientes sin contraseña, el sistema genera una y la muestra una única vez | `PersonalController::store`, `PacientesController::store` |

Dos reglas del catálogo están **contradichas por el código actual** y se registran como defectos, no como reglas: el precio del servicio (`DEF-2`) y el alta de usuarios desde administración (`DEF-1`). Ver `code-quality-assessment.md`.

---

## 5. Capacidades declaradas pero no implementadas

El modelo de datos promete tres cosas que el código no cumple. Documentarlas evita que se cuenten como funcionalidad existente:

| Capacidad | Evidencia de que está declarada | Evidencia de que no está implementada |
| --- | --- | --- |
| **Recordatorios de cita** | Columna `citas.recordatorio_enviado` | Sólo se lee, nunca se escribe. No hay ningún job, mailable, notification ni tarea programada en todo `app/` |
| **Adjuntar radiografías y documentos al expediente** | Columna `expedientes.archivos_url`, validada como JSON | Se inserta siempre como `null`; no hay ninguna gestión de subida de ficheros |
| **Reportes de administración** | Ruta `/admin/reportes` con entrada de menú | La ruta devuelve una vista `placeholder` |

Además, las dos vistas SQL `v_citas_hoy` y `v_historial_paciente` están definidas en la línea base del esquema y **ninguna línea de PHP las consulta**: los controladores reconstruyen esos mismos cruces a mano.

---

## 6. Estado de la línea base de datos versionada

La línea base `database/dentissa.sql` trae datos de ejemplo, pero **no un juego de datos operable**. El recuento verificado sobre el volcado es el siguiente:

| Tabla | Filas sembradas | Consecuencia de negocio |
| --- | --- | --- |
| `usuarios` | 8 (1 `admin`, 1 `dentista`, 6 `cliente`) | **No hay ninguna cuenta con rol `asistente`**, de modo que ese rol no puede ejercitarse sin crear antes el usuario |
| `personal` | 1 | Sólo existe un profesional; el balanceo de `BRN-2` no tiene alternativas que comparar |
| `servicios` | 15 | Catálogo operable |
| `citas` | 2 | Citas de demostración |
| `horarios_dentista` | **0** | **Ninguna cita puede agendarse tras un arranque limpio**: la comprobación 1 de `BRN-4` falla siempre hasta que un administrador guarde un horario |
| `expedientes` | 0 | Sin expedientes de ejemplo |

El último punto es el más relevante para quien reciba el sistema: las dos citas sembradas **no son reproducibles desde la interfaz** en un entorno recién levantado, porque la regla de agenda las rechazaría. El primer paso operativo obligatorio tras levantar el entorno es definir el horario semanal del dentista.

---

## 7. Estado de madurez del producto

Dentissa es un **proyecto académico o de primera iteración funcional**, no un sistema en producción. La evidencia es convergente:

- **El flujo principal funciona de extremo a extremo**: registro, acceso, agenda con validación real de disponibilidad, expediente clínico y paneles por rol están implementados y son coherentes.
- **No existe ninguna red de seguridad**: la cobertura de pruebas sobre el dominio es cero, no hay integración continua de ningún tipo, el formateador está instalado pero nunca se ejecuta y no hay análisis estático.
- **La documentación no describe el producto**: el `README.md` es el genérico del framework Laravel, sin personalizar.
- **Hay datos personales reales y credenciales dentro del repositorio y en la historia de git.** Es el riesgo más serio del proyecto y se detalla en `code-quality-assessment.md`, hallazgo `SEC-1`.
- **El arranque no es reproducible desde cero por el camino documentado.** El guion `composer setup` falla contra una base de datos vacía; el único camino que funciona es Docker, y funciona por una secuencia que nada explica.

Traducido a negocio: la funcionalidad existe y es demostrable, pero **el sistema no es todavía operable con datos de pacientes reales** sin resolver antes la exposición de datos sensibles, la reproducibilidad del esquema y los dos defectos funcionales confirmados.

---

## 8. Glosario del dominio

| Término del código | Significado de negocio |
| --- | --- |
| `usuarios` | Identidad y credenciales de cualquier actor del sistema, sea paciente o personal |
| `pacientes` | Perfil clínico y demográfico de un cliente; extiende su fila de `usuarios` |
| `personal` | Perfil profesional de un miembro de la clínica; extiende su fila de `usuarios` |
| `citas` | Reserva de un intervalo de tiempo de un dentista para un paciente |
| `expedientes` | Registro clínico de una consulta: diagnóstico, tratamiento, receta y próxima visita |
| `servicios` | Catálogo de tratamientos con duración, precio y categoría |
| `horarios_dentista` | Disponibilidad semanal recurrente de un dentista; gobierna si una cita puede agendarse |
| `historial_citas` | Bitácora de cambios de estado de una cita |
| `asistente_dentista` | Tabla puente que decide qué agenda ve cada asistente |
| `activo` | Marca de baja lógica; una fila inactiva se conserva pero deja de operar |
| `duracion_min` | Duración de la cita en minutos; por defecto 30, o la del servicio elegido |

---

## Sources

- `app/Http/Controllers/` — los 13 controladores, leídos íntegros
- `app/Http/Middleware/RoleMiddleware.php`, `app/Http/Middleware/PreventBackHistory.php`
- `app/Models/Usuario.php`, `app/Models/Servicio.php`
- `routes/web.php` — 161 líneas, superficie HTTP completa
- `database/dentissa.sql` — 671 líneas, línea base del esquema con 17 tablas, 2 vistas y 1 trigger, y sus bloques `INSERT`
- `database/migrations/2026_07_18_195924_add_google_id_to_usuarios_table.php`, `database/migrations/2026_07_20_000000_create_asistente_dentista_table.php`, `database/migrations/2026_07_20_000001_add_dentista_id_to_pacientes_table.php`
- `resources/views/citas/show.blade.php`, `resources/views/expedientes/show.blade.php` — guardas de visibilidad por rol
- `config/auth.php` — proveedor de autenticación
- Escaneo del desarrollador: `aidlc/spaces/default/intents/260902-docs-sistema-dental/inception/reverse-engineering/developer-scan.md`
