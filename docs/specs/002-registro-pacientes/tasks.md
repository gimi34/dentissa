---
spec: 002-registro-pacientes
plan: plan.md
impl_base: b1d338c
status: approved
---

# Tareas · 002 Autorregistro de pacientes

Formato:
`- [ ] T### [P] <verbo + qué> — <archivos> — hecho cuando: <criterio> — cubre: CA# — depende: T###`
- T001–T089: trabajo. T090–T099: reservadas.
- `[P]` = paralelizable con las demás `[P]` de su grupo una vez cumplidas sus dependencias (no
  comparte archivos con otra tarea abierta).
- `[-]` = obsoleta (`— obsoleta: <motivo> (AAAA-MM-DD)`): no cuenta como abierta ni como hecha.
- Bajo una tarea: `  - nota: …`, `  - bloqueo: AAAA-MM-DD — <qué falta> — <skill a ejecutar>`.
- **Alcance:** solo CA8 cambia comportamiento. CA1–CA6 reciben tests de caracterización; CA7 ya tiene test
  (`LimiteIntentosTest`) y solo tiene que seguir en verde (plan, Estrategia de pruebas).
- **Un solo archivo de tests nuevo**, `tests/Feature/RegistroPacientesTest.php`: las tareas de test lo comparten y van
  en orden, sin `[P]`.
- **Commits:** `AGENTS.md` no admite commits con tests en rojo, así que las tareas de test que fallan hoy (T014, T015)
  van en el mismo commit que la implementación que las pone en verde.
- **Corrección tras `/analyze` r1 (2026-11-21):** `/tasks --fix` editó en su lugar T011 (A1, A7), T014 (A5), T015 y T021 (A2); tras la ronda 2, T015 (N1). No se renumeró ninguna.
- **Datos de prueba:** correos `@example.test` y contraseñas ficticias, sin espacios al principio ni al final.

## Constitution Check
Contra `plan.md` (constitución 1.3.0). Lo que el desglose podría romper:

| Principio | Resultado | Justificación / ajuste | Cómo se verifica |
|---|---|---|---|
| P1 Spec aprobada antes de código | ✅ | Spec y plan aprobados (2026-11-21). Ninguna tarea de código empieza antes de que `tasks.md` esté `approved` y `/analyze` en `pass`. | manual: `aidd.py status` antes de `/implement` |
| P2 Test que falla antes y pasa después | ✅ | T014 y T015, de test, van antes que T020 y T021, que dependen de ellas. Las de caracterización (T010–T013) pasan desde el principio y se comprueban rompiendo un momento el código que protegen, con su nota. | test: T010–T015 |
| P3 Autorización en servidor | ✅ | T013 caracteriza que el rol se fija en el servidor (CA5, TM3). Ninguna tarea cambia autorizaciones. | test: T013 |
| P4 Entrada validada y SQL con bindings | ✅ | T020 solo cambia el texto de un mensaje; ninguna tarea añade SQL. | lint: semgrep `p/php` en T022 |
| P5 Nada sensible en el repo | ✅ | Datos y contraseñas ficticios en T010–T015. | lint: gitleaks en T022 |
| P6 Sin vulnerabilidades críticas/altas | ✅ | Ninguna tarea toca dependencias. | lint: `composer audit --locked` y `node .github/scripts/auditar-npm.mjs` en T022 |
| P7 Auditoría de datos sensibles | ❌ aceptado | Excepción aprobada por el usuario el 2026-11-21 (plan D6 → spec 021): ninguna tarea añade el evento de alta. | manual: diff sin eventos nuevos en T022 |
| P8 Correlación y logs limpios | ✅ | Ninguna tarea añade `Log::`. | manual: diff sin `Log::` en T022 |
| P9 La interfaz usa solo el sistema de diseño | ✅ | T021 usa `.error-msg` y dos reglas nuevas de `auth.css` sin valores literales (color heredado y foco con `--focus-ring*`); T015 prueba los tokens, el foco y el contraste ≥ 4,5:1 (`/analyze` A2). | test: T015 (`…_los_enlaces_del_rechazo_solo_usan_tokens`, `…_tienen_foco_y_contraste`, `…_el_error_del_correo_queda_asociado_al_campo`) |
| P10 Rutas indexables descubribles | ✅ | Ninguna tarea cambia el `GET /registro`. | test: `PaginasPublicasTest` en verde en T022 |

