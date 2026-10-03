---
spec: 027-horarios-validos
status: approved   # aprobado por el usuario el 2026-10-03 (--fix A22–A28)
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
| P2 Test que falla antes y pasa después | ✅ | Constitución 1.2.0. CA1–CA8 cambian comportamiento y sus tests fallan hoy: se guarda sin validar, se rellenan horas por defecto, se acepta un horario para un asistente y no hay lista. CA9 y el acceso a la ficha ya se cumplen con `role:admin`: sus tests son de caracterización y se comprueban quitando un momento el middleware. Todos con `DatabaseTransactions` contra `dentissa_test`. | test: notas de las tareas de test (fallo previo o rotura comprobada) |
| P3 Autorización en servidor, por rol y recurso | ✅ | `POST /admin/personal/{id}/horarios`: `role:admin` más el Gate `definir-horario` (solo admin; recurso de tipo dentista, si no 404). `GET /admin/personal/{id}` (modificado: ahora muestra pacientes y fechas de citas): `role:admin` como hoy; la sección de horario y la lista se calculan solo si el Gate lo permite. Tests por rol, permitido y denegado, en las dos rutas. | test: `HorarioDentistaTest::test_solo_la_administracion_guarda_horarios`, `test_no_se_guarda_horario_para_quien_no_es_dentista`, `HorarioDentistaVistasTest::test_la_ficha_de_un_dentista_solo_la_ve_la_administracion` |
| P4 Entrada validada y SQL con bindings | ✅ | `dias` con claves 1–7; cada día solo admite `activo`, `hora_inicio` y `hora_fin`; las horas con `date_format:H:i` y obligatorias si el día está marcado; el fin posterior al inicio con la regla propia `HoraFinPosterior`, que solo compara cuando las dos horas son textos `H:i` válidos (con `after:` de Laravel, una lista en `hora_inicio` da un `TypeError`, A5). Query Builder con bindings. En Blade, solo salida escapada (dobles llaves). | test: `HorarioDentistaTest::test_un_horario_manipulado_se_rechaza_sin_error_500`, `test_una_franja_invertida_o_que_cruza_la_medianoche_se_rechaza` · lint: semgrep `p/php` |
| P5 Nada sensible en el repo | ✅ | Solo código, tests y documentación. | lint: gitleaks |
| P6 Sin vulnerabilidades críticas/altas | ✅ | Sin dependencias nuevas. Línea base del 2026-10-02: composer audit `--locked` y npm audit con 0 críticas y 0 altas. | lint: composer audit y npm audit en `/review` |
| P7 Auditoría de datos sensibles | ➖ | El horario es un dato operativo, no está en la lista de P7. La lista de citas afectadas solo se muestra y no cambia datos (spec 027, Auditoría). | — |
| P8 Correlación y logs limpios | ✅ | Sin logs nuevos. Las consultas nuevas solo enlazan ids, días y horas, sin datos de salud ni personales. | manual: `/review` revisa que no haya llamadas a `Log::` nuevas |
| P9 La interfaz usa solo el sistema de diseño | ✅ | La primera tarea incorpora al sistema los tokens de espaciado, de grosor, de borde de control y de foco, `.field-hint`/`.field-hint-error` en `app.css` (con tokens), el componente de lista y el anillo de foco ("Cambios a incorporar al sistema"). Las piezas nuevas o modificadas (parcial de la lista, errores por día, resumen y las filas de cada día con su casilla, nombre, campos de hora y notas) solo usan tokens y clases del sistema: sus estilos en línea pasan a `app.css` (decisión del usuario, 2026-10-02, A24). Los campos de hora pasan a un borde ≥ 3:1 (A1). Pares de contraste, sobre `--white` y sobre `--p50` (fila de un día marcado): texto `--text` y `--text-muted` (nombre del día y notas "No disponible" y duración, que hoy usan `--text-light`, 3.5:1) 21:1 / 19.59:1; error `--red` 7.07:1 / 6.60:1; badges agendada (`--p600` sobre `--p100`) y confirmada (`--blue` sobre `--blue-bg`); enlace `--p600` 7.00:1 / 6.54:1; borde de control `--p400` 3.93:1 / 3.67:1; foco `--p600` 7.00:1 / 6.54:1. | test: `HorarioDentistaVistasTest::test_las_piezas_nuevas_solo_usan_tokens` · test: `test_los_pares_de_contraste_cumplen_aa` · test: `test_los_errores_de_un_dia_se_asocian_a_sus_campos` · manual: ficha a 1440 px contra `system.html` (foco con teclado) |

