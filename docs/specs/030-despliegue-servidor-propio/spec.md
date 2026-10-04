---
id: 030
slug: despliegue-servidor-propio
status: approved
created: 2026-10-03
extends: []
---

# 030 · Producción en un servidor propio expuesto a internet

## Problema
Hoy la aplicación solo funciona en el equipo de cada integrante: no hay ningún entorno al que
puedan entrar pacientes, personal o profesores. El responsable del proyecto tiene un servidor propio,
recién instalado, y una dirección en internet. Quiere publicar en él la aplicación como entorno de
producción del proyecto académico, con datos ficticios.

Tal como está, la configuración de local no se puede publicar:
- la base de datos y su administrador quedarían a la vista;
- el modo de depuración muestra detalles internos;
- las cuentas del volcado tienen contraseñas conocidas;
- no hay copia de seguridad ni forma de volver a la versión anterior;
- el inicio de sesión no limita los intentos.

Atiende **parcialmente** los riesgos del objetivo 5 del roadmap: lo que hace falta para publicar con
seguridad. Las cabeceras de seguridad, las alertas y el cifrado de copias y disco van a la spec 031,
y el resto se queda en el roadmap. Atiende por completo el límite de intentos (RS4), porque la
aplicación quedará abierta a todo internet.

## Historias de usuario
- Como responsable del proyecto, quiero publicar la aplicación en mi servidor y que se pueda usar desde internet con una dirección fija y conexión segura, para que el equipo, los profesores y los usuarios de prueba la usen sin instalar nada.
- Como responsable del proyecto, quiero que el servidor solo exponga la aplicación, para que nadie pueda llegar a la base de datos ni a herramientas internas.
- Como responsable del proyecto, quiero copia de seguridad antes de cada despliegue y una forma ensayada de volver a la versión anterior, para recuperarme de un despliegue fallido sin perder datos.
- Como persona con cuenta (paciente o personal), quiero que nadie pueda adivinar mi contraseña probando sin límite, para que mi cuenta siga siendo mía aunque la aplicación esté en internet.

## Criterios de aceptación
- [ ] CA1 · Dado el servidor recién instalado y el procedimiento documentado del proyecto, cuando el responsable lo sigue para desplegar una versión liberada, entonces la aplicación responde en su dirección pública con conexión segura y pasa la verificación posterior al despliegue: comprobación de salud, inicio de sesión con una cuenta de prueba de cada rol, y agendar y cancelar una cita ficticia.
- [ ] CA2 · Dada la aplicación publicada, cuando una persona navega, envía formularios, inicia sesión o vuelve del acceso con Google, entonces todas las direcciones, redirecciones y cookies de sesión que genera la aplicación usan la conexión segura; ninguna página remite a una dirección sin cifrar.
- [ ] CA3 (abuso) · Dado un atacante en internet o en la red local, cuando intenta conectarse al servidor por cualquier vía que no sea la dirección pública de la aplicación, entonces no llega a la base de datos, a su herramienta de administración ni a ningún servicio interno; la única vía administrativa es el acceso remoto con clave desde la red local, sin contraseña.
- [ ] CA4 (abuso) · Dado un error inesperado en producción, cuando ocurre, entonces la persona ve un mensaje genérico sin detalles internos (rutas, consultas, versiones ni datos), y la lista de verificación de la configuración de producción lo confirma en cada despliegue.
- [ ] CA5 (abuso) · Dado el entorno de producción, cuando alguien prueba las contraseñas de las cuentas del volcado que figuran en el repositorio o en su documentación, entonces ninguna funciona. Además, las credenciales de producción (base de datos, clave de la aplicación y acceso con Google) no están en el repositorio ni en el paquete de la aplicación, y la base de datos no se usa con su cuenta de máximo privilegio.
- [ ] CA6 · Dado un despliegue, cuando empieza, entonces antes de cualquier cambio queda una copia de seguridad fechada de la base de datos fuera de la aplicación; y restaurar esa copia deja la base exactamente como estaba, algo que se ha ensayado al menos una vez.
- [ ] CA7 · Dado un despliegue que falla en la verificación posterior, cuando el responsable sigue el procedimiento documentado de vuelta atrás, entonces la versión anterior vuelve a funcionar y pasa la misma verificación en 15 minutos o menos. Se ha ensayado al menos una vez antes de dar la spec por terminada.
- [ ] CA8 (abuso) · Dado un atacante, cuando en 1 minuto supera 5 intentos (fallidos de inicio de sesión con el mismo correo desde la misma dirección, o de cualquier tipo de alta de cuenta o de vuelta del acceso con Google desde la misma dirección), entonces los siguientes se rechazan temporalmente con un mensaje que dice cuánto esperar, sin revelar si la cuenta existe. Una persona legítima vuelve a poder entrar al terminar la espera.
- [ ] CA9 · Dado un reinicio del servidor o de los servicios de la aplicación, cuando termina, entonces la aplicación vuelve a estar disponible por sí sola en 5 minutos o menos, y los registros del servidor web y de la aplicación anteriores al reinicio se conservan.
- [ ] CA10 · Dada una versión liberada, cuando se construye y despliega en el servidor, entonces usa las mismas versiones fijas de todos sus componentes que en local, y la configuración del lenguaje del servidor (límites de subida y zona horaria) se aplica de verdad; el paquete de la aplicación no incluye secretos, el repositorio de git, dependencias de desarrollo ni el volcado de datos.

