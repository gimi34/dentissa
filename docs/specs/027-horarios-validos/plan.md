---
spec: 027-horarios-validos
status: approved   # aprobado por el usuario el 2026-10-02
created: 2026-10-02
---

# Plan · 027 Horario válido del dentista y aviso de citas afectadas

## Enfoque técnico
Un servicio nuevo, `App\Agenda\HorarioDentista`, junto a `ReservaCitas` y `EstadosCita`, concentra:
- el guardado del horario;
- el cálculo de las citas futuras que quedan fuera de él.

El guardado se hace en una transacción. Primero bloquea la fila del dentista en `personal`, el
mismo bloqueo que toma la reserva de citas (spec 025), así que una reserva simultánea ve el horario
anterior completo o el nuevo, nunca uno a medias. Después borra el horario anterior e inserta uno
por día marcado.

El controlador valida cada día antes de llamar al servicio (formato `HH:MM`, fin posterior al
inicio el mismo día, horas obligatorias en los días marcados, solo días 1–7). Un Gate nuevo,
`definir-horario`, solo permite a la administración y solo sobre un dentista. Cualquier otra
persona o un id inexistente responde 404.

La ficha del dentista muestra siempre la lista de citas futuras que quedan fuera de su horario,
calculada con la misma regla de franja que la reserva. Las piezas nuevas de la pantalla usan solo
tokens del sistema de diseño (P9): la primera tarea incorpora al sistema lo que falta.

## Constitution Check
| Principio | Resultado | Justificación / ajuste | Cómo se verifica |
|---|---|---|---|
| P1 Spec aprobada antes de código | ✅ | Spec 027 `approved` (2026-10-02). | manual: `aidd.py status` antes de `/implement` |
| P2 Test que falla antes y pasa después | ✅ | Constitución 1.2.0. CA1–CA8 cambian comportamiento y sus tests fallan hoy: se guarda sin validar, se rellenan horas por defecto, se acepta un horario para un asistente y no hay lista. CA9 ya se cumple con `role:admin`: su test es de caracterización y se comprueba quitando un momento el middleware. Todos con `DatabaseTransactions` contra `dentissa_test`. | test: notas de las tareas de test (fallo previo o rotura comprobada) |
| P3 Autorización en servidor, por rol y recurso | ✅ | `POST /admin/personal/{id}/horarios`: `role:admin` más el Gate `definir-horario` (solo admin; recurso de tipo dentista, si no 404). `GET /admin/personal/{id}`: `role:admin` como hoy; la sección de horario y la lista se calculan solo si el Gate lo permite. Tests por rol, permitido y denegado. | test: `HorarioDentistaTest::test_solo_la_administracion_guarda_horarios`, `test_no_se_guarda_horario_para_quien_no_es_dentista` |
| P4 Entrada validada y SQL con bindings | ✅ | `dias` con claves 1–7; cada día solo admite `activo`, `hora_inicio` y `hora_fin`; las horas con `date_format:H:i`, obligatorias si el día está marcado, y el fin con `after` del inicio. Query Builder con bindings. En Blade, solo salida escapada (dobles llaves). | test: `HorarioDentistaTest::test_un_horario_manipulado_se_rechaza_sin_error_500`, `test_una_franja_invertida_o_que_cruza_la_medianoche_se_rechaza` · lint: semgrep `p/php` |
| P5 Nada sensible en el repo | ✅ | Solo código, tests y documentación. | lint: gitleaks |
| P6 Sin vulnerabilidades críticas/altas | ✅ | Sin dependencias nuevas. Línea base del 2026-10-02: composer audit `--locked` y npm audit con 0 críticas y 0 altas. | lint: composer audit y npm audit en `/review` |
| P7 Auditoría de datos sensibles | ➖ | El horario es un dato operativo, no está en la lista de P7. La lista de citas afectadas solo se muestra y no cambia datos (spec 027, Auditoría). | — |
| P8 Correlación y logs limpios | ✅ | Sin logs nuevos. Las consultas nuevas solo enlazan ids, días y horas, sin datos de salud ni personales. | manual: `/review` revisa que no haya llamadas a `Log::` nuevas |
| P9 La interfaz usa solo el sistema de diseño | ✅ | La primera tarea incorpora al sistema los tokens de espaciado, `.field-hint`/`.field-hint-error` en `app.css`, el componente de lista y el anillo de foco ("Cambios a incorporar al sistema"). Las piezas nuevas (parcial de la lista y errores por día) solo usan tokens y clases del sistema. Pares de contraste usados: texto `#000` sobre blanco (21:1), error 7.07:1, badges agendada 6.05:1 y confirmada 5.70:1, enlace `--p600` 7.00:1 y foco `--p600` 7.00:1. | test: `HorarioDentistaVistasTest::test_las_piezas_nuevas_solo_usan_tokens` · test: `test_los_errores_de_un_dia_se_asocian_a_sus_campos` · manual: ficha a 1440 px contra `system.html` |

