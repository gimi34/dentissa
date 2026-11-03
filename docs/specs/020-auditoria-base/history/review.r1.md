---
spec: 020-auditoria-base
verdict: changes_requested
round: 1
date: 2026-10-30
base: 811e822
head: 277bcd1
human_signoff: no requerido
---

# Review · 020 Registro de auditoría base y correlación por petición

## Resumen
Cambios pedidos. Los 14 criterios tienen tests que pasan, pero la hoja del error 503 trae valores
literales nuevos que P9 prohíbe (R1). Además hay seis riesgos reales en la implementación: tests que
no fijan el trigger de borrado (R2); una orden de vuelta engañosa en la primera liberación (R3);
comprobaciones del `scheduler` que fallan abiertas en los scripts (R4); dos rutas de las citas del
paciente que rompen CA4 (R5, R6); y un 500 con filtros enviados como array (R7). R8 (`npm audit`) se
resuelve con la chore de `follow-redirects` antes de liberar. El usuario decidió corregir R1–R7 y
los menores baratos; el resto de menores va al roadmap.
Conteo: bloqueante 1 · importante 7 · menor 27

Revisión por módulos (el diff de código ocupa 220 KB): núcleo, consulta e interfaz, y despliegue,
cada uno con los encargos A, C+D y S y perfil `critical` (Opus); accesibilidad y SEO con perfil
`standard` (Sonnet). `design` no está en `skills.enabled`: la accesibilidad se revisó con la lista
de la skill, sin `design:accessibility-review` ni `design:design-critique`.

## Verificación automática
| Comando | Resultado |
|---|---|
| `php artisan test` (contra `dentissa_test`) | ✅ 530 pasan (8195 aserciones); sin línea base de fallos |
| `vendor/bin/pint --test` (69 PHP cambiados) | ✅ |
| build | no aplica (solo `welcome.blade.php` usa Vite; ni la CI ni la imagen lo construyen) |
| `python .ai/bin/aidd.py validate docs/specs/020-auditoria-base` | ✅ 0 errores |
| `design/medir.mjs` sobre `/_disenio/020` con `criterios.json` | ✅ código 0: las seis pantallas pasan; solo las excepciones de la barra lateral (DS1 y el menú cerrado del teléfono); ninguna imagen `data-origen="muestra"` |
| `capturar.mjs --medir` | ✅ en T046 (2026-10-30, mismo código de interfaz): 27 comprobaciones, `fallos: []` |

## Criterios de aceptación
| CA | Test | Estado | Nota |
|---|---|---|---|
| CA1 | `RegistroAuditoriaTest::test_un_evento_guarda_todos_los_campos` y otros | ✅ | |
| CA2 | `InmutabilidadAuditoriaTest::test_un_evento_no_se_puede_modificar`, `…_borrar`, `test_un_borrado_masivo_se_rechaza`; ensayo T047 | ⚠ | Pasan, pero no fijan las condiciones del trigger de borrado (R2) |
| CA3 | `RegistroAuditoriaTest::test_los_campos_solo_admiten_nombres`, `test_el_detalle_rechaza_texto_libre` | ✅ | Los patrones aceptan un salto de línea final (R9, menor) |
| CA4 | `DenegacionesAuditadasTest::test_una_cita_ajena_denegada_como_no_encontrada_queda_registrada` y otros | ❌ | Un id con espacio final da 500 (R5); sin ficha de paciente, 404 sin evento (R6) |
| CA5 | `InmutabilidadAuditoriaTest::test_la_purga_elimina_solo_eventos_de_mas_de_5_anios`, `test_la_purga_respeta_el_corte_al_segundo`, `test_el_corte_no_desborda_los_anios_bisiestos`; ensayo T047 | ⚠ | El umbral del trigger no está fijado (R2) |
| CA6 | `ConsultaAuditoriaTest::test_el_admin_consulta_el_registro`, `test_el_admin_filtra_por_actor_accion_recurso_y_fechas` y otros | ⚠ | Filtro como array → 500 (R7); el caso de `recurso_id` puede pasar con la tabla vacía (R33) |
| CA7 | `ConsultaAuditoriaTest::test_un_no_admin_no_consulta_el_registro` | ✅ | |
| CA8 | `IdentificadorPeticionTest::test_se_reutiliza_un_identificador_valido` y otros | ✅ | |
| CA9 | `IdentificadorPeticionTest::test_un_identificador_malformado_se_sustituye` y otros | ✅ | |
| CA10 | `IdentificadorPeticionTest::test_logs_y_eventos_comparten_request_id_y_actor` y otros | ✅ | |
| CA11 | `RegistroAuditoriaTest::test_en_modo_critico_un_fallo_detiene_la_accion` y otros; `ErrorAuditoriaTest` | ✅ | La hoja de la 503 incumple P9 (R1) |
| CA12 | `ConsultaAuditoriaTest::test_la_consulta_queda_registrada_con_sus_filtros`, `InmutabilidadAuditoriaTest::test_la_purga_queda_registrada` | ✅ | |
| CA13 | `IdentificadorPeticionTest::test_un_identificador_repetido_no_transfiere_el_actor` y otros | ✅ | |
| CA14 | `VigilanteTest::test_un_fallo_de_auditoria_avisa_por_correo` y otros | ✅ | |

