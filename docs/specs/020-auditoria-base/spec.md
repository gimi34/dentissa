---
id: 020
slug: auditoria-base
status: approved
created: 2026-09-28
extends: [030, 031, 032]
---

# 020 · Registro de auditoría base y correlación por petición

## Problema
Hoy el sistema no deja rastro de nada: ni de quién abre una ficha clínica, ni de quién intenta
entrar donde no debe, ni de quién cambia un rol. Con datos de salud, eso impide responder "quién
hizo qué, sobre qué, cuándo y con qué resultado", que es lo que exige la constitución (P7) y el
marco de referencia del expediente clínico. Tampoco hay forma de relacionar los registros técnicos
de una misma petición. Esta spec crea el registro de auditoría común, inmodificable y consultable
solo por el admin, y el identificador de petición que lo enlaza con los registros técnicos; otras
specs (014, 021, 022 y 041) lo usan para registrar sus eventos. Atiende OB1 **parcialmente** (los
eventos concretos de autenticación, cuentas y datos clínicos van en 021 y 022) y OB2 por completo.

## Historias de usuario
- Como admin, quiero consultar quién hizo qué y cuándo, para investigar un acceso indebido.
- Como responsable del proyecto, quiero que nadie pueda alterar ni borrar ese registro, para que sea evidencia fiable.
- Como integrante del equipo, quiero que todos los registros de una misma petición compartan un identificador, para reconstruir lo que pasó.
- Como paciente, quiero que nadie pueda ver mis datos clínicos sin dejar rastro.

## Criterios de aceptación
- [ ] CA1 · Dada una acción auditable, cuando ocurre, entonces queda un evento con: actor, acción, tipo e identificador del recurso, resultado, fecha y hora, dirección de origen e identificador de la petición. El actor es el usuario de la sesión; si la acción la hace una cuenta comprobada por otra credencial distinta de la sesión (por ejemplo, el enlace para restablecer la contraseña de la spec 041), es esa cuenta; si no hay ninguno de los dos, se guarda el identificador intentado. El actor nunca sale de un identificador enviado por el cliente.
- [ ] CA2 (abuso) · Dado cualquier usuario, incluido un admin, cuando intenta modificar o borrar un evento, entonces la aplicación no ofrece ninguna forma de hacerlo y el almacén rechaza la modificación o el borrado de cualquier evento, de uno en uno o de muchos a la vez, venga de donde venga, incluidos los intentos hechos con las credenciales con que opera la propia aplicación, salvo la purga por retención (CA5). Solo quedan fuera de este criterio las operaciones hechas con privilegios totales sobre el almacén que lo vacían entero de una vez, desactivan su protección o cambian su estructura (ver Supuestos).
- [ ] CA3 · Dado cualquier evento, cuando se registra, entonces no contiene datos personales ni de salud en claro, salvo la dirección de origen y el identificador intentado de CA1 (ver Supuestos): solo identificadores y, en los cambios, los nombres de los campos cambiados sin sus valores.
- [ ] CA4 · Dado un usuario autenticado, cuando el sistema le deniega una acción (por ejemplo, entrar al área de otro rol), entonces queda un evento de acceso denegado con la acción y el recurso solicitados, también cuando la denegación se responde como «no encontrado» para no revelar que el recurso existe; un recurso que de verdad no existe no genera evento.
- [ ] CA5 · Dados eventos de más de 5 años, cuando se ejecuta la purga periódica, entonces se eliminan solo esos y los más recientes se conservan intactos.
- [ ] CA6 · Dado un admin, cuando consulta el registro, entonces puede filtrar por actor, acción, tipo de recurso, identificador de recurso y rango de fechas, con resultados paginados del más reciente al más antiguo.
- [ ] CA7 (abuso) · Dado un usuario que no es admin, cuando intenta consultar el registro, entonces se le deniega y no ve ningún evento.
- [ ] CA8 · Dada cualquier petición, cuando se responde, entonces la respuesta lleva un identificador de petición: si el cliente envió uno con formato válido se reutiliza; si no, se genera uno nuevo.
- [ ] CA9 (abuso) · Dado un cliente que envía un identificador de petición malformado o excesivamente largo, cuando se procesa la petición, entonces se descarta y se usa uno generado; el valor enviado nunca se refleja ni se registra tal cual.
- [ ] CA10 · Dada una petición que produce registros técnicos o eventos de auditoría, cuando se consultan, entonces todos llevan el mismo identificador de petición; los registros técnicos llevan además el usuario de la sesión, si la hay, y los eventos llevan el actor de CA1, que coincide con él salvo cuando la acción la hace una cuenta comprobada por otra credencial.
- [ ] CA11 · Dada una acción sobre datos clínicos o una consulta del registro de auditoría, cuando su evento no puede escribirse, entonces la acción no se realiza y el usuario ve un error genérico sin detalles internos.
- [ ] CA13 (abuso) · Dado un cliente que envía un identificador de petición válido ya usado en otra petición (por ejemplo, copiado de otra respuesta), cuando se procesa la petición, entonces se acepta, pero cada evento conserva su propia identidad, actor, fecha y dirección de origen, y el identificador repetido nunca hace que un evento reciba el actor ni el acceso de la otra petición.
- [ ] CA14 · Dado un evento que no puede escribirse en producción, cuando ocurre, entonces el responsable recibe un aviso por correo como los demás avisos de producción (spec 032), sin datos del evento ni de la persona, y con el mismo límite de frecuencia que esos avisos.

