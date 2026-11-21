---
spec: 002-registro-pacientes
status: draft
constitution_version: 1.3.0
created: 2026-11-21
---

# Plan · 002 Autorregistro de pacientes

## Enfoque técnico
La spec documenta el registro tal como funciona. Solo cambia CA8: el rechazo de un correo ya registrado deja de decir
que tiene cuenta.

- **CA8:** el mensaje de la regla `unique` de `email` en `RegisterController::register` pasa de «Este correo ya está
  registrado. ¿Quieres iniciar sesión?» a uno neutro, guardado en una constante del controlador. Con ese error, la
  vista del registro muestra bajo el campo dos enlaces: iniciar sesión y, si la recuperación está activa, recuperarla.
  El campo queda asociado a su mensaje (`aria-invalid`, `aria-describedby`), como en el perfil (043). El resto del
  rechazo no cambia: vuelve al paso 1 con los datos escritos salvo la contraseña, y el límite de 5 altas por minuto y
  dirección (030) sigue igual.
- **CA1–CA6** ya se cumplen y reciben tests de caracterización (P2), cada uno comprobado rompiendo el código que
  protege. CA7 ya tiene test (`LimiteIntentosTest`).

No hay migraciones, dependencias ni rutas nuevas. La única pantalla que cambia es el estado de error del registro.

## Constitution Check
| Principio | Resultado | Justificación / ajuste | Cómo se verifica |
|---|---|---|---|
| P1 Spec aprobada antes de código | ✅ | Spec 002 aprobada por el usuario el 2026-11-21, con CA8 reescrito en `/plan`. No tiene sección «Diseño»; el cambio de pantalla es un mensaje y dos enlaces en el estado de error, con las clases y los tokens que ya existen (D3), así que no necesita `design.md`. | manual: `aidd.py status` antes de `/implement` |
| P2 Test que falla antes y pasa después | ✅ | CA8 cambia comportamiento: su test falla hoy (sale «Este correo ya está registrado»). CA1–CA6 son caracterización, cada una comprobada rompiendo el código (Estrategia de pruebas). CA7 ya tiene test. | test: ver Trazabilidad |
| P3 Autorización en servidor | ✅ | El registro es público por diseño y el rol se fija en el servidor (CA5; su test lo caracteriza con `rol=admin` en el formulario). No cambia ninguna autorización. | test: `RegistroPacientesTest::test_el_rol_es_siempre_cliente` |
| P4 Entrada validada y SQL con bindings | ✅ | Ninguna entrada nueva; solo cambia el texto de un mensaje de `validate()`. Sin SQL nuevo. | lint: semgrep `p/php` sobre los archivos tocados |
| P5 Nada sensible en el repo | ✅ | Los tests usan datos y contraseñas ficticios. | lint: gitleaks sobre el diff |
| P6 Sin vulnerabilidades críticas/altas | ✅ | Sin cambios de dependencias. Línea base del 2026-11-21 (`/release` 011): `composer audit --locked` sin altas ni críticas (1 baja de `laravel/framework`); `auditar-npm.mjs` sin avisos; la 053 fusionada después no cambió dependencias. | lint: `composer audit --locked` y `auditar-npm.mjs` en CI y `/release` |
| P7 Auditoría de datos sensibles | ❌ aceptado: el alta de cuentas (formulario y Google) sigue sin evento de auditoría. La spec no tiene criterios de «Auditoría» y añadirlo queda para la 021 (altas y cambios de cuentas, `docs/observability.md`), como en la 051 — aprobado por el usuario el 2026-11-21 | El rechazo de CA8 no es un evento obligatorio. | manual: diff sin eventos nuevos |
| P8 Correlación y logs limpios | ✅ | No añade ningún `Log::`. El correo rechazado no se escribe en ningún log ni en la sesión más allá del `old()` de siempre. | manual: diff sin `Log::` |
| P9 La interfaz usa solo el sistema de diseño | ✅ | El mensaje usa `.error-msg` y los enlaces dos reglas nuevas de `auth.css` sin valores literales: `color: inherit` con subrayado, y foco visible con `--focus-ring`, `--focus-ring-width` y `--focus-ring-offset`, como `.enlace-inicio` (`/analyze` A2). El texto es `--red` sobre el fondo del formulario (`--white`); el test calcula que el contraste es ≥ 4,5:1 con los valores de los tokens. Los enlaces se distinguen por el subrayado, no solo por el color. Sin `style=""` nuevos. | test: `RegistroPacientesTest::test_los_enlaces_del_rechazo_solo_usan_tokens`, `…_los_enlaces_del_rechazo_tienen_foco_y_contraste`, `…_el_error_del_correo_queda_asociado_al_campo` |
| P10 Rutas indexables descubribles | ✅ | `/registro` es indexable, pero su `GET` no cambia: el título y la indexación son los de siempre. Solo cambia la página que se ve tras un rechazo. Deuda previa, sin cambios: `/registro` no tiene descripción ni canónica (pendientes en `docs/seo.md`, `/analyze` A6). | test: `PaginasPublicasTest` en verde (sin cambios) |