## Conformidad con el plan
- Cambios fuera del plan: ninguno. `scope.md` marca la migración y `docker-compose.yml` como «sin
  tarea», pero las citan T001 y T031 (con el nombre genérico `AAAA_MM_DD_…`) y T040. Los tres
  archivos extra de T053 (logos con `data-origen`, zona de toque del teléfono) los aprobó el usuario.
- Partes del plan sin implementar: ninguna. Las tareas sin diff de código son de documentación
  (T041, T048) o de `docs/design/` (T052, T053), que el paquete de review deja fuera.

## Constitution Check (sobre el código)
| Principio | Resultado | Evidencia |
|---|---|---|
| P1 Spec aprobada antes de código | ✅ | `aidd.py status`: spec, plan y tareas aprobados; `impl_base` 811e822 |
| P2 Test que falla antes y pasa después | ⚠ | Notas de fallo previo en cada tarea de test; R2: el trigger de borrado no tiene un test que falle si se debilita |
| P3 Autorización en servidor | ✅ | `/admin/auditoria` en el grupo `role:admin`; `ConsultaAuditoriaTest` prueba los cuatro roles |
| P4 Entrada validada y SQL con bindings | ❌ | Un filtro como array termina en 500 (R7); un id con espacio final, en 500 (R5) |
| P5 Nada sensible en el repo | ✅ | gitleaks sin fugas |
| P6 Ninguna crítica o alta sin excepción aprobada | ⚠ | `npm audit`: 1 alta ajena a la 020 (R8), resuelta por la chore de `follow-redirects` |
| P7 Auditoría de accesos y cambios a datos sensibles | ❌ | R6: una denegación sobre una cita real sin evento |
| P8 Correlación por petición y logs sin datos sensibles | ✅ | `IdentificadorPeticionTest`, `RegistroAuditoriaTest::test_un_fallo_del_almacen_no_deja_sql_ni_valores_en_el_log` |
| P9 Solo el sistema de diseño | ❌ | R1: `public/css/errores.css:14,17,22,25,26,29,30,33`, literales nuevos; `ReglasCss::literales()` no los detecta |
| P10 Las rutas indexables son descubribles | ✅ | Ninguna ruta indexable cambia; la 503 responde 503 con `noindex` |

## Seguridad
| Herramienta | Resultado |
|---|---|
| gitleaks v8.21.2 (commits de la rama y repo completo) | ✅ 0 hallazgos (61 y 826 commits) |
| semgrep 1.99.0 `p/php` (`--baseline-commit` con `origin/main`) | ✅ 0 nuevos; 0 en el repo completo (123 archivos) |
| `composer audit` / `npm audit` | composer: ✅ · npm: crít 0 · alta 1 (`follow-redirects` 1.16.1, GHSA-mrwp-f3q8-3g9p; ajena a la 020, R8) |
| trivy 0.57.1 `fs --severity HIGH,CRITICAL` | ✅ 0 hallazgos |

