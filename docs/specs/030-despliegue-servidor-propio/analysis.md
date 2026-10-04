---
result: fail
round: 1
mode: full
constitution_version: 1.2.1
date: 2026-10-03
spec_sha: 6b7238aa703a
plan_sha: bc6e47874e1c
tasks_sha: 03171226af8d
---

# Análisis · 030 Producción en un servidor propio expuesto a internet

## Resumen
`fail`. El análisis es completo y lo hizo un subagente independiente; cada hallazgo se verificó contra
los documentos de origen. Los hallazgos críticos son tres:
- correcciones de riesgo que también piden staging se marcarían mitigadas enteras (A1);
- la parte de local de RS7.b no está ni dentro ni fuera (A2);
- P7 se da por "no aplica" sin una excepción aceptada, aunque la spec toca el login (A4).

Hay además varios altos de ejecutabilidad en `desplegar.sh`, el archivo de entorno y el escaneo de
imágenes. El usuario resolvió todas las decisiones pendientes el 2026-10-03 y aceptó 12 hallazgos
menores como notas de tarea.

Conteo: CRÍTICA 3 · ALTA 9 · MEDIA 19 · BAJA 3

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 10 / 10 (CA1, CA2 y CA8 con su parte pública solo en T097 → A6) |
| Amenazas con control y test | 7 / 9 (TM4 sin test de nginx antes de T097 → A14; cookie de TM9 sin comprobar en el servidor → A15); faltan dos amenazas (A13, A29) |
| Principios de la constitución evaluados | 9 / 9 |

