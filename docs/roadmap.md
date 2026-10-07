---
updated: 2026-10-04
---

# Roadmap

**Etapa actual:** MVP (proyecto académico; entrega aproximada: fin de semestre, diciembre de 2026)

Objetivos aceptados por el usuario en `/init` (decisión del usuario, 2026-09-28).

## Objetivos
| # | Objetivo | Criterio de éxito | Spec | Estado |
|---|---|---|---|---|
| 1 | Esquema reproducible y repo sin datos sensibles | Un clon limpio levanta la base completa con `php artisan migrate --seed` sin el volcado, y los tests usan esa base. El historial de git ya no contiene el volcado con datos ni las contraseñas del README. Riesgos: RD2.a, RD2.b, RD2.c, RD2.d, RS6.a, RS6.b, RS6.d; fuera: RS6.c → objetivo 5 | 013 (parte 1) | pendiente |
| 2 | Autorización por alcance y sesiones seguras | Cada acción sobre pacientes, expedientes y citas tiene test por rol (permitido y 403) según la regla confirmada; `/cliente` exige su rol; login y registro limitados. Riesgos: RS1.a, RS1.b, RS1.c, RS1.d, RS1.e, RS2.a, RS2.b, RS3.a, RS3.b, RS4.a, RS4.b, RS4.c, RS5.a, RS5.b, RS5.c, RS9.a, RS9.b, RS10.a, RS10.b, RS11.a, RS13.a, RS13.b, RS14.a | 014 (RS1); [026](specs/026-estados-cita/spec.md) (RS2.a en parte: la cancelación del paciente); [030](specs/030-despliegue-servidor-propio/spec.md) (RS4, liberada en v0.5.0); 015–019 previstas | pendiente |
| 3 | Auditoría, correlación y logs limpios | Cada evento obligatorio de `docs/observability.md` tiene un test que comprueba su registro; toda respuesta lleva `X-Request-Id`; ningún log contiene datos sensibles. Riesgos: OB1.a, OB1.b, OB1.c, OB1.d, OB1.e, OB1.f, OB1.g, OB1.h, OB2.a, OB2.b, OB3.a, OB3.b, OB4.a, OB5.a, OB5.b, OB5.c, OB6.a; fuera: OB4.b y OB4.c → objetivo 5, OB6.b → objetivo 5 | 020 (OB1.a, OB1.c, OB1.g, OB1.h, OB2); [026](specs/026-estados-cita/spec.md) (OB5.a); 021–023 previstas | pendiente |
| 4 | Monolito modular con regla de agenda única | La regla de agenda vive en un solo sitio con pruebas propias, la doble reserva es imposible y una prueba de arquitectura vigila los módulos Agenda, Pacientes, Clínica e Identidad. Riesgos: RS12.a, RS12.b, RS12.c, RS15.a; fuera: RS15.b → objetivo 5 | [025](specs/025-agenda-unica/spec.md) (regla de agenda); [026](specs/026-estados-cita/spec.md) (estados); [027](specs/027-horarios-validos/spec.md) (horarios); 028 prevista (reprogramar); 013 (parte 3) | pendiente |
| 5 | Staging y producción con rollback | Staging y prod desplegados en una plataforma gratuita (ADR), con backup previo, rollback ensayado en staging y checklist de configuración verificado. Riesgos: RD1.a, RD1.b, RD1.c, RD1.d, RD3.a, RD3.b, RD3.c, RD3.d, RD4.a, RD5.a, RD6.a, RD7.a, RS6.c, RS7.a, RS7.b, RS7.c, RS7.d, RS7.e, RS8.a, RS8.b, RS15.b, OB4.b, OB4.c, OB6.b, OB7.a, OB7.b; fuera: RS6.a, RS6.b y RS6.d → objetivo 1, RS15.a → objetivo 4, OB4.a → objetivo 3, OB6.a → objetivo 3 | [030](specs/030-despliegue-servidor-propio/spec.md) (producción en un servidor propio con Cloudflare Tunnel, liberada en v0.5.0 el 2026-10-04: https://dentissa.app); 031 prevista (cabeceras y HSTS, acceso remoto); [032](specs/032-alertas-cifrado-copias/spec.md) (alertas y copias cifradas, draft) | pendiente |
| 6 | Accesibilidad WCAG 2.1 AA de la interfaz | Toda pantalla cumple el sistema de diseño (`docs/design/system.md`) en contraste, foco, tamaño de toque y estados accesibles: DS1 (texto con contraste < 4.5:1), DS2 (foco e indicadores invisibles), DS6 (toque < 44 px) y DS7 (estados, avisos y diálogos accesibles), comprobado en `/review` contra `system.html`; fuera: DS3, DS4, DS5, DS8, DS9, DS10 y DS11 → Pendientes y deuda (se corrigen al tocar cada pantalla) (decisión del usuario, 2026-10-02) | — | pendiente |

<!-- Un objetivo que atiende riesgos (RS/OB/RD) cita **cada** corrección del riesgo: las que
     cubre y las que deja fuera con su destino. Nunca resume un riesgo citando solo su número. -->

## Próxima etapa
Para pasar de MVP a "entrega académica": objetivos 1, 2 y 3 cumplidos, staging desplegado
(objetivo 5) y la constitución aprobada.

## Pendientes y deuda
- Dependencias con vulnerabilidades conocidas (riesgo de dependencias en `docs/security.md`,
  detectado en `/plan 014`): spec propia de actualización antes de cualquier release (decisión
  del usuario, 2026-09-28). Se atiende en la spec [024](specs/024-dependencias-seguras/spec.md),
  sin objetivo propio (decisión del usuario, 2026-09-29). Hecho: liberada en v0.1.0 (2026-10-02);
  RS16 mitigado. Avisos posteriores (EX1 y RS17): spec
  [029](specs/029-dependencias-al-dia/spec.md), sin objetivo propio. Hecho: liberada en v0.4.1
  (2026-10-03); EX1 retirada y RS17 mitigado.
- 024/R8: la evidencia de T022 no registra que `integrity` y `resolved` coincidan con el registro
  (comprobado en `/review`: coinciden las 48). En próximas specs de dependencias, compararlos con
  `npm view <p>@<v> dist.integrity dist.tarball` (/review 024, aceptado sin tarea).
- 020 antes de `/release 020` (plan de la 024, Rollout pasos 4 y 5): reconstruir `feature/020-auditoria-base`
  sobre `main` sin sus copias de los commits de la 024 y comprobar que no cambian `docs/specs/024-dependencias-seguras`,
  la fila 024 de `docs/specs/README.md` ni su enlace aquí; después, que el merge de la 024 es ancestro del commit
  que se libera y que `composer audit --ignore-severity=low --ignore-severity=medium` y
  `npm audit --audit-level=high` terminan con código 0, es decir, 0 críticas y 0 altas (decisión del
  usuario, 2026-09-29; comando de la spec 024, D10).
- EX1 (`docs/security.md`, spec 024, D10): actualizar `laravel/framework` a ≥ 12.69.0 y
  `league/flysystem` a ≥ 3.35.3 antes del 2026-10-31. Al vencer, el usuario decide si se actualizan
  en una spec propia o se renueva EX1 con fecha y aprobación nuevas; lo comprueba el primer `/plan`
  o `/release` posterior al 2026-10-31 (decisión del usuario, 2026-09-29). Hecho: spec 029 en
  v0.4.1 (2026-10-03), con `laravel/framework` v12.69.2 y `league/flysystem` 3.36.0; EX1 retirada.
- Retirar `overrides.rolldown` (fijado en 1.2.9) de `package.json` cuando `rolldown` 1.2.11 o
  posterior lleve al menos 7 días publicada, para que `npm audit fix` pueda volver a actualizarlo
  (spec 024, D2; /review 024 R7).
- `aidlc/spaces/default/intents/260920-monolito-modular/` puede eliminarse cuando el usuario
  revise su migración a la spec 013.
- Aprobar o ajustar las specs inferidas 001–012 (`/specify --edit NNN`).
- Restos del esqueleto de Laravel: `App\Models\User`, tabla `users`, `DatabaseSeeder`,
  `welcome.blade.php`, nombre y README por defecto.
- Formatear el código existente con Pint en un cambio aislado (nunca se ha ejecutado; deriva generalizada).
- Unificar convenciones de rutas (`/crear` vs `/create`, `/edit` vs `/editar`, nombres con y sin `.index`).
- `Route::resource('servicios')` expone `show` sin método; `ServiciosController@index` ignora el buscador.
- Objetivo de accesibilidad: WCAG 2.1 AA (decisión del usuario, 2026-10-02); principio P9 de la
  constitución y objetivo 6. Sistema de diseño extraído en `docs/design/system.md`.
- Deuda de diseño fuera del objetivo 6 (`docs/design/system.md`, se corrige al tocar cada pantalla;
  decisión del usuario, 2026-10-02):
  - DS3: `--text-muted` vale lo mismo que `--text`; no hay texto secundario.
  - DS4: 861 `style=""` en las vistas, 20 tamaños de letra y la escala de espaciado solo en las reglas de
    la 027; sidebar `#C75078` fuera de los tokens.
  - DS5: 11 bloques `<style>` redefinen `.field`; `citas.css` solo en 7 vistas; `auth.css` redefine tokens.
  - DS8: la app no se adapta a móvil (sin `@media`): a 390 px, 52 de 60 vistas se desbordan; empezar
    por las pantallas del paciente.
  - DS9: Roboto desde Google Fonts sin aprobar en `security.md`; 'DM Sans' y 'DM Mono' sin cargar.
  - DS10: el movimiento no respeta `prefers-reduced-motion`.
  - DS11: Tailwind 4, Vite y `welcome.blade.php` sin uso; clases CSS sin uso.
  - DS12: crear y editar servicio muestran los campos con la fuente del navegador (Arial 13.33px) y el
    desplegable gris.
  - DS13: el campo de hora del horario (110px) recorta "AM/PM" con el formato de 12 h.
  - DS14: emojis mezclados con los iconos SVG en el panel del paciente.
  - DS15: favicon vacío, isotipo sin uso y títulos "Dashboard" por defecto en servicios.
  (DS12–DS15 añadidas en la re-extracción del 2026-10-04, decisión del usuario.)
- `layouts/app.blade.php:244` mete la contraseña generada con `{{ }}` dentro de un `onclick` de
  JavaScript en lugar de `@js()` (P4); riesgo bajo, porque la genera el sistema. Corregirlo en la spec
  que toque usuarios o el layout (upgrade a AI-DD 1.11.0, decisión del usuario, 2026-10-02).
- Deuda de la spec 025 (/review, aceptada sin tarea; liberada en v0.2.0):
  - 025/R8: `SET SESSION innodb_lock_wait_timeout` en `ReservaCitas` deja 5 s fijados en la conexión
    para el resto de la petición; restaurar el valor o usar `SET STATEMENT … FOR` si aparecen procesos
    persistentes.
  - 025/R9: `ReservaCitas::reservar()` convierte `DeadlockException` en no disponible; documentar o
    impedir que se llame dentro de otra transacción.
  - 025/R10: unir los dos tests de bloqueo real de `ReservaConcurrenteTest`. Resuelto en la propia 025
    (T024), sin acción pendiente.
  - 025/R11: extraer los helpers de fixtures de agenda de los 5 tests a un trait común.
  - 025/R12: la búsqueda de solapes empieza en el inicio del día; revisar en la spec 027 si se permiten
    franjas que crucen la medianoche. Resuelta por la spec 027 (CA2): el horario no admite franjas
    que crucen la medianoche (la hora de fin es posterior a la de inicio el mismo día), así que la
    regla de agenda no cambia.
  - 025/R13: el test de la espera de 5 s depende del texto del SQL; comprobar el efecto
    (`@@SESSION.innodb_lock_wait_timeout`) si se pasa a binding.
  - 025/R14: el test de orden debería exigir que el bloqueo sea la primera lectura de la transacción.
  - 025/R16: actualizar en el plan de la 025 (TM2 y Trazabilidad) el nombre del test unido en T024.
  - 025/R17: las migraciones de la spec 013 deben crear `idx_citas_dentista` con ese nombre, porque
    `ReservaCitas` lo fuerza (lo cubre `ReservaConcurrenteTest`).
- Deuda de la spec 030 (liberada en v0.5.0; /review, aceptada sin tarea):
  - 030/R7: el caso de la cookie `Secure` de `ProxyHttpsTest` es de caracterización de
    `session.secure`; en producción lo protege `verificar.sh` (`SESSION_SECURE_COOKIE=true`).
  - 030/R12: las contraseñas candidatas de `verificar.sh` salen del README por patrón; revisarlo al
    cerrar RS6.a (sin contraseñas en el README, 0 candidatas).
  - 030/R13: `dentissa:rotar-credenciales` cambia todas las cuentas con contraseña; limitarlo a las
    del volcado o pedir confirmación por número antes de usarlo con usuarios reales.
  - 030/R14: el desbloqueo pasada la espera solo se prueba en el login (falta en el alta y en Google).
  - 030/R15: `apt-get upgrade`/`apk upgrade` solo en producción (paquetes del sistema distintos de local).
  - 030/R16: ventana entre `tooManyAttempts` y `hit` en el login con peticiones simultáneas.
  - 030/R17: separar `db` en una red `internal` solo con `app`.
  - 030/R18: endurecer tres regex de `ConfiguracionProduccionTest` (`.env` a secas, todas las
    apariciones de `DENTISSA_ENV_DIR`, redirección a nivel de `server`).
  - 030/R20: mover el mensaje común de límite y `origenLimite` fuera del controlador.
  - 030/R21: test del caso `password_hash = ''` del comando de credenciales.
- Incidencias de la liberación de la spec 030 (T097, 2026-10-04/05):
  - El callback de Google responde 500 cuando llega sin un inicio previo
    (`Laravel\Socialite\Two\InvalidStateException` sin capturar en `GoogleAuthController::callback`;
    ocurrió una vez desde una IP de Google). Capturar los fallos de Socialite y volver al login con
    un mensaje, con su test (código previo a la 030; decisión del usuario, 2026-10-05).
  - Documentar en `deployment.md` que, tras cambiar un archivo de `/opt/dentissa/entorno`, hay que
    recrear el servicio con `up -d --force-recreate <servicio>` (y en `app`, `config:cache`): un
    `up -d` no relee el archivo (el token del túnel y las credenciales de Google tardaron por eso).
  - `verificar.sh` debe fallar si `GOOGLE_CLIENT_ID` no tiene formato de id (sigue el marcador de la
    guía), y la guía de entorno debe advertirlo.
  - Las respuestas llevan `x-powered-by: PHP/8.4.25`: `expose_php = Off` en la imagen de producción
    (junto con las cabeceras de la spec 031).
  - Documentar el acceso de administración desde fuera de casa (Tailscale) en `deployment.md` y
    actualizar la fila del SSH en `security.md` (hoy "solo desde la red local") cuando se instale.
- Deuda de la spec 027 (liberada en v0.4.0; /review, aceptada sin tarea):
  - Extraer la regla de franja común a `ReservaCitas` y `HorarioDentista::citasFuera` (hoy
    repetida; los bordes los vigilan `ReglaAgendaTest` y `CitasFueraDelHorarioTest`) → spec 013.
  - 027/R8: comprobar `tipo = dentista` y que la fila exista dentro de la transacción del horario
    (hoy se comprueba antes, fuera de ella).
  - 027/R9: una fila heredada con `disponible = 0` sale marcada en la ficha y se normaliza a 1 al
    guardar; `citasFuera` ya la trata como día sin horario.
  - 027/R11: "Horarios de atención" como `h2` y nombre accesible para el formulario (DS7).
  - 027/R12: llevar el foco al resumen de errores del horario tras el rechazo.
  - 027/R13: texto oculto en los "Ver cita" de la lista de citas fuera (2.4.9, AAA).
  - 027/R14: objetivos de toque de la fila, el campo de hora y "Ver cita" (DS6, objetivo 6).
  - 027/R16: `DB::raw`/`whereRaw` previos de `PersonalController` (P4, código previo).
  - 027/R17: validación del horario en un Form Request y estado de la vista en un view-model.
  - 027/R18: tokens de tamaño propios para la casilla y la etiqueta del día.
  - 027/R19: ayudas de test de la 027 al trait común (junto con 025/R11).
  - 027/R20: excluir `vendor/` de trivy en local (falsos positivos de la documentación de mockery).
- Deuda de la spec 026 (/review, aceptada sin tarea; liberada en v0.3.0):
  - 026/R2: mensaje propio cuando se pide el mismo estado que ya tiene la cita (doble envío, dos
    pestañas), en lugar del mensaje genérico de transición.
  - 026/R3: tests del paciente en el borde exacto de la hora de inicio y con una cita agendada ya
    empezada.
  - 026/R6: dos aserciones fijan las fechas en UTC (deuda D4); actualizarlas con la spec que corrija D4.
  - 026/R7: documentar en `CambioEstadoConcurrenteTest` que el bloqueo real llega como
    `DeadlockException` (transacción anidada) y el 1205 de producción solo lo cubre el test simulado.
  - 026/R8: limpieza robusta en `CambioEstadoConcurrenteTest` si `dentissa_test` conserva el trigger.
  - 026/R9: quitar la rama `cliente → admin` del helper `cambiar` de `EscenarioEstadosCita`.
  - 026/R11: derivar los destinos del admin de `CambiarEstadoCita` de `EstadosCita::TRANSICIONES`.
  - 026/R12: `conPuedeCancelar` y `aviso_cancelar` deben usar la regla del servicio.
  - 026/R13: una sola fuente para el texto de "aún no empieza" (`avisoDeEstado` y `antesDeTiempo`).
  - 026/R14: etiquetas de estado fuera de la clase de excepción `CambioDeEstadoNoPermitido`.
  - 026/R15: `autorizadoParaCita` (show y store) al Gate `gestionar-cita`, con comparación estricta,
    en la 014.
  - 026/R16: evitar consultas de alcance repetidas en `EstadosCita::disponibles()`.
  - 026/R17: `gestionar-cita` es redundante en `CitasController::destroy`.
  - 026/R18: excepción de dominio en lugar de `NotFoundHttpException` en `EstadosCita`.
  - 026/R19: validar `CLINICA_ZONA_HORARIA` al arrancar (hoy un valor inválido da 500).
  - 026/R21: corregir a `A06:2025` las categorías OWASP de TM3, TM4 y TM8 en el plan de la 026.
  - 026/R22: asociar el error de `estado` al grupo de radios (`aria-describedby`) y llevarle el foco.
  - 026/R23: unificar el patrón del error de `estado` con el resto de formularios de citas.
  - 026/R24: contraste de "(ahora: X)" en la `legend` del formulario de estado.
  - 026/R25: llevar los estilos en línea del formulario de estado a `citas.css`.
  - 026/R27: el límite de 500 caracteres del motivo cuenta distinto los saltos de línea en el
    navegador y en el servidor; normalizar antes de validar.
- Enmienda MINOR de P2 (constitución): distinguir los cambios de comportamiento, que exigen un test
  que falle antes, de los tests de caracterización, que se comprueban rompiendo el código que
  protegen. Motivo: P2 necesitó excepción en la spec 024 (CA3–CA7) y en la 025 (CA2, CA3, CA6, CA7)
  (spec 025, D9; decisión del usuario, 2026-09-29). Hecho: constitución 1.2.0 (2026-10-02, spec 027).
- Auditoría del alta de citas con notas (dato de salud) → spec 022. Excepción P7 de la spec 025:
  la 025 no cambia qué notas se guardan ni quién (D9; decisión del usuario, 2026-09-30).
- Auditoría del 403 del alta de citas del personal (`autorizadoParaCita`) → spec 020, registro
  central de accesos denegados (OB1.c). Excepción P7 de la spec 025 (D9; decisión del usuario,
  2026-09-30).
- Gate o Policy del alta de citas del paciente (`POST /cliente/agendar`) y rechazo por rol en
  `/cliente/*` → spec 015. Excepción P3 de la spec 025. El alta del personal recibe su Gate en la
  014 (D9; decisión del usuario, 2026-09-30).
- Residuo de SQL con bindings en el log: cualquier `QueryException` de `ReservaCitas` (bloqueo,
  lecturas o insert) distinta de 1205/1213 sigue el reporte por defecto y registra el SQL con sus
  valores, notas incluidas → spec 023 (OB4.a). Excepción P8 de la spec 025 (D9, A38; decisión del
  usuario, 2026-09-30).
- Al retomar `/plan 014 --fix`: su plan añade el Gate `agendar-para-paciente` y el alcance del
  paciente sobre el `CitasController@store` que deja la 025, que ya delega la regla en
  `App\Agenda\ReservaCitas` (spec 025, D7; decisión del usuario, 2026-09-29).
- Límite de peticiones en las rutas de escritura, empezando por el alta de citas (TM6 de la spec
  025: reservas del mismo dentista esperan en serie hasta 5 s y `/cliente/agendar` no tiene
  límite) → spec propia (spec 025, D13; decisión del usuario, 2026-09-30).
- Spec 013 (prueba de arquitectura, CA10): debe permitir que `App\Agenda\ReservaCitas` bloquee la
  fila de `personal` (tabla de Identidad) o migrar ese acceso a una interfaz de Identidad para
  bloquear a un dentista (spec 025, D11, A27).
- Comparaciones de fecha que siguen en UTC aunque las citas guardan la hora local de la clínica:
  paneles, próximas y pasadas del paciente, `after_or_equal:today` del alta y fechas del historial
  de la cita, mostradas sin convertir. La spec 026 solo usa `config('clinica.zona_horaria')` para
  decidir si una cita ya empezó (spec 026, D4; decisión del usuario, 2026-10-02).
- Al retomar la 014: debe incorporar con `/specify --edit 014` el Gate del detalle de cita del
  personal (`GET /{rol}/citas/{id}`), y en `/plan 014 --fix` partir de `App\Autorizacion\AlcanceClinico`
  ya creado (con `personalId` y `dentistasDelAlcance`), de los Gates `gestionar-cita` y
  `cambiar-estado-cita` en `AppServiceProvider` y de que `CitasController` ya delega en él su
  alcance (spec 026, D3, A11, A23).
- Excepción P3 de la spec 026: el detalle de cita del personal recibe su Gate en la 014, y "Mis
  citas" y el detalle del paciente, `role:cliente` en la 015 (spec 026, D11; decisión del
  usuario, 2026-10-02).
