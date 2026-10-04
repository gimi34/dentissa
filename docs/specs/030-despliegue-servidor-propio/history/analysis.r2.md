---
result: fail
round: 2
mode: delta
constitution_version: 1.2.1
date: 2026-10-03
spec_sha: d7dce91e4569
plan_sha: 622714d22347
tasks_sha: 1c798b5930ed
---

# Análisis · 030 Producción en un servidor propio expuesto a internet

## Resumen
`fail`. Análisis delta con subagente: el diff contra la ronda 1 ocupa unos 100 KB. De los 22 hallazgos
corregidos de la ronda 1, 17 quedan resueltos y 5 resueltos con efecto nuevo.

Hay dos altos nuevos, que el usuario decide corregir:
- **A35:** nada obliga a redirigir a https la entrada por `http://`, así que el login podría
  servirse sin cifrar hasta Cloudflare (CA2, TM9).
- **A36:** los archivos de entorno por servicio ya no quedan excluidos del paquete, y el plan no
  fija dónde va el clon (P5).

Los siete medios y bajos se aceptan como notas de tarea.

Conteo: CRÍTICA 0 · ALTA 2 · MEDIA 5 · BAJA 2

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 10 / 10 (a CA2 le falta la entrada por http → A35) |
| Amenazas con control y test | 11 / 11 (al control de TM9 le falta la entrada por http → A35) |
| Principios de la constitución evaluados | 9 / 9 (P5 → A36; P7 con excepción aceptada) |

## Seguimiento de rondas anteriores
Ronda anterior: [history/analysis.r1.md](../history/analysis.r1.md).