## Cambios por módulo
| Módulo | Cambio | Riesgo |
|---|---|---|
| `app/Agenda/HorarioDentista.php` (nuevo) | Constructor con `ConnectionInterface`, como `ReservaCitas` y `EstadosCita`. `guardar(string $dentistaId, array $franjas): int`, con `$franjas = [dia => ['inicio' => 'H:i', 'fin' => 'H:i']]`, en una transacción: `lockForUpdate()` de la fila de `personal`, borrar el horario y una fila por día con `disponible = 1`. Devuelve el número de citas fuera tras guardar. Los errores 1205/1213 y `DeadlockException` → `HorarioNoGuardado::ocupado()`; cualquier otro se relanza. `citasFuera(string $dentistaId): Collection` devuelve las citas `agendada` o `confirmada` del dentista que aún no empiezan en la zona de la clínica (como `EstadosCita::haEmpezado`) y que quedan fuera: en un día sin horario, o con `inicio < franja.inicio` o `inicio + duracion_min > franja.fin` (misma regla y mismos bordes que `ReservaCitas`). Lleva paciente, fecha, hora y estado, ordenadas por fecha. | Medio: regla de franja repetida respecto de `ReservaCitas` (ver Riesgos) |
| `app/Agenda/HorarioNoGuardado.php` (nuevo) | `RuntimeException` con `ocupado()`: "Otra persona está agendando con este dentista en este momento. Vuelve a guardar el horario." | Bajo |
| `app/Autorizacion/GestionPersonal.php` (nuevo) | `definirHorario(Usuario $usuario, object $personal): Response`: admin y `tipo = dentista` → permitir; admin con otro tipo → `denyAsNotFound()` (404); cualquier otro rol → `deny()` (403; hoy ya lo corta el middleware) | Bajo |
| `app/Providers/AppServiceProvider.php` | `Gate::define('definir-horario', [GestionPersonal::class, 'definirHorario'])` | Bajo |
| `app/Http/Controllers/PersonalController.php` | `saveHorarios(Request, string $id, HorarioDentista)`: 404 si el id no existe → `Gate::authorize('definir-horario', $miembro)` → validación (reglas y mensajes de Contratos) → franjas solo de los días con `activo` → `guardar` → mensaje según el resultado (Contratos). Si se lanza `HorarioNoGuardado`, `back()->withInput()->with('error', …)`. `show`: si `Gate::allows('definir-horario', $miembro)`, pasa `$puedeDefinirHorario = true` y `$citasFuera = citasFuera($id)`; si no, `false` y una colección vacía | Medio |
| `resources/views/personal/show.blade.php` | La sección "Horarios de atención" solo se muestra si `$puedeDefinirHorario`. Valores de hora en `H:i` (`old()` tras un error). Bajo cada día, `@error` de `hora_inicio` y `hora_fin` con `field-hint field-hint-error` e `id`, y en los campos `aria-describedby` y `aria-invalid`. Resumen `role="alert"` al principio del formulario si hay errores en `dias`. `@include('personal.partials.citas-fuera')` si hay citas fuera | Bajo: revisar a mano contra `system.html` |
| `resources/views/personal/partials/citas-fuera.blade.php` (nuevo) | Sección "Citas fuera del horario" con el componente `.lista-citas`: paciente, fecha (`d/m/Y`), hora, badge de estado (`partials/badge-estado`) y enlace "Ver cita" (`admin.citas.show`). Sin `style=""`, solo clases del sistema | Bajo |
| `public/css/app.css` | Tokens `--space-1` … `--space-8` (4, 6, 8, 10, 14, 16, 20, 28 px) y `--focus-ring: var(--p600)` en `:root`. Se mueven aquí `.field-hint` y `.field-hint-error`. Componente nuevo `.lista-citas` (filas con tokens de espaciado, borde `--border`, texto `--text`) y anillo `:focus-visible` con `--focus-ring` para `.lista-citas a` y `.horario-dia input` | Bajo: tokens nuevos y clases movidas, sin cambiar valores existentes |
| `public/css/citas.css` | Se quitan `.field-hint` y `.field-hint-error` (ya están en `app.css`, que carga el layout en todas las vistas) | Bajo: mismas reglas, en otro archivo |
| `docs/design/system.md`, `docs/design/system.html` | "Cambios a incorporar" (abajo): `version` 1.1.0, escala de espaciado, campo y lista, foco, y su entrada en "Decisiones"; DS4 y DS5 en parte | Bajo |
| `tests/Feature/HorarioDentistaTest.php` (nuevo) | CA1–CA6 y CA9, bloqueo | — |
| `tests/Feature/CitasFueraDelHorarioTest.php` (nuevo) | CA7 y CA8 | — |
| `tests/Feature/HorarioDentistaVistasTest.php` (nuevo) | "Qué ve cada rol", accesibilidad de los errores y solo tokens | — |

