---
spec: 041-recuperar-contrasena
status: approved   # draft | approved | blocked
constitution_version: 1.3.0
created: 2026-10-27
---

# Plan · 041 Recuperar contraseña

## Enfoque técnico
El restablecimiento usa el `PasswordBroker` de Laravel sobre la tabla `password_reset_tokens`, que
ya existe. Ese broker ya da el token aleatorio guardado con hash, la caducidad de 60 minutos, un
solo token vigente por cuenta y la espera de 60 s entre envíos. Un controlador de acceso nuevo,
`RecuperarContrasenaController`, sirve cuatro pantallas sobre un layout de acceso común, que
también adopta el inicio de sesión (diseño aprobado, `c7`).

Para que la respuesta sea idéntica con y sin cuenta (CA8), el controlador valida el formato y
responde siempre igual. La búsqueda de la cuenta (solo activas), el token y el correo van en un
trabajo `afterResponse()` (ADR 0008). El enlace lleva el token y el identificador de la cuenta,
nunca el correo, y se construye desde `APP_URL`. nginx enmascara el token en sus logs de acceso,
tanto en la petición como en el Referer.

Las sesiones abiertas se cierran con un número de versión en `usuarios.version_sesion`. Cada
sesión guarda la versión con la que entró; el restablecimiento la aumenta, y un middleware del
grupo `web` cierra en su siguiente petición toda sesión con una versión antigua. Una sesión sin
versión (abierta antes del despliegue) cuenta como versión 0. Funciona también con las cuentas de
Google sin contraseña, a las que el `AuthenticateSession` de Laravel no vigila.

El diseño `c7` se hizo sobre el `system.md` 1.8.0 de la rama de la 038, en pausa; `main` está en
1.6.0. La 041 no espera a la 038: la primera tarea lleva a `main` todos los tokens que usan las
pantallas, también los cinco que vienen de la 038 (D13, D14).

El restablecimiento y su evento de auditoría (spec 020, modo crítico) van en la misma transacción.
La 020 se implementa antes y su contrato gana un actor opcional (decisión del usuario, 2026-10-27).

## Constitution Check
| Principio | Resultado | Justificación / ajuste | Cómo se verifica |
|---|---|---|---|
| P1 Spec aprobada antes de código | ✅ | Spec 041 y `design.md` aprobados el 2026-10-27. `/implement 041` empieza después de liberar la 020 (D1). | manual: `aidd.py status` antes de `/implement` |
| P2 Test que falla antes y pasa después | ✅ | Todo comportamiento nuevo lleva un test Feature que hoy falla: rutas, broker, limitadores, versión de sesión, correos, auditoría y la configuración de nginx. El tiempo de CA8 se mide en un test sobre el kernel HTTP sin la fase de terminación (D6). | test: ver Trazabilidad |
| P3 Autorización en servidor | ✅ | Rutas públicas sin rol: la autorización es tener el token de esa cuenta. El broker lo compara con el hash guardado para la cuenta del enlace; un token de otra cuenta, alterado, caducado o ya usado se rechaza (CA5, CA10). Las cuentas desactivadas no reciben enlace ni pueden usarlo (CA11). Sin cambios en roles ni Gates. | test: `RecuperarContrasenaAbusoTest::test_el_token_de_una_cuenta_no_cambia_la_contrasena_de_otra`, `…_rechaza_un_token_alterado`, `…_una_cuenta_desactivada_no_puede_restablecer` |
| P4 Entrada validada y SQL con bindings | ✅ | `$request->validate()`: correo (`required`, `email:rfc`, `max:255`), token (`required`, `string`, `max:255`), cuenta (`required`, `uuid`), contraseña (`required`, `string`, `min:8`, `max:255`) y confirmación (`required`, `same:password`). Solo Eloquent y el broker, sin SQL crudo. | test: `RecuperarContrasenaTest::test_valida_cada_campo` · lint: semgrep `p/php` |
| P5 Nada sensible en el repo | ✅ | La cuenta de Gmail sigue en `app.env` (spec 032). El interruptor `RECUPERAR_CONTRASENA` no es un secreto. Ningún token ni contraseña de ejemplo real en tests o docs. | lint: gitleaks |
| P6 Sin vulnerabilidades críticas/altas | ✅ | Sin cambios en `composer.json`, `composer.lock` ni los archivos de npm: el broker, `Mail` y `afterResponse()` son de Laravel. Los correos usan directamente `CssToInlineStyles`, una dependencia transitiva que ya está en el lock, declarada y verificada en D9. Roboto se copia como archivo (OFL 1.1), no como dependencia (D10). Línea base del 2026-10-27: `composer audit --locked` y `npm audit --audit-level=high` sin avisos; EX2 vigente para las imágenes. | lint: `composer audit --locked`, `npm audit --audit-level=high` en CI y `/release` |
| P7 Auditoría de datos sensibles | ✅ | El restablecimiento completado registra `contrasena_restablecida` en el almacén solo anexado de la 020: actor y sujeto, la cuenta; resultado `exito`; fecha, IP y `request_id`. Modo crítico, en la misma transacción que el cambio: si no se puede registrar, la contraseña no cambia (CA12, D5). Las solicitudes de enlace y los enlaces inválidos no son eventos obligatorios (spec, "Auditoría"). | test: `RecuperarContrasenaAuditoriaTest::test_el_restablecimiento_queda_registrado`, `…_sin_registro_la_contrasena_no_cambia` |
| P8 Correlación y logs limpios | ❌ aceptado: el `error_log` de nginx escribe la petición y el Referer, con el token, cuando PHP falla (502/504); su formato no se puede configurar. Acotado a ese log y a esos fallos: el token es de un solo uso y caduca en 60 minutos, y el log solo existe en el servidor. Destino: rotación y retención de logs (OB8.b, objetivo 3) — aprobado por el usuario el 2026-10-28 (`/analyze` A28). El resto cumple: `request_id` y `actor_id` llegan por el middleware y el `Context` de la 020, que se libera antes. El único log nuevo, `restablecimiento.correo_fallido`, lleva solo la clase del error. nginx escribe `/contrasena/nueva/***` en lugar del token, en la petición y en el Referer, en local y en producción (D7). Residuos declarados en «Riesgos»: el mailer `log` de local y la `QueryException` de OB4.a (decisión del usuario, 2026-10-28). | test: `RecuperarContrasenaAbusoTest::test_ningun_log_contiene_el_correo_el_token_ni_la_contrasena`, `ConfiguracionProduccionTest::test_nginx_enmascara_el_token_de_restablecimiento`, `…_nginx_enmascara_el_token_en_el_referer` · manual: revisión de cada `Log::` del diff y, tras el despliegue, `dentissa-error.log` (Observabilidad) |
| P9 La interfaz usa solo el sistema de diseño | ✅ | Las pantallas siguen `design.md` (`c7`). La primera tarea lleva a `system.md` y `system.html` de `main` los «Cambios a incorporar al sistema» y los cinco tokens de la 038 que usa `c7` (D13); la versión se fija al fusionar. `acceso.css` usa solo tokens: ningún literal fuera de `:root` y ningún `var(--x)` sin token en `system.md`. El login pierde sus estilos en línea, los dos tonos fuera del sistema y Google Fonts. Los correos copian en su hoja los valores de los tokens, porque los clientes de correo no entienden `var()`; un test exige que cada valor de esa hoja sea el de un token de `system.md` (D9, decisión del usuario, 2026-10-28). Las propiedades revisadas y las exenciones están en D17. WCAG 2.1 AA con `evaluate.mjs` sobre la build. | test: `RecuperarContrasenaVistasTest::test_los_tokens_del_acceso_son_los_de_system_md`, `…_acceso_css_sin_literales_fuera_de_root`, `…_los_valores_de_los_correos_son_tokens`, `…_sin_estilos_en_linea` · manual: `design/medir.mjs` sobre la build (Trazabilidad) |
| P10 Rutas indexables descubribles | ✅ | Las rutas nuevas y `/login` no son indexables: `<meta name="robots" content="noindex">` desde el layout de acceso, y fuera del sitemap, que hoy no existe (`docs/seo.md`). Con eso queda hecha SE2.a (decisión del usuario, 2026-10-28). Toda respuesta servida en la URL del token no carga recursos de otros sitios y lleva `<meta name="referrer" content="no-referrer">` antes de cualquier recurso. | test: `RecuperarContrasenaVistasTest::test_las_pantallas_nuevas_no_se_indexan`, `…_el_login_no_se_indexa`, `…_la_pagina_del_token_no_envia_referer` |

