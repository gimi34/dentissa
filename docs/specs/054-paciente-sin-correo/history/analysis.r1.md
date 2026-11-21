---
result: fail
round: 1
mode: full
constitution_version: 1.3.0
date: 2026-11-21
spec_sha: c90d1a8bcec6
plan_sha: cf2867835426
tasks_sha: 9e15e2e14c77
---

# Análisis · 054 Alta de pacientes sin correo

## Resumen
**fail.** El plan da por hecho que la vinculación con Google nunca encuentra una cuenta sin correo,
y es falso. Si Google no devuelve correo, la búsqueda por correo se convierte en `email IS NULL` y
la cuenta de Google queda vinculada a un paciente sin correo. Eso rompe CA7 y TM1 (A1). Hay además
tres ALTOS:
- la contraseña escrita queda guardada en la sesión al volver con el aviso (A2);
- un test existente que choca con las ayudas nuevas (A3);
- el aviso frente al alcance de la 014 (A4).

Todos se corrigen con `--fix` del plan y de las tareas. Análisis completo hecho por un subagente
independiente (perfil `critical`); A1, A2, A3, A5, A6, A10 y A13 se comprobaron contra el código.

Conteo: CRÍTICA 1 · ALTA 3 · MEDIA 7 · BAJA 7

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 11 / 11 (CA8 con la comprobación de T025 como implementación) |
| Amenazas con control y test | 8 / 9 (TM1 sin control real para Google sin correo, A1) |
| Principios de la constitución evaluados | 10 / 10 |