## Preparación
No hace falta: sin dependencias, configuración, interruptor ni migración (plan, Rollout).

## Tests (antes de implementar)
- [x] T010 Crear `RegistroPacientesTest` y escribir `test_el_alta_crea_la_cuenta_y_la_ficha_y_entra` (un `POST /registro` válido crea un `usuarios` con rol `cliente`, `activo` 1 y la contraseña cifrada, y su fila en `pacientes`; queda con sesión y redirige al panel del paciente) y `test_si_falla_la_ficha_no_queda_la_cuenta` (con `DB::beforeExecuting` lanzando un error antes del `insert` en `pacientes`, el alta falla y no queda ninguna fila en `usuarios` con ese correo) — tests/Feature/RegistroPacientesTest.php — hecho cuando: los dos pasan (caracterización) y quitar un momento el `DB::transaction` de `RegisterController::register` hace fallar el segundo; se anota la rotura — cubre: CA1
  - nota: A8 — el callback de `DB::beforeExecuting` lanza un `RuntimeException` al ver ``insert into `pacientes` ``; el test aserta el 500, que no queda ningún `usuarios` con ese correo (`assertDatabaseMissing`) y que no hay sesión.
  - nota: A9 — `datosDelAlta()` es el payload base compartido: confirmación, `terminos`, ciudad de la lista («Ario») y el resto de campos; cada test aserta su clave de error o `assertSessionHasNoErrors`.
  - nota: comprobado rompiendo: con `call_user_func` en lugar de `DB::transaction` en `register`, `test_si_falla_la_ficha_no_queda_la_cuenta` falla (la cuenta queda); al restaurar, pasa. Línea base: suite completa 1521 en verde (19 913 aserciones).
- [x] T011 Escribir, con los dentistas del volcado desactivados dentro de la transacción y los `id` y `personal.creado_en` de los del escenario fijados para que el resultado no dependa del orden de MariaDB (`/analyze` A1): `test_se_asigna_el_dentista_activo_con_menos_pacientes` (D3 inactivo y el más antiguo, sin pacientes; D1 activo, más antiguo que D2 y con un paciente; D2 activo y sin pacientes → se asigna D2), `test_a_igualdad_el_dentista_mas_antiguo` (dos dentistas activos sin pacientes; el más antiguo tiene el UUID mayor → se asigna el más antiguo) y `test_sin_dentistas_activos_el_alta_queda_sin_dentista` (ningún dentista activo → el alta se completa y la ficha queda con `dentista_id` vacío; `/analyze` A7) — tests/Feature/RegistroPacientesTest.php — hecho cuando: los tres pasan (caracterización) y cada rotura hace fallar el test indicado, anotada en la tarea: quitar el orden por `COUNT` → el primero (se asigna D1, más antiguo); quitar el `orderBy('personal.creado_en')` → el segundo; quitar el `where('usuarios.activo', 1)` → el primero (se asigna D3) y el tercero — cubre: CA2 — depende: T010
  - nota: A14 — los dentistas y el paciente de D1 se crean con ayudas del propio archivo (`crearDentista`, `crearPacienteDe`), con `id`, correo y `creado_en` fijos, y los dentistas del volcado se desactivan dentro de la transacción (`desactivarDentistasDelVolcado`).
  - nota: comprobado rompiendo cada control: sin el orden por `COUNT` falla el primero (se asigna D1); sin `where('usuarios.activo', 1)` fallan el primero (D3) y el tercero; al restaurar, pasan.
  - nota: N2 — sin el `orderBy('personal.creado_en')`, el empate no tiene orden definido en SQL y MariaDB elige uno distinto entre ejecuciones (comprobado: con dos dentistas, el test fallaba solo a veces aunque se fijaran `id`, correos y orden de inserción). El test empata seis dentistas: el más antiguo solo gana por azar una vez de cada seis. La rotura falló en 3 de 4 ejecuciones; con el orden intacto el test pasa siempre.