## Cambios por módulo
| Módulo | Cambio | Riesgo |
|---|---|---|
| `app/Agenda/HorarioDentista.php` (nuevo) | Constructor con `ConnectionInterface`, como `ReservaCitas` y `EstadosCita`. `guardar(string $dentistaId, array $franjas): int`, con `$franjas = [dia => ['inicio' => 'H:i', 'fin' => 'H:i']]`, en una transacción: `lockForUpdate()` de la fila de `personal`, borrar el horario y una fila por día con `disponible = 1`. Devuelve el número de citas fuera tras guardar. Los errores 1205/1213 y `DeadlockException` → `HorarioNoGuardado::ocupado()`; cualquier otro se relanza. `citasFuera(string $dentistaId): Collection` devuelve las citas `agendada` o `confirmada` del dentista que aún no empiezan en la zona de la clínica (como `EstadosCita::haEmpezado`) y que quedan fuera: en un día sin horario, o con `inicio < franja.inicio` o `inicio + duracion_min > franja.fin` (misma regla y mismos bordes que `ReservaCitas`). Lleva paciente, fecha, hora y estado, ordenadas por fecha. | Medio: regla de franja repetida respecto de `ReservaCitas` (ver Riesgos) |
| `app/Agenda/HorarioNoGuardado.php` (nuevo) | `RuntimeException` con `ocupado()`: "Otra persona está agendando con este dentista en este momento. Vuelve a guardar el horario." | Bajo |
| `app/Autorizacion/GestionPersonal.php` (nuevo) | `definirHorario(Usuario $usuario, object $personal): Response`: admin y `tipo = dentista` → permitir; admin con otro tipo → `denyAsNotFound()` (404); cualquier otro rol → `deny()` (403; hoy ya lo corta el middleware) | Bajo |
| `app/Rules/HoraFinPosterior.php` (nuevo) | Regla de validación (`ValidationRule` + `DataAwareRule`) para `dias.*.hora_fin`: lee la `hora_inicio` del mismo día y, solo si las dos son textos `H:i` válidos, falla cuando el fin es igual o anterior al inicio, con el mensaje de Contratos. Con cualquier otro valor (lista, texto, `25:99`, vacío) no compara y deja el error a `date_format` o `required_with`. Comprobado con una prueba de concepto el 2026-10-02 | Bajo |
| `app/Providers/AppServiceProvider.php` | `Gate::define('definir-horario', [GestionPersonal::class, 'definirHorario'])` | Bajo |
| `app/Http/Controllers/PersonalController.php` | `saveHorarios(Request, string $id, HorarioDentista)`: 404 si el id no existe → `Gate::authorize('definir-horario', $miembro)` → validación (reglas y mensajes de Contratos, con `HoraFinPosterior`) → franjas solo de los días con `activo` → `guardar` → mensaje según el resultado y el número de días (Contratos). Si se lanza `HorarioNoGuardado`, `back()->withInput()->with('error', …)`. `show`: si `Gate::allows('definir-horario', $miembro)`, pasa `$puedeDefinirHorario = true` y `$citasFuera = citasFuera($id)`; si no, `false` y una colección vacía | Medio |
| `resources/views/personal/show.blade.php` | La sección "Horarios de atención" solo se muestra si `$puedeDefinirHorario`. Valores de hora en `H:i`. El formulario lleva un campo oculto `formulario=horario`. **Tras cualquier rechazo que vuelve con los datos** (validación o bloqueo agotado: `old('formulario') === 'horario'`), cada día toma de `old()` lo enviado: casilla marcada solo si su `activo` es el texto `"1"`, horas solo si son texto (una lista u otro valor se muestra vacío, nunca se imprime con la salida escapada de Blade, A22), y fila visible y habilitada si estaba marcado (sin `hidden-row` ni `disabled`); si no, todo sale del horario guardado (A6, A23, D11). Cada fila es `.horario-dia` (más `.horario-dia--activo` si el día está marcado) y su contenido usa `.horario-dia__nombre`, `.horario-dia__horas` y `.horario-dia__nota`; cada campo de hora lleva `field` y está dentro de su fila (A26). Ni las filas ni su contenido llevan `style=`, y `toggleDia` solo cambia la clase `horario-dia--activo`, `hidden-row`, `hidden` y `disabled`, sin `.style` (A24). Bajo cada día, `@error` de `hora_inicio` y `hora_fin` con `field-hint field-hint-error` e `id`, y en los campos `aria-describedby` y `aria-invalid`. Resumen `role="alert"` al principio del formulario (contrato en "Errores y resumen"). Los elementos nuevos no llevan `style=`. `@include('personal.partials.citas-fuera')` si hay citas fuera | Bajo: revisar a mano contra `system.html` |
| `resources/views/personal/partials/citas-fuera.blade.php` (nuevo) | Sección "Citas fuera del horario" con el componente `.lista-citas`: paciente, fecha (`d/m/Y`), hora, badge de estado (`partials/badge-estado`) y enlace "Ver cita" (`admin.citas.show`). Sin `style=""`, solo clases del sistema | Bajo |
| `public/css/app.css` | En `:root`: `--space-1` … `--space-8` (4, 6, 8, 10, 14, 16, 20, 28 px), `--border-width: 1px`, `--focus-ring: var(--p600)`, `--focus-ring-width: 2px`, `--focus-ring-offset: 2px`, `--border-control: var(--p400)`, `--text-body: 13.5px`, `--text-small: 11.5px` (los nombres que ya usa `system.html`, A25) y `--field-time-width: 110px`. Se mueven aquí `.field-hint` (`font-size: var(--text-small)`, `margin-top: var(--space-1)`: mismos valores) y `.field-hint-error`. Componente nuevo `.lista-citas` (filas con tokens de espaciado, borde `var(--border-width) solid var(--border)`, texto `--text`). Componente "Día del horario" (A24), que sustituye los estilos en línea con los valores más cercanos de la escala: `.horario-dia` (flex, `gap: var(--space-5)`, `padding: var(--space-4) var(--space-5)`, `border-radius: var(--radius-sm)`, borde `var(--border-width) solid var(--border)`, fondo transparente); `.horario-dia--activo` (fondo `--p50`, borde `--p200`); `.horario-dia__nombre` (`font-size: var(--text-body)`, color `--text-muted`, peso 500 en `--activo`); `.horario-dia__horas` (flex, `gap: var(--space-3)`); `.horario-dia__nota` (`font-size: var(--text-small)`, color `--text-muted`); casilla con `accent-color: var(--p400)` y tamaño `var(--space-6)`; y `.horario-dia .field` (`width: var(--field-time-width)`, `padding: var(--space-2) var(--space-4)`, `font-size: var(--text-body)`, `border-color: var(--border-control)`, A1). Anillo `:focus-visible` con `--focus-ring`, `--focus-ring-width` y `--focus-ring-offset` para `.lista-citas a` y `.horario-dia input`. Ninguna regla nueva o movida usa px, hex ni rgb fuera de `:root` | Bajo: tokens nuevos y clases movidas, sin cambiar valores existentes; el componente nuevo solo afecta a la sección de horario de la ficha (cambios de 2 px en espaciado y de 0.5 px en letra, revisados a mano) |
| `public/css/citas.css` | Se quitan `.field-hint` y `.field-hint-error` (ya están en `app.css`, que carga el layout en todas las vistas) | Bajo: mismas reglas, en otro archivo |
| `docs/design/system.md`, `docs/design/system.html` | "Cambios a incorporar" (abajo): `version` 1.1.0, escala de espaciado, campo y lista, foco, y su entrada en "Decisiones"; DS4 y DS5 en parte | Bajo |
| `tests/Feature/HorarioDentistaTest.php` (nuevo) | CA1–CA6 y CA9, bloqueo | — |
| `tests/Feature/CitasFueraDelHorarioTest.php` (nuevo) | CA7 y CA8 | — |
| `tests/Feature/HorarioDentistaVistasTest.php` (nuevo) | "Qué ve cada rol", accesibilidad de los errores y solo tokens | — |

