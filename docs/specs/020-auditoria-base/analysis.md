---
result: pass
round: 7
mode: delta
constitution_version: 1.3.0
date: 2026-10-29
spec_sha: 5547ed748a7e
plan_sha: b51502e81e00
tasks_sha: 2477f23ed362
---

# Análisis · 020 Registro de auditoría base y correlación por petición

## Resumen
Resultado **pass**. `/specify --edit`, `/plan --fix` y `/tasks --fix` resuelven todos los hallazgos de la
ronda 6 (A66–A79). Quedan cinco nuevos sin ninguno crítico ni alto (A80–A84). Ninguno cambia el
diseño, y el usuario los acepta como notas de tarea para `/implement`.
- **Método:** delta contra la ronda 6 (`aidd.py changes`, unos 117 KB por las líneas largas). Lo hizo un
  subagente independiente (perfil `standard`, `sonnet`) que comprobó contra el código las
  correcciones: `ClienteCitasController`, `CambiarEstadoCita`, `ConfiguracionProduccionTest`, los
  scripts de `deploy/`, los dos Compose, `capturar.mjs`, `medir.mjs`, `criterios.json`, el layout,
  `config/clinica.php` y `Vigilante::interpretarFallo`. Cada hallazgo se verificó después abriendo el
  archivo citado (A83: `deploy/restaurar.sh:177-182`).
- **Sin cambio estructural:** no hay módulo, contrato ni criterio nuevos. Dos tareas se dividieron
  (T051, T053) y se añadieron T050 y T052, todas dentro de los cambios del plan.
- **Validador:** 0 errores. Avisos: spec grande (decisión del usuario D0) y análisis desactualizado
  (antes de esta ronda).

Ronda anterior: [history/analysis.r6.md](history/analysis.r6.md).

Conteo: CRÍTICA 0 · ALTA 0 · MEDIA 4 · BAJA 1

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 14 / 14 (CA4 ya es viable con T050) |
| Amenazas con control y test | 18 / 18 (TM17 y TM18 con su residuo aceptado) |
| Principios de la constitución evaluados | 10 / 10. P2 con A80 y A82 como notas; P3 con la exención de la vista previa registrada (A73); P8 ❌ con la excepción aceptada (A44) |