- [x] T012 Escribir `test_el_registro_con_google_crea_la_cuenta_sin_contrasena`: con `google_registro` en la sesión, `POST /registro/contacto` con los términos aceptados crea el usuario con su `google_id`, sin contraseña y con rol `cliente`, y su ficha; queda con sesión y redirige al panel — tests/Feature/RegistroPacientesTest.php — hecho cuando: pasa (caracterización) y guardar un momento una contraseña en `contactoStore` lo hace fallar; se anota la rotura — cubre: CA4 — depende: T011
  - nota: A14 — `google_registro` con `google_id`, `nombre`, `apellido`, `email` (nuevo, `@example.test`) y `foto_url` nula, como `CiudadListaCerradaTest`; el `POST` lleva `terminos` y una ciudad de la lista.
  - nota: comprobado rompiendo: con `Hash::make(...)` en lugar de `null` en `contactoStore`, el test falla; al restaurar, pasa.
- [x] T013 Escribir `test_el_rol_es_siempre_cliente` (un `POST /registro` válido con `rol=admin` y `activo=0` añadidos crea una cuenta `cliente` activa) y `test_una_contrasena_corta_se_rechaza` (7 caracteres → error en `password` y ninguna cuenta con ese correo) — tests/Feature/RegistroPacientesTest.php — hecho cuando: los dos pasan (caracterización) y tomar un momento el rol de `$request->input('rol', 'cliente')` hace fallar el primero y bajar el mínimo a 7 el segundo; se anota la rotura — cubre: CA5, CA6 — depende: T012
  - nota: A12 — el test de la contraseña comprueba también el umbral exacto: 8 caracteres se aceptan y crean la cuenta.
  - nota: comprobado rompiendo: con `request()->input('rol', 'cliente')` en `register`, `test_el_rol_es_siempre_cliente` falla (queda `admin`); con `Password::min(7)`, `test_una_contrasena_corta_se_rechaza` falla; al restaurar, pasan.
- [x] T014 Escribir `test_un_correo_registrado_se_rechaza_sin_decir_que_existe`: registro con el correo de una cuenta existente, también en mayúsculas. Error en `email` con el texto exacto de `RegisterController::CORREO_NO_DISPONIBLE`; la página del formulario no contiene «ya está registrado» ni «ya existe»; bajo el campo hay un enlace a `route('login')` y, con `acceso.recuperar_contrasena` activo, otro a `route('contrasena.solicitud')`, con esos `href` exactos; vuelven el nombre, el apellido, el teléfono, la fecha de nacimiento, el género, la dirección y la ciudad, no vuelve la contraseña y la casilla de términos sale sin marcar (`/analyze` A5); no se crea ninguna cuenta — tests/Feature/RegistroPacientesTest.php — hecho cuando: falla por la razón esperada (hoy la constante no existe y el mensaje dice «Este correo ya está registrado») — cubre: CA3, CA8 — depende: T013
  - nota: fallaba antes de T020 por la razón esperada («Undefined constant RegisterController::CORREO_NO_DISPONIBLE»). Va en el mismo commit que T020 y T021 (`AGENTS.md`: sin commits en rojo).
  - nota: A10 — los enlaces se buscan dentro del grupo del campo del correo (`grupoDelCorreo`, xpath `ancestor::div[contains(@class, 'field-group')][1]`), no en toda la página: el aviso general repite el mensaje sin enlaces.
  - nota: A13 — `test_el_correo_que_vuelve_va_escapado`: un correo con `<b>` vuelve al campo escapado (caracterización; pasa desde el principio porque Blade escapa `old()`).
  - nota: la ciudad va dentro de `<optgroup>`; el xpath la busca como descendiente (`//option`).
