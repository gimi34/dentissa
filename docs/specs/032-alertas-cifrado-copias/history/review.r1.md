---
spec: 032-alertas-cifrado-copias
verdict: changes_requested
round: 1
date: 2026-10-25
base: 853226f
head: a1ab8c4
human_signoff: no requerido
---

# Review · 032 Alertas y copias cifradas en producción

## Resumen
**changes_requested.** No hay bloqueantes: los criterios tienen sus tests en verde y están
ensayados en el servidor. Pero hay cinco importantes que el usuario decide corregir antes de liberar
(2026-10-25):
- **R1–R3, el vigilante:**
  - una hora mal formada lo tumba de forma persistente;
  - un primer aviso que no se pudo enviar se pierde (CA4);
  - un log ilegible arrastra al otro aviso.
- **R4:** un test que pasaría con la lógica invertida.
- **R5:** una copia en claro en la carpeta personal, sin arreglo.

También se corrigen cuatro menores de seguridad baratos (R6–R9); los demás menores van al roadmap.

Revisores independientes:
- A (spec) y S (seguridad), con `opus`;
- C+D (constitución y calidad), con `sonnet`.

Paquete: `aidd.py review-pack`, 853226f..a1ab8c4, `code.diff` de 131 KB. Se descartó un menor (las
referencias 020/021 de P7: la 031 fijó la 021, /analyze 031 A25).

Conteo: bloqueante 0 · importante 5 · menor 17

## Verificación automática
| Comando | Resultado |
|---|---|
| `php artisan test` (contenedor efímero de `dentissa-app:latest` con el worktree) | ✅ 412 passed (7069 aserciones; línea base 353) |
| Tests nuevos de la spec, segunda vez (`VigilanteTest`, `AvisoProduccionTest`, `RegistroFallosAccesoTest`) | ✅ 39 passed |
| `vendor/bin/pint --test` (13 PHP tocados, en T050 sobre el mismo código) | ✅ |
| `bash -n` en los 7 scripts de `deploy/` tocados | ✅ |
| `python .ai/bin/aidd.py validate docs/specs/032-alertas-cifrado-copias` | ✅ 0 errores |
| Build | ➖ sin assets nuevos (`npm run build` no aplica a la spec) |

## Criterios de aceptación
| CA | Test | Estado | Nota |
|---|---|---|---|
| CA1 | `VigilanteTest::test_mas_del_1_por_ciento_de_5xx_avisa`, `…_el_1_por_ciento_no_avisa`, `…_ventana_de_5_minutos`, `…_no_se_engana_con_corchetes_ni_espacios` | ⚠ | Ensayado (T042, < 1 min). R1 y R3 pueden dejar el vigilante sin avisar |
| CA2 | `RegistroFallosAccesoTest` (10 casos), `VigilanteTest::test_mas_de_10_fallos_por_origen_avisa`, `…_por_cuenta_avisa`, `…_10_fallos_no_avisa`, `LimiteIntentosTest` | ✅ | Ensayado (T042: 12 fallos, límite igual) |
| CA3 | verificación manual | ✅ | UptimeRobot con el servidor apagado (T043: caída a ~2 min y vuelta) |
| CA4 | `VigilanteTest::test_silencio_de_30_minutos_acumula`, `…_envia_lo_pendiente_al_acabar_el_silencio`, `…_cada_evento_se_cuenta_una_vez`, `…_un_envio_fallido_no_marca_el_aviso_como_enviado` | ⚠ | R2: un primer aviso fallido se pierde si la ráfaga sale de la ventana antes del reintento |
| CA5 | `AvisoProduccionTest::test_el_correo_no_lleva_datos_personales_ni_secretos`, `…_lleva_tipo_recuentos_horas_y_como_consultar` | ✅ | Prueba de comportamiento real; revisión de los correos del ensayo |
| CA6 | `ConfiguracionProduccionTest::test_verificar_comprueba_el_envio_de_avisos`, `…_no_imprime_valores_del_correo`, `AvisoProduccionTest::test_el_aviso_de_prueba_se_envia` | ✅ | Ensayado rompiendo (T042) |
| CA7 | `ConfiguracionProduccionTest::test_respaldar_cifra_con_la_clave_publica`, `…_no_escribe_en_claro`, `…_se_detiene_sin_gpg_o_sin_clave`, `…_no_pisa_una_copia_existente` | ✅ | Ensayado (T041). R7: la última línea del volcado en un temporal del PC |
| CA8 | `ConfiguracionProduccionTest::test_los_archivos_de_clave_quedan_fuera_del_paquete_y_del_repo`, `…_la_clave_nunca_llega_al_servidor` | ✅ | Ensayado (T041, T044: 0 claves secretas en el servidor) |
| CA9 | `ConfiguracionProduccionTest::test_restaurar_comprueba_antes_de_borrar`, `…_restaurar_distingue_los_rechazos`, `…_restaurar_no_toca_la_base_sin_datos_descifrados`, `…_volver_con_copia_no_depende_de_la_version_de_destino`, `…_desplegar_imprime_la_vuelta_del_contrato` | ✅ | Ensayado (T044: igual que la foto, tres rechazos, frase cancelada, vuelta en 4 min 44 s) |
| CA10 | `ConfiguracionProduccionTest::test_cifrar_copias_antiguas_cifra_antes_de_borrar`, `…_verificar_falla_con_copias_en_claro` | ⚠ | R5. EX3 se aprueba en `/release` (T096) |
| RNF | gratuito, español, no ralentiza, sin secretos | ✅ | — |