## Contratos y datos

### Endpoint
| Método y ruta | Rol | Entrada | Éxito | Rechazos |
|---|---|---|---|---|
| `POST /admin/personal/{id}/horarios` | admin (`role:admin` + Gate `definir-horario`) | `dias[N][activo]=1`, `dias[N][hora_inicio]=HH:MM`, `dias[N][hora_fin]=HH:MM`, con N de 1 (lunes) a 7 (domingo). Los días sin `activo` se ignoran. | 302 `back()` con `success` (textos exactos en "Mensajes de éxito") | 404: id inexistente o persona que no es dentista · 403: otro rol (middleware) · 302 `back()->withInput()` con errores en `dias`, `dias.N`, `dias.N.activo` o `dias.N.hora_*` (validación; nunca 500, A5) · 302 con `error` si el bloqueo se agota |

### Validación y mensajes
| Regla | Mensaje |
|---|---|
| `dias` → `nullable\|array:1,2,3,4,5,6,7` | "El horario enviado no es válido." |
| `dias.*` → `array:activo,hora_inicio,hora_fin` | "El horario enviado no es válido." |
| `dias.*.activo` → `nullable\|in:1` | "El horario enviado no es válido." |
| `dias.*.hora_inicio` → `nullable\|required_with:dias.*.activo\|date_format:H:i` | requerida: "Indica la hora de inicio de este día." · formato: "Escribe la hora con el formato HH:MM." |
| `dias.*.hora_fin` → `nullable\|required_with:dias.*.activo\|date_format:H:i` + `new HoraFinPosterior` | requerida: "Indica la hora de fin de este día." · formato: igual · `HoraFinPosterior`: "La hora de fin debe ser posterior a la de inicio, el mismo día." |