## Fuera de alcance
- Eventos de login, logout, acceso con Google, altas de cuentas, cambios de rol y de contraseña (spec 021).
- Eventos de lectura y cambio de pacientes, expedientes y citas, y el historial de citas completo (spec 022; las lecturas de la 014 ya usan este registro).
- Formato de los logs técnicos por entorno, redacción de errores de base de datos y health check (spec 023).
- Exportar el registro o enviarlo a un servicio externo, y alertas sobre su contenido: solo se avisa de que un evento no pudo escribirse (CA14).
- Trasladar la vigilancia de producción (spec 032) a las tareas programadas y retirar su temporizador: va en una spec propia (decisión del usuario, 2026-10-28). Esta spec solo hace que la purga (CA5) también corra en producción.

## Seguridad y privacidad
- Datos sensibles involucrados: el propio registro es información restringida. Contiene identificadores de personas y recursos clínicos, la dirección de origen y, sin sesión, el identificador intentado (ambos datos personales), pero ningún dato de salud (CA3).
- Quién puede hacer qué: solo el admin consulta el registro. Nadie lo modifica ni lo borra, salvo la purga automática por retención y la limitación aceptada de CA2 (ver Supuestos).
- Casos de abuso:
  - Como admin malintencionado, intento borrar el rastro de un acceso → no hay forma en la aplicación y el almacén lo rechaza (CA2), salvo vaciarlo entero, desactivar su protección o cambiar su estructura con privilegios totales sobre el almacén (limitación aceptada, ver Supuestos).
  - Como personal sin permiso, intento leer el registro para ver qué pacientes consultan otros → se deniega y queda registrado (CA7, CA4).
  - Como atacante, envío identificadores de petición malformados o demasiado largos para inyectar contenido → se descartan (CA9).
  - Como atacante, repito un identificador de petición válido (copiado de otra respuesta) para confundir la trazabilidad → se acepta como riesgo residual: el identificador solo sirve para correlacionar; cada evento conserva su identidad, actor, fecha y dirección de origen, y nunca recibe el actor ni el acceso de otra petición (CA13).
  - Como atacante, provoco fallos del registro para acceder a datos clínicos sin dejar rastro → la acción se deniega (CA11), y en producción el responsable recibe un aviso (CA14).
  - Como atacante, envío el identificador de otra cuenta para que un evento se le atribuya → el actor es solo el usuario de la sesión o la cuenta que la acción comprobó con su credencial, nunca un identificador enviado (CA1). Cada spec que registre con una cuenta comprobada lo demuestra con un test que envía el identificador de otra cuenta.

