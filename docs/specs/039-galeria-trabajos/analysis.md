---
result: fail
round: 2
mode: delta
constitution_version: 1.3.0
date: 2026-11-08
spec_sha: 1623177086f6
plan_sha: 061c63df4b84
tasks_sha: 0aa0e0128f0d
---

# Análisis · 039 Galería de trabajos

## Resumen
`fail` por un hallazgo alto: K9 se justifica con 12 fotos, pero la medición solo publica seis (A23).
Los 16 hallazgos abiertos de la ronda 1 quedan resueltos; seis dejaron un efecto nuevo, recogido en
los hallazgos de esta ronda. Ninguno necesita una decisión del usuario: todos se corrigen con
`/plan 039 --fix` y `/tasks 039 --fix`.
Delta hecho por un subagente independiente (perfil `standard`) sobre el diff de `aidd.py changes`
(85 KB); cada hallazgo verificado en su archivo.
Conteo: CRÍTICA 0 · ALTA 1 · MEDIA 6 · BAJA 4

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 16 / 16 |
| Amenazas con control y test | 9 / 9 |
| Principios de la constitución evaluados | 10 / 10 |

## Seguimiento de rondas anteriores
| ID | Severidad | Estado | Evidencia |
|---|---|---|---|
| A1 | ALTA | resuelto con efecto nuevo (→ A21) | plan → «Entrega de las fotos», ADR 0009, T014, T024 |
| A2 | ALTA | resuelto con efecto nuevo (→ A24) | plan P8 y Observabilidad; T012, T021 |
| A3 | ALTA | resuelto con efecto nuevo (→ A25) | plan P6 y Riesgos; T002, T030 |
| A4 | ALTA | resuelto | T019, T017 |
| A5 | ALTA | resuelto (detalle en A28) | plan D9 y `ProcesadorFoto`; T010, TM3 |
| A6 | ALTA | resuelto con efecto nuevo (→ A22, A23) | D15, ADR 0009, T010, T014, T020, T027 |
| A7 | MEDIA | resuelto | T015, T028 |
| A8 | MEDIA | resuelto | plan → Contratos, D18; T004 |
| A9 | MEDIA | resuelto | T012; TM5 |
| A10 | MEDIA | resuelto | D16, TM8, T014, T024, T091, T095 |
| A11 | MEDIA | resuelto con efecto nuevo (→ A26) | D17; T032, T033 |
| A12 | MEDIA | resuelto | nota de T096; plan → Rollout |
| A13 | MEDIA | resuelto con efecto nuevo (→ A23) | T029; plan → «Datos de medición» |
| A14 | MEDIA | resuelto | dependencias de T021 y T023 |
| A16 | BAJA | resuelto | plan → Observabilidad y D4 |
| A18 | BAJA | resuelto | plan P3 |

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A21 | Ambigüedad | MEDIA | plan → «Entrega de las fotos», TM8; T014, T095 | `ResponseHeaderBag` de Symfony añade `private` a un `Cache-Control` que solo lleva `no-cache`: la cabecera real es `no-cache, private`, y el test de «cabeceras exactas» fallaría con el valor del plan. | El contrato fija `Cache-Control: no-cache, private` (ninguna caché compartida la guarda). Lo comprueban `PaginaGaleriaTest::test_la_foto_se_sirve_con_revalidacion` y T095. `/plan --fix`, `/tasks --fix` |
| A22 | Inconsistencia | MEDIA | plan → «Entrega de las fotos» (`sizes` de la primera foto del mosaico); `c3/galeria.css:146-148` | Por debajo de 900 px la primera foto deja de ocupar dos columnas (`grid-column: auto`): mide 50vw entre 560 y 899 px y 100vw por debajo. El `sizes` del plan inventa un corte a 800 px. | El `sizes` de la primera foto sigue los cortes reales de c3. Lo comprueba `PaginaGaleriaTest::test_cada_foto_ofrece_las_dos_medidas`. `/plan --fix` |
| A23 | Cobertura | ALTA | plan → Trazabilidad (RNF de K9 «con 12 fotos»), «Datos de medición»; T029, T027, T030 | K9 se justifica con el caso de 12 fotos (unos 920 kB), pero T029 solo publica seis, así que ninguna tarea mide ese caso límite. | El script de medición puede publicar 12 fotos con grano realista. `medir.mjs` pasa K9 en `/` y `/galeria` con 12, lo anota T030, y `retirar` deja la base vacía. `/plan --fix`, `/tasks --fix` |
| A24 | Inconsistencia | MEDIA | plan → Observabilidad («un solo log») frente a T021 («por cada uno») | Si el disco no puede borrar ninguno de los dos archivos, `quitar` emite dos logs, no uno. | Una regla única: un log por archivo no borrado. El test hace fallar los dos borrados y espera dos logs, con `ancho` 800 y 1600. `/plan --fix`, `/tasks --fix` |
| A25 | Inconsistencia | MEDIA | ADR 0009 → Consecuencias («se trata como en EX2») | Contradice el plan corregido (A3). | El ADR dice lo mismo que el plan: se corrige o lo registra el usuario como excepción propia. `/plan --fix` (ADR) |
| A26 | Ambigüedad | MEDIA | T032, T033; `deploy/verificar.sh:118-119` (16 comprobaciones fijas de configuración) | Ninguna tarea dice en qué sección de `verificar.sh` van las comprobaciones ni en qué modos corren. El bloque de configuración cuenta exactamente 16 y tiene un test que lo exige. | Las comprobaciones van en una sección propia, «Galería (spec 039)», que corre con y sin `--interna`, con `-u www-data` y el contador de fallos del script. El bloque de 16 no cambia. Lo comprueban `test_verificar_comprueba_lo_que_necesita_la_galeria` y el test existente de las 16. `/plan --fix`, `/tasks --fix` |
| A27 | Ambigüedad | MEDIA | plan → Contratos (404 de `galeria.foto`), D4; T024 | No se dice qué responde `foto(...)` cuando hay fila pero falta el archivo (por ejemplo, si se pierde el volumen). | Con fila y sin archivo, la foto responde 404, sin 500 ni log con la ruta. Lo comprueba un caso de `PaginaGaleriaTest`. `/plan --fix`, `/tasks --fix` |
| A28 | Ambigüedad | BAJA | plan → `ProcesadorFoto`, D9; T020 | Con orientación 6 u 8, el destino antes de girar mide 1200 × 1600, no 1600 × 1200. | `/plan --fix` y nota en T020. T010 comprueba que tras 3, 6 y 8 la salida es 4:3 horizontal |
| A29 | Redacción | BAJA | tasks → Constitution Check, P2 | La columna de verificación sigue con «T010–T018». | `/tasks --fix` |
| A30 | Redacción | BAJA | tasks → Constitution Check, P1; plan → Impacto | Fechas de aprobación distintas (2026-11-07 y 2026-11-08). | `/tasks --fix`: el plan se aprobó el 2026-11-07 y otra vez el 2026-11-08, tras `/analyze` |
| A31 | Cobertura (docs) | BAJA | plan → Descubribilidad; T031 | T031 no incluye la fila de `/galeria/fotos/{foto}/{ancho}` con `noindex` en `docs/seo.md`. | `/tasks --fix` |

## Decisiones pendientes del usuario
Ninguna.

## Aceptados
Los de la ronda 1 (A15, A17, A19 y A20) siguen vigentes: ver `history/analysis.r1.md`.
- **A15** → nota de T015: orden relativo de las secciones, no adyacencia.
- **A17** → nota de T016: vocabulario del registro de la 020.
- **A19** → nota de T021, T012: índice único de `posicion`; subir, bajar y quitar en el centro y en los extremos.
- **A20** → nota de T026: el menú del escritorio lo miden `capturar.mjs --medir` y la verificación manual.