No se usa `after:` de Laravel: con una lista en `hora_inicio` lanza un `TypeError` (500, A5,
reproducido el 2026-10-02), y con `hora_inicio` mal escrita añade un segundo error engañoso en
`hora_fin`. Con `HoraFinPosterior`, una hora inicial inválida solo da su error de formato.

### Mensajes de éxito
El mensaje es la frase base más, si N > 0, la frase del recuento (N = citas fuera tras guardar),
separadas por un espacio:

| Caso | Texto |
|---|---|
| Base, con algún día | "Horario guardado." |
| Base, sin días | "Horario guardado sin días de atención: no se podrán agendar citas con este dentista hasta que tenga horario." |
| Recuento, N = 1 | "1 cita quedó fuera del horario: revísala en «Citas fuera del horario»." |
| Recuento, N > 1 | "N citas quedaron fuera del horario: revísalas en «Citas fuera del horario»." |

### Errores y resumen
- Los errores de `dias.N.hora_inicio` y `dias.N.hora_fin` (N de 1 a 7) van bajo su día, asociados
  a sus campos.
- El resumen `role="alert"` aparece si hay cualquier error cuya clave empiece por `dias`. Contiene:
  - "El horario enviado no es válido." si hay errores en `dias`, `dias.N` (cualquier N, también 0
    u 8), `dias.N.activo` o en las horas de un día fuera de 1–7: no tienen un campo visible al que
    asociarse (A10);
  - "Revisa el horario de: <días>." con los nombres de los días 1–7 que tienen errores en sus
    horas.

Sin migraciones ni cambios de esquema: el horario sigue en `horarios_dentista` (un registro por
día, `disponible = 1`).

### Qué ve cada rol
| Pantalla y elemento | Admin en la ficha de un dentista | Admin en la ficha de un asistente o administrador | Dentista, asistente, paciente | Test |
|---|---|---|---|---|
| Sección "Horarios de atención" | formulario con los 7 días | no aparece | no acceden a la ficha (403, como hoy) | `HorarioDentistaVistasTest::test_la_ficha_solo_ofrece_horario_a_los_dentistas`, `test_la_ficha_de_un_dentista_solo_la_ve_la_administracion` |
| Error de un día | junto al día, asociado a sus campos y con resumen `role="alert"` | — | — | `test_los_errores_de_un_dia_se_asocian_a_sus_campos` |
| Sección "Citas fuera del horario" | aparece si hay citas futuras fuera; si no hay, no aparece | no aparece | no acceden a la ficha (403) | `CitasFueraDelHorarioTest::test_la_ficha_lista_las_citas_futuras_fuera_del_horario`, `test_sin_citas_fuera_no_hay_lista`, `HorarioDentistaVistasTest::test_la_ficha_de_un_dentista_solo_la_ve_la_administracion` |

### Cambios a incorporar al sistema
La primera tarea los aplica en `app.css`, `system.md` (versión 1.1.0, con su entrada en
"Decisiones") y `system.html`:
- **Escala de espaciado** `--space-1` … `--space-8` = 4, 6, 8, 10, 14, 16, 20 y 28 px, los valores
  literales más usados (system.md → Espaciado). Atiende DS4 en parte.
- **Grosores:** `--border-width` (1px, ya en `system.html`), `--focus-ring-width` (2px) y
  `--focus-ring-offset` (2px), para que las reglas nuevas no usen px (A2).