## Conformidad con el plan
- Cambios fuera del plan: ninguno (`scope.md`: 0 archivos sin tarea). `deploy/desplegar.sh` está en
  T037; las specs 030 y 013 en T038 y T091 (cobertura de RS15 y OB6).
- Partes del plan sin implementar: ninguna. Las 13 tareas sin diff de código son de documentación,
  preparativos y ensayos.
- Desviaciones registradas en sus tareas:
  - `MAIL_PORT=465` con TLS implícito en lugar de 587 (A42, T019 y T032);
  - la clasificación del rechazo por el código de `pkdecrypt_failed` (T016).

## Constitution Check (sobre el código)
| Principio | Resultado | Evidencia |
|---|---|---|
| P1 Spec aprobada antes de código | ✅ | Spec 2026-10-07, plan 2026-10-25, tareas aprobadas antes de `/implement` (`853226f`) |
| P2 Test que falla antes y pasa después | ✅ | T010–T019 fallaron por la razón esperada (notas); controles comprobados rompiendo. R4: un test de texto que pasaría con la lógica invertida |
| P3 Autorización en servidor | ➖ | Sin rutas ni permisos nuevos |
| P4 Entrada validada y SQL con bindings | ⚠ | Expresión anclada y tests; R1: una hora que no encaja sale como excepción. Sin SQL nuevo |
| P5 Nada sensible en el repo | ✅ | `*.asc` y `*.gpg` ignorados con test; gitleaks limpio |
| P6 Sin vulnerabilidades críticas/altas | ✅ | Sin dependencias nuevas; `composer audit` y `npm audit` limpios |
| P7 Auditoría de datos sensibles | ❌ aceptado | Excepción de la 030 (usuario, 2026-10-03), vence con la 021 |
| P8 Correlación y logs limpios | ❌ aceptado | Sin `request_id` hasta la 020 (usuario, 2026-10-25, `history/analysis.r1.md` A4); logs limpios con test |
| P9 Interfaz con el sistema de diseño | ➖ | Sin pantallas |
| P10 Rutas indexables | ➖ | Sin rutas |

## Seguridad
| Herramienta | Resultado |
|---|---|
| gitleaks v8.21.2 (`--no-git` sobre el diff 853226f..HEAD y los archivos tocados: el `.git` del worktree apunta a una ruta de Windows que el contenedor no ve) | ✅ sin fugas |
| semgrep `p/php` 1.99.0 (`--no-git-ignore`, 8 archivos) | ✅ 0 hallazgos |
| `composer audit --locked` · `npm audit` | ✅ crít: 0 · alta: 0 · media: 0 |
| trivy fs 0.57.1 (`--severity HIGH,CRITICAL`) | ✅ sin vulnerabilidades ni secretos. 3 avisos de configuración (AVD-DS-0002 sin `USER` no root, AVD-DS-0029 sin `--no-install-recommends`) en `Dockerfile` y `docker/prod/Dockerfile`, que la 032 no toca: anteriores, fuera del diff |

| Tema OWASP (2025) | Resultado | Evidencia |
|---|---|---|
| A01 Broken Access Control | ✅ | Sin rutas; clave del limitador idéntica (`AuthController::sujetoLimite`) |
| A04 Cryptographic Failures | ✅ | Cifrado asimétrico con la clave pública, `umask 077`, `chmod 600`, HMAC con clave derivada, SMTP 465 con TLS. R6, R7 |
| A05 Injection | ✅ | `printf %q` y listas de permitidos en `restaurar.sh`; expresión anclada. R8, R9 |
| A06 Insecure Design | ✅ | TM1–TM15 con control y test; confirmación escrita; DROP tras la primera línea |
| A02 Security Misconfiguration | ✅ | `nginx_logs:ro`, `User=` de la cuenta de despliegue, `verificar.sh` sin valores |
| A03 Software Supply Chain | ✅ | Sin lockfiles cambiados |
| A07 Authentication Failures | ✅ | Límite igual, aviso de ráfagas, cuenta de envío dedicada (TM13) |
| A08 Data Integrity | ✅ | Pasada completa en el PC antes de enviar nada. R8 |
| A09 Logging and Alerting | ⚠ | Alertas en uso; R2 (aviso perdido) |
| A10 Exceptional Conditions | ❌ | R1 y R3: una excepción sin capturar deja el vigilante caído |

