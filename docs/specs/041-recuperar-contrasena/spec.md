---
id: 041
slug: recuperar-contrasena
status: approved
created: 2026-10-27
extends: [001]
---

# 041 · Recuperar la contraseña desde el inicio de sesión

## Problema
Quien olvida su contraseña no tiene forma de recuperar el acceso por su cuenta: el enlace
«¿Olvidaste tu contraseña?» del inicio de sesión no lleva a ninguna parte (spec 001, fuera de
alcance) y `docs/security.md` lo registra como hueco («No hay recuperación de contraseña»). Afecta a
pacientes y a todo el personal, que dependen de que la administración les cambie la contraseña a
mano, y a los pacientes que entraron con Google, que ni siquiera tienen una. Sin resolverlo, cada
olvido es una cita que no se agenda o un turno de trabajo que empieza sin acceso.

## Historias de usuario
- Como paciente o miembro del personal con cuenta activa que olvidó su contraseña, quiero pedir desde el inicio de sesión un enlace a mi correo para recuperar el acceso sin depender de la clínica.
- Como esa misma persona, quiero fijar una contraseña nueva desde el enlace para volver a entrar.
- Como paciente cuya cuenta se creó con Google, quiero crear una contraseña desde el mismo enlace para poder entrar también con mi correo.
- Como titular de una cuenta, quiero que al restablecer mi contraseña se cierren las sesiones abiertas y se me avise para que quien conociera la anterior pierda el acceso y yo me entere si no fui yo.

## Criterios de aceptación
- [ ] CA1 · Dado un visitante en el inicio de sesión, cuando pulsa «¿Olvidaste tu contraseña?», entonces llega a una pantalla donde escribe su correo para pedir el enlace, con una forma visible de volver al inicio de sesión.
- [ ] CA2 · Dado el correo de una cuenta activa, de cualquier rol, cuando pide el enlace, entonces la pantalla muestra el mensaje de confirmación genérico y en menos de 5 minutos llega a ese buzón (también en producción) un correo en español de Dentissa con un enlace personal de restablecimiento, que indica que caduca en 60 minutos y que puede ignorarse si no lo pidió.
- [ ] CA3 · Dado un enlace vigente, cuando la persona escribe una contraseña nueva de al menos 8 caracteres y la misma confirmación, entonces la contraseña se reemplaza, vuelve al inicio de sesión con un mensaje de éxito, puede entrar con la nueva y ya no con la anterior.
- [ ] CA4 · Dada una contraseña nueva de menos de 8 caracteres, o una confirmación que no coincide, cuando la envía, entonces se rechaza con el error junto al campo, la contraseña no cambia y el enlace sigue sirviendo mientras esté vigente.
- [ ] CA5 · Dado un enlace caducado (más de 60 minutos), ya usado o sustituido por otro pedido después, cuando se abre o se envía, entonces se rechaza con un mensaje que lo explica y ofrece pedir uno nuevo, y la contraseña no cambia.
- [ ] CA6 · Dada una cuenta creada con Google y sin contraseña, cuando pide el enlace y fija una contraseña, entonces puede entrar con su correo y esa contraseña, y sigue pudiendo entrar con Google.
- [ ] CA7 · Dado un restablecimiento completado, cuando termina, entonces se cierran todas las sesiones abiertas de esa cuenta en cualquier equipo y llega a su correo un aviso de que la contraseña cambió, sin enlace de restablecimiento y con la indicación de contactar a la clínica si no fue la persona.
- [ ] CA8 (abuso) · Como atacante, escribo correos para averiguar cuáles tienen cuenta → la pantalla responde con el mismo mensaje, el mismo código de respuesta y un tiempo de respuesta indistinguible (margen en "Requisitos no funcionales") para un correo con cuenta activa, con cuenta desactivada o sin cuenta; solo la cuenta activa recibe correo.
- [ ] CA9 (abuso) · Como atacante, pido muchos enlaces para inundar el buzón de alguien o pruebo correos y enlaces en masa → se limita: como mucho un correo de restablecimiento por cuenta por minuto (el exceso recibe el mismo mensaje genérico, sin correo) y 5 envíos por minuto por dirección de origen tanto al pedir el enlace como al fijar la contraseña; al superar este último, la pantalla pide esperar.
- [ ] CA10 (abuso) · Como atacante, intento adivinar un enlace, alterarlo o usar el enlace de una cuenta para cambiar la contraseña de otra → se rechaza como en CA5; un enlace solo cambia la contraseña de la cuenta a la que se envió.
- [ ] CA11 (abuso) · Como usuario desactivado, pido un enlace o uso uno recibido antes de la desactivación → no recibo correo (con el mensaje genérico de CA8), el enlace no cambia la contraseña y la cuenta sigue sin poder entrar.
- [ ] CA12 · Dado un restablecimiento completado, cuando termina, entonces queda registrado en el registro de auditoría de la spec 020: la cuenta como actor y sujeto, la acción «restablecimiento de contraseña», el resultado y la fecha, sin la contraseña ni el enlace.