## Cambios por módulo
| Módulo | Cambio | Riesgo |
|---|---|---|
| `app/Http/Controllers/RegisterController.php` | Constante pública `CORREO_NO_DISPONIBLE` con el mensaje neutro; `'email.unique' => self::CORREO_NO_DISPONIBLE` en `register` (D1) | Bajo: solo cambia un texto |
| `resources/views/auth/register.blade.php` | Bajo el campo `email`, si su error es `CORREO_NO_DISPONIBLE`, dos enlaces: «iniciar sesión» (`route('login')`) y, con `config('acceso.recuperar_contrasena')`, «recuperar tu contraseña» (`route('contrasena.solicitud')`). El campo lleva `aria-invalid` y `aria-describedby` hacia su mensaje cuando hay error (D2, D3) | Bajo: estado de error del paso 1 |
| `public/css/auth.css` | `.page-register .error-msg a`: `color: inherit; text-decoration: underline;` y `.page-register .error-msg a:focus-visible` con `--focus-ring*` (D3) | Bajo |
| `tests/Feature/RegistroPacientesTest.php` (nuevo) | Tests de CA1–CA6 (caracterización) y CA8 | — |

## Contratos y datos
- **Sin rutas nuevas, migraciones ni datos nuevos.**
- **`POST /registro` con un correo ya registrado:** sigue siendo un rechazo de validación (redirección al formulario
  con el error en `email`, CA3), pero el texto pasa a «No pudimos crear la cuenta con estos datos. Si ya tienes una,
  inicia sesión o recupera tu contraseña.». Vuelven los datos escritos salvo `password` y `password_confirmation`
  (`dontFlash` de siempre); la casilla `terminos` vuelve sin marcar, como hoy (spec CA8, decisión del usuario,
  2026-11-21). Ningún test existente aserta el texto anterior.
- **`POST /registro` en el resto de casos:** sin cambios (CA1–CA6, CA7).
- **`POST /registro/contacto` (Google):** sin cambios. El correo viene de Google; si ya tiene cuenta, la vuelta de
  Google la vincula antes de llegar aquí (spec 018), así que este flujo no tiene rechazo por correo repetido.
- **Pantalla «Crear cuenta», estado de error del paso 1:** el mensaje aparece en el aviso general y bajo el campo;
  bajo el campo, con los dos enlaces. Sin enlaces para el resto de errores del correo (vacío, formato).

## Estrategia de pruebas
PHPUnit Feature con `DatabaseTransactions` contra la base de tests, en `tests/Feature/RegistroPacientesTest.php`.

- **CA8 (cambia):** registro con el correo de una cuenta existente (también en mayúsculas): error en `email` con el
  texto neutro exacto, la página no contiene «ya está registrado» ni «ya existe», los dos enlaces están bajo el campo
  (el de recuperar solo con `acceso.recuperar_contrasena` activo), vuelven el nombre, el apellido, el teléfono, la fecha de nacimiento, el
  género, la dirección y la ciudad, no vuelve la contraseña y la casilla de términos sale sin marcar, y no se crea
  ninguna cuenta. Falla hoy.