- **Campo:** `.field-hint` y `.field-hint-error` pasan a `app.css`, con los mismos valores expresados
  con tokens (`--text-small` 11.5px, que ya define `system.html`, y `--space-1`). Borde de control `--border-control`
  (`--p400`, 3.93:1 sobre blanco y 3.67:1 sobre `--p50`), aplicado en esta spec solo a los campos
  de hora del horario (decisión del usuario, 2026-10-02, A1); el resto de campos sigue en DS2.
  Atiende DS5 y DS2 en parte.
- **Componente "Lista de citas"** (`.lista-citas`): filas separadas por `--border`, padding
  `--space-5`/`--space-6`, texto `--text` (no `--text-light`, para cumplir 4.5:1) y una acción por
  fila con `.btn btn-outline btn-sm`.
- **Tipografía:** `--text-body` (13.5px) y `--text-small` (11.5px) pasan de `system.html` a
  `system.md` y `app.css` (A25). Atiende DS4 en parte.
- **Componente "Día del horario"** (`.horario-dia` y sus partes, ver la fila de `app.css`), con
  `--field-time-width` (110px) para el campo de hora: sustituye los estilos en línea de la sección
  (A24).
- **Anillo de foco** `--focus-ring` (`--p600`, 7.00:1 sobre blanco) de `--focus-ring-width` y
  desplazamiento `--focus-ring-offset`, con `:focus-visible` en las piezas nuevas. Atiende DS2 solo en esta pantalla.

## Estrategia de pruebas
Todos los tests son Feature con PHPUnit, `DatabaseTransactions` y datos propios: admin, dentista,
asistente, pacientes y citas a horas locales con `Carbon::setTestNow` en UTC, como en la 026. Las
aserciones de HTML se hacen sobre la sección por `id` (`horario`, `citas-fuera`).
- **`HorarioDentistaTest`:**
  - `test_un_horario_valido_se_guarda_y_la_ficha_lo_muestra` (CA1): guarda 2 días y comprueba las filas y la ficha, que muestra `value="09:00"` (no `09:00:00`), y vuelve a enviar el formulario tal como lo muestra la ficha.
  - `test_una_franja_invertida_o_que_cruza_la_medianoche_se_rechaza` (CA2): 18:00–09:00 y 22:00–02:00, con el mensaje bajo el día y el horario anterior intacto.
  - `test_un_horario_manipulado_se_rechaza_sin_error_500` (CA3): día 8, día 0, hora `25:99`, texto y listas, con 302 y errores, sin 500. Incluye de forma explícita una lista en `hora_inicio` con `hora_fin` válida (A5), y `hora_inicio = "25:99"` con `hora_fin` válida, que solo da el error de formato. Siguiendo la redirección, "El horario enviado no es válido." aparece dentro del resumen `role="alert"` (A10). En **cada** variante se sigue la redirección y la ficha responde 200, también con listas en `hora_inicio`, `hora_fin` y `activo` de un día de 1 a 7 (A22).
  - `test_un_dia_marcado_sin_horas_se_rechaza` (CA4): hoy se rellena 09:00–18:00, así que falla.
  - `test_un_horario_sin_dias_se_guarda_con_aviso` (CA5): texto exacto, y una variante con una cita futura que añade la frase del recuento.
  - `test_no_se_guarda_horario_para_quien_no_es_dentista` (CA6): asistente, administrador e id inexistente, con 404 y sin filas.
  - `test_solo_la_administracion_guarda_horarios` (CA9, caracterización): dentista, asistente y paciente reciben 403; se comprueba quitando un momento `role:admin` de la ruta.
  - `test_el_horario_bloquea_al_dentista_antes_de_borrar` (TM5): `DB::listen` exige `select … from personal … for update` antes del `delete from horarios_dentista`.
  - `test_un_bloqueo_agotado_no_da_error_500` (TM5): conexión simulada con 1205/1213 → mensaje.
  - `test_un_horario_no_guardado_por_bloqueo_vuelve_con_mensaje` (TM5, por HTTP): el servicio simulado lanza `HorarioNoGuardado` en `guardar` (y devuelve una colección vacía en `citasFuera`); 302 con el mensaje en `error`, horario sin cambios, y siguiendo la redirección la ficha muestra lo enviado (p. ej. `value="10:00"` en un día marcado), no el horario guardado (A23).
