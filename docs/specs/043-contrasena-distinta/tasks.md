---
spec: 043-contrasena-distinta
plan: plan.md
status: draft
---

# Tareas · 043 La contraseña nueva tiene que ser distinta de la actual

Formato:
`- [ ] T### [P] <verbo + qué> — <archivos> — hecho cuando: <criterio> — cubre: CA# — depende: T###`
- T001–T089: trabajo. T090–T099: reservadas.
- `[P]` = paralelizable con las demás `[P]` de su grupo una vez cumplidas sus dependencias (no
  comparte archivos con otra tarea abierta).
- `[-]` = obsoleta (`— obsoleta: <motivo> (AAAA-MM-DD)`): no cuenta como abierta ni como hecha.
- Bajo una tarea: `  - nota: …`, `  - bloqueo: AAAA-MM-DD — <qué falta> — <skill a ejecutar>`.
- **Proceso ligero (spec, Decisiones):** sin `/design-spec`, con una sola ronda de `/review`; sale en v0.14.1.

## Constitution Check
Contra `plan.md` (constitución 1.3.0). Lo que el desglose podría romper:

| Principio | Resultado | Justificación / ajuste | Cómo se verifica |
|---|---|---|---|
| P1 Spec aprobada antes de código | ✅ | Spec y plan aprobados el 2026-11-03; ninguna tarea de código empieza antes de `/analyze`. | manual: `aidd.py status` |
| P2 Test que falla antes y pasa después | ✅ | T010 y T011, de test, van antes que T020–T022, y cada tarea de implementación depende de su test. Los casos que pasan desde el principio se comprueban rompiendo el control, con su nota. | test: T010, T011 |
| P3 Autorización en servidor | ✅ | T020 compara con la cuenta de `cuentaDelEnlace()`; T010 lo prueba (TM3). Ninguna tarea toca rutas ni roles. | test: T010 (`test_se_compara_con_la_contrasena_de_la_cuenta_del_enlace`) |
| P4 Entrada validada y SQL con bindings | ✅ | La comparación va dentro de los validadores que ya existen (T020) o después de ellos (T021), sin SQL nuevo. | test: T010, T011 (`…_los_errores_de_siempre_van_antes_…`) · lint: semgrep en T023 |
| P5 Nada sensible en el repo | ✅ | Contraseñas de ejemplo ficticias en T010 y T011. | lint: gitleaks en T023 |
| P6 Sin vulnerabilidades críticas/altas | ✅ | Ninguna tarea toca dependencias. | lint: `composer audit --locked`, `npm audit --audit-level=high` en T023 |
| P7 Auditoría de datos sensibles | ✅ | T010 prueba que un rechazo no registra `contrasena_restablecida`. | test: T010 (`…_no_cierra_sesiones_ni_avisa_ni_audita`) |
| P8 Correlación y logs limpios | ✅ | Ningún `Log::` nuevo. T010 y T011 prueban que la contraseña rechazada no queda en la sesión. | test: T010, T011 (`…_la_contrasena_rechazada_no_queda_en_la_sesion`) · manual: diff sin `Log::` en T023 |
| P9 La interfaz usa solo el sistema de diseño | ✅ | T022 solo añade atributos a la vista del perfil, sin estilos ni clases. | test: T011 (`…_perfil_el_error_queda_asociado_al_campo_y_con_el_foco`) · manual: diff de T022 |
| P10 Rutas indexables descubribles | ➖ | Sin rutas nuevas ni cambiadas (plan). | — |

## Preparación
No hace falta: sin dependencias, configuración, interruptor ni migración (plan, Rollout D5).

## Tests (antes de implementar)
- [ ] T010 Escribir los tests de la recuperación en `ContrasenaDistintaTest`: `test_restablecer_con_la_actual_se_rechaza_junto_al_campo` (texto exacto junto a `#clave`, con su `autofocus`; la contraseña no cambia), `…_restablecer_con_la_actual_no_cierra_sesiones_ni_avisa_ni_audita` (`version_sesion` igual, sin `ContrasenaCambiada` con `Mail::fake`, sin `contrasena_restablecida`), `…_tras_el_rechazo_el_enlace_sigue_sirviendo` (el mismo enlace fija después otra), `…_una_diferencia_minima_se_acepta_en_la_recuperacion` (solo mayúsculas; un espacio final), `…_una_cuenta_sin_contrasena_fija_la_suya_por_el_enlace`, `…_los_errores_de_siempre_van_antes_en_la_recuperacion` (una actual heredada de menos de 8 caracteres repetida da el error de longitud; una confirmación distinta, el de confirmación), `…_los_rechazos_cuentan_para_el_limite_del_enlace` (tras 5 rechazos, el 6.º envío, aunque sea válido, pide esperar y no cambia nada), `…_se_compara_con_la_contrasena_de_la_cuenta_del_enlace` (la contraseña de otra cuenta se acepta) y `…_la_contrasena_rechazada_no_queda_en_la_sesion` (recuperación) — tests/Feature/ContrasenaDistintaTest.php — hecho cuando: los tres primeros fallan porque hoy se acepta la misma contraseña y los demás pasan — cubre: CA1, CA3, CA4, CA5, CA7
- [ ] T011 Escribir los tests del perfil en el mismo archivo: `test_perfil_con_la_actual_se_rechaza_junto_al_campo`, `…_una_diferencia_minima_se_acepta_en_el_perfil`, `…_una_cuenta_sin_contrasena_crea_la_suya_en_el_perfil`, `…_los_errores_de_siempre_van_antes_en_el_perfil`, `…_perfil_sin_la_actual_correcta_no_revela_si_es_igual`, `…_perfil_con_el_limite_agotado_no_revela_si_es_igual` (5 fallos y después la actual correcta con la nueva igual → «Demasiados intentos»), `…_perfil_el_error_queda_asociado_al_campo_y_con_el_foco` (`aria-invalid`, `aria-describedby` hacia el mensaje y `autofocus` en el primer campo con error) y el caso del perfil de `…_la_contrasena_rechazada_no_queda_en_la_sesion` — tests/Feature/ContrasenaDistintaTest.php — hecho cuando: el primero y el de accesibilidad fallan por la razón esperada y los demás pasan — cubre: CA2, CA3, CA4, CA5, CA6 — depende: T010