## Fuera de alcance
- Un entorno de staging separado: este servidor es producción (decisión del usuario, 2026-10-03). Las correcciones que piden staging quedan en el roadmap.
- Cabeceras de seguridad y HSTS, alertas, y cifrado del disco y de las copias de seguridad → spec 031 (decisión del usuario, 2026-10-03).
- Restringir quién puede llegar a la aplicación: queda abierta a todo internet (decisión del usuario, 2026-10-03).
- Despliegue automático desde el CI: el despliegue es manual y lo aprueba una persona.
- Quitar las contraseñas del README, sustituir el volcado por datos generados y purgar el historial de git → objetivo 1 (RS6.a, RS6.b, RS6.d).
- El registro de auditoría de los intentos de inicio de sesión → spec 020.
- Envío de correos desde producción.
- Decidir el futuro de las herramientas del frontend, fijar las acciones del CI y alinear la versión declarada del lenguaje → roadmap, objetivo 5.

## Seguridad y privacidad
- **Datos sensibles:** la aplicación publicada guarda datos personales, de salud y credenciales según la clasificación de `docs/security.md`. En este proyecto académico son ficticios (decisión del usuario, 2026-09-28), pero se protegen igual. Las copias de seguridad contienen los mismos datos.
- **Quién puede hacer qué:**
  - los permisos de cada rol en la aplicación no cambian;
  - el responsable del proyecto administra el servidor y es el único que despliega, siempre con aprobación explícita;
  - cualquier persona en internet llega a las páginas públicas (inicio de sesión y registro).
- **Casos de abuso:**
  - Como atacante en internet o en la red local, intento conectarme a la base de datos, a su herramienta de administración o a otro servicio del servidor → no hay ninguna vía abierta (CA3).
  - Como atacante, provoco errores para ver detalles internos → solo veo un mensaje genérico (CA4).
  - Como atacante, pruebo las contraseñas del volcado publicadas en el repositorio → ninguna funciona (CA5).
  - Como atacante, pruebo contraseñas sin límite, creo cuentas en masa o abuso del acceso con Google → se rechaza temporalmente tras 5 intentos por minuto: los fallidos en el inicio de sesión y todos en el alta y en Google (CA8).

