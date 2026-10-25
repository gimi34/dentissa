---
result: fail
round: 2
mode: full
constitution_version: 1.3.0
date: 2026-10-25
spec_sha: 7ba3fdfb034e
plan_sha: 5f2a4691d644
tasks_sha: 24c93503edec
---

# Análisis · 032 Alertas y copias cifradas en producción

## Resumen
Resultado **fail**, por un hallazgo alto que el usuario decide corregir.

- **A29:** la segunda pasada de `restaurar.sh` borra la base antes de garantizar el descifrado. Si
  la caché del `gpg-agent` (600 s) ha caducado, la frase se pide con la base ya vacía.
- **Ronda 1:** de A1–A28, 20 quedan resueltos y 8 resueltos con efecto nuevo (A29–A39).
- **Correcciones:** el usuario decide corregir en el plan y las tareas los hallazgos que cambian el
  diseño (A29, A32, A33, A34, A36, A37) y dos de fuente (A38, A46). Los demás quedan aceptados
  como notas de sus tareas.
- **Modo:** completo, porque cambió más del 40 % de las líneas (795) desde la ronda 1. Lo hizo un
  subagente independiente (perfil `critical`, `opus`). A33 se comprobó en
  `app/Providers/AppServiceProvider.php:49-52`.

Ronda anterior: [history/analysis.r1.md](../history/analysis.r1.md).

Conteo: CRÍTICA 0 · ALTA 1 · MEDIA 9 · BAJA 10

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 10 / 10 |
| Amenazas con control y test | 15 / 15 (TM13 solo con verificación manual) |
| Principios de la constitución evaluados | 10 / 10 (P7 mal marcado: A37) |

