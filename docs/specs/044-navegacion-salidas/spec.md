---
id: 044
slug: navegacion-salidas
status: approved
created: 2026-11-08
extends: [001, 002, 012, 038, 041]
---

# 044 · Salidas de las pantallas: volver al inicio desde el acceso, el panel y los errores

## Problema
Quien entra en el inicio de sesión, el registro o la recuperación de contraseña no puede volver a la
página de inicio: el logo no es un enlace y ninguna de esas pantallas lo ofrece. La pantalla de
«contraseña nueva» no tiene ninguna salida. Desde el panel de cualquier rol tampoco se llega a la
página pública. Al cerrar sesión se termina en el inicio de sesión, y los errores (dirección que no
existe, acceso denegado, formulario caducado, demasiados intentos, fallo del servidor) muestran una
página genérica en inglés sin enlaces. Así, el visitante que solo quería mirar se queda atrapado y
tiene que usar el botón Atrás o escribir la dirección. La spec atiende la brecha SE4 de
`docs/seo.md` y, **parcialmente**, la SE2 (su SE2.b; la SE2.a ya estaba mitigada).

## Historias de usuario
- Como visitante en una pantalla de acceso (inicio de sesión, registro o recuperación de contraseña), quiero volver a la página de inicio para seguir viendo la clínica sin entrar.
- Como paciente o miembro del personal con sesión, quiero ir a la página pública desde mi panel sin cerrar la sesión, para ver los servicios o la ubicación y volver después.
- Como usuario que cierra sesión, quiero terminar en la página de inicio con la confirmación de que salí, para saber que nadie más puede usar mi cuenta.
- Como cualquiera que llega a una página de error, quiero entender qué pasó y tener a dónde ir, sin quedarme en un callejón sin salida.

## Criterios de aceptación
- [ ] CA1 · Dado un visitante en cualquiera de las pantallas de acceso, cuando la abre a 320, 390 o 1440 px de ancho, entonces ve sin desplazarse, como primer enlace del formulario, la salida de esa pantalla: «Volver al inicio», que lleva a la página de inicio, en el inicio de sesión, el registro y completar el registro con Google; y «Volver a iniciar sesión», que lleva al inicio de sesión, en las cuatro de recuperación de contraseña (pedir el enlace, enlace enviado, enlace caducado y contraseña nueva), sin repetirlo más abajo.
- [ ] CA2 · Dado un visitante en esas pantallas, cuando el logo de la clínica está visible, entonces es un enlace a la página de inicio cuyo nombre para lectores de pantalla es «Ir a la página de inicio».
- [ ] CA3 · Dado un visitante en «Contraseña nueva», cuando decide no seguir, entonces tiene «Volver a iniciar sesión»; salir no cambia la contraseña ni gasta el enlace del correo, que sigue valiendo hasta que caduque.
- [ ] CA4 · Dado alguien que está completando el registro con Google (pantalla de contacto), cuando pulsa «Volver al inicio», entonces llega a la página de inicio sin que se cree ninguna cuenta; si después vuelve a registrarse con Google, empieza de nuevo sin datos a medias.
- [ ] CA5 · Dado un usuario con sesión de cualquier rol (administración, dentista, asistente o paciente), cuando abre la navegación de su panel (barra lateral en escritorio, menú en el móvil), entonces encuentra «Página de inicio», que lleva a la página de inicio sin cerrar la sesión; desde allí, «Ir a mi panel» lo devuelve a su panel. Con sesión, la página de inicio se muestra con «Ir a mi panel» en la cabecera en lugar de llevar al panel (reemplaza 038 CA2 y 001 CA5); al iniciar sesión se sigue entrando directo al panel de cada rol.
- [ ] CA6 · Dado un usuario con sesión, cuando cierra sesión, entonces llega a la página de inicio con el aviso «Has cerrado sesión», marcado como mensaje de estado para que los lectores de pantalla lo lean al llegar a la página; la cabecera ofrece «Iniciar sesión» y «Crear cuenta», y el botón Atrás del navegador no vuelve a mostrar datos del panel.
- [ ] CA7 · Dado cualquiera, con o sin sesión, cuando abre una dirección que no existe, entonces recibe la respuesta «no encontrada» con una página en español con el estilo de la clínica, «No encontramos esta página», un enlace a la página de inicio y, sin sesión, «Iniciar sesión» y «Crear cuenta», o, con sesión, «Ir a mi panel»; la página pide a los buscadores que no la indexen.
- [ ] CA8 · Dado un usuario con sesión, cuando abre una página que su rol no puede usar, entonces recibe la respuesta «acceso denegado» con una página en español, «No tienes acceso a esta página», con la explicación concreta cuando la hay (p. ej. «Como dentista no puedes reprogramar citas…», como hoy) y enlaces a su panel y a la página de inicio; el acceso denegado sigue quedando en el registro de auditoría igual que hoy.
- [ ] CA9 · Dado un visitante que deja abierto un formulario hasta que caduca, cuando lo envía, entonces ve una página en español, «La página caducó», que le pide volver a intentarlo, con «Volver a iniciar sesión» e «Ir a la página de inicio».
- [ ] CA10 · Dado alguien que supera un límite de intentos que responde con la página de error (hoy ningún límite de la aplicación lo hace: es una página preventiva; los límites actuales siguen respondiendo como hoy) o una petición que falla en el servidor, cuando llega a la página de error, entonces ve un mensaje en español («Demasiados intentos, espera unos minutos y vuelve a probar» o «Algo salió mal, vuelve a intentarlo más tarde») con un enlace a la página de inicio, con la misma respuesta de error que hoy.
- [ ] CA11 · (abuso) Dado un atacante que añade a la dirección un destino de vuelta externo o pide páginas inexistentes o prohibidas, cuando usa los enlaces de vuelta o ve las páginas de error, entonces los enlaces llevan siempre a páginas fijas del propio sitio, y las páginas de error no muestran rutas internas, trazas, consultas, versiones ni si el recurso existe para otro rol: lo que hoy responde «no encontrada» sigue respondiendo «no encontrada».
- [ ] CA12 · Dado un buscador que pide la comprobación de salud del sistema, cuando recibe la respuesta, entonces esta le indica que no la indexe, y la comprobación sigue respondiendo bien.

