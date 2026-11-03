---
result: fail
round: 1
mode: full
constitution_version: 1.3.0
date: 2026-10-28
spec_sha: 8456cad72e93
plan_sha: a1d0fa699542
tasks_sha: 4faaea11d967
---

# Análisis · 041 Recuperar contraseña

## Resumen
Resultado **fail**.
- Hay un hallazgo crítico: el diseño aprobado (`c7`) usa tokens que solo existen en el
  `system.md` 1.8.0 de la rama de la 038, en pausa. `main` está en 1.6.0. Además, la 038 retira
  `--sombra-tarjeta-alta`, que `c7` usa, y las dos specs reclaman la versión 1.9.0 (A1).
- Ocho hallazgos altos piden corregir el plan y las tareas antes de implementar:
  - relación con la 038 (A2);
  - sesiones sin versión (A3);
  - limitador de fijar contraseña (A4);
  - envío del aviso sin test (A5);
  - token en el Referer del log (A6);
  - P9 en los correos (A7);
  - literales de `acceso.css` (A8);
  - control TM10 de la 020 (A9).
- Análisis completo, la primera ronda, hecho por un subagente independiente (perfil `critical`,
  `opus`). Viabilidad comprobada contra el `PasswordBroker` instalado.
- Validador: 0 errores.
- El usuario decidió el 2026-10-28 las cuestiones A1/A2, A7, A13 y A17 (ver «Decisiones del usuario»).

Conteo: CRÍTICA 1 · ALTA 8 · MEDIA 9 · BAJA 5

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 12 / 12. Con huecos parciales en CA7 (el envío del aviso no tiene test, A5) y CA9 (la espera al fijar no tiene pantalla ni test, A4) |
| Amenazas con control y test | 12 / 12 nominal, 10 / 12 sin reservas. TM6 no cubre el Referer (A6); el test de TM10 no es viable tal como está escrito (A11) |
| Principios de la constitución evaluados | 10 / 10 (P9 ❌ por A1, A7 y A8; el resto ✅ con los huecos citados) |