- Pregunta abierta de la spec 013 sobre `trg_historial_cita`: resuelta. La spec 026 lo retira con
  una migración y el historial lo escribe la aplicación, con autor (spec 026, D2; decisión del
  usuario, 2026-10-02).
- Residuos P7 de la spec 026: el cambio de estado y su motivo quedan en el historial de la cita,
  pero sin evento de auditoría formal → spec 022; los 403 de los Gates `gestionar-cita` y
  `cambiar-estado-cita` no se registran → spec 020 (D10; decisión del usuario, 2026-10-02).
- Residuo P8 de la spec 026: una `QueryException` de `EstadosCita` distinta de 1205/1213 sigue el
  reporte por defecto y registra el SQL con sus valores, motivo incluido → spec 023 (D10;
  decisión del usuario, 2026-10-02).
- Requisito de tiempo de la spec 026 (tests en menos de 10 s en local): sus 46 tests tardan unos
  12 s en Docker Desktop sobre Windows (unos 0,24 s por test más la espera de 1 s del test de
  bloqueo). Medirlo en el CI de Linux antes de decidir si se ajusta el límite o los tests
  (decisión del usuario, 2026-10-02).
- Re-extraer el sistema de diseño con el método de AI-DD 1.11.2 (`/init --upgrade --redo-design`).
  Hecho el 2026-10-04: `system.md` 1.2.0 con 60 vistas capturadas a 390 y 1440 px, deuda conciliada
  (DS1–DS11 vigentes, DS12–DS15 nuevas) y la versión anterior en `docs/design/history/`.