- [x] T015 Escribir `test_el_error_del_correo_queda_asociado_al_campo` (con error en el correo, `#email` lleva `aria-invalid="true"` y `aria-describedby` hacia el `id` del mensaje), `test_otros_errores_del_correo_no_llevan_los_enlaces` (con un correo mal escrito, el mensaje de formato sale sin los dos enlaces), `test_los_enlaces_del_rechazo_solo_usan_tokens` (el mensaje y los enlaces no llevan `style=""`, y la regla `.page-register .error-msg a` de `auth.css` existe y no tiene colores ni medidas literales) y `test_los_enlaces_del_rechazo_tienen_foco_y_contraste` (con `ReglasCss` sobre `css/auth.css`: `.page-register .error-msg a:focus-visible` usa `--focus-ring`, `--focus-ring-width` y `--focus-ring-offset`, y el contraste de `--red` sobre `--white` es ≥ 4,5:1 con los tokens de `auth.css`; `/analyze` A2). Para leer esos tokens, `tokensDeColor()` de `ReglasCss` recibe un parámetro `string $archivo = 'css/app.css'`, compatible con sus usos actuales (`/analyze` N1) — tests/Feature/RegistroPacientesTest.php, tests/Feature/Concerns/ReglasCss.php — hecho cuando: el primero, el tercero y el cuarto fallan por la razón esperada (hoy el campo no tiene `aria-*` y las reglas no existen), el segundo pasa y los tests que ya usan `tokensDeColor()` siguen en verde — cubre: CA8 — depende: T014
  - nota: fallaban antes de T021 el de `aria-*` (sin `aria-invalid`), el de tokens (sin la regla) y el de foco (sin la regla de foco); `…_otros_errores_del_correo_no_llevan_los_enlaces` pasaba, como se esperaba.
  - nota: A10 — los enlaces y el `aria-*` se buscan dentro del grupo del campo del correo (`grupoDelCorreo`), no en toda la página.
  - nota: N1 — `tokensDeColor(string $archivo = 'css/app.css')` en `ReglasCss`; el test lo llama con `css/auth.css`. Contraste de `--red` sobre `--white`: ≥ 4,5:1 (unos 7:1).
  - nota: A11 — `test_con_el_limite_agotado_no_salen_los_enlaces`: cinco altas con el correo existente y la sexta da el mensaje del límite, sin enlaces, y vuelven los datos (caracterización del límite de la 030).

## Implementación
- [x] T020 Añadir a `RegisterController` la constante pública `CORREO_NO_DISPONIBLE` con «No pudimos crear la cuenta con estos datos. Si ya tienes una, inicia sesión o recupera tu contraseña.» y usarla en `'email.unique'` (plan D1) — app/Http/Controllers/RegisterController.php — hecho cuando: la parte del mensaje de T014 pasa, siguen en verde T010–T013, `TerminosPrivacidadTest`, `CiudadListaCerradaTest` y `LimiteIntentosTest`, y volver un momento al texto anterior hace fallar T014 (CA8, TM1) — cubre: CA8 — depende: T014
  - nota: comprobado rompiendo: con el texto anterior en `email.unique`, fallan `…_un_correo_registrado_se_rechaza_sin_decir_que_existe` y `…_el_error_del_correo_queda_asociado_al_campo`; al restaurar, pasan. Siguen en verde `TerminosPrivacidadTest`, `CiudadListaCerradaTest`, `LimiteIntentosTest`, `NavegacionSalidasTest`, `NavegacionSalidasCssTest`, `HoraClinicaFormulariosTest` y `PaginasPublicasTest` (196 tests con `RegistroPacientesTest`).
