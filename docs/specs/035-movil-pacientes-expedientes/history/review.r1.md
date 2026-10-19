---
spec: 035-movil-pacientes-expedientes
verdict: changes_requested
round: 1
date: 2026-10-18
base: 883af40
head: 7ed2fae
human_signoff: no requerido
---

# Review · 035 Móvil para pacientes y expedientes del personal

## Resumen
Sin bloqueantes: la suite, Pint, el validador, los escáneres y `--medir` están en verde, y la
seguridad y la constitución se cumplen. El usuario decide corregir los dos importantes del código de
la spec (R1, migas con una palabra muy larga; R2, tests de CA6 con envíos que la pantalla no hace) y
los menores R4–R14 antes de liberar; R3 (borde de los campos, previo a la 035) y R15–R20 van al roadmap.
Revisores con perfil de modelo (AI-DD 1.15.0): A y S con `opus`, C+D y accesibilidad con `sonnet`.
Insumo adicional: un `/code-review` del usuario sobre la rama (8 hallazgos, todos considerados).
Conteo: bloqueante 0 · importante 3 · menor 17

## Verificación automática
| Comando | Resultado |
|---|---|
| `php artisan test` | ✅ 324 tests (6300 aserciones); línea base de T002: 310, sin fallos |
| Tests nuevos (`MovilPacientesExpedientesTest`, `MovilPacienteTest`), dos veces | ✅ 41 y 41, sin intermitencias |
| `pint --test` (PHP tocados) | ✅ 2 archivos |
| `aidd.py validate docs/specs/035-movil-pacientes-expedientes` | ✅ 0 errores |
| `node docs/design/capturar.mjs --medir` (línea base v0.8.0 de la misma sesión, base de prueba del 2026-10-18) | ✅ «todo en orden», código 0 |
| `npm run build` | ➖ no aplica (solo `welcome.blade.php`) |

## Criterios de aceptación
| CA | Test | Estado | Nota |
|---|---|---|---|
| CA1 | `MovilPacientesExpedientesTest` (ficha en una columna, perfil), `--medir` (035 pantallas) | ✅ | |
| CA2 | `MovilPacientesExpedientesTest` (historial en tarjetas), `--medir` | ✅ | |
| CA3 | `MovilPacientesExpedientesTest` (lista de expedientes, paginación), `--medir` | ✅ | |
| CA4 | `MovilPacientesExpedientesTest` (ficha del expediente), `--medir` | ✅ | |
| CA5 | `MovilPacientesExpedientesTest` (etiquetas, tipos de campo), `--medir` | ✅ | R14: la fecha del alta de expediente conserva su máximo de 220 px en el móvil |
| CA6 | `MovilPacientesExpedientesTest::test_cada_campo_validado_muestra_su_error_junto_a_el`, `--medir` | ⚠ | R2: el caso del expediente envía los dos formularios a la vez y la edición del paciente no tiene caso; R4: la ficha del expediente no marca el campo; R12: `--medir` solo mide los errores en las altas |
| CA7 | `MovilPacientesExpedientesTest` (acciones), `--medir` (toque, centrado) | ✅ | |
| CA8 | `MovilPacientesExpedientesTest` (textos que parten), `MovilPacienteTest::test_los_textos_largos_parten_de_linea`, `--medir` (30 letras) | ⚠ | R1: una palabra de ≥ 42 letras en las migas ensancha la página |
| CA9 | `--medir` (sin desplazamiento a 320, 390, 844 × 390, sin JS) | ⚠ | R1 |
| CA10 | `--medir` (escritorio igual a la línea base a 1024 y 1440) | ✅ | |
| CA11 | `MovilPacienteTest` (menú sin recuadro), `--medir` | ✅ | R11: el test no vigila el fondo del panel |

## Conformidad con el plan
- Cambios fuera del plan: ninguno (`scope.md`: 0 archivos sin tarea; `movil.css` y la lista de `MovilPacienteTest` en tareas de vistas, con nota de archivo extra justificado).
- Partes del plan sin implementar: ninguna. T001, T014, T028 y T041 solo tocan `docs/` (fuera de `code.diff`).
- Desvíos anotados en las tareas: `.migas a` con `break-word` y sin `min-width: 0` (A21, T024; origen de R1) y `.acciones-formulario .btn` con `flex: 1 1 auto` en vez del mismo ancho de D5 (T025).