## Fuera de alcance
- Las páginas de términos de uso y aviso de privacidad, y sus enlaces del registro (spec 045).
- Migas y botones «Volver» de las pantallas internas (citas, pacientes, expedientes, personal, servicios, usuarios, perfil): spec 046, con una auditoría previa de esas pantallas.
- Cambiar qué puede ver cada rol: los permisos y las respuestas de acceso denegado o no encontrada siguen como hoy.
- Recordar de qué página venía alguien para devolverlo allí: los enlaces de vuelta tienen destinos fijos.
- Cambiar el diseño de la página de inicio o de las pantallas de acceso más allá de los enlaces nuevos y el aviso de sesión cerrada.
- El `sitemap.xml` y la línea `Sitemap:` de `robots.txt` (SE3).

## Seguridad y privacidad
- Datos sensibles involucrados: ninguno nuevo. Las páginas de error no muestran datos de pacientes, cuentas ni del sistema; la pantalla «Contraseña nueva» lleva un enlace secreto que salir de ella no debe exponer a terceros.
- Quién puede hacer qué: cualquiera ve las pantallas de acceso, la página de inicio y las de error; «Página de inicio» del panel y «Ir a mi panel» solo con sesión. Ningún permiso cambia.
- Casos de abuso:
  - Como atacante, pongo en la dirección un destino de vuelta hacia otro sitio para usar la clínica como trampolín de phishing → los enlaces de vuelta tienen destinos fijos del propio sitio (CA11).
  - Como atacante, pido direcciones inexistentes o prohibidas para descubrir rutas, versiones o qué recursos existen → la página de error no lo revela y la respuesta no cambia respecto de hoy (CA11).
  - Como alguien con acceso a un equipo compartido, uso Atrás después de que otra persona cerró sesión → no se muestran datos del panel (CA6).

## Cobertura de riesgos
| Corrección | Alcance | Criterios / motivo y destino |
|---|---|---|
| SE2.a — `noindex` en `/login` (y en `/registro/contacto`) | fuera | ya mitigada en v0.14.0 (spec 041); las pantallas de acceso nuevas o modificadas la conservan |
| SE2.b — `noindex` en la 404 propia y `X-Robots-Tag: noindex` en `/up` | dentro | CA7, CA12 |
| SE4.a — página 404 propia, en español, con enlace al inicio de sesión y al registro y `noindex` | dentro | CA7 |
| SE3.a — `sitemap.xml` con las rutas indexables | fuera | no es navegación entre pantallas → roadmap objetivo 7 |
| SE3.b — `Sitemap:` en `robots.txt` | fuera | va con el `sitemap.xml` → roadmap objetivo 7 |

## Auditoría
- Acceso denegado a una página del panel por rol → sigue registrando quién, qué ruta, sobre qué recurso y con qué resultado, como hoy (spec 020); la página nueva no lo cambia (CA8).
- Cerrar sesión: hoy no se registra (spec 001; pendiente en `docs/observability.md` como «listeners de eventos de autenticación», objetivo 3). La 044 cambia su destino y no añade el evento: **excepción a P7** aprobada por el usuario el 2026-11-08, como la de la spec 030; el evento llega con esa corrección, junto con los de inicio de sesión (Decisiones).