## Cobertura de riesgos
| Corrección | Alcance | Criterios / motivo y destino |
|---|---|---|
| RD1.a — copia de seguridad de la base antes de cada despliegue y restauración probada | dentro | CA6 (la restauración se ensaya en producción o en local, porque no hay staging) |
| RD1.b — procedimiento documentado y ensayado de vuelta a la versión anterior | dentro | CA7 |
| RD1.c — toda migración nueva con su vuelta atrás probada en staging | fuera | No hay staging (decisión del usuario, 2026-10-03) → roadmap, objetivo 5 |
| RD3.a — elegir la plataforma y registrarla en un ADR | dentro | CA1 |
| RD3.b — configuración de producción, sin depuración y con secretos | dentro | CA1, CA4, CA5 |
| RD3.c — conexión cifrada en staging y producción | dentro | CA1, CA2 (producción; staging no existe) |
| RD4.a — que la configuración del lenguaje del servidor se aplique | dentro | CA10 |
| RD5.a — decidir el futuro de las herramientas del frontend | fuera | → roadmap, objetivo 5 |
| RD6.a — fijar las acciones del CI y comprobar el job de tests | fuera | → roadmap, objetivo 5 |
| RD7.a — alinear la versión declarada del lenguaje | fuera | → roadmap, objetivo 5 (el servidor usa la misma versión que local, CA10) |
| RS4.a — límite de intentos en el inicio de sesión | dentro | CA8 (decisión del usuario, 2026-10-03) |
| RS4.b — límite de intentos en el alta de cuentas | dentro | CA8 |
| RS4.c — límite de intentos en la vuelta del acceso con Google | dentro | CA8 |
| RS6.c — cambiar las contraseñas de las cuentas del volcado antes de desplegar | dentro | CA5 |
| RS6.a — quitar las contraseñas del README | fuera | → objetivo 1 |
| RS6.b — sustituir el volcado por datos generados | fuera | → objetivo 1 |
| RS6.d — purgar del historial de git el volcado y las contraseñas | fuera | → objetivo 1 |
| RS7.a — configuración separada, credenciales como secretos y usuario de base de datos sin máximo privilegio | dentro | CA5 |
| RS7.b — servicios internos sin exponer y herramienta de base de datos solo en local | dentro | CA3 |
| RS7.c — excluir secretos y archivos sobrantes del paquete de la aplicación | dentro | CA10 |
| RS7.d — fijar las versiones de los componentes de infraestructura | dentro | CA10 |
| RS8.a — cabeceras de seguridad | fuera | → spec 031 |
| RS8.b — HSTS detrás de la conexión cifrada | fuera | → spec 031 |
| RS15.a — cifrar los campos clínicos en la base | fuera | → objetivo 4 (spec 013) |
| RS15.b — cifrado del disco y de las copias de seguridad | fuera | → spec 031 |
| OB4.a — redactar los datos de las consultas en los errores registrados | fuera | → objetivo 3 |
| OB4.b — modo de producción y depuración apagada verificados en la lista de despliegue | dentro | CA4 |
| OB6.a — comprobación de salud que incluya la base de datos | fuera | → objetivo 3 |
| OB6.b — alertas mínimas | fuera | → spec 031 |
| OB7.a — conservar los registros del servidor web | dentro | CA9 |

## Auditoría
No aplica en esta spec. El evento obligatorio "login fallido" (`docs/observability.md`) necesita el
registro de auditoría, que aún no existe (spec 020). Cuando exista, los rechazos por exceso de
intentos se registrarán como login fallido; esta spec no lo crea.

## Requisitos no funcionales
- Desplegar una versión nueva siguiendo el procedimiento lleva 15 minutos o menos, sin contar la verificación posterior.
- Volver a la versión anterior lleva 15 minutos o menos (CA7).
- Tras un reinicio, la aplicación vuelve sola en 5 minutos o menos (CA9).
- Ningún despliegue a producción sin aprobación explícita del responsable (constitución, regla de despliegue).

## Preguntas abiertas

## Decisiones
| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-10-03 | brecha | El objetivo 5 tiene 21 correcciones, demasiadas para una spec | Dividir: 030 (publicar con seguridad: servidor, configuración, túnel, credenciales, copias y vuelta atrás, más RS4) y 031 (cabeceras y HSTS, alertas, cifrado); el resto, al roadmap | usuario |
| 2026-10-03 | brecha | Con un solo servidor, ¿qué entorno es? | Producción; staging queda pendiente en el roadmap | usuario |
| 2026-10-03 | brecha | ¿Quién puede llegar a la aplicación desde internet? | Todo internet, sin control de acceso previo | usuario |
| 2026-10-03 | brecha | ¿Hay un dominio gestionado en el proveedor del túnel? | Sí, el usuario ya tiene uno; el subdominio se fija en el plan | usuario |
| 2026-10-03 | brecha | Abierta a internet sin límite de intentos en el inicio de sesión (RS4, Alta) | RS4 entra en la 030 | usuario |
| 2026-10-03 | implícita | ¿Puede desplegarse sin aprobación? | No: aprobación humana explícita del responsable | `docs/deployment.md` (regla innegociable), `.ai/project.yaml` (`prod_approval: human`) |
| 2026-10-03 | implícita | Modo de depuración y nivel de registros fuera de local | Depuración apagada y nivel `info` | `docs/constitution.md` P8 |
| 2026-10-03 | implícita | ¿Los datos de producción son reales? | No, ficticios (proyecto académico) | `docs/deployment.md` (Entornos) |
| 2026-10-03 | implícita | El registro de auditoría de los logins fallidos | Depende de la spec 020 | `docs/observability.md` (Registro de auditoría) |
| 2026-10-03 | supuesto | Umbral del límite de intentos | Más de 5 intentos en 1 minuto, por correo y dirección (inicio de sesión) o por dirección (alta y Google) | /specify |
| 2026-10-03 | brecha | ¿Qué intentos cuentan en el alta y en la vuelta de Google? Contar solo los fallidos no frena las altas masivas que salen bien (RS4.b) | En el inicio de sesión, los fallidos; en el alta y en Google, todos | usuario (/plan 030) |
| 2026-10-03 | supuesto | ¿Desde dónde se administra el servidor? | Solo desde la red local, por acceso remoto con clave y sin contraseña | /specify |
| 2026-10-03 | supuesto | Tiempos de despliegue, vuelta atrás y reinicio | 15, 15 y 5 minutos | /specify (ventana de 15 minutos de `docs/deployment.md`) |

