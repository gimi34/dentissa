---
spec: 020-auditoria-base
verdict: changes_requested
round: 2
date: 2026-10-31
base: 277bcd1
head: db30bbd
human_signoff: no requerido
---

# Review · 020 Registro de auditoría base y correlación por petición

## Resumen
Cambios pedidos. Todas las correcciones de la ronda 1 (R1–R7, R9, R16–R21, R23, R24, R26, R33) están
hechas y verificadas, pero la de R4 trae una regresión en `restaurar.sh` (R36). La segunda lectura
de la lista de servicios va antes de comprobar si la carga se cortó, y con el SSH caído daría una
indicación peligrosa en una ruta de vuelta atrás. Hay además cinco menores. El usuario decidió
corregir R36 y los cinco menores. R8 (`npm audit`) sigue pendiente de la chore de `follow-redirects`,
que tiene que estar en `main` antes de `/release`.
Conteo (ronda 2, nuevos): bloqueante 0 · importante 1 · menor 5

Ronda `--rerun` sobre `277bcd1..db30bbd` (diff de código de 38 KB): un único revisor independiente
(perfil `critical`, Opus) con los encargos A, C+D y S. Ronda 1 en `history/review.r1.md`.

## Verificación automática
| Comando | Resultado |
|---|---|
| `php artisan test` (contra `dentissa_test`) | ✅ 543 pasan (8334 aserciones); sin línea base de fallos |
| `vendor/bin/pint --test` (11 PHP tocados en las correcciones) | ✅ (T069) |
| build | no aplica |
| `python .ai/bin/aidd.py validate docs/specs/020-auditoria-base` | ✅ 0 errores |
| `design/medir.mjs` sobre `/_disenio/020` | ✅ código 0 y el mismo `resumen.md` que T053 (T056 y T058, tras el último cambio de interfaz); capturas del 503 idénticas píxel a píxel |

## Criterios de aceptación
| CA | Test | Estado | Nota |
|---|---|---|---|
| CA1 | `RegistroAuditoriaTest::test_un_evento_guarda_todos_los_campos` y otros | ✅ | |
| CA2 | `InmutabilidadAuditoriaTest::test_sin_el_indicador_no_se_borra_un_evento_antiguo` y los de la ronda 1 | ✅ | R2 corregido; comprobado rompiendo el trigger |
| CA3 | `RegistroAuditoriaTest::test_un_valor_con_salto_de_linea_final_se_rechaza` y otros | ✅ | R40: faltan los casos de `campos` y `recurso_tipo` (menor) |
| CA4 | `DenegacionesAuditadasTest::test_una_cita_ajena_con_espacio_final_responde_como_la_inexistente`, `test_un_usuario_sin_ficha_de_paciente_deja_su_denegacion` y otros | ✅ | R5 y R6 corregidos |
| CA5 | `InmutabilidadAuditoriaTest::test_con_el_indicador_el_trigger_corta_a_los_5_anios_en_utc` y otros; `ConfiguracionProduccionTest` | ⚠ | R36 en la vuelta con copia |
| CA6 | `ConsultaAuditoriaTest::test_un_filtro_enviado_como_lista_vuelve_con_su_error` y otros | ✅ | R7 y R33 corregidos |
| CA7 | `ConsultaAuditoriaTest::test_un_no_admin_no_consulta_el_registro` | ✅ | |
| CA8 | `IdentificadorPeticionTest::test_se_reutiliza_un_identificador_valido` y otros | ✅ | |
| CA9 | `IdentificadorPeticionTest::test_un_identificador_malformado_se_sustituye` y otros | ✅ | |
| CA10 | `IdentificadorPeticionTest::test_logs_y_eventos_comparten_request_id_y_actor` y otros | ✅ | |
| CA11 | `AuditoriaVistasTest::test_el_css_de_la_020_no_trae_literales_de_tipografia_ni_multiplicadores` y otros; `ErrorAuditoriaTest` | ✅ | R1 corregido; R38: el test no ve divisiones (menor) |
| CA12 | `ConsultaAuditoriaTest::test_la_consulta_queda_registrada_con_sus_filtros` y otros | ✅ | |
| CA13 | `IdentificadorPeticionTest::test_un_identificador_repetido_no_transfiere_el_actor` y otros | ✅ | |
| CA14 | `VigilanteTest::test_un_fallo_de_auditoria_avisa_por_correo` y otros | ✅ | |

## Conformidad con el plan
- Cambios fuera del plan: ninguno (`scope.md`: 0 archivos sin tarea). T070 y T071, añadidas durante
  `/implement` con aprobación del usuario, llevan los tokens del logo a `system.html`.
- Partes del plan sin implementar: ninguna. T071 no aparece en el diff de código porque toca
  `docs/design/system.html`.

