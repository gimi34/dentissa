---
result: fail
round: 1
mode: full
constitution_version: 1.3.0
date: 2026-11-18
spec_sha: abbe59d4810d
plan_sha: 0bcb938f86fe
tasks_sha: 9a1e7d340b68
---

# Análisis · 051 Ciudad elegida de una lista cerrada

## Resumen
No pasa: el plan marca P3 y P7 como cumplidos, pero los endpoints que modifica no los cumplen hoy y
no hay excepción registrada. Además, el foco del desplegable nuevo no llega a 3:1 en el registro y
el perfil, y un test existente fallaría con el ajuste previsto. Todos se corrigen con `--fix`.
Conteo: CRÍTICA 2 · ALTA 2 · MEDIA 4 · BAJA 8

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 12 / 12 |
| Amenazas con control y test | 6 / 6 |
| Principios de la constitución evaluados | 10 / 10 |

## Seguimiento de rondas anteriores
Primera ronda: no hay hallazgos anteriores.

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A1 | Constitución | CRÍTICA | `plan.md` Constitution Check P3; `tasks.md` Constitution Check P3 | Los endpoints modificados no cumplen P3: `PacientesController::store/update` no limitan por alcance (RS1.a) y `PUT /cliente/perfil` no lleva `role:cliente` (`routes/web.php:213`, RS2.a). El plan marca ✅ y afirma que hay middleware `role:` | `/plan 051 --fix A1`: P3 como `❌ aceptado`, con los endpoints y el destino de cada corrección; test de caracterización de la autorización de hoy |
| A2 | Constitución | CRÍTICA | `plan.md` Constitution Check P7; `spec.md` Fuera de alcance | Los cinco formularios cambian datos de pacientes y los registros crean cuentas sin auditarlo (OB1.e, OB1.f pendientes); el plan marca ✅ | `/plan 051 --fix A2`: P7 como `❌ aceptado`, con OB1.e y OB1.f y su destino |
| A3 | Constitución | ALTA | `design.md` Accesibilidad; `plan.md` P9 | El foco del campo en el registro (`auth.css`, borde `--p300`, `outline: none`) y en el perfil (`citas.css`, `--p300`) queda por debajo de 3:1, y el acta encargaba a `/plan` comprobarlo | `/plan 051 --fix A3` y `/tasks 051 --fix A3`: el desplegable del registro y del perfil tiene foco y borde ≥ 3:1 (test: reglas del bloque 051 y contraste con `ReglasCss`) |
| A4 | Inconsistencia | ALTA | `tasks.md` T027; `plan.md` fila de `MovilPacientesExpedientesTest` | En `setUp`, p1 tiene «Ciudad de prueba», fuera de la lista: su edición mostrará el aviso y romperá «sin errores, sin aria-describedby» y `assertSame("error-ciudad", aria-describedby)` | `/tasks 051 --fix A4`: `MovilPacientesExpedientesTest` pasa completo con p1 en una ciudad de la lista |
| A5 | Inconsistencia | MEDIA | `plan.md` parcial `opciones-ciudad`; CA10 | Si se elige «Sin indicar» y falla otro campo, `old('ciudad')` es `null`, ninguna opción queda elegida y el navegador marca la ciudad antigua | `/plan 051 --fix A5` y `/tasks 051 --fix A5`: tras un error se respeta «Sin indicar» (test: «Sin indicar» más un error en otro campo) |
| A6 | Seguridad | MEDIA | `plan.md` `CiudadDeLaLista` | Sin la regla `string`, `ciudad[]=x` llega como array y puede acabar en un error 500 | `/plan 051 --fix A6` y `/tasks 051 --fix A6`: un array se rechaza con el mensaje de la lista (test: `ciudad[]`) |
| A7 | Ambigüedad | MEDIA | `plan.md` fila de los registros; CA5; K2 | En el registro con correo, el error de ciudad queda oculto en el paso 2 y no se enlaza con `aria-describedby` | `/plan 051 --fix A7` y `/tasks 051 --fix A7`: decisión del usuario más abajo |
| A8 | Conflicto | MEDIA | `plan.md` D8; `014/plan.md` | La 014 rehará `index` y la autorización de la edición, y no recoge los cambios de la 051 | `/plan 051 --fix A8`: dejar en D8 lo que la 014 debe aplicar |
| A9 | Inconsistencia | BAJA | `plan.md` P8 | El `request_id` no es nulo en la migración: `Auditoria` genera uno fuera de HTTP | `/plan 051 --fix A9` |
| A10 | Ambigüedad | BAJA | `plan.md` D4 e `index` | `DISTINCT` puede devolver «morelia» como representante de «Morelia» y hacerla desaparecer del filtro | `/plan 051 --fix A10` |
| A11 | Ambigüedad | BAJA | `plan.md` `CiudadDeLaLista` | La entrada llega recortada: una ciudad antigua con espacios sobrantes no volvería idéntica | `/plan 051 --fix A11` |
| A12 | Ambigüedad | BAJA | CA7; `plan.md` `index` | No se fija el orden de las opciones del filtro | `/plan 051 --fix A12` |
| A13 | Inconsistencia | BAJA | `plan.md` Rollout; `tasks.md` T097; `deploy/desplegar.sh` | `desplegar.sh` recomienda `restaurar.sh` si falla con migraciones aplicadas; el plan dice `volver.sh` sin explicarlo | `/tasks 051 --fix A13` |
| A14 | Inconsistencia | BAJA | `spec.md` K1; `plan.md`; `tasks.md` T011 | «Cuatro formularios» y «cinco puntos de entrada» nombran lo mismo de dos formas | `/plan 051 --fix A14` |
| A15 | Inconsistencia | BAJA | `tasks.md` T010; `plan.md` Estrategia | `CiudadDeLaListaTest` no aparece en el plan | `/plan 051 --fix A15` |
| A16 | Ambigüedad | BAJA | `design.md`; K3 | La opción antigua puede ser más larga que el nombre más largo de la lista | `/tasks 051 --fix A16` |

## Decisiones pendientes del usuario
Resueltas el 2026-11-18:
- **A1:** excepción a P3, como en la 050; la deuda sigue en RS1.a (spec 014) y RS2.a (spec 015).
- **A2:** excepción a P7, como en la 050; OB1.e va a la spec 022 y OB1.f a la 021.
- **A7:** con un error en el paso 2, el registro abre ese paso y enlaza el error con el campo.

## Aceptados
Ninguno: todos se corrigen con `/plan 051 --fix` y `/tasks 051 --fix`, y después `/analyze 051` (delta).