## Cobertura de riesgos
| Corrección | Alcance | Criterios / motivo y destino |
|---|---|---|
| OB1.a — almacén solo-anexado con actor, acción, recurso, resultado, fecha, IP y request_id | dentro | CA1, CA2, CA3 |
| OB1.b — eventos de autenticación y acceso con Google | fuera | eventos concretos de acceso → spec 021 |
| OB1.c — accesos denegados | dentro | CA4 |
| OB1.d — lecturas de ficha y expedientes | fuera | spec 014 (CA12) y spec 022 |
| OB1.e — cambios de pacientes, expedientes y citas | fuera | spec 022 |
| OB1.f — altas, roles, activación y contraseñas | fuera | spec 021 |
| OB1.g — retención de 5 años con purga programada | dentro | CA5, CA12 |
| OB1.h — consulta solo admin que audita su propia lectura | dentro | CA6, CA7, CA12 |
| OB2.a — identificador de petición aceptado o generado y devuelto | dentro | CA8, CA9, CA13 |
| OB2.b — identificador y actor en el contexto de logs y auditoría | dentro | CA10 |
| OB4.a — redactar los valores enlazados de los errores de base de datos | fuera | spec 023, antes de tener datos reales. Mientras tanto, la consulta o la purga fallidas pueden dejar identificadores y fechas en el log técnico: residuo aceptado con la condición de la 030 (datos ficticios, logs solo en el servidor) (decisión del usuario, 2026-10-28; /analyze 020 A44) |
| OB4.b — modo de producción verificado al desplegar | fuera | ya mitigada (v0.5.0, spec 030); esta spec no la cambia |
| OB4.c — lo mismo en staging | fuera | objetivo 5, cuando exista staging |

## Auditoría
- [ ] CA12 · Dada una consulta del registro por un admin o una ejecución de la purga, cuando ocurre, entonces queda registrado quién consultó y con qué filtros, o cuántos eventos purgó la purga y hasta qué fecha.

## Diseño
Quien consulta el registro es la administración, casi siempre investigando algo concreto: un acceso
indebido o una ficha consultada sin motivo. Necesita acotar rápido por persona, acción, recurso o
fechas y leer cada evento sin ambigüedad. La pantalla debe sentirse parte del panel de
administración: sobria y precisa, una herramienta y no un tablero.

Qué debe lograr cada pantalla:
- **Consulta del registro:** en cinco segundos se entiende qué eventos se ven y cómo acotarlos. Los
  filtros y la lista quedan a la vista. Cada evento dice quién, qué, sobre qué, con qué resultado,
  cuándo, desde dónde y con qué identificador de petición. Tiene estado vacío, error de filtro
  junto al campo y paginación del más reciente al más antiguo. En el teléfono, cada evento se lee
  completo sin desplazamiento horizontal.
- **Error genérico cuando el registro no está disponible (CA11):** explica que la acción no se hizo,
  sin detalles internos, y da el identificador de petición para soporte. Se ve igual con y sin
  sesión, porque también aparece en las pantallas de acceso de la spec 041.
- **Entrada «Auditoría» en el menú del admin** (sección «Análisis»): marcada como la sección actual
  cuando se está en la consulta, también en el menú del teléfono.

Recursos de marca: los del sistema; sin imágenes propias.
Qué debe diseñarse (`/design-spec 020`): la consulta con sus estados (con eventos, vacía, filtro no
válido y varias páginas), el error genérico y la entrada del menú.
Sistema: [docs/design/system.md](../../design/system.md).
Diseño por criterios: [design.md](design.md) (`approved`; `c4` aceptado con 80/100 en el cuarto de cinco candidatos).