## Cambios por módulo
| Módulo | Cambio | Riesgo |
|---|---|---|
| `docs/design/system.md`, `system.html` | Primera tarea: los cambios de `design.md` (tokens `--font-acceso`, `--text-acceso-*`, `--acceso-form-max`, `--duration-respuesta`, `--ease-clinico`; carnet, troquel, talón, bloque «Vale hasta», botón en espera, casilla de 44 px, panel con degradado del sistema, marco del acceso) y los cinco tokens de la 038 que usa `c7`, con el mismo nombre y valor (D13). La versión es la siguiente libre al fusionar (D14). | Medio: conflicto con la 038 (D14) |
| `public/css/acceso.css` (nuevo) | Tokens del sistema más los del acceso en `:root`, como `auth.css`, y los componentes del carnet. El login deja `auth.css`, que sigue sirviendo al registro (`register`, `registro-contacto`); su sección `.page-login` se borra. | Medio: el login cambia de aspecto |
| `public/fonts/roboto/` (nuevo) | `roboto-latin-wght-normal.woff2` (variable, de `@fontsource-variable/roboto` 5.2.5) con su `OFL.txt` (D10) | Bajo |
| `resources/views/layouts/acceso.blade.php` (nuevo) | Carcasa común: franja con el logo en el teléfono, panel de marca en el escritorio (con su texto por pantalla) y tarjeta. `noindex` en todas sus pantallas, también en el login (SE2.a). Una sección de cabecera para que las vistas servidas en la URL del token pongan `no-referrer` antes de cualquier recurso. Roboto propia, sin JavaScript. | Bajo |
| `resources/views/auth/login.blade.php` | Pasa al layout de acceso, con `noindex` (SE2.a, decisión del usuario, 2026-10-28). El enlace «¿Olvidaste tu contraseña?» va a `route('contrasena.solicitud')` cuando el interruptor está encendido; si no, no se muestra. Aviso de éxito con `role="status"`. Lema como párrafo y un solo `h1`. Sin estilos en línea ni Google Fonts. El comportamiento del login no cambia. | Medio: vista de la spec 001 |
| `resources/views/auth/recuperar/` (nuevo) | `solicitud` (pedir y su estado «Demasiados intentos» con el botón desactivado), `enviada`, `nueva` (con errores junto al campo y su estado de espera, D15) y `caducado`. `nueva` y `caducado` ponen `no-referrer` antes de cualquier recurso | Bajo |
| `app/Http/Controllers/RecuperarContrasenaController.php` (nuevo) | `solicitudForm`, `solicitar`, `enviada`, `nuevaForm` y `restablecer` (Contratos) | Medio: flujo de autenticación |
| `routes/web.php` | Cinco rutas bajo `/contrasena`, que solo se registran con `config('acceso.recuperar_contrasena')` (D8), y una ruta de vista previa solo en `local` (D11). Los limitadores van solo en los dos `POST` (D15) | Bajo |
| `config/clinica.php` | Clave `contacto` con la misma forma que en la 038 (`telefono`, `telefono_enlace` y `horario`), con el teléfono real de la página de inicio actual (D16) | Bajo: punto de fusión con la 038 (D14) |
| `config/acceso.php` (nuevo), `.env.example` | `recuperar_contrasena` ← `RECUPERAR_CONTRASENA` (por defecto `false`) | Bajo |
| `app/Providers/AppServiceProvider.php` | Limitadores `solicitar-enlace` y `fijar-contrasena`: 5 envíos por minuto por `AuthController::origenLimite`. La respuesta lleva a la pantalla del formulario en estado de espera: «pedir», conservando el correo; «contraseña nueva», con el token y la cuenta del formulario y nunca la contraseña (D15). Listener de `Login` que guarda `version_sesion` en la sesión. | Bajo |
| `app/Http/Middleware/SesionVigente.php` (nuevo) + `bootstrap/app.php` | Grupo `web`. Con sesión iniciada, una sesión sin versión guardada (de antes del despliegue) cuenta como versión 0. Si la versión de la sesión es menor que `usuarios.version_sesion`, cierra la sesión, la invalida y redirige a `/login`; si no, guarda la versión y sigue (D4) | Medio: corre en cada petición autenticada |
| `database/migrations/2026_10_27_000000_version_sesion_en_usuarios.php` (nuevo) | `usuarios.version_sesion` `int unsigned not null default 0` (solo expand) | Bajo |
| `app/Models/Usuario.php` | `sendPasswordResetNotification($token)` envía `EnlaceRestablecimiento`; `version_sesion` como `integer` | Bajo |
| `app/Mail/EnlaceRestablecimiento.php`, `app/Mail/ContrasenaCambiada.php` (nuevos) + `resources/views/correos/` | Correos HTML con versión de texto. El enlace se arma desde `config('app.url')` y la hora de caducidad va en `clinica.zona_horaria`. El correo del enlace lleva además la dirección de respaldo completa (la URL, partida en líneas). El teléfono y el horario salen de `config('clinica.contacto')` (D16). El aviso no lleva enlace de restablecimiento. Su hoja copia los valores de los tokens y se pasa a estilos en línea con `CssToInlineStyles` (D9). | Medio: primer correo a las cuentas |
| `app/Auditoria/Auditoria.php` (spec 020) | Parámetro opcional `?Usuario $actor` en `registrar()`, añadido con `/plan 020 --fix` antes de implementar la 020. Ese fix actualiza el control TM10 de la 020 según lo que pide D2. La 041 solo lo usa. | Medio: cambia un control de seguridad de la 020 (D2) |
| `docker/nginx/dentissa.conf`, `docker/nginx/dentissa.prod.conf` | Dos `map` (contexto `http`) que cambian el token de `/contrasena/nueva/…` por `***` en la petición y en el Referer, y `log_format` igual a `combined` con los dos campos enmascarados. El vigilante de la 032 sigue leyendo el mismo formato. Convive con el `map` de marcos de la 038 (D14). | Medio: ruta protegida de despliegue (con confirmación) |
| `deploy/` (plantilla de `app.env` en `deploy/README.md`) | `RECUPERAR_CONTRASENA=true`, que se enciende tras comprobar el correo en producción (Rollout) | Bajo |
| `tests/Feature/` | `RecuperarContrasenaTest`, `RecuperarContrasenaAbusoTest`, `RecuperarContrasenaAuditoriaTest`, `RecuperarContrasenaVistasTest`, `CorreosRestablecimientoTest`, `SesionVigenteTest`; casos nuevos en `ConfiguracionProduccionTest` | — |

## Contratos y datos
**Rutas** (grupo `web`, sin `auth`; CSRF y `PreventBackHistory` como el resto):

