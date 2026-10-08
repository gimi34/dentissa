---
result: fail
round: 2
mode: full
constitution_version: 1.2.1
date: 2026-10-08
spec_sha: 7b68114c7ac2
plan_sha: dc4c8a1df744
tasks_sha: 04d3c50d8ac1
---

# Análisis · 028 Reprogramar citas

## Resumen
No pasa. Las correcciones de la ronda 1 resuelven 25 de sus 30 hallazgos, pero quedan problemas en P9:
- Con una cita completada, la pantalla nueva muestra el badge verde «Completada» con un contraste de 2.98:1 (A31, crítico).
- El test de contraste no fija sobre qué fondo se mide cada par ni cubre las clases de los bloques tocados (A32).

A12 sigue abierto en parte. La ronda fue completa porque cambió más del 40 % de las tareas; la hizo un subagente independiente, y el agente principal verificó A31 y A34 en el código.

Conteo de nuevos: CRÍTICA 1 · ALTA 1 · MEDIA 5 · BAJA 10

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 10 / 10 |
| Amenazas con control y test | 11 / 11 |
| Principios de la constitución evaluados | 9 / 9 |

## Seguimiento de rondas anteriores
| ID | Severidad | Estado | Evidencia |
|---|---|---|---|
| A1 | CRÍTICA | resuelto con efecto nuevo (→ A31, A32, A37) | `.formulario-accesible` en D11 y "Cambios a incorporar"; T001, T024, tests de contraste y foco en T015. Contrastes recalculados correctos. |
| A2 | ALTA | resuelto con efecto nuevo (→ A35, A32) | Test por bloque en T015 y limpieza en T026, T028 y T029, pero sin `id` definidos para los bloques de acciones. |
| A3 | ALTA | resuelto | spec CA5 y fila `contradicción` con `usuario`; plan D16 e Impacto; T011, T030. |
| A4 | ALTA | resuelto (nota A42) | spec CA4, D13, test de T011, T022. |
| A5 | MEDIA | resuelto con efecto nuevo (→ A33, A43) | D10, T024, test de T015. |
| A6 | MEDIA | resuelto | plan → Rutas; T012, T024, T025. |
| A7 | MEDIA | resuelto (nota A41) | plan → Mensajes; T013, T027. |
| A8 | MEDIA | resuelto | plan → Entrada; T022; tests en T011 y T013; T027. Compatible con `ReservaCitas::reglasDeEntrada()`. |
| A9 | MEDIA | resuelto | Constructor fijado en el plan; T014, T022. |
| A10 | MEDIA | resuelto | D6 con los cuatro archivos; T021, T028. |
| A11 | MEDIA | resuelto (→ A39) | Trazabilidad marca la caracterización en CA3 y CA8. |
| A12 | MEDIA | abierto (parcial) | T015 ya separa los tests, pero T025 cierra con «los casos del personal» de `ReprogramarCitaRolesTest`: `test_el_dentista_no_puede_reprogramar` incluye la ruta `/cliente/…`, que no existe hasta T027, y `test_el_formulario_de_una_cita_que_no_se_puede_mover_muestra_el_motivo` mezcla roles. |
| A13 | MEDIA | resuelto | `aviso_plazo_corto` en T028 y T029; test en T013. |
| A14 | MEDIA | resuelto | T013 con GET. |
| A15 | MEDIA | resuelto | T011 con los bordes de 025 CA3 y CA7. |
| A16 | MEDIA | resuelto | 390 y 1440 px en P9, en Estrategia, en T025 y en T029. |
| A17 | MEDIA | resuelto (nota A45) | T040 con `trivy fs` y EX2. |
| A18 | MEDIA | resuelto | spec CA5; plan → Mensajes; T030. |
| A19 | BAJA | resuelto | plan → Estrategia. |
| A20 | BAJA | resuelto | T015 `test_el_detalle_del_dentista_no_enlaza_a_reprogramar`. |
| A21 | BAJA | resuelto | D15; T014; T022. |
| A22 | BAJA | resuelto | D14. |
| A23 | BAJA | resuelto con efecto nuevo (→ A36) | T031. |
| A24 | BAJA | resuelto | plan → Impacto. |
| A25 | BAJA | resuelto | T091. |
| A26 | BAJA | resuelto | spec CA2; T013. |
| A27 | BAJA | resuelto | T040. |
| A28 | BAJA | resuelto | D12. |
| A29 | BAJA | resuelto con efecto nuevo (→ A40) | La decisión de A40 mantiene `yaEmpezo()` para las citas ya empezadas: T029 no debe retirarlo. |
| A30 | BAJA | sin cambio | Sigue válido: la 031 solo coincide en documentos, la 014 solo en texto de `CitasController` y `AppServiceProvider`, y la 020 es el destino correcto. |

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A31 | Constitución P9 | CRÍTICA | `plan.md` → Pantallas (resumen con badge); T024; T012 | Con una cita completada, el resumen de la pantalla nueva incluye `partials/badge-estado`, que la pinta con `badge-green`: `--green` sobre `--green-bg` da 2.98:1 a 11px (DS1). No hay excepción. | Dentro de `.formulario-accesible`, el badge verde usa `--text` sobre `--green-bg` (decisión del usuario, 2026-10-08). Test: GET de reprogramar con una cita completada (personal y paciente), con el par del estado incluido en el test de contraste. `/plan --fix`, `/tasks --fix`. |
| A32 | Constitución P9 | ALTA | T015 `test_los_pares_de_contraste_cumplen_aa`; `plan.md` P9; T026 | Los pares no dicen sobre qué fondo van. El método de la 027 compara cada `color` con `--white` y `--p50`, así que daría blanco sobre blanco en `.btn-primary` y no mediría `.btn-danger` sobre su `--red-bg`. Tampoco cubre las clases de los bloques tocados: la línea «por …» del historial va hoy en `--text-light` (3.51:1), y no están las clases nuevas del historial ni los badges. | Lista explícita de pares frente/fondo de las reglas nuevas y de las clases usadas en la pantalla nueva y en los bloques tocados: ≥ 4.5:1 en texto y ≥ 3:1 en borde y foco, con un mínimo de comprobaciones. El plan dice a qué clase o color pasa la línea «por …». `/plan --fix`, `/tasks --fix`. |
| A33 | Inconsistencia | MEDIA | T015 `test_cada_formulario_…`; T012; A6 | El caso de una cancelación rechazada por cita ya cerrada no puede pintar el error en `#no-reagendar`, porque el GET de una cita cerrada muestra el aviso sin esa sección. | Aceptado como nota (ver abajo). |
| A34 | Seguridad / RNF sin 500 | MEDIA | `plan.md` → Entrada y TM5; T011, T013, T024 | Si `fecha`, `hora`, `motivo` o `formulario` llegan como lista, el PATCH responde 302. Pero el GET siguiente pinta `old()` con `e()`, y `htmlspecialchars` sobre un array da un 500. Los tests solo miran el PATCH. | Aceptado como nota (ver abajo). |
| A35 | Ambigüedad (continuación de A2) | MEDIA | `plan.md` → fila de `citas/show` y del panel del paciente; T015, T026, T028, T029 | No están definidos los `id` ni el alcance de los bloques de «acciones»: en `show`, ¿`#estado` o «Acciones rápidas»?; en `cliente-show`, `topbar-actions`; en `cliente-index`, una fila por cita, con `id` únicos. | El plan nombra cada `id` y lo que contiene; los tests de T015 buscan por esos `id` y `EstadosCitaVistasTest` sigue encontrando `#estado`. `/plan --fix`, `/tasks --fix`. |
| A36 | Realizabilidad (efecto de A23) | MEDIA | T031; `docs/design/capturar.mjs` | `capturar.mjs` tiene una lista fija de vistas y vuelve a capturar toda la galería. La base de capturas no tiene procedimiento escrito, faltan datos (una cita agendada futura, una del paciente con ≥ 24 h), las vistas nuevas no entran en el «Inventario de vistas» y la tarea necesitaría 4 archivos. | Capturar solo las vistas nuevas, ampliando `capturar.mjs` para poder elegirlas; documentar cómo se monta la base; añadir las vistas al inventario; el resto de `docs/design/capturas/` no cambia (decisión del usuario, 2026-10-08). `/tasks --fix`. |
| A37 | Constitución P9 (literales) | MEDIA | `plan.md` → «Formulario accesible» y ancho de la hora; T015, T024 | Ningún test comprueba que las reglas CSS nuevas usen solo tokens (la 027 tenía `test_las_reglas_css_nuevas_solo_usan_tokens`). No hay token de familia tipográfica. El ancho de la hora del historial es de 100px, frente a los 110px de `--field-time-width`. Falta `.formulario-accesible .field:focus` para ganar a `citas.css`, que carga `citas/show` después de `app.css`. | Las reglas nuevas no tienen px, hex/rgb ni pesos literales; la familia se hereda; el ancho usa un token que ya existe o uno nuevo añadido en T001; el borde con foco es `--border-control`. Test como el de la 027 sobre los selectores nuevos. `/plan --fix`, `/tasks --fix`. |
| A38 | Inconsistencia (nombres) | BAJA | `plan.md` P9 y Trazabilidad RNF; T015 | El plan cita `test_lo_nuevo_solo_usa_el_sistema` y `test_los_errores_van_asociados_a_su_campo`; las tareas los dividen en tests con otros nombres. | Los nombres del plan coinciden con los de las tareas. `/plan --fix`. |
| A39 | P2 (texto) | BAJA | `plan.md` P2 frente a Trazabilidad | P2 enumera 4 casos de caracterización y Trazabilidad marca 2. Los otros dos no lo son: el 404 va junto al 200, y el test de logs falla porque la clase aún no existe. | P2 dice lo mismo que Trazabilidad. `/plan --fix`. |
| A40 | Ambigüedad | BAJA | plan → Mensajes; T028; reescritura de `test_el_paciente_ve_por_que_no_puede_cancelar_una_cita_que_ya_empezo` | Una cita ya empezada o pasada mostraría «Faltan menos de 24 horas para tu cita…», que ya no es cierto. | Para las citas ya empezadas, el paciente sigue viendo «Tu cita ya empezó y no se puede cancelar. Si necesitas ayuda, llama a la clínica.»; el texto de plazo, solo cuando faltan menos de 24 h. `yaEmpezo()` se mantiene y el test de la 026 no cambia de mensaje (decisión del usuario, 2026-10-08). `/plan --fix`, `/tasks --fix`. |
| A41 | Ambigüedad | BAJA | `plan.md` → fila de `EstadosCita::comprobarReprogramable`; T021, T027 | No está fijado qué texto recibe el paciente al reprogramar una cita agendada ya empezada. | Aceptado como nota (ver abajo). |
| A42 | Realizabilidad | BAJA | D13; T022 | No se dice qué `Carbon` recibe `comprobarHueco`. El de la zona de la clínica desplazaría 6 h frente a la franja y a las citas. | Aceptado como nota (ver abajo). |
| A43 | Diseño | BAJA | D10; T024 | El layout ya pinta `session('error')`, así que pintarlo en la sección lo duplica. Tampoco está definido qué pasa si `formulario` falta o trae otro valor. | Aceptado como nota (ver abajo). |
| A44 | Terminología | BAJA | `citas/show` → «Acciones rápidas» | «Reagendar / Nueva cita» (crea otra cita) queda junto a «Reprogramar» y «No reagendar». | Se renombra a «Agendar otra cita», sin cambiar lo que hace (decisión del usuario, 2026-10-08). `/plan --fix`, `/tasks --fix`. |
| A45 | P6 | BAJA | T040 | EX2 cubre `trivy image`, no `trivy fs`, y la 028 no cambia imágenes. | Aceptado como nota (ver abajo). |
| A46 | P2 (razón del fallo) | BAJA | T013 | `test_los_mensajes_de_validacion_de_la_cancelacion_estan_en_espanol` no falla hoy por mensajes en inglés: `cancelar` no valida `motivo` y no hay error. | Aceptado como nota (ver abajo). |
| A47 | Cobertura (spec → Diseño) | BAJA | spec («errores… anunciados»); plan P9; T015 | Solo se prueban `aria-describedby` y `aria-invalid`; no está definido ni probado que los errores se anuncien. | Aceptado como nota (ver abajo). |