| ID | Severidad | Estado | Evidencia |
|---|---|---|---|
| A1 | CRÍTICA | resuelto | La spec (Cobertura de riesgos) divide las correcciones y saca las de staging (RD1.d, RD3.d, RS7.e, OB4.c, OB7.b) → objetivo 5. T091 crea las nuevas y T098 deja abiertos sus riesgos |
| A2 | CRÍTICA | resuelto | Spec CA3 y RS7.b dentro; plan `docker-compose.yml` con puertos en `127.0.0.1`; T014 y T024 |
| A3 | ALTA | resuelto | T098 va riesgo por riesgo y coincide con `deployment.md`, `security.md` y `observability.md` |
| A4 | CRÍTICA | resuelto | Fila `contradicción` con fuente `usuario`; P7 ❌ aceptado; `Lockout` en T010, T021 y T022; T051 (detalle → A42) |
| A5 | ALTA | resuelto con efecto nuevo (→ A39) | Fila `contradicción` y aprobación en T040 y T044; la spec y el plan dicen "antes de cada ensayo" |
| A6 | ALTA | resuelto | CA1 reescrito; T092 y T097 |
| A7 | ALTA | resuelto con efecto nuevo (→ A41) | D17 y T030; generar `APP_KEY` con `run` crea la base antes del primer despliegue |
| A8 | ALTA | resuelto | `desplegar.sh` y `volver.sh` con `--interna`; Rollout |
| A9 | ALTA | resuelto con efecto nuevo (→ A36) | D15 y Contratos con un archivo por servicio; los nombres ya no quedan excluidos del paquete |
| A10 | ALTA | resuelto | `DENTISSA_ENV_DIR` y T027 |
| A11 | ALTA | resuelto | D14, T033 (antes de T040) y T050 |
| A13 | MEDIA | resuelto | `trustProxies` solo con el protocolo; TM10; T011 y T020 |
| A14 | MEDIA | resuelto | Subred `172.28.0.0/24` coherente en D3, la conf, el Compose y T014, T026, T027 y T040 |
| A16 | MEDIA | resuelto con efecto nuevo (→ A41) | Contratos de `app.env`; T031 y T032 |
| A20 | MEDIA | resuelto con efecto nuevo (→ A40) | D17: se migra antes de levantar y se restaura antes de levantar la versión anterior |
| A22 | MEDIA | resuelto | D16 y T028 |
| A25 | MEDIA | resuelto | `extends: [001, 002]` y T098 |
| A26 | ALTA | resuelto | Fila `contradicción` con fuente `usuario` y T051 |
| A28 | MEDIA | resuelto | T003; T024 y T027 con la aprobación de rutas protegidas |
| A29 | MEDIA | resuelto | TM11; T028, T029 y T043 |
| A30 | MEDIA | resuelto | Filas con fuente `usuario (/analyze A30)` |
| A34 | MEDIA | resuelto | RS13.a dentro y RS13.b fuera; T091, T051 y T098 |

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A35 | Seguridad / Cobertura (CA2, TM9) | ALTA | plan `bootstrap/app.php`, D3, `verificar.sh`, TM9; T020, T031, T097 | Con `trustProxies` solo para el protocolo, CA2 se cumple por https. Pero `X-Forwarded-Proto` refleja el protocolo del visitante, y nada garantiza que `http://<subdominio>` se redirija a https (HSTS va a la 031). La app serviría el login por http. | Decisión del usuario: corregir. Toda petición por `http://` a la dirección pública recibe una redirección permanente a `https://` antes de servir una página (comprobación: `verificar.sh` en modo público y T097 con `curl -I http://<subdominio>/login` → 301/308 a https). TM9 cita el control. `/plan --fix A35`, `/tasks --fix A35`. |
| A36 | Seguridad (P5; CA5, CA10) | ALTA | plan Contratos, D15, `.dockerignore`, P5; T014, T025, T040, T042 | `app.env`, `db.env` y `tunnel.env` no coinciden con `.env*` de `.dockerignore` ni con `.gitignore`, y el plan no fija dónde va el clon. Con el clon en `/opt/dentissa`, entrarían en la imagen `app`, y `desplegar.sh` los vería como no rastreados. | Decisión del usuario: corregir. Ningún archivo de entorno del servidor puede entrar en el contexto de construcción ni en la imagen, esté donde esté el clon (comprobación: caso de T014 y listado de la imagen en T026 y T042 sin esos nombres). `/plan --fix A36`, `/tasks --fix A36`. |
| A37 | Inconsistencia | MEDIA | ADR 0005 (Decisión) frente a D3 y D15; T090 | El ADR dice que Laravel confía en el proxy "para el protocolo y el host" y cita `.env.production`. | Aceptado como nota de T090. |
| A38 | Ambigüedad | MEDIA | plan, `docker-compose.prod.yml` ("red interna"); T027 | "Red interna" podría implementarse con `internal: true`, que corta la salida de `cloudflared` y de `app` (Google). | Aceptado como nota de T027. |
| A39 | Inconsistencia | MEDIA | spec y plan ("antes de cada ensayo"); T043, T045 | La aprobación previa solo figura en T040 y T044; T043 (restaurar) y T045 (reiniciar) no la piden. | Aceptado como nota de T043, T045. |
| A40 | Inconsistencia | MEDIA | plan `volver.sh`, D17; T030, T044 | `volver.sh` restaura la copia mientras la versión que falló sigue atendiendo peticiones. | Aceptado como nota de T030, T044. |
| A41 | Inconsistencia | MEDIA | plan `deploy/README.md`, D17; T032, T040 | Generar `APP_KEY` con `docker compose run --rm app` arranca `db` (por `depends_on`) y crea la base antes del primer despliegue, y la rama "sin copia previa" ya no se ejerce. | Aceptado como nota de T032, T040. |
| A42 | Inconsistencia | BAJA | spec Auditoría ("cada rechazo"); plan P7; T010 | La spec pide que cada rechazo sea identificable, y el plan emite `Lockout` solo en el login y en Google. | Aceptado como nota de T010. |
| A43 | Cobertura | BAJA | plan Cambios por módulo e Impacto; T003, T051 | `docs/roadmap.md` (T051) no figura en el plan, y el plan asigna `hub.docker.com` a T090/T091 cuando lo hace T003. | Aceptado como nota de T003, T051. |

## Decisiones pendientes del usuario
Ninguna. El 2026-10-03 el usuario decidió corregir A35 y A36 con `--fix` y aceptar los demás como
notas.