## Diseño
Quien se equivoca de pantalla o cambia de idea tiene que ver la salida sin buscarla, con el mismo
tono de la clínica y sin competir con la acción principal de cada pantalla (entrar, registrarse,
pedir el enlace). Sistema: [docs/design/system.md](../../design/system.md).

Pantallas que toca: las siete de acceso (inicio de sesión, registro, completar registro con Google,
pedir enlace, enlace enviado, enlace caducado, contraseña nueva), la navegación del panel de los
cuatro roles en escritorio y móvil, la página de inicio (aviso de sesión cerrada) y cinco páginas de
error nuevas (no encontrada, acceso denegado, página caducada, demasiados intentos, fallo del
servidor). Recursos de marca: el logo de la clínica y el estilo de las pantallas de acceso y de la
página de inicio. Qué debe diseñarse: la posición del «Volver al inicio» a todos los anchos (en el
móvil el panel del logo puede no verse), la entrada «Página de inicio» del panel, el aviso de sesión
cerrada y una plantilla común para las páginas de error.

Sin ciclo de candidatos de `/design-spec` (decisión del usuario): `/plan` coloca los enlaces con los
componentes que ya existen y el usuario los valida en pantalla durante `/implement`.

## Criterios de diseño
Modo: operar · sin ciclo de candidatos (decisión del usuario) · la verificación es la del usuario en pantalla y los tests de `/implement`.

| ID | Qué se mide | Objetivo | Sale de |
|---|---|---|---|
| K1 | La salida de arriba de las pantallas de acceso («Volver al inicio» o, en la recuperación, «Volver a iniciar sesión») | visible sin desplazarse a 320, 390 y 1440 px; objetivo de toque ≥ 44 px | CA1 |
| K2 | Logo de las pantallas de acceso | enlace con nombre accesible «Ir a la página de inicio» | CA2 |
| K3 | «Página de inicio» en el panel | presente para los cuatro roles en escritorio y en el menú del móvil | CA5 |
| K4 | Aviso «Has cerrado sesión» | visible en la primera pantalla y anunciado a lectores de pantalla | CA6 |
| K5 | Páginas de error | en español, con el estilo de la clínica y al menos un enlace de salida a 320, 390 y 1440 px | CA7–CA10 |

Puertas: las de `design-spec/references/criterios.md` §2 que apliquen (contraste AA, foco visible, toque ≥ 44 px, sin desbordes a 320 px).

## Requisitos no funcionales
- Accesibilidad (WCAG 2.1 AA, constitución P9): enlaces nuevos con foco visible, contraste AA y objetivo de toque ≥ 44 px, a 320, 390 y 1440 px de ancho; el aviso de sesión cerrada se anuncia sin mover el foco; las páginas de error tienen un único encabezado principal.
- Descubribilidad (`docs/seo.md`): la página de inicio sigue indexable y su resultado no cambia por el aviso de sesión cerrada; las páginas de error y la comprobación de salud no se indexan; las pantallas de acceso siguen sin indexarse, salvo el registro, que sigue indexable.
- Las páginas de error no dependen de la base de datos para pintarse: con la base caída, la página de fallo del servidor se ve igual (la plantilla no consulta la base). El camino de una denegación con la base caída es de la spec 020 y queda en el roadmap (Decisiones).

## Preguntas abiertas

