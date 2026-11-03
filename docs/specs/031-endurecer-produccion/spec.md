---
id: 031
slug: endurecer-produccion
status: released
released: v0.10.0 (2026-10-20)
created: 2026-10-05
extends: [001, 030]
---

# 031 · Endurecer producción tras la apertura

## Problema
La aplicación está en internet desde la v0.5.0 (spec 030), pero el navegador no recibe ninguna
instrucción de protección: otra web puede mostrarla incrustada para engañar a quien la usa, y nada
obliga a volver siempre por la conexión segura. Las respuestas revelan la versión del lenguaje del
servidor. La liberación de la 030 dejó además cuatro incidencias en el roadmap:
- la vuelta del acceso con Google termina en un error del servidor si llega sin haberlo iniciado;
- un servicio puede seguir con una configuración antigua después de cambiarla, y nada lo detecta;
- la verificación del despliegue no detecta que las credenciales de Google siguen con el texto de
  ejemplo de la guía;
- el responsable solo puede administrar el servidor desde su casa.

Atiende **parcialmente** los riesgos de cabeceras de seguridad (RS8) del objetivo 5: la política
de contenido estricta y lo que pide staging quedan fuera. Las alertas y el cifrado de las copias
van a la spec 032.

## Historias de usuario
- Como paciente o miembro del personal, quiero que el navegador impida que otras webs muestren la aplicación incrustada y que siempre use la conexión segura, para que no me engañen al usar mi cuenta.
- Como responsable del proyecto, quiero que la aplicación no revele las versiones de sus componentes, para no facilitar ataques dirigidos a fallos conocidos.
- Como persona que entra con Google, quiero que un fallo en la vuelta de Google me devuelva al inicio de sesión con un mensaje claro, para poder intentarlo de nuevo en lugar de ver un error.
- Como responsable del proyecto, quiero que la verificación del despliegue detecte la configuración incompleta o que no se ha aplicado, para no enterarme cuando alguien no puede entrar.
- Como responsable del proyecto, quiero administrar el servidor desde fuera de casa sin abrir ninguna vía nueva desde internet, para atender incidencias desde cualquier lugar.

## Criterios de aceptación
- [x] CA1 · Dada cualquier respuesta de la aplicación publicada (páginas, redirecciones y errores), cuando la recibe el navegador, entonces le indica cuatro cosas: que no la muestre dentro de páginas de otros sitios; que no deduzca el tipo de un archivo distinto del declarado; que al ir a otro sitio no envíe la dirección completa, solo el dominio; y que solo cargue scripts, estilos, fuentes e imágenes de la propia aplicación y de los proveedores de fuentes que ya usa. Todas las pantallas actuales de cada rol siguen funcionando igual, sin contenido bloqueado.
- [x] CA2 (abuso) · Dado un atacante con una página propia que incrusta la aplicación para que la persona pulse sin saberlo (clickjacking), cuando alguien abre esa página, entonces el navegador no muestra la aplicación dentro de ella.
- [x] CA3 · Dada la aplicación publicada, cuando el navegador recibe cualquier respuesta por la conexión segura, entonces se le indica que use solo la conexión segura con el dominio y sus subdominios durante al menos 180 días. En el entorno local, sin conexión segura, no se envía esa indicación.
- [x] CA4 (abuso) · Dado un visitante, cuando recibe cualquier respuesta de la aplicación publicada, incluidas las de error y las de archivos estáticos, entonces en las cabeceras no figura la versión del lenguaje del servidor ni la del servidor web.
- [x] CA5 (abuso) · Dado un visitante que llega a la vuelta del acceso con Google sin haberlo iniciado, con el inicio caducado, tras cancelarlo en Google o con datos manipulados, cuando llega, entonces vuelve al inicio de sesión con el mensaje «No se pudo iniciar sesión con Google. Inténtalo de nuevo.». No inicia sesión, no se crea ni se vincula ninguna cuenta, no hay error del servidor, y la visita sigue contando para el límite de intentos de la vuelta de Google (030 CA8).
- [x] CA6 · Dada la configuración de producción con las credenciales de Google vacías o con el texto de ejemplo de la guía, cuando se ejecuta la verificación posterior al despliegue, entonces falla e indica cuál falta, sin mostrar ningún valor. La guía de configuración de producción avisa de que hay que sustituirlas.
- [x] CA7 · Dado un cambio en la configuración guardada de un servicio de producción (credenciales, clave del túnel), cuando el responsable sigue el procedimiento documentado, entonces el servicio pasa a usar el valor nuevo. Si un servicio en marcha usa una configuración distinta de la guardada, la verificación posterior al despliegue falla y dice qué servicio es, sin mostrar valores.
- [x] CA8 · Dado el responsable fuera de su red local con un dispositivo suyo autorizado en la red privada del proyecto, cuando se conecta a la administración del servidor, entonces entra con su clave, sin contraseña. Esta vía se suma al acceso desde la red local y reemplaza en ese punto a 030 CA3, que solo permitía la red local. Tras un reinicio del servidor, la vía vuelve a funcionar sola en 5 minutos o menos. Con esta vía activa, volver a una versión anterior a esta spec da en la verificación posterior un único fallo conocido y documentado (un servicio de la red privada escucha fuera del propio equipo); en eso se modifica 030 CA7 para esas versiones.
- [x] CA9 (abuso) · Dado un atacante en internet, cuando busca una vía de administración en la dirección pública de la casa del responsable o del servidor, entonces no encuentra ningún puerto abierto. Desde un dispositivo que no pertenece a la red privada no llega al servidor, aunque conozca su dirección privada. Con contraseña no se entra por ninguna vía.
- [x] CA10 · Dado un dispositivo autorizado que se pierde o deja de usarse, cuando el responsable sigue el procedimiento documentado para retirarlo, entonces ese dispositivo deja de llegar al servidor y los demás siguen entrando.
- [x] CA11 · Dada la versión liberada, cuando se construye, entonces cada imagen usa la versión más nueva publicada hace 7 días o más dentro de su misma rama (sin cambio de versión mayor ni menor), y el escaneo de imágenes no da hallazgos críticos ni altos sin una excepción vigente. La excepción de las imágenes (EX2) se renueva solo para lo que no tenga corrección publicada, o se retira.