- Excepción P7 de la spec 030: los logins fallidos y los rechazos por límite de intentos no se
  registran como eventos de auditoría hasta la spec 020; el login y la vuelta de Google ya emiten
  `Illuminate\Auth\Events\Lockout` en cada rechazo para que la 020 los audite sin tocar el login
  (spec 030, /analyze A4; decisión del usuario, 2026-10-03).
- Residuo de OB4.a aceptado al abrir producción con la spec 030: los errores de base de datos pueden
  dejar SQL con sus valores en `laravel.log` del servidor. Se acepta con datos ficticios; la spec 023
  (OB4.a) debe liberarse antes de tener datos reales (spec 030, /analyze A26; decisión del usuario,
  2026-10-03).
- La spec 020 debe añadir su servicio `scheduler` (purga de la auditoría) también a
  `docker-compose.prod.yml` y probar sus triggers con el usuario de base `dentissa_app`, sin root
  (spec 030, /analyze A26).
- Staging (objetivo 5): RD1.d (restauración probada en staging), RD3.d (TLS en staging), RS7.e
  (configuración de staging separada), OB4.c (checklist de staging) y OB7.b (logs de nginx en
  staging), creadas al dividir las correcciones que la spec 030 cumple en producción (/analyze A1).
- RS13.b (objetivo 2): mensaje genérico en el alta que no revele si el correo ya tiene cuenta; la
  spec 030 solo añade el límite de intentos (RS13.a) (/analyze A34).
- RS18.a: refrescar las imágenes fijadas (`php`, `mariadb`, `cloudflared` y las demás) a las más
  nuevas con 7 días o más y volver a escanear con `trivy image` antes del 2026-10-31, cuando vence
  la excepción EX2; renovarla o retirarla según el resultado (spec 030, T036; decisión del usuario,
  2026-10-04).
- Entorno local: la base `dentissa` de un equipo creado antes de la spec 030 cargó el volcado a
  medias (la colación de la base cortaba la carga en `horarios_dentista`) y le faltan
  `fk_horario_personal`, `fk_pacientes_usuario` y `fk_personal_usuario`. Para repararla hay que
  recrear su volumen (`docker compose down -v` borra los datos locales) (spec 030, T034).
- En la lista de pacientes de la dentista, el botón "Nuevo paciente" apunta a
  `asistente.pacientes.create`, así que la dentista recibe 403 (`resources/views/pacientes/index.blade.php:5`;
  detectado en la re-extracción del diseño, 2026-10-04).