| Método y ruta | Nombre | Middleware | Entrada | Respuesta |
|---|---|---|---|---|
| `GET /contrasena/olvidada` | `contrasena.solicitud` | — | — | 200, pantalla «¿Olvidaste tu contraseña?». Con el error de espera en la sesión: aviso «Demasiados intentos» y botón desactivado (CA9) |
| `POST /contrasena/olvidada` | `contrasena.solicitar` | `throttle:solicitar-enlace` | `email` | Siempre 302 a `contrasena.enviada`, con el correo y la hora de caducidad (ahora + 60 min, hora local) en el flash, haya cuenta o no (CA8). Formato inválido: 302 atrás con el error junto al campo. Antes de responder, despacha `afterResponse()` con `Password::sendResetLink(['email' => …, 'activo' => 1])`; el estado del broker se ignora. Al pasar el límite: 302 a `contrasena.solicitud` en estado de espera, con el correo (CA9) |
| `GET /contrasena/enviada` | `contrasena.enviada` | — | flash | 200, «Revisa tu correo» con el mensaje genérico. Sin flash: 302 a `contrasena.solicitud` |
| `GET /contrasena/nueva/{token}?cuenta={uuid}` | `contrasena.nueva` | — (D15) | token, cuenta | 200, «Elige tu contraseña nueva» si la cuenta está activa y `Password::tokenExists()`. Si no, 200 «Este enlace ya no sirve» (CA5, CA10, CA11). Las dos llevan `no-referrer` antes de cualquier recurso. Con el error de espera en la sesión: «Elige tu contraseña nueva» en estado de espera (D15) |
| `POST /contrasena/nueva` | `contrasena.restablecer` | `throttle:fijar-contrasena` | `token`, `cuenta`, `password`, `password_confirmation` | Válido: `Password::reset(['email' => <correo de la cuenta activa>, 'activo' => 1, …])` en una transacción (D5) → 302 a `/login` con «Tu contraseña cambió…» (CA3). Error de campo: 302 a `contrasena.nueva` con el token y la cuenta del formulario, nunca `back()`: con `no-referrer` el `POST` no lleva Referer y la URL previa de la sesión puede ser otra página. El error sale junto a su campo y el enlace sigue vigente (CA4). Token o cuenta inválidos: pantalla «Este enlace ya no sirve» (CA5). Al pasar el límite: 302 a `contrasena.nueva` con el token y la cuenta del formulario, en estado de espera; si su formato no es válido, a `contrasena.solicitud` en espera (CA9, D15) |
| `GET /_disenio/041/{pantalla}` | — | solo `APP_ENV=local` | — | Cada pantalla y cada correo con los datos de muestra del diseño, para `medir.mjs` (D11) |

- **Enlace del correo:** `config('app.url').'/contrasena/nueva/'.$token.'?cuenta='.$usuario->id`.
  No sale de la petición, así que una cabecera `Host` falsa no lo cambia (TM12). El correo de la
  cuenta no va nunca en la dirección (P8).
- **Broker** (`config/auth.php`, sin cambios): `expire` 60 y `throttle` 60 (un correo por cuenta y
  minuto; el exceso devuelve `RESET_THROTTLED`, que se ignora: CA9). Un pedido nuevo borra el
  token anterior (CA5, «sustituido»), y `reset()` borra el token usado.
- **Al restablecer** (callback de `Password::reset`, dentro de `DB::transaction`):
  1. `password_hash = Hash::make($password)`;
  2. `version_sesion = version_sesion + 1`;
  3. guardar;
  4. registrar `contrasena_restablecida` en modo crítico, con la cuenta como actor y sujeto;
  5. tras confirmar la transacción, `afterResponse()` envía `ContrasenaCambiada` a la cuenta
     (CA7). Si la transacción falla, no se envía.

  No inicia sesión (spec, "Fuera de alcance").
- **Evento de auditoría:** `registrar(accion: 'contrasena_restablecida', resultado: 'exito',
  recursoTipo: 'usuario', recursoId: $usuario->id, campos: ['password_hash'], critico: true,
  actor: $usuario)`. `$usuario` es el que el broker entrega al callback tras validar el token,
  nunca el `cuenta` de la petición sin validar (D2).
- **Migración:** `version_sesion` (expand). No hay contract: la columna se queda si se revierte la
  versión.
- **Interruptor:** `config('acceso.recuperar_contrasena')` decide si se registran las rutas y si el
  login muestra el enlace (D8).
- **Pantallas y estados** (`design.md`, `candidatos/c7/`):
  - pedir;
  - demasiados intentos, con el botón desactivado;
  - revisa tu correo (`role="status"`);
  - contraseña nueva, con los errores enlazados por `aria-describedby` y `aria-invalid`;
  - contraseña nueva en espera: el aviso y el botón en espera de «demasiados intentos», sin
    componentes ni valores nuevos (D15);
  - enlace que ya no sirve (`role="alert"`);
  - login con el aviso de éxito (`role="status"`);
  - los dos correos.

  Las pantallas no usan JavaScript y tienen un solo `h1`. A 320, 390 y 1440 px se miden con
  `design/medir.mjs` (`criterios.json`, K1–K12 y las 12 puertas) sobre la build.
- **Pendientes del evaluador** (`design.md` → Resultado): las tareas de cada pantalla los aplican y
  vuelven a medir:
  - valores del talón como frases con mayúscula;
  - frase del panel equilibrada;
  - «Tu contraseña no cambió» separado del aviso rojo;
  - en el correo, la dirección de respaldo completa (la URL del enlace, partida en varias líneas).

## Estrategia de pruebas
- **Feature (PHPUnit, `DatabaseTransactions`, `dentissa_test`):**
  - el flujo completo con `Mail::fake()` y `Bus::fake()` donde haga falta;
  - el broker real, con las tres situaciones de token: caducado (con `travel(61)->minutes()`), usado y sustituido;
  - los limitadores, con `RateLimiter::clear` entre casos: 5 envíos permitidos, el sexto ve la
    espera sin redirigir en bucle; cuenta todo `POST`, válido o no (un error de campo gasta un
    intento), y abrir el enlace no gasta; con entrada malformada, la espera no falla;
  - la versión de sesión, con dos sesiones de la misma cuenta y con una sesión sin versión;
  - el registro de auditoría, con el doble del almacén de la 020, que falla a voluntad;
  - las vistas, sobre el HTML que devuelve el servidor: `noindex`, `referrer`, sin estilos en línea, roles ARIA y textos de CA1–CA9.
- **Tiempo de CA8:** `RecuperarContrasenaAbusoTest::test_la_respuesta_tarda_lo_mismo_con_y_sin_cuenta`.
  - Llama al kernel HTTP (`handle`, sin `terminate`) 20 veces, alternando una cuenta activa y un correo sin cuenta.
  - Exige que la diferencia de medias sea menor de 300 ms y que la cuenta activa no tenga token antes de terminar.
  - Así se prueba que el trabajo caro no está en la respuesta.
- **Correos:** `CorreosRestablecimientoTest` renderiza los dos `Mailable`. Comprueba:
  - el enlace con `APP_URL`, también con una cabecera `Host` falsa, y la dirección de respaldo
    completa en HTML y en texto;
  - «60 minutos» y la hora local;
  - «Si no lo pediste…»;
  - en el aviso, sin enlace de restablecimiento y con el teléfono y el horario de `config('clinica.contacto')`;
  - la versión de texto;
  - que cada valor de la hoja (color, tamaño, interlineado, espaciado, radio) sea el de un token.

  Además, `RecuperarContrasenaTest` comprueba que un restablecimiento envía `ContrasenaCambiada` a
  la cuenta, y `RecuperarContrasenaAuditoriaTest`, que no se envía si falla el registro.
