---
result: pass
round: 3
mode: delta
constitution_version: 1.3.0
date: 2026-11-12
spec_sha: f5b68d6136af
plan_sha: f1c72c3caa7f
tasks_sha: 0f9162cd4c26
---

# Análisis · 045 Términos de uso y aviso de privacidad

## Resumen
**Pass.** Ronda sobre el cambio de CA4 que decidió el usuario en la verificación manual de T025: en el registro,
el texto se abre en una ventana (`dialog`) sin salir de la página, en lugar de otra pestaña. Es un cambio
estructural acotado (componente, parciales, cuatro tareas nuevas y TM1 retirada); lo analizó un subagente
(perfil `standard`) sobre el diff y los artefactos. Sin CRÍTICOS ni ALTOS; los seis hallazgos (A17–A22) se
corrigieron en la consolidación de esta ronda. La ronda anterior está en
[history/analysis.r2.md](history/analysis.r2.md).
Conteo (nuevos): CRÍTICA 0 · ALTA 0 · MEDIA 3 · BAJA 3, todos resueltos

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 8 / 8 (CA4: T011, T016 → T025, T028, T029, T031) |
| Amenazas con control y test | 1 / 1 vigente (TM2); TM1 retirada, con el test de que no hay `target` |
| Principios de la constitución evaluados | 10 / 10 en las líneas cambiadas (P2, P4, P9, P10 en particular) |

## Seguimiento de rondas anteriores
Los hallazgos A1–A16 quedaron resueltos en las rondas 1 y 2; el cambio de CA4 no reabre ninguno (TM2 y las
listas `VISTAS` siguen igual; A5 y A7, sobre la casilla, los sustituye el test actualizado de T016).

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A17 | Cobertura / P2 | MEDIA | `plan.md` P2, D12, Trazabilidad CA4; T016, T025 | Abrir, cerrar (Escape, fuera) y devolver el foco es comportamiento de la app y solo tenía verificación manual | **Resuelto:** T016 añade `test_el_script_de_las_ventanas_abre_y_cierra` (estructura del script: `showModal` desde `data-dialogo` y solo si existe, cierre con `data-cerrar` y pulsando fuera, `.focus()` al cerrar), y la fila P2 dice que la ejecución en el navegador se comprueba además a mano (T025, T095) porque no hay tests de navegador |
| A18 | Inconsistencia | MEDIA | `design.md` → Accesibilidad | Seguía «(se abre en otra pestaña)» dentro del enlace | **Resuelto:** la línea describe las ventanas (`aria-haspopup`, `aria-labelledby`, foco a «Cerrar» y de vuelta al enlace, cierre por teclado, fondo inerte) |
| A19 | Inconsistencia | BAJA | `spec.md` → Notas para /plan | Seguía «Pestaña nueva con `target="_blank"`» | **Resuelto:** ventana con el texto de la página; sin JavaScript, la página en la misma pestaña |
| A20 | Cobertura / P9, P10 | MEDIA | `plan.md` P9, P10; T027 | `/registro`, indexable, lleva ahora el texto de las dos páginas en ventanas cerradas, y T027 no medía su Lighthouse | **Resuelto:** P9 y P10 lo recogen (tokens de `.dialogo-legal`; contenido propio de la ruta, no texto oculto) y T027 incluye Lighthouse SEO ≥ 90 de `/registro` |
| A21 | Ambigüedad | BAJA | `spec.md` → Criterios de diseño | «CA4, la ventana, se mide sola con K8» omitía sus tests y la verificación manual | **Resuelto:** «CA4 lo cubren sus tests y la verificación manual, y K8 mide la ventana» |
| A22 | Inconsistencia | BAJA | `tasks.md` → tabla de amenazas | TM1 retirada seguía con tarea de control | **Resuelto:** control «—» y test T016 (enlaces sin `target`) |

## Decisiones pendientes del usuario
- Ninguna. Spec, plan y tareas están en `draft` por el cambio de CA4 y necesitan su aprobación para seguir con
  `/implement`.

## Aceptados
Ninguno.