- **`CitasFueraDelHorarioTest`:**
  - `test_la_ficha_lista_las_citas_futuras_fuera_del_horario` (CA7): una cita en un día que se quita, otra que empieza antes y otra que termina después; aparecen las tres con paciente, fecha, hora, estado y enlace, y las citas no cambian.
  - `test_al_guardar_el_mensaje_dice_cuantas_quedaron_fuera` (CA7): texto exacto con N = 1 y con N = 2.
  - `test_sin_citas_fuera_no_hay_lista` (CA8).
  - `test_las_citas_pasadas_o_cerradas_no_aparecen` (CA8): pasada, cancelada, no asistida, completada y en curso fuera del horario, y ninguna aparece.
  - `test_una_cita_justo_en_el_borde_de_la_franja_no_cuenta_como_fuera` (CA7, bordes iguales a la reserva).
  - `test_solo_aparecen_citas_del_dentista` (TM6).
  - `test_el_nombre_del_paciente_se_muestra_escapado` (TM7).
- **`HorarioDentistaVistasTest`:**
  - `test_la_ficha_solo_ofrece_horario_a_los_dentistas`;
  - `test_la_ficha_de_un_dentista_solo_la_ve_la_administracion` (P3, TM8, caracterización): dentista, asistente y paciente reciben 403 en `GET /admin/personal/{id}` de un dentista con citas fuera, sin el nombre del paciente; se comprueba quitando un momento `role:admin`;
  - `test_los_errores_de_un_dia_se_asocian_a_sus_campos`: `aria-describedby` apunta al `id` del error, hay `aria-invalid` y el resumen `role="alert"` dice "Revisa el horario de: <día>.". Incluye un día que no tenía horario, marcado sin horas: tras el rechazo, su casilla sigue marcada, su fila no tiene `hidden-row` ni `disabled` y su error se ve; y un día desmarcado en el envío sigue desmarcado (A6);
  - `test_las_piezas_nuevas_solo_usan_tokens` (P9): el parcial `citas-fuera` no tiene `style=`; en la sección `horario`, los mensajes `field-hint` y el resumen `role="alert"` no tienen `style=`; las reglas nuevas o movidas de `app.css` (`.lista-citas`, `.horario-dia`, `:focus-visible`, `.field-hint`, `.field-hint-error`) no tienen colores hex/rgb ni px fuera de `:root`, y los campos de hora usan `--border-control` (A1, A2, A8). En la sección `horario`, ninguna fila `horario-dia` ni su contenido tiene `style=`, cada campo de hora está dentro de una fila `horario-dia` y lleva la clase `field`, y `toggleDia` no asigna `.style` (A24, A26);
  - `test_los_pares_de_contraste_cumplen_aa` (P9): lee de `:root` de `app.css` los valores de los tokens (resolviendo `var()`), calcula el contraste WCAG y exige ≥ 4.5:1 en los pares de texto y ≥ 3:1 en el borde de control y el foco, sobre `--white` y `--p50` (pares del Constitution Check).
- **Tests que dependen de la vista** (pasan cuando la ficha está cambiada, no solo el controlador, A3): `test_un_horario_valido_se_guarda_y_la_ficha_lo_muestra` (la ficha debe mostrar `H:i`), `test_una_franja_invertida_o_que_cruza_la_medianoche_se_rechaza` (mensaje bajo el día), la parte de HTML de `test_un_horario_manipulado_se_rechaza_sin_error_500` (resumen y 200 al seguir la redirección), la parte de HTML de `test_un_horario_no_guardado_por_bloqueo_vuelve_con_mensaje` (lo enviado en la ficha), los de `CitasFueraDelHorarioTest` que miran la sección `citas-fuera` y todos los de `HorarioDentistaVistasTest`.
- **Regresión:** la suite completa, incluidas `ReglaAgendaTest` y `ReservaConcurrenteTest` de la 025, porque leen `horarios_dentista`.

