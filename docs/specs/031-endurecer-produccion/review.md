---
spec: 031-endurecer-produccion
verdict: approved
round: 2
date: 2026-10-20
base: ba5cdda
head: 011da90
human_signoff: no requerido
---

# Review · 031 Endurecer producción tras la apertura

## Resumen
`approved`. Las correcciones de la ronda 1 (R1–R3, R5–R11; tareas T060–T068) están implementadas y
probadas. El código nuevo solo deja un menor (R20, aceptado → roadmap).

La verificación automática detecta un aviso alto de `npm audit` (R21): `follow-redirects` 1.16.0,
con dos avisos publicados el mismo 2026-10-20. No viene de este diff (el lockfile no cambió desde la
ronda 1 y `main` tiene la misma versión). Por decisión del usuario se corrige con un `/chore` en
`main`, y la rama de la 031 lo trae antes de `/release`: es condición del release, junto con R4 y R19.

Revisión `--rerun` sin subagentes: el diff de código ocupa unos 22 KB. Se releyó desde el disco el
paquete de review (`ba5cdda..011da90`).
Conteo (código nuevo): bloqueante 0 · importante 0 · menor 1. Condición de release: R21 (SCA, ajeno al diff).

## Verificación automática
| Comando | Resultado |
|---|---|
| `php artisan test` | ✅ 352 tests (6665 aserciones), sin fallos |
| Tests tocados (`ConfiguracionProduccionTest`, `CabecerasSeguridadTest`, `GoogleVueltaFallidaTest`), dos veces en `/implement` | ✅ 15 + 9 + 11 y lo mismo en la segunda pasada |
| `pint --test` (4 PHP tocados) | ✅ |
| `bash -n` (`verificar.sh`, `aplicar-entorno.sh`) | ✅ |
| `aidd.py validate docs/specs/031-endurecer-produccion` | ✅ 0 errores |
| `npm run build` · `--medir` | ➖ no aplica (sin vistas, JavaScript ni layout) |

## Criterios de aceptación
Solo cambian las pruebas de CA4, CA5, CA7, CA9 y CA11. El resto, como en la ronda 1.

| CA | Test | Estado | Nota |
|---|---|---|---|
| CA4 | `CabecerasSeguridadTest::test_las_trazas_no_llevan_argumentos`; `ConfiguracionProduccionTest::test_verificar_nombra_las_cabeceras_que_sobran_y_la_ruta_es_local` | ✅ | R6, R7 |
| CA5 | `GoogleVueltaFallidaTest` (11): casos separados, comprobador real del `state` (sin `state` en sesión y con uno distinto) y `ConnectException` | ✅ | R3; comprobado rompiendo `report()`, `stateless()` y el `catch` de Guzzle (nota de T064) |
| CA7 | `test_el_entorno_aplicado_no_da_ok_sin_comparar_todos_los_servicios`; `test_aplicar_entorno_recrea_el_servicio_y_termina_verificando` | ✅ | R1, R2, R5; prueba local con un servicio «fantasma» (nota de T061) y 8 roturas del script (nota de T062) |
| CA9 | `test_el_acceso_remoto_usa_el_repositorio_firmado_y_openssh` (anclado) | ✅ | R8; imitaciones con `echo` detectadas (nota de T065) |
| CA11 | `test_el_despliegue_construye_sin_cache`, `test_cada_despliegue_rehace_la_actualizacion_del_sistema` (anclados) | ✅ | R8, R9. La comprobación en el servidor sigue siendo condición de T097 (R4) |

## Conformidad con el plan
`scope.md`: 8 archivos de código, todos citados por una tarea. Sin alcance extra. T068 no tiene diff
porque solo verifica. La sección «Descubribilidad» del plan («No aplica») resuelve R11.
`docs/deployment.md` y `deploy/README.md` §10 quedan al día con R1 (nota de T091).

## Constitution Check (sobre el código)
| Principio | Resultado | Evidencia |
|---|---|---|
| P1 | ✅ | Tareas añadidas por `/review` y aprobadas |
| P2 | ✅ | Cada test nuevo falló hoy por la razón esperada o se comprobó rompiendo (notas de T060–T066) |
| P3, P7, P8 | ➖ | Sin cambios de autorización, auditoría ni logs. `zend.exception_ignore_args` reduce lo que llega a las trazas |
| P4 | ✅ | `grep -qxF --` trata el servicio como texto fijo y nunca como opción |
| P5 | ✅ | gitleaks `ba5cdda..HEAD` sin fugas. Las credenciales de `GoogleVueltaFallidaTest` son de prueba |
| P6 | ⚠ | Un aviso alto de `npm audit` ajeno al diff (R21), condición de `/release` |
| P9, P10 | ➖ | Sin vistas ni rutas públicas |

## Seguridad
| Herramienta | Resultado |
|---|---|
| gitleaks (`ba5cdda..HEAD`) | ✅ sin fugas |
| semgrep `p/php` (4 PHP tocados) | ✅ 0 hallazgos |
| `composer audit` (altas y críticas) | ✅ sin avisos |
| `npm audit --audit-level=high` | ❌ `follow-redirects` 1.16.0 (vía `axios` 1.20.0, desarrollo): GHSA-q3jh-wrc6-jgq8 y GHSA-8r9p-f939-6h3c, publicados el 2026-10-20, corregidos en 1.16.1 (publicada el 2026-10-01) → R21 |
| trivy fs (HIGH, CRITICAL) | ✅ sin avisos (su base aún no recoge los de `npm audit` del día) |