## Fuera de alcance
- Una política de contenido estricta, sin scripts, manejadores ni estilos incrustados en las vistas: requiere cambiar unas 40 vistas → roadmap, al tocar cada pantalla (decisión del usuario, 2026-10-05).
- Alertas y cifrado de las copias de seguridad → spec 032 (decisión del usuario, 2026-10-05).
- Cifrado del disco del servidor: exige reinstalarlo. Se propone aceptarlo como riesgo mientras los datos sean ficticios, con fecha de revisión, en la spec 032 (decisión del usuario, 2026-10-05).
- Staging, y la indicación de usar solo conexión segura en staging → roadmap, objetivo 5.
- Inscribir el dominio en la lista de precarga de los navegadores: el dominio ya exige conexión segura en los navegadores por su extensión.
- Cambiar los permisos de los roles, el límite de intentos o lo que se registra en la auditoría.

## Seguridad y privacidad
- **Datos sensibles:** no añade datos nuevos. Protege en el navegador las páginas que ya muestran datos personales y de salud, y cambia la vía de administración del servidor, que da acceso a todos ellos y a las copias de seguridad.
- **Quién puede hacer qué:**
  - Los permisos de los roles en la aplicación no cambian.
  - Solo el responsable del proyecto administra el servidor: desde la red local o desde sus dispositivos autorizados en la red privada, siempre con clave.
  - Solo el responsable autoriza y retira dispositivos de la red privada.
- **Casos de abuso:**
  - Como atacante, incrusto la aplicación en mi web para que la persona pulse sin saberlo → el navegador no la muestra (CA2).
  - Como atacante, busco las versiones de los componentes para usar fallos conocidos → no aparecen en las respuestas (CA4).
  - Como atacante, envío a la vuelta de Google peticiones sin inicio previo o manipuladas para provocar errores o colarme en una cuenta → vuelvo al inicio de sesión sin sesión ni cuenta, sin error del servidor y con el límite de intentos aplicado (CA5).
  - Como atacante en internet, busco una vía para administrar el servidor o pruebo contraseñas → no hay puertos abiertos ni se aceptan contraseñas (CA9).
  - Como persona que encuentra un dispositivo perdido del responsable, intento entrar al servidor → el responsable lo ha retirado y no llega (CA10).