## Contratos y datos

### Endpoint
| Método y ruta | Rol | Entrada | Éxito | Rechazos |
|---|---|---|---|---|
| `POST /admin/personal/{id}/horarios` | admin (`role:admin` + Gate `definir-horario`) | `dias[N][activo]=1`, `dias[N][hora_inicio]=HH:MM`, `dias[N][hora_fin]=HH:MM`, con N de 1 (lunes) a 7 (domingo). Los días sin `activo` se ignoran. | 302 `back()` con `success`: "Horario guardado.", más "N citas quedaron fuera del horario: revísalas en «Citas fuera del horario»." si N > 0. Sin días: "Horario guardado sin días de atención: no se podrán agendar citas con este dentista hasta que tenga horario." | 404: id inexistente o persona que no es dentista · 403: otro rol (middleware) · 302 `back()->withInput()` con `errors.dias.N.*` (validación) · 302 con `error` si el bloqueo se agota |

### Validación y mensajes
| Regla | Mensaje |
|---|---|
| `dias` → `nullable\|array:1,2,3,4,5,6,7` | "El horario enviado no es válido." |
| `dias.*` → `array:activo,hora_inicio,hora_fin` | "El horario enviado no es válido." |
| `dias.*.activo` → `nullable\|in:1` | "El horario enviado no es válido." |
| `dias.*.hora_inicio` → `nullable\|required_with:dias.*.activo\|date_format:H:i` | requerida: "Indica la hora de inicio de este día." · formato: "Escribe la hora con el formato HH:MM." |
| `dias.*.hora_fin` → `nullable\|required_with:dias.*.activo\|date_format:H:i\|after:dias.*.hora_inicio` | requerida: "Indica la hora de fin de este día." · formato: igual · `after`: "La hora de fin debe ser posterior a la de inicio, el mismo día." |

Sin migraciones ni cambios de esquema: el horario sigue en `horarios_dentista` (un registro por
día, `disponible = 1`).

