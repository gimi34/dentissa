---
result: pass
round: 2
mode: delta
constitution_version: 1.3.0
date: 2026-10-20
spec_sha: 3672869d6dd0
plan_sha: f53008f6a372
tasks_sha: d42a02e68cd4
---

# Análisis · 038 Página de inicio pública

## Resumen
`pass`. Las 12 correcciones de la ronda 1 (A1–A11, A18) están en el plan y las tareas. La corrección
de A8 y A10 dejó un efecto menor (A20), que se corrigió en el plan durante esta ronda. Delta sobre el
estado de `3808f98` (unos 32 KB, sin cambios estructurales), revisado sin subagente releyendo del
disco las secciones afectadas.
Conteo: CRÍTICA 0 · ALTA 0 · MEDIA 0 · BAJA 1 (resuelta en la ronda)

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 10 / 10 (CA8 «todos los enlaces funcionan» con test en T013) |
| Amenazas con control y test | 6 / 6 |
| Principios de la constitución evaluados | 10 / 10 (P10 con excepción aceptada por el usuario) |

## Seguimiento de rondas anteriores
| ID | Severidad | Estado | Evidencia |
|---|---|---|---|
| A1 | ALTA | resuelto | plan D13 «Prerrequisito de la 031» (decisión del usuario: `/fix` en `main`) y Riesgos; T004 resuelve los conflictos a favor de la 038 |
| A2 | MEDIA | resuelto | T004 y nota A2 de T016 (coherentes con el `/fix`); las comprobaciones de `verificar.sh` siguen en `CabecerasSeguridadTest`, donde ya estaban (la rama de la 031 no las tiene en `ConfiguracionProduccionTest`) |
| A3 | ALTA | resuelto | plan «Navegación de la 038» con su test; caso de enlaces en T013; nota A3 de T025 |
| A4 | ALTA | resuelto | plan, Constitution Check P10 `❌ aceptado` (usuario, 2026-10-20), RNF y Riesgos; T042 mide JS propio, de terceros y total, y si el `iframe` entra en la primera carga |
| A5 | MEDIA | resuelto | T014 declara `ReglasCss.php`, controles positivos y nota A5 (`var()` en `font-family`, duraciones, `rem`/`em`, `cubic-bezier(`) |
| A6 | MEDIA | resuelto | plan «Clases de los componentes», que T014 usa |
| A7 | MEDIA | resuelto | T021 se cierra con un caso directo de `porCategoria()`, T022 con la redirección por rol y `route:list`, y T023 con un caso del layout sobre una vista de prueba; sin vistas mínimas sin declarar |
| A8 | MEDIA | resuelto | T016 exige «comprobado rompiendo `trustProxies`» |
| A9 | MEDIA | resuelto | plan D10 (030 CA2 no cambia, por eso no va en `extends`); T041 anota la 030 y T043 la 031 |
| A10 | MEDIA | resuelto con efecto nuevo (→ A20) | T043 actualiza `docs/security.md`; el plan no lo listaba |
| A11 | ALTA | resuelto | T013 prueba títulos en orden y nombres accesibles |
| A18 | MEDIA | resuelto | T041 anota la aprobación del usuario (2026-10-20) |
| A12–A17, A19 | BAJA | aceptados | notas de tarea y roadmap (ver Aceptados) |

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A20 | Inconsistencia | BAJA | plan «Cambios por módulo»: `ProxyHttpsTest` y Documentación | Tras A1, el plan seguía diciendo que esas pruebas «son los 2 fallos que quedan en `main`», y la fila de Documentación no recogía `security.md` ni las specs 030 y 031 que tocan T041 y T043 | Corregido en esta ronda (`plan.md`, filas de `ProxyHttpsTest` y Documentación) |

## Decisiones pendientes del usuario
Ninguna.

## Aceptados
Aceptados por el usuario el 2026-10-20 como notas de tarea (sin otra vuelta de plan):
- **A12** → nota de T010, T012: los casos de vacío desactivan todos los servicios en la transacción; el orden se comprueba con categorías propias
- **A13** → nota de T012, T021: agrupación sin distinguir mayúsculas ni acentos; «Otros servicios» se funde con las vacías
- **A14** → nota de T012, T026: el índice de `/servicios` muestra el número de servicios por categoría
- **A15** → nota de T010, T025: «Aviso de la puerta» como `h2`; «Ver la ubicación» en la placa y «Cómo llegar» solo en la dirección
- **A16** → roadmap: el plan de la 039 modifica los casos «ningún script» y «ningún recurso externo» de `PaginasPublicasTest` en lugar de duplicarlos
- **A17** → nota de T040: corrección del sitemap y barra de la raíz en `docs/seo.md`; enmienda de P10 → roadmap
- **A19** → nota de T010, T013: canónica de producción fijada en el test, búsqueda de referencias solo en `resources/` y `public/`, motivo del fallo de T010