- **Configuración:** `ConfiguracionProduccionTest` lee `dentissa.prod.conf` y `dentissa.conf`, y comprueba los dos `map` y el `log_format`. Un test aplica las mismas expresiones a una línea de ejemplo con el token en la petición y en el Referer, y obtiene `***` en los dos.
- **Diseño:** `node docs/specs/041-recuperar-contrasena/design/medir.mjs http://localhost:8000/_disenio/041 --evaluate <skill>/scripts/evaluate.mjs --out .ai/inspect/041-build`. Va en el `hecho cuando:` de las tareas de pantallas y en `/review`. Puertas que fallan = bloqueante; KPI = importante.
- **Manual (T095, producción):**
  - un correo real llega en menos de 5 minutos (CA2);
  - el remitente es «Dentissa»;
  - el enlace abre y restablece;
  - el aviso llega (CA7).

## Modelo de amenazas
Flujo: visitante → nginx (Cloudflare) → `RecuperarContrasenaController` → broker y `usuarios` → trabajo tras la respuesta → SMTP de Gmail → buzón → enlace → formulario → transacción con auditoría.

| ID | Amenaza (STRIDE) | Categoría OWASP | Componente | Control | Test |
|---|---|---|---|---|---|
| TM1 | Information disclosure: averiguar qué correos tienen cuenta por el texto, el código o el tiempo de respuesta (CA8) | A07:2025 | `solicitar` | Misma redirección y mismo flash para todos; el trabajo que depende de la cuenta va después de responder | `RecuperarContrasenaAbusoTest::test_misma_respuesta_con_cuenta_activa_desactivada_o_sin_cuenta`, `…_la_respuesta_tarda_lo_mismo_con_y_sin_cuenta` |
| TM2 | Denial of service: inundar un buzón o probar en masa (CA9) | A06:2025 | `solicitar`, `restablecer` | Espera del broker de 60 s por cuenta; limitadores de 5 envíos por minuto por origen en los dos formularios, con la pantalla de espera y sin bucles de redirección (D15) | `…_un_correo_por_cuenta_por_minuto`, `…_cinco_solicitudes_por_minuto_por_origen`, `…_cinco_intentos_de_fijar_por_minuto_por_origen`, `…_la_espera_al_fijar_no_redirige_en_bucle`, `…_los_errores_de_campo_gastan_intentos`, `…_la_espera_con_entrada_malformada_no_falla` |
| TM3 | Spoofing: adivinar o alterar el token (CA10, CA5) | A07:2025 | broker | Token aleatorio de 64 caracteres guardado con hash; caduca en 60 min; borrado al usarlo o al pedir otro | `…_rechaza_un_token_alterado`, `RecuperarContrasenaTest::test_rechaza_un_enlace_caducado`, `…_usado`, `…_sustituido` |
| TM4 | Elevation of privilege: usar el token de la cuenta A con el identificador de la cuenta B (CA10) | A01:2025 | `restablecer` | El broker compara el token con el hash de la cuenta B; falla y nada cambia | `…_el_token_de_una_cuenta_no_cambia_la_contrasena_de_otra` |
| TM5 | Spoofing: una cuenta desactivada recupera el acceso (CA11) | A07:2025 | broker | `activo = 1` en las credenciales al pedir y al restablecer; el login ya exige `activo` | `…_una_cuenta_desactivada_no_recibe_correo`, `…_una_cuenta_desactivada_no_puede_restablecer` |
| TM6 | Information disclosure: el token, el correo o la contraseña en los logs (P8) | A09:2025 | nginx, logs de Laravel | `map` de nginx que enmascara el token en la petición y en el Referer; ningún `Log::` con datos de la petición; el fallo de correo solo con la clase del error | `…_ningun_log_contiene_el_correo_el_token_ni_la_contrasena`, `ConfiguracionProduccionTest::test_nginx_enmascara_el_token_de_restablecimiento`, `…_nginx_enmascara_el_token_en_el_referer` |
| TM7 | Information disclosure: el enlace sale por `Referer`, a otros sitios o al log del propio sitio | A02:2025 | vistas `nueva` y `caducado` (todas las respuestas de la URL del token) | Sin recursos externos (Roboto propia) y `<meta name="referrer" content="no-referrer">` antes de cualquier recurso; el log lo enmascara además (TM6) | `RecuperarContrasenaVistasTest::test_la_pagina_del_token_no_envia_referer` (en «nueva», en «caducado» y en la espera), `…_sin_recursos_externos` |
| TM8 | Spoofing: quien leyó la contraseña anterior sigue con su sesión abierta (CA7) | A07:2025 | `SesionVigente` | `version_sesion` sube al restablecer y cierra las demás sesiones en su siguiente petición, también en cuentas de Google sin contraseña y en las sesiones abiertas antes del despliegue (sin versión cuentan como 0) | `SesionVigenteTest::test_restablecer_cierra_las_otras_sesiones`, `…_tambien_en_cuentas_de_google`, `…_una_sesion_anterior_al_despliegue_se_cierra_si_hubo_restablecimiento`, `…_una_sesion_anterior_al_despliegue_sigue_si_no_lo_hubo` |
| TM9 | Repudiation: cambiar la contraseña sin rastro (CA12) | A09:2025 | transacción de `restablecer` | Evento de la 020 en modo crítico dentro de la transacción | `RecuperarContrasenaAuditoriaTest::test_el_restablecimiento_queda_registrado`, `…_sin_registro_la_contrasena_no_cambia` |
| TM10 | Tampering: enviar el formulario desde otro sitio | A01:2025 | formularios | CSRF del grupo `web` (ya activo) | `RecuperarContrasenaTest::test_sin_token_csrf_se_rechaza` |
| TM11 | Information disclosure: la página con el token queda en la caché o en el historial del navegador compartido | A02:2025 | vista `nueva` | `PreventBackHistory` (`no-store`) en el grupo `web` | `RecuperarContrasenaVistasTest::test_la_pagina_del_token_no_se_guarda_en_cache` |
| TM12 | Spoofing: enlace envenenado con una cabecera `Host` falsa que apunta a otro sitio | A02:2025 | `EnlaceRestablecimiento` | El enlace se arma con `config('app.url')`, nunca con la petición | `CorreosRestablecimientoTest::test_el_enlace_usa_app_url_aunque_el_host_sea_otro` |
| TM13 | Information disclosure: la URL con un token vigente queda en el historial de un navegador compartido (`no-store` no lo evita) | A07:2025 | broker | El token deja de servir al usarlo, al pedir otro o a los 60 minutos. Residuo aceptado por el usuario el 2026-10-28 (`/analyze` A30): quien abra ese historial antes de que pase una de las tres cosas puede usarlo, igual que quien tenga el buzón | `RecuperarContrasenaTest::test_rechaza_un_enlace_usado`, `…_sustituido`, `…_caducado` |
| TM14 | Information disclosure: el correo escrito queda en el flash de la sesión, guardada en un archivo sin cifrar | A02:2025 | `solicitar`, `enviada` | El flash dura una petición: «Revisa tu correo» lo muestra y desaparece. El cifrado de las sesiones en producción queda fuera: sigue pendiente en `docs/security.md` (objetivo 2) | `RecuperarContrasenaTest::test_el_correo_no_queda_en_la_sesion_tras_mostrarlo` |