| Tema OWASP | Resultado | Evidencia |
|---|---|---|
| A01:2025 Broken Access Control | ❌ | Consulta solo admin ✅ (`ConsultaAuditoriaTest`); vista previa solo en local ✅ (`DisenioAuditoriaTest::test_la_vista_previa_no_existe_fuera_de_local`); R5 y R6 en las citas del paciente |
| A02:2025 Security Misconfiguration | ⚠ | `scheduler` sin puertos, sin root, `pull_policy: never` ✅; R4 (guardas que fallan abiertas); `cap_drop` y `no-new-privileges` (R23) |
| A03:2025 Software Supply Chain Failures | ⚠ | Ninguna dependencia nueva; R8 (`follow-redirects`, ajena a la 020) |
| A05:2025 Injection | ✅ | Filtros con `validate` y bindings; triggers con SQL estático; salida con `{{ }}` |
| A06:2025 Insecure Design | ⚠ | Inmutabilidad por triggers ✅ (ensayo T047); R2: su test no fija las condiciones |
| A08:2025 Software or Data Integrity Failures | ✅ | Registro solo de anexado (triggers) |
| A09:2025 Logging & Alerting Failures | ✅ | Eventos obligatorios con test; aviso por correo sin datos del evento (CA14); R9 (salto de línea final en identificadores, menor) |
| A10:2025 Mishandling of Exceptional Conditions | ❌ | Fallo del registro → 503 genérico ✅ (CA11); R5 y R7 terminan en 500 |
| A04, A07 | ➖ | La 020 no toca cifrado ni autenticación |

## Observabilidad
- Eventos de auditoría de la spec emitidos y probados: sí (`RegistroAuditoriaTest`,
  `DenegacionesAuditadasTest`, `ConsultaAuditoriaTest`, `InmutabilidadAuditoriaTest`), salvo la
  denegación sin ficha de paciente (R6).
- Datos sensibles en logs del código tocado: ninguno
  (`test_un_fallo_del_almacen_no_deja_sql_ni_valores_en_el_log`; el aviso no lleva datos del evento).
- `request_id` presente en los logs nuevos: sí (`IdentificadorPeticionTest::test_logs_y_eventos_comparten_request_id_y_actor`).

## SEO
| Tema | Resultado | Evidencia |
|---|---|---|
| 2. Páginas de error y rutas protegidas | ✅ | `/no-existe-020` → 404; `/admin/auditoria` sin sesión → 302 a `/login`; la 503 real responde 503 con `noindex` (`ErrorAuditoriaTest::test_la_pagina_de_error_no_se_indexa`) |
| 3. Indexación y canónica | ✅ | `/registro` sin cambios (title «Dentissa · Crear cuenta»); SE1 y SE3, ya conocidos, no son de la 020 |
| Vista previa `/_disenio/020` | ✅ | Solo en local (`routes/web.php`, `DisenioAuditoriaTest`) y con `noindex` |
- Sección «Descubribilidad» del plan aplicada: sí («No aplica»; ninguna ruta de `docs/seo.md` cambia).