Temas de `shared/security-checklist.md` en el diff:
- **Inyección en scripts** (A05:2025): `grep -qxF --` lo cierra.
- **Configuración** (A02:2025): `zend.exception_ignore_args`, mensajes de cabeceras correctos y
  «Entorno aplicado» sin falso OK.
- **Validación del `state` de OAuth** (A07:2025): probada con el comprobador real.

## Observabilidad
Sin eventos ni logs nuevos. Las trazas de `report()` dejan de llevar argumentos (R6).

## Hallazgos

### Seguimiento de la ronda 1
| ID | Estado | Evidencia |
|---|---|---|
| R1 | ✅ resuelto | `verificar.sh`: `try` por servicio, `SERVICIOS <n>`, código de salida y recuento (T060, T061) |
| R2 | ✅ resuelto | Test de `aplicar-entorno.sh` anclado a las órdenes (T062) |
| R3 | ✅ resuelto | `GoogleVueltaFallidaTest` rehecho (T064) |
| R4 | ⏳ condición de T097 | Refresco de `cloudflared`/`mariadb`, `web` sin «CACHED» y `trivy image` en el servidor |
| R5 | ✅ resuelto | `grep -qxF --` (T063) |
| R6 | ✅ resuelto | `zend.exception_ignore_args = On` (T065, T066) |
| R7 | ✅ resuelto | Mensajes y `local ruta` (T065, T066) |
| R8 | ✅ resuelto | Expresiones ancladas a la orden real (T065) |
| R9 | ✅ resuelto | `RUN` del `Dockerfile` en líneas separadas (T067) |
| R10 | ✅ resuelto | `: RedirectResponse` (T067) |
| R11 | ✅ resuelto | «Descubribilidad: No aplica» en el plan (T067) |
| R12–R18 | aceptados → roadmap | sin cambios |
| R19 | ⏳ condición de T097 | cadena http → https con la respuesta real |

### Nuevos
| ID | Severidad | Archivo:línea | Hallazgo | Sugerencia |
|---|---|---|---|---|
| R20 | menor | `deploy/verificar.sh` (bloque «Entorno aplicado») | Si python3 no puede leer el Compose, ese único fallo cuenta dos veces en `FALLOS`: su línea FALLO y el `fallo` del recuento. El resultado no cambia (falla igual) | No sumar el `fallo` del recuento si ya hubo una línea FALLO de lectura del Compose |
| R21 | condición de release (SCA, ajeno al diff) | `package-lock.json` (`follow-redirects` 1.16.0) | `npm audit --audit-level=high` falla por dos avisos altos publicados el 2026-10-20. También afecta a `main` y a la CI de cualquier PR | `/chore` en `main`: `follow-redirects` a 1.16.1 (≥ 7 días) solo en el lockfile; la 031 trae ese `main` antes de `/release` |

## Preparación para release
- **Rollback:** `deploy/volver.sh v0.9.0`, igual que en la ronda 1. Sin migraciones nuevas.
- **Documentación:** `docs/architecture.md` y `docs/deployment.md` al día (T090, T091).
- **SCA:** R21 bloquea `/release` hasta el `/chore` en `main` (decisión del usuario, 2026-10-20).
  EX2 sigue vigente hasta el 2026-11-30.
- **Antes de `/release`:** la rama trae `origin/main`, con el `/fix` de la raíz (PR #36) y el
  `/chore` de R21.
- **Condiciones de T097:** R4 y R19.

## Tareas añadidas
Ninguna en esta ronda.

## Aceptados sin tarea
Aceptados por el usuario el 2026-10-19 (ronda 1) y el 2026-10-20 (ronda 2):
- **R4** → condición de `/release` (T097): refrescar `cloudflared` y `mariadb` si ya cumplen 7 días, ver la capa de `web` sin «CACHED» y `trivy image` del `web` del servidor; ajustar EX2.
- **R12** → roadmap: aserción sobre el registro de `Failed` cuando exista la auditoría (spec 021).
- **R13** → roadmap: decidir si la vuelta de Google captura cualquier excepción de Socialite.
- **R14** → roadmap: comprobar la huella de la clave del repositorio de Tailscale.
- **R15** → roadmap: bloque `"tests"` en la política del tailnet.
- **R16** → roadmap: fijar por digest las imágenes de producción.
- **R17** → roadmap: excluir de «nada escucha fuera de localhost» solo el proceso `tailscaled`.
- **R18** → roadmap: comprobar el SSH sin contraseña también para la red local y la privada (`sshd -T -C addr=…`).
- **R19** → condición de `/release` (T097): cadena http → https con la respuesta real.
- **R20** → roadmap: no contar dos veces el fallo de lectura del Compose en «Entorno aplicado».
- **R21** → condición de `/release`: `/chore` en `main` que suba `follow-redirects` a 1.16.1 y la rama lo trae antes del release.