## Implementación
- [ ] T020 Añadir a `restablecer` un `$validador->after()` que, sin otros errores y si `$usuario->password_hash` existe, añade a `password` «Elige una contraseña distinta de la actual» cuando `Hash::check` acierta (plan D1, D4) — app/Http/Controllers/RecuperarContrasenaController.php — hecho cuando: pasan los tests de T010 y se comprueba que cada control falla su test al romperlo un momento: quitar «sin otros errores» (CA5), comparar con `Usuario::find($cuenta)` (TM3), quitar `throttle:fijar-contrasena` de la ruta (CA7, TM2), quitar la condición de `password_hash` (CA4) y pasar a minúsculas (CA3) — cubre: CA1, CA3, CA4, CA5, CA7 — depende: T010
- [ ] T021 Añadir a `updatePassword`, después de `errorDeContrasenaActual()` y antes del `update`, el rechazo con `back()->withErrors(['password' => 'Elige una contraseña distinta de la actual'])` cuando la cuenta tiene contraseña y `Hash::check` acierta (plan D2, D4) — app/Http/Controllers/PerfilController.php — hecho cuando: pasan los tests de T011 salvo el de accesibilidad y se comprueba rompiendo: comparar antes de la contraseña actual (CA6, TM1) y quitar la condición de `password_hash` (CA4) — cubre: CA2, CA3, CA4, CA5, CA6 — depende: T011
- [ ] T022 Asociar los errores a su campo en «Cambiar contraseña» del perfil, en `password_actual` y `password`, con el patrón de `contrasena_correo` (plan D6): `id` en el campo y el mensaje, `aria-invalid="true"` y `aria-describedby` con error, y `autofocus` en `password_actual` si tiene error y si no en `password`; sin estilos ni clases nuevos — resources/views/cliente/perfil.blade.php — hecho cuando: pasa `…_perfil_el_error_queda_asociado_al_campo_y_con_el_foco` y siguen en verde `PerfilCambioCorreoTest` y el resto de T011 — cubre: CA2 — depende: T021

## Integración y documentación
- [ ] T023 Pasar la verificación final: suite completa, Pint sobre los 4 PHP tocados, gitleaks sobre el diff, semgrep `p/php`, `composer audit --locked`, `npm audit --audit-level=high` y revisión de que el diff no tiene `Log::` nuevos — sin archivos — hecho cuando: todo en verde y la única media de `composer audit` es la de la línea base (`symfony/mime`) — depende: T022
- [ ] T090 Actualizar `docs/architecture.md` si cambió la estructura — sin cambios (plan, Impacto en arquitectura); se anota en la tarea
- [ ] T091 Actualizar `docs/deployment.md` y `docs/observability.md` si cambiaron variables, entornos, pasos de deploy, logs, eventos de auditoría o métricas — sin cambios (plan, Observabilidad y Rollout); se anota en la tarea
- [ ] T092 Marcar spec como `implemented`

## Despliegue (lo ejecuta `/release`)
- [ ] T095 Desplegar a staging y verificar criterios de aceptación. No hay staging (objetivo 5): la comprobación se hace en producción, dentro de T097, con la cuenta del usuario y sin escribir ninguna contraseña en ningún documento:
  - CA1: pedir un enlace y escribir la contraseña actual → «Elige una contraseña distinta de la actual» junto al campo; después fijar otra y entrar con ella — resultado: —
- [ ] T096 Aprobación humana para producción
- [ ] T097 Desplegar a producción y vigilar métricas del plan (Rollout): `deploy/desplegar.sh v0.14.1`, `verificar.sh` y la comprobación de T095; vigilar 5xx en `/contrasena/nueva` y `/cliente/perfil/password`. Rollback: `deploy/desplegar.sh v0.14.0`
- [ ] T098 Marcar spec como `released` y anotar el cambio en el Historial de las specs 011 y 041

## Cobertura
| Criterio | Tarea(s) de test | Tarea(s) de implementación |
|---|---|---|
| CA1 | T010 | T020 |
| CA2 | T011 | T021, T022 |
| CA3 | T010, T011 | T020, T021 |
| CA4 | T010, T011 | T020, T021 |
| CA5 | T010, T011 | T020, T021 |
| CA6 | T011 | T021 |
| CA7 | T010 | T020 |

| Amenaza (TM#) | Tarea(s) de control | Tarea(s) de test |
|---|---|---|
| TM1 | T021 | T011 |
| TM2 | T020 | T010 |
| TM3 | T020 | T010 |
| TM4 | T020, T021 | T010, T011 |

| Cambio del plan (módulo) | Tarea(s) |
|---|---|
| app/Http/Controllers/RecuperarContrasenaController.php | T020 |
| app/Http/Controllers/PerfilController.php | T021 |
| resources/views/cliente/perfil.blade.php | T022 |
| tests/Feature/ContrasenaDistintaTest.php | T010, T011 |

| Requisito no funcional | Tarea(s) de test o verificación manual (T095) |
|---|---|
| Error asociado al campo y foco en el primero con error | T011 (perfil), T010 (recuperación), T022 |