## Seguimiento de rondas anteriores
Primera ronda.

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A1 | Seguridad / Inconsistencia | CRÍTICA | plan «Enfoque técnico», «Sin cambios, comprobado», TM1; `GoogleAuthController.php:45`; `RegisterController.php:147` (`contactoStore`) | `Usuario::where('email', $googleUser->getEmail())` con un correo nulo de Google (basta quitar el permiso de correo en la autorización) se convierte en `WHERE email IS NULL` y vincula el `google_id` a la primera cuenta sin correo: quien entra con Google queda dentro de la cuenta del paciente. `contactoStore` podría además crear una cuenta sin correo desde el registro público (fuera de alcance, spec 002). La versión anterior tiene el mismo código, pero solo es explotable cuando existan cuentas sin correo | `/plan 054 --fix A1`: debe cumplirse que **un inicio de sesión con Google sin correo ni vincula ninguna cuenta ni crea una** y vuelve al inicio de sesión con el fallo de siempre (test: usuario de Socialite con `getEmail()` nulo frente a un paciente sin correo → sin sesión, sin `google_id` guardado y sin filas nuevas). Añadir `GoogleAuthController` (y `RegisterController::contactoStore` si hace falta) a «Cambios por módulo», corregir TM1 y citar RS5; romper la guarda en T025. Al desplegar, el control debe estar antes de que exista la primera cuenta sin correo (misma versión) |
| A2 | Seguridad | ALTA | plan D6 y «Contratos»; `pacientes/create.blade.php:70`; `bootstrap/app.php:58` | `back()->withInput()` guarda en la sesión todo lo escrito, también la contraseña temporal (`dontFlash` solo actúa en errores de validación). La sesión se guarda en archivo y sin cifrar, y la contraseña vuelve a pintarse en un campo de texto | `/plan 054 --fix A2` con la decisión del usuario: **la contraseña nunca llega a la sesión al volver con el aviso; al confirmar sin escribirla de nuevo y con correo, se genera y se muestra una vez** (test: tras el aviso, la sesión no contiene `password` y el campo vuelve vacío; al confirmar con correo, `password_generada`). TM nuevo; citar RS10 si aplica |
| A3 | Cobertura | ALTA | plan «Estrategia de pruebas»; T026; `tests/Feature/MovilPacientesExpedientesTest.php:372` y `:385` | Ese test exige que, sin errores, ningún campo del formulario tenga `aria-describedby`, y que con error el atributo sea exactamente `error-<campo>`. Las ayudas nuevas, enlazadas como «error, después ayuda», lo rompen en el alta y la edición. T026 no permite cambiar lo que comprueba | `/plan 054 --fix A3` y `/tasks 054 --fix A3`: una tarea propia adapta esas aserciones al patrón nuevo (lista de ids que empieza por el error; sin errores, solo ayudas), sin perder lo que protegen (test: el propio `MovilPacientesExpedientesTest` adaptado sigue fallando si se quita el enlace del error) |
| A4 | Conflicto (014) / Constitución P3 | ALTA | spec CA9; plan D7, D11 y P3 | Con la 014, el asistente solo verá pacientes de sus dentistas, pero el aviso enseña el nombre de cualquier coincidencia. Además, la excepción P3 aceptada nombra `store`, `edit` y `update`, no `create`, que es donde se cargan los nombres | Decisión del usuario (2026-11-21): **el aviso muestra el nombre siempre, también fuera del alcance (solo el nombre, nunca otros datos); la 014 lo acepta como excepción**. `/plan 054 --fix A4`: registrarlo en D7/D11 como encargo para `/plan 014 --fix`, ampliar la excepción P3 a `create`, y añadir la fila a «Decisiones» de la spec |
| A5 | Inconsistencia | MEDIA | plan «Cambios por módulo» (`PosiblesDuplicados`); T010, T020 | El filtro `usuarios.rol = 'cliente'` deja fuera fichas de paciente de cuentas del personal, que existen (RS19.a, «un admin con ficha de paciente»); el directorio no filtra por rol | `/plan 054 --fix A5`: el aviso considera toda fila de `pacientes`, sea cual sea el rol de su cuenta (test: un admin con ficha de paciente coincide) |
| A6 | Inconsistencia | MEDIA | plan D4; `PacientesController.php:95` (`password` `nullable\|string\|min:6`) | D4 ignora la contraseña de un alta sin correo, pero la regla `min:6` la sigue validando: con menos de 6 caracteres, el alta sin correo se rechaza con un mensaje en inglés | `/plan 054 --fix A6`: sin correo, la contraseña no se valida ni se guarda (test: alta sin correo con contraseña de 3 caracteres → creada, sin contraseña) |
| A7 | Constitución P2 | MEDIA | `tasks.md` «Constitution Check» P2; T014; T025 | Se dice que el 403 del asistente se verifica rompiendo el control en T025, pero T025 solo rompe los de CA7 y CA8 | `/tasks 054 --fix A7`: T025 también rompe el `abort(403)` de `edit`/`update` y lo anota |
| A8 | Conflicto (052) | MEDIA | spec «Decisiones» (fila de la 052), plan D11, T098; spec 052 CA1, CA6, CA8 | La 052 envía a toda cuenta activa; con correo nulo fallaría y reintentaría (CA8). El aviso solo estaba en la 054 | Resuelto en parte con la decisión del usuario (2026-11-21): nota añadida a «Notas para /plan» de la 052 (worktree `dentissa-052`). `/tasks 054 --fix A8`: T003 comprueba si la 052 ya está en `main` y, si lo está, añade la tarea que la adapta |
| A9 | Constitución P9 | MEDIA | plan `pacientes/index` y P9; `index.blade.php:132` | La marca «Sin correo» del directorio no tiene clase propia; lo inmediato sería copiar el `style=""` del correo, con su literal | `/plan 054 --fix A9`: la marca usa una clase del bloque `/* Spec 054 */`, solo con tokens (test: `test_el_bloque_054_solo_usa_tokens` la cubre y la vista no añade `style=""`) |
| A10 | Inconsistencia (CA3) | MEDIA | plan `pacientes/create`, T022; `design.md` «Dónde» | «Registrar de todos modos» es el primer botón de envío del formulario: pulsar Enter en cualquier campo confirmaría el duplicado sin querer | `/plan 054 --fix A10`: Enter en un campo nunca confirma el duplicado (test de marcado: el primer `submit` del formulario no lleva `confirmar_duplicado`) |
| A11 | Conflicto (014) | MEDIA | plan D11; T011, T014; plan 014 «Cambios por módulo» | Con la 014, el 403 del asistente al editar pasará a 404 y el alta del asistente exigirá `dentista_id`: los tests de la 054 cambiarían | `/plan 054 --fix A11`: las altas de prueba como asistente llevan un dentista asignado, y D11 encarga a `/plan 014 --fix` ajustar el test del 403 |
| A12 | Cobertura (RNF) | BAJA | plan «Rendimiento» | No hay índice en `usuarios.apellido`: `LIKE 'x%'` recorre la tabla; «no crece con el número de pacientes» es inexacto | `/plan 054 --fix A12`: corregir la redacción (la medida real sigue siendo manual) |
| A13 | Inconsistencia | BAJA | plan «abuso»; T015; `routes/web.php:163` | `POST /dentista/pacientes` responde 405 (existe el `GET`), no 404, y no deja `acceso_denegado` | `/plan 054 --fix A13` y `/tasks 054 --fix A13`: esperar 405 y ninguna fila nueva |
| A14 | Ambigüedad | BAJA | spec K2; `design.md`; plan «Accesibilidad medible» | Un `role="alert"` presente al cargar no se anuncia de forma fiable; el error de CA2 solo se lee al llegar al campo | `/plan 054 --fix A14`: el aviso se anuncia al cargar (foco en su título); verificación manual con lector de pantalla en T022 |
| A15 | Ambigüedad | BAJA | spec «Decisiones» (supuestos de desactivados y espacios) | Dos supuestos con fuente «agente» que afectan a CA3 y CA1 | Confirmados por el usuario (2026-11-21): fila en «Decisiones» de la spec con fuente `usuario` en la ronda de `--fix` |
| A16 | Cobertura | BAJA | `tasks.md` «Cobertura», fila CA10 | El test del alta denegada (CA10) está en T015, pero la fila solo cita T016 | `/tasks 054 --fix A16` |
| A17 | Inconsistencia | BAJA | `design.md` «Caja» (icono de 18 px); plan | El icono del aviso no está en el plan y su tamaño es un literal | `/plan 054 --fix A17`: el icono va con tamaño por token o se quita del acta con `/design-spec 054 --fix` |
| A18 | Terminología | BAJA | spec CA11 («cambio de cuenta»); plan «Contratos»; `docs/observability.md` OB1.f | La spec dice cambio de cuenta y el evento usa el recurso `pacientes` | `/tasks 054 --fix A18`: T091 deja explícita la correspondencia de los dos eventos con OB1.f |

Afirmaciones del plan comprobadas y ciertas: el login exige correo y `Hash::check` con un hash nulo
falla; la recuperación busca por un correo validado y ya tolera `password_hash` nulo;
`password_hash` es nulable desde la migración de `google_id`; `uq_usuarios_email` admite varios
nulos; la tabla usa `utf8mb4_unicode_ci`; no hay `lang/` (idioma `en`); solo admin y asistente tienen
`POST pacientes`; `pacientes/edit` incluye `pacientes/create`; `system.md` está en 1.20.0; las vistas
de Usuarios, Personal y `citas/show` ya ocultan con `@if` un correo vacío.

## Decisiones pendientes del usuario
Ninguna: resueltas el 2026-11-21.
- **A4:** el aviso muestra el nombre siempre; la 014 lo acepta como excepción.
- **A2:** la contraseña se vacía al volver con el aviso y, si hay correo, se genera al confirmar.
- **A15:** se confirman los dos supuestos.
- **A8:** nota en la 052 ahora.

## Aceptados
Ninguno: todos los hallazgos se corrigen en la ronda de `--fix`.