- [x] T021 En `auth/register.blade.php`, bajo el campo `email` y solo si su error es `CORREO_NO_DISPONIBLE`, los enlaces «Iniciar sesión» (`route('login')`) y, con `config('acceso.recuperar_contrasena')`, «Recuperar tu contraseña» (`route('contrasena.solicitud')`); el mensaje con `id`, y el campo con `aria-invalid` y `aria-describedby` cuando tiene error. En `auth.css`, `.page-register .error-msg a { color: inherit; text-decoration: underline; }` y `.page-register .error-msg a:focus-visible` con `outline: var(--focus-ring-width) solid var(--focus-ring); outline-offset: var(--focus-ring-offset);` (plan D2, D3, D4) — resources/views/auth/register.blade.php, public/css/auth.css — hecho cuando: pasan T014 y T015, siguen en verde `CiudadListaCerradaTest` (paso con error) y `NavegacionSalidasCssTest`, quitar un momento la condición del mensaje hace fallar `…_otros_errores_del_correo_no_llevan_los_enlaces` y quitar la regla de foco hace fallar `…_tienen_foco_y_contraste` · verificación manual: estado de error del paso 1 con un correo ya registrado a 390 y 1440 px, contra la pantalla actual del registro (mismo aspecto salvo el texto y los dos enlaces subrayados, legibles y pulsables), y el anillo de foco de los dos enlaces al llegar con el teclado — cubre: CA8 — depende: T015, T020
  - nota: comprobado rompiendo: con `@if(true)` en lugar de la condición del mensaje, fallan `…_otros_errores_del_correo_no_llevan_los_enlaces` y `…_con_el_limite_agotado_no_salen_los_enlaces`; sin la regla de foco, falla `…_tienen_foco_y_contraste`; al restaurar, pasan.
  - nota: verificación manual OK 2026-11-21 (usuario): app del worktree servida contra la base de tests y capturada con Playwright 1.63.0 a 390 y 1440 px; el mensaje neutro arriba y bajo el campo, con «Iniciar sesión · Recuperar tu contraseña» subrayados en el rojo del mensaje; vuelven los datos salvo la contraseña; con el teclado, el Tab desde el correo llega a «Iniciar sesión» con el anillo de foco (`solid 2px`). Los enlaces miden 71×14 px: dentro del texto no tienen tamaño mínimo exigido (WCAG 2.1 AA).

- [x] T023 Escribir `test_otro_error_no_revela_si_el_correo_tiene_cuenta`: con el correo de una cuenta existente y otro campo inválido (sin `terminos`; y en otro envío, con la confirmación distinta), la respuesta solo trae el error de ese campo, sin error en `email` ni `CORREO_NO_DISPONIBLE`, y es la misma que con un correo nuevo con el mismo error — tests/Feature/RegistroPacientesTest.php — hecho cuando: falla por la razón esperada (hoy sale también el mensaje neutro en `email`) — cubre: CA8 — depende: T021
  - nota: añadida por /review: R1
  - nota: R1 — fallaba antes de T024 por la razón esperada («sin los términos: el correo registrado no da error propio»). Los errores se leen de la sesión ya serializada (`erroresDeLaSesion()`, `['default']['messages']`): un primer intento con `getBag()` fallaba por eso, no por el bug, y se corrigió antes de dar el test por bueno. Va en el mismo commit que T024.
- [x] T024 En `register`, comprobar que el correo no está registrado solo cuando el resto del formulario es válido: primero la validación sin `unique`; después, con todo válido, una segunda validación del correo con `unique:usuarios,email` y el mismo mensaje `CORREO_NO_DISPONIBLE`, que vuelve con los datos escritos salvo la contraseña — app/Http/Controllers/RegisterController.php — hecho cuando: pasan T023 y el resto de `RegistroPacientesTest`, siguen en verde `TerminosPrivacidadTest`, `CiudadListaCerradaTest`, `LimiteIntentosTest` y `HoraClinicaFormulariosTest`, y volver un momento a una sola validación hace fallar T023 — cubre: CA8 — depende: T023
  - nota: añadida por /review: R1
  - nota: R1 — `unique:usuarios,email` sale de la primera validación y va en una segunda, con el mismo mensaje `CORREO_NO_DISPONIBLE`, después de que el resto pase. Comprobado rompiendo: con la versión de una sola validación, T023 falla; al restaurar, pasa. Siguen en verde `RegistroPacientesTest`, `TerminosPrivacidadTest`, `CiudadListaCerradaTest`, `LimiteIntentosTest` y `HoraClinicaFormulariosTest` (133 tests).