## Estado de los hallazgos de la ronda 1
| Hallazgo | Estado | Evidencia |
|---|---|---|
| R1 | corregido | `errores.css` solo con tokens (mismos valores); tokens en `app.css`, `system.md` 1.8.0 y `system.html`; `AuditoriaVistasTest.php:108,127` |
| R2 | corregido | `InmutabilidadAuditoriaTest.php:115-134`; fallan con el trigger roto (T059) |
| R3 | corregido | `deploy/README.md:410-419`, `docs/deployment.md:117-120` |
| R4 | corregido, con regresión | Guardas que fallan cerradas en los tres scripts; la segunda lectura de `restaurar.sh:185` es R36 |
| R5, R6 | corregidos | `ClienteCitasController.php:76-78,199-201,235-237`; `DenegacionesAuditadasTest.php:129,144` |
| R7 | corregido | `auditoria/index.blade.php:14`; `ConsultaAuditoriaTest.php:318` |
| R8 | pendiente | `npm audit`: 1 alta (`follow-redirects` 1.16.1); la corrige la chore `chore/follow-redirects-1.16.2`, que aún no está en `main` |
| R9 | corregido | `\z` en `Auditoria.php:30-34,130` y `RegistroCentralDenegaciones.php:28-32`; R40 completa sus tests |
| R16, R17, R18 | corregidos | `desplegar.sh:109-117`, `volver.sh:55-70`, `restaurar.sh:188`; `ConfiguracionProduccionTest` |
| R19, R20 | corregidos | `deploy/README.md:273-293,421-428` |
| R21, R23 | corregidos | `docker-compose.prod.yml:44-46`; `ConfiguracionProduccionTest.php:286-297`; sin capacidades, `schedule:list` lista la purga (T063) |
| R24, R26 | corregidos | `auditoria/index.blade.php:99`, `errors/auditoria-no-disponible.blade.php:31`; `AuditoriaVistasTest.php:148,157` |
| R33 | corregido | `ConsultaAuditoriaTest.php:136-143` |

## Constitution Check (sobre el código)
| Principio | Resultado | Evidencia |
|---|---|---|
| P1 Spec aprobada antes de código | ✅ | Tareas de `/review` aprobadas por el usuario |
| P2 Test que falla antes y pasa después | ✅ | Cada corrección con su test, que falla antes (notas de T054–T071) |
| P3 Autorización en servidor | ✅ | Sin cambios de alcance; R6 añade la auditoría de una denegación |
| P4 Entrada validada y SQL con bindings | ✅ | R5 y R7 corregidos; R39: reglas de filtro aún con `$` (menor, hoy cubiertas por `TrimStrings`) |
| P5 Nada sensible en el repo | ✅ | gitleaks sin fugas |
| P6 Ninguna crítica o alta sin excepción aprobada | ⚠ | R8 |
| P7 Auditoría de accesos y cambios a datos sensibles | ✅ | R6 corregido |
| P8 Correlación por petición y logs sin datos sensibles | ✅ | Sin cambios |
| P9 Solo el sistema de diseño | ✅ | R1 corregido; R41: comentario de versión desfasado (menor) |
| P10 Las rutas indexables son descubribles | ✅ | Sin cambios en rutas públicas |

## Seguridad
| Herramienta | Resultado |
|---|---|
| gitleaks v8.21.2 (commits de la rama) | ✅ 0 hallazgos |
| semgrep 1.99.0 `p/php` (`--baseline-commit`) | ✅ 0 nuevos |
| `composer audit` / `npm audit` | composer: ✅ · npm: crít 0 · alta 1 (R8, ajena a la 020) |
| trivy 0.57.1 `fs --severity HIGH,CRITICAL` | ✅ 0 hallazgos |

| Tema OWASP | Resultado | Evidencia |
|---|---|---|
| A01:2025 Broken Access Control | ✅ | R5 y R6 corregidos: el id guardado cubre espacio final, mayúsculas y equivalencias de la colación |
| A02:2025 Security Misconfiguration | ✅ | `scheduler` sin capacidades ni escalada (R23); guardas que fallan cerradas (R4) |
| A03:2025 Software Supply Chain Failures | ⚠ | R8 |
| A05:2025 Injection | ✅ | Sin cambios en consultas; `\z` en los patrones (R9) |
| A06:2025 Insecure Design | ✅ | Trigger de borrado fijado por tests (R2) |
| A09:2025 Logging & Alerting Failures | ✅ | Ningún mensaje nuevo de los scripts imprime secretos |
| A10:2025 Mishandling of Exceptional Conditions | ⚠ | R7 y R5 corregidos; R36 (indicación errónea si cae el SSH durante la carga) |
| A04, A07, A08 | ➖ | Sin cambios en esos temas |

## Observabilidad
- Eventos de auditoría de la spec emitidos y probados: sí, también la denegación sin ficha de
  paciente (R6, `DenegacionesAuditadasTest::test_un_usuario_sin_ficha_de_paciente_deja_su_denegacion`).
- Datos sensibles en logs del código tocado: ninguno.
- `request_id` presente en los logs nuevos: sin logs nuevos en las correcciones.