## Constitution Check (sobre el código)
| Principio | Resultado | Evidencia |
|---|---|---|
| P1 Spec antes que código | ✅ | spec, design.md, plan y tareas aprobados; `analysis.md` r2 pass (desactualizado solo por las tareas T029–T032 añadidas en /implement) |
| P2 Test que falla antes y pasa después | ✅ | notas de T010–T014 y T030 (fallaban por la razón esperada); `test_los_campos_tienen_su_tipo` es de regresión, comprobado rompiendo |
| P3 Autorización en el servidor | ✅ | el diff no toca `app/`, `routes/`, `config/` ni `bootstrap/` |
| P4 Entradas validadas y salida codificada | ✅ | sin `{!! !!}`; `old()` y datos con `{{ }}` |
| P5 Nada sensible en el repo | ✅ | gitleaks sin fugas; la base de prueba y la línea base, fuera del repo |
| P6 Sin vulnerabilidades críticas/altas | ✅ | composer/npm audit sin avisos; trivy solo el falso positivo conocido |
| P7 Auditoría | ➖ | la spec no toca datos, permisos ni autenticación |
| P8 Errores sin detalles internos | ✅ | los errores nuevos solo muestran `{{ $message }}` del validador |
| P9 Solo el sistema de diseño · WCAG 2.1 AA | ✅ | bloque 035 de `app.css` y `movil.css` solo con tokens (tests con control positivo; los 10 tokens coinciden con `system.md`); los `style=""` del diff son estilos movidos de elementos existentes (el total baja en las cinco vistas), sin valores nuevos; pares nuevos: `--p600` sobre blanco (≈ 7:1), `--p800` sobre `--p200` y `--text-muted` sobre blanco (pares previos del escritorio), foco con `--focus-ring`. R3 (bordes) es código previo → deuda DS |
| P10 Rutas indexables descubribles | ➖ | ninguna ruta indexable tocada |

## Seguridad
| Herramienta | Resultado |
|---|---|
| gitleaks (`git --log-opts=883af40..HEAD`) | ✅ sin fugas |
| semgrep (`p/php --metrics=off`, archivos tocados) | ✅ sin hallazgos |
| composer audit + npm audit | crít: 0 · alta: 0 · media: 0 |
| trivy fs | ✅ solo el falso positivo conocido de `vendor/mockery/mockery/docs/requirements.txt` (9), igual que en T002 |

| Tema OWASP (2025) | Resultado | Evidencia |
|---|---|---|
| A01 Control de acceso | ✅ | sin cambios en controladores, rutas, Gates ni middleware; los `@if($base !== 'asistente')` se conservan |
| A02 Configuración y secretos | ➖ | sin cambios de configuración; tests sin secretos |
| A03 Cadena de suministro | ✅ | sin cambios en manifiestos ni lockfiles; sin rutas protegidas tocadas |
| A04 Datos sensibles | ✅ | atributos nuevos con valores fijos (`data-label`, `id`, `aria-*`); sin datos de salud en URL ni atributos |
| A05 Inyección / XSS | ✅ | salida con `{{ }}`; CSS sin `url()`, `@import` ni `attr()` |
| A06 Diseño inseguro | ✅ | sin entradas, acciones ni permisos nuevos (plan: modelo de amenazas no aplica) |
| A07 Autenticación | ➖ | no se toca |
| A08 Integridad / CSRF | ✅ | `@csrf` en todos los formularios tocados |
| A09 Registro | ➖ | sin logs nuevos |
| A10 Errores | ✅ | mensajes del validador, sin detalles internos |

## Observabilidad
- Eventos de auditoría de la spec emitidos y probados: no aplica (el plan no define eventos).
- Datos sensibles en logs del código tocado: ninguno (no se añaden logs).
- `request_id` presente en los logs nuevos: no aplica.