- [x] T025 Endurecer tres tests: `test_los_empates_se_resuelven_por_antiguedad_en_orden` (seis dentistas activos empatados y seis altas seguidas: cada una se asigna al más antiguo de los que aún no tienen paciente, en el orden de `creado_en`); en `…_los_enlaces_del_rechazo_solo_usan_tokens`, que `.page-register .error-msg` declare `color: var(--red)` y que `.page-register .error-msg a` declare `color: inherit` y `text-decoration: underline`; y `test_el_registro_sigue_indexable_con_su_titulo` (`GET /registro`: 200, `<title>Dentissa · Crear cuenta</title>`, sin `noindex` en `meta robots` ni en `X-Robots-Tag`) — tests/Feature/RegistroPacientesTest.php — hecho cuando: pasan, y se comprueba rompiendo: sin el orden por antigüedad falla el de los empates (se anota en cuántas ejecuciones); sin el subrayado o con otro color en el mensaje falla el de tokens; con `noindex` en la vista falla el de indexación — cubre: CA2, CA8 — depende: T024
  - nota: añadida por /review: R2, R3, R4
  - nota: R2 — `test_los_empates_se_resuelven_por_antiguedad_en_orden`: seis dentistas insertados en un orden distinto al de antigüedad y con UUID que tampoco lo siguen; seis altas seguidas, con `Cache::flush()` entre ellas por el límite de la 030. Comprobado rompiendo: sin el `orderBy('personal.creado_en')`, el test falló en las 3 ejecuciones (antes, con una sola alta, 3 de 4).
  - nota: R3 — comprobado rompiendo: sin `text-decoration: underline` y con `--text-light` en lugar de `--red` en `.error-msg`, `…_solo_usan_tokens` falla; al restaurar, pasa.
  - nota: R4 — `test_el_registro_sigue_indexable_con_su_titulo`; comprobado rompiendo: con `<meta name="robots" content="noindex">` en la vista, falla; al restaurar, pasa.
- [x] T026 Completar tests y un comentario: con `acceso.recuperar_contrasena` en `false`, el rechazo del correo registrado solo lleva el enlace a `route('login')`; en el rechazo, la sesión no guarda `password` ni `password_confirmation` en el `old input` y el campo del correo vuelve con su valor; el registro con Google guarda el teléfono en `usuarios` y la ciudad en `pacientes`; y el comentario de `declaraciones()` en `ReglasCss` deja de decir «en `app.css`» — tests/Feature/RegistroPacientesTest.php, tests/Feature/Concerns/ReglasCss.php — hecho cuando: pasan (caracterización) y quitar un momento la condición de `acceso.recuperar_contrasena` en la vista hace fallar el primero — cubre: CA4, CA8 — depende: T025
  - nota: añadida por /review: R6, R7, R8, R9
  - nota: R6 — `test_sin_recuperacion_solo_se_ofrece_iniciar_sesion`; comprobado rompiendo: con `@if(true)` en lugar de la condición de `acceso.recuperar_contrasena`, falla; al restaurar, pasa. R7 — `test_el_rechazo_conserva_el_correo_y_no_la_contrasena` (old input de la sesión y `value` del campo). R8 — el test de Google comprueba el teléfono y la ciudad guardados. R9 — comentario de `declaraciones()` corregido. 20 tests en verde.
