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
- [ ] T014 Escribir `test_un_correo_registrado_se_rechaza_sin_decir_que_existe`: registro con el correo de una cuenta existente, también en mayúsculas. Error en `email` con el texto exacto de `RegisterController::CORREO_NO_DISPONIBLE`; la página del formulario no contiene «ya está registrado» ni «ya existe»; bajo el campo hay un enlace a `route('login')` y, con `acceso.recuperar_contrasena` activo, otro a `route('contrasena.solicitud')`, con esos `href` exactos; vuelven el nombre, el apellido, el teléfono, la fecha de nacimiento, el género, la dirección y la ciudad, no vuelve la contraseña y la casilla de términos sale sin marcar (`/analyze` A5); no se crea ninguna cuenta — tests/Feature/RegistroPacientesTest.php — hecho cuando: falla por la razón esperada (hoy la constante no existe y el mensaje dice «Este correo ya está registrado») — cubre: CA3, CA8 — depende: T013
- [ ] T015 Escribir `test_el_error_del_correo_queda_asociado_al_campo` (con error en el correo, `#email` lleva `aria-invalid="true"` y `aria-describedby` hacia el `id` del mensaje), `test_otros_errores_del_correo_no_llevan_los_enlaces` (con un correo mal escrito, el mensaje de formato sale sin los dos enlaces), `test_los_enlaces_del_rechazo_solo_usan_tokens` (el mensaje y los enlaces no llevan `style=""`, y la regla `.page-register .error-msg a` de `auth.css` existe y no tiene colores ni medidas literales) y `test_los_enlaces_del_rechazo_tienen_foco_y_contraste` (con `ReglasCss` sobre `css/auth.css`: `.page-register .error-msg a:focus-visible` usa `--focus-ring`, `--focus-ring-width` y `--focus-ring-offset`, y el contraste de `--red` sobre `--white` es ≥ 4,5:1 con los tokens de `auth.css`; `/analyze` A2). Para leer esos tokens, `tokensDeColor()` de `ReglasCss` recibe un parámetro `string $archivo = 'css/app.css'`, compatible con sus usos actuales (`/analyze` N1) — tests/Feature/RegistroPacientesTest.php, tests/Feature/Concerns/ReglasCss.php — hecho cuando: el primero, el tercero y el cuarto fallan por la razón esperada (hoy el campo no tiene `aria-*` y las reglas no existen), el segundo pasa y los tests que ya usan `tokensDeColor()` siguen en verde — cubre: CA8 — depende: T014

## Implementación
- [ ] T020 Añadir a `RegisterController` la constante pública `CORREO_NO_DISPONIBLE` con «No pudimos crear la cuenta con estos datos. Si ya tienes una, inicia sesión o recupera tu contraseña.» y usarla en `'email.unique'` (plan D1) — app/Http/Controllers/RegisterController.php — hecho cuando: la parte del mensaje de T014 pasa, siguen en verde T010–T013, `TerminosPrivacidadTest`, `CiudadListaCerradaTest` y `LimiteIntentosTest`, y volver un momento al texto anterior hace fallar T014 (CA8, TM1) — cubre: CA8 — depende: T014
- [ ] T021 En `auth/register.blade.php`, bajo el campo `email` y solo si su error es `CORREO_NO_DISPONIBLE`, los enlaces «Iniciar sesión» (`route('login')`) y, con `config('acceso.recuperar_contrasena')`, «Recuperar tu contraseña» (`route('contrasena.solicitud')`); el mensaje con `id`, y el campo con `aria-invalid` y `aria-describedby` cuando tiene error. En `auth.css`, `.page-register .error-msg a { color: inherit; text-decoration: underline; }` y `.page-register .error-msg a:focus-visible` con `outline: var(--focus-ring-width) solid var(--focus-ring); outline-offset: var(--focus-ring-offset);` (plan D2, D3, D4) — resources/views/auth/register.blade.php, public/css/auth.css — hecho cuando: pasan T014 y T015, siguen en verde `CiudadListaCerradaTest` (paso con error) y `NavegacionSalidasCssTest`, quitar un momento la condición del mensaje hace fallar `…_otros_errores_del_correo_no_llevan_los_enlaces` y quitar la regla de foco hace fallar `…_tienen_foco_y_contraste` · verificación manual: estado de error del paso 1 con un correo ya registrado a 390 y 1440 px, contra la pantalla actual del registro (mismo aspecto salvo el texto y los dos enlaces subrayados, legibles y pulsables), y el anillo de foco de los dos enlaces al llegar con el teclado — cubre: CA8 — depende: T015, T020