## Criterios de diseño
Modo: operar · umbral 80 · mínimo por punto 3 · presupuesto 5 candidatos (propuesta; ver Supuestos).
Cada pantalla se mide a 320, 390 y 1440 px.

| ID | Qué se mide | Objetivo | Sale de |
|---|---|---|---|
| K1 | Consulta: los filtros y el primer evento | En la primera pantalla a 390 y 1440 px | CA6 |
| K2 | Cada evento completo | Actor, acción, recurso, resultado, fecha, origen e identificador de petición presentes; a 320 px sin desplazamiento horizontal | CA1, CA6 |
| K3 | Estado vacío | «No hay eventos con estos filtros» presente | CA6 |
| K4 | Filtro no válido | El error junto a su campo | CA6 |
| K5 | Paginación | Visible tras la lista | CA6 |
| K6 | Error genérico | «No se realizó» y el identificador de petición en la primera pantalla a 320 px, con y sin sesión | CA11 |
| K7 | Menú | «Auditoría» marcada como la sección actual en la consulta, también en el menú del teléfono | CA6 |
| K8 | JavaScript por página | ≤ 100 kB | constitución P10 |
| K9 | Estabilidad visual | CLS ≤ 0.1 | modo operar |
| K10 | Titulares | ≤ 2 líneas | modo operar |

CA7 (denegar a quien no es admin) y los demás criterios no tienen nada nuevo que medir en la
pantalla: los verifican sus tests.

Puertas: las de `design-spec/references/criterios.md` §2, sin cambios. Rúbrica: jerarquía 25,
primera pantalla 25, sistema 30, lectura 20, plantilla 0 (ver Supuestos).

## Requisitos no funcionales
- Accesibilidad: WCAG 2.1 AA en las pantallas nuevas (P9), a 320, 390 y 1440 px de ancho; los estados y errores se anuncian con texto, no solo con color.
- Descubribilidad: la consulta y el error genérico no aparecen en buscadores (son pantallas privadas o de error).
- Registrar un evento no añade más de 50 ms al tiempo de respuesta de la acción en local.
- La consulta del registro con filtros responde en menos de 2 s con 100.000 eventos.

## Preguntas abiertas

## Supuestos
- La dirección de origen se guarda porque la exige el evento de acceso; es dato personal y se protege como el resto del registro.
- El fallo cerrado (CA11) aplica a acciones sobre datos clínicos y a la consulta del registro (decisión del usuario, 2026-09-28). Para los demás eventos (denegaciones, accesos), si el registro falla la acción sigue su curso y el fallo queda en el log técnico.
- La inmutabilidad se garantiza en la aplicación y en el almacén (decisión del usuario, 2026-09-28).
- Limitación aceptada de CA2: quien tenga privilegios totales sobre el almacén, incluidas las credenciales con que opera la aplicación, puede hacer tres cosas que CA2 no cubre: vaciar el almacén entero de una vez, desactivar su protección y cambiar su estructura (por ejemplo, renombrarlo o modificar sus campos). CA2 sí cubre la modificación y el borrado de eventos, de uno en uno o de muchos a la vez, también con las credenciales de la aplicación. Es coherente con el ADR 0004 (decisiones del usuario, 2026-09-28; redacción unificada, /analyze 020 A43).
- Reutilizar un identificador de petición válido enviado por el cliente se acepta como riesgo residual: solo correlaciona registros y no es prueba de identidad (decisión del usuario, 2026-09-28). Va en su propio criterio (CA13), y "distinguibles" significa que cada evento conserva su identidad, actor, fecha y dirección de origen y nunca recibe el actor ni el acceso de otra petición (decisión del usuario, 2026-09-28).
- El identificador intentado (por ejemplo, el correo escrito en un inicio de sesión fallido) y la dirección de origen son datos personales: son las únicas excepciones a CA3 y se protegen como el resto del registro. El tratamiento concreto del identificador intentado lo define la spec 021.
- El filtro por actor de CA6 es por identificador del usuario, no por nombre ni correo, para que la consulta registrada en CA12 no guarde datos personales (decisión del usuario, 2026-09-28).
- La purga se ejecuta una vez al mes y requiere que cada entorno ejecute las tareas programadas, también producción (/analyze 020 A45, decisión del usuario, 2026-10-28).
- Una cuenta comprobada por otra credencial distinta de la sesión puede ser el actor de un evento (CA1). Lo necesita la spec 041 para registrar el restablecimiento de contraseña, que ocurre sin sesión (decisión del usuario, 2026-10-27; /analyze 020 A50). Quien registra con una cuenta así responde de haberla comprobado.
- Criterios de diseño propuestos para /design-spec: modo operar, umbral 80, mínimo 3 por punto y presupuesto de 5 candidatos (los del proyecto). En la rúbrica, `plantilla` pesa 0 porque es una herramienta interna del panel de administración, y sus 15 puntos pasan a `sistema` (30) y `lectura` (20).
- El objetivo 3 se divide en las specs 020–023 y esta se crea primero (decisión del usuario, 2026-09-28).
- La spec se mantiene junta con sus 14 criterios; solo el traslado de la vigilancia al `scheduler` de producción sale a una spec propia (decisión del usuario, 2026-10-28).