- [x] T027 Escribir `test_el_aviso_general_se_anuncia`: con un error en el registro, el aviso general (`.alert-error`) lleva `role="alert"` y su icono `aria-hidden="true"` — tests/Feature/RegistroPacientesTest.php — hecho cuando: falla por la razón esperada (hoy el aviso no tiene `role`) — cubre: CA8 — depende: T026
  - nota: añadida por /review: R5
  - nota: R5 — fallaba antes de T028 por la razón esperada («El aviso se anuncia al cargar»). Va en el mismo commit que T028.
- [x] T028 Añadir `role="alert"` al aviso general de errores del registro y `aria-hidden="true" focusable="false"` a su icono, sin estilos nuevos — resources/views/auth/register.blade.php — hecho cuando: pasa T027, siguen en verde `RegistroPacientesTest`, `CiudadListaCerradaTest` y `TerminosPrivacidadTest`, y quitar un momento el `role` hace fallar T027; revisión de accesibilidad: solo atributos — cubre: CA8 — depende: T027
  - nota: añadida por /review: R5
  - nota: R5 — `role="alert"` en el aviso y `aria-hidden="true" focusable="false"` en su icono, sin estilos. Comprobado rompiendo: sin el `role`, T027 falla; al restaurar, pasa. Siguen en verde `RegistroPacientesTest`, `CiudadListaCerradaTest` y `TerminosPrivacidadTest` (123 tests). Revisión de accesibilidad: solo atributos; el mensaje del correo se sigue leyendo dos veces (aviso y campo), aceptado como aceptable por el revisor.
## Integración y documentación
- [x] T022 Pasar la verificación final: suite completa, Pint sobre los PHP tocados (`RegisterController.php` y `RegistroPacientesTest.php`), gitleaks sobre el diff, semgrep `p/php` sobre los archivos tocados, `composer audit --locked`, `node .github/scripts/auditar-npm.mjs` y revisión de que el diff no tiene `Log::` ni eventos de auditoría nuevos — sin archivos — hecho cuando: todo en verde, con `LimiteIntentosTest` (CA7) y `PaginasPublicasTest` (P10) en verde, y `composer audit` sin altas ni críticas nuevas respecto a la línea base del plan — cubre: CA7 — depende: T020, T021
  - nota: 2026-11-21 — suite completa 1536 en verde (20 007 aserciones; 1521 de la línea base y 15 nuevos), con `LimiteIntentosTest` (CA7) y `PaginasPublicasTest` (P10); Pint limpio en los 3 PHP; gitleaks (`dir`) sin fugas en los 5 archivos y en la carpeta de la spec; semgrep `p/php` 0 hallazgos (`--no-git-ignore`); `composer audit --locked` sin altas ni críticas; `auditar-npm.mjs` sin avisos; el diff no añade `Log::` ni eventos de auditoría.
- [x] T090 Actualizar `docs/architecture.md` si cambió la estructura — sin cambios previstos (plan, Impacto en arquitectura); se comprueba si nombra el mensaje del registro y se anota en la tarea
  - nota: no cambia la estructura; la fila «Registro» de la tabla de módulos describe ahora el rechazo neutro del correo ya registrado (`CORREO_NO_DISPONIBLE`, spec 002) y sus enlaces.
- [x] T091 Actualizar `docs/deployment.md` y `docs/observability.md` si cambiaron variables, entornos, pasos de deploy, logs, eventos de auditoría o métricas — sin cambios (plan, Observabilidad y Rollout); `docs/security.md` (RS13.b en parte) y `docs/roadmap.md` (excepción P7 y confirmación del correo) se actualizan en `/release`; se anota en la tarea
  - nota: `docs/deployment.md` y `docs/observability.md` sin cambios: sin variables, pasos de deploy, logs, eventos ni métricas nuevos. RS13.c ya está en `docs/security.md` y en el objetivo 2 del roadmap (`/analyze` A3); en `/release`, RS13.b pasa a mitigada y el roadmap recoge la excepción P7 (→ 021) y RS13.c como pendiente.