## Cobertura de riesgos
| Corrección | Alcance | Criterios / motivo y destino |
|---|---|---|
| RS8.a — cabeceras de seguridad: política de contenido, no incrustar en otros sitios, no deducir tipos y limitar la dirección de origen (corrección dividida: política de contenido compatible con el código incrustado actual) | dentro | CA1, CA2 |
| RS8.d — política de contenido estricta, sin código incrustado en las vistas (nueva al dividir RS8.a) | fuera | Requiere cambiar unas 40 vistas (decisión del usuario, 2026-10-05) → roadmap, por pantallas |
| RS8.b — indicación de usar solo conexión segura, en producción (corrección dividida) | dentro | CA3 |
| RS8.c — lo mismo en staging (nueva al dividir RS8.b) | fuera | No hay staging → roadmap, objetivo 5 |
| RS18.a — refrescar las imágenes y volver a escanearlas antes de que venza EX2 (2026-10-31) | dentro | CA11 (decisión del usuario, /plan 031) |

## Auditoría
No aplica en esta spec, por la excepción a P7 de la spec 030 (aceptada por el usuario el
2026-10-03; `docs/roadmap.md`): los inicios de sesión fallidos, también los de Google, no se
registran como eventos de auditoría hasta la spec 021, que registra los eventos de acceso sobre el
registro que crea la 020. El fallo de la vuelta de Google (CA5) debe quedar identificable como inicio
de sesión fallido, para que la 021 lo registre sin volver a cambiar el acceso con Google.

## Diseño
Solo cambia un mensaje en la pantalla de inicio de sesión (CA5), con el bloque de errores que ya
existe y sin cambios visuales. Sistema: [docs/design/system.md](../../design/system.md).

## Requisitos no funcionales
- Ninguna pantalla actual pierde funciones ni contenido por las indicaciones al navegador (CA1). Se comprueba recorriendo las pantallas de cada rol con la consola del navegador sin bloqueos.
- La vía de administración desde fuera vuelve sola tras un reinicio en 5 minutos o menos (CA8).
- Ningún despliegue a producción sin aprobación explícita del responsable (constitución, regla de despliegue).

## Preguntas abiertas

## Decisiones
| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-10-05 | brecha | La 031 prevista juntaba cuatro riesgos y cinco incidencias (unos 14 criterios) | Dividir: 031 (cabeceras, indicación de conexión segura, versiones ocultas, incidencias de la 030 y acceso de administración desde fuera) y 032 (alertas y cifrado de las copias) | usuario |
| 2026-10-05 | contradicción | 030 CA3 (liberada) dice que la única vía de administración es la red local, y el usuario quiere administrar desde fuera de casa | Se añade una red privada de los dispositivos del responsable, sin abrir puertos y siempre con clave; esta spec reemplaza en ese punto a 030 CA3 (CA8) | usuario |
| 2026-10-05 | brecha | ¿Política de contenido estricta? Unas 8 vistas tienen scripts incrustados, 11 tienen manejadores y 32 tienen estilos en línea | Básica ahora, compatible con el código actual; la estricta, al roadmap (RS8.d) | usuario |
| 2026-10-05 | brecha | Cifrar el disco exige reinstalar el servidor | Solo se cifran las copias (spec 032); el disco, como riesgo aceptado con fecha de revisión mientras los datos sean ficticios | usuario |
| 2026-10-05 | brecha | ¿Qué hacer con el error del servidor en la vuelta de Google? | Corregirlo en una spec, sin una v0.5.1 aparte | usuario (/release 030, T097) |
| 2026-10-05 | implícita | ¿Cuenta un fallo de la vuelta de Google para el límite de intentos? | Sí: en la vuelta de Google cuentan todos los intentos | `docs/specs/030-despliegue-servidor-propio/spec.md` (CA8) |
| 2026-10-05 | implícita | ¿Se registra en la auditoría el fallo de la vuelta de Google? | No hasta la spec 021 (excepción P7 de la 030; la 020 deja los eventos de acceso a la 021) | `docs/roadmap.md` (Excepción P7 de la spec 030), `docs/specs/020-auditoria-base/spec.md` (Cobertura de riesgos: eventos de acceso → 021) · usuario (/analyze 031 A9) |
| 2026-10-05 | implícita | ¿Puede desplegarse sin aprobación? | No: aprobación humana explícita del responsable | `docs/deployment.md` (regla innegociable) |
| 2026-10-06 | brecha | Duración de la indicación de conexión segura y alcance | 180 días, con subdominios; sin inscripción en la lista de precarga, porque la extensión del dominio ya la exige | usuario (180 días, 2026-10-06; /analyze 031 A16) |
| 2026-10-05 | supuesto | Cuánta dirección enviar al ir a otro sitio | Solo el dominio a otros sitios; la dirección completa dentro de la aplicación | valor habitual |
| 2026-10-06 | brecha | ¿Entra el refresco de imágenes de RS18.a, que vence el 2026-10-31? La 031 ya reconstruye las imágenes | Sí: CA11, en un solo despliegue antes del vencimiento | usuario (/plan 031) |
| 2026-10-06 | contradicción | 030 CA7 exige que la versión anterior pase la misma verificación, y con la red privada activa la verificación de una versión anterior a la 031 da un fallo | Se acepta ese único fallo conocido y documentado al volver a una versión anterior a la 031 (CA8) | usuario (/analyze 031 A2) |
| 2026-10-05 | supuesto | Texto del fallo de la vuelta de Google | «No se pudo iniciar sesión con Google. Inténtalo de nuevo.» | valor razonable |