Proceso: el único cambio en una ruta protegida es `docker-compose.prod.yml`, aprobado por el usuario
el 2026-10-23 (T026). No hay instrucciones dirigidas a agentes en el diff.

## Observabilidad
- **Eventos de auditoría de la spec emitidos y probados:** no aplica (la sección Auditoría de la
  spec no tiene criterios; P7 ❌ aceptado).
- **Datos sensibles en logs del código tocado:** ninguno. El canal `seguridad` guarda huellas sin
  IP, correo ni id (`test_el_registro_no_contiene_ip_ni_correo`), y `Log::warning('aviso no
  enviado')` solo lleva el tipo.
- **`request_id` en los logs nuevos:** no, porque la correlación no existe hasta la 020 (P8 ❌
  aceptado, vence al liberar la 020).

## Preparación para release
- **Rollback:** ensayado (T044), con `volver.sh <anterior>` sin migraciones; la 032 no tiene
  migraciones.
- **Feature flags:** el plan no los pide; se apaga parando el temporizador.
- **Documentación:** `architecture.md` (T090), `deployment.md` y `observability.md` (T091) al día.
- **Primer despliegue:** sigue el orden de D6 (`deploy/README.md` §9).
- **Cobertura de riesgos:** RS15.b y OB6.b «en curso (spec 032)»; RS15.c como EX3 pendiente de
  aprobación en T096; RS15.a, RS15.d, OB6.a y OB6.c fuera, con destino. Ningún riesgo figura
  mitigado antes de tiempo.