- **Accesibilidad de CA8:** con error en el correo, `#email` lleva `aria-invalid="true"` y `aria-describedby` hacia el
  `id` del mensaje; el mensaje y los enlaces solo usan clases y las reglas nuevas de `auth.css`, sin `style=""` ni
  colores literales (test sobre el HTML y el CSS, patrón de `TerminosPrivacidadTest::…_solo_usa_tokens`). Con el
  trait `ReglasCss` sobre `css/auth.css`: la regla `:focus-visible` de los enlaces usa los tres tokens de foco y el
  contraste de `--red` sobre `--white` es ≥ 4,5:1 (`/analyze` A2).
- **Caracterización (P2):** cada una se comprueba rompiendo un momento lo que protege y se anota en la tarea:
  - CA1: el alta válida crea `usuarios` (rol `cliente`, contraseña cifrada) y `pacientes`, entra con sesión y redirige
    al panel; si el alta de la ficha falla, no queda el usuario (transacción).
  - CA2: con los dentistas del escenario como únicos activos (los del volcado se desactivan dentro de la
    transacción), se asigna el activo con menos pacientes; a igualdad, el más antiguo; un inactivo nunca; sin ningún
    dentista activo, el alta se completa y la ficha queda sin dentista. Los `id` y los `creado_en` de los dentistas se
    fijan para que cada rotura (conteo, antigüedad, activo) haga fallar un test de forma determinista: sin el orden
    por antigüedad, MariaDB resuelve el empate en un orden no definido (`/analyze` A1).
  - CA3: un correo ya registrado no crea cuenta y deja el error en `email` (mismo test que CA8, con la aserción de
    rechazo).
  - CA4: la vuelta de Google sin cuenta (sesión `google_registro`) y `POST /registro/contacto` crean el usuario sin
    contraseña, con su `google_id`, y su ficha, y entran con sesión.
  - CA5: un `rol=admin` enviado en el formulario no cambia nada: la cuenta es `cliente`.
  - CA6: una contraseña de 7 caracteres se rechaza y no crea cuenta.
- **Ya cubiertos:** CA7 (`LimiteIntentosTest::test_el_alta_admite_cinco_intentos_por_minuto_y_direccion`), la casilla
  de términos (`TerminosPrivacidadTest`), la ciudad (`CiudadListaCerradaTest`), la fecha (`HoraClinicaFormulariosTest`)
  y las salidas (`NavegacionSalidasTest`). Siguen en verde.
- Suite completa al terminar, Pint sobre los PHP tocados, gitleaks y semgrep `p/php` sobre el diff.

## Modelo de amenazas
| ID | Amenaza (STRIDE) | Categoría OWASP | Componente | Control | Test |
|---|---|---|---|---|---|
| TM1 | Information disclosure: un atacante usa el registro para saber qué correos tienen cuenta leyendo el mensaje | A07:2025 Fallos de autenticación | `POST /registro` | Mensaje neutro para el correo ya registrado (D1); límite de 5 altas por minuto y dirección (030) | `test_un_correo_registrado_se_rechaza_sin_decir_que_existe`; `LimiteIntentosTest::test_el_alta_admite_cinco_intentos_por_minuto_y_direccion` |
| TM2 | Information disclosure residual: el alta con un correo nuevo entra al panel y con uno registrado no, así que la diferencia sigue visible | A07:2025 | `POST /registro` | **Fuera de esta spec**: RS13.c → spec futura de confirmación del correo (decisión del usuario, 2026-11-21; spec, Fuera de alcance y «Cobertura de riesgos») | — (fuera) |
| TM3 | Elevation of privilege: un visitante envía `rol=admin` u otros campos privilegiados en el registro | A01:2025 Control de acceso | `POST /registro` | El rol y `activo` se fijan en el servidor; los datos salen de `validate()` | `test_el_rol_es_siempre_cliente` (caracterización) |
| TM4 | Tampering: un enlace del rechazo apunta a una dirección construida con lo que envió el usuario | A01:2025 | vista del registro | Los enlaces salen de `route()` sin parámetros del usuario; el mensaje es una constante que Blade escapa | `test_un_correo_registrado_se_rechaza_sin_decir_que_existe` (comprueba los `href` exactos) |