## Decisiones pendientes del usuario
Ninguna. Todas las tomó el usuario el 2026-10-08:
- **A31:** texto oscuro en el badge verde dentro del componente.
- **A36:** capturar solo las vistas nuevas.
- **A40:** se mantiene el texto de «ya empezó».
- **A44:** «Agendar otra cita».
- Aceptar como notas los ocho hallazgos menores.

## Aceptados
Aceptados por el usuario el 2026-10-08:
- **A33** → nota de T015, T025: el error de una cancelación rechazada se pinta en `#no-reagendar` cuando la cita sigue abierta (motivo de 501 caracteres, u `ocupada`); con la cita ya cerrada, la pantalla muestra el aviso con el motivo.
- **A34** → nota de T011, T013, T024: tras un PATCH con listas en `fecha`, `hora`, `motivo` o `formulario` (personal y paciente), el GET del formulario responde 200 con el error del campo visible. La vista solo pinta `old()` cuando es texto.
- **A41** → nota de T021, T027: un paciente que intenta reprogramar una cita agendada ya empezada, o una en curso, recibe «Esta cita ya no se puede reprogramar. Si necesitas ayuda, llama a la clínica.», con su caso en T013.
- **A42** → nota de T022: `comprobarHueco` recibe la hora local sin zona, como `reservar`; el instante en la zona de la clínica solo se usa para decidir «ya pasó».
- **A43** → nota de T024: el mensaje `error` se ve una sola vez (el del layout, o el de la sección, no los dos), y con `formulario` ausente o con otro valor, los errores de `motivo` y `estado` se pintan en la sección de reprogramar; test con `formulario` ausente.
- **A45** → nota de T040: `trivy fs` se compara con la línea base del plan; EX2 (`trivy image`) se revisa en `/release`.
- **A46** → nota de T013: la razón esperada del fallo de `test_los_mensajes_de_validacion_de_la_cancelacion_estan_en_espanol` es que hoy `cancelar` no valida `motivo`.
- **A47** → nota de T015: los errores de fecha, hora y motivo se anuncian con `role="alert"` (como `#estado`), y `test_los_errores_*` lo comprueba.

## Correcciones
1. `/plan 028 --fix A31,A32,A35,A37,A38,A39,A40,A44`
2. `/tasks 028 --fix A12,A31,A32,A35,A36,A37,A40,A44`, que también añade las notas de los aceptados.
3. `/analyze 028`, que será delta.
