---
id: 032
slug: alertas-cifrado-copias
status: implemented
created: 2026-10-07
extends: [030]
---

# 032 · Alertas y copias cifradas en producción

## Problema
La aplicación está en internet desde la v0.5.0 (spec 030), pero nadie se entera de que falla:
si da errores del servidor, si alguien prueba contraseñas en ráfaga o si deja de responder, el
responsable lo sabe cuando una persona se queja. Además, las copias de seguridad de la base, con
datos personales y de salud (hoy ficticios), se guardan en claro en el servidor y en el PC del
responsable: quien consiga una puede leerlo todo.

Atiende **parcialmente** la brecha de alertas (OB6) y el riesgo de datos de salud sin cifrar en
reposo (RS15): la comprobación de salud con la base, lo que pide staging y el cifrado de los
campos clínicos quedan fuera, y el disco del servidor sin cifrar se acepta como riesgo hasta que
se carguen datos reales.

## Historias de usuario
- Como responsable del proyecto, quiero recibir un correo cuando la aplicación da errores del servidor, cuando hay una ráfaga de inicios de sesión fallidos o cuando deja de responder desde internet, para actuar antes de que me lo digan.
- Como responsable del proyecto, quiero que las copias de seguridad queden cifradas de forma que solo yo pueda abrirlas, para que una copia robada del servidor o de mi PC no exponga datos de pacientes.
- Como responsable del proyecto, quiero restaurar una copia cifrada con mi clave y saber cómo guardar esa clave, para no perder la capacidad de recuperar la base.
- Como responsable del proyecto, quiero que el riesgo del disco sin cifrar quede aceptado con su condición de revisión, para no olvidarlo si un día se cargan datos reales.

## Criterios de aceptación
- [x] CA1 · Dada la aplicación publicada, cuando en 5 minutos más del 1 % de sus respuestas son errores del servidor, entonces el responsable recibe un correo en 10 minutos o menos que indica el tipo de aviso, cuántos errores hubo, en qué intervalo y cómo consultar el detalle en el servidor.
- [x] CA2 (abuso) · Dado un atacante que prueba contraseñas, cuando en 5 minutos hay más de 10 inicios de sesión fallidos (con contraseña o en la vuelta del acceso con Google) desde una misma dirección o contra una misma cuenta, entonces el responsable recibe un correo en 10 minutos o menos con el número de fallos, el intervalo y cómo consultar el detalle en el servidor. El límite de intentos (030 CA8) sigue funcionando igual.
- [x] CA3 · Dada la aplicación publicada, cuando deja de responder correctamente desde internet durante 5 minutos seguidos, aunque el servidor esté apagado o sin conexión, entonces el responsable recibe un correo en 10 minutos o menos; y cuando vuelve a responder, recibe otro que lo indica.
- [x] CA4 (abuso) · Dado un atacante que provoca errores o fallos de inicio de sesión sin parar para inundar al responsable, cuando continúa, entonces el responsable recibe como máximo un correo por tipo de aviso cada 30 minutos, con el recuento acumulado desde el anterior; ningún aviso se pierde del todo.
- [x] CA5 (abuso) · Dado alguien que lee un correo de aviso (por ejemplo, con acceso al buzón), cuando lo abre, entonces no encuentra datos personales ni de salud, correos de cuentas, contraseñas, direcciones de red de las personas ni secretos de la configuración: solo el tipo de aviso, recuentos, horas y cómo consultar el detalle en el servidor.
- [x] CA6 · Dada la configuración de producción sin el envío de avisos configurado, o con el texto de ejemplo de la guía, cuando se ejecuta la verificación posterior al despliegue, entonces falla e indica qué falta, sin mostrar valores. Con la configuración completa, el responsable puede enviar un aviso de prueba siguiendo el procedimiento documentado y le llega en 5 minutos o menos.
- [x] CA7 · Dado un despliegue, cuando se hace la copia de seguridad previa, entonces la copia queda cifrada desde el primer momento: en ningún momento existe en claro en el disco del servidor, y la que el responsable se lleva a su PC también está cifrada. Si el servidor no tiene con qué cifrar, la copia no se hace en claro: el despliegue se detiene antes de cualquier cambio y lo dice.
- [x] CA8 (abuso) · Dado un atacante que obtiene una copia (del servidor, del PC del responsable o de un correo) o que entra en el servidor, cuando intenta leerla, entonces no puede: lo que permite abrir las copias no está en el servidor, en el repositorio ni en el paquete de la aplicación, solo en poder del responsable.
- [x] CA9 · Dada una copia cifrada y la clave del responsable, cuando sigue el procedimiento documentado de restauración (también el de vuelta atrás con copia, 030 CA7), entonces la base queda exactamente como estaba (mismas filas y misma comprobación de cada tabla), ensayado al menos una vez. Con una clave que no es la suya o una copia alterada o incompleta, la restauración se detiene sin tocar la base y dice por qué. Reemplaza en ese punto a 030 CA6, cuyas copias eran en claro.
- [x] CA10 · Dada la liberación de esta spec, cuando termina, entonces no queda en el servidor ninguna copia en claro de antes, la guía indica cómo sustituir y borrar las del PC, cómo guardar una segunda copia de la clave en un lugar seguro distinto del PC y qué pasa si se pierde (las copias no se recuperan); y la documentación de seguridad registra el disco del servidor sin cifrar como riesgo aceptado, aprobado por el responsable, que vence al cargar el primer dato real de pacientes.