## SEO
| Tema | Resultado | Evidencia |
|---|---|---|
| 2. Páginas de error y rutas protegidas | ✅ | Sin cambios desde la ronda 1 (la 503 sigue con estado 503 y `noindex`) |
- Sección «Descubribilidad» del plan aplicada: sí (sin cambios).

## Hallazgos
| ID | Severidad | Archivo:línea | Hallazgo | Sugerencia |
|---|---|---|---|---|
| R36 | importante | deploy/restaurar.sh:185 (frente a :197-198) | Regresión de la corrección de R4: la lista de servicios se vuelve a leer antes de comprobar `resultado`. Si la carga se corta porque cae el SSH, la lectura también falla y el script sale con «levanta app, web y el scheduler a mano», con la base vacía o a medias, sin llegar al «repite la orden antes de levantar app y web». | Leer la lista solo donde se usa: en la rama «ningún dato descifrado» sin `--volver`, y tras la comprobación de carga incompleta, justo antes de «Levantando»; un test que fije ese orden |
| R37 | menor | tests/Feature/ConfiguracionProduccionTest.php:788 | `preg_replace('/\berror ".*$/', …)` borra hasta el final de la línea: una orden sin guarda escrita tras el mensaje (`… \|\| error "m"; … up -d scheduler`) pasaría. | Quitar solo la cadena del mensaje (`/\berror "[^"]*"/`) y añadir ese caso como control |
| R38 | menor | tests/Feature/Auditoria/AuditoriaVistasTest.php:116 | La regla de multiplicadores no ve las divisiones (`/ 2`) ni `* .5`. | Detectar `*` o `/` seguidos de un número, también `.5`; solo `* -1` permitido |
| R39 | menor | app/Http/Controllers/AuditoriaController.php:51,57 | Las reglas de filtro siguen con `$`. Hoy `TrimStrings` quita el salto de línea; sin él, `recurso_id=x%0A` validaría y `Auditoria` lanzaría la excepción: 500. | `\z` también en estas reglas |
| R40 | menor | tests/Feature/Auditoria/RegistroAuditoriaTest.php:168 | El test de R9 no cubre el patrón de `campos` (`Auditoria.php:130`) ni `recurso_tipo`. | Casos `campos: ["telefono\n"]` y `recurso_tipo` `"pacientes\n"` |
| R41 | menor | public/css/errores.css:3 | El comentario de cabecera dice «system.md 1.7.0»; la hoja usa los tokens de la 1.8.0. | Actualizarlo a 1.8.0 |

## Preparación para release
- Rollback factible: sí. La vuelta sin copia no cambia. En la vuelta con copia (`restaurar.sh`), R36
  da una indicación errónea solo si cae el SSH durante la carga; se corrige en esta ronda.
- Migraciones compatibles con la versión anterior: sí (sin cambios).
- Docs actualizadas (architecture, deployment): sí; T064 añade la vuelta sin copia de la primera
  liberación.
- SCA: R8 pendiente de la chore (condición del Rollout antes de `/release`).

## Tareas añadidas
- T072 ← R36, R37 (test)
- T073 ← R36
- T074 ← R38
- T075 ← R40
- T076 ← R39 (test)
- T077 ← R39
- T078 ← R41
- T079 ← verificación final de la ronda 2

## Aceptados sin tarea
- **R8** → roadmap: `npm audit` de la 020 depende de la chore de `follow-redirects` 1.16.2, que debe llegar a `main` y fusionarse en la rama antes de `/release 020`.
- **R10** → roadmap: patrones de identificador duplicados entre `Auditoria` y `RegistroCentralDenegaciones`.
- **R11** → roadmap: el p95 de `registrar` se mide dentro de una transacción, sin el coste del commit.
- **R12** → roadmap: índices de `auditoria` para filtrar por `actor_id` y por `recurso_id` sin tipo.
- **R13** → roadmap: docblock desplazado en `VigilanteTest`.
- **R14** → roadmap: la fila de middlewares de `AGENTS.md` no nombra `IdentificadorPeticion` ni `ContextoActor`.
- **R15** → roadmap: `verificar.sh` no compara la imagen del `scheduler` con la de `app`.
- **R22** → roadmap: `app` sin `pull_policy` con una imagen del espacio `library/`.
- **R25** → roadmap: enlace del teléfono del 503 con `href` vacío si falta `telefono_enlace`.
- **R27** → roadmap: «Todas» en el selector «Tipo de recurso» (también en c4).
- **R28** → roadmap: el aviso de filtros rechazados no lleva al primer campo con error.
- **R29** → roadmap: `data-label="Origen"` frente a «Origen y petición» en el teléfono.
- **R30** → roadmap: un filtro rechazado vuelve a `url()->previous()`.
- **R31** → roadmap: `valoresRegistrados()` antes del registro crítico (500 en vez de 503 si cae la base).
- **R32** → roadmap: `page` fuera de rango en la consulta del registro.
- **R34** → roadmap: test del enlace «Auditoría» solo para el admin.
- **R35** → roadmap: inventario de `docs/seo.md` sin la 503 de auditoría ni la vista previa.