## Fuera de alcance
- Que quien se registra con Google tenga que crear su contraseña en el alta: se especificó como spec 042 y el usuario la descartó (2026-10-27); esas cuentas siguen naciendo sin contraseña y pueden crearla con esta spec (CA6).
- Verificación del correo al registrarse y doble factor (spec 001).
- Cerrar las demás sesiones al cambiar la contraseña desde el perfil (riesgo pendiente del objetivo 2 en `docs/security.md`); esta spec solo lo hace en el restablecimiento.
- Que la administración envíe el enlace o restablezca la contraseña de otra cuenta, o sustituya con él la contraseña generada en las altas del personal (riesgo pendiente en `docs/security.md`).
- Mensajes del correo o de las pantallas en otros idiomas.
- Entrar automáticamente tras restablecer: se vuelve al inicio de sesión.

## Seguridad y privacidad
- Datos sensibles involucrados: credenciales (la contraseña nueva y el enlace de restablecimiento, que equivale a una credencial mientras está vigente) y datos personales (el correo). No toca datos de salud.
- Quién puede hacer qué: cualquier visitante puede pedir un enlace para cualquier correo; solo quien abre el enlace recibido en el buzón de una cuenta activa puede fijar la contraseña de esa cuenta. Aplica por igual a cliente, asistente, dentista y admin.
- Casos de abuso:
  - Como atacante, uso la pantalla para descubrir qué correos tienen cuenta → misma respuesta para todos (CA8).
  - Como atacante, inundo el buzón de alguien o pruebo en masa → se limita por cuenta y por origen (CA9).
  - Como atacante, adivino o altero un enlace, o lo reutilizo para otra cuenta → se rechaza (CA10, CA5).
  - Como usuario desactivado, intento recuperar el acceso por esta vía → se rechaza (CA11).
  - Como alguien que leyó la contraseña anterior, sigo con la sesión abierta → se cierra al restablecer (CA7).
  - Como alguien con acceso a los registros técnicos, busco enlaces, correos o contraseñas → no aparecen (requisitos no funcionales, P8).

## Auditoría
- Restablecimiento completado → registra actor, sujeto, acción, resultado y fecha en el registro de la spec 020 (CA12; `docs/observability.md`, «cambio de contraseña»). Esta spec depende de la 020 y no se libera antes que ella (decisión del usuario, 2026-10-27).
- Las solicitudes de enlace y los intentos con enlaces inválidos no son eventos obligatorios de `docs/observability.md` y no se registran en esta spec.

## Diseño
Quien llega aquí acaba de fallar al entrar y puede estar con prisa o preocupado: las pantallas deben
transmitir calma y que el problema tiene arreglo fácil, con un solo paso visible a la vez y lenguaje
llano, sin tono de alarma. Deben sentirse continuación del inicio de sesión, no una sección aparte.

Qué debe diseñarse (`/design-spec 041`):
- Pantalla para pedir el enlace (correo, enviar, volver al inicio de sesión) y su estado de «revisa tu correo».
- Pantalla para fijar la contraseña nueva (contraseña y confirmación) con sus errores junto al campo.
- Estados: enlace caducado o inválido (con «pedir uno nuevo»), demasiados intentos, y el mensaje de éxito en el inicio de sesión.
- Los dos correos: el del enlace y el aviso de contraseña cambiada, legibles en el móvil.

Imágenes: no hay imágenes propias; se reutiliza la identidad visual del inicio de sesión.
Sistema: [docs/design/system.md](../../design/system.md).
Diseño por criterios: [design.md](design.md) (`approved`; `c7` aceptado con 88/100 tras siete candidatos).

