---
result: fail
round: 2
mode: delta
constitution_version: 1.2.0
date: 2026-10-02
spec_sha: 2de730ee8ff9
plan_sha: 612d1527bf82
tasks_sha: 9600e8af3f81
---

# Análisis · 027 Horario válido del dentista y aviso de citas afectadas

## Resumen
`fail`. Las correcciones de la ronda 1 resuelven sus 13 hallazgos abiertos, pero dos lo hacen con
efectos nuevos:
- **A22 (crítico):** al conservar lo enviado con `old()`, una lista en una hora da un 500 al mostrar
  la ficha tras el rechazo. Reproducido en el contenedor: `htmlspecialchars()` recibe un array.
- **A24 (alto):** P9 se declara cumplido en los campos de hora, que siguen con estilos en línea en px.

Delta hecho por un subagente independiente (diff de unos 59 KB); cada hallazgo se verificó contra
los archivos. Sin cambio estructural: solo se añaden `app/Rules/HoraFinPosterior.php` y TM8.

Conteo: CRÍTICA 1 · ALTA 2 · MEDIA 4 · BAJA 1

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 9 / 9 |
| Amenazas con control y test | 8 / 8 |
| Principios de la constitución evaluados | 9 / 9 |

## Seguimiento de rondas anteriores
Ronda 1: [history/analysis.r1.md](history/analysis.r1.md).

| ID | Severidad | Estado | Evidencia |
|---|---|---|---|
| A1 | CRÍTICA | resuelto | D12, `--border-control` (`--p400`: 3.93:1 / 3.67:1, comprobado con `:root`), `.horario-dia .field` en T003 y test en T012. Hueco en el test → A26 |
| A2 | ALTA | resuelto | Tokens de grosor, de anillo y de desplazamiento en el plan, T001 y T003 |
| A3 | ALTA | resuelto | Plan → "Tests que dependen de la vista"; T022 solo nombra tests sin HTML |
| A5 | CRÍTICA | resuelto | Contratos y D5: `HoraFinPosterior` en lugar de `after:`, se puede hacer en Laravel 12.62 (`DataAwareRule`); caso explícito en T010 |
| A6 | ALTA | resuelto con efecto nuevo (→ A22, A23) | Fila `show.blade.php`, D11, T023 y test de T012 |
| A7 | ALTA | resuelto | TM8, P3 y `test_la_ficha_de_un_dentista_solo_la_ve_la_administracion` |
| A8 | ALTA | resuelto con efecto nuevo (→ A21, A25) | `test_los_pares_de_contraste_cumplen_aa`; pares del Check coinciden con `app.css` |
| A10 | MEDIA | resuelto | Plan → "Errores y resumen"; T010 y T023 |
| A11 | MEDIA | resuelto | Plan → "Mensajes de éxito"; T010 y T011 |
| A13 | MEDIA | resuelto | T010: test HTTP con el servicio simulado (se inyecta en el método, `$this->mock` lo sustituye) |
| A15 | MEDIA | resuelto | Spec → Decisiones: fuente `usuario (/analyze A15)` |
| A19 | BAJA | resuelto | `cubre` de T040 y T012, y la tabla de cobertura |
| A20 | BAJA | resuelto | T090 → fila "Autorización de personal" |

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A22 | Seguridad (CA3, TM4) | CRÍTICA | plan → fila `show.blade.php` (`old()`), test de CA3; T023, T010 | Las horas salen de `old()` dentro de `{{ }}`. Si `hora_inicio` llega como lista, el POST da 302, pero al mostrar la ficha tras la redirección `e(array)` lanza un error y la página da 500 (reproducido el 2026-10-02 con `Blade::render`). El test solo sigue la redirección en los casos de días 0 y 8. | Ninguna entrada manipulada (listas en `hora_inicio`, `hora_fin` o `activo` de un día de 1 a 7) da 500 al mostrar la ficha tras el rechazo. `test_un_horario_manipulado_se_rechaza_sin_error_500` sigue la redirección en cada variante y exige 200. → `/plan --fix`, `/tasks --fix` |
| A21 | Constitución (P2) | ALTA | T003 (depende de T001 y T002) frente a T012 ("fallan… faltan los tokens") | T003 añade los tokens antes de que exista T012, así que el test de contraste y la parte CSS del test de tokens pasarían la primera vez: el "hecho cuando" de T012 no se puede cumplir. | Los tests de tokens y de contraste se ven fallar antes de que `app.css` tenga los tokens. Lo comprueba la nota de fallo previo de T012. → `/tasks --fix` |
| A24 | Constitución (P9) | ALTA | plan → P9 ("campos de hora… solo usan tokens"), fila `show.blade.php`; T023, T012 | Los campos de hora y las filas se modifican, pero conservan `style="width:110px;padding:6px 10px;font-size:13px"` y `padding:10px 12px;border:1px solid …`. Ninguna tarea los quita ni hay test. | Decisión del usuario (2026-10-02): el estilo de las filas `horario-dia` y de los campos de hora pasa a `app.css` con tokens. Los elementos modificados de la sección `horario` no llevan `style=` con px ni colores literales, y `test_las_piezas_nuevas_solo_usan_tokens` lo comprueba sobre ellos. → `/plan --fix`, `/tasks --fix` |
| A23 | Inconsistencia | MEDIA | plan → fila `show.blade.php` y D11 ("tras un error de validación"); T023; T010 (`…por_bloqueo_vuelve_con_mensaje`) | Con `HorarioNoGuardado`, el controlador conserva lo enviado (`withInput`), pero la vista solo usa `old()` tras un error de validación y muestra el horario guardado. "Vuelve a guardar el horario" guardaría el anterior. | Tras cualquier rechazo que vuelve con los datos (validación o bloqueo agotado), la ficha muestra lo enviado. El test del bloqueo sigue la redirección y lo comprueba. → `/plan --fix`, `/tasks --fix` |
| A25 | Duplicación | MEDIA | plan → "Campo" y fila `app.css`; T001, T003 (`--font-size-hint`) | `system.html:110` ya define `--text-small: 11.5px` para etiquetas y ayudas. Quedarían dos tokens con el mismo valor y uso. | Un solo token de tamaño pequeño, `--text-small`, en `system.md`, `system.html` y `app.css`; el test de tokens comprueba que `.field-hint` lo usa. → `/plan --fix`, `/tasks --fix` |
| A26 | Cobertura (P9) | MEDIA | plan → `test_las_piezas_nuevas_solo_usan_tokens`; T012 | El test comprueba la regla `.horario-dia .field`, pero no que los campos de hora del HTML estén en una fila `horario-dia` con la clase `field`. Sin la clase, el borde sigue en 1.31:1 y ningún test falla. | Cada campo de hora de la sección `horario` está dentro de una fila `horario-dia` y lleva `field`. Lo comprueba un test de T012 sobre el HTML. → `/plan --fix`, `/tasks --fix` |
| A27 | Inconsistencia | MEDIA | plan → Modelo de amenazas, TM1 ("`after`, `required_with`") | TM1 sigue nombrando `after`, en contra de Contratos, D5 y TM4. | El control de TM1 es `HoraFinPosterior` (y `required_with`), con los mismos tests. → `/plan --fix` |
| A28 | Inconsistencia | BAJA | plan → "Qué ve cada rol", fila "Citas fuera del horario" | La columna "Dentista, asistente, paciente" dice 403, pero la columna Test no cita el test que lo prueba. | La fila cita `test_la_ficha_de_un_dentista_solo_la_ve_la_administracion`. → `/plan --fix` |

