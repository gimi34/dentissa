---
spec: 032-alertas-cifrado-copias
verdict: changes_requested
round: 2
date: 2026-10-25
base: a1ab8c4
head: ccba646
human_signoff: no requerido
---

# Review · 032 Alertas y copias cifradas en producción

## Resumen
**changes_requested.** No hay bloqueantes. Las correcciones de r1 están hechas y en verde, salvo una:
la de R3 abrió dos importantes nuevos dentro del canal `seguridad`, y el usuario decide corregirlos
antes de liberar (2026-10-25):
- **R23:** un archivo diario ilegible hace contar varias veces los fallos del otro, y puede saltar un
  aviso de accesos falso.
- **R24:** si el archivo de ayer no se puede leer, el de hoy no se lee y no llega ningún aviso de
  accesos.

También se corrigen cinco menores baratos que son restos de r1 (R25–R29); R30 y R31 van al roadmap.

Ronda `--rerun` sin subagentes: el `code.diff` de a1ab8c4..ccba646 ocupa 23 KB, así que lo revisé
releyendo los archivos desde cero. Los hallazgos coinciden con un `/code-review` previo del usuario
sobre el mismo rango; cada uno se comprobó en el código. El test que faltaba para el canal
`seguridad` se agrupa con R23 y R24.

Conteo: bloqueante 0 · importante 2 · menor 7

## Seguimiento de la ronda 1
| ID | Estado | Evidencia |
|---|---|---|
| R1 | resuelto | `Vigilante::interpretar` captura la excepción de Carbon; `test_una_hora_mal_formada_cuenta_como_linea_sin_formato` (T052, T053) |
| R2 | resuelto, con un efecto menor (→ R25) | El envío fallido deja el aviso pendiente con `desde`; `test_un_primer_aviso_fallido_no_se_pierde` (T054, T055) |
| R3 | resuelto entre fuentes, con efecto nuevo (→ R23, R24) | `Vigilante::leer` aísla acceso y seguridad, pero no los dos archivos diarios de `seguridad` |
| R4 | resuelto en parte (→ R27) | Cuatro comprobaciones ancladas a su `if` y su rama; la de copias en claro no |
| R5 | resuelto, con un efecto menor (→ R28) | `cifrar-copias-antiguas.sh` recorre también la carpeta personal; `verificar.sh` da FALLO sin carpeta, pero sigue con la búsqueda |
| R6 | resuelto | `trap 'rm -f -- "${marca}"' EXIT` tras el `mktemp` de la carga remota |
| R7 | resuelto | La pasada 1 guarda solo `completo` o `incompleto` |
| R8 | resuelto | `mariadb --sandbox`, comprobado con el cliente de 10.11.19 (`system` no permitido) |
| R9 | resuelto en `restaurar.sh` (→ R29) | `volver.sh`, que es quien llama a `git checkout`, no comprueba la versión |

## Verificación automática
| Comando | Resultado |
|---|---|
| `php artisan test` (contenedor efímero de `dentissa-app:latest` con el worktree, en ccba646) | ✅ 417 passed (7088 aserciones; línea base 353) |
| `VigilanteTest`, segunda vez | ✅ 28 passed |
| `vendor/bin/pint --test` (todo el proyecto) | ✅ 107 archivos |
| `bash -n` en `restaurar.sh`, `verificar.sh`, `cifrar-copias-antiguas.sh` y `volver.sh` | ✅ |
| `python .ai/bin/aidd.py validate docs/specs/032-alertas-cifrado-copias` | ✅ 0 errores |
| Build | ➖ sin assets nuevos |

## Criterios de aceptación
| CA | Test | Estado | Nota |
|---|---|---|---|
| CA1 | los de r1 + `VigilanteTest::test_una_hora_mal_formada_cuenta_como_linea_sin_formato`, `…_un_log_de_acceso_ilegible_no_tumba_el_aviso_de_accesos` | ✅ | R26: el mensaje del comando no distingue lectura de envío |
| CA2 | los de r1 | ⚠ | R23 (aviso falso) y R24 (aviso que no llega) con un archivo del canal `seguridad` ilegible |
| CA3 | verificación manual | ✅ | Sin cambios en esta ronda |
| CA4 | los de r1 + `VigilanteTest::test_un_primer_aviso_fallido_no_se_pierde` | ⚠ | R23 rompe «cada evento se cuenta una vez»; R25 |
| CA5 | los de r1 | ✅ | — |
| CA6 | los de r1 + `ConfiguracionProduccionTest::test_verificar_comprueba_avisos_y_copias_fuera_de_la_interna` (rehecho) | ✅ | — |
| CA7 | los de r1 + `…_restaurar_no_deja_restos_y_carga_en_sandbox` | ✅ | Prueba local con el servidor simulado igual que en T029 |
| CA8 | los de r1 | ✅ | — |
| CA9 | los de r1 + `…_restaurar_no_deja_restos_y_carga_en_sandbox` | ✅ | R29 |
| CA10 | los de r1 + `…_verificar_falla_si_no_puede_leer_las_copias` | ⚠ | R27, R28. EX3 se aprueba en `/release` (T096) |
| RNF | gratuito, español, no ralentiza, sin secretos | ✅ | — |

## Conformidad con el plan
- Cambios fuera del plan: ninguno (`scope.md`: 0 archivos sin tarea, 0 tareas sin diff).
- Los 7 archivos de código cambiados son los de T052–T062.

## Constitution Check (sobre el código)
Sin cambios respecto a r1 salvo:

| Principio | Resultado | Evidencia |
|---|---|---|
| P2 Test que falla antes y pasa después | ✅ | T052–T061 fallaron por la razón esperada o se comprobaron rompiendo (notas). R27: falta anclar una comprobación |
| P4 Entrada validada y SQL con bindings | ✅ | R1 resuelto; R29 en `volver.sh` |
| P7, P8 | ❌ aceptado | Como en r1 |

## Seguridad
| Herramienta | Resultado |
|---|---|
| gitleaks v8.21.2 (`--no-git` sobre `app/Avisos`, `deploy`, `tests/Feature` y la carpeta de la spec) | ✅ sin fugas |
| semgrep `p/php` 1.99.0 (`--no-git-ignore`, los 4 PHP tocados) | ✅ 0 hallazgos |
| `composer audit` · `npm audit` | ➖ sin lockfiles cambiados desde r1 (`deps.stat`); resultado de r1 vigente |
| trivy fs | ➖ sin cambios en Dockerfiles ni Compose desde r1; resultado de r1 vigente |

| Tema OWASP (2025) | Resultado | Evidencia |
|---|---|---|
| A05 Injection | ✅ | R8 (sandbox) y R9 (versión) resueltos en `restaurar.sh`; R29 |
| A04 Cryptographic Failures | ✅ | R6, R7 resueltos |
| A09 Logging and Alerting | ⚠ | R2 resuelto; R23, R24 |
| A10 Exceptional Conditions | ⚠ | R1 y R3 resueltos entre fuentes; R24 |

Proceso: sin rutas protegidas en el diff y sin instrucciones dirigidas a agentes.

## Observabilidad
- Log nuevo `Log::warning('lectura fallida', ['fuente' => …])`: sin datos sensibles, pero no está
  en `docs/observability.md` (R26).
- `request_id`: P8 ❌ aceptado, como en r1.

## Preparación para release
Sin cambios respecto a r1: rollback ensayado (T044), sin migraciones ni flags, documentación al día
y primer despliegue en el orden de D6.

## Hallazgos
| ID | Severidad | Archivo:línea | Hallazgo | Sugerencia |
|---|---|---|---|---|
| R23 | importante | `app/Avisos/Vigilante.php:147-165` | `leerSeguridad` añade a `$estado->datos['seguridad']['fallos']` (por referencia) los fallos del archivo de ayer, pero los desplazamientos solo se escriben tras el bucle. Si el de hoy no se puede abrir, la excepción sale después de añadir los fallos y, desde R3, el estado se guarda: la siguiente ejecución relee el de ayer desde el desplazamiento antiguo y vuelve a contar los mismos fallos (con 3 fallos, a la cuarta ejecución hay 12 y sale un aviso falso). No hay test con un archivo del canal `seguridad` ilegible | Los fallos y el desplazamiento de cada archivo se guardan juntos; test con el de hoy ilegible y 3 fallos en el de ayer |
| R24 | importante | `app/Avisos/Vigilante.php:147` | Si el archivo de ayer, que se lee primero, no se puede abrir, la excepción corta el bucle y el de hoy no se lee en todo el día: no llega ningún aviso de accesos | Cada archivo diario se lee por separado; test con el de ayer ilegible y 11 fallos en el de hoy |
| R25 | menor | `app/Avisos/Vigilante.php:389`, `resources/views/correos/aviso.blade.php:17` | Un aviso pendiente por un primer envío fallido (R2) sale con `$anterior` = el último envío, quizá de hace días, y el correo dice «Son los acumulados desde el aviso anterior, de las …», aunque el recuento empieza en `desde` | Citar el aviso anterior solo si lo pendiente nació en su silencio |
| R26 | menor | `app/Console/Commands/Vigilar.php:35`, `app/Avisos/Vigilante.php:38`, `docs/observability.md:15` | Desde R3, `ejecutar()` también devuelve false si falla una lectura, pero el comando dice «Algún aviso no se pudo enviar», el docblock solo habla de envíos y el log `lectura fallida` no está documentado | Mensaje y docblock que nombren las dos causas; fila de logs en `observability.md` |
| R27 | menor | `tests/Feature/ConfiguracionProduccionTest.php:806-813` | El test de R4 ancla cuatro comprobaciones, pero no la de copias en claro (`verificar.sh:304-305`): con `-ne` en lugar de `-eq`, o con ok/fallo intercambiados, sigue en verde | Anclar también esa comprobación |
| R28 | menor | `deploy/verificar.sh:300-305` | Sin carpeta de copias, imprime FALLO y sigue con `find … 2> /dev/null`, que da 0, y a continuación «OK ninguna copia en claro en ${DESTINO}…»: dos líneas que se contradicen | La búsqueda va en la rama `else` de la comprobación de la carpeta |
| R29 | menor | `deploy/volver.sh:42-55` | `volver.sh` pasa `${ref}` a `git checkout` sin comprobarlo; se ejecuta también a mano (guía y vuelta que imprime `desplegar.sh`): `bash deploy/volver.sh -f` llega a git como opción | La misma expresión que `restaurar.sh`, antes de `git fetch` |
| R30 | menor | `deploy/cifrar-copias-antiguas.sh:43` | Las copias de la carpeta personal quedan cifradas en esa carpeta, fuera de la poda de `respaldar.sh` y de la ruta de descarga de la guía | Moverlas a `${DESTINO}` |
| R31 | menor | `app/Avisos/Vigilante.php:323` | `avisoDeErrores` y `avisoDeFormato` vuelven a filtrar con `minutosEnVentana()` lo que `podar()` acaba de dejar filtrado | Leer `$estado->datos['minutos']` directamente |

## Tareas añadidas
| Tarea | Hallazgo |
|---|---|
| T063, T064 | R23, R24 |
| T065, T066 | R25 |
| T067, T068 | R26 |
| T069, T070 | R27, R28 |
| T071, T072 | R29 |

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