## Fuera de alcance
- Comprobación de salud que incluya la base de datos (OB6.a) → roadmap, objetivo 3.
- Avisos de latencia, de disco casi lleno o de cola de trabajos; métricas y paneles → roadmap.
- Aviso de copia fallida y copia automática diaria: las copias siguen haciéndose solo al desplegar, con el responsable delante (decisión del usuario, 2026-10-07).
- Notificaciones en el móvil u otros canales distintos del correo (decisión del usuario, 2026-10-07).
- Cifrar el disco del servidor: exige reinstalarlo; se acepta como riesgo (CA10).
- Cifrar los campos clínicos dentro de la base (RS15.a) → spec 013.
- Llevar copias fuera del servidor de forma automática (el paso al PC sigue siendo manual).
- Staging y sus alertas y copias → roadmap, objetivo 5.
- Cambiar el límite de intentos, los permisos de los roles o lo que se registra en la auditoría.

## Seguridad y privacidad
- **Datos sensibles:** las copias contienen todos los datos personales y de salud y las credenciales con hash de las cuentas (`docs/security.md`, clasificación restringida y confidencial). Los correos de aviso salen del servidor hacia un buzón externo, así que no llevan ninguno de esos datos (CA5).
- **Quién puede hacer qué:**
  - Solo el responsable del proyecto recibe los avisos y puede abrir y restaurar las copias.
  - El servidor puede crear copias cifradas, pero no abrirlas.
  - Los roles de la aplicación no cambian.
- **Casos de abuso:**
  - Como atacante, pruebo contraseñas en ráfaga → el límite me frena y el responsable recibe un aviso (CA2).
  - Como atacante, provoco errores o fallos sin parar para llenar el buzón y que el responsable ignore los avisos → como máximo un correo por tipo cada 30 minutos, con el recuento (CA4).
  - Como alguien con acceso al buzón del responsable, leo los avisos → no contienen datos personales, de salud ni secretos (CA5).
  - Como atacante, robo una copia del servidor o del PC, o entro en el servidor → no puedo leer su contenido (CA8).
  - Como atacante, sustituyo o altero una copia para que se restaure → la restauración se detiene sin tocar la base (CA9).

## Cobertura de riesgos
| Corrección | Alcance | Criterios / motivo y destino |
|---|---|---|
| OB6.a — Health check que compruebe la conexión a MariaDB | fuera | comprobación interna de la aplicación, no de alertas → roadmap, objetivo 3 |
| OB6.b — Alertas mínimas gratuitas (5xx, logins fallidos), en producción | dentro | CA1, CA2, CA3, CA4, CA5, CA6 |
| OB6.b — la misma corrección en staging (se divide como OB6.c) | fuera | staging no existe → roadmap, objetivo 5 |
| RS15.a — Cast `encrypted` en los campos clínicos | fuera | llega con los modelos Eloquent → spec 013 |
| RS15.b — Cifrado de backups, en producción | dentro | CA7, CA8, CA9, CA10 |
| RS15.b — Cifrado de disco, en producción (se divide como RS15.c) | dentro | aceptado como excepción hasta cargar datos reales (CA10) |
| RS15.b — Cifrado de disco y de backups en staging (se divide como RS15.d) | fuera | staging no existe → roadmap, objetivo 5 |

## Auditoría
No aplica a la auditoría de la aplicación: esta spec no añade acciones de usuario sobre datos
sensibles, autenticación ni permisos. Los avisos leen fallos que la aplicación ya produce (errores
y fallos de inicio de sesión) sin cambiar qué se registra, y abrir o restaurar una copia es una
operación del responsable en el servidor, fuera de la aplicación. Registrar los inicios de sesión
fallidos en la auditoría sigue en el objetivo 3 del roadmap.

## Requisitos no funcionales
- Gratuito: ni los avisos ni el cifrado tienen coste (`docs/observability.md`, OB6.b).
- Los correos de aviso están en español y se entienden sin abrir el servidor: qué pasó, cuándo y dónde mirar.
- Detectar errores y fallos de inicio de sesión no hace más lenta la aplicación de forma apreciable para quien la usa.
- Ningún secreto (credenciales del envío de correo, clave de las copias) queda en el repositorio, en el paquete de la aplicación ni en la salida de la verificación.

## Preguntas abiertas
Ninguna.