## Hallazgos
| ID | Severidad | Archivo:línea | Hallazgo | Sugerencia |
|---|---|---|---|---|
| R1 | importante | `app/Avisos/Vigilante.php:262` | `CarbonImmutable::createFromFormat` lanza `InvalidFormatException` (Carbon 3, modo estricto; comprobado en el contenedor) con una línea que encaja en `COMBINED` pero cuya hora no es `d/M/Y:H:i:s O`: `ejecutar()` aborta antes de guardar el estado, el desplazamiento no avanza y la misma línea tumba todas las ejecuciones siguientes; no llega ningún aviso, tampoco el de formato ni el de accesos | La línea cuenta como «sin formato»; test con `[2026-10-25T10:00:00+00:00] "GET / HTTP/1.1" 500 …` y `[abc] …` |
| R2 | importante | `app/Avisos/Vigilante.php:362-371` | Si falla el envío del primer aviso de una ráfaga (rama de la condición), no queda pendiente; si el SMTP sigue caído hasta que la ráfaga sale de la ventana, el aviso se pierde (CA4, TM10). `test_un_envio_fallido_no_marca_el_aviso_como_enviado` reintenta con los fallos aún en la ventana | Un envío fallido deja el aviso pendiente con su recuento e intervalo; test: falla el envío, pasan más de 5 min sin eventos y el aviso llega al volver el SMTP |
| R3 | importante | `app/Avisos/Vigilante.php:233` | `fopen` sin comprobar: un log existente pero ilegible lanza una excepción en `leerAcceso`, que corre antes que `leerSeguridad`; cae también el aviso de accesos | Un fallo al leer una fuente no impide la otra ni guardar el estado; queda `Log::warning` con la fuente y el comando sale con error |
| R4 | importante | `tests/Feature/ConfiguracionProduccionTest.php` (`test_verificar_comprueba_avisos_y_copias_fuera_de_la_interna`) | Comprueba la sección «Avisos y copias» con `assertStringContainsString` sobre el texto con comentarios, sin la rama `ok`/`fallo` de cada orden: pasaría con las órdenes en un comentario o con la lógica invertida (p. ej. el llavero sin claves secretas, `verificar.sh:293`) | Anclar cada comprobación a `if <orden>; then ok …` / `else fallo …` sin comentarios |
| R5 | importante | `deploy/verificar.sh:299-301`, `deploy/cifrar-copias-antiguas.sh` | La búsqueda de copias en claro mira `${DESTINO}` y `${HOME}`, pero el FALLO remite a `cifrar-copias-antiguas.sh`, que solo cifra `${DESTINO}`; y un `${DESTINO}` inexistente o ilegible da OK (`find … 2>/dev/null \| wc -l` = 0) | `cifrar-copias-antiguas.sh` cifra también las de la carpeta personal; `verificar.sh` da FALLO si la carpeta de copias no existe o no se puede leer |
| R6 | menor | `deploy/restaurar.sh:47-52` (carga remota) | La marca de `/dev/shm` solo se borra en el éxito y en `exit 4`; si `mariadb` falla, queda en RAM con la última línea recibida (puede ser un INSERT con datos). Lo vieron A, C+D y S | `trap` de borrado tras el `mktemp` de la carga remota |
| R7 | menor | `deploy/restaurar.sh:89-104` | En el PC, `final` (temporal en disco) guarda la última línea del volcado descifrado; con un volcado incompleto es una línea de datos en claro | Guardar solo si la línea es `-- Dump completed`, no la línea |
| R8 | menor | `deploy/restaurar.sh:50` | La carga usa `mariadb` sin `--sandbox`: una copia fabricada con la clave pública (que está en el servidor) podría llevar órdenes `\!`/`system`. Hoy exige poder escribir donde están las copias | `mariadb --sandbox` en la carga; TM3 aclara que «sustituir» solo se cubre frente a quien no puede escribir las copias |
| R9 | menor | `deploy/restaurar.sh:140` | La versión de `--volver` puede empezar por `-` y llegar a `git checkout` como opción | Rechazar una versión que empiece por `-` |
| R10 | menor | `app/Avisos/Vigilante.php:235, 276` | Con más de 50 MiB/min el vigilante se queda atrás sin avisar; lo leído tarde cae fuera de la ventana | Riesgo residual de TM12 |
| R11 | menor | `deploy/verificar.sh:293` | «El llavero del servidor no tiene claves secretas» solo mira la cuenta de despliegue | Texto «de la cuenta de despliegue» |
| R12 | menor | `app/Avisos/Vigilante.php:353` | En el silencio, lo pendiente nace con la condición y cualquier evento nuevo, aunque el nuevo no sea el que la cumple (correo engañoso posible); en errores, el porcentaje puede subir sin 5xx nuevos | Calcular la condición con los eventos posteriores al último envío |
| R13 | menor | `tests/Feature/ConfiguracionProduccionTest.php` (`test_restaurar_distingue_los_rechazos`) | Intercambiar los mensajes de la frase y de la clave ajena seguiría pasando | Anclar cada mensaje a su condición |
| R14 | menor | `tests/Feature/ConfiguracionProduccionTest.php` (`test_preparar_vigilancia_instala_el_temporizador`) | No comprueba `set -euo pipefail` ni `Requires=docker.service` | Añadir las dos aserciones |
| R15 | menor | `tests/Feature/ConfiguracionProduccionTest.php` (`test_desplegar_imprime_la_vuelta_del_contrato`) | `shell_exec` con `bash`: solo en Linux, sin nota | Nota en el docblock (CI y desarrollo en contenedores Linux) |
| R16 | menor | `app/Avisos/Vigilante.php:188-190` | `&` entre booleanos (intencional, sin cortocircuito) parece un error | Comentarlo o tres variables |
| R17 | menor | `app/Console/Commands/Vigilar.php` / `app/Avisos/Vigilante.php` | Envío con `Log::warning` duplicado | Reutilizar un método del vigilante |
| R18 | menor | `app/Avisos/Vigilante.php:202` | `ventanaDeErrores()` es público solo para los tests | Documentarlo |
| R19 | menor | `app/Avisos/Estado.php:74` | `public array $datos` mutado por referencia desde fuera | Métodos explícitos en `Estado` |
| R20 | menor | `deploy/restaurar.sh:170` | `printf '%q'` de la carga multilínea exige bash como shell remoto (lo es en Ubuntu), sin documentar | Una línea en la cabecera o `bash -s` |
| R21 | menor | `deploy/cifrar-copias-antiguas.sh` | Sin `trap` para `.part` huérfanos y sin comprobar el `.gpg` antes de `shred -u` | `trap` y `gpg --list-packets` antes de borrar |
| R22 | menor | `tests/Feature/ConfiguracionProduccionTest.php` | Los tests de los scripts leen texto; el comportamiento lo prueban los ensayos (convención del proyecto, declarada en la Trazabilidad) | Aceptable; un test que ejecute `comprobar()` con un gpg falso, si se quiere más |

## Tareas añadidas
| Tarea | Hallazgo |
|---|---|
| T052, T053 | R1 |
| T054, T055 | R2 |
| T056, T057 | R3 |
| T058 | R4 |
| T059, T060 | R5 |
| T061, T062 | R6, R7, R8, R9 |

## Aceptados sin tarea
Aceptados por el usuario el 2026-10-25 (menores, a la deuda del roadmap):
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