## Trazabilidad
| Criterio de aceptación | Cambio(s) | Test(s) |
|---|---|---|
| CA1: el enlace del login abre la pantalla para pedir, con vuelta al login | `login.blade.php`, rutas, `solicitud` | `RecuperarContrasenaTest::test_el_login_enlaza_a_recuperar`, `…_la_pantalla_de_solicitud_tiene_volver` · manual: `medir.mjs` (K1) |
| CA2: mensaje genérico y correo en español con enlace personal, 60 minutos y «puede ignorarse» | `solicitar`, `EnlaceRestablecimiento` | `RecuperarContrasenaTest::test_una_cuenta_activa_recibe_el_enlace` · `CorreosRestablecimientoTest::test_el_correo_del_enlace_tiene_caducidad_y_aviso` · manual: `medir.mjs` (K2, K3) · verificación manual: T095, un correo real llega en menos de 5 minutos a un buzón de prueba |
| CA3: contraseña de 8 o más con confirmación → reemplaza, vuelve al login con éxito, entra con la nueva y no con la vieja | `restablecer`, login | `RecuperarContrasenaTest::test_restablece_y_vuelve_al_login_con_exito`, `…_entra_con_la_nueva_y_no_con_la_anterior` · manual: `medir.mjs` (K4, K7) |
| CA4: menos de 8 o sin coincidir → error junto al campo, la contraseña no cambia y el enlace sigue | validación, vista `nueva` | `RecuperarContrasenaTest::test_rechaza_menos_de_ocho_caracteres`, `…_rechaza_confirmacion_distinta_junto_al_campo`, `…_tras_un_error_el_enlace_sigue_sirviendo`, `…_el_error_de_campo_vuelve_a_la_url_del_token_sin_referer` · manual: `medir.mjs` (K4b) |
| CA5: caducado, usado o sustituido → mensaje con «pedir uno nuevo» y la contraseña no cambia | broker, vista `caducado` | `RecuperarContrasenaTest::test_rechaza_un_enlace_caducado`, `…_usado`, `…_sustituido`, en GET y en POST · manual: `medir.mjs` (K5, K5b) |
| CA6: cuenta de Google sin contraseña → la crea y entra con correo y con Google | broker, `Usuario` | `RecuperarContrasenaTest::test_una_cuenta_de_google_crea_su_contrasena_y_sigue_con_google` |
| CA7: cierra todas las sesiones y envía el aviso sin enlace, con contacto de la clínica | `SesionVigente`, `version_sesion`, `ContrasenaCambiada`, `config/clinica.php` | `SesionVigenteTest::test_restablecer_cierra_las_otras_sesiones`, `…_tambien_en_cuentas_de_google`, `…_una_sesion_anterior_al_despliegue_se_cierra_si_hubo_restablecimiento` · `RecuperarContrasenaTest::test_restablecer_envia_el_aviso_a_la_cuenta` · `RecuperarContrasenaAuditoriaTest::test_sin_registro_no_se_envia_el_aviso` · `CorreosRestablecimientoTest::test_el_aviso_no_tiene_enlace_y_da_el_contacto` · manual: `medir.mjs` (K8) |
| CA8 (abuso): misma respuesta, código y tiempo | `solicitar`, `afterResponse()` | `RecuperarContrasenaAbusoTest::test_misma_respuesta_con_cuenta_activa_desactivada_o_sin_cuenta`, `…_la_respuesta_tarda_lo_mismo_con_y_sin_cuenta` |
| CA9 (abuso): 1 por cuenta por minuto y 5 por origen al pedir y al fijar; la pantalla pide esperar | broker, limitadores, vistas `solicitud` y `nueva` | `…_un_correo_por_cuenta_por_minuto`, `…_cinco_solicitudes_por_minuto_por_origen`, `…_cinco_intentos_de_fijar_por_minuto_por_origen`, `…_la_espera_al_fijar_no_redirige_en_bucle`, `…_los_errores_de_campo_gastan_intentos`, `…_la_espera_con_entrada_malformada_no_falla`, `RecuperarContrasenaVistasTest::test_la_espera_desactiva_el_boton` (en «pedir» y en «contraseña nueva») · manual: `medir.mjs` (K6) |
| CA10 (abuso): enlace adivinado, alterado o de otra cuenta → rechazo | broker | `…_rechaza_un_token_alterado`, `…_el_token_de_una_cuenta_no_cambia_la_contrasena_de_otra` |
| CA11 (abuso): desactivada → sin correo, el enlace previo no sirve, sigue sin entrar | credenciales con `activo` | `…_una_cuenta_desactivada_no_recibe_correo`, `…_una_cuenta_desactivada_no_puede_restablecer` |
| CA12: auditoría en el registro de la 020, sin contraseña ni enlace | transacción, `Auditoria` | `RecuperarContrasenaAuditoriaTest::test_el_restablecimiento_queda_registrado` (actor y sujeto, acción, resultado, fecha; `campos` sin valores, `detalle` sin token), `…_sin_registro_la_contrasena_no_cambia` |
| RNF: WCAG 2.1 AA a 320, 390 y 1440 px, avisos con texto | vistas, `acceso.css` | `RecuperarContrasenaVistasTest::test_roles_de_los_avisos_y_errores` · manual: `medir.mjs` sobre la build (puertas `G-contraste`, `G-toque`, `G-foco`, `G-reducido`, `G-sin-js`) |
| RNF: diferencia de medias menor de 300 ms en 20 solicitudes | `afterResponse()` | `…_la_respuesta_tarda_lo_mismo_con_y_sin_cuenta` |
| RNF: entrega en menos de 5 minutos en producción | SMTP de Gmail | verificación manual: T095 |
| RNF: ningún log con correo, enlace ni contraseña | nginx, controlador | `…_ningun_log_contiene_el_correo_el_token_ni_la_contrasena`, `ConfiguracionProduccionTest::test_nginx_enmascara_el_token_de_restablecimiento`, `…_nginx_enmascara_el_token_en_el_referer` (residuos de local y de OB4.a en «Riesgos»; el `error_log` de nginx en los fallos de PHP, excepción acotada a P8, A28) · verificación manual: T095, `grep` de `dentissa-access.log` (0) y de `dentissa-error.log` (se anota) |
| RNF: secretos del correo fuera del repo | `app.env` | lint: gitleaks · `ConfiguracionProduccionTest` (sin `MAIL_PASSWORD` con valor en el repo) |
| RNF: pantallas fuera de buscadores y del sitemap; el enlace no sale a otros sitios | vistas, layout de acceso | `RecuperarContrasenaVistasTest::test_las_pantallas_nuevas_no_se_indexan`, `…_el_login_no_se_indexa` (SE2.a), `…_la_pagina_del_token_no_envia_referer`, `…_sin_recursos_externos` |
| TM1: enumeración de cuentas | `solicitar` | `…_misma_respuesta_con_cuenta_activa_desactivada_o_sin_cuenta`, `…_la_respuesta_tarda_lo_mismo_con_y_sin_cuenta` |
| TM2: inundar buzones o probar en masa | broker, limitadores | `…_un_correo_por_cuenta_por_minuto`, `…_cinco_solicitudes_por_minuto_por_origen`, `…_cinco_intentos_de_fijar_por_minuto_por_origen`, `…_la_espera_al_fijar_no_redirige_en_bucle`, `…_los_errores_de_campo_gastan_intentos`, `…_la_espera_con_entrada_malformada_no_falla` |
| TM3: token adivinado o alterado | broker | `…_rechaza_un_token_alterado`, `…_rechaza_un_enlace_caducado`, `…_usado`, `…_sustituido` |
| TM4: token de otra cuenta | broker | `…_el_token_de_una_cuenta_no_cambia_la_contrasena_de_otra` |
| TM5: cuenta desactivada | credenciales con `activo` | `…_una_cuenta_desactivada_no_recibe_correo`, `…_una_cuenta_desactivada_no_puede_restablecer` |
| TM6: token o datos en los logs | nginx, controlador | `…_ningun_log_contiene_el_correo_el_token_ni_la_contrasena`, `ConfiguracionProduccionTest::test_nginx_enmascara_el_token_de_restablecimiento`, `…_nginx_enmascara_el_token_en_el_referer` |
| TM7: el enlace sale por `Referer` | vistas `nueva` y `caducado` | `RecuperarContrasenaVistasTest::test_la_pagina_del_token_no_envia_referer`, `…_sin_recursos_externos` |
| TM8: sesión abierta con la contraseña anterior | `SesionVigente` | `SesionVigenteTest::test_restablecer_cierra_las_otras_sesiones`, `…_tambien_en_cuentas_de_google`, `…_una_sesion_anterior_al_despliegue_se_cierra_si_hubo_restablecimiento`, `…_una_sesion_anterior_al_despliegue_sigue_si_no_lo_hubo` |
| TM9: cambio sin rastro | transacción, `Auditoria` | `RecuperarContrasenaAuditoriaTest::test_el_restablecimiento_queda_registrado`, `…_sin_registro_la_contrasena_no_cambia` |
| TM10: formulario desde otro sitio | CSRF | `RecuperarContrasenaTest::test_sin_token_csrf_se_rechaza` |
| TM11: la página del token en caché | `PreventBackHistory` | `RecuperarContrasenaVistasTest::test_la_pagina_del_token_no_se_guarda_en_cache` |
| TM12: enlace envenenado por `Host` | `EnlaceRestablecimiento` | `CorreosRestablecimientoTest::test_el_enlace_usa_app_url_aunque_el_host_sea_otro` |
| TM13: token vigente en el historial del navegador | broker | `RecuperarContrasenaTest::test_rechaza_un_enlace_usado`, `…_sustituido`, `…_caducado` |
| TM14: el correo escrito en la sesión | `solicitar`, `enviada` | `RecuperarContrasenaTest::test_el_correo_no_queda_en_la_sesion_tras_mostrarlo` |