## Decisiones
| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-10-05 | brecha | La 031 prevista juntaba demasiados riesgos | Las alertas y el cifrado de las copias van juntos en la 032; la 031 se quedó con cabeceras, incidencias de la 030 y acceso remoto. La 032 tiene dos capacidades que podrían liberarse por separado, pero son 10 criterios | usuario (spec 031, Decisiones) |
| 2026-10-07 | brecha | Canal de los avisos | Correo a la cuenta del responsable | usuario |
| 2026-10-07 | brecha | Qué más debe avisar, además de OB6.b | Aplicación caída desde internet (CA3). Copia fallida no: ver la fila siguiente | usuario |
| 2026-10-07 | brecha | Las copias solo se hacen al desplegar, con el responsable delante: un aviso de copia fallida no aporta | Sin copia diaria automática ni aviso de copia fallida | usuario |
| 2026-10-07 | brecha | Quién puede abrir las copias cifradas | Solo el responsable, con una clave que no está en el servidor; el servidor cifra pero no descifra; si la clave se pierde, las copias no se recuperan | usuario |
| 2026-10-07 | contradicción | La 031 dejaba escrito «con fecha de revisión» para el riesgo del disco, y `/review` pide que toda excepción tenga vencimiento; el usuario prefiere revisarlo solo antes de cargar datos reales | La excepción del disco vence al cargar el primer dato real de pacientes, sin fecha fija; sustituye la «fecha de revisión» propuesta en la 031 | usuario |
| 2026-10-07 | implícita | Umbrales de los avisos de errores y de inicios de sesión fallidos | Más del 1 % de errores del servidor en 5 minutos; más de 10 fallos en 5 minutos por dirección o por cuenta | `docs/observability.md` → Métricas y alertas |
| 2026-10-07 | implícita | Los avisos deben ser gratuitos | Sin coste | `docs/observability.md` OB6.b |
| 2026-10-07 | supuesto | Plazo de llegada de un aviso | 10 minutos o menos desde que se cumple la condición; 5 minutos o menos para el aviso de prueba | agente |
| 2026-10-07 | supuesto | Cuándo se considera caída la aplicación | 5 minutos seguidos sin responder correctamente desde internet; también se avisa al volver | agente |
| 2026-10-07 | supuesto | Cómo evitar que una ráfaga llene el buzón | Como máximo un correo por tipo de aviso cada 30 minutos, con el recuento acumulado | agente |
| 2026-10-07 | supuesto | Qué contienen los avisos | Tipo, recuentos, horas y cómo consultar el detalle en el servidor; sin datos personales, de salud, correos, contraseñas, direcciones de red de las personas ni secretos | agente (`docs/security.md`, clasificación) |
| 2026-10-07 | supuesto | Qué pasa con las copias en claro de antes | Ninguna queda en claro en el servidor al liberar (se cifran o se borran, lo decide `/plan`); la guía indica sustituir y borrar las del PC | agente |
| 2026-10-07 | supuesto | División de las correcciones que mezclan producción, disco y staging | OB6.b en producción dentro y OB6.c (staging) fuera; RS15.b (copias en producción) dentro, RS15.c (disco en producción) aceptada como excepción y RS15.d (staging) fuera | agente (como las divisiones de staging de la 030) |

## Notas para /plan
- La 031 cambia `deploy/verificar.sh`, `deploy/README.md` y otros scripts de `deploy/`: la implementación de la 032 debe partir de la 031 ya fusionada en `main`, o rebasarse sobre ella.
- El aviso de aplicación caída tiene que comprobarse desde fuera del servidor (CA3: también con el servidor apagado), con un servicio gratuito.
- `respaldar.sh`, `restaurar.sh`, `volver.sh <tag> <copia>` y `desplegar.sh` son los puntos de las copias (spec 030). Volver a una versión anterior a la 032 con una copia cifrada no debe dejar la base a medias.
- La guía de despliegue (§ copias y § archivos de entorno) recoge la clave y las credenciales del correo; el responsable ya usa Gmail para el acceso con Google.

## Historial
| Fecha | Cambio | Motivo |
|---|---|---|
| 2026-10-07 | Creación en borrador | `/specify 032` |
| 2026-10-07 | Aprobada | Aprobación del usuario |
| 2026-10-25 | Implementada: criterios con sus tests en verde (412 en la suite) y ensayados en el servidor (T041–T044); la excepción EX3 de CA10 la aprueba el responsable en la liberación (T096) | `/implement 032` |
| 2026-10-25 | Vuelve a approved: `/review` r1 changes_requested (R1–R9, tareas T052–T062) | `/review 032` |
| 2026-10-25 | Implementada de nuevo: correcciones de /review r1 (T052–T062) con sus tests en verde (417 en la suite) | `/implement 032` |
| 2026-10-25 | Vuelve a approved: `/review` r2 changes_requested (R23–R29, tareas T063–T072) | `/review 032 --rerun` |
| 2026-10-25 | Implementada de nuevo: correcciones de /review r2 (T063–T072) con sus tests en verde (422 en la suite) | `/implement 032` |