## Decisiones pendientes del usuario
Resueltas el 2026-10-02:
- **A24:** los estilos de las filas y de los campos de hora pasan a clases con tokens (no se registra una excepción de P9).
- **Resto:** todos los hallazgos de esta ronda se corrigen con `--fix`; A25 usa el `--text-small` existente.

## Aceptados
Siguen vigentes los aceptados de la ronda 1 (usuario, 2026-10-02):
- **A4** → nota de T010, T011: cada test que hoy pasaría por afirmar una ausencia lleva una cita de control que sí debe aparecer (o exige `H:i`) para fallar por la razón esperada; si no, se declara de caracterización y se anota su rotura.
- **A9** → nota de T001, T003: un solo token de foco, ≥ 3:1, con el mismo nombre en `system.md`, `system.html` y `app.css` (sin dejar `--color-focus` y `--focus-ring` con valores distintos).
- **A12** → nota de T011, T020: `disponible = 0` cuenta como día sin horario, igual que `ReservaCitas`; sin `DB::raw`/`whereRaw`; caso en `test_la_ficha_lista_las_citas_futuras_fuera_del_horario`.
- **A14** → roadmap: al rehacer la 014 y la 020, decidir si la denegación 404 de `definir-horario` se audita, con su test.
- **A16** → nota de T023: "Citas fuera del horario" va antes de "Horarios de atención", con un encabezado de sección real.
- **A17** → nota de T001: la fila de Decisiones de `system.md` 1.1.0 justifica la escala de espaciado.
- **A18** → nota de T003, T004: la comparación con la línea base excluye los tests nuevos de la 027.