## Hallazgos
| ID | Severidad | Archivo:línea | Hallazgo | Sugerencia |
|---|---|---|---|---|
| R1 | importante | `public/css/movil.css:267` | `.migas a` es `inline-flex` y su texto queda en un ítem flexible anónimo con mínimo de contenido: `overflow-wrap: break-word` no lo reduce, así que una palabra sin espacios más ancha que el hueco (≈ 42 letras a 320 px) se sale del enlace y ensancha la página en las migas de la ficha y del alta de expediente (CA8, CA9). `--medir` solo prueba 30 letras | Test primero: `--medir` con una palabra de 45 letras en el enlace de las migas a 320; después, que esa palabra parta sin perder el toque de la 033 (≥ 44 px) ni partir letra a letra un nombre que cabe en la línea siguiente |
| R2 | importante | `tests/Feature/MovilPacientesExpedientesTest.php:289` | El caso «edición del expediente» envía en un solo PUT los campos del formulario principal y la fecha sugerida, combinación que la pantalla no produce; no se prueba que tras un error del formulario principal la fecha caiga a su valor guardado (`old('proxima_visita', …)`) ni el error solo de «Guardar fecha». La edición del paciente no tiene caso con errores (CA6) | Dos envíos reales del expediente (principal con error; ocultos + fecha no válida) y el PUT de la edición del paciente con errores |
| R3 | importante (código previo) | `resources/views/pacientes/create.blade.php:217`, `expedientes/show.blade.php:223`, `expedientes/create.blade.php` (`.field` del `<style>`) | El borde de los campos en reposo (`--border` sobre blanco, ≈ 1,3:1) no llega a 3:1 (WCAG 1.4.11). Ya era así antes de la 035; corregirlo cambia el escritorio (CA10) | → roadmap (deuda DS), en la spec que rehaga los formularios |
| R4 | menor | `resources/views/expedientes/show.blade.php:73,79,86,93,154` | Se añade `field-error`, pero ningún CSS que carga la ficha del expediente la define: el campo con error no se marca, a diferencia de los otros dos formularios; el test solo mira la clase | Regla con tokens para `.field-error` de los formularios del expediente y test que lo compruebe |
| R5 | menor | `public/css/movil.css:257,260` | `.acciones-formulario .btn` recibe `flex: 1` en la regla combinada y `flex: 1 1 auto` justo debajo, que la anula | Separar las reglas; el test comprueba que el formulario no recibe `flex: 1` |
| R6 | menor | `resources/views/pacientes/show.blade.php:130,252`, `resources/views/expedientes/index.blade.php:80` | Tres `<svg>` decorativos sin `aria-hidden="true"` | Añadirlo, con test |
| R7 | menor | `docs/specs/035-movil-pacientes-expedientes/plan.md` (Constitution Check, P9) | Dice «nueve tokens nuevos»; son diez desde T031 (`--expediente-dato-max`) | Corregir el texto |
| R8 | menor | `tests/Feature/MovilPacientesExpedientesTest.php:228-234` | `test_los_valores_de_informacion_del_expediente_usan_su_clase` pasa con un solo `.info-valor` y busca `max-width` solo en `span` | Contar los valores de «Información» y exigir la clase en todos; buscar `max-width` en cualquier `style` de `main` |
| R9 | menor | `tests/Feature/MovilPacientesExpedientesTest.php:140-157` | `partible('Consulta')` y similares buscan en toda la página: pueden coincidir con otro elemento | Anclar cada búsqueda a su sección |
| R10 | menor | `tests/Feature/MovilPacientesExpedientesTest.php:212-225` | `test_los_campos_tienen_su_tipo` toma el primer campo por nombre y podría coger un oculto | Filtrar `not(@type='hidden')` |
| R11 | menor | `tests/Feature/MovilPacienteTest.php:362` | CA11 (contraste del icono sin recuadro): el test vigila el fondo de `.topbar`, no el del panel donde está «Cerrar menú» | Comprobar también `.sidebar { background: var(--white) }` en la media query |
| R12 | menor | `docs/design/capturar.mjs:881` | `--medir` mide los errores dentro del ancho a 320 solo en las altas, no en la edición del paciente ni en la ficha del expediente | Ampliar la comprobación a esas dos pantallas |
| R13 | menor | `public/css/movil.css:30-40,252` | Con las acciones en la segunda fila de la barra, la barra no tiene relleno vertical: «Menú» y los botones quedan pegados a los bordes | Relleno vertical con tokens solo cuando la barra contiene `.acciones-ficha`, con test |
| R14 | menor | `resources/views/expedientes/create.blade.php:123` | La fecha de próxima visita conserva `style="max-width:220px"`: en el móvil no ocupa el ancho disponible (CA5) | Token y clase con el máximo de hoy en el escritorio y sin máximo en el móvil, con test |
| R15 | menor (código previo) | `resources/views/pacientes/show.blade.php:5,31` y las demás vistas | La fórmula `$base` del rol se repite en cada vista | → roadmap |
| R16 | menor | `resources/views/pacientes/create.blade.php`, `expedientes/create.blade.php`, `expedientes/show.blade.php` | El marcado de error accesible se repite a mano en ~29 campos (lo protege `test_cada_campo_validado_muestra_su_error_junto_a_el`) | → roadmap (componente Blade de campo) |
| R17 | menor (código previo, DS4) | `tests/Feature/Concerns/ReglasCss.php` (`literales()`) | No detecta `%`, `rem` ni `em`: `border-radius: 50%` de `.perfil-avatar` pasa sin token | → roadmap (token `--radius-full` o excepción documentada) |
| R18 | menor (código previo, DS1) | `.field-label` de los tres formularios | `--text-light` a 11 px: 3,5:1, por debajo de 4,5:1 | → roadmap (ya es DS1) |
| R19 | menor (código previo) | `<style>` de `pacientes/create`, `expedientes/show`, `expedientes/create` | `.error-msg` con `12px` y `4px` literales | → roadmap (pasarlo a `app.css` con tokens) |
| R20 | menor (código previo, seguridad) | `resources/views/pacientes/create.blade.php:64` | La «Contraseña temporal» va en `type="text"` (visible y recordable por el navegador) | → roadmap (`type="password"` con `autocomplete="new-password"` o generarla siempre; relacionada con RS10) |