## Observabilidad
- **Logs nuevos:** `restablecimiento.correo_fallido`, nivel `error`.
  - Campos: solo `error`, la clase corta de la excepción.
  - `request_id` y `actor_id` llegan por el `Context` de la 020; el trabajo tras la respuesta corre en la misma petición.
  - Nunca el correo, el token, el enlace ni la contraseña.
  - Test: `…_ningun_log_contiene_el_correo_el_token_ni_la_contrasena` y `CorreosRestablecimientoTest::test_un_fallo_del_correo_solo_registra_la_clase`.
- **Eventos de auditoría:** `contrasena_restablecida`.
  - Se emite en el callback de `Password::reset`, dentro de la transacción, en modo crítico.
  - Test: `RecuperarContrasenaAuditoriaTest`.
  - Cubre solo el restablecimiento entre los cambios de contraseña obligatorios de `docs/observability.md`. Las altas, los roles, la activación y el cambio desde el perfil siguen en la 021.
- **Métricas y alertas:** ninguna nueva. El vigilante de la 032 sigue viendo los 5xx de estas rutas en el log de nginx.
- **Cómo se verifica tras el deploy:**
  - en producción, `SELECT accion, resultado, actor_id = recurso_id FROM auditoria WHERE accion = 'contrasena_restablecida' ORDER BY id DESC LIMIT 1` tras la prueba de T095;
  - `grep -c '/contrasena/nueva/[^*]' dentissa-access.log` debe ser 0 (cubre la petición y el Referer);
  - `grep -c '/contrasena/nueva/[^*]' dentissa-error.log`: se anota el resultado. Si no es 0, son
    fallos de PHP en esa ruta, que la excepción de P8 acepta (`/analyze` A28).

## Descubribilidad
| Ruta | Indexable | Título | Descripción | Canónica | Sitemap | Datos estructurados | Test |
|---|---|---|---|---|---|---|---|
| `/contrasena/olvidada` | no (acceso, como `/login`) | «Dentissa · Recuperar acceso» | — | — | no | — | `test_las_pantallas_nuevas_no_se_indexan` |
| `/contrasena/enviada` | no (estado del acceso) | «Dentissa · Revisa tu correo» | — | — | no | — | ídem |
| `/contrasena/nueva/{token}` | no (contiene un secreto) | «Dentissa · Contraseña nueva» | — | — | no | — | ídem, y `…_la_pagina_del_token_no_envia_referer` |
| `/login` (vista cambiada) | no: ahora con `noindex` desde el layout de acceso (SE2.a) | sin cambios | — | — | no | — | `test_el_login_no_se_indexa` |

- Renderizado: Blade en el servidor, igual que `seo.rendering`.
- Imagen para compartir: no aplica (no indexables).
- Brechas de `docs/seo.md` (decisión del usuario, 2026-10-28):
  - SE2.a entra y queda mitigada. `/login` lleva `noindex`, y `/registro/contacto` solo se sirve
    con los datos de Google en la sesión: sin ellos redirige a `/registro`
    (`RegisterController::contactoForm`), así que un buscador nunca lo recibe.
  - SE2.b sigue pendiente, en el objetivo de SEO (`noindex` en la 404 propia y `X-Robots-Tag`
    en `/up`).

## Rollout
- **Feature flag:** `RECUPERAR_CONTRASENA` (`config('acceso.recuperar_contrasena')`).
  - Por defecto `false`.
  - Apagado: las rutas no existen (404) y el login no muestra el enlace.
  - El aspecto nuevo del login no depende del interruptor.
- **Prerrequisitos:**
  1. `/plan 020 --fix` (actor opcional, D2) y `/tasks 020 --fix`.
  2. Implementar y liberar la 020 antes que la 041 (D1).
  3. La cuenta de Gmail de la 032 en `app.env`, ya configurada.
- **Orden del despliegue:**
  1. `bash deploy/desplegar.sh <tag>` aplica la migración (`version_sesion`, expand) y la imagen de `web` con el `log_format` nuevo, con el interruptor apagado.
  2. `verificar.sh` como siempre.
  3. Prueba de T095 con el interruptor encendido en `app.env` (`aplicar-entorno.sh app`) y una cuenta de prueba: correo en menos de 5 minutos, restablecer, sesión cerrada en otro navegador, evento de auditoría y log de nginx sin token.
  4. Si todo pasa, se deja encendido.
- **Compatibilidad:**
  - La columna nueva tiene valor por defecto: el código anterior la ignora.
  - Las sesiones abiertas antes del despliegue cuentan como versión 0: siguen abiertas si su cuenta no restableció la contraseña desde entonces, y se cierran si lo hizo (D4).
- **Rollback:**
  - Primero, `RECUPERAR_CONTRASENA=false` y `aplicar-entorno.sh app`: el flujo desaparece sin desplegar.
  - Si hace falta, `deploy/volver.sh <tag anterior>`. La migración no se revierte: la columna `version_sesion` no estorba al código anterior. Los tokens pendientes caducan solos en 60 minutos.
- **Métricas a vigilar:**
  - 5xx en `/contrasena/*` (vigilante de la 032);
  - `restablecimiento.correo_fallido` en el log;
  - ráfagas de 429 en el log de nginx.