## Seguimiento de rondas anteriores
| ID | Severidad | Estado | Evidencia |
|---|---|---|---|
| A66 | CRÍTICA | resuelto | Plan D4 y Contratos (tabla de la cita ajena); T013 (cuatro rutas reales, id inexistente, misma respuesta) y T050. Código: `show` (`ClienteCitasController.php:72-98`) y `citaDelPaciente` (`:193-216`) hacen `abort(404)` antes del Gate, y T050 los cambia; `cancelar` (`:113-120`) llega al Gate con `denyAsNotFound` (`CambiarEstadoCita.php:41-46`) |
| A67 | ALTA | resuelto con efecto nuevo (→ A80, A81) | Plan D2 y Compose; T019, T040, T043 (`restart: unless-stopped` y el mismo `local.ini`; tests ajustados por servicio) |
| A68 | ALTA | resuelto con efecto nuevo (→ A83) | Plan D22; T044, T051, T047. Parar antes del checkout y `up -d --remove-orphans db` cubren el destino sin `scheduler`; T051 actualiza la expresión de `ConfiguracionProduccionTest.php:637` |
| A69 | ALTA | resuelto | Plan D24; T052, T046. «Auditoría» es la última entrada de la barra y `.sidebar-user` va aparte (`layouts/app.blade.php`), así que no desplaza nada; `EN_PAGINA.excluidos` (`capturar.mjs:694`) admite el rectángulo |
| A70 | MEDIA | resuelto | Plan D19; T017, T037, T053. `medir.mjs:53` ya usa `p.url`; T053 dice qué salida se acepta |
| A71 | MEDIA | resuelto con efecto nuevo (→ A80, A82, A84) | T006, T028, T029, T030, T036 citan solo lo que sus dependencias permiten |
| A72 | MEDIA | resuelto | `spec.md` (`extends: [030, 031, 032]`, Decisiones, Historial); T098 |
| A73 | MEDIA | resuelto | Plan P3 (exención con el usuario como fuente), D19, TM15; T017 (`test_la_vista_previa_no_consulta_la_base`), T037 |
| A74 | MEDIA | resuelto | Plan Rollout y D22; T045, T051 (`test_aplicar_entorno_recrea_el_servicio_y_termina_verificando`) |
| A75 | BAJA | resuelto | T013, T019 (caracterización con su rotura); T018 se escribe para fallar hoy (`avisos.umbral_fallos`) |
| A76 | BAJA | resuelto | Plan Rollout; T045, T097 (anotar la versión instalada, checkout a mano, volver a la anotada) |
| A77 | BAJA | resuelto | Plan D23; T016, T036. Misma clave y valores que la D16 de la 041 |
| A78 | BAJA | resuelto | Plan → Modelo de amenazas, TM17 y TM18 con residuo aceptado (usuario, 2026-10-28) |
| A79 | BAJA | resuelto | T090 y T091 con archivos y «hecho cuando» |

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A80 | Constitución (P2, dependencias) | MEDIA | T019 (`test_el_scheduler_se_reinicia_solo_y_monta_el_local_ini_de_app`), T040, T043, T045 | El test comprueba en uno solo el Compose local y el de producción. T040 y T043 no dependen una de otra, así que ninguna puede ponerlo en verde sola, y T045 exige `ConfiguracionProduccionTest` completo sin tener a T040 en su cadena | Cada «hecho cuando» cita solo tests que sus dependencias pueden poner en verde. Test: el caso local lo cierra T040 y el de producción T043, por separado o con un proveedor de datos por archivo, y T045 corre tras T040 |
| A81 | Inconsistencia (plan frente a tareas y test) | MEDIA | Plan → Cambios por módulo (tests) y D2; T019; `ConfiguracionProduccionTest.php:72-82` (`imagenes()`), `:208-217` | T019 dice que `imagenes()` deja fuera el `image:` de un servicio con `build:`. Pero `imagenes()` lee el texto, y el `image:` del `scheduler`, que es un servicio sin `build:`, seguiría contando y haría fallar el test de versiones | Se excluye solo la imagen que construye el Compose (la de `app`) y toda aparición de ese mismo valor (el `scheduler`). Cualquier otra imagen que se descarga sigue con versión exacta. Test: el de imágenes pasa con el `scheduler` y falla si `mariadb:10.11.19` pasa a `mariadb:10.11` (rotura ya prevista en T019) |
| A82 | Ambigüedad / P2 | MEDIA | T017, T030, T036 | `test_las_vistas_nuevas_no_tienen_estilos_en_linea` y `test_el_css_nuevo_solo_usa_tokens` cubren a la vez la consulta (T030) y la página 503 (T036). T030 y T036 piden «sus casos», pero los casos no están separados | Cada vista y cada CSS tienen su caso: un proveedor de datos con un caso por archivo, o un test por vista. Test: T030 cierra los de la consulta y T036 los del 503, cada una por su lado |
| A83 | Inconsistencia (plan D22 frente a `restaurar.sh`) | MEDIA | Plan D22; T044, T051; `deploy/restaurar.sh:177-182` | Sin `--volver`, si no llega ningún dato descifrado (frase cancelada), `restaurar.sh` vuelve a levantar `app web` y sale. Con D22, el `scheduler` ya estaría parado y la purga quedaría parada sin aviso hasta que lo detecte `verificar.sh` | Toda salida de `restaurar.sh` que vuelve a levantar `app` y `web` en la misma versión levanta también el `scheduler` si el Compose lo tiene. Test: `test_restaurar_para_todo_lo_que_escribe_antes_de_cargar` (T051) comprueba también la rama de «ningún dato descifrado» |
| A84 | Ambigüedad | BAJA | T029; T014 (`test_el_error_de_un_filtro_queda_junto_a_su_campo`); plan → Rutas | T029 pide que los filtros inválidos «vuelvan con sus errores sin 500», pero el HTML del error es de T030. No dice qué parte comprueba cada tarea | T029 comprueba la redirección con los errores de validación en la sesión (`assertSessionHasErrors`, sin 500 y sin `auditoria_consultada`). T030 comprueba el HTML junto al campo (`aria-invalid`, `aria-describedby`, `role="alert"`) |

## Aceptados
Aceptados por el usuario el 2026-10-29, para resolver en `/implement`:
- **A80** → nota de T019, T040, T043, T045: el test del `scheduler` tiene un caso por Compose (proveedor de datos o dos tests); T040 cierra el local, T043 el de producción y T045 se ejecuta después de T040 para exigir `ConfiguracionProduccionTest` completo
- **A81** → nota de T019, T043: `imagenes()` excluye la imagen que construye el Compose y toda aparición de ese mismo valor (el `scheduler`); cualquier otra imagen sigue con versión exacta, con la rotura `mariadb:10.11` comprobada
- **A82** → nota de T017, T030, T036: `test_las_vistas_nuevas_no_tienen_estilos_en_linea` y `test_el_css_nuevo_solo_usa_tokens` con un caso por vista y por CSS; T030 cierra los de la consulta y T036 los del 503
- **A83** → nota de T044, T051: la salida de `restaurar.sh` sin datos descifrados (sin `--volver`) vuelve a levantar también el `scheduler` si el Compose lo tiene, con su caso en `test_restaurar_para_todo_lo_que_escribe_antes_de_cargar`
- **A84** → nota de T029, T030: T029 comprueba la redirección con errores en la sesión, sin 500 y sin evento; T030, el HTML del error junto a su campo