### Qué ve cada rol
| Pantalla y elemento | Admin en la ficha de un dentista | Admin en la ficha de un asistente o administrador | Dentista, asistente, paciente | Test |
|---|---|---|---|---|
| Sección "Horarios de atención" | formulario con los 7 días | no aparece | no acceden a la ficha (403, como hoy) | `HorarioDentistaVistasTest::test_la_ficha_solo_ofrece_horario_a_los_dentistas` |
| Error de un día | junto al día, asociado a sus campos y con resumen `role="alert"` | — | — | `test_los_errores_de_un_dia_se_asocian_a_sus_campos` |
| Sección "Citas fuera del horario" | aparece si hay citas futuras fuera; si no hay, no aparece | no aparece | — | `CitasFueraDelHorarioTest::test_la_ficha_lista_las_citas_futuras_fuera_del_horario`, `test_sin_citas_fuera_no_hay_lista` |

### Cambios a incorporar al sistema
La primera tarea los aplica en `app.css`, `system.md` (versión 1.1.0, con su entrada en
"Decisiones") y `system.html`:
- **Escala de espaciado** `--space-1` … `--space-8` = 4, 6, 8, 10, 14, 16, 20 y 28 px, los valores
  literales más usados (system.md → Espaciado). Atiende DS4 en parte.
- **Campo:** `.field-hint` y `.field-hint-error` pasan a `app.css`, con los mismos valores. Atiende
  DS5 en parte.
- **Componente "Lista de citas"** (`.lista-citas`): filas separadas por `--border`, padding
  `--space-5`/`--space-6`, texto `--text` (no `--text-light`, para cumplir 4.5:1) y una acción por
  fila con `.btn btn-outline btn-sm`.
- **Anillo de foco** `--focus-ring` (`--p600`, 7.00:1 sobre blanco) con `:focus-visible` en las
  piezas nuevas. Atiende DS2 solo en esta pantalla.

## Estrategia de pruebas
Todos los tests son Feature con PHPUnit, `DatabaseTransactions` y datos propios: admin, dentista,
asistente, pacientes y citas a horas locales con `Carbon::setTestNow` en UTC, como en la 026. Las
aserciones de HTML se hacen sobre la sección por `id` (`horario`, `citas-fuera`).
- **`HorarioDentistaTest`:**
  - `test_un_horario_valido_se_guarda_y_la_ficha_lo_muestra` (CA1): guarda 2 días y comprueba las filas y la ficha.
  - `test_una_franja_invertida_o_que_cruza_la_medianoche_se_rechaza` (CA2): 18:00–09:00 y 22:00–02:00, con el mensaje bajo el día y el horario anterior intacto.
  - `test_un_horario_manipulado_se_rechaza_sin_error_500` (CA3): día 8, día 0, hora `25:99`, texto y listas, con 302 y errores, sin 500.
  - `test_un_dia_marcado_sin_horas_se_rechaza` (CA4): hoy se rellena 09:00–18:00, así que falla.
  - `test_un_horario_sin_dias_se_guarda_con_aviso` (CA5).
  - `test_no_se_guarda_horario_para_quien_no_es_dentista` (CA6): asistente, administrador e id inexistente, con 404 y sin filas.
  - `test_solo_la_administracion_guarda_horarios` (CA9, caracterización): dentista, asistente y paciente reciben 403; se comprueba quitando un momento `role:admin` de la ruta.
  - `test_el_horario_bloquea_al_dentista_antes_de_borrar` (TM5): `DB::listen` exige `select … from personal … for update` antes del `delete from horarios_dentista`.
  - `test_un_bloqueo_agotado_no_da_error_500` (TM5): conexión simulada con 1205/1213 → mensaje.
- **`CitasFueraDelHorarioTest`:**
  - `test_la_ficha_lista_las_citas_futuras_fuera_del_horario` (CA7): una cita en un día que se quita, otra que empieza antes y otra que termina después; aparecen las tres con paciente, fecha, hora, estado y enlace, y las citas no cambian.
  - `test_al_guardar_el_mensaje_dice_cuantas_quedaron_fuera` (CA7).
  - `test_sin_citas_fuera_no_hay_lista` (CA8).
  - `test_las_citas_pasadas_o_cerradas_no_aparecen` (CA8): pasada, cancelada, no asistida, completada y en curso fuera del horario, y ninguna aparece.
  - `test_una_cita_justo_en_el_borde_de_la_franja_no_cuenta_como_fuera` (CA7, bordes iguales a la reserva).
  - `test_solo_aparecen_citas_del_dentista` (TM6).
  - `test_el_nombre_del_paciente_se_muestra_escapado` (TM7).