## Trazabilidad
| Criterio de aceptación | Cambio(s) | Test(s) |
|---|---|---|
| CA1: el alta crea usuario y paciente en una transacción e inicia sesión | ninguno (ya se cumple) | `RegistroPacientesTest::test_el_alta_crea_la_cuenta_y_la_ficha_y_entra`, `…_si_falla_la_ficha_no_queda_la_cuenta` (caracterización) |
| CA2: se asigna el dentista activo con menos pacientes, a igualdad el más antiguo; nunca un inactivo; sin activos, sin dentista | ninguno (ya se cumple) | `RegistroPacientesTest::test_se_asigna_el_dentista_activo_con_menos_pacientes`, `…_a_igualdad_el_dentista_mas_antiguo`, `…_sin_dentistas_activos_el_alta_queda_sin_dentista` (caracterización) |
| CA3: un correo ya registrado se rechaza con un error de validación | ninguno (ya se cumple) | `RegistroPacientesTest::test_un_correo_registrado_se_rechaza_sin_decir_que_existe` (la parte del rechazo es caracterización) |
| CA4: el registro con Google crea el usuario sin contraseña y su paciente | ninguno (ya se cumple) | `RegistroPacientesTest::test_el_registro_con_google_crea_la_cuenta_sin_contrasena` (caracterización) |
| CA5: el rol es siempre `cliente` | ninguno (ya se cumple) | `RegistroPacientesTest::test_el_rol_es_siempre_cliente` (caracterización) |
| CA6: una contraseña de menos de 8 caracteres se rechaza | ninguno (ya se cumple) | `RegistroPacientesTest::test_una_contrasena_corta_se_rechaza` (caracterización) |
| CA7 (abuso): las altas se limitan por dirección | ninguno (030) | `LimiteIntentosTest::test_el_alta_admite_cinco_intentos_por_minuto_y_direccion` (existente) |
| CA8 (abuso): el rechazo no dice que el correo tenga cuenta, con enlaces y los datos conservados | `RegisterController`, `auth/register.blade.php`, `auth.css` | `RegistroPacientesTest::test_un_correo_registrado_se_rechaza_sin_decir_que_existe`, `…_el_error_del_correo_queda_asociado_al_campo`, `…_los_enlaces_del_rechazo_solo_usan_tokens`, `…_los_enlaces_del_rechazo_tienen_foco_y_contraste`, `…_otros_errores_del_correo_no_llevan_los_enlaces` · verificación manual: estado de error del paso 1 a 390 y 1440 px, contra la pantalla actual del registro, y el foco de los dos enlaces con el teclado |
| RNF: contraseña con `Password::min(8)` | ninguno | `RegistroPacientesTest::test_una_contrasena_corta_se_rechaza` |
| TM1 | `RegisterController` | `test_un_correo_registrado_se_rechaza_sin_decir_que_existe` |
| TM2 | fuera → spec futura | — |
| TM3 | ninguno (ya se cumple) | `test_el_rol_es_siempre_cliente` |
| TM4 | `auth/register.blade.php` | `test_un_correo_registrado_se_rechaza_sin_decir_que_existe` |

## Observabilidad
- Logs nuevos: ninguno.
- Eventos de auditoría: ninguno (excepción P7, decisión del usuario del 2026-11-21 → spec 021). El rechazo por correo
  repetido no es un evento obligatorio.
- Métricas y alertas: sin cambios. Un rechazo de validación no cuenta como fallo de acceso ni como 5xx.
- Cómo se verifica tras el deploy: registrarse con el correo de una cuenta de prueba ya existente muestra el mensaje
  neutro con los dos enlaces y no crea nada.

## Descubribilidad
| Ruta | Cambio | Test |
|---|---|---|
| `/registro` | Ninguno en el `GET` (título, descripción, canónica e indexación iguales); solo cambia el estado de error tras un `POST` rechazado | `PaginasPublicasTest` en verde, sin cambios |

## Rollout
- Feature flag: no aplica. Es un texto y dos enlaces en un estado de error, sin riesgo para el alta; volver atrás es
  el rollback.
