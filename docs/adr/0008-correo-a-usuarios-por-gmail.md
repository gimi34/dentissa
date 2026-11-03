---
id: 0008
status: accepted
date: 2026-10-27
---

# ADR 0008 · Correos a las cuentas por el SMTP de Gmail, enviados después de responder

## Contexto
La spec 041 (recuperar contraseña) es la primera que envía correos a las cuentas: el enlace de
restablecimiento y el aviso de contraseña cambiada (CA2, CA7). Producción solo envía hoy avisos
internos por el SMTP de Gmail (`smtp.gmail.com:465`, una cuenta solo para enviar con contraseña de
aplicación; spec 032, ADR 0007), y `docs/security.md` dice que esos correos no llevan datos
personales ni secretos (TM7 y TM13 de la 032). El enlace de restablecimiento equivale a una
credencial mientras está vigente.

Además, la respuesta al pedir el enlace tiene que tardar lo mismo con cuenta y sin ella (CA8: menos
de 300 ms de diferencia media), y no hay colas en producción (`QUEUE_CONNECTION=database` sin
trabajador). Proyecto académico, sin presupuesto.

## Decisión
- **Proveedor:** el mismo SMTP de Gmail de la 032 (decisión del usuario, 2026-10-27). El remitente
  es esa cuenta (`MAIL_FROM_ADDRESS`) con el nombre «Dentissa»; Gmail no permite firmar como
  `no-responder@dentissa.app` sin un alias verificado.
- **Cuándo se envía:** después de enviar la respuesta. El controlador responde siempre igual y deja
  la búsqueda de la cuenta, el token y el envío en un trabajo `afterResponse()`, que Laravel ejecuta
  al terminar la petición. Con PHP-FPM, `Response::send()` cierra la conexión con
  `fastcgi_finish_request()` antes, así que el cliente no espera el SMTP ni el hash del token.
- **Qué llevan:** solo el enlace (o el aviso), la hora local de caducidad y el contacto de la
  clínica; nunca la contraseña ni datos de salud. Los secretos de la cuenta de Gmail siguen en
  `app.env` (P5).
- `docs/security.md` cambia la frase de TM13: la cuenta envía también correos a las cuentas, con un
  enlace de un solo uso que caduca en 60 minutos.

## Alternativas consideradas
- Proveedor transaccional con dominio propio (`no-responder@dentissa.app`) — servicio externo nuevo,
  DNS (SPF, DKIM) y otra cuenta que custodiar; el usuario prefirió no añadirlo en el MVP.
- Cola de base de datos con un trabajador en producción — un contenedor o proceso más que vigilar y
  desplegar solo para dos correos; `afterResponse()` basta mientras el volumen sea bajo.
- Enviar dentro de la petición — la diferencia de tiempo delataría qué correos tienen cuenta (CA8).

## Consecuencias
- Sin coste ni servicio nuevo; el límite de Gmail (unos 500 correos al día) sobra para la clínica.
- Un fallo del SMTP no se ve en la pantalla (la respuesta ya se envió): se escribe
  `restablecimiento.correo_fallido` (nivel `error`, solo la clase del error) y la persona puede
  pedir otro enlace al minuto.
- Si el SMTP tarda, el proceso de PHP-FPM queda ocupado hasta terminar: con el volumen esperado no
  importa; si creciera, se pasa a una cola con trabajador.
- El remitente es una dirección de Gmail, no del dominio: algunos filtros pueden mandarlo a spam;
  el texto de «Revisa tu correo» ya pide revisar esa carpeta.