## Seguimiento de rondas anteriores
Primera ronda.

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A1 | Cobertura de riesgos | CRÍTICA | tasks T098; spec, Cobertura de riesgos; `deployment.md` RD1.a y RD3.c; `security.md` RS7.a; `observability.md` OB4.b y OB7.a | En su documento de origen, RD1.a, RD3.c, RS7.a, OB4.b y OB7.a incluyen staging, que la spec deja fuera. T098 las marcaría mitigadas enteras, y la parte de staging desaparecería del seguimiento. | Decisión del usuario: dividir. En su documento, cada una se parte en producción (dentro, que mitiga la 030) y staging (corrección nueva, pendiente → objetivo 5). Ajustar la Cobertura de riesgos de la spec, T091 y T098 (`/specify --edit`, `/tasks --fix`). |
| A2 | Cobertura de riesgos | CRÍTICA | `security.md` RS7.b; spec, Cobertura de riesgos; T024, T098 | RS7.b también pide que en local los puertos se publiquen solo en `127.0.0.1`. `docker-compose.yml` publica 3306 (root/root) y 8080 en todas las interfaces, y ninguna tarea lo cambia. | Decisión del usuario: incluirlo. En local, todos los `ports` quedan en `127.0.0.1` (test: caso de T014 que falla con un puerto sin `127.0.0.1:`); `/plan --fix` (Cambios por módulo, Trazabilidad), `/tasks --fix` (T014, T024). |
| A3 | Cobertura de riesgos | ALTA | T098 | La conclusión por riesgo de T098 contradice la spec: dice que RD3, RS7 y OB7 "siguen con correcciones de fuera pendientes" cuando todas sus correcciones están dentro, y omite RD4. | Tras A1 y A2, T098 enumera riesgo por riesgo qué pasa a mitigado (RS4, RD4…) y cuál sigue abierto, con la corrección pendiente concreta (`/tasks --fix`). |
| A4 | Constitución (P7) | CRÍTICA | plan, Constitution Check P7; T021; spec, Auditoría | P7 exige auditar los logins fallidos y la 030 modifica `AuthController::login`. El plan marca P7 como ➖ sin excepción aceptada. Además, el rechazo por límite no pasa por `Auth::attempt`, así que no dispara ningún evento que la 020 pueda escuchar. | Decisión del usuario: excepción P7 hasta la 020, registrada en la spec (Decisiones, fuente `usuario`) y en el roadmap. El rechazo por límite emite el evento estándar de bloqueo del framework (test: `Event::fake` en `LimiteIntentosTest`) para que la 020 lo audite sin tocar el login. `/specify --edit`, `/plan --fix`, `/tasks --fix` (T010, T021). |
| A5 | Constitución (Gobierno) | ALTA | plan D13; T040, T044; `deployment.md` (despliegue solo de tags); spec, RNF | D13 instala en el servidor de producción commits sin revisar, aunque sin exponer, sin una fila `contradicción` con fuente `usuario` y sin registrar la aprobación. | Decisión del usuario: excepción con aprobación. Fila `contradicción` en la spec. T040 y T044 exigen la aprobación explícita del usuario antes de ejecutarse, anotada. `deployment.md` (T091) recoge el ensayo sin exposición. La fila del RNF de aprobación cita T040, T044 y T096. |
| A6 | Inconsistencia | ALTA | spec CA1; T092 | CA1 exige "una versión liberada" que "responde en su dirección pública", pero T092 lo marcaría por un ensayo interno de un commit. | Decisión del usuario: reescribir CA1 (`/specify --edit`) como despliegue desde cero con verificación por la red interna, que se marca en T092. La comprobación pública (HTTPS, login por rol y cita) es verificación del Rollout en T097, junto con las partes públicas de CA2 y CA8. Ajustar la Trazabilidad del plan y T092. |
| A7 | Inconsistencia | ALTA | plan, `deploy/desplegar.sh`; T030, T040 | `desplegar.sh` hace la copia primero y se detiene si falla. En el primer despliegue no hay base, así que no podría completarse. Tampoco espera a que `db` termine de cargar el volcado antes de migrar. | `/plan --fix`: el primer despliegue funciona sin copia previa y lo indica en su salida, y la migración solo empieza cuando `db` responde. `/tasks --fix` T030: `hecho cuando` lo comprueba leyendo el código, y T040 lo ejercita. |
| A8 | Inconsistencia | ALTA | plan, `deploy/desplegar.sh` y Rollout; T030 | `desplegar.sh` "ejecuta `verificar.sh`" sin modo. Sin `--interna` fallaría en el ensayo y en el primer paso de la release, con el túnel parado. | `/plan --fix`: `desplegar.sh` y `volver.sh` verifican siempre por la red interna, y la verificación pública es un paso aparte del Rollout. `/tasks --fix` T030. |
| A9 | Inconsistencia / Seguridad | ALTA | plan, Contratos (variables) y `docker-compose.prod.yml`; T027 | Un único `env_file` para los cuatro servicios no cuadra con los nombres que espera cada imagen (MariaDB, la app y `cloudflared`; `TUNNEL_TOKEN` frente a `CLOUDFLARE_TUNNEL_TOKEN`). Además, cada servicio recibiría los secretos de los demás. | `/plan --fix`: cada servicio recibe solo sus variables, con los nombres exactos fijados en Contratos (test: caso de T014 que comprueba que `cloudflared` y `db` no reciben el entorno de la app). `/tasks --fix` T014, T027, T032. |
| A10 | Ambigüedad | ALTA | T027 | `docker compose config` "con un archivo de entorno de prueba" no se puede ejecutar en el equipo de desarrollo con una ruta absoluta de Linux, y no se dice cómo sustituirla. | `/plan --fix` (junto con A9): la ruta de los archivos de entorno es configurable, con valor por defecto en `/opt/dentissa`. `/tasks --fix` T027: la ruta del archivo de prueba, con solo nombres de variables. |
| A11 | Seguridad (P6) | ALTA | plan, Constitution Check P6; T002, T050 | Llegan a producción imágenes base nuevas y solo se ejecuta `trivy fs`, que no analiza sus paquetes del sistema. El plan dice "T040" y las tareas, T050. | `/plan --fix`: las imágenes base fijadas y las construidas (`app`, `web`) pasan `trivy image` sin críticas ni altas antes del ensayo, o con una excepción registrada. `/tasks --fix`: tarea antes de T040. |
| A12 | Inconsistencia (orden) | MEDIA | T040, T050 | Los escáneres de T050 van después del ensayo en el servidor. | Aceptado como nota de T040. |
| A13 | Seguridad | MEDIA | plan D3 y Modelo de amenazas; T011, T020 | Con `trustProxies(at: '*')`, Laravel acepta `X-Forwarded-Host` y `X-Forwarded-Port` de cualquier cliente, lo que permite inyectar host y puerto en URLs y redirecciones. No hay ninguna amenaza para esto, y T011 habla de "desde el proxy" sin poder distinguirlo. | `/plan --fix`: nueva TM. Un `X-Forwarded-Host` o `X-Forwarded-Port` ajeno no cambia `url('/')` ni las redirecciones (test: caso nuevo en `ProxyHttpsTest`). Control a elegir en el plan. `/tasks --fix` T011, T020. |
| A14 | Cobertura (TM4) | MEDIA | plan `dentissa.prod.conf` y `docker-compose.prod.yml`; T027, T040; tabla de amenazas | `real_ip` confía en `172.16.0.0/12`, pero la subred de la red de Compose no se fija. El control de nginx solo se prueba en T097, que no figura en la Cobertura de TM4. | `/plan --fix`: la red de producción tiene una subred fija dentro del rango de confianza (test: caso de T014). En el ensayo, una petición con `CF-Connecting-IP` y un `X-Forwarded-For` falso deja la IP de `CF-Connecting-IP` en el access log (comprobación en T040). T097 entra en la Cobertura de TM4. |
| A15 | Cobertura (CA2, TM9) | MEDIA | T011, T031 | El caso de la cookie `Secure` de T011 ya pasa hoy (es de caracterización). `verificar.sh` no comprueba `SESSION_SECURE_COOKIE`, `APP_URL` ni el redirect de Google en https. | Aceptado como nota de T011 y T031. |
| A16 | Cobertura | MEDIA | plan, Contratos (variables); T031, T032 | Las variables de producción no incluyen `APP_KEY` (ni cómo generarla sin `.env`), `CACHE_STORE` (los limitadores dependen de él) ni `SESSION_DRIVER`. | `/plan --fix` Contratos. `/tasks --fix` T031 (`APP_KEY` no vacía, `CACHE_STORE` distinto de `array`) y T032 (cómo generar la clave). |
| A17 | Inconsistencia | MEDIA | plan, Trazabilidad CA6; T043 | El recuento de filas no detecta un UPDATE; la Estrategia pedía además una suma de comprobación. | Aceptado como nota de T043. |
| A18 | Cobertura | MEDIA | T044; spec CA7 | CA7 pide pasar "la misma verificación" tras la vuelta atrás, incluidos el login por rol y la cita. | Aceptado como nota de T044. |
| A19 | Cobertura | MEDIA | T045, T097; spec CA9 | El reinicio se ensaya con el túnel parado y nunca se comprueba que `cloudflared` vuelva solo. | Aceptado como nota de T097. |
| A20 | Inconsistencia | MEDIA | plan, `desplegar.sh` y `volver.sh`; `deployment.md` (migrar antes de cambiar el tráfico) | `desplegar.sh` levanta la versión nueva y migra después; `volver.sh` levanta y restaura después. `deployment.md` pide lo contrario. | `/plan --fix`: migrar antes de levantar la versión nueva y restaurar antes de levantar la anterior. Si se mantiene otro orden, registrarlo como decisión. `/tasks --fix` T030. |
| A21 | Inconsistencia | MEDIA | T030 | `desplegar.sh` y `volver.sh` hacen `checkout` del repositorio que los contiene mientras bash los está leyendo. | Aceptado como nota de T030. |
| A22 | Ambigüedad | MEDIA | T028; spec, Notas para /plan | Con sudo, `~` es `/root`, así que la comprobación de la clave mira donde no toca. No se define el usuario de despliegue ni sus permisos (grupo `docker`, `/opt/dentissa`, copias). | `/plan --fix`: el usuario de despliegue y sus permisos; la comprobación de clave usa el usuario que invoca sudo. `/tasks --fix` T028. |
| A23 | Ambigüedad | MEDIA | T014 | "La app no usa root" no se puede comprobar como texto del Compose, y `COPY --from=` escapa a la comprobación de imágenes fijadas. | Aceptado como nota de T014. |
| A24 | Inconsistencia | MEDIA | plan, `AuthController`; T021 | "Si se superan 5 fallos" frente a "tras 5 fallos se rechaza el 6.º": error de uno en el umbral. | Aceptado como nota de T021. |
| A25 | Inconsistencia (`extends`) | MEDIA | spec frontmatter; 001 CA9; 002 CA7 | La 030 cambia el comportamiento de la 001 (límite en login y Google) y de la 002 (límite en el alta) con `extends: []`. | `/specify --edit`: `extends: [001, 002]`, declarando que resuelve en parte 001 CA9 (límite sí; el registro, en la 020) y 002 CA7. T098 los actualiza al liberar. |
| A26 | Duplicación y conflicto | ALTA | spec, Decisiones; 020 plan P8 (decisión del usuario 2026-09-28: la 023 antes de producción) | La 030 abre producción con OB4.a fuera, en contra de una decisión anterior del usuario. Además, la 020 añade `scheduler` solo al Compose de local. | Decisión del usuario: aceptar el residuo (datos ficticios, logs solo en el servidor; la 023 antes de tener datos reales). Fila `contradicción` en la spec (`/specify --edit`). En el roadmap, la 020 debe añadir `scheduler` a `docker-compose.prod.yml` y probar sus triggers sin root (T091). |
| A27 | Duplicación y conflicto | BAJA | T020, T022; 014 y 020 | 014, 020 y 030 editan `AppServiceProvider.php`, `bootstrap/app.php` y `routes/web.php`. | Aceptado como nota de T020, T022. |
| A28 | Seguridad | MEDIA | T002, T024, T027; `security.md`, Permisos del agente | T002 consulta `hub.docker.com`, que no está aprobado. T024 y T027 tocan rutas protegidas sin aprobación registrada. | Decisión del usuario: aprobar ambas cosas. `hub.docker.com` pasa a los dominios del agente en `security.md` (T091; T002 lo anota) y T024 y T027 quedan aprobadas como cambios en rutas protegidas (`/tasks --fix`). |
| A29 | Seguridad | MEDIA | plan, Modelo de amenazas y D9; spec, Datos sensibles | Ninguna amenaza cubre las copias de seguridad, que contienen datos personales y de salud, van sin cifrar y se copian por `scp` a un PC. | `/plan --fix`: nueva TM con control parcial (permisos 600, 14 copias, `scp` por SSH) y residuo → 031 (RS15.b). Test: comprobación de permisos en T043. |
| A30 | Ambigüedad | MEDIA | spec, Decisiones (filas `supuesto`) | Hay tres supuestos sin confirmar que condicionan el diseño: el umbral de 5 por minuto, la administración solo desde la red local y los tiempos de 15, 15 y 5 minutos. | Decisión del usuario: los tres confirmados. Pasan a fuente `usuario` (`/specify --edit`). |
| A31 | Cobertura | MEDIA | T031; spec CA5 | Las contraseñas se rotan una sola vez. Si se recrea la base, el volcado devuelve las del README y nada lo detecta. | Aceptado como nota de T031. |
| A32 | Ambigüedad | BAJA | T090 | T090 no sigue el formato de tarea. | Aceptado como nota de T090. |
| A33 | Seguridad | BAJA | T032, T046 | La prueba por túnel SSH con cookie `Secure` solo funciona entrando por `localhost`. | Aceptado como nota de T032. |
| A34 | Cobertura de riesgos | MEDIA | spec, Cobertura de riesgos; `security.md` RS13.a | RS13.a (mensaje genérico y límite en el alta) recibe el límite de la 030, pero no aparece en la tabla. | `/specify --edit`: si una corrección se cumple solo en parte, se divide en su documento (A1). El límite del alta va dentro (CA8) y el mensaje genérico fuera, con destino. T091 y T098 lo reflejan. |

## Decisiones pendientes del usuario
Ninguna. Todas se resolvieron el 2026-10-03:
- **A1:** dividir.
- **A2:** incluir.
- **A4:** excepción P7 hasta la 020, con el evento de bloqueo.
- **A5:** excepción con aprobación anotada.
- **A6:** reescribir CA1.
- **A26:** aceptar el residuo de OB4.a.
- **A28:** aprobar `hub.docker.com` y las rutas protegidas.
- **A30:** confirmar los tres supuestos.

## Aceptados
Aceptados por el usuario el 2026-10-03:
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
