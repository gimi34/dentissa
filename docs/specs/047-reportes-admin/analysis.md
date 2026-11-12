---
result: pass
round: 2
mode: delta
constitution_version: 1.3.0
date: 2026-11-11
spec_sha: c795ee531903
plan_sha: 67815a8c4389
tasks_sha: 925021ad4b75
---

# Análisis · 047 Reportes de la clínica para la administración

## Resumen
`pass`. Delta sobre las correcciones de la ronda 1, sin subagente: el diff supera los 30 KB, pero casi
todo son líneas largas de tarea editadas en su lugar, sin cambio estructural (ningún módulo, contrato
ni criterio nuevo; las mismas 24 tareas). Las secciones cambiadas se releyeron desde los archivos. Los
tres altos y los medios quedan resueltos; los bajos, resueltos o aceptados.
Conteo (nuevos): CRÍTICA 0 · ALTA 0 · MEDIA 0 · BAJA 0

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 10 / 10 |
| Amenazas con control y test | 7 / 7 (TM3: peticiones repetidas como residuo aceptado) |
| Principios de la constitución evaluados | 10 / 10 |

## Seguimiento de rondas anteriores
| ID | Severidad | Estado | Evidencia |
|---|---|---|---|
| A1 | ALTA | resuelto | T010 y P2 del plan: cada caracterización se rompe en su pieza (`role:admin`, `PreventBackHistory`, `IdentificadorPeticion`, condición de rol del layout) |
| A2 | ALTA | resuelto | T010: el test del Gate con el caso permitido; T013: CA10 con control positivo |
| A3 | ALTA | resuelto | Decisión del usuario: T014 comprueba el contraste con `contraste()`; P9 del plan |
| A4 | MEDIA | resuelto | Plan D12 y Estrategia de pruebas; T011–T013 con fecha en 2031 y periodo vacío comprobado |
| A5 | MEDIA | resuelto | Decisión del usuario: CA6 con un solo mensaje; plan y T013/T024 lo comprueban una vez |
| A6 | MEDIA | resuelto | Plan, Reglas del periodo; T021; proveedor de T012 por campo, 302 y nunca 500 |
| A7 | MEDIA | resuelto | Trazabilidad CA3–CA5 y T013 con los tests sobre el HTML; el porcentaje lo escribe la vista |
| A8 | MEDIA | resuelto | `design.md`: las tres tablas son `tabla-tarjetas`; plan D13; T027 exige código 0 |
| A9 | MEDIA | resuelto | Decisión del usuario: solo texto desde `config('clinica.nombre')` (plan D11, T024) |
| A10 | MEDIA | resuelto | Decisión del usuario: K1 a 320×568 y resumen antes del filtro bajo 900 px; T025 y T027 |
| A11 | BAJA | resuelto | Plan D3 y T023 (detección de errores en la sesión) |
| A12 | BAJA | resuelto | TM7 del plan: grupo `web` |
| A13 | BAJA | resuelto | Plan, Cambios por módulo; T011 con helper propio |
| A14 | BAJA | aceptado | nota de T091 |
| A15 | BAJA | resuelto | T091 y plan, Impacto en arquitectura |
| A16 | BAJA | resuelto | Spec CA4, CA5 y CA7; T011 y T012 |
| A17 | BAJA | aceptado | nota de T028; P4 del plan deja escrita la lectura |
| A18 | BAJA | resuelto | Plan, Riesgos |
| A19 | BAJA | aceptado | nota de T098 |
| A20 | BAJA | resuelto | `design.md` y plan: `--touch-min` a todos los anchos; T014 |
| A21 | BAJA | resuelto | TM3 del plan: residuo aceptado |
| A22 | BAJA | resuelto | T010 (403 con periodo en la dirección) y T013 (formulario `GET`) |
| A23 | BAJA | aceptado | → plan |

## Hallazgos nuevos
Ninguno. Comprobado en el delta:
- Ninguna tarea pasa de 3 archivos (T024 gana `config/clinica.php`: 2) y las dependencias no cambian.
- El orden distinto en el móvil no cambia el orden del HTML ni del foco (el bloque movido no tiene
  controles), así que no afecta al orden de tabulación (WCAG 2.4.3).
- `config/clinica.php` no es ruta protegida y no lo toca el plan de la 014.

## Decisiones pendientes del usuario
Ninguna.

## Aceptados
Aceptados por el usuario el 2026-11-11:
- **A14** → nota de T091: contar los controladores al editar `AGENTS.md`, no copiar una cifra.
- **A17** → nota de T028: revisar la salida de `semgrep` sobre `ReporteCitas`; la expresión `COUNT(*)` es constante y sin entrada del usuario; ninguna supresión sin motivo.
- **A19** → nota de T098: añadir una línea al Historial de la 012 (el placeholder de «Reportes» ya no existe), sin añadirla a `extends`.
- **A23** → plan: los riesgos de otros documentos no se citan por identificador porque la spec no los atiende; el plan los describe y remite a su documento.