- [x] T092 Marcar spec como `implemented`
  - nota: 2026-11-21 — de nuevo tras las tareas de `/review` r1 (T023–T028): suite completa 1542 en verde (20 061 aserciones), Pint limpio, gitleaks y semgrep sin hallazgos en los archivos tocados.

## Despliegue (lo ejecuta `/release`)
- [x] T095 Desplegar a staging y verificar criterios de aceptación. No hay staging (objetivo 5): la comprobación se hace en producción, dentro de T097, sin escribir ningún correo real en ningún documento:
  - CA8: registrarse con el correo de una cuenta de prueba ya existente → el mensaje neutro con los dos enlaces y ninguna cuenta nueva
  - nota: resultado: el usuario confirma el 2026-11-21 en producción que, con el correo de una cuenta de prueba existente, sale el mensaje neutro con «Iniciar sesión · Recuperar tu contraseña», y que sin marcar los términos solo sale el error de los términos (R1); sin ningún correo en los documentos.
- [x] T096 Aprobación humana para producción
  - aprobado por gimi34 el 2026-11-21 18:45 para v0.27.0
- [x] T097 Desplegar a producción y vigilar métricas del plan (Rollout): `deploy/desplegar.sh vX.Y.Z` (sin migraciones), `verificar.sh` y la comprobación de T095; vigilar 5xx en `/registro` (aviso `dentissa-vigilar`). Rollback: `bash deploy/volver.sh <tag anterior>`, sin copia
  - nota: 2026-11-21 — `deploy/desplegar.sh v0.27.0` en 152 s (sin la verificación), anterior v0.26.0, copia `dentissa-20261122-004630.sql.gz.gpg`; `verificar.sh` interna y por la dirección pública: todo OK. Tag `v0.27.0` sobre `2b116e09` (PR #107). Rollback, si hiciera falta: `bash deploy/volver.sh v0.26.0`, sin la copia.
- [x] T098 Marcar spec como `released`

## Cobertura
| Criterio | Tarea(s) de test | Tarea(s) de implementación |
|---|---|---|
| CA1 | T010 | ninguna: ya se cumple (caracterización; la rotura de T010 lo prueba) |
| CA2 | T011, T025 | ninguna: ya se cumple (caracterización; las roturas de T011 y T025 lo prueban) |
| CA3 | T014 | ninguna: ya se cumple (la parte del rechazo); T020 cambia solo su texto |
| CA4 | T012, T026 | ninguna: ya se cumple (caracterización; la rotura de T012 lo prueba) |
| CA5 | T013 | ninguna: ya se cumple (caracterización; la rotura de T013 lo prueba) |
| CA6 | T013 | ninguna: ya se cumple (caracterización; la rotura de T013 lo prueba) |
| CA7 | `LimiteIntentosTest` (existente), en verde en T022 | ninguna: ya se cumple (030); T022 confirma que sigue en verde |
| CA8 | T014, T015, T023, T025, T026, T027 | T020, T021, T024, T028 |

| Amenaza (TM#) | Tarea(s) de control | Tarea(s) de test |
|---|---|---|
| TM1 | T020, T024 | T014, T023 |
| TM2 | ninguna: fuera de esta spec → spec futura de confirmación del correo (plan, Modelo de amenazas; decisión del usuario, 2026-11-21) | ninguna (fuera) |
| TM3 | ninguna: ya se cumple (el rol se fija en el servidor); la rotura de T013 lo prueba | T013 |
| TM4 | T021 | T014 |

| Cambio del plan (módulo) | Tarea(s) |
|---|---|
| app/Http/Controllers/RegisterController.php | T020, T024 |
| resources/views/auth/register.blade.php | T021, T028 |
| public/css/auth.css | T021 |
| tests/Feature/RegistroPacientesTest.php | T010, T011, T012, T013, T014, T015, T023, T025, T026, T027 |
| tests/Feature/Concerns/ReglasCss.php | T015, T026 |

| Requisito no funcional | Tarea(s) de test o verificación manual (T095) |
|---|---|
| Contraseña con `Password::min(8)` | T013 |
