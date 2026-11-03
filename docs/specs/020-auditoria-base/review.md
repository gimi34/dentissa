---
spec: 020-auditoria-base
verdict: approved
round: 3
date: 2026-10-31
base: db30bbd
head: 47485cb
human_signoff: no requerido
---

# Review · 020 Registro de auditoría base y correlación por petición

## Resumen
Aprobada. Las correcciones de la ronda 2 (R36–R41) están hechas y verificadas, sin bloqueantes ni
importantes nuevos. El único hallazgo nuevo, R42 (menor), lo acepta el usuario sin tarea. Antes de
`/release 020` hay que cumplir la condición del Rollout que deja R8: la chore de `follow-redirects`
1.16.2 en `main` y `main` fusionada en la rama, con `npm audit --audit-level=high` en código 0.
Conteo (ronda 3, nuevos): bloqueante 0 · importante 0 · menor 1

Ronda `--rerun` sobre `db30bbd..47485cb` (diff de código de 12 KB, por debajo del umbral de 30 KB):
revisada sin subagente, releyendo los archivos desde cero. Rondas anteriores en
`history/review.r1.md` y `history/review.r2.md`.

## Verificación automática
| Comando | Resultado |
|---|---|
| `php artisan test` (contra `dentissa_test`) | ✅ 545 pasan (8350 aserciones); sin línea base de fallos |
| `vendor/bin/pint --test` (5 PHP tocados en la ronda 2) | ✅ (T079) |
| `bash -n` sobre `desplegar.sh`, `volver.sh` y `restaurar.sh` | ✅ |
| build | no aplica |
| `python .ai/bin/aidd.py validate docs/specs/020-auditoria-base` | ✅ 0 errores |
| `design/medir.mjs` sobre `/_disenio/020` | ✅ sin cambios de interfaz desde T058 (código 0, mismo `resumen.md` que T053); la ronda 2 solo cambió un comentario de `errores.css` |

## Criterios de aceptación
| CA | Test | Estado | Nota |
|---|---|---|---|
| CA1 | `RegistroAuditoriaTest::test_un_evento_guarda_todos_los_campos` y otros | ✅ | |
| CA2 | `InmutabilidadAuditoriaTest::test_sin_el_indicador_no_se_borra_un_evento_antiguo` y otros | ✅ | |
| CA3 | `RegistroAuditoriaTest::test_un_valor_con_salto_de_linea_final_se_rechaza` (también `campos` y `recurso_tipo`) y otros | ✅ | R40 corregido |
| CA4 | `DenegacionesAuditadasTest::test_una_cita_ajena_con_espacio_final_responde_como_la_inexistente` y otros | ✅ | |
| CA5 | `InmutabilidadAuditoriaTest::test_con_el_indicador_el_trigger_corta_a_los_5_anios_en_utc`; `ConfiguracionProduccionTest::test_restaurar_lee_los_servicios_despues_de_comprobar_la_carga` y otros | ✅ | R36 corregido |
| CA6 | `ConsultaAuditoriaTest::test_un_filtro_con_salto_de_linea_final_vuelve_con_su_error` y otros | ✅ | R39 corregido |
| CA7 | `ConsultaAuditoriaTest::test_un_no_admin_no_consulta_el_registro` | ✅ | |
| CA8 | `IdentificadorPeticionTest::test_se_reutiliza_un_identificador_valido` y otros | ✅ | |
| CA9 | `IdentificadorPeticionTest::test_un_identificador_malformado_se_sustituye` y otros | ✅ | |
| CA10 | `IdentificadorPeticionTest::test_logs_y_eventos_comparten_request_id_y_actor` y otros | ✅ | |
| CA11 | `AuditoriaVistasTest::test_el_css_de_la_020_no_trae_literales_de_tipografia_ni_multiplicadores` y otros; `ErrorAuditoriaTest` | ✅ | R38 y R41 corregidos; R42 aceptado |
| CA12 | `ConsultaAuditoriaTest::test_la_consulta_queda_registrada_con_sus_filtros` y otros | ✅ | |
| CA13 | `IdentificadorPeticionTest::test_un_identificador_repetido_no_transfiere_el_actor` y otros | ✅ | |
| CA14 | `VigilanteTest::test_un_fallo_de_auditoria_avisa_por_correo` y otros | ✅ | |

## Conformidad con el plan
- Cambios fuera del plan: ninguno (`scope.md`: 0 archivos sin tarea).
- Partes del plan sin implementar: ninguna (`scope.md`: 0 tareas sin diff; 74/74 tareas).

## Estado de los hallazgos de la ronda 2
| Hallazgo | Estado | Evidencia |
|---|---|---|
| R36 | corregido | `restaurar.sh:189,202`: la lista se lee en la rama «ningún dato descifrado» y tras la comprobación de carga incompleta, no antes; `servicios` es `local` (:164); `ConfiguracionProduccionTest::test_restaurar_lee_los_servicios_despues_de_comprobar_la_carga` falla con la versión anterior (T072) |
| R37 | corregido | `ConfiguracionProduccionTest.php:789` quita solo la cadena del mensaje; el control de una orden tras el mensaje falla con el ayudante anterior (T072). Ningún mensaje de los scripts lleva comillas escapadas, así que `[^"]*` no corta uno a medias |
| R38 | corregido | `AuditoriaVistasTest.php:116-121`: divisiones y `* .5` dentro de `calc()`; comprobado rompiendo `errores.css` (T074). Hueco restante: R42 |
| R39 | corregido | `AuditoriaController.php:51,58` con `\z`; `ConsultaAuditoriaTest::test_un_filtro_con_salto_de_linea_final_vuelve_con_su_error` (sin `TrimStrings`) daba 500 antes |
| R40 | corregido | `RegistroAuditoriaTest.php:175-176`; comprobado volviendo a `$` cada patrón (T075) |
| R41 | corregido | `errores.css:3` nombra la 1.8.0 |
| R8 | pendiente fuera de la 020 | `npm audit`: 1 alta (`follow-redirects` 1.16.1); la corrige `chore/follow-redirects-1.16.2` (`docs/changes/2026-10-30-chore-follow-redirects-1.16.2.md`), que aún no está en `main` |

