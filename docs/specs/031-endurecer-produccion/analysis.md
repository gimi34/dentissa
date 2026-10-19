---
result: pass
round: 2
mode: delta
constitution_version: 1.2.1
date: 2026-10-06
spec_sha: 688d2baec5ed
plan_sha: 3885aa3c23bc
tasks_sha: acc7eb8afb91
---

# Análisis · 031 Endurecer producción tras la apertura

## Resumen
`pass`. Ronda delta sobre las correcciones de la ronda 1 (commits `bef4b86` y `3ff0002`), hecha por
un subagente independiente porque el diff supera los 30 KB; cada hallazgo se verificó en el archivo
citado. Quedan resueltos A3, A4, A9 y A16, y resueltos con algún efecto menor A1, A2, A6, A7 y A12.
De A10 queda un resto bajo. No hay hallazgos críticos ni altos. El usuario aceptó el 2026-10-06 los
2 medios y 7 bajos como notas de tarea, porque ninguno cambia el diseño.
Conteo: CRÍTICA 0 · ALTA 0 · MEDIA 2 · BAJA 7 (contando el resto de A10)

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 11 / 11 |
| Amenazas con control y test | 13 / 13 (TM9 con la ACL del tailnet; TM11 con el origen en las actualizaciones automáticas) |
| Principios de la constitución evaluados | 9 / 9 (sin violaciones; P7 ❌ aceptado, excepción hasta la 021) |

## Seguimiento de rondas anteriores
| ID | Severidad | Estado | Evidencia |
|---|---|---|---|
| A1 | ALTA | resuelto con efecto nuevo (→ A23) | Contrato de `verificar.sh` y T025: las secciones corren en los dos modos y fallan sin `tailscaled`; T040 `depende: … T041`; el Rollback ya no usa `tailscale down` |
| A2 | ALTA | resuelto con efecto nuevo (→ A25) | spec CA8 y fila `contradicción` con fuente `usuario`; plan Rollback y Riesgos; T028 §8; T040 («exactamente el FALLO conocido»); ADR 0006. El `verificar.sh` de v0.5.0 junta los listeners en una sola línea de FALLO |
| A3 | ALTA | resuelto | contrato de `verificar.sh`, Trazabilidad CA1 y T024: la 301 de nginx se comprueba por la red interna con `X-Forwarded-Proto: http`; la redirección del borde de Cloudflare queda fuera; coincide con `dentissa.prod.conf:21-23` |
| A4 | ALTA | resuelto | la regla es la misma en el plan («Estado de las correcciones»), T031 y T098, y es coherente con `security.md` (RS18.a y la regla de mitigación) |
| A6 | MEDIA | resuelto con efecto nuevo (→ A27) | la ACL figura en TM9, D9, Riesgos, ADR 0006, T027, T028 (§11) y T041 |
| A7 | MEDIA | resuelto con efecto nuevo (→ A22) | `Allowed-Origins` en D9, TM11, T012, T027 y T041; versión y fecha en D10 y T041 |
| A9 | MEDIA | resuelto | spec (Auditoría y Decisiones) y plan (P7) dicen 021; T051 corrige `roadmap.md:228` |
| A10 | MEDIA | abierto en parte, rebajado a BAJA y aceptado | D7, D8, T025 y T026 ya no usan una lista fija. Faltaban el servicio extra en la prueba de concepto de T002 y una alternativa sin lista fija (ver Aceptados) |
| A12 | MEDIA | resuelto con efecto nuevo (→ A24) | contrato, D5, P8, Observabilidad, T011 (caso con `Exceptions::fake()`) y T023; el mensaje de Guzzle no incluye el cuerpo de la petición ni el token |
| A16 | BAJA | resuelto | la fila de HSTS es `brecha`, con fuente `usuario` |

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A22 | Seguridad | MEDIA | tasks T041; plan TM11 | Que el origen aparezca en la lista de `unattended-upgrade --dry-run -d` no prueba que el patrón coincida con los campos reales del repositorio de Tailscale. Además, recién instalado no hay ninguna actualización pendiente que lo ponga a prueba. | Aceptado como nota (T027, T041) |
| A23 | Inconsistencia | MEDIA | contrato de `verificar.sh`; T028; `deploy/desplegar.sh:104-113` | Si la guía se sigue en orden en un servidor limpio, el primer despliegue (§6) llega sin Tailscale (§11) y da un FALLO que el aviso de primer despliegue no prevé. | Aceptado como nota (T028) |
| A24 | Inconsistencia | BAJA | plan, Estrategia de pruebas y Trazabilidad CA8 | La Estrategia sigue con seis casos y no recoge todos los pasos que se añadieron a T011, T012, T040 y T041. | Aceptado → plan |
| A25 | Inconsistencia (030) | BAJA | tasks T098; spec CA8 y fila de A9 | T098 no remite 030 CA7 a 031 CA8 ni registra el nuevo plazo de la excepción P7 en la 030. La fila de A9 es `implícita` y cita un roadmap que aún dice 020. | Aceptado como nota (T051, T098) |
| A26 | Conflicto (020) | BAJA | plan, «Solapes con las specs en curso» | El plan de la 020 solo añade `scheduler` al Compose local, no a los dos. | Aceptado → roadmap |
| A27 | Seguridad | BAJA | tasks T041 | `python3 -m http.server` sirve el directorio actual en todas las interfaces. | Aceptado como nota (T041) |
| A28 | Inconsistencia (ADR 0001) | BAJA | ADR 0006 (`bef4b86`) | Se editó un ADR aceptado. El ADR 0001 pide sustituirlo; hay precedente en la 030 con el ADR 0005. | Aceptado como nota (T090) |
| A29 | Ambigüedad | BAJA | tasks T024; contrato de «Cabeceras» | La 301 interna apunta a `https://127.0.0.1/…`. Si la comprobación sigue la redirección, falla aunque la 301 sea correcta. | Aceptado como nota (T024) |