## Decisiones
| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-10-28 | contradicción | La barra lateral del escritorio (#C75078) no cumple el contraste AA (DS1) y la puerta de contraste mide la página entera | La barra se queda como está, como en la 034: deuda DS1 del objetivo 6; la puerta se aplica al resto de la pantalla ([design.md](design.md)) | usuario |
| 2026-10-28 | brecha | La 020 cambia cómo se despliega, se vuelve atrás y se verifica producción (un servicio más que se levanta, se para y se comprueba), que definen las specs 030 y 031, y solo extendía la 032 | Extiende también la 030 y la 031; al liberar, cada una recibe su fila de Historial | usuario (/analyze 020 A72) |
| 2026-10-28 | diseño | Resultado del ciclo de /design-spec | `c4` aceptado con 80/100; 25 eventos por página y selectores para acción y tipo de recurso; acta en [design.md](design.md) | /design-spec |

## Notas para /plan
- La spec 014 consume este registro para CA11 (accesos denegados desde sus Gates, vía `Gate::after`) y CA12 (lecturas en `PacientesController@show` y `ExpedientesController@show`); su plan asume un punto de registro único, algo como `Auditoria::registrar(accion, recurso_tipo, recurso_id, resultado, contexto)`. Si la API difiere, corregir con `/plan 014 --fix`.
- Almacén propuesto en `docs/observability.md`: tabla propia en MariaDB (`auditoria`), sin claves foráneas en cascada hacia tablas de negocio. Inmutabilidad en la base: trigger que rechace `UPDATE`/`DELETE` salvo la purga, o un usuario de BD sin esos permisos; decidir en el plan.
- Formato válido del identificador de petición propuesto: UUID.
- Correlación: middleware en `bootstrap/app.php` con `Context::add('request_id', …)` y cabecera `X-Request-Id` en la respuesta.
- Denegaciones generales: `RoleMiddleware` (`abort(403)`) y los `abort(403)` sueltos de los controladores.
- Purga: comando programado en `routes/console.php`; hoy no hay scheduler en Docker Compose.
- La tabla nueva se crea con migración aunque el resto del esquema siga en el volcado (spec 013).
- /analyze 020 ronda 5 (2026-10-28), hallazgos para el `--redo`:
  - **A45:** el `scheduler` también en el Compose de producción, con la purga mensual; Rollout de convivencia con el temporizador del vigilante hasta su spec.
  - **A46:** los Gates de las specs 026–028 deniegan con `denyAsNotFound` (404); CA4 los cubre, distinguiéndolos de un 404 real.
  - **A44:** el residuo de OB4.a se acepta de nuevo con la condición de la 030 (decisión del usuario, 2026-10-28).
  - **A60 y CA14:** `App\Avisos\Vigilante` (spec 032) avisa de `auditoria.fallo`.
  - **A49:** el menú sigue el patrón de la 033 (`.active` y `aria-current="page"`) y actualiza `MovilPacienteTest::SECCIONES`; `capturar.mjs --medir` en la verificación.
  - **A50 y A51:** la API del registro ya tiene `?Usuario $actor` (D15, por la D2 de la 041).
  - **A52:** el error 503 no depende del layout autenticado.
  - **A47:** el Constitution Check se hace contra la constitución 1.3.0.

## Historial
| Fecha | Cambio | Motivo |
|---|---|---|
| 2026-09-28 | Creación | /specify, objetivo 3 del roadmap (OB1, OB2) |
| 2026-09-28 | Aprobada por el usuario | /specify |
| 2026-09-28 | CA2 acota la inmutabilidad y los Supuestos declaran su limitación; CA3 nombra sus excepciones (dirección de origen, identificador intentado); CA9 y los casos de abuso aceptan como residual la repetición de un identificador válido; el caso de abuso de consulta cita CA4; supuesto del filtro por actor. Vuelve a `draft`. | /analyze 020 ronda 1: A01, A10, A11, A25, A27 (decisión del usuario, 2026-09-28) |
| 2026-09-28 | Aprobada de nuevo por el usuario | /specify --edit |
| 2026-09-28 | CA2 cubre también los intentos con las credenciales de la aplicación y el borrado masivo; solo quedan fuera vaciar el almacén o desactivar su protección. El identificador válido repetido pasa de CA9 a un criterio propio, CA13 (abuso), con "distinguibles" concretado. "Quién puede hacer qué" remite a la limitación y "Datos sensibles" nombra el identificador intentado. Vuelve a `draft`. | /analyze 020 ronda 2: A29, A30, A31 (decisión del usuario, 2026-09-28) |
| 2026-09-28 | Aprobada de nuevo por el usuario | /specify --edit |
| 2026-09-28 | La exclusión de CA2 abarca también los cambios de estructura del almacén hechos con privilegios totales; el caso de abuso del admin y los Supuestos lo recogen. Vuelve a `draft`. | /analyze 020 ronda 3: A39 (decisión del usuario, 2026-09-28) |
| 2026-09-28 | Aprobada de nuevo por el usuario | /specify --edit |
| 2026-10-28 | CA1 y CA10 admiten como actor una cuenta comprobada por otra credencial (spec 041) y prohíben tomarlo de un identificador enviado; CA4 cubre las denegaciones que responden «no encontrado»; CA14 nuevo (aviso del fallo al escribir un evento en producción) y `extends: [032]`; la limitación de CA2 en una sola frase; «Fuera de alcance» separa el traslado del vigilante; secciones «Diseño» y «Criterios de diseño»; accesibilidad y descubribilidad en los RNF; notas para el `--redo`. Vuelve a `draft`. | /analyze 020 ronda 5: A43, A45, A46, A48, A50, A51, A60 (decisiones del usuario, 2026-10-27 y 2026-10-28) |
| 2026-10-28 | Aprobada de nuevo por el usuario; se mantiene junta | /specify --edit |
| 2026-10-28 | Diseño aprobado (`design.md`, `c4` con 80/100) | /design-spec 020 (aprobación del usuario) |
| 2026-10-28 | `extends: [030, 031, 032]`: la 020 cambia el despliegue, la vuelta atrás y la verificación de producción de la 030 y la 031. Vuelve a `draft`. | /analyze 020 ronda 6: A72 (decisión del usuario, 2026-10-28) |
| 2026-10-29 | Aprobada de nuevo por el usuario | /specify --edit |