## Aceptados
Aceptados por el usuario el 2026-10-03 (ronda 1, siguen vigentes; ver [history/analysis.r1.md](../history/analysis.r1.md)):
- **A12** → nota de T040: antes del primer despliegue en el servidor, gitleaks, semgrep `p/php`, `composer audit` y `npm audit` sobre la rama sin hallazgos críticos ni altos, con la salida anotada. T050 los repite al final.
- **A15** → nota de T011, T031: en T011, el caso de la cookie `Secure` es de caracterización y se comprueba rompiéndolo (`session.secure=false`). `verificar.sh` comprueba además `SESSION_SECURE_COOKIE=true`, y `APP_URL` y `GOOGLE_REDIRECT_URI` en https, sin imprimir valores secretos.
- **A17** → nota de T043: además del recuento de filas, `CHECKSUM TABLE` por tabla igual antes y después de restaurar.
- **A18** → nota de T044: tras `volver.sh`, login por rol y cita ficticia por túnel SSH, como en T046.
- **A19** → nota de T097: con el túnel ya abierto, `docker compose restart` (o un reinicio del servidor) y la dirección pública vuelve sola en 5 minutos o menos.
- **A21** → nota de T030: `desplegar.sh` y `volver.sh` no se ven afectados por el `checkout` que hacen (todo el cuerpo en una función invocada al final, o se reejecutan desde una copia temporal).
- **A23** → nota de T014: la aserción de "sin root" es que `app` no recibe la contraseña de root de la base y `db` declara un usuario propio. La comprobación de imágenes fijadas incluye `COPY --from=`.
- **A24** → nota de T021: con 5 fallos en la ventana, el siguiente intento se rechaza (el 6.º); el test lo fija así.
- **A27** → nota de T020, T022: 014, 020 y 030 tocan `bootstrap/app.php`, `AppServiceProvider.php` y `routes/web.php`. La que se fusione después resuelve el conflicto conservando las dos cosas, y la nota indica con qué rama se comparó.
- **A31** → nota de T031: `verificar.sh` comprueba que ninguna contraseña del README autentica, sin imprimirlas.
- **A32** → nota de T090: archivos (`docs/architecture.md`, ADR 0005), hecho cuando (secciones de Impacto en arquitectura presentes y ADR `accepted`) y depende (T040).
- **A33** → nota de T032: la guía avisa de que la prueba por túnel SSH debe abrirse en `http://localhost:<puerto>`, porque por otra dirección la cookie `Secure` no se envía.

Aceptados por el usuario el 2026-10-03 (ronda 2):
- **A37** → nota de T090: el ADR 0005 se alinea con D3 (Laravel solo confía en el protocolo) y D15 (un archivo de entorno por servicio); T090 lo comprueba leyendo el ADR.
- **A38** → nota de T027: la red de producción no publica puertos pero no se declara `internal: true`. `cloudflared` y `app` necesitan salida a internet (Cloudflare, Google), y T040 lo comprueba con una petición de salida desde `app`.
- **A39** → nota de T043, T045: restaurar la base y reiniciar el servidor también son operaciones de ensayo. El usuario da su aprobación explícita antes, y la nota la recoge.
- **A40** → nota de T030, T044: `volver.sh` detiene `app` y `web` antes de restaurar la copia, para que ninguna versión atienda peticiones contra un esquema a medio restaurar. En T044, los logs de `app` y `web` no muestran 5xx durante la vuelta atrás.
- **A41** → nota de T032, T040: la guía genera `APP_KEY` sin arrancar `db` (`run --rm --no-deps`), y T040 anota la salida de `desplegar.sh` anunciando el primer despliegue sin copia previa.
- **A42** → nota de T010: el texto de la spec ("cada rechazo") se cumple así: el login y Google emiten `Lockout` (login fallido para la 020), y el rechazo del alta se identifica por su limitador y su respuesta, no por un evento de login. El caso de T010 lo fija.
- **A43** → nota de T003, T051: `docs/roadmap.md` (T051) y `docs/security.md` (T003, dominio) son archivos de la spec aunque el plan no los liste en Cambios por módulo; la tabla de módulos de `tasks.md` los recoge.