## Hallazgos
| ID | Severidad | Archivo:línea | Hallazgo | Sugerencia |
|---|---|---|---|---|
| R1 | bloqueante | public/css/errores.css:14,17,22,25,26,29,30,33; tests/Feature/Concerns/ReglasCss.php:64 | P9: literales nuevos en la hoja del 503: `letter-spacing: 0.08em` y `-0.01em`, `line-height: 1.15`, y multiplicadores en `calc()` sobre tokens (`* 2.5`, `* -2`, `* 2`, `* 1.2`, `* 1.5`). `literales()` solo busca px, colores, pesos y familias. | Tokens nuevos con los mismos valores en el sistema y en `:root`; `errores.css` los usa; un test de la 020 que detecte esos literales |
| R2 | importante | tests/Feature/Auditoria/InmutabilidadAuditoriaTest.php:93-141 | Los tests no fijan las dos condiciones de `auditoria_sin_delete`: ningún test borra sin el indicador un evento solo antiguo (el rechazo del borrado masivo lo dan las filas recientes), y el umbral de 5 años solo se prueba con un evento de 4 años; lo demás pasa por el comando, que nunca toca lo reciente. Un trigger sin `COALESCE(@auditoria_purga, 0) <> 1` o con `INTERVAL 4 YEAR 6 MONTH` pasa todos. | `DELETE` directos: sin el indicador, un evento de hace 7 años se rechaza; con el indicador, −5 años +1 min se rechaza y −5 años −1 min se borra |
| R3 | importante | deploy/desplegar.sh:138-145; deploy/README.md:386-405; docs/deployment.md:113-117,183-194 | En la primera liberación (checkout a mano), si la verificación falla, `desplegar.sh` imprime `restaurar.sh <copia> --volver <tag de la 020>`: cargaría una copia sin la tabla `auditoria` y dejaría la 020 en marcha con 503 en las acciones clínicas. Ni el README §9 ni `deployment.md` advierten de no seguirla. | Orden de vuelta explícita en el README §9 y en `deployment.md`: `bash deploy/volver.sh <versión anotada>`, sin copia, y no seguir la orden de `restaurar.sh` que imprime `desplegar.sh` |
| R4 | importante | deploy/volver.sh:58,87; deploy/desplegar.sh:112; deploy/restaurar.sh:171,187,198 | `if <compose> config --services \| grep -qxF scheduler` falla abierta: en un `if`, `set -e` no actúa, y si `config` o el `ssh` fallan (o hay SIGPIPE con `pipefail`), el `scheduler` se da por inexistente. Puede quedarse en marcha durante la carga de una copia, o quedarse en la versión anterior. | Leer la lista una vez con `servicios="$(… config --services)" \|\| error …` y comprobar con `grep -qxF scheduler <<< "${servicios}"`, como `aplicar-entorno.sh:35-37` |
| R5 | importante | app/Http/Controllers/ClienteCitasController.php:228-233 | Con la colación PAD SPACE, `/cliente/citas/<uuid ajeno>%20` encuentra la cita y `denegar()` recibe el id con el espacio: `Auditoria` lanza `InvalidArgumentException` y la respuesta es 500, frente al 404 de un id inexistente. Distingue una cita ajena de una inexistente (CA4, A66). | `denegar()` con el id guardado en la base (`->value('id')`); test con espacio final |
| R6 | importante | app/Http/Controllers/ClienteCitasController.php:76,197 | `abort_unless($paciente, 404)` responde 404 sin evento cuando un usuario sin ficha de paciente pide una cita que existe; `cliente.citas.show` solo lleva `auth` (RS2.a), así que un dentista llega. CA4 exige el evento `denegado_404`. | Sin paciente, `noEncontrada($id)`; test de un dentista con cita existente (404 con evento) e inexistente (404 sin evento) |
| R7 | importante | resources/views/auditoria/index.blade.php:13 | `?actor[]=x` falla la validación, pero en la vuelta `old('actor')` es un array y `{{ $valor($nombre) }}` lanza `htmlspecialchars()`: 500 (P4). | `old()` solo si es cadena; test con array que siga la redirección y espere 200 con el error junto al campo |
| R8 | importante | package-lock.json (`follow-redirects` 1.16.1) | `npm audit --audit-level=high` da 1 alta (GHSA-mrwp-f3q8-3g9p) sin excepción en `security.md`; es de `main`, no de la 020, y el Rollout exige código 0 antes de liberar. | Fusionar en `main` la chore `chore/follow-redirects-1.16.2` (`docs/changes/2026-10-30-chore-follow-redirects-1.16.2.md`) y `main` en la rama antes de `/release 020` |
| R9 | menor | app/Auditoria/Auditoria.php:29-33; app/Auditoria/RegistroCentralDenegaciones.php:27-31 | Los patrones terminan en `$` sin `D`: aceptan un salto de línea final (`"p1\n"`), que podría llegar a `recurso_id` desde un segmento de ruta con `%0A` (TM12). | `\z` en lugar de `$`; casos con `\n` en los tests |
| R10 | menor | app/Auditoria/Auditoria.php:129; app/Auditoria/RegistroCentralDenegaciones.php:27-31 | El patrón del campo se repite en línea y el registro central duplica los tres patrones de `Auditoria`. | Constantes públicas en `Auditoria` reutilizadas |
| R11 | menor | tests/Feature/Auditoria/AuditoriaRendimientoTest.php:24,67-81 | El p95 de `registrar` se mide dentro de la transacción del test, sin el coste del commit de producción. | Documentar la limitación o medir con autocommit en una base desechable |
| R12 | menor | database/migrations/2026_10_29_000000_create_auditoria_table.php:22,34 | `actor_id` sin índice, y el filtro por `recurso_id` sin tipo no usa el índice compuesto; el RNF pasa con 100 000 eventos, pero el coste crece con la retención. | Índice `(actor_id, ocurrido_en)` y, si hace falta, uno sobre `recurso_id` |
| R13 | menor | tests/Feature/VigilanteTest.php:702 | El docblock de `conSeguridadIlegible` quedó encima de `escribirFalloDeAuditoria`. | Moverlo a su método |
| R14 | menor | AGENTS.md:85 | La fila de `app/Http/Middleware/` no nombra `IdentificadorPeticion` ni `ContextoActor`. | Añadirlos (ruta protegida: con aprobación) |
| R15 | menor | deploy/verificar.sh:67-77 | No comprueba que el `scheduler` ejecute la misma imagen que `app`. | OK/FALLO comparando `docker inspect -f '{{.Image}}'` |
| R16 | menor | deploy/desplegar.sh:103-114 | El `scheduler` instalado sigue en marcha durante el build y `migrate`; un corte deja `app` nueva y `scheduler` viejo. | Pararlo, con guarda, antes de `migrate` |
| R17 | menor | deploy/volver.sh:58-65 | El `scheduler` se para antes del `git fetch`; si fallan el fetch o el build, queda parado sin aviso. | Pararlo después del fetch, justo antes del checkout |
| R18 | menor | deploy/restaurar.sh:183 | El mensaje de «ningún dato descifrado» con `--volver` no nombra el `scheduler`, también parado. | Nombrarlo en el mensaje |
| R19 | menor | deploy/README.md:286,289 | El §7 «Restaurar una copia» habla solo de `app` y `web`, y no pide el clon del PC en la 020 o posterior. | Actualizar los pasos 3 y 4 |
| R20 | menor | deploy/README.md:386-405 | La primera liberación no dice qué hacer si se olvida el checkout a mano. | `up -d scheduler` y `verificar.sh`, sin volver atrás |
| R21 | menor | tests/Feature/ConfiguracionProduccionTest.php:268-285 | El test del `scheduler` no rechaza claves de más (`environment`, `privileged`, otro volumen, otra red). | Lista exacta de claves, de volúmenes y una sola red |
| R22 | menor | docker-compose.prod.yml:14-20 | `app` tiene `image:` en el espacio `library/` sin `pull_policy`: Compose intentaría descargarla si falta. | Documentarlo o usar un nombre con espacio propio |
| R23 | menor | docker-compose.prod.yml:38-53 | El `scheduler` tiene las capacidades por defecto y no bloquea la escalada de privilegios. | `cap_drop: [ALL]` y `security_opt: ["no-new-privileges:true"]` |
| R24 | menor | resources/views/auditoria/index.blade.php:98 | La tabla de eventos no tiene nombre accesible (la `<section>` sí). | `aria-labelledby="titulo-eventos"` en la `<table>` |
| R25 | menor | resources/views/errors/auditoria-no-disponible.blade.php:37 | Sin `telefono_enlace`, el enlace queda con `href` vacío (hoy la config siempre lo define). | Enlace solo si hay `telefono_enlace` |
| R26 | menor | resources/views/errors/auditoria-no-disponible.blade.php:31 | `aria-labelledby` sobre un `<p>`: ARIA 1.2 no admite nombrar un párrafo. | Quitarlo (la etiqueta visible va justo antes) |
| R27 | menor | resources/views/auditoria/index.blade.php:52 | «Todas» también en «Tipo de recurso» (concordancia: «Todos»); igual en c4. | «Todos» en ese selector |
| R28 | menor | resources/views/auditoria/index.blade.php:30-33 | El aviso `role="alert"` no lleva al primer campo con error. | Opcional: foco o enlace al primer campo |
| R29 | menor | resources/views/auditoria/index.blade.php:139 | En el teléfono, `data-label="Origen"` no coincide con el encabezado «Origen y petición». | Alinear la etiqueta |
| R30 | menor | app/Http/Controllers/AuditoriaController.php:26 | Un filtro rechazado vuelve a `url()->previous()`: sin Referer, el error se pierde en otra página. | Redirigir siempre a `/admin/auditoria` |
| R31 | menor | app/Http/Controllers/AuditoriaController.php:27 | `valoresRegistrados()` lee antes del registro crítico: si la base cae, 500 en vez del 503 de CA11. | Leer después del registro |
| R32 | menor | resources/views/auditoria/index.blade.php:148-153 | Una `page` fuera de rango pinta una tabla vacía con «página 99 de 2». | Redirigir a la última página o mostrar el vacío |
| R33 | menor | tests/Feature/Auditoria/ConsultaAuditoriaTest.php:129,135-140 | El caso de `recurso_id` pasa aunque la tabla salga vacía: el valor aparece en el `value` del campo. | Comprobar dentro de la tabla con XPath |
| R34 | menor | docs/specs/020-auditoria-base/plan.md (Descubribilidad) | Ningún test sobre el HTML comprueba que el enlace «Auditoría» solo sale para el admin. | Opcional: test del layout para dentista y asistente |
| R35 | menor | docs/seo.md (inventario) | El inventario no lista la 503 de auditoría ni la vista previa local. | Añadirlas al liberar |

