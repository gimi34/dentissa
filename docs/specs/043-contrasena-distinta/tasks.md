---
spec: 043-contrasena-distinta
plan: plan.md
impl_base: 9dba9ac
status: approved
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
- **Corrección tras `/analyze` r1 (2026-11-04):** `/tasks --fix` editó en su lugar T010, T011, T020–T023 y T091 (A1–A5, A8–A10) y añadió T024 (`role:cliente`, A1). No se renumeró ninguna.
- **Línea base (2026-11-04, `impl_base` 9dba9ac):** suite completa 702 en verde (12 009 aserciones), sin fallos previos.
- **Datos de prueba:** ninguna contraseña actual lleva espacios al principio ni al final (`TrimStrings` recorta `password_actual`, `/analyze` A8). Para CA3, la actual va en minúsculas y la nueva solo cambia una letra a mayúscula, o añade un espacio final.

## Constitution Check
Contra `plan.md` (constitución 1.3.0). Lo que el desglose podría romper:

| Principio | Resultado | Justificación / ajuste | Cómo se verifica |
|---|---|---|---|
| P1 Spec aprobada antes de código | ✅ | Spec, plan y tareas se aprueban de nuevo tras `/analyze` r1; ninguna tarea de código empieza antes de que `tasks.md` esté `approved` y `/analyze` en `pass`. | manual: `aidd.py status` |
| P2 Test que falla antes y pasa después | ✅ | T010 y T011, de test, van antes que T020–T022, y cada tarea de implementación depende de su test. Los casos que pasan desde el principio se comprueban rompiendo el control, con su nota. | test: T010, T011 |
| P3 Autorización en servidor | ✅ | T024 añade `role:cliente` a `PUT /cliente/perfil/password` y T011 prueba el permitido (cliente) y el denegado (admin, dentista y asistente → 403). T020 solo compara después de validar el enlace; T010 lo prueba (TM3). | test: T011 (`test_el_personal_no_puede_cambiar_la_contrasena_por_el_perfil`), T010 (`…_con_un_enlace_de_otra_cuenta_nunca_se_compara`) |
| P4 Entrada validada y SQL con bindings | ✅ | La comparación va dentro de los validadores que ya existen (T020) o después de ellos (T021), sin SQL nuevo. | test: T010, T011 (`…_los_errores_de_siempre_van_antes_…`) · lint: semgrep en T023 |
| P5 Nada sensible en el repo | ✅ | Contraseñas de ejemplo ficticias en T010 y T011. | lint: gitleaks en T023 |
| P6 Sin vulnerabilidades críticas/altas | ✅ | Ninguna tarea toca dependencias. | lint: `composer audit --locked`, `npm audit --audit-level=high` en T023 |
| P7 Auditoría de datos sensibles | ✅ | T010 prueba que un rechazo no registra `contrasena_restablecida`. | test: T010 (`…_no_cierra_sesiones_ni_avisa_ni_audita`) |
| P8 Correlación y logs limpios | ✅ | Ningún `Log::` nuevo. T010 y T011 prueban que la contraseña rechazada no queda en la sesión. | test: T010, T011 (`…_la_contrasena_rechazada_no_queda_en_la_sesion`) · manual: diff sin `Log::` en T023 |
| P9 La interfaz usa solo el sistema de diseño | ✅ | T022 solo añade atributos a la vista del perfil (`for`, `id`, `aria-*`, `autofocus`), sin estilos ni clases; las etiquetas quedan asociadas (WCAG 1.3.1, 4.1.2). | test: T011 (`…_perfil_el_error_queda_asociado_al_campo_y_con_el_foco`) · manual: diff de T022 |
| P10 Rutas indexables descubribles | ➖ | Sin rutas nuevas ni cambiadas (plan). | — |

## Preparación
No hace falta: sin dependencias, configuración, interruptor ni migración (plan, Rollout D5).