## Decisiones
| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-11-08 | brecha | Qué áreas entran en la mejora de navegación | Acceso y registro, panel → página de inicio, páginas de error propias y la navegación interna; repartidas en tres specs | usuario |
| 2026-11-08 | brecha | División (más de 20 criterios juntos) | 044 salidas (esta), 045 términos y privacidad, 046 navegación interna | usuario |
| 2026-11-08 | brecha | Enlaces «términos de uso» y «política de privacidad» que llevan a `#` | Crear páginas sencillas, en la spec 045 | usuario |
| 2026-11-08 | brecha | Destino al cerrar sesión | La página de inicio con el aviso «Has cerrado sesión» (CA6) | usuario |
| 2026-11-08 | diseño | Ciclo de candidatos de `/design-spec` | Diseño directo con los componentes existentes; validación del usuario en pantalla en `/implement` | usuario |
| 2026-11-08 | brecha | Extiende 5 specs (001, 002, 012, 038, 041), por encima de 4 | Se mantiene junta: son cambios pequeños de navegación en cada una, que solo tienen sentido juntos | usuario (división elegida) |
| 2026-11-08 | supuesto | Qué páginas de error se hacen propias | No encontrada, acceso denegado, página caducada, demasiados intentos y fallo del servidor. Demasiados intentos es preventiva: hoy los límites responden con su propio aviso y no cambian (/analyze A2) | razonable |
| 2026-11-08 | supuesto | Destinos de los enlaces de vuelta | Fijos (inicio, inicio de sesión, crear cuenta, mi panel); nunca la página anterior ni un destino de la dirección | razonable (CA11) |
| 2026-11-08 | supuesto | Logo del panel como enlace | No se exige: la salida es la entrada «Página de inicio» del menú | razonable |
| 2026-11-08 | implícita | Las respuestas de acceso denegado y no encontrada no cambian | Se mantienen las de hoy (p. ej. una cita ajena responde «no encontrada») | `docs/security.md`, spec 028 |
| 2026-11-08 | contradicción | 038 CA2 lleva al panel a quien abre la página de inicio con sesión; CA5 pide llegar a ella desde el panel | Con sesión, la página de inicio se muestra con «Ir a mi panel» (reemplaza 038 CA2); el inicio de sesión sigue llevando al panel | usuario (en /plan 044) |
| 2026-11-08 | implícita | Explicación de la página de acceso denegado | Se conserva el mensaje concreto de cada permiso que hoy ve el usuario (p. ej. el del dentista al reprogramar) | `tests/Feature/EstadosCitaRolesTest.php`, `app/Autorizacion/` |
| 2026-11-08 | contradicción | 001 CA5 también lleva al panel a quien abre `/` con sesión (/analyze A4) | 044 CA5 lo reemplaza, igual que a 038 CA2 | usuario (decisión de /plan 044) |
| 2026-11-08 | brecha | P7 exige auditar los logouts y la 044 cambia `logout()` (/analyze A5) | Excepción P7: el evento de logout llega con los listeners de autenticación de `docs/observability.md` (objetivo 3), junto con los de inicio de sesión; la 044 no quita ningún evento | usuario |
| 2026-11-08 | brecha | Página 429 sin ningún camino real hoy (/analyze A2) | Se mantiene como preventiva; CA10 lo dice | usuario |
| 2026-11-08 | brecha | Con la base caída, una denegación podría no llegar a su página por la auditoría de la 020 (/analyze A8) | El requisito se limita a la plantilla; el riesgo de `RegistroCentralDenegaciones` va al roadmap | usuario |
| 2026-11-09 | brecha | En la recuperación de contraseña, ¿la salida de arriba lleva al inicio o al inicio de sesión? (verificación de T020) | Las cuatro pantallas de recuperación llevan arriba «Volver a iniciar sesión» y pierden el de abajo; en el escritorio el logo sigue llevando a la página de inicio | usuario (2026-11-09, /implement 044) |
| 2026-11-08 | implícita | Indexación | Inicio y registro indexables; acceso, errores y salud no | `docs/seo.md`, `.ai/project.yaml → seo` |

## Notas para /plan
- Auditoría del 2026-11-08 sobre `main` (4b95310): `layouts/acceso.blade.php` (logo `<img>` sin enlace en `.panel-left`; en el móvil ese panel puede ocultarse), `auth/register.blade.php` y `auth/registro-contacto.blade.php` (HTML propio), `auth/recuperar/{solicitud,enviada,caducado,nueva}.blade.php` (`nueva` sin enlaces), `layouts/app.blade.php` (barra lateral y menú móvil), `AuthController@logout` → `redirect('/login')`, `RoleMiddleware` → `abort(403)`; no existe `resources/views/errors/` salvo `auditoria-no-disponible.blade.php`, que ya tiene «Volver al inicio».
- Tocar `layouts/app.blade.php` obliga a `node docs/design/capturar.mjs --medir` en la verificación final de `/implement` y en `/review` (AGENTS.md).
- La pantalla «Contraseña nueva» lleva el token en la URL: comprobar que los enlaces nuevos no lo filtran (política de referer de la 041).
- `/up` con `X-Robots-Tag: noindex` (CA12) puede ir en nginx (`docker/nginx/`, ruta protegida) o en Laravel: decidirlo en `/plan`.

## Historial
| Fecha | Cambio | Motivo |
|---|---|---|
| 2026-11-08 | Creada en `draft` | Petición del usuario: «no hay opción de regresar a la landing page cuando te metes al login…» |
| 2026-11-08 | Aprobada | Aprobación del usuario |
| 2026-11-08 | CA5 reemplaza 038 CA2 (inicio con sesión); CA8 conserva la explicación concreta; vuelve a `draft` | Contradicción hallada en /plan 044, resuelta por el usuario |
| 2026-11-08 | Aprobada de nuevo | Aprobación del usuario |
| 2026-11-08 | CA5 reemplaza también 001 CA5; CA6, CA9 y CA10 precisados; excepción P7 del logout; RNF de las páginas de error acotado; vuelve a `draft` | /analyze r1: A2, A4, A5, A8, A9, A11 (decisiones del usuario) |
| 2026-11-08 | Aprobada de nuevo | Aprobación del usuario |
| 2026-11-09 | CA1 y CA3: la recuperación de contraseña sale al inicio de sesión | Decisión del usuario al verificar T020 (/implement 044); aprobada por el usuario |
