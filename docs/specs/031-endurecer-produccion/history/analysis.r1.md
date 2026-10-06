---
result: fail
round: 1
mode: full
constitution_version: 1.2.1
date: 2026-10-06
spec_sha: 69a05527ad0a
plan_sha: 2704187d4843
tasks_sha: 3e19af1d9d41
---

# Análisis · 031 Endurecer producción tras la apertura

## Resumen
`fail`. Análisis completo, hecho por un subagente independiente; cada hallazgo se verificó en el
archivo citado. No hay críticos. Hay cuatro altos de ejecutabilidad que se corrigen:
- el orden de los ensayos frente a la sección nueva de Tailscale de `verificar.sh` (A1);
- la vuelta atrás a v0.5.0 con Tailscale activo (A2);
- las cabeceras exigidas en la 301 pública, que genera Cloudflare y no nginx (A3);
- la regla de RS18.a, distinta en el plan y en las tareas (A4).

El usuario resolvió las decisiones pendientes el 2026-10-06. Se corrigen además los medios que
cambian el diseño (A6, A7, A9, A10, A12), y el resto se acepta como notas de tarea.

Conteo: CRÍTICA 0 · ALTA 4 · MEDIA 8 · BAJA 9

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 11 / 11 (CA1–CA5 y CA9 con PHPUnit; el resto, comprobaciones declaradas, constitución 1.2.1) |
| Amenazas con control y test | 13 / 13 (los controles de TM9 y TM11 no eran efectivos tal como estaban escritos: A6, A7) |
| Principios de la constitución evaluados | 9 / 9 |

