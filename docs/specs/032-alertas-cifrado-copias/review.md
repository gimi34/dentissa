---
spec: 032-alertas-cifrado-copias
verdict: approved
round: 3
date: 2026-10-27
base: ccba646
head: 05aadb4
human_signoff: no requerido
---

# Review · 032 Alertas y copias cifradas en producción

## Resumen
**approved.** Las correcciones de r2 (T063–T072) resuelven R23–R29 y no abren hallazgos nuevos. No
quedan bloqueantes ni importantes abiertos; los menores aceptados de r1 y r2 (R10–R22, R30, R31) van
al roadmap en `/release`.

Ronda `--rerun` sin subagentes: el `code.diff` de ccba646..05aadb4 ocupa 18 KB; lo revisé releyendo
los archivos desde cero. `scope.md`: 6 archivos de código, 0 sin tarea, 0 tareas sin diff.

Conteo: bloqueante 0 · importante 0 · menor 0 (nuevos)

## Seguimiento de la ronda 2
| ID | Estado | Evidencia |
|---|---|---|
| R23 | resuelto | Cada archivo diario de `seguridad` pasa por `Vigilante::leer` por separado; los fallos y el desplazamiento de un archivo leído se guardan juntos y uno ilegible conserva el suyo. `test_un_archivo_de_seguridad_ilegible_no_duplica_fallos` (T063, T064; comprobado rompiendo) |
| R24 | resuelto | Un archivo ilegible ya no corta el bucle. `test_el_archivo_de_ayer_ilegible_no_impide_leer_el_de_hoy` (T063, T064) |
| R25 | resuelto | El correo de lo pendiente cita el aviso anterior solo si nació en su silencio (`desde` igual a `ultimo_envio`, o sin `desde`); un envío fallido nunca ocurre dentro del silencio, así que su `desde` no coincide. `test_el_reintento_de_un_aviso_fallido_no_cita_el_aviso_anterior` (T065, T066) |
| R26 | resuelto | Mensaje «Algún log no se pudo leer o algún aviso no se pudo enviar…», docblocks y fila de logs de `docs/observability.md`. `test_el_comando_dice_si_fallo_una_lectura` (T067, T068) |
| R27 | resuelto | La comprobación de copias en claro está anclada a su rama ok / fallo; `-ne`, ok y fallo intercambiados y la búsqueda fuera del `else` hacen fallar el caso (T069, T070) |
| R28 | resuelto | Sin carpeta de copias, FALLO y sin búsqueda (`verificar.sh`, rama `else`) (T069, T070) |
| R29 | resuelto | `volver.sh` comprueba la versión antes de `git fetch`, con la misma línea que `restaurar.sh`; prueba local con un git falso: `-f`, `v1;reboot` y `--orphan` rechazados sin llamar a git (T071, T072) |

## Verificación automática
| Comando | Resultado |
|---|---|
| `php artisan test` (contenedor efímero de `dentissa-app:latest` con el worktree, en 05aadb4, con la base `dentissa_db` local) | ✅ 422 passed (7110 aserciones; línea base 353) |
| `VigilanteTest`, segunda vez | ✅ 32 passed |
| `vendor/bin/pint --test` (todo el proyecto) | ✅ 107 archivos |
| `bash -n deploy/*.sh` | ✅ |
| `python .ai/bin/aidd.py validate docs/specs/032-alertas-cifrado-copias` | ✅ 0 errores |
| Build | ➖ sin assets nuevos |

## Criterios de aceptación
| CA | Test | Estado | Nota |
|---|---|---|---|
| CA1 | los de r2 + `VigilanteTest::test_el_comando_dice_si_fallo_una_lectura` | ✅ | — |
| CA2 | los de r2 + `…_el_archivo_de_ayer_ilegible_no_impide_leer_el_de_hoy` | ✅ | Ensayado en T042 |
| CA3 | verificación manual | ✅ | UptimeRobot (T043) |
| CA4 | los de r2 + `…_un_archivo_de_seguridad_ilegible_no_duplica_fallos`, `…_el_reintento_de_un_aviso_fallido_no_cita_el_aviso_anterior` | ✅ | — |
| CA5 | los de r1 | ✅ | — |
| CA6 | los de r2 | ✅ | — |
| CA7 | los de r2 | ✅ | Ensayado en T041 |
| CA8 | los de r1 | ✅ | Ensayado en T041, T044 |
| CA9 | los de r2 + `ConfiguracionProduccionTest::test_volver_comprueba_la_version_antes_de_git` | ✅ | Ensayado en T044 |
| CA10 | los de r2 + `…_verificar_falla_si_no_puede_leer_las_copias` (rehecho) | ✅ | EX3 se aprueba en `/release` (T096) |
| RNF | gratuito, español, no ralentiza, sin secretos | ✅ | — |