## Seguimiento de rondas anteriores
Primera ronda: no hay rondas anteriores.

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A1 | Inconsistencia / Constitución (P9) | CRÍTICA | `design.md` frontmatter (`system_version: 1.8.0`); `plan.md` → Cambios por módulo, fila `system.md`; T001, T024 | `c7` usa tokens que no están en el `system.md` de `main` (1.6.0): `--sombra-tarjeta`, `--sombra-tarjeta-alta`, `--radius-pill`, `--green-texto`, `--focus-ring-inverso`, `--duration-exito`, `--font-publica`, `--text-publico*`, `--leading-publico`, entre otros, todos de la 038 (rama `feature/038-pagina-inicio`, sin fusionar). T001 solo añade los tokens propios de la 041 y fija «1.9.0», versión que el plan de la 038 también reclama. La ronda 5 de la 038 retira `--sombra-tarjeta-alta`. Implementar T024 siguiendo `c7` usaría tokens fuera del sistema. | `/plan 041 --fix A1` y `/tasks 041 --fix A1`, según la decisión del usuario (041 independiente). T001 lleva a `system.md` cada token que `c7` usa y no está en `main`, con su valor y su motivo, y la versión se fija al fusionar. Test: `test_los_tokens_del_acceso_son_los_de_system_md` (T017) contra el `system.md` de la rama, y ningún `var(--x)` de `acceso.css` sin token en `system.md` |
| A2 | Duplicación y conflicto | ALTA | `plan.md` → Riesgos y mitigaciones, D12; T003, T035, T040 | La 038 toca los mismos contratos y el plan no declara esa relación: un `map` en contexto `http` de `dentissa.conf` y `dentissa.prod.conf`, `public/fonts/` (que pasa a ruta protegida), `AGENTS.md` («dos layouts»), `system.md` y la spec 001 (CA4). D12 cuenta `acceso` como «tercer layout», contando el de la 038. | `/plan 041 --fix A2`: una sección con la relación con la 038, sus puntos de fusión y lo que se comprueba tras fusionar (`nginx -t`, `ConfiguracionProduccionTest` y `CabecerasSeguridadTest` en verde con los dos `map`). D12 sin dar por hecho el layout de la 038 |
| A3 | Inconsistencia (plan contra CA7) | ALTA | `plan.md` → Cambios por módulo (`SesionVigente`) y Rollout → Compatibilidad; T015, T032 | Una sesión sin versión guarda la versión *actual*. Si entre el despliegue y su siguiente petición hubo un restablecimiento, esa sesión sobrevive, en contra de CA7 («todas las sesiones») y de TM8. El plan acepta ese residuo sin decisión del usuario. | `/plan 041 --fix A3` y `/tasks 041 --fix A3`: CA7 se cumple también para las sesiones abiertas antes del despliegue. Test: una sesión sin versión, un restablecimiento de esa cuenta y la siguiente petición redirige a `/login`; una sesión sin versión de una cuenta sin restablecer sigue abierta |
| A4 | Ambigüedad / Viabilidad (CA9, CA4) | ALTA | `plan.md` → Contratos y datos (GET y POST con `throttle:fijar-contrasena`), Cambios por módulo (`AppServiceProvider`); T028; `design.md` (la espera solo en «pedir») | El limitador comparte contador entre el GET y el POST: cada error gasta 2 intentos (el POST y el GET de vuelta), y al tercer envío ya hay bloqueo, en contra de los «5 envíos» de CA9 y de CA4. La respuesta `back()` en un GET limitado vuelve a la misma URL limitada, en bucle. Tampoco hay estado de espera diseñado en «contraseña nueva». | `/plan 041 --fix A4` y `/tasks 041 --fix A4`: definir qué peticiones cuentan (CA9 dice «envíos») y qué ve quien fija la contraseña al pasar el límite. Tests: 5 POST permitidos, el sexto muestra la espera sin bucle, y el GET del enlace tras el límite responde sin redirigir a sí mismo |
| A5 | Cobertura (CA7) | ALTA | `plan.md` → Trazabilidad CA7; T011, T014, T016, T029 | Ningún test comprueba que un restablecimiento completado envíe `ContrasenaCambiada` a la cuenta. T016 solo renderiza el `Mailable`. | `/tasks 041 --fix A5`. En T011: tras restablecer, se envía `ContrasenaCambiada` al correo de la cuenta. En T014: con el almacén de la 020 fallando, no se envía |
| A6 | Seguridad (TM6, TM7, P8) | ALTA | `plan.md` → Modelo de amenazas TM6/TM7, D7; T018, T029; `docker/nginx/seguridad/comunes.conf:13` | `combined` guarda `$http_referer` y el `map` solo enmascara `$request`. Con `Referrer-Policy: strict-origin-when-cross-origin`, las peticiones al mismo origen desde la página del token (CSS, fuente, POST) llevan la URL con el token si el `<meta name="referrer">` no va antes que cualquier recurso. «Este enlace ya no sirve» también se sirve en la URL del token y no lleva el meta. | `/plan 041 --fix A6` y `/tasks 041 --fix A6`: ninguna línea del log de acceso contiene el token, ni en la petición ni en el Referer. Tests: T018 aplica el formato a una línea con Referer `/contrasena/nueva/<token>` y obtiene `***`; T017 comprueba que toda respuesta de la URL del token lleva `no-referrer` antes de cualquier `<link>` |
| A7 | Constitución (P9) | ALTA | `plan.md` D9; T020; `docs/constitution.md` → P9 | La hoja de los correos lleva literales copiados de los tokens, sin decisión registrada. El test solo cubre los colores: tamaños, espaciados y radios quedan sin verificar. | Decisión del usuario (2026-10-28): se copian los valores de los tokens, sin excepción. `/plan 041 --fix A7` la registra en D9 y `/tasks 041 --fix A7`. Test: cada valor de la hoja (color, tamaño, espaciado, radio) es el de un token de `system.md` |
| A8 | Constitución (P9, verificación) | ALTA | `plan.md` → Constitution Check P9; T017, T024 | La prohibición de literales fuera de `:root` en `acceso.css` solo se revisa a mano en T024. | `/tasks 041 --fix A8`. Test en T017: falla si `acceso.css` tiene un literal de color, tamaño, espaciado, radio, sombra o duración fuera de `:root` |
| A9 | Conflicto con la 020 / Seguridad | ALTA | `plan.md` D2 y fila `Auditoria.php`; plan de la 020 → TM10 (actor «nunca de la petición»); T002 | `?Usuario $actor` cambia el control TM10 de la 020: en la 041 el actor sale de `cuenta` (validado por el token), y hoy `actor_rol` sale de `Auth::user()`. El plan no dice qué debe cumplir `/plan 020 --fix`. | `/plan 041 --fix A9`: D2 dice qué debe cumplir el contrato. `/plan 020 --fix` actualiza TM10 (quién puede pasar el actor y que el rol salga del actor) con su test. T002 comprueba que `registrar(actor:)` guarda el rol de ese actor y que los tests de TM10 de la 020 siguen en verde |
| A10 | Viabilidad / Cobertura | MEDIA | `plan.md` D8; T004, T010, T026; `phpunit.xml` | Las rutas solo se registran con el interruptor encendido al arrancar. Por defecto está apagado y ninguna tarea lo enciende en `phpunit.xml` ni en la CI: los tests de rutas darían 404. | `/tasks 041 --fix A10`: la suite corre con el interruptor encendido y el caso «apagado» recrea la aplicación con él apagado (T004, T010) |
| A11 | Viabilidad / Constitución (P2) | MEDIA | TM10; T011 `test_sin_token_csrf_se_rechaza`; T026 | `ValidateCsrfToken` no actúa durante los tests (`runningUnitTests`): el test no puede obtener 419. CSRF, `no-store` y la vista previa solo en local ya se cumplen, pero falta la comprobación rompiendo que exige P2. | `/tasks 041 --fix A11`: un test que active de verdad la verificación CSRF obtiene 419 y la contraseña no cambia. T026 anota «comprobado rompiendo» para TM10, TM11 y D11 |
| A12 | Viabilidad (CA8) | MEDIA | T012 `…_la_respuesta_tarda_lo_mismo_con_y_sin_cuenta` | 20 solicitudes desde un mismo origen superan el límite de 5 por minuto: desde la sexta todas reciben la espera y la medición no compara el caso real. | `/tasks 041 --fix A12`: las 20 respuestas son redirección a `contrasena.enviado` (se limpia el limitador o se varía el origen) |
| A13 | Seguridad (P8) / Cobertura de riesgos | MEDIA | spec → RNF «Registros técnicos»; TM6; `.env.example` (`MAIL_MAILER=log`); `docs/observability.md` OB4.a | En local, el mailer `log` escribe el correo completo (con el enlace y su token) en `laravel.log`. Con OB4.a pendiente, una `QueryException` en los dos flujos puede dejar el correo en los bindings del log. El plan no declara ninguna de las dos cosas. | Decisión del usuario (2026-10-28): residuo declarado. `/plan 041 --fix A13`: en «Riesgos», el mailer `log` se acepta solo en local (datos ficticios), y la `QueryException` queda fuera, con destino en OB4.a |
| A14 | Inconsistencia | MEDIA | `design.md` → Resultado (pendientes del evaluador); `plan.md` → Contratos (pendientes); T021 | El pendiente es la **dirección de respaldo** (la URL completa del enlace, partida en líneas), no la dirección postal de la clínica, como dice T021. | `/tasks 041 --fix A14`. Test (T016): las versiones HTML y de texto llevan el enlace de respaldo completo (`APP_URL`, token y `cuenta`) |
| A15 | Ambigüedad | MEDIA | CA7, K8; `plan.md` → Cambios por módulo (correos); T016, T022; `config/clinica.php` | No se define de dónde salen el teléfono y los datos de la clínica que muestran los correos y el talón. Hoy solo existe `clinica.zona_horaria`; el teléfono real está escrito en `landing/index.blade.php:266`. | `/plan 041 --fix A15`: el origen del teléfono (y del horario, si se muestra). Test (T016, T017): el valor configurado aparece en el aviso y en el talón |
| A16 | Seguridad (`agent-security.md` §2) | MEDIA | `plan.md` D9 y P6 | Usar directamente `CssToInlineStyles` (`tijsverkoyen/css-to-inline-styles`, dependencia transitiva de Laravel) cuenta como dependencia nueva sin declarar ni verificar. El plan dice «sin dependencias nuevas». | `/plan 041 --fix A16`: declararla y verificarla (§2), o hacer el inlining con el mecanismo de Mail de Laravel sin importar la clase. Lint: `composer audit --locked` en T042 |
| A17 | Cobertura de riesgos (SE2.a) | MEDIA | `plan.md` → Descubribilidad (fila `/login`); T033, T040; `docs/seo.md` SE2.a | El login pasa al layout de acceso, que emite `noindex`: SE2.a queda hecha de hecho, pero el plan y T040 dicen «sin cambio de estado». | Decisión del usuario (2026-10-28): SE2.a entra. `/plan 041 --fix A17` y `/tasks 041 --fix A17`. Test: `/login` lleva `noindex`. T040 cambia el estado de SE2.a según cubra o no `/registro/contacto` (si queda fuera, «atiende SE2.a parcialmente») |
| A18 | Seguridad (ruta protegida) | MEDIA | T040; `shared/agent-security.md` (`AGENTS.md` fuera de `/init`) | `AGENTS.md` es ruta protegida y T040 no pide aprobación explícita. | `/tasks 041 --fix A18`: T040 pide la aprobación del usuario antes de editar `AGENTS.md` |
| A19 | Duplicación y conflicto (020) | BAJA | `plan.md` → Cambios por módulo (`SesionVigente`, listener de `Login`); plan de la 020 (`ContextoActor` al final del grupo `web`) | No se fija el orden entre `ContextoActor` y `SesionVigente` en el grupo `web`, ni cómo conviven los dos listeners de `Login`. | Nota de T031 y T032 (propuesta de aceptación) |
| A20 | Seguridad (`agent-security.md` §2) | BAJA | `plan.md` D10; T003 | La verificación de Roboto no anota la fecha de publicación de la 5.2.5 (antigüedad mínima de 7 días). | Nota de T003 (propuesta de aceptación) |
| A21 | Seguridad | BAJA | `plan.md` TM11; Contratos de `POST /contrasena/olvidada` | `no-store` no evita que la URL con un token vigente quede en el historial del navegador, y el correo escrito queda en la sesión (driver `file` sin cifrar, RS10.c). Ninguna de las dos cosas está en el modelo de amenazas. | `/plan 041 --fix A21`: declarar los dos residuos con su control o su motivo |
| A22 | Inconsistencia (RS11) | BAJA | `plan.md` P4 (`min:8`); `RegisterController.php:32`, `PerfilController.php:79` (`Password::min(8)`) | La contraseña nueva usa `min:8` en texto, no la regla del registro y del perfil. | Nota de T029: la misma regla que el registro (`Password::min(8)`); `test_valida_cada_campo` (propuesta de aceptación) |
| A23 | Inconsistencia (redacción) | BAJA | `spec.md` (Criterios de diseño: presupuesto 5, «K1–K11»); `design.md` (12/12 puertas con 13 nombres); `plan.md` D3 (ADR «proposed»); nombres de ruta y método (`contrasena.restablecer` → `restablecerForm`, `contrasena.guardar` → `restablecer`, `/enviada` frente a `enviado`) | Pequeñas incoherencias de texto y de nombres entre los artefactos. | `/plan 041 --fix A23` para D3 y los nombres; en la spec, nota en su Historial al liberar (propuesta: → spec) |