## Notas para /plan
- **Servidor:** Ubuntu Server 24.04 LTS recién instalado, con SSH ya instalado. El usuario empieza desde cero: falta Docker, el cortafuegos, el acceso SSH solo con clave, las actualizaciones automáticas de seguridad y el usuario de despliegue.
- **Túnel:** Cloudflare Tunnel (`cloudflared`) hacia nginx por la red interna de Docker, sin abrir puertos en el router. El token del túnel es un secreto. El usuario ya tiene un dominio en Cloudflare.
- **Proxy:** detrás del túnel, Laravel necesita `trustProxies` para generar `https` y que las cookies seguras funcionen (CA2), con `SESSION_SECURE_COOKIE=true` y `APP_URL` en https. `GOOGLE_REDIRECT_URI` cambia, y hay que registrarlo en Google Cloud Console.
- **Compose de producción:** separado del de local; sin `ports` en `db`; sin phpMyAdmin; imágenes fijadas por versión (RS7.d); usuario de MariaDB propio (RS7.a); montaje de `local.ini` corregido (RD4.a: hoy apunta a `docker/php/local.ini`, que en un clon nuevo no existe, y el archivo real está en `docker/nginx/php/local.ini`); `.dockerignore` ampliado (RS7.c); logs de nginx a stdout o a un volumen (OB7.a); `restart: unless-stopped` (CA9).
- **Base de datos:** el esquema solo sale del volcado `database/dentissa.sql` más las migraciones (esquema no reproducible, objetivo 1). En producción, cambiar las contraseñas de las cuentas del volcado (RS6.c) antes de abrir el túnel.
- **Copias:** `mysqldump` (o `mariadb-dump`) fechado, guardado fuera del contenedor, y ensayo de restauración.
- **Vuelta atrás:** volver al tag anterior y restaurar la copia si hubo migración.
- **Límite de intentos (RS4):** `RateLimiter`/`throttle` en `POST /login`, `POST /registro`, `/registro/contacto` y `/auth/google/callback`.
- **Cómo trabaja el agente en el servidor:** decidirlo en el plan. O bien scripts y procedimiento que el usuario ejecuta por SSH, o bien acceso SSH con clave desde este equipo (el agente nunca lee claves ni `.env`).
- **Documentación:** ADR de la plataforma (servidor propio con Cloudflare Tunnel) y `docs/deployment.md` (entornos, cómo desplegar, copias, vuelta atrás, verificación posterior y checklist).
- Guía previa dada al usuario en esta sesión (2026-10-03): Compose de producción con `cloudflared`, `trustProxies`, `.env` de producción y backups.

## Historial
| Fecha | Cambio | Motivo |
|---|---|---|
| 2026-10-03 | Creación | /specify: despliegue en un servidor propio con Cloudflare Tunnel (objetivo 5) |
| 2026-10-03 | Aprobada | Decisión del usuario |
| 2026-10-03 | CA8: en el alta y en Google cuentan todos los intentos, no solo los fallidos. Vuelve a `draft` | /plan 030, decisión del usuario |
| 2026-10-03 | Aprobada de nuevo | Decisión del usuario |