## Tests (antes de implementar)
- [x] T010 Escribir los tests de la recuperación en `ContrasenaDistintaTest`: `test_restablecer_con_la_actual_se_rechaza_junto_al_campo` (texto exacto junto a `#clave`, con su `autofocus`; la contraseña no cambia), `…_restablecer_con_la_actual_no_cierra_sesiones_ni_avisa_ni_audita` (`version_sesion` igual, sin `ContrasenaCambiada` con `Mail::fake`, sin `contrasena_restablecida`), `…_tras_el_rechazo_el_enlace_sigue_sirviendo` (el mismo enlace fija después otra), `…_una_diferencia_minima_se_acepta_en_la_recuperacion` (solo mayúsculas; un espacio final), `…_una_cuenta_sin_contrasena_fija_la_suya_por_el_enlace`, `…_los_errores_de_siempre_van_antes_en_la_recuperacion` (una actual heredada de menos de 8 caracteres repetida da el error de longitud; una confirmación distinta, el de confirmación), `…_los_rechazos_cuentan_para_el_limite_del_enlace` (caracterización: 5 envíos rechazados con una contraseña corta y el 6.º, aunque sea válido, pide esperar y no cambia nada; `/analyze` A9), `…_con_un_enlace_de_otra_cuenta_nunca_se_compara` (el token de A con `cuenta` = B y la contraseña de B como nueva → enlace inválido, nunca «distinta», y ninguna de las dos cambia) y `…_la_contrasena_rechazada_no_queda_en_la_sesion` (recuperación: sin `_old_input` ni la contraseña en la sesión) — tests/Feature/ContrasenaDistintaTest.php — hecho cuando: los tres primeros fallan porque hoy se acepta la misma contraseña y los demás pasan (sus roturas, en T020) — cubre: CA1, CA3, CA4, CA5, CA7
  - nota: fallan hoy los cinco que envían la actual (el de junto al campo, el de sesiones/aviso/auditoría, el del enlace que sigue sirviendo, el del límite y el de la sesión), porque hoy se acepta; pasan los cuatro de caracterización (CA3, CA4, CA5 y TM3), cuyas roturas se hacen en T020.
  - nota: A9 — `…_los_rechazos_cuentan_para_el_limite_del_enlace` usa como rechazos cinco envíos de la actual (rechazados por «distinta») en lugar de contraseñas cortas: así prueba CA7 tal como lo dice la spec (también cuentan los rechazos nuevos) y falla hoy en lugar de ser de caracterización; su rotura (quitar el `throttle`) sigue en T020.
- [x] T011 Escribir los tests del perfil en el mismo archivo: `test_el_personal_no_puede_cambiar_la_contrasena_por_el_perfil` (admin, dentista y asistente → 403 y su contraseña no cambia), `test_perfil_con_la_actual_se_rechaza_junto_al_campo`, `…_una_diferencia_minima_se_acepta_en_el_perfil`, `…_una_cuenta_sin_contrasena_crea_la_suya_en_el_perfil`, `…_los_errores_de_siempre_van_antes_en_el_perfil`, `…_perfil_sin_la_actual_correcta_no_revela_si_es_igual`, `…_perfil_con_el_limite_agotado_no_revela_si_es_igual` (5 fallos y después la actual correcta con la nueva igual → «Demasiados intentos»), `…_perfil_el_error_queda_asociado_al_campo_y_con_el_foco` (cada campo de «Cambiar contraseña» con `label[for]` hacia su `id`; con error, `aria-invalid`, `aria-describedby` hacia el mensaje y `autofocus` en el primer campo con error, con un solo `autofocus` en la página) y el caso del perfil de `…_la_contrasena_rechazada_no_queda_en_la_sesion` — tests/Feature/ContrasenaDistintaTest.php — hecho cuando: el del personal, el de la actual repetida y el de accesibilidad fallan por la razón esperada y los demás pasan (sus roturas, en T021 y T024) — cubre: CA2, CA3, CA4, CA5, CA6, CA8 — depende: T010
  - nota: fallan hoy el del personal (302 en lugar de 403), el de la actual repetida (sin error) y el de accesibilidad (los campos no tienen `id`); el caso del perfil de `…_la_contrasena_rechazada_no_queda_en_la_sesion` falla con el de la recuperación. Pasan los cinco de caracterización (CA3, CA4, CA5, CA6 dos veces), cuyas roturas se hacen en T021.
  - nota: A12 — `…_los_errores_de_siempre_van_antes_en_el_perfil` envía la actual como nueva con una confirmación distinta y espera «Las contraseñas no coinciden.» (en el perfil, `confirmed` deja su error en `password`), más una actual heredada de 7 caracteres repetida.