- **`HorarioDentistaVistasTest`:**
  - `test_la_ficha_solo_ofrece_horario_a_los_dentistas`;
  - `test_los_errores_de_un_dia_se_asocian_a_sus_campos` (`aria-describedby` apunta al `id` del error y hay `aria-invalid` y resumen `role="alert"`);
  - `test_las_piezas_nuevas_solo_usan_tokens` (P9): el parcial `citas-fuera` no tiene `style=` y las reglas `.lista-citas` y `--focus-ring` de `app.css` no tienen colores hex/rgb ni px fuera de `:root`.
- **Regresión:** la suite completa, incluidas `ReglaAgendaTest` y `ReservaConcurrenteTest` de la 025, porque leen `horarios_dentista`.

## Modelo de amenazas
| ID | Amenaza (STRIDE) | Categoría OWASP | Componente | Control | Test |
|---|---|---|---|---|---|
| TM1 | Tampering: franja invertida, que cruza la medianoche o un día sin horas, que deja la agenda incoherente | A06:2025 | `saveHorarios` | Validación por día (`after`, `required_with`) | `test_una_franja_invertida_o_que_cruza_la_medianoche_se_rechaza`, `test_un_dia_marcado_sin_horas_se_rechaza` |
| TM2 | Elevation: un rol distinto de admin guarda horarios | A01:2025 | ruta y Gate | `role:admin` + Gate `definir-horario` | `test_solo_la_administracion_guarda_horarios` |
| TM3 | Tampering: horario para un asistente, un administrador o un id inexistente | A01:2025 | Gate | `definir-horario` exige tipo dentista → 404 | `test_no_se_guarda_horario_para_quien_no_es_dentista` |
| TM4 | DoS: entrada manipulada (claves fuera de 1–7, listas, horas mal escritas) que provoca un 500 | A05:2025 | validación | `array:` con claves permitidas y `date_format:H:i` | `test_un_horario_manipulado_se_rechaza_sin_error_500` |
| TM5 | Tampering (carrera): una reserva simultánea ve el horario a medias o sin días | A06:2025 | `HorarioDentista::guardar` | Bloqueo de la fila de `personal` antes de borrar, el mismo que la reserva; 1205/1213 → mensaje | `test_el_horario_bloquea_al_dentista_antes_de_borrar`, `test_un_bloqueo_agotado_no_da_error_500` |
| TM6 | Information disclosure: la lista muestra citas de otro dentista | A01:2025 | `citasFuera` | Filtro por `dentista_id` | `test_solo_aparecen_citas_del_dentista` |
| TM7 | Tampering (XSS): el nombre de un paciente con HTML en la lista | A05:2025 | parcial `citas-fuera` | Salida escapada de Blade | `test_el_nombre_del_paciente_se_muestra_escapado` |