## Seguimiento de rondas anteriores
Primera ronda: no hay hallazgos previos.

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A1 | Inconsistencia | ALTA | tasks T025, T040, T041; plan D11 y Rollback | La sección «Administración remota» de `verificar.sh` exige `tailscaled` en marcha. Pero T040 (que espera «todo OK») no depende de T041 y no se dice en qué modos corre la sección. El Rollback propone `sudo tailscale down`, tras lo cual toda verificación fallaría. | Fijar en el plan y en T025 en qué modos corre la sección y qué da sin Tailscale. T040 `depende: T041`. El Rollback no usa `tailscale down` como vuelta atrás de la app. Comprobación: la nota de T040 recoge la salida de `desplegar.sh`, `verificar.sh`, `aplicar-entorno.sh` y `volver.sh` en el orden real. → `/plan --fix`, `/tasks --fix` |
| A2 | Inconsistencia (030 CA7) | ALTA | plan Rollback; tasks T040; `deploy/volver.sh:75` | `volver.sh` ejecuta el `verificar.sh` del tag al que vuelve. El de v0.5.0 da FALLO en «nada escucha fuera de localhost salvo SSH» con `tailscaled` escuchando en 100.x. Contradice 030 CA7 sin declararlo. | Decisión del usuario: aceptar ese único FALLO conocido al volver a una versión anterior a la 031 con Tailscale activo. Se declara en la spec (modifica 030 CA7), en el plan y en la guía, y se ensaya en T040 (el resultado de la vuelta atrás es exactamente ese FALLO). → `/specify --edit`, `/plan --fix`, `/tasks --fix` |
| A3 | Seguridad / Inconsistencia | ALTA | tasks T024; spec CA1; `deploy/README.md:62` (Always Use HTTPS) | La 301 pública de `http://` la genera el borde de Cloudflare, sin las cabeceras de nginx, así que `verificar.sh` daría un FALLO falso. | La 301 que genera nginx se comprueba por la red interna con `X-Forwarded-Proto: http` y debe llevar las cuatro cabeceras. Por la dirección pública solo se exigen cabeceras en las respuestas https, y la 301 pública se comprueba como hasta ahora (solo el destino https). El plan aclara que la redirección del borde de Cloudflare no es una respuesta de la aplicación. → `/plan --fix`, `/tasks --fix` |
| A4 | Cobertura de riesgos | ALTA | plan «Estado de las correcciones al liberar»; tasks T031, T098 | El plan da RS18.a como mitigada, y las tareas solo «si EX2 se retira». Si EX2 se renueva, RS18.a queda sin estado. | Una sola regla, coherente con el texto de RS18.a en `security.md`: RS18.a queda mitigada al refrescar, escanear y decidir sobre EX2. Lo que no tenga corrección publicada queda «aceptada (EX2)» con su nuevo vencimiento. Comprobación: la nota de T031 y `security.md` lo reflejan. → `/plan --fix`, `/tasks --fix` |
| A5 | Seguridad | MEDIA | plan D11; tasks T025 | La comprobación «SSH sin contraseña» con `ssh -o BatchMode=yes localhost` puede dar OK falso si falla antes, por la clave de host. Además, solo prueba localhost. | Aceptado como nota (ver Aceptados). |
| A6 | Seguridad | MEDIA | plan TM9, D9; tasks T012, T027; ADR 0006 | `tailscaled` acepta todo lo que entra por `tailscale0` antes que `ufw`, así que la regla de `ufw` no limita nada. | Decisión del usuario: ACL del tailnet que solo deja llegar al servidor por tcp:22 (copiada en la guía). TM9 y el ADR citan la ACL como control real. Comprobación en T041: un puerto de prueba que escucha en el servidor no es alcanzable desde la red privada. → `/plan --fix`, `/tasks --fix` |
| A7 | Seguridad (agent-security §2) | MEDIA | plan D10, TM11; ADR 0006 | La verificación de la dependencia no registra versión ni fecha, y las actualizaciones automáticas no incluyen el origen de Tailscale, así que el control de TM11 no ocurre. | Decisión del usuario: añadir el origen de Tailscale a las actualizaciones automáticas. D10 registra versión y fecha al instalar. Comprobación en T041: `unattended-upgrade --dry-run -d` incluye el paquete. → `/plan --fix`, `/tasks --fix` |
| A8 | Ambigüedad | MEDIA | spec CA11; plan D13; tasks T003 | `composer:2.x` permite cambio menor, contra CA11. En `cloudflared` (versión por calendario) «misma rama» no está definida, y `tag_last_pushed` no es la fecha de primera publicación. | Aceptado como nota (ver Aceptados). |
| A9 | Constitución (P7) | MEDIA | spec Auditoría y Decisiones; roadmap (excepción P7 de la 030); spec 020 (OB1.b → 021) | La excepción dice «hasta la spec 020», pero los eventos de login los registra la 021. | Decisión del usuario: la excepción vence con la 021. Cambia en la spec 031, el plan (P7) y el roadmap. Comprobación: grep de «hasta la spec 020» sin resultados en esos textos. → `/specify --edit`, `/plan --fix` |
| A10 | Duplicación y conflicto | MEDIA | plan «Sin solapes»; spec 020 (servicio `scheduler` con `app.env`) | La 020 añadirá un servicio con `app.env` que D7 y D8 no cubrirían, con su lista fija de servicios. | D7 y D8 toman del Compose todo servicio con `env_file`, no una lista fija; el plan declara el solape y el orden con la 020. Comprobación: la prueba de concepto de T002 con un servicio extra. → `/plan --fix`, `/tasks --fix` |
| A11 | Constitución / agent-security §4 | MEDIA | tasks T022, T029, T030 | `docker-compose.yml` y `Dockerfile` son rutas protegidas (`.ai/project.yaml`) y las tareas no piden aprobación. | Aceptado como nota (ver Aceptados). |
| A12 | Cobertura (observabilidad) | MEDIA | plan D5, Observabilidad, TM7 | Al capturar los fallos de Google sin rastro, los errores de configuración (como el `invalid_client` de la 030) dejarían de verse. | Decisión del usuario: los errores del proveedor (`GuzzleException`) se siguen reportando, sin datos personales. La vuelta sin inicio o cancelada no se registra. Test: un `ClientException` se reporta una vez y el estado inválido no se reporta. → `/plan --fix`, `/tasks --fix` |
| A13 | Inconsistencia | BAJA | tasks T029, T030 `[P]` | El criterio de T030 («`ConfiguracionProduccionTest` completo») depende también de T029. | Aceptado como nota. |
| A14 | Constitución (P2) | BAJA | tasks T011 | Dos casos podrían pasar hoy (el límite cuenta antes del controlador; la excepción salta antes de vincular). | Aceptado como nota. |
| A15 | Seguridad / Ambigüedad | BAJA | plan D7; tasks T002, T025 | La prueba de concepto no cubre un `$` literal ni una clave borrada, y no se exige que los valores no pasen por argumentos de proceso. | Aceptado como nota. |
| A16 | Ambigüedad | BAJA | spec Decisiones (HSTS 180 días como `supuesto`) | Fue decisión del usuario (2026-10-06). | Se corrige al editar la spec por A2 y A9 (fuente `usuario`). → `/specify --edit` |
| A17 | Cobertura de riesgos | BAJA | `docs/deployment.md` («RS15.b → spec 031»); ADR 0005 Consecuencias; tasks T091 | Las alertas y el cifrado ahora van a la 032. | Aceptado como nota. |
| A18 | Inconsistencia | BAJA | plan, contrato de la CSP (`fonts.bunny.net`) | Bunny solo lo usa `welcome.blade.php`, que no tiene ruta. | Aceptado como nota. |
| A19 | Cobertura | BAJA | tasks T004 | Sin `cubre:`; la corrección de npm viene de P6 y D12, no de un criterio. | Aceptado como nota. |
| A20 | Cobertura | BAJA | tasks T041 | Faltan dos pasos: un dispositivo fuera del tailnet no llega a la IP 100.x, y el comprobador externo no mira IPv6. | Aceptado como nota. |
| A21 | Seguridad | BAJA | tasks T027 | `tailscale up` cambia por defecto el DNS del servidor (`--accept-dns`), lo que puede afectar a la resolución de los contenedores. | Aceptado como nota. |