## Seguimiento de rondas anteriores
| ID | Severidad | Estado | Evidencia |
|---|---|---|---|
| A1 | CRÍTICA | resuelto con efecto nuevo (→ A29) | D3 `--volver` con `volver.sh --sin-levantar`; T017, T044. La frase única depende de la caché del agente |
| A2 | ALTA | resuelto | Contratos (`DENTISSA_APP`), T016 |
| A3 | ALTA | resuelto con efecto nuevo (→ A30) | D12 fuera de `--interna`, T019, T041 |
| A4 | CRÍTICA | resuelto con efecto nuevo (→ A38) | P8 «❌ aceptado» en el plan y en las tareas |
| A5 | ALTA | resuelto | D3, TM4, T016, T044, ADR 0007 |
| A6 | ALTA | resuelto | D9, TM13, T034, T040 |
| A7 | MEDIA | resuelto | TM14, TM15, D8, T011 |
| A8 | MEDIA | resuelto | D2 con `awk` (mawk conserva `$0` en `END`), T015, T041 |
| A9 | MEDIA | resuelto | Contratos, T012 |
| A10 | MEDIA | resuelto | Contratos, D8, T003, T011 |
| A11 | MEDIA | resuelto (detalle → A45) | Contratos, T010, T012 |
| A12 | MEDIA | resuelto con efecto nuevo (→ A33) | D7, T036, T010 |
| A13 | MEDIA | resuelto con efecto nuevo (→ A34) | Contratos, T016, T029, T044 |
| A14 | MEDIA | resuelto con efecto nuevo (→ A31) | Estrategia, T044 |
| A15 | MEDIA | resuelto | Rollout paso 0, D6, T041 |
| A16 | MEDIA | resuelto | D5, D12, T018, T019, T097 |
| A17 | MEDIA | resuelto con efecto nuevo (→ A32) | D4, T037, T017 |
| A18 | MEDIA | resuelto | T014, T026 |
| A19 | MEDIA | resuelto | D14, T033, T040 |
| A20 | MEDIA | resuelto con efecto nuevo (→ A39) | D11, T051 |
| A21 | BAJA | resuelto | T013 |
| A22 | BAJA | resuelto | D1 y ADR 0007 en `accepted` |
| A23 | BAJA | resuelto | Impacto en arquitectura, T090, T035 |
| A24 | BAJA | resuelto con efecto nuevo (→ A35) | T098 |
| A25 | BAJA | resuelto | D15, T051 |
| A26 | BAJA | resuelto | Riesgos, Trazabilidad del RNF |
| A27 | BAJA | resuelto (comentario → A46) | Contratos, TM8 |
| A28 | BAJA | resuelto | D12, T019, T042 |

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A29 | Seguridad | ALTA | D3 (pasada 2, `--volver`), Rollout, T016, T040, T044 | La frase única depende de la caché del `gpg-agent` (600 s desde el último uso). Entre las dos pasadas están la confirmación y, con `--volver`, `git fetch` y `build`. La pasada 2 para servicios y hace `DROP` antes de `gpg --decrypt`: un error o una cancelación de la frase deja la base vacía. Con la entrada vacía, `mariadb` sale con 0. | Requisito: la base no se toca hasta que llegan datos descifrados, y el éxito exige código 0 de gpg y de la carga remota. Tests: caso de texto en T016 y ensayo en T044 con la caché vaciada entre pasadas (la base no cambia). `/plan --fix`, `/tasks --fix` |
| A30 | Inconsistencia | MEDIA | T041 frente a Rollout y T042 | T041 ejecuta `verificar.sh` completo antes de instalar el temporizador (T042): «temporizador activo» da FALLO. | Aceptado → nota de T041 |
| A31 | Ambigüedad | MEDIA | T044, D3 | `restaurar.sh` levanta `app` y `web` al terminar; con producción abierta, la tabla `cache` cambia antes de comparar con la foto. | Aceptado → nota de T044 |
| A32 | Ambigüedad | MEDIA | D4, T037; `deploy/desplegar.sh:94,118-120` | `desplegar.sh` no sabe si `migrate --force` aplicó migraciones, y el plan no dice cómo lo sabrá. | Requisito: la detección es explícita y, en la duda, se propone la vuelta con copia. Test en ConfiguracionProduccionTest. `/plan --fix`, `/tasks --fix` |
| A33 | Cobertura | MEDIA | D7, T010, T020; `AppServiceProvider.php:49-52` | El `Lockout` del limitador `google` no trae `email`, y `sujetoLimite(string)` no admite `null`: podría dar un 500 en la vuelta bloqueada. No está definido cómo se sabe el `metodo` de un `Lockout`. | Requisito: el `Lockout` de Google escribe `bloqueado`/`google` con `cuenta` `null` y la respuesta no cambia. Test en T010 y `LimiteIntentosTest` en verde. `/plan --fix`, `/tasks --fix` |
| A34 | Cobertura | MEDIA | Contratos (mensajes), D3, T016, T029 | Una frase errónea o cancelada sale con 2 sin `NO_SECKEY` y se clasificaría como copia alterada. | Mensaje propio «no se pudo usar la clave». Test en T016 y rechazo con frase errónea en T029. `/plan --fix`, `/tasks --fix` |
| A35 | Inconsistencia | MEDIA | spec 030 CA7; D4, T030, T098 | La 032 cambia el procedimiento de vuelta con copia de 030 CA7 (15 min) sin declararlo. | Aceptado → nota de T098, T044 |
| A36 | Cobertura de riesgos | MEDIA | D8, TM10; `docs/observability.md` OB8.a/OB8.b | Si cambia el formato del log (OB8.a), todas las líneas se descartan y el aviso de 5xx muere en silencio. | Requisito: muchas líneas que no encajan dejan señal (aviso o FALLO). Test en VigilanteTest. `/plan --fix`, `/tasks --fix` |
| A37 | Constitución | MEDIA | Constitution Check P7 (plan y tareas); T036, T020 | La 032 toca el login y los fallos de acceso sin auditoría: P7 aplica y se apoya en la excepción de la 030, pero figura como «➖». | P7 → «❌ aceptado» con la excepción de la 030 (usuario, 2026-10-03, vence con la 021). `/plan --fix`, `/tasks --fix` |
| A38 | Constitución | MEDIA | P8, decisiones del 2026-10-25 | Las decisiones del usuario del 2026-10-25 solo constaban en el análisis r1. | Queda conservado en `history/analysis.r1.md`, que no se borra. El plan cita esa fuente en P8 y en las decisiones. `/plan --fix` |
| A39 | Duplicación | BAJA | D11, T051; plan de la 020 | Ninguna tarea anota la 020, y su `scheduler` es solo de local. | Aceptado → nota de T051 |
| A40 | Inconsistencia | BAJA | Contratos de `volver.sh` | Solo se rechaza un archivo existente; otra opción o una ruta mal escrita se ignorarían. | Aceptado → nota de T017, T030 |
| A41 | Seguridad | BAJA | T042 | «Con una copia en claro» crearía un volcado real en claro. | Aceptado → nota de T042 |
| A42 | Seguridad | BAJA | D9, TM13 | STARTTLS no se exige: un intermediario podría quitarlo y ver la contraseña de aplicación. | Aceptado → nota de T019, T032 |
| A43 | Seguridad | BAJA | D3 (`<ref>`, `DENTISSA_APP` en la orden remota) | Se interpolan sin validar ni citar. | Aceptado → nota de T016, T029 |
| A44 | Seguridad | BAJA | TM12, D8 paso 2 | El canal `seguridad` se lee entero cada minuto, sin tope. | Aceptado → nota de T012, T024 |
| A45 | Inconsistencia | BAJA | Contratos (`"channel":"seguridad"`) | Laravel nombra el logger con el entorno salvo que el canal declare `name`. | Aceptado → nota de T010, T020 |
| A46 | Constitución | BAJA | P8, TM8 | La aceptación de la seudonimización era del agente. | Aceptado por el usuario (2026-10-25): TM8 lo registra con fuente usuario. `/plan --fix` |
| A47 | Ambigüedad | BAJA | RNF «no ralentiza», D7; `desplegar.sh` (sin `event:cache`) | Sin caché de eventos, Laravel explora `app/Listeners` en cada arranque. | Aceptado → nota de T050 |
| A48 | Inconsistencia | BAJA | spec `extends: [030]`; T098 (001 CA9) | La 001 se anota pero no está en `extends`. | Aceptado → nota de T098 |