## Trazabilidad
| Criterio de aceptación | Cambio(s) | Test(s) |
|---|---|---|
| CA1: horario válido guardado y mostrado | `HorarioDentista::guardar`, `saveHorarios`, vista | `HorarioDentistaTest::test_un_horario_valido_se_guarda_y_la_ficha_lo_muestra` |
| CA2 (abuso): franja invertida o que cruza la medianoche rechazada | validación `after` | `test_una_franja_invertida_o_que_cruza_la_medianoche_se_rechaza` |
| CA3 (abuso): horario manipulado sin 500 | validación `array:`/`date_format` | `test_un_horario_manipulado_se_rechaza_sin_error_500` |
| CA4: día marcado sin horas rechazado | `required_with` | `test_un_dia_marcado_sin_horas_se_rechaza` |
| CA5: horario sin días con aviso | `saveHorarios` (mensaje) | `test_un_horario_sin_dias_se_guarda_con_aviso` |
| CA6 (abuso): solo dentistas | Gate `definir-horario`, vista | `test_no_se_guarda_horario_para_quien_no_es_dentista`, `HorarioDentistaVistasTest::test_la_ficha_solo_ofrece_horario_a_los_dentistas` |
| CA7: lista de citas fuera tras guardar | `citasFuera`, `show`, parcial | `CitasFueraDelHorarioTest::test_la_ficha_lista_las_citas_futuras_fuera_del_horario`, `test_al_guardar_el_mensaje_dice_cuantas_quedaron_fuera`, `test_una_cita_justo_en_el_borde_de_la_franja_no_cuenta_como_fuera` |
| CA8: sin citas fuera no hay lista; las pasadas o cerradas nunca | `citasFuera` | `test_sin_citas_fuera_no_hay_lista`, `test_las_citas_pasadas_o_cerradas_no_aparecen` |
| CA9 (abuso): otros roles rechazados (caracterización) | `role:admin` + Gate | `HorarioDentistaTest::test_solo_la_administracion_guarda_horarios` |
| TM1 | validación | `test_una_franja_invertida_o_que_cruza_la_medianoche_se_rechaza`, `test_un_dia_marcado_sin_horas_se_rechaza` |
| TM2 | ruta y Gate | `test_solo_la_administracion_guarda_horarios` |
| TM3 | Gate | `test_no_se_guarda_horario_para_quien_no_es_dentista` |
| TM4 | validación | `test_un_horario_manipulado_se_rechaza_sin_error_500` |
| TM5 | `guardar` | `test_el_horario_bloquea_al_dentista_antes_de_borrar`, `test_un_bloqueo_agotado_no_da_error_500` |
| TM6 | `citasFuera` | `test_solo_aparecen_citas_del_dentista` |
| TM7 | parcial | `test_el_nombre_del_paciente_se_muestra_escapado` |
| RNF: mensajes en español que dicen qué corregir | Contratos (mensajes) | tests de CA2, CA3, CA4 y CA5, con el texto exacto |
| RNF: WCAG 2.1 AA en lo nuevo o modificado | vista, parcial, `app.css` | `test_los_errores_de_un_dia_se_asocian_a_sus_campos`, `test_las_piezas_nuevas_solo_usan_tokens` · verificación manual: ficha de un dentista a 1440 px con un error y con citas fuera, contra `docs/design/system.html` (contraste de los pares usados y foco visible con teclado) |

## Observabilidad
No aplica: la spec no añade logs, ni eventos de auditoría (spec 027, Auditoría: el horario es un
dato operativo), ni métricas. Los rechazos se devuelven al usuario sin registrar nada.

Verificación tras el despliegue: guardar el horario de un dentista de prueba y comprobar la ficha y
la lista de citas fuera.

## Rollout
- Sin despliegue configurado: hoy solo existe el entorno local (`docs/deployment.md`). Cuando haya
  staging, se libera como cualquier versión.
- Feature flag: no (decisión del usuario, 2026-10-02).
- Orden: un solo paso, sin migraciones. Los cambios de CSS añaden tokens y mueven dos clases sin
  cambiar valores, así que las vistas existentes no cambian de aspecto.
- Compatibilidad: el formulario sigue enviando los mismos campos. Lo que cambia para la
  administración: un día marcado necesita sus dos horas, y la ficha de un asistente ya no ofrece
  horario.
- Rollback: volver a desplegar la versión anterior. Los horarios guardados siguen siendo válidos
  para la versión anterior.
- Qué vigilar: errores 500 en `POST …/horarios` y en la ficha del personal.

## Decisiones (→ ADR si son arquitectónicas)
- **D1. Dónde vive la lógica.**
  - Opciones: (a) servicio `App\Agenda\HorarioDentista`; (b) dejarla en `PersonalController`.
  - Se elige (a): el horario pertenece a la Agenda (módulo de la 013) y comparte bloqueo y regla de franja con `ReservaCitas`.
  - Sin ADR.