Controles que el revisor comprobó y son correctos:
- la herencia de `add_header` con `include` en el `location` de estáticos;
- la 301 del `if` de nivel `server`, que hereda las cabeceras;
- la CSP, que no rompe nada de lo actual, porque `'unsafe-inline'` cubre scripts, manejadores y estilos y no hay imágenes externas ni `fetch`;
- la captura en la vuelta de Google, que cubre la cancelación, el estado caducado o manipulado y un `code` manipulado;
- el patrón de D6, que detecta los marcadores de la guía.

## Decisiones pendientes del usuario
Ninguna: resueltas el 2026-10-06 (A2, A6, A7, A9, A12 y el bloque de aceptados).

## Aceptados
Aceptados por el usuario el 2026-10-06:
- **A5** → nota de T025, T041: la comprobación «SSH sin contraseña» falla en cerrado si no obtiene la lista de métodos (clave de host desconocida, error de conexión), sin aceptar la clave de host. En T041 se comprueba la configuración efectiva para la red local y para la red privada (`sudo sshd -T -C addr=<ip>,user=<cuenta>`), sin `passwordauthentication yes`.
- **A8** → nota de T003: rango por imagen. `php` 8.4.x, `composer` 2.10.x, `nginx` 1.30.x, `mariadb` 10.11.x y `phpmyadmin` 5.2.x, sin cambio de versión mayor ni menor. `cloudflared` usa versión por calendario: la más nueva publicada hace ≥ 7×24 h dentro de 2026. Fecha: `tag_last_pushed` de la etiqueta exacta, anotando que las etiquetas de parche no se reescriben. Se anota cada etiqueta con su rango.
- **A11** → nota de T022, T029, T030: tocan rutas protegidas (`docker-compose.yml`, `Dockerfile`). Antes de editarlas se pide la aprobación explícita del usuario, y la nota de cada tarea la registra.
- **A13** → nota de T030: depende también de T029; su criterio de hecho se comprueba con T029 terminada.
- **A14** → nota de T011: cada caso comprueba también la respuesta nueva (302 a `login` con el mensaje), para que falle hoy. Si alguno sigue pasando, se marca como caracterización con su «comprobado rompiendo».
- **A15** → nota de T002, T025: la prueba de concepto incluye un valor con `$` literal, una variable interpolada y una clave borrada del archivo. En `verificar.sh`, los valores pasan por tubería o archivos temporales con permisos 600, nunca por argumentos de proceso.
- **A17** → nota de T091: `docs/deployment.md` (copias sin cifrar) y las Consecuencias del ADR 0005 apuntan a la 032 para el cifrado de copias (RS15.b) y las alertas (OB6.b).
- **A18** → nota de T010, T020: la CSP no incluye `fonts.bunny.net` (solo lo usa `welcome.blade.php`, sin ruta); fuentes solo de `fonts.googleapis.com` y `fonts.gstatic.com`.
- **A19** → nota de T004: viene de P6 y del plan D12 (sin criterio de la spec).
- **A20** → nota de T041: se comprueba que un dispositivo fuera de la red privada no llega a la IP 100.x del servidor, y si el servidor tiene IPv6 global (`ip -6 addr show scope global`). Si la tiene, el comprobador de puertos también mira esa dirección.
- **A21** → nota de T027, T041: `tailscale up --accept-dns=false` en el servidor. En T041, después de instalar, `verificar.sh` y el login con Google siguen funcionando.
