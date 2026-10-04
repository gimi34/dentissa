---
result: pass
round: 3
mode: delta
constitution_version: 1.2.1
date: 2026-10-03
spec_sha: d7dce91e4569
plan_sha: 92b19bc2574c
tasks_sha: 159fe8e18e0c
---

# Análisis · 030 Producción en un servidor propio expuesto a internet

## Resumen
`pass`. Análisis delta con subagente, porque el diff contra la ronda 2 ocupa unos 51 KB.

- **A35:** resuelto. El nginx de producción redirige con 301 a https la entrada por `http://`
  (D18). Las comprobaciones internas y el login detrás del túnel no se ven afectados.
- **A36:** resuelto con efecto nuevo. Los archivos de entorno van en `/opt/dentissa/entorno`, fuera
  del clon `/opt/dentissa/app`, y quedan excluidos en `.dockerignore` y `.gitignore`. Esa misma
  exclusión anula la comprobación por `git status` de T042 (→ A44).

Los cuatro hallazgos nuevos no cambian el diseño, y el usuario los acepta como notas de tarea.

Conteo: CRÍTICA 0 · ALTA 0 · MEDIA 1 · BAJA 3

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 10 / 10 |
| Amenazas con control y test | 11 / 11 (la redirección de TM9 se ejerce antes de exponer gracias a la nota A47) |
| Principios de la constitución evaluados | 9 / 9 (P7 con excepción aceptada) |

## Seguimiento de rondas anteriores
Ronda anterior: [history/analysis.r2.md](history/analysis.r2.md).

| ID | Severidad | Estado | Evidencia |
|---|---|---|---|
| A35 | ALTA | resuelto | Plan: `dentissa.prod.conf` en Cambios por módulo, Contratos ("Redirección a https"), D18 con prueba de concepto, TM9 y Trazabilidad de CA2 y TM9. Tareas: T014 (regla), T026 (implementación), T031 (modo público) y T097 (`curl -I http://…/login` → 301). Las peticiones internas no llevan la cabecera y no se redirigen |
| A36 | ALTA | resuelto con efecto nuevo (→ A44) | Plan: P5, Cambios por módulo (`.dockerignore` `**/*.env`, `.gitignore` `*.env`, `/opt/dentissa/entorno` 700, clon en `/opt/dentissa/app`), Contratos, D15, TM6 y Rollout. Tareas: T014, T025, T026 (listado con un `app.env` de prueba), T027, T028, T040 y T042. Las rutas son coherentes. `git ls-files '*.env'` sale vacío, así que no se excluye nada versionado |

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A44 | Cobertura / Inconsistencia (TM6, P2) | MEDIA | T042 | Con `*.env` en `.gitignore`, `git status` no muestra un archivo de entorno dentro del clon, así que la comprobación de T042 no puede fallar. | Aceptado como nota de T042. |
| A45 | Inconsistencia | BAJA | T042 | El "hecho cuando" de T042 dice "las cuatro comprobaciones", pero enumera cinco. | Aceptado como nota de T042. |
| A46 | Ambigüedad | BAJA | T025 | "`git status` no cambia" no puede cumplirse, porque la tarea modifica `.gitignore` y `.dockerignore`. | Aceptado como nota de T025. |
| A47 | Cobertura de riesgos (TM9, CA2) | BAJA | T040, T097; plan TM9 y D18 | La redirección solo se prueba en funcionamiento con el túnel abierto. Además, "Always Use HTTPS" de Cloudflare podría ocultar que la regla de nginx falla. | Aceptado como nota de T040. |

## Decisiones pendientes del usuario
Ninguna.

## Aceptados
Aceptados por el usuario el 2026-10-03 (ronda 1, siguen vigentes; ver [history/analysis.r1.md](history/analysis.r1.md)):
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

Aceptados por el usuario el 2026-10-03 (ronda 2, siguen vigentes; ver [history/analysis.r2.md](history/analysis.r2.md)):
- **A37** → nota de T090: el ADR 0005 se alinea con D3 (Laravel solo confía en el protocolo) y D15 (un archivo de entorno por servicio, fuera del clon); T090 lo comprueba leyendo el ADR.
- **A38** → nota de T027: la red de producción no publica puertos pero no se declara `internal: true`. `cloudflared` y `app` necesitan salida a internet (Cloudflare, Google), y T040 lo comprueba con una petición de salida desde `app`.
- **A39** → nota de T043, T045: restaurar la base y reiniciar el servidor también son operaciones de ensayo. El usuario da su aprobación explícita antes, y la nota la recoge.
- **A40** → nota de T030, T044: `volver.sh` detiene `app` y `web` antes de restaurar la copia, para que ninguna versión atienda peticiones contra un esquema a medio restaurar. En T044, los logs de `app` y `web` no muestran 5xx durante la vuelta atrás.
- **A41** → nota de T032, T040: la guía genera `APP_KEY` sin arrancar `db` (`run --rm --no-deps`), y T040 anota la salida de `desplegar.sh` anunciando el primer despliegue sin copia previa.
- **A42** → nota de T010: el texto de la spec ("cada rechazo") se cumple así: el login y Google emiten `Lockout` (login fallido para la 020), y el rechazo del alta se identifica por su limitador y su respuesta, no por un evento de login. El caso de T010 lo fija.
- **A43** → nota de T003, T051: `docs/roadmap.md` (T051) y `docs/security.md` (T003, dominio) son archivos de la spec aunque el plan no los liste en Cambios por módulo; la tabla de módulos de `tasks.md` los recoge.

Aceptados por el usuario el 2026-10-03 (ronda 3):
- **A44** → nota de T042: que no haya ningún archivo de entorno dentro del clon se comprueba con `find /opt/dentissa/app -name '*.env'`, que debe salir vacío, y no con `git status`, que los ignora.
- **A45** → nota de T042: el "hecho cuando" se lee como "las cinco comprobaciones": `Auth::validate`, `CURRENT_USER()`, el listado de la imagen, ningún `*.env` en el clon (A44) y las etiquetas de las imágenes.
- **A46** → nota de T025: la regla nueva no ignora nada necesario. `git ls-files -ci --exclude-standard` no lista ningún `*.env`, y `git status --porcelain` antes y después solo difiere en `.gitignore` y `.dockerignore`.
- **A47** → nota de T040: antes de abrir el túnel, dos peticiones internas a `web`. Con `X-Forwarded-Proto: http`, 301 con `Location: https://<host>/login`; con `https` o sin la cabecera, 200. La salida se anota.