## Constitution Check (sobre el código)
| Principio | Resultado | Evidencia |
|---|---|---|
| P1 Spec aprobada antes de código | ✅ | Tareas de `/review` aprobadas por el usuario |
| P2 Test que falla antes y pasa después | ✅ | T072, T076 fallan antes; T074, T075 comprobados rompiendo lo protegido |
| P3 Autorización en servidor | ✅ | Sin cambios |
| P4 Entrada validada y SQL con bindings | ✅ | R39: reglas de filtro con `\z` |
| P5 Nada sensible en el repo | ✅ | gitleaks sin fugas |
| P6 Ninguna crítica o alta sin excepción aprobada | ⚠ | R8, condición de `/release` |
| P7 Auditoría de accesos y cambios a datos sensibles | ✅ | Sin cambios |
| P8 Correlación por petición y logs sin datos sensibles | ✅ | Los mensajes nuevos de `restaurar.sh` no imprimen valores |
| P9 Solo el sistema de diseño | ✅ | Sin literales nuevos; R42 es un hueco del test, no del CSS |
| P10 Las rutas indexables son descubribles | ✅ | Sin cambios |

## Seguridad
| Herramienta | Resultado |
|---|---|
| gitleaks v8.21.2 (commits de la rama) | ✅ 0 hallazgos (T079) |
| semgrep 1.99.0 `p/php` (`--baseline-commit`) | ✅ 0 nuevos (T079) |
| `composer audit` / `npm audit` | composer: ✅ · npm: crít 0 · alta 1 (R8, ajena a la 020) |
| trivy 0.57.1 `fs --severity HIGH,CRITICAL` | ✅ 0 hallazgos (ronda 2; sin cambios de dependencias ni imágenes desde entonces) |

| Tema OWASP | Resultado | Evidencia |
|---|---|---|
| A01:2025 Broken Access Control | ✅ | Sin cambios |
| A02:2025 Security Misconfiguration | ✅ | Guardas del `scheduler` que fallan cerradas, también tras la carga (R36) |
| A03:2025 Software Supply Chain Failures | ⚠ | R8 |
| A05:2025 Injection | ✅ | Reglas de filtro con `\z` (R39) |
| A10:2025 Mishandling of Exceptional Conditions | ✅ | R36: con el SSH caído durante la carga, el mensaje es el de la carga incompleta |
| A04, A06, A07, A08, A09 | ➖ | Sin cambios en esta ronda |

## Observabilidad
- Eventos de auditoría de la spec emitidos y probados: sí (sin cambios en esta ronda).
- Datos sensibles en logs del código tocado: ninguno.
- `request_id` presente en los logs nuevos: sin logs nuevos.

## SEO
| Tema | Resultado | Evidencia |
|---|---|---|
| 2. Páginas de error y rutas protegidas | ✅ | Sin cambios desde la ronda 1 |
- Sección «Descubribilidad» del plan aplicada: sí (sin cambios).

## Hallazgos
| ID | Severidad | Archivo:línea | Hallazgo | Sugerencia |
|---|---|---|---|---|
| R42 | menor | tests/Feature/Auditoria/AuditoriaVistasTest.php:118-119 | La regla de R38 solo mira el número a la derecha del operador: `calc(2 * var(--x))` no se detecta (comprobado). Hoy ninguna hoja de la 020 lo usa. | Detectar también un número seguido de `*` o `/` dentro de `calc()` |

## Preparación para release
- Rollback factible: sí. Vuelta sin copia ensayada (T047), con la orden explícita de la primera
  liberación (T064); vuelta con copia con el `scheduler` parado durante la carga y mensajes
  correctos también si cae el SSH (R36).
- Migraciones compatibles con la versión anterior: sí (solo expansión: tabla y triggers nuevos).
- Feature flags: no aplica (plan, Rollout).
- Docs actualizadas (architecture, deployment): sí (T090, T091, T064).
- SCA: **condición antes de `/release 020`** (Rollout; R8): fusionar en `main` la chore
  `chore/follow-redirects-1.16.2` y `main` en `feature/020-auditoria`; `npm audit --audit-level=high`
  con código 0. El merge de la 024 (`fddca7d`) ya es ancestro de la rama.
- Cobertura de riesgos: las correcciones OB de la 020 siguen «en curso (spec 020)» hasta `/release`.

## Tareas añadidas
Ninguna en esta ronda. Las de las rondas anteriores (T054–T071 y T072–T079) están en
`history/review.r1.md` y `history/review.r2.md`.

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
- **R42** → roadmap: la regla de literales del CSS de la 020 no detecta un multiplicador numérico a la izquierda (`calc(2 * var(--x))`).