## Conformidad con el plan
- Cambios fuera del plan: ninguno. El único archivo fuera de su tarea (`ConfiguracionProduccionTest.php` en T070) está justificado en su nota: el test de T018 se ajusta a la forma en varias líneas sin perder exigencias.

## Constitution Check (sobre el código)
Sin cambios respecto a r2: P2 ✅ (T063, T065, T067, T069 y T071 fallaron por la razón esperada y los controles se comprobaron rompiendo), P4 ✅, P5 ✅, P6 ✅, P7 y P8 ❌ aceptados (excepciones vigentes), P3, P9 y P10 ➖.

## Seguridad
| Herramienta | Resultado |
|---|---|
| gitleaks v8.21.2 (`--no-git` sobre `app`, `deploy`, `tests/Feature` y `docs`) | ✅ sin fugas |
| semgrep `p/php` 1.99.0 (`--no-git-ignore`, los 4 PHP tocados) | ✅ 0 hallazgos |
| `composer audit` · `npm audit` | ➖ sin lockfiles cambiados desde r1 (`deps.stat`); resultado de r1 vigente |
| trivy fs | ➖ sin cambios en Dockerfiles ni Compose desde r1; resultado de r1 vigente |

| Tema OWASP (2025) | Resultado | Evidencia |
|---|---|---|
| A05 Injection | ✅ | R29: la versión de `volver.sh` no llega a git como opción |
| A09 Logging and Alerting | ✅ | R23–R26 resueltos |
| A10 Exceptional Conditions | ✅ | Un archivo ilegible no tumba ni falsea los avisos |

Proceso: sin rutas protegidas en el diff y sin instrucciones dirigidas a agentes.

## Observabilidad
- `Log::warning('lectura fallida')` (campo `fuente`, sin el mensaje de la excepción) documentado en `docs/observability.md`.
- `request_id`: P8 ❌ aceptado, como en r1.

## Preparación para release
Sin cambios respecto a r1: rollback ensayado (T044), sin migraciones ni flags, documentación al día
y primer despliegue en el orden de D6 (`deploy/README.md` §9).

## Hallazgos
Ninguno nuevo en esta ronda.

## Tareas añadidas
Ninguna en esta ronda (las de r1 y r2 están en `history/`).

## Aceptados sin tarea
Aceptados por el usuario el 2026-10-25 (menores, a la deuda del roadmap):
- **R30** → roadmap: las copias de la carpeta personal quedan cifradas allí, fuera de la poda y de la ruta de la guía.
- **R31** → roadmap: `minutosEnVentana()` se aplica dos veces tras `podar()` en `Vigilante`.

Aceptados por el usuario en r1 (2026-10-25), que siguen vigentes:
- **R10** → roadmap: el vigilante no avisa si se queda atrás con más de 50 MiB/min de log de acceso.
- **R11** → roadmap: texto de `verificar.sh` «el llavero de la cuenta de despliegue» (no mira el de root).
- **R12** → roadmap: lo pendiente del silencio puede nacer con un evento que no es el que cumple la condición.
- **R13** → roadmap: anclar cada mensaje de rechazo de `restaurar.sh` a su condición en el test.
- **R14** → roadmap: el test de `preparar-vigilancia.sh` no exige `set -euo pipefail` ni `Requires=docker.service`.
- **R15** → roadmap: nota de que `test_desplegar_imprime_la_vuelta_del_contrato` necesita bash (Linux).
- **R16** → roadmap: `&` entre booleanos en `Vigilante::ejecutar` sin comentario.
- **R17** → roadmap: el envío con `Log::warning` está duplicado entre `Vigilar` y `Vigilante`.
- **R18** → roadmap: `Vigilante::ventanaDeErrores()` es público solo para los tests.
- **R19** → roadmap: `Estado::$datos` público y mutado por referencia.
- **R20** → roadmap: la carga remota de `restaurar.sh` exige bash como shell remoto.
- **R21** → roadmap: `cifrar-copias-antiguas.sh` sin `trap` de `.part` ni comprobación del `.gpg` antes de `shred`.
- **R22** → roadmap: los tests de los scripts leen texto; un test con un gpg falso para `comprobar()`.