## Criterios de diseño
Modo: operar · umbral 80 · mínimo por punto 3 · presupuesto 5 candidatos (decisión del usuario, 2026-10-27).
Cada pantalla y cada correo se mide como una página a 320, 390 y 1440 px (los correos, a 320 y 390);
la lista completa está en [design/criterios.json](design/criterios.json).

| ID | Qué se mide | Objetivo | Sale de |
|---|---|---|---|
| K1 | Pedir el enlace | «Enviar enlace» y «Volver a iniciar sesión» en la primera pantalla, a todos los anchos | CA1 |
| K2 | Revisa tu correo | «Revisa tu correo» y el mensaje genérico en la primera pantalla | CA2, CA8 |
| K3 | Correo con el enlace | «Elegir contraseña nueva», «60 minutos» y «Si no lo pediste, ignora este correo» presentes | CA2 |
| K4 | Contraseña nueva | Los dos campos y «Guardar contraseña» en la primera pantalla; «Al menos 8 caracteres» y el error junto al campo presentes (K4b) | CA3, CA4 |
| K5 | Enlace caducado | «Pedir un enlace nuevo» en la primera pantalla; «Tu contraseña no cambió» presente (K5b) | CA5 |
| K6 | Demasiados intentos | «Espera un minuto» en la primera pantalla a 320 px | CA9 |
| K7 | Éxito en el inicio de sesión | «Tu contraseña cambió» e «Iniciar sesión» en la primera pantalla | CA3 |
| K8 | Aviso de contraseña cambiada | «¿No fuiste tú?» y el teléfono de la clínica presentes | CA7 |
| K9 | JavaScript por página | ≤ 100 kB | constitución P10 |
| K10 | Estabilidad visual | CLS ≤ 0.1 | modo operar |
| K11 | Titulares | ≤ 2 líneas | modo operar |
| K12 | Letra del acceso | Roboto carga de verdad (no la de reemplazo) | constitución P9 |

CA6, CA10–CA12 y el tiempo de respuesta de CA8 no tienen nada que medir en la pantalla: los
verifican los tests de sus tareas.

Puertas: las de `design-spec/references/criterios.md` §2, sin cambios. Rúbrica: jerarquía 25,
primera pantalla 25, sistema 25, lectura 15, plantilla 10 (ver "Decisiones").

## Requisitos no funcionales
- Accesibilidad: WCAG 2.1 AA en las pantallas nuevas (P9), a 320, 390 y 1440 px de ancho; los errores y la confirmación se anuncian con texto, no solo con color.
- Indistinguibilidad: en 20 solicitudes alternas con un correo con cuenta activa y otro sin cuenta, la diferencia entre sus tiempos medios de respuesta es menor de 300 ms (CA8).
- Entrega: el correo llega en menos de 5 minutos en producción (CA2).
- Registros técnicos (P8): ningún log contiene el correo escrito, el enlace ni la contraseña.
- Secretos (P5): las credenciales del servicio de correo no están en el repositorio.
- Descubribilidad (P10, `docs/seo.md`): las pantallas nuevas no deben aparecer en los buscadores ni en el mapa del sitio, como el inicio de sesión; la dirección del enlace no se envía a sitios externos.

## Preguntas abiertas