## Notas para /plan
- Red privada: el usuario mencionó Tailscale (sin puertos en el router; desactivar la caducidad de la clave del servidor; `ufw` que permita SSH por la interfaz de la red privada). Actualizar `docs/deployment.md` y la fila del SSH en `docs/security.md`.
- Cabeceras: `add_header` en nginx o un middleware, también en las respuestas de error y de estáticos (`always`). La política de contenido debe admitir `fonts.googleapis.com`/`fonts.gstatic.com` y `fonts.bunny.net`, y el código incrustado actual (RS8.d al roadmap).
- Versiones: `expose_php = Off` en la imagen de producción y `server_tokens off` en nginx (hoy sale `x-powered-by: PHP/8.4.25`).
- Vuelta de Google: capturar `Laravel\Socialite\Two\InvalidStateException` y otros fallos de Socialite en `GoogleAuthController::callback` (el 500 ocurrió el 2026-10-05 desde una IP de Google).
- `verificar.sh`: exigir que `GOOGLE_CLIENT_ID` termine en `.apps.googleusercontent.com` y que el secreto no esté vacío ni sea el marcador. Comparar el entorno de cada contenedor en marcha (`docker inspect`) con `/opt/dentissa/entorno/*.env`, sin mostrar valores. Documentar `up -d --force-recreate <servicio>` (y `config:cache` en `app`) tras cambiar un archivo de entorno.
- Si la liberación cae antes del 2026-10-31, puede incluir el refresco de imágenes de RS18.a (vence EX2).

## Historial
| Fecha | Cambio | Motivo |
|---|---|---|
| 2026-10-05 | Creación | /specify: endurecer producción tras la 030 (objetivo 5) |
| 2026-10-06 | Aprobada (la indicación de conexión segura se queda en 180 días) | Decisión del usuario |
| 2026-10-06 | CA11: refresco y escaneo de imágenes (RS18.a dentro). Vuelve a `draft` | /plan 031, decisión del usuario |
| 2026-10-06 | Aprobada de nuevo (al aprobar el plan) | Decisión del usuario |
| 2026-10-06 | CA8 declara el fallo conocido al volver a versiones anteriores (modifica 030 CA7); la excepción P7 vence con la 021; HSTS como decisión del usuario. Vuelve a `draft` | /analyze 031 A2, A9, A16 |
| 2026-10-06 | Aprobada de nuevo | Decisión del usuario |
| 2026-10-20 | Liberada en v0.10.0 (PR #38, merge `e4fd141`, tag `v0.10.0`) y desplegada en producción | /release |
| 2026-10-20 | CA1 ampliado por la 038 CA7: la política de contenido admite el marco de `https://www.google.com` solo en `/` (`frame-src` desde `marcos.conf`); el resto de rutas sigue sin marcos externos, y `frame-ancestors 'none'` y `X-Frame-Options` no cambian. `verificar.sh` comprueba la redirección a https con `/admin` (038 D10). Rige al liberar la 038 | /implement 038 (T043) |
| 2026-10-31 | Comportamiento modificado por 020 en v0.12.0: `verificar.sh` comprueba el `scheduler` y «Tareas programadas», «Entorno aplicado» cubre cinco servicios y `aplicar-entorno.sh app` recrea también el `scheduler` | /release |