## Preparación para release
- Rollback factible: sí, con R3 corregido. Sin copia: la migración solo añade y `volver.sh` quita el
  `scheduler` (`--remove-orphans`), ensayado en T047. La orden que imprime `desplegar.sh` en la
  primera liberación es engañosa (R3), y las guardas del `scheduler` pueden fallar abiertas (R4).
- Migraciones compatibles con la versión anterior: sí (solo expansión: tabla y triggers nuevos).
- Feature flags: no aplica (plan, Rollout).
- Docs actualizadas (architecture, deployment): sí (T090, T091); R3 añade el aviso que falta.
- SCA: R8 pendiente de la chore. El merge de la 024 (`fddca7d`) es ancestro de `HEAD`.
- Cobertura de riesgos: las correcciones OB de la 020 siguen «en curso (spec 020)» hasta `/release`.

## Tareas añadidas
- T054 ← R1, R24, R26 (test)
- T055 ← R1
- T056 ← R1, R26
- T057 ← R7, R33 (test)
- T058 ← R7, R24
- T059 ← R2
- T060 ← R4, R16, R17, R18, R21, R23 (test)
- T061 ← R4, R16, R17
- T062 ← R4, R18
- T063 ← R23
- T064 ← R3, R19, R20
- T065 ← R5, R6 (test)
- T066 ← R5, R6
- T067 ← R9 (test)
- T068 ← R9
- T069 ← verificación final de las correcciones (R1–R7)

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
