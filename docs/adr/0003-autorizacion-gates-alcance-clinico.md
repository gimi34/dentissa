---
id: 0003
status: accepted
date: 2026-09-28
---

# ADR 0003 · Autorización por alcance clínico con Gates sobre una clase de alcance

## Contexto
La spec 014 exige que cada acción sobre pacientes, expedientes y alta de citas compruebe el rol y
el alcance del usuario sobre el recurso (principio P3). Hoy la autorización es un middleware de
rol más condicionales sueltos en cada controlador, y el acceso a datos es Query Builder: no hay
modelos Eloquent de paciente ni de expediente a los que asociar Policies.

## Decisión
- Una clase `App\Autorizacion\AlcanceClinico` concentra la regla: un dentista alcanza a los
  pacientes asignados a él o con alguna cita no cancelada con él; un asistente, a los de sus
  dentistas asignados; un admin, a todos.
- Los permisos se expresan como Gates registrados en `AppServiceProvider` (`ver-paciente`,
  `editar-paciente`, `ver-expediente`, `editar-expediente`, `crear-expediente`,
  `agendar-para-paciente`) que delegan en esa clase.
- Fuera de alcance → `Response::denyAsNotFound()` (404); visible pero no modificable → 403.
- Los listados usan el mismo criterio mediante un filtro de consulta de la clase.

## Alternativas consideradas
- Crear modelos mínimos de paciente y expediente para colgar Policies — adelanta parte de la
  spec 013 y amplía el cambio; se descarta por ahora (decisión del usuario, 2026-09-28).
- Mantener comprobaciones en cada controlador — repite la regla y es lo que produjo el riesgo RS1.

## Consecuencias
- Positivas: una sola regla de alcance, probada de forma aislada; controladores más simples.
- Negativas: los Gates reciben ids en lugar de modelos; cuando la spec 013 introduzca modelos,
  convendrá migrarlos a Policies (conservando los tests de la 014).
