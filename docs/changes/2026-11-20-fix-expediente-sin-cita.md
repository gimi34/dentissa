---
lane: fix
status: released                 # open → done → released · escalated (→ escalated_to)
released: v0.24.0 (2026-11-21)
base: a2cd958                # commit de partida; lo registra la skill al empezar (lo usa lane-check)
relates: [007]               # specs cuyo comportamiento toca ([] si ninguna)
covers: []                   # fix: criterios de esas specs que el arreglo vuelve a cumplir
escalated_to: null           # NNN de la spec si el cambio se escaló al flujo completo
---

# Fix: el alta de expediente sin `cita_id` da un 500

## Problema
Un alta de expediente (`POST /{admin|dentista}/expedientes`) enviada sin el campo `cita_id` responde con un error 500
(`Undefined array key "cita_id"`) en lugar de guardar el expediente sin cita. El formulario siempre envía el campo
(vacío si no hay cita), así que solo pasa con una petición hecha a mano. Visto en el `/review` r2 de la 050 (R14,
`app/Http/Controllers/ExpedientesController.php:134`), preexistente; anotado en el roadmap como 050/R14.
El mismo defecto está en «Guardar notas» de una cita (`PATCH /admin/citas/{id}/notas`, `CitasController::updateNotas`):
sin el campo `notas_internas`, 500. Lo vio la búsqueda del mismo patrón y entra en esta ficha (decisión del usuario).

## Reproducción
`tests/Feature/AltaExpedienteSinCitaTest.php`, antes del arreglo:
- `test_el_alta_sin_el_campo_cita_id_guarda_el_expediente_sin_cita` → `ErrorException: Undefined array key "cita_id"`
  en `app/Http/Controllers/ExpedientesController.php:134`.
- `test_guardar_notas_sin_el_campo_notas_internas_las_deja_vacias` → `ErrorException: Undefined array key
  "notas_internas"` en `app/Http/Controllers/CitasController.php:499`.
- `test_el_alta_con_cita_id_vacio_sigue_guardando_el_expediente_sin_cita` pasa antes y después: fija lo que ya hacía
  el formulario.

## Causa raíz
`$request->validate()` devuelve solo los campos que llegan en la petición. Con una regla `nullable` el campo puede
faltar, y entonces su clave no existe en `$data`; leerla con `$data['cita_id'] ?: null` o `$data['notas_internas']`
da el aviso «Undefined array key», que Laravel convierte en excepción (500). Desde el formulario nunca pasa porque
el campo llega siempre: vacío, y el middleware `ConvertEmptyStringsToNull` lo deja en `null` con la clave presente.
Los demás campos opcionales de esos mismos métodos ya se leían con `?? null`.

## Cambio
- `app/Http/Controllers/ExpedientesController.php` (`store`): `$data['cita_id'] ?: null` → `$data['cita_id'] ?? null`.
- `app/Http/Controllers/CitasController.php` (`updateNotas`): `$data['notas_internas']` → `$data['notas_internas'] ?? null`.
- `tests/Feature/AltaExpedienteSinCitaTest.php` (nuevo): los tres tests de «Reproducción».
- Commit `fix(fix-expediente-sin-cita): campos opcionales ausentes en el alta de expediente y las notas de la cita`,
  con `Ficha:` en el cuerpo.

## Escalado
`lane-check` al empezar (archivos previstos) y sobre el diff real desde `a2cd958`: «Escalar: no. El cambio cabe en el
carril» (4 archivos, 2 de código). Sin datos, contratos, permisos ni dependencias: las reglas de validación y la
autorización no cambian.

## Verificación
- Test nuevo: los dos casos de «Reproducción» fallan sin el arreglo por «Undefined array key» y pasan con él; el del
  campo vacío pasa en los dos casos. Los tests de citas afectados (`AltaCitasFlujosTest`, `EstadosCitaTransicionesTest`)
  en verde.
- Suite completa: 1502 tests en verde (19463 aserciones), código de salida 0; línea base sobre `a2cd958`: 1499 en verde
  en la CI del PR #97. Pint sin cambios en los tres archivos PHP tocados.
- Review ligera (subagente, solo el diff, la ficha y el test): **aprobada**, sin bloqueantes. Repitió la reproducción
  revirtiendo solo el código (2 fallan, 1 pasa; con el arreglo, 3 pasan), confirmó la causa raíz y no encontró el mismo
  defecto en otros controladores (los demás campos opcionales se leen con `??`, `empty()`, `isset` o tras comprobar la
  clave). Menores: M1, un `PATCH` de notas sin el campo las vacía (ya decidido en «Decisiones»: igual que el formulario
  con el campo vacío; aceptado); M2, el test sí prueba el vaciado porque el escenario siembra una nota (sin cambio).

## Decisiones
| Fecha | Tipo | Pregunta / conflicto | Decisión | Fuente |
|---|---|---|---|---|
| 2026-11-20 | implícita | Ninguna spec dice qué hacer si falta `cita_id` | Un 500 es un defecto evidente; la regla `nullable` ya declara el campo opcional, así que se guarda sin cita | el síntoma; `ExpedientesController::store` |
| 2026-11-20 | implícita | Sin `notas_internas`, ¿se dejan las notas como estaban o se vacían? | Se vacían, igual que con el campo vacío del formulario: la regla `nullable` ya lo admite | `CitasController::updateNotas` |
| 2026-11-20 | brecha | El mismo defecto en las notas internas de la cita | Entra en esta ficha | usuario |