## Implementación
- [x] T020 Añadir a `restablecer` un `$validador->after()` que, sin otros errores y si `$usuario->password_hash` existe, añade a `password` «Elige una contraseña distinta de la actual» cuando `Hash::check` acierta (plan D1, D4) — app/Http/Controllers/RecuperarContrasenaController.php — hecho cuando: pasan los tests de T010 y se comprueba que cada control falla su test al romperlo un momento: quitar «sin otros errores» (CA5); comparar antes de validar el enlace, con `Usuario::find($cuenta)` (TM3); quitar `throttle:fijar-contrasena` de la ruta (CA7, TM2); tratar la falta de contraseña como «igual» (CA4; quitar la condición no rompe nada porque `Hash::check` con `null` da `false`, `/analyze` A2); normalizar la nueva a minúsculas (CA3); añadir `->withInput()` a la respuesta (TM4) — cubre: CA1, CA3, CA4, CA5, CA7 — depende: T010
  - nota: comprobado rompiendo cada control, con su test en rojo y en verde al restaurar: «sin otros errores» quitado (CA5), comparación antes de validar el enlace con `Usuario::find($cuenta)` (TM3), sin `throttle:fijar-contrasena` (CA7, TM2), falta de contraseña tratada como «igual» (CA4), la nueva normalizada a minúsculas (CA3) y `->withInput()` en la redirección (TM4). Los 72 tests de la 041 siguen en verde.
  - nota: A13 — el comentario del `after()` dice que `Hash::check` solo distingue los primeros 72 bytes; los tests usan caracteres simples.
  - nota: A16 — en la recuperación no hay respuesta nueva: el error sale por la redirección `->withErrors($validador)` que ya existía, y la rotura de TM4 fue `->withInput()` en ella.
  - nota: archivo extra justificado: `ContrasenaDistintaTest` (de T010). `assertSessionHasErrors` con valores consume los errores de la sesión antes de abrir la pantalla; el test de junto al campo comprueba el texto exacto en la pantalla, y `setUp` inicia la sesión.
- [x] T021 Añadir a `updatePassword`, después de `errorDeContrasenaActual()` y antes del `update`, el rechazo con `back()->withErrors(['password' => 'Elige una contraseña distinta de la actual'])` cuando la cuenta tiene contraseña y `Hash::check` acierta (plan D2, D4) — app/Http/Controllers/PerfilController.php — hecho cuando: pasan los tests de T011 salvo el del personal y el de accesibilidad, y se comprueba rompiendo: comparar antes de la contraseña actual (CA6, TM1); comparar antes de `validate()` (CA5); normalizar la nueva a minúsculas (CA3); tratar la falta de contraseña como «igual» (CA4); añadir `->withInput()` a la respuesta (TM4) — cubre: CA2, CA3, CA4, CA5, CA6 — depende: T011
  - nota: comprobado rompiendo cada control, con su test en rojo y en verde al restaurar: comparar antes de la contraseña actual (CA6, TM1; fallan los dos tests de CA6), comparar antes de `validate()` (CA5), normalizar la nueva a minúsculas (CA3), tratar la falta de contraseña como «igual» (CA4) y `->withInput()` en la respuesta nueva (TM4). `PerfilCambioCorreoTest` sigue en verde.
  - nota: A12 — la rotura «comparar antes de `validate()`» hace fallar `…_los_errores_de_siempre_van_antes_en_el_perfil`, que envía la actual con una confirmación distinta (sale «distinta» en lugar de «Las contraseñas no coinciden.»).
  - nota: archivo extra justificado: `ContrasenaDistintaTest` (de T011). El test de junto al campo del perfil comprueba el texto y la falta del aviso de éxito en la pantalla, porque las aserciones de sesión con valores consumen los errores antes de abrirla.
- [x] T022 Asociar etiquetas y errores a su campo en «Cambiar contraseña» del perfil, en `password_actual`, `password` y `password_confirmation`, con el patrón de `contrasena_correo` (plan D6): `for` en cada etiqueta e `id` en el campo y el mensaje; con error, `aria-invalid="true"` y `aria-describedby`, y `autofocus` en `password_actual` si tiene error y si no en `password`; sin estilos ni clases nuevos — resources/views/cliente/perfil.blade.php — hecho cuando: pasa `…_perfil_el_error_queda_asociado_al_campo_y_con_el_foco` y siguen en verde `PerfilCambioCorreoTest` y el resto de T011 — cubre: CA2 — depende: T021
  - nota: ids `contrasena-actual`, `contrasena-nueva` y `contrasena-confirmacion`, con su `label for`; mensajes `contrasena-actual-error` y `contrasena-nueva-error`. Se añadió también `autocomplete` (`current-password`, `new-password`), solo atributos. El test de accesibilidad fallaba antes («el campo tiene id») y pasa después; `PerfilCambioCorreoTest` sigue en verde. Archivo extra justificado: `ContrasenaDistintaTest`, que abre la página sin aserciones de sesión previas, porque consumen los errores.