Descartado al consolidar: que los supuestos de `/specify` necesitaran otra confirmación. El
usuario aprobó la spec el 2026-10-27 con esas filas a la vista, y el contrato las deja ahí
precisamente para esa revisión.

## Decisiones del usuario
Decididas el 2026-10-28:
- **A1/A2** → la 041 es independiente de la 038. T001 lleva a `system.md` de `main` todos los
  tokens que usa `c7`, incluidos los que vienen de la 038, y la versión se fija al fusionar. El plan
  declara la relación con la 038 y sus puntos de fusión.
- **A7** → los correos copian los valores de los tokens, con un test de todos los valores. Sin
  excepción a P9.
- **A17** → SE2.a entra en la 041: `noindex` en `/login`, con test.
- **A13** → residuo declarado: el mailer `log` solo en local; la `QueryException`, en OB4.a.

## Aceptados
Aceptados por el usuario el 2026-10-28:
- **A19** → nota de T031, T032: fijar el orden de `SesionVigente` respecto a `ContextoActor` de la 020 en el grupo `web` y comprobar que los dos listeners de `Login` conviven; los tests de correlación de la 020 siguen en verde.
- **A20** → nota de T003: anotar la fecha de publicación de `@fontsource-variable/roboto` 5.2.5 (antigüedad mínima de 7 días).
- **A22** → nota de T029: la contraseña nueva usa `Password::min(8)`, la misma regla que el registro y el perfil; lo comprueba `test_valida_cada_campo`.
- **A23** → spec: las incoherencias de redacción de la spec (presupuesto 5 frente a 7, «K1–K11» frente a K12) se anotan en su Historial en `/release`; las del plan van en `/plan 041 --fix A23`.