Controles que el revisor comprobó y son correctos:
- `--accept-dns=false`;
- `report($e)` solo para `GuzzleException`, con su test y su comprobación rompiendo;
- la 301 de nivel `server`;
- el único FALLO de v0.5.0 al volver atrás;
- el estado de RS18.a y RS18;
- `aplicar-entorno.sh <servicio>` contra `config --services` salvo `db`;
- `tasks.md`: tareas de 3 archivos o menos, `[P]` sin archivos compartidos, sin ciclos, Cobertura cuadrada.

## Decisiones pendientes del usuario
Ninguna.

## Aceptados
Siguen vigentes los aceptados de la ronda 1 ([history/analysis.r1.md](history/analysis.r1.md)): A5,
A8, A11, A13, A14, A15, A17, A18, A19, A20 y A21.

Aceptados por el usuario el 2026-10-06:
- **A10** → nota de T002: la prueba de concepto incluye un servicio extra con `env_file` (como el `scheduler` previsto de la 020). Si no cuadra, la alternativa tampoco usa una lista fija o declara qué servicio deja sin cubrir.
- **A22** → nota de T027, T041: el patrón de `Allowed-Origins` usa los campos reales del `Release` del repositorio de Tailscale. En T041 se anotan los campos `o=`, `a=`, `n=` y `l=` de `apt-cache policy tailscale` y se muestra que el patrón los cubre, no solo que aparece en la lista.
- **A23** → nota de T028: en la guía, `preparar-acceso-remoto.sh` y el alta en la red privada van en la preparación del servidor, antes del primer despliegue. La guía se comprueba en orden: en un servidor limpio, la primera verificación solo da los FALLOs documentados como esperados.
- **A24** → plan: sin cambio. Para la Estrategia de pruebas y la Trazabilidad de CA8, la referencia son T011, T012, T040 y T041.
- **A25** → nota de T051, T098: T051 corrige el roadmap a la 021 (con eso, la fila implícita de A9 cuadra con su documento). T098 deja en la 030, CA7, la remisión `→ modificado por 031 CA8` y registra en su Historial el nuevo plazo de la excepción P7.
- **A26** → roadmap: ya consta que la 020 debe añadir su `scheduler` también a `docker-compose.prod.yml`. La 031 no lo cambia.
- **A27** → nota de T041: el puerto de prueba sirve una carpeta temporal vacía (`mktemp -d`), y se para al terminar. La nota recoge dónde escuchaba y que se paró.
- **A28** → nota de T090: el ADR 0006 registra que se refinó antes de liberar la 031 (/analyze A6, A7), como se hizo con el 0005 en la 030.
- **A29** → nota de T024: la comprobación de la 301 interna se decide solo por las cabeceras de la propia 301, sin seguir la redirección. En T024 o T040 da OK con la 301 real, y FALLO si se quita el `include`.