## Decisiones (→ ADR si son arquitectónicas)
- **D0 · Tamaño:** la spec se mantiene junta con sus 12 criterios: pedir, fijar y sus protecciones no se liberan por separado sin dejar un hueco (decisión del usuario, 2026-10-27).
- **D1 · Orden:** la 020 se implementa y libera antes que la 041 (decisión del usuario, 2026-10-27).
- **D2 · Actor de auditoría sin sesión:** se amplía el contrato de la 020 con un parámetro opcional `?Usuario $actor` en `registrar()`. Por defecto, `Auth::user()`. Va con `/plan 020 --fix` antes de implementar la 020 (decisión del usuario, 2026-10-27).
  - Alternativas descartadas: sesión solo en memoria (frágil), actor vacío (no cumple CA12).
  - Qué debe cumplir el contrato, porque cambia el control TM10 de la 020 (el actor nunca sale de
    la petición) (`/analyze` A9):
    - el actor que se pasa es un `Usuario` cargado y comprobado en el servidor, nunca un
      identificador que llega en la petición sin validar. En la 041 es el `$user` que el broker
      entrega al callback tras validar el token;
    - `actor_rol` y `actor_id` salen del actor pasado, no de la sesión;
    - `/plan 020 --fix` actualiza TM10 de la 020 con esas dos reglas y un test que lo compruebe, y
      sus tests de TM10 siguen en verde. T002 de la 041 lo verifica antes de empezar.
- **D3 · Correo:** el SMTP de Gmail de la 032, enviado después de responder (decisión del usuario, 2026-10-27) → [ADR 0008](../../adr/0008-correo-a-usuarios-por-gmail.md) (accepted).
  - `docs/security.md` cambia la frase de TM13 de la 032 en T090.
  - El remitente es la cuenta de Gmail con el nombre «Dentissa». `no-responder@dentissa.app` era contenido de muestra del diseño.
- **D4 · Cierre de sesiones:** columna `version_sesion` + middleware `SesionVigente` + listener de `Login`, solo al restablecer, respetando "Fuera de alcance" de la spec (decisión del usuario, 2026-10-27).
  - Una sesión sin versión guardada cuenta como versión 0, el valor por defecto de la columna. Así
    CA7 se cumple también para las sesiones abiertas antes del despliegue (`/analyze` A3).
  - Alternativas descartadas:
    - `AuthenticateSession` de Laravel: no vigila las cuentas con contraseña vacía (las de Google), y CA7 fallaría con ellas.
    - Driver de sesión `database`: cambia la configuración de producción y el cifrado de sesiones, fuera de alcance.
  - El cierre de sesiones al cambiar la contraseña desde el perfil sigue pendiente en `docs/security.md` (objetivo 2). Esta spec deja lista la pieza (subir la versión) para quien lo atienda.
- **D5 · Restablecimiento y auditoría atómicos:** el callback del broker cambia la contraseña, sube la versión y registra en modo crítico dentro de `DB::transaction`. El broker borra el token después del callback, dentro de la misma transacción: si el registro falla, nada cambia y se muestra el error genérico (503) de la 020.
- **D6 · CA8 sin colas:** `afterResponse()` en lugar de un trabajador. La prueba automática mide el kernel sin la fase de terminación.
- **D7 · El token fuera de los logs:**
  - `map $request` y `map $http_referer` en nginx (local y producción), con el mismo formato
    `combined`. El Referer también se enmascara: las peticiones al mismo origen desde la página del
    token lo llevan si el navegador no aplica antes `no-referrer` (`/analyze` A6).
  - El enlace usa el identificador de la cuenta, no su correo.
  - Alternativa descartada: el token en el fragmento (`#`), que necesita JavaScript (puerta `G-sin-js` del diseño).
- **D8 · Interruptor** `RECUPERAR_CONTRASENA`: obligatorio porque el flujo cambia el acceso y depende de un SMTP externo.
- **D9 · Correos y P9:** la hoja del correo copia los valores de los tokens, porque los clientes de correo no entienden `var()`, y `CssToInlineStyles` la convierte en estilos en línea al enviar. Un test exige que cada valor de la hoja (color, tamaño, interlineado, espaciado, radio) sea el de un token de `system.md`, sin excepción a P9 (decisión del usuario, 2026-10-28, `/analyze` A7). Las plantillas Blade del correo no llevan `style=""`.
  - `CssToInlineStyles` es `tijsverkoyen/css-to-inline-styles`, que ya está en `composer.lock` como
    dependencia de `laravel/framework` (`^2.2.5`). Se usa directamente, así que se declara y se
    verifica según `shared/agent-security.md` §2 (`/analyze` A16):
    - existe en Packagist con ese nombre;
    - la versión del lock, v2.4.0, se publicó el 2025-12-02, así que supera los 7 días;
    - la mantiene Tijs Verkoyen, tiene repositorio público y Laravel la usa para sus correos;
    - licencia BSD-3-Clause;
    - sin scripts de Composer.
  - No se añade a `composer.json`: si Laravel la retira, la suite lo detecta.
- **D17 · Qué revisan los tests de P9** (`/analyze` A29):
  - **Propiedades revisadas** en `acceso.css` (fuera de `:root`) y en la hoja de los correos:
    - color, fondo, color y ancho de borde;
    - familia, tamaño, peso e interlineado de la letra;
    - márgenes, rellenos y separaciones (`gap`);
    - radios, sombras, anchos máximos y duraciones de transición.
  - **Cómo deben ser:** en `acceso.css`, siempre `var(--…)` de un token de `system.md`. En la hoja
    de los correos, el valor literal de un token de `system.md`.
  - **Exentos:**
    - los valores `0`, `auto`, `none`, `inherit`, `currentColor` y `transparent`;
    - los porcentajes y `fr` de maquetación;
    - las condiciones de `@media`, que CSS no deja escribir con `var()`. Usan los cortes del
      sistema (769 px);
    - los atributos HTML de las tablas del correo (`width="100%"`, `cellpadding="0"`).
  - **Tests:** `…_acceso_css_sin_literales_fuera_de_root` y `…_los_valores_de_los_correos_son_tokens`
    (`RecuperarContrasenaVistasTest`), y `…_los_valores_de_la_hoja_son_tokens`
    (`CorreosRestablecimientoTest`). Fallan con un literal fuera de las exenciones y pasan con ellas.
- **D10 · Roboto propia:**
  - `roboto-latin-wght-normal.woff2` de `@fontsource-variable/roboto` 5.2.5, licencia OFL 1.1, con `OFL.txt`, en `public/fonts/roboto/`.
  - Se copia el archivo, no se añade la dependencia.
  - Verificación de `shared/agent-security.md` §2:
    - paquete publicado por el proyecto Fontsource, con millones de descargas semanales;
    - la fuente es Roboto de Google;
    - sin scripts de instalación;
    - descargado con `npm pack --ignore-scripts` el 2026-10-27.
  - Quita la petición a Google Fonts del login.
- **D11 · Vista previa del diseño solo en local:** `/_disenio/041/{pantalla}` se registra solo con `APP_ENV=local`, para que `medir.mjs` mida los estados que dependen de la sesión (enviado, espera, éxito) y de un token. Test: `RecuperarContrasenaVistasTest::test_la_vista_previa_no_existe_fuera_de_local`.
- **D12 · Layout de acceso:** `layouts/acceso.blade.php` se suma a `layouts/app.blade.php`, el único que hay hoy en `main` (la 038 añade `publico`, D14). Lo usan el login y la recuperación; el registro sigue con su HTML hasta que una spec lo toque.
- **D13 · Sistema de diseño en `main`:** `c7` se diseñó sobre el `system.md` 1.8.0 de la rama de la 038 (`design.md`, `system_version`), y `main` está en 1.6.0. La 041 es independiente de la 038 (decisión del usuario, 2026-10-28, `/analyze` A1). La primera tarea lleva a `main`:
  - los tokens y componentes de «Cambios a incorporar al sistema» de `design.md`;
  - los cinco tokens de la 038 que usan las pantallas de `c7`, con el mismo nombre y valor que en
    la 038, para que la fusión no choque:
    - `--green-texto` `#137A5A`;
    - `--text-publico` 17 px;
    - `--leading-publico` 1.5;
    - `--radius-pill` 999 px;
    - `--sombra-tarjeta-alta` `0 14px 32px rgba(114, 36, 62, 0.16)`.

    Cada uno se anota en `system.md` con la 041 como motivo.
  - Test: ningún `var(--x)` de `acceso.css` ni de las vistas del acceso sin su token en
    `system.md`.