- **D2. Autorización:** Gate `definir-horario` en `App\Autorizacion\GestionPersonal`, con el mismo patrón que la 026 (ADR 0003). Un admin sobre alguien que no es dentista → 404: un horario de dentista no existe para otro tipo de personal.
- **D3. Citas fuera:** sección fija en la ficha, calculada en cada visita (decisión del usuario, 2026-10-02). Tras guardar, el mensaje dice cuántas son. No hay estado guardado ni datos en sesión.
- **D4. Bloqueo:** el guardado bloquea la fila de `personal` antes de borrar, en el mismo orden que `ReservaCitas` (personal → horarios → citas), para no crear interbloqueos nuevos. La espera es la de MariaDB por defecto (sin `SET SESSION`, como en la 026).
- **D5. Validación en el controlador** (`$request->validate`, como en la 025 y la 026); el servicio recibe franjas ya válidas.
- **D6. Formato de hora en el formulario:** `HH:MM`. Los valores guardados (`HH:MM:SS`) se muestran recortados para que el formulario los reenvíe en el formato que se valida.
- **D7. Sistema de diseño primero** (decisión del usuario, 2026-10-02): tokens de espaciado, campo, lista y foco (ver "Cambios a incorporar al sistema"). Avanza DS4, DS5 y DS2 solo en lo que esta pantalla necesita.
- **D8. Constitución 1.2.0** (decisión del usuario, 2026-10-02): P2 distingue los criterios de caracterización. CA9 se apoya en eso, sin excepción.
- **D9. Sin feature flag** (decisión del usuario, 2026-10-02).
- **D10. Deuda cerrada:** CA2 cierra 025/R12 (franjas que cruzan la medianoche). La regla de agenda no cambia.

## Impacto en arquitectura
Al implementar (T090/T091):
- **`docs/architecture.md`:**
  - módulo "Agenda (horario)" con `app/Agenda/HorarioDentista.php`;
  - la fila de Autorización con el Gate `definir-horario`;
  - en Personal, que el horario lo guarda el servicio.
- **`docs/security.md`:**
  - RS12.b → "en curso (spec 027)";
  - RS12.c sigue "pendiente", con la nota "horario de dentista: spec 027";
  - "Dónde se aplica", con el Gate `definir-horario`.
- **`docs/deployment.md`:** sin cambios (no hay variables ni migraciones).
- **`docs/roadmap.md`:** 025/R12, resuelta por la 027.

## Riesgos y mitigaciones
- **Regla de franja repetida** en `ReservaCitas` y `HorarioDentista::citasFuera`. Si una cambia y la otra no, la lista no coincidiría con lo que la reserva rechaza. Mitigación: `test_una_cita_justo_en_el_borde_de_la_franja_no_cuenta_como_fuera` usa los mismos bordes que `ReglaAgendaTest`. Extraer la regla común queda para la spec 013 (roadmap).
- **Bloqueo nuevo sobre `personal`:** un guardado de horario espera a las reservas en curso de ese dentista, y al revés. Las transacciones son cortas y siguen el mismo orden que la reserva. Mitigación: `test_el_horario_bloquea_al_dentista_antes_de_borrar` y el mensaje de bloqueo agotado.
- **Mover clases de `citas.css` a `app.css`:** las 7 vistas que cargan `citas.css` también cargan `app.css` desde el layout, y los valores no cambian. Mitigación: la suite de vistas existente y una revisión manual de una vista de citas.
- **El formulario guarda horas `HH:MM:SS`** y la validación exige `HH:MM`: si la vista no recortara los valores, el siguiente guardado se rechazaría. Mitigación: D6 y `test_un_horario_valido_se_guarda_y_la_ficha_lo_muestra`, que vuelve a enviar el formulario tal como lo muestra la ficha.
- **La ficha de personal tiene mucha deuda DS** (estilos en línea, foco invisible, contraste de `--text-light`). P9 solo obliga a las piezas nuevas o modificadas; el resto sigue en el objetivo 6 y en "Pendientes y deuda".