## Integración y documentación
- [ ] T022 Pasar la verificación final: suite completa, Pint sobre los PHP tocados (`RegisterController.php` y `RegistroPacientesTest.php`), gitleaks sobre el diff, semgrep `p/php` sobre los archivos tocados, `composer audit --locked`, `node .github/scripts/auditar-npm.mjs` y revisión de que el diff no tiene `Log::` ni eventos de auditoría nuevos — sin archivos — hecho cuando: todo en verde, con `LimiteIntentosTest` (CA7) y `PaginasPublicasTest` (P10) en verde, y `composer audit` sin altas ni críticas nuevas respecto a la línea base del plan — cubre: CA7 — depende: T020, T021
- [ ] T090 Actualizar `docs/architecture.md` si cambió la estructura — sin cambios previstos (plan, Impacto en arquitectura); se comprueba si nombra el mensaje del registro y se anota en la tarea
- [ ] T091 Actualizar `docs/deployment.md` y `docs/observability.md` si cambiaron variables, entornos, pasos de deploy, logs, eventos de auditoría o métricas — sin cambios (plan, Observabilidad y Rollout); `docs/security.md` (RS13.b en parte) y `docs/roadmap.md` (excepción P7 y confirmación del correo) se actualizan en `/release`; se anota en la tarea
- [ ] T092 Marcar spec como `implemented`

## Despliegue (lo ejecuta `/release`)
- [ ] T095 Desplegar a staging y verificar criterios de aceptación. No hay staging (objetivo 5): la comprobación se hace en producción, dentro de T097, sin escribir ningún correo real en ningún documento:
  - CA8: registrarse con el correo de una cuenta de prueba ya existente → el mensaje neutro con los dos enlaces y ninguna cuenta nueva
- [ ] T096 Aprobación humana para producción
- [ ] T097 Desplegar a producción y vigilar métricas del plan (Rollout): `deploy/desplegar.sh vX.Y.Z` (sin migraciones), `verificar.sh` y la comprobación de T095; vigilar 5xx en `/registro` (aviso `dentissa-vigilar`). Rollback: `bash deploy/volver.sh <tag anterior>`, sin copia
- [ ] T098 Marcar spec como `released`

## Cobertura
| Criterio | Tarea(s) de test | Tarea(s) de implementación |
|---|---|---|
| CA1 | T010 | ninguna: ya se cumple (caracterización; la rotura de T010 lo prueba) |
| CA2 | T011 | ninguna: ya se cumple (caracterización; las tres roturas de T011 lo prueban) |
| CA3 | T014 | ninguna: ya se cumple (la parte del rechazo); T020 cambia solo su texto |
| CA4 | T012 | ninguna: ya se cumple (caracterización; la rotura de T012 lo prueba) |
| CA5 | T013 | ninguna: ya se cumple (caracterización; la rotura de T013 lo prueba) |
| CA6 | T013 | ninguna: ya se cumple (caracterización; la rotura de T013 lo prueba) |
| CA7 | `LimiteIntentosTest` (existente), en verde en T022 | ninguna: ya se cumple (030); T022 confirma que sigue en verde |
| CA8 | T014, T015 | T020, T021 |

| Amenaza (TM#) | Tarea(s) de control | Tarea(s) de test |
|---|---|---|
| TM1 | T020 | T014 |
| TM2 | ninguna: fuera de esta spec → spec futura de confirmación del correo (plan, Modelo de amenazas; decisión del usuario, 2026-11-21) | ninguna (fuera) |
| TM3 | ninguna: ya se cumple (el rol se fija en el servidor); la rotura de T013 lo prueba | T013 |
| TM4 | T021 | T014 |

| Cambio del plan (módulo) | Tarea(s) |
|---|---|
| app/Http/Controllers/RegisterController.php | T020 |
| resources/views/auth/register.blade.php | T021 |
| public/css/auth.css | T021 |
| tests/Feature/RegistroPacientesTest.php | T010, T011, T012, T013, T014, T015 |
| tests/Feature/Concerns/ReglasCss.php | T015 |

| Requisito no funcional | Tarea(s) de test o verificación manual (T095) |
|---|---|
| Contraseña con `Password::min(8)` | T013 |