- [x] T024 Añadir `->middleware('role:cliente')` a la ruta `cliente.perfil.password` (plan D8) — routes/web.php — hecho cuando: pasa `test_el_personal_no_puede_cambiar_la_contrasena_por_el_perfil`, siguen en verde los tests del perfil con un cliente y `PerfilCambioCorreoTest`, y quitar el middleware un momento hace fallar el test del personal (CA8, TM5) — cubre: CA8 — depende: T011
  - nota: comprobado rompiendo: sin `role:cliente`, `test_el_personal_no_puede_cambiar_la_contrasena_por_el_perfil` falla (302 en lugar de 403) y pasa al restaurarlo (CA8, TM5). Los tests del perfil con un cliente y `PerfilCambioCorreoTest` siguen en verde (29 tests).

## Integración y documentación
- [ ] T023 Pasar la verificación final: suite completa, Pint sobre los 4 PHP tocados (los dos controladores, `routes/web.php` y el test), gitleaks sobre el diff, semgrep `p/php`, `composer audit --locked`, `npm audit --audit-level=high` y revisión de que el diff no tiene `Log::` nuevos — sin archivos — hecho cuando: todo en verde y la única media de `composer audit` es la de la línea base (`symfony/mime`) — depende: T022, T024
  - nota: 2026-11-05 — suite completa 719 en verde (12 140 aserciones; 702 de la línea base y 17 nuevos); Pint limpio en los 4 PHP; gitleaks sin fugas en los 6 commits desde `impl_base` y en los archivos del diff; semgrep `p/php` sin hallazgos; `npm audit` sin avisos; el diff no tiene `Log::` nuevos.
  - bloqueo: 2026-11-05 — `composer audit --locked` falla con 5 avisos de Symfony publicados el 2026-11-04 sin gravedad (`symfony/http-foundation`, `http-kernel` y `routing` < 7.4.23) más el medio de `symfony/mime`; no los causa esta spec — `/chore` en `chore/symfony-7.4.23` (decisión del usuario, 2026-11-05), y después incorporar `main` a esta rama y repetir T023
- [x] T090 Actualizar `docs/architecture.md` si cambió la estructura — sin cambios (plan, Impacto en arquitectura); se anota en la tarea
  - nota: el plan no preveía cambios, pero `docs/architecture.md` decía que `role:cliente` va ruta a ruta solo en cancelar y reprogramar: ahora incluye el cambio de contraseña del perfil (spec 043). Sin otros cambios de estructura.
- [x] T091 Actualizar `docs/deployment.md` y `docs/observability.md` si cambiaron variables, entornos, pasos de deploy, logs, eventos de auditoría o métricas — sin cambios (plan, Observabilidad y Rollout). En `docs/security.md`, RS2.a anota la ruta del cambio de contraseña del perfil con `role:cliente` (spec 043) y sigue pendiente; en `docs/roadmap.md` → Pendientes, `TrimStrings` recorta `password_actual` y `contrasena_correo` (043/A8)
  - nota: `deployment.md` y `observability.md` sin cambios (sin variables, logs, eventos ni métricas nuevos). `security.md`: RS2.a anota `PUT /cliente/perfil/password` con `role:cliente` (spec 043) y sigue `pendiente`. `roadmap.md` → Pendientes: 043/A8.
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
| CA8 | T011 | T024 |

| Amenaza (TM#) | Tarea(s) de control | Tarea(s) de test |
|---|---|---|
| TM1 | T021 | T011 |
| TM2 | T020 | T010 |
| TM3 | T020 | T010 |
| TM4 | T020, T021 | T010, T011 |
| TM5 | T024 | T011 |

| Cambio del plan (módulo) | Tarea(s) |
|---|---|
| app/Http/Controllers/RecuperarContrasenaController.php | T020 |
| app/Http/Controllers/PerfilController.php | T021 |
| routes/web.php | T024 |
| resources/views/cliente/perfil.blade.php | T022 |
| tests/Feature/ContrasenaDistintaTest.php | T010, T011 |

| Requisito no funcional | Tarea(s) de test o verificación manual (T095) |
|---|---|
| Error asociado al campo y foco en el primero con error | T011 (perfil), T010 (recuperación), T022 |