- **D14 · Relación con la 038 (en pausa, rama `feature/038-pagina-inicio`)** (decisión del usuario, 2026-10-28, `/analyze` A2): la 041 no espera a la 038. La que se fusione después resuelve estos puntos:
  - **`system.md` y `system.html`:** cada una toma la siguiente versión libre al fusionar, en lugar
    de «1.9.0». La 038 no puede retirar ninguno de los cinco tokens de D13 mientras el acceso
    los use, aunque su ronda 5 da `--sombra-tarjeta-alta` por «sin uso».
  - **nginx:** los `map` de la 041 (token) y el de la 038 (marcos) conviven en el contexto `http`
    de `dentissa.conf` y `dentissa.prod.conf`, con nombres de variable distintos. Tras fusionar:
    `nginx -t`, `ConfiguracionProduccionTest` y `CabecerasSeguridadTest` en verde.
  - **`public/fonts/`:** la 041 añade `roboto/`. La 038 añade otras familias y convierte la
    carpeta en ruta protegida; si llega antes, la tarea de Roboto pide aprobación.
  - **`AGENTS.md`:** «Reglas y estilo» lista los layouts que existan al fusionar.
  - **Spec 001:** la 038 sustituye su CA4 (la portada) y la 041 cambia la vista del login. No se
    contradicen; cada una anota su cambio en el Historial de la 001.
  - **`routes/web.php`:** la 038 cambia `/` y añade `/servicios`; la 041 añade `/contrasena/*`.
    Sin rutas en común.
  - **`config/clinica.php`:** la 041 añade `contacto.telefono`, `contacto.telefono_enlace` y
    `contacto.horario` con la forma de la 038 y el teléfono real (D16). La 038, al retomarse, usa
    esos valores en lugar de su teléfono de relleno y añade sus otras claves. Tras fusionar, una
    sola clave `contacto`, y el teléfono de la portada y el de los correos son el mismo (`/analyze` A27).
- **D15 · Limitador al fijar la contraseña** (`/analyze` A4):
  - los limitadores cuentan los envíos (los dos `POST`), como dice CA9: todo `POST`, pase o no la
    validación, así que un error de campo gasta un intento (`/analyze` A24). El `GET` del enlace
    no lleva limitador: el token es aleatorio de 64 caracteres (TM3);
  - al pasar el límite, la respuesta lleva a la pantalla del formulario (nunca a la misma URL
    limitada): «pedir» con el correo y «contraseña nueva» con el token y la cuenta del formulario,
    sin la contraseña. Como esa respuesta corre antes de validar, solo usa el token y la cuenta
    si son texto de formato válido (el token, alfanumérico de hasta 255 caracteres; la cuenta, un
    UUID). Si no, lleva a «pedir» en espera, sin error (`/analyze` A25);
  - «contraseña nueva» en espera reutiliza el aviso y el botón en espera de «demasiados intentos»
    (`design.md` → Componentes), sin componentes ni valores nuevos. Se mide con `medir.mjs` como
    estado de la vista previa.
- **D16 · Datos de la clínica en los correos y el talón** (`/analyze` A15, A27): `config/clinica.php` gana la clave `contacto` con la misma forma que en la rama de la 038, para que la fusión deje una sola fuente (decisión del usuario, 2026-10-28):
  - `telefono` `753 162 5220` y `telefono_enlace` `tel:+527531625220`: el teléfono real que publica hoy la página de inicio (`resources/views/landing/index.blade.php`), no el de relleno de la 038 (`753 000 0000`);
  - `horario`: `['Lunes a viernes' => '9:00–20:00', 'Sábados' => '9:00–14:00']`, el de la página de inicio con la forma de la 038.

  Las demás claves de la 038 (`direccion`, `horario_semanal`) llegan con ella. El correo, el aviso y el talón leen de `config('clinica.contacto')`, sin texto fijo en las vistas.

## Impacto en arquitectura
- `docs/architecture.md` (T090):
  - fila «Autenticación»: recuperación de contraseña, broker, versión de sesión, `SesionVigente`;
  - correo saliente a las cuentas;
  - layouts (`acceso`).
- `docs/security.md`:
  - autenticación: ya hay recuperación;
  - entradas: las cinco rutas;
  - TM13 de la 032 y la cuenta de Gmail;
  - riesgo de sesiones al cambiar credenciales: el restablecimiento cierra sesiones; el perfil sigue pendiente.
- `docs/observability.md`: `contrasena_restablecida` (los demás cambios de contraseña, en la 021).
- `docs/deployment.md`: `RECUPERAR_CONTRASENA` y el `log_format` enmascarado.
- `docs/seo.md`: las tres rutas no indexables y SE2.a mitigada (`/login` con `noindex`); SE2.b sigue pendiente.
- `AGENTS.md`: «Reglas y estilo» (los layouts que existan, D12, D14) y el mapa (middleware, `app/Mail`).
- ADR 0008: `accepted` (2026-10-28).

## Riesgos y mitigaciones
- **Gmail manda el correo a spam o rechaza el envío.** «Revisa tu correo» lo advierte; la prueba de T095 con el interruptor; rollback por interruptor.
- **El trabajo tras la respuesta depende de PHP-FPM** (`fastcgi_finish_request`): con `php artisan serve` en local la respuesta tarda más. El test mide el kernel; la prueba manual de tiempos se hace en producción con el interruptor en T095.
- **`SesionVigente` corre en cada petición autenticada.** Compara un entero de la sesión con el del usuario ya cargado: sin consulta adicional.
- **Las pantallas del acceso cambian el aspecto del login (spec 001):**
  - tests existentes que leen el HTML del login, como `GoogleVueltaFallidaTest` y `LimiteIntentosTest`, que se revisan en la tarea del login;
  - `medir.mjs` sobre la build.
- **Dependencia de la 020:** si su implementación se retrasa, la 041 espera (D1). La tarea de auditoría es la única que la usa: el resto puede probarse con el doble de la 020.
- **Conflictos con la 038** (en pausa): `system.md`, nginx, `public/fonts/`, `AGENTS.md` y la spec 001. Los puntos de fusión y lo que se comprueba están en D14.
- **Residuos de P8 declarados** (decisión del usuario, 2026-10-28, `/analyze` A13):
  - **En local**, `MAIL_MAILER=log` escribe el correo completo, con el enlace y su token, en
    `laravel.log`. Se acepta solo en local, con datos ficticios. En producción el mailer es SMTP, y
    los tests usan `array`.
  - **OB4.a:** una `QueryException` al pedir o al restablecer puede dejar el correo en los
    bindings del log. Queda fuera de esta spec: es OB4.a, que sigue pendiente en
    `docs/observability.md`, con su destino en el roadmap.
  - **`error_log` de nginx** (decisión del usuario, 2026-10-28, `/analyze` A28): en los fallos de
    PHP (502/504) escribe la petición y el Referer con el token, y su formato no se puede
    configurar. Se acepta como excepción acotada a P8 (Constitution Check), con destino en la
    rotación y la retención de logs (OB8.b, objetivo 3). La verificación tras el despliegue revisa
    ese log.
- **Riesgos de `docs/security.md` y `docs/observability.md` que esta spec toca de lado:** las sesiones al cambiar credenciales (solo el restablecimiento las cierra; el perfil y el cambio de correo siguen pendientes) y los eventos de cuentas (solo el restablecimiento; el resto, en la 021). Ninguno queda cerrado por esta spec.