## Decisiones del usuario
Decididas por el usuario el 2026-10-25:
- Corregir en el plan y las tareas los hallazgos que cambian el diseño (A29, A32, A33, A34, A36,
  A37), más A38 y A46, que solo registran fuentes. Los demás quedan aceptados como notas de sus
  tareas.
- **A46:** se acepta la seudonimización de las huellas (con la `APP_KEY` se recupera una IPv4). TM8
  lo registra con fuente usuario.

## Aceptados
Aceptados por el usuario el 2026-10-25:
- **A30** → nota de T041: el `verificar.sh` completo de T041 anota como esperado el FALLO «temporizador activo», porque el temporizador se instala en T042; el de T042 tiene que salir todo OK.
- **A31** → nota de T044: la comparación con la foto se hace antes de dar tráfico a la aplicación, o se excluyen las tablas volátiles (`cache`, `sessions`, `jobs`) declarándolo; la nota dice cómo.
- **A35** → nota de T098, T044: T098 anota 030 CA7 como modificado por 032 CA9 (vuelta con copia desde el PC); T044 recoge la duración de `restaurar.sh --volver` frente a los 15 min.
- **A39** → nota de T051: la entrada del roadmap dice que la 020 debe añadir el `scheduler` al Compose de producción, mover `dentissa:vigilar` a él y retirar el temporizador.
- **A40** → nota de T017, T030: cualquier segundo argumento distinto de `--sin-levantar` se rechaza antes del checkout, con su caso en el test.
- **A41** → nota de T042: «con una copia en claro» usa un archivo señuelo sin datos llamado `dentissa-señuelo.sql.gz`, que se borra al terminar.
- **A42** → nota de T019, T032: `verificar.sh` exige TLS obligatorio en el envío (puerto 465 con TLS implícito, o 587 con el cifrado exigido en la configuración del mailer), con su caso en el test.
- **A43** → nota de T016, T029: `<ref>` y `DENTISSA_APP` se validan (formato de tag, commit o ruta absoluta sin espacios) o se citan con `printf %q` en la orden remota, con su caso en el test.
- **A44** → nota de T012, T024: la lectura del canal `seguridad` es incremental o tiene tope, con su caso en VigilanteTest.
- **A45** → nota de T010, T020: el canal declara `name` `seguridad`, o el test comprueba el valor real.
- **A47** → nota de T050: se registra el coste de explorar los listeners al arrancar, o se comprueba que `desplegar.sh` hace `event:cache`, y queda anotado en la verificación manual del RNF.
- **A48** → nota de T098: la 001 solo se anota porque su comportamiento no cambia (el canal `seguridad` no es su registro de intentos); no entra en `extends`.