Descartado: `.dato-recortado` no recorta en el escritorio (`/code-review` n.º 3). Un elemento flexible con
`overflow: hidden` tiene mínimo automático 0, así que encoge y recorta, y `--medir` compara el escritorio
con la línea base sin diferencias.

## Preparación para release
- Rollback factible: sí; solo vistas y CSS, sin migraciones: `deploy/volver.sh <v0.8.0>` sin copia.
- Migraciones compatibles con la versión anterior: no hay migraciones.
- Feature flags: el plan no pide ninguno.
- Docs actualizadas: `architecture.md` (T090); `deployment.md` y `observability.md` sin cambios (T091, no aplica).
- SCA: sin vulnerabilidades críticas ni altas.
- Cobertura de riesgos: la spec no cita riesgos `RS`, `OB` ni `RD`.

## Tareas añadidas
- T042 ← R1, R12
- T043 ← R2, R8, R9, R10
- T044 ← R11
- T045 ← R4, R5, R6, R13, R14
- T046 ← R14
- T047 ← R4, R5, R13, R14
- T048 ← R6
- T049 ← R1
- T050 ← R7
- T051 ← R1, R4, R13, R14 (verificación final, capturas y limpieza)

## Aceptados sin tarea
Aceptados por el usuario el 2026-10-18:
- **R3** → roadmap: borde de los campos de los formularios de paciente y expediente por debajo de 3:1 (WCAG 1.4.11), código previo; en la spec que rehaga los formularios (deuda DS).
- **R15** → roadmap: la fórmula `$base` del rol repetida en cada vista; helper o view composer en un `/refactor`.
- **R16** → roadmap: componente Blade de campo con etiqueta y error accesible.
- **R17** → roadmap: `literales()` de `ReglasCss` no detecta `%`, `rem` ni `em`; token `--radius-full` o excepción documentada (DS4).
- **R18** → roadmap: `.field-label` con 3,5:1 (DS1).
- **R19** → roadmap: `.error-msg` con literales en los `<style>` de las vistas; pasarlo a `app.css` con tokens.
- **R20** → roadmap: la contraseña temporal del alta de paciente en `type="text"` (relacionada con RS10).