## Modelo de amenazas
| ID | Amenaza (STRIDE) | Categoría OWASP | Componente | Control | Test |
|---|---|---|---|---|---|
| TM1 | Tampering: franja invertida, que cruza la medianoche o un día sin horas, que deja la agenda incoherente | A06:2025 | `saveHorarios` | Validación por día (`HoraFinPosterior`, `required_with`) | `test_una_franja_invertida_o_que_cruza_la_medianoche_se_rechaza`, `test_un_dia_marcado_sin_horas_se_rechaza` |
| TM2 | Elevation: un rol distinto de admin guarda horarios | A01:2025 | ruta y Gate | `role:admin` + Gate `definir-horario` | `test_solo_la_administracion_guarda_horarios` |
| TM3 | Tampering: horario para un asistente, un administrador o un id inexistente | A01:2025 | Gate | `definir-horario` exige tipo dentista → 404 | `test_no_se_guarda_horario_para_quien_no_es_dentista` |
| TM4 | DoS: entrada manipulada (claves fuera de 1–7, listas, horas mal escritas) que provoca un 500 | A05:2025 | validación | `array:` con claves permitidas, `date_format:H:i` y `HoraFinPosterior`, que no compara valores que no son horas | `test_un_horario_manipulado_se_rechaza_sin_error_500` |
| TM5 | Tampering (carrera): una reserva simultánea ve el horario a medias o sin días | A06:2025 | `HorarioDentista::guardar` | Bloqueo de la fila de `personal` antes de borrar, el mismo que la reserva; 1205/1213 → mensaje | `test_el_horario_bloquea_al_dentista_antes_de_borrar`, `test_un_bloqueo_agotado_no_da_error_500` |
| TM6 | Information disclosure: la lista muestra citas de otro dentista | A01:2025 | `citasFuera` | Filtro por `dentista_id` | `test_solo_aparecen_citas_del_dentista` |
| TM7 | Tampering (XSS): el nombre de un paciente con HTML en la lista | A05:2025 | parcial `citas-fuera` | Salida escapada de Blade | `test_el_nombre_del_paciente_se_muestra_escapado` |
| TM8 | Information disclosure: un rol distinto de admin ve en la ficha de un dentista los nombres y fechas de las citas de sus pacientes | A01:2025 | `GET /admin/personal/{id}` | `role:admin` en la ruta; la lista solo se calcula si `definir-horario` lo permite | `HorarioDentistaVistasTest::test_la_ficha_de_un_dentista_solo_la_ve_la_administracion` |

## Trazabilidad
| Criterio de aceptación | Cambio(s) | Test(s) |
|---|---|---|
| CA1: horario válido guardado y mostrado | `HorarioDentista::guardar`, `saveHorarios`, vista | `HorarioDentistaTest::test_un_horario_valido_se_guarda_y_la_ficha_lo_muestra` |
| CA2 (abuso): franja invertida o que cruza la medianoche rechazada | `HoraFinPosterior`, vista | `test_una_franja_invertida_o_que_cruza_la_medianoche_se_rechaza` |
| CA3 (abuso): horario manipulado sin 500 | validación `array:`/`date_format`, `HoraFinPosterior`, resumen de la vista | `test_un_horario_manipulado_se_rechaza_sin_error_500` |
| CA4: día marcado sin horas rechazado | `required_with`, vista (`old()`) | `test_un_dia_marcado_sin_horas_se_rechaza`, `HorarioDentistaVistasTest::test_los_errores_de_un_dia_se_asocian_a_sus_campos` |
| CA5: horario sin días con aviso | `saveHorarios` (mensaje) | `test_un_horario_sin_dias_se_guarda_con_aviso` |
| CA6 (abuso): solo dentistas | Gate `definir-horario`, vista | `test_no_se_guarda_horario_para_quien_no_es_dentista`, `HorarioDentistaVistasTest::test_la_ficha_solo_ofrece_horario_a_los_dentistas` |
| CA7: lista de citas fuera tras guardar | `citasFuera`, `show`, parcial | `CitasFueraDelHorarioTest::test_la_ficha_lista_las_citas_futuras_fuera_del_horario`, `test_al_guardar_el_mensaje_dice_cuantas_quedaron_fuera`, `test_una_cita_justo_en_el_borde_de_la_franja_no_cuenta_como_fuera` |
| CA8: sin citas fuera no hay lista; las pasadas o cerradas nunca | `citasFuera` | `test_sin_citas_fuera_no_hay_lista`, `test_las_citas_pasadas_o_cerradas_no_aparecen` |
| CA9 (abuso): otros roles rechazados (caracterización) | `role:admin` + Gate | `HorarioDentistaTest::test_solo_la_administracion_guarda_horarios` |
| TM1 | validación | `test_una_franja_invertida_o_que_cruza_la_medianoche_se_rechaza`, `test_un_dia_marcado_sin_horas_se_rechaza` |
| TM2 | ruta y Gate | `test_solo_la_administracion_guarda_horarios` |
| TM3 | Gate | `test_no_se_guarda_horario_para_quien_no_es_dentista` |
| TM4 | validación | `test_un_horario_manipulado_se_rechaza_sin_error_500` |
| TM5 | `guardar`, `saveHorarios` | `test_el_horario_bloquea_al_dentista_antes_de_borrar`, `test_un_bloqueo_agotado_no_da_error_500`, `test_un_horario_no_guardado_por_bloqueo_vuelve_con_mensaje` |
| TM6 | `citasFuera` | `test_solo_aparecen_citas_del_dentista` |
| TM7 | parcial | `test_el_nombre_del_paciente_se_muestra_escapado` |
| TM8 | ruta | `test_la_ficha_de_un_dentista_solo_la_ve_la_administracion` |
| RNF: mensajes en español que dicen qué corregir | Contratos (mensajes) | tests de CA2, CA3, CA4 y CA5, con el texto exacto |
| RNF: WCAG 2.1 AA en lo nuevo o modificado | vista, parcial, `app.css` | `test_los_errores_de_un_dia_se_asocian_a_sus_campos`, `test_las_piezas_nuevas_solo_usan_tokens`, `test_los_pares_de_contraste_cumplen_aa` · verificación manual: ficha de un dentista a 1440 px con un error y con citas fuera, contra `docs/design/system.html` (foco visible con teclado y aspecto) |

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
- **D5. Validación en el controlador** (`$request->validate`, como en la 025 y la 026); el servicio recibe franjas ya válidas. El orden de las horas no se comprueba con `after:` (A5).
  - Opciones: (a) `after:` (500 con listas); (b) validar en dos pasos (los errores de orden llegarían en un segundo envío); (c) regla propia que solo compara horas válidas.
  - Se elige (c), `App\Rules\HoraFinPosterior`: todos los errores salen en un solo envío y ninguna entrada da 500 (prueba de concepto del 2026-10-02 con 11 entradas manipuladas).