## Decisiones
| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-10-27 | brecha | ¿El enlace debe llegar a un buzón real en producción? Hoy `MAIL_*` no tiene uso real (`docs/deployment.md`) | Sí, correo real en producción | usuario |
| 2026-10-27 | contradicción | P7 exige auditar los cambios de contraseña, pero el registro de auditoría (spec 020) no está implementado | Se respeta P7: la 041 registra el restablecimiento en el registro de la 020 y no se libera antes que ella; sin excepción a P7 | usuario |
| 2026-10-27 | brecha | ¿Qué cuentas pueden recuperar la contraseña? | Todas las activas, de cualquier rol | usuario |
| 2026-10-27 | brecha | ¿Qué pasa con las cuentas creadas con Google, que no tienen contraseña? | Pueden crearla con el enlace (CA6). Exigirla en el alta se especificó aparte como spec 042 y se descartó | usuario |
| 2026-10-27 | supuesto | Las cuentas de Google ya existentes sin contraseña, ¿pueden crearla con el enlace? | Sí (CA6) | /specify |
| 2026-10-27 | implícita | Política de la contraseña nueva | Al menos 8 caracteres, como el registro y el perfil | `docs/specs/002-registro-pacientes/spec.md` CA6 |
| 2026-10-27 | implícita | Las cuentas desactivadas | No pueden recuperar el acceso, igual que no pueden entrar (CA11) | `docs/specs/001-autenticacion/spec.md` CA2 |
| 2026-10-27 | implícita | Límite por origen | 5 por minuto por dirección de origen, como el inicio de sesión y el registro (CA9) | `docs/security.md` → Superficie de ataque (spec 030) |
| 2026-10-27 | implícita | ¿Se indexan las pantallas nuevas? | No, como el inicio de sesión | `docs/seo.md` → Reglas del sitio |
| 2026-10-27 | implícita | Respuesta que no revele si el correo tiene cuenta | Mensaje genérico (CA8), como pide `docs/security.md` para el registro | `docs/security.md` → Enumeración de cuentas en el registro |
| 2026-10-27 | supuesto | Vigencia y uso del enlace | 60 minutos, un solo uso, y solo vale el último pedido (CA5) | /specify |
| 2026-10-27 | supuesto | Límite por cuenta | Un correo de restablecimiento por cuenta por minuto (CA9) | /specify |
| 2026-10-27 | supuesto | ¿Qué pasa tras restablecer? | Se cierran todas las sesiones de la cuenta, llega un aviso al correo y se vuelve al inicio de sesión sin entrar automáticamente (CA3, CA7) | /specify, ASVS nivel 2 (`docs/security.md`) |
| 2026-10-27 | supuesto | Plazo de entrega del correo y margen de tiempo indistinguible | Menos de 5 minutos; diferencia media menor de 300 ms | /specify |
| 2026-10-27 | supuesto | ¿Se auditan las solicitudes de enlace? | No: no son eventos obligatorios de `docs/observability.md` | /specify |
| 2026-10-27 | diseño | Criterios de diseño y pesos de la rúbrica | Modo operar, K1–K11, umbral 80, mínimo 3, presupuesto 5; `sistema` 25 y `plantilla` 10 porque las pantallas deben sentirse continuación del inicio de sesión, no una sección aparte | usuario |
| 2026-10-27 | diseño | Resultado del ciclo de /design-spec | Presupuesto agotado sin candidato sobre el umbral; mejor `c4` (78/100), acta en [design.md](design.md) | /design-spec |
| 2026-10-27 | diseño | ¿Qué hacer con el presupuesto agotado? | Dos candidatos más desde `c4` (presupuesto 7) | usuario |
| 2026-10-27 | diseño | Resultado del ciclo ampliado | `c7` aceptado con 88/100; acta en [design.md](design.md) | /design-spec |

## Notas para /plan
- El enlace del login es `href="#"` en `resources/views/auth/login.blade.php:129`; la tabla `password_reset_tokens` existe sin rutas (`docs/specs/README.md`).
- La contraseña vive en `usuarios.password_hash` (`getAuthPassword()`, spec 001); las cuentas de Google la tienen nula.
- Correo real en producción: elegir un proveedor gratuito; hoy `MAIL_MAILER=log` y `MAIL_*` no tiene uso real (`docs/deployment.md`); los secretos van en `app.env` del servidor.
- Según la spec 020, los eventos de altas, roles y contraseñas son de la 021; la 041 registra el restablecimiento en el registro de la 020 y la 021 conserva el resto.
- Las sesiones usan el driver `file` (`docs/security.md`): tenerlo en cuenta para cerrar las de otros equipos (CA7).
- La indistinguibilidad (CA8) probablemente exige no enviar el correo dentro de la petición, y no hay colas hoy (`docs/security.md`: «No hay … colas»).
- El acceso tiene su propio HTML, fuera de los dos layouts (`AGENTS.md`).

## Historial
| Fecha | Cambio | Motivo |
|---|---|---|
| 2026-10-27 | Spec creada en draft | /specify |
| 2026-10-27 | Aprobada; se quitan las menciones a la 042, descartada | usuario |
| 2026-10-27 | Sección "Criterios de diseño" (AI-DD 1.19.0) | /design-spec 041 |
| 2026-10-27 | Diseño aprobado (`design.md`, `c7`) | usuario |