- Orden de despliegue: `deploy/desplegar.sh vX.Y.Z` sin migraciones.
- Compatibilidad: total; no cambia datos ni sesiones.
- Rollback: `bash deploy/volver.sh <tag anterior>`, sin la copia.
- Métricas a vigilar tras el deploy: 5xx en `/registro` (aviso automático `dentissa-vigilar`) y la comprobación
  manual de Observabilidad.

## Decisiones (→ ADR si son arquitectónicas)
| ID | Pregunta | Opciones | Decisión |
|---|---|---|---|
| D1 | ¿Cómo deja de revelar el registro que un correo tiene cuenta? | mensaje neutro · confirmar el correo antes de crear la cuenta · excepción sin código | **Mensaje neutro** con el límite de la 030; confirmar el correo queda para una spec futura (TM2) (decisión del usuario, 2026-11-21). El texto vive en una constante del controlador, que la vista y el test leen |
| D2 | ¿Dónde van los enlaces de iniciar sesión y recuperar la contraseña? | bajo el campo, solo con el mensaje neutro · en el aviso general · con cualquier error del correo | **Bajo el campo, solo con el mensaje neutro**: es donde el usuario mira, y con un correo vacío o mal escrito no tienen sentido. El de recuperar, solo si la recuperación está activa (`acceso.recuperar_contrasena`), como en el inicio de sesión |
| D3 | ¿Cómo se ven los enlaces dentro del mensaje de error? | regla nueva en `auth.css` que hereda el color · clase de otro formulario | **Dos reglas nuevas**: `.page-register .error-msg a` con `color: inherit` y subrayado, que toma el color de `--red` y distingue el enlace sin depender del color (P9, WCAG 1.4.1); y `.page-register .error-msg a:focus-visible` con el anillo de `--focus-ring*`, porque `auth.css` solo lo define para otros elementos (WCAG 2.4.7, `/analyze` A2) |
| D4 | ¿Se asocia el error del correo a su campo? | sí, `aria-invalid` y `aria-describedby` · no | **Sí**, solo en el campo `email`, que es el que cambia; los demás campos del registro siguen como están (deuda previa, sin cambios) |
| D5 | ¿Se avisa por correo al dueño cuando alguien intenta registrarse con su dirección? | sí · no | **No** (decisión del usuario, 2026-11-21; spec, Fuera de alcance) |
| D6 | Auditoría del alta | evento en esta spec · excepción P7 | **Excepción P7** → spec 021, como en la 051 (decisión del usuario, 2026-11-21) |

Ninguna decisión es arquitectónica: sin ADR.

## Impacto en arquitectura
- `docs/architecture.md`: sin cambios de estructura; si nombra el mensaje del registro, se actualiza. Lo comprueba
  T090.
- `docs/security.md`: esta spec parte RS13.b y crea RS13.c (`/analyze` A3; spec, «Cobertura de riesgos»). En
  `/release`, RS13.b pasa a mitigada (mensaje neutro, spec 002 CA8) y RS13.c sigue pendiente (→ objetivo 2, spec
  futura de confirmación del correo); RS13.a sigue mitigada (030). RS13 no queda mitigado del todo.
- `docs/roadmap.md`: el objetivo 2 ya cita RS13.c. En `/release`, la excepción P7 de la 002 como deuda (→ 021) y la
  confirmación del correo como pendiente.

## Riesgos y mitigaciones
| Riesgo | Mitigación |
|---|---|
| El mensaje neutro confunde a quien ya tenía cuenta y no lo recuerda | El mensaje y los enlaces le llevan a iniciar sesión o a recuperar la contraseña |
| La vista compara el error con la constante y un cambio de texto la rompe en silencio | El test lee la misma constante y comprueba los enlaces |
| Un test de caracterización pasa por accidente (p. ej. CA2 con los dentistas del volcado) | CA2 desactiva dentro de la transacción los dentistas que no son del escenario y fija sus `id` y `creado_en`; cada caracterización se comprueba rompiendo el código |
| Otras specs en curso: la 054 toca `GoogleAuthController` y podría hacer opcional el correo; la 014 usa `pacientes.dentista_id`, que viene de la asignación de CA2 | Sin archivos en común. Si la 054 deja el correo opcional, se revisan la regla `unique` y CA8; la asignación de CA2 no cambia (`/analyze` A15) |