- **D6. Formato de hora en el formulario:** `HH:MM`. Los valores guardados (`HH:MM:SS`) se muestran recortados para que el formulario los reenvíe en el formato que se valida.
- **D7. Sistema de diseño primero** (decisión del usuario, 2026-10-02): tokens de espaciado, campo, lista y foco (ver "Cambios a incorporar al sistema"). Avanza DS4, DS5 y DS2 solo en lo que esta pantalla necesita.
- **D8. Constitución 1.2.0** (decisión del usuario, 2026-10-02): P2 distingue los criterios de caracterización. CA9 se apoya en eso, sin excepción.
- **D9. Sin feature flag** (decisión del usuario, 2026-10-02).
- **D11. Formulario tras un rechazo:** tras un error de validación o un bloqueo agotado, cada día conserva lo enviado (`old()`, señalado por el campo oculto `formulario=horario`, A23). Solo se muestran valores de texto: una lista enviada se muestra vacía, porque imprimirla con la salida escapada de Blade da un 500 (A22, comprobado el 2026-10-02). Los días sin horario siguen mostrando 09:00 y 18:00 en sus campos deshabilitados, como hoy: la administración los ve al marcar el día, y mientras están deshabilitados no se envían (CA4 solo prohíbe rellenar horas sin que se vean).
- **D12. Borde de control ≥ 3:1** solo en los campos de hora del horario (decisión del usuario, 2026-10-02, A1). Los demás campos de la app siguen en DS2 y el objetivo 6.
- **D13. Sin estilos en línea en la sección de horario** (decisión del usuario, 2026-10-02, A24): las filas de cada día, su contenido y el JavaScript que las alterna pasan a clases con tokens. Se usan los valores más cercanos de la escala (12px → `--space-5`, 13px → `--text-body`, 15px → `--space-6`, 12px de letra → `--text-small`), y "No disponible" pasa de `--text-light` (3.5:1, no cumple 4.5:1) a `--text-muted`. El resto de la ficha sigue con su deuda DS.
- **D10. Deuda cerrada:** CA2 cierra 025/R12 (franjas que cruzan la medianoche). La regla de agenda no cambia.

## Impacto en arquitectura
Al implementar (T090/T091):
- **`docs/architecture.md`:**
  - módulo "Agenda (horario)" con `app/Agenda/HorarioDentista.php` y la regla `app/Rules/HoraFinPosterior.php`;
  - la fila de Autorización de personal con el Gate `definir-horario`;
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
- **El formulario guarda horas `HH:MM:SS`** y la validación exige `HH:MM`: si la vista no recortara los valores, el siguiente guardado se rechazaría. Mitigación: D6 y `test_un_horario_valido_se_guarda_y_la_ficha_lo_muestra`, que exige `value="09:00"` y vuelve a enviar el formulario tal como lo muestra la ficha.
- **La ficha de personal tiene mucha deuda DS** (estilos en línea, foco invisible, contraste de `--text-light`). P9 solo obliga a las piezas nuevas o modificadas; el resto sigue en el objetivo 6 y en "Pendientes y deuda".
