---
spec: 043-contrasena-distinta
status: approved
constitution_version: 1.3.0
created: 2026-11-03
---

# Plan · 043 La contraseña nueva tiene que ser distinta de la actual

## Enfoque técnico
Una comprobación más en los dos sitios que hoy fijan la contraseña de la propia cuenta, al final de
las que ya hacen. En la recuperación, un `after()` del validador de `restablecer` compara la nueva
con el hash de la cuenta que `cuentaDelEnlace()` ya resolvió por el token. Solo se ejecuta si no hay
otros errores, y devuelve el error en `password` por la misma redirección de siempre, sin tocar el
broker ni el token (D1). En el perfil, la comparación va en `updatePassword` después de
`errorDeContrasenaActual()`: sin la contraseña actual correcta, o con el límite de fallos agotado,
nunca se compara (D2, CA6). Las dos comparan con `Hash::check` contra el hash guardado (D4). Las
cuentas sin contraseña no tienen con qué comparar y siguen igual. `PUT /cliente/perfil/password` pasa a
exigir `role:cliente`, como la 026 y la 028 con sus rutas (D8, CA8). Sin migraciones, rutas,
dependencias ni pantallas nuevas. En la vista del perfil, cada campo de «Cambiar contraseña» queda con
su etiqueta asociada, y el campo con error recibe el foco y queda unido a su mensaje, como en la 041
(D6).

## Constitution Check
| Principio | Resultado | Justificación / ajuste | Cómo se verifica |
|---|---|---|---|
| P1 Spec aprobada antes de código | ✅ | Spec 043 aprobada por el usuario el 2026-11-03 y de nuevo tras `/analyze` r1 (CA8). No tiene pantallas nuevas y por eso no tiene `design.md` (spec, "Diseño": no aplica). | manual: `aidd.py status` antes de `/implement` |
| P2 Test que falla antes y pasa después | ✅ | `ContrasenaDistintaTest` (nuevo) cubre cada criterio. CA1 y CA2 fallan hoy, porque se acepta la misma contraseña. CA8 falla hoy, porque el personal llega a la ruta. Los casos que ya pasan (CA3, CA4, CA5, CA6, CA7, TM3 y TM4) se comprueban rompiendo el control que protegen, con una rotura que de verdad cambia el comportamiento (Estrategia de pruebas). | test: ver Trazabilidad |
| P3 Autorización en servidor | ✅ | Los dos endpoints modificados verifican el rol y el alcance. `PUT /cliente/perfil/password` pasa a exigir `role:cliente` con middleware (D8, CA8), y su alcance es la cuenta de la sesión, sin ningún identificador en la petición. `POST /contrasena/nueva` es pública y su autorización es el token: la comparación solo se hace con la cuenta que el token valida, después de validar el enlace (TM3). `GET /cliente/perfil` y `PUT /cliente/perfil` siguen solo con `auth` porque esta spec no los modifica (RS2.a → spec 015, «Cobertura de riesgos» de la spec). | test: `ContrasenaDistintaTest::test_el_personal_no_puede_cambiar_la_contrasena_por_el_perfil` (admin, dentista y asistente → 403), `…_perfil_con_la_actual_se_rechaza_junto_al_campo` y `…_una_diferencia_minima_se_acepta_en_el_perfil` (cliente permitido), `…_con_un_enlace_de_otra_cuenta_nunca_se_compara` |
| P4 Entrada validada y SQL con bindings | ✅ | La comparación va después de las reglas que ya existen. En la recuperación va dentro de su validador, en un `after()`, tras `required`, `string`, `min:8`, `max:255` y la confirmación. En el perfil va fuera de `validate()`, tras `required`, `confirmed` y `Password::min(8)` y la comprobación de la actual (D2). Sin SQL nuevo. | test: `…_los_errores_de_siempre_van_antes_en_la_recuperacion`, `…_en_el_perfil` · lint: semgrep `p/php` |
| P5 Nada sensible en el repo | ✅ | Los tests usan contraseñas de ejemplo ficticias, como los de la 041. | lint: gitleaks |
| P6 Sin vulnerabilidades críticas/altas | ✅ | Sin cambios en `composer.json`, `composer.lock` ni los archivos de npm. Línea base del 2026-11-03: `composer audit --locked`, 1 media (`symfony/mime` CVE-2026-45070), la misma de la 041, que no bloquea P6. | lint: `composer audit --locked`, `npm audit --audit-level=high` en CI y `/release` |
| P7 Auditoría de datos sensibles | ✅ | Un rechazo no cambia la contraseña y no registra `contrasena_restablecida`: el evento sigue en la transacción del broker, a la que el rechazo no llega (spec, "Auditoría"). El cambio desde el perfil sigue sin evento (lo hará la 021), como antes de esta spec. | test: `…_restablecer_con_la_actual_no_cierra_sesiones_ni_avisa_ni_audita` |
| P8 Correlación y logs limpios | ✅ | No hay logs nuevos. La contraseña rechazada no vuelve a la sesión porque las dos respuestas nuevas (`redirect()->withErrors()` y `back()->withErrors()`) no conservan la entrada: no llaman a `withInput()`, que no respeta `dontFlash` (TM4). | test: `…_la_contrasena_rechazada_no_queda_en_la_sesion` · manual: diff sin `Log::` |
| P9 La interfaz usa solo el sistema de diseño | ✅ | El mensaje usa los estilos de error que ya existen: `.error-msg` en la recuperación y `.field-hint-error` en el perfil. En el perfil solo se añaden atributos (`for` en las etiquetas, `id`, `aria-invalid`, `aria-describedby`, `autofocus`), sin estilos ni clases nuevos. Con ellos, los campos que se tocan cumplen WCAG 1.3.1 y 4.1.2 (A5). | test: `…_perfil_el_error_queda_asociado_al_campo_y_con_el_foco` · manual: revisión del diff de la vista |
| P10 Rutas indexables descubribles | ➖ | No crea ni cambia rutas: `/contrasena/nueva/{token}` sigue con `noindex` (041) y el perfil es privado. | — |

## Cambios por módulo
| Módulo | Cambio | Riesgo |
|---|---|---|
| `app/Http/Controllers/RecuperarContrasenaController.php` | `restablecer`: `$validador->after()` que, sin otros errores y si la cuenta tiene `password_hash`, añade a `password` «Elige una contraseña distinta de la actual» cuando `Hash::check` acierta (D1) | Bajo: si el `after()` se ejecuta con otros errores, sale el error equivocado (CA5 lo prueba) |
| `app/Http/Controllers/PerfilController.php` | `updatePassword`: después de `errorDeContrasenaActual()`, si la cuenta tiene contraseña y la nueva es la actual, `back()->withErrors(['password' => '…'])` sin guardar (D2) | Bajo: hay que mantener el orden, actual primero y comparación después (CA6) |
| `routes/web.php` | `->middleware('role:cliente')` en la ruta `cliente.perfil.password` (D8) | Bajo: el personal recibe 403; no tiene ningún enlace a esa pantalla |
| `resources/views/cliente/perfil.blade.php` | Campos `password_actual`, `password` y `password_confirmation`: `for` en su etiqueta e `id`; con error, `aria-invalid`, `aria-describedby` hacia su mensaje y `autofocus` en el primero (D6) | Bajo: el formulario de datos no debe competir por el foco (un solo `autofocus`, test) |
| `tests/Feature/ContrasenaDistintaTest.php` (nuevo) | Tests de los dos flujos | — |

## Contratos y datos
- **Sin rutas nuevas, migraciones ni datos.** `PUT /cliente/perfil/password` cambia de middleware: `auth` y `role:cliente`. Para el personal pasa de 302 a 403; para el cliente, igual.
- **Respuesta nueva en `POST /contrasena/nueva`:** la redirección de siempre a `contrasena.nueva` con
  `errors.password = «Elige una contraseña distinta de la actual»`. El token sigue en la tabla de
  restablecimientos y la vista ya muestra el error de `password` junto al campo con `autofocus`
  (041 R3).
- **Respuesta nueva en `PUT /cliente/perfil/password`:** `back()` con
  `errors.password = «Elige una contraseña distinta de la actual»` y sin `success`.
- **Orden de los errores:**
  - En la recuperación: primero las reglas de `password` y `password_confirmation` y después la
    comparación.
  - En el perfil: primero las reglas de `validate()`, después el límite y la contraseña actual, y al
    final la comparación.
- **Pantallas y estados:** ninguna pantalla nueva.
  - El estado de error nuevo usa en cada pantalla el componente de error que ya tiene.
  - Accesibilidad en el perfil: cada campo de «Cambiar contraseña» tiene su etiqueta asociada
    (WCAG 1.3.1, 4.1.2); el campo con error lleva `aria-invalid="true"` y `aria-describedby` hacia
    su mensaje (WCAG 3.3.1, 1.3.1), y el foco va al primer campo con error, con un solo `autofocus`
    en la página (como 041 R3).

## Estrategia de pruebas
- **Feature (PHPUnit, `DatabaseTransactions` contra `dentissa_test`).** Un archivo nuevo,
  `ContrasenaDistintaTest`, con cuentas creadas en el propio test, como `PerfilCambioCorreoTest`.
  - Para la recuperación genera el token con `Password::createToken()` y hace el POST a
    `/contrasena/nueva`.
  - Para el perfil hace el PUT a `/cliente/perfil/password` con `actingAs`.
  - Usa `Mail::fake()` para el aviso y consulta la tabla de auditoría para `contrasena_restablecida`.
- **Comprobar cada control rompiéndolo** (obligatorio en los de seguridad, nota en la tarea):
  - CA8: quitar `role:cliente` de la ruta.
  - CA6: comparar antes de la contraseña actual.
  - CA7: quitar `throttle:fijar-contrasena` de la ruta. Los rechazos del test son contraseñas cortas, que pasan por el mismo limitador.
  - TM3: comparar antes de validar el enlace, con la cuenta del parámetro (`Usuario::find($cuenta)`). El test envía el token de A con `cuenta` = B y la contraseña de B; debe ver el enlace inválido y nunca «distinta».
  - TM4: añadir `->withInput()` a cada respuesta nueva.
  - CA5: en la recuperación, quitar la condición «sin otros errores»; en el perfil, comparar antes de `validate()`.
  - CA3: normalizar la nueva a minúsculas, con datos fijos: la actual en minúsculas y la nueva con una mayúscula.
  - CA4: `Hash::check` con un hash `null` devuelve `false`, así que quitar la condición de `password_hash` no cambia nada. La rotura es tratar la falta de contraseña como «igual».
- **Regresión:** la suite completa, en especial `RecuperarContrasena*Test`, `SesionVigenteTest` y
  `PerfilCambioCorreoTest`, que comparte el límite de fallos.

## Modelo de amenazas
| ID | Amenaza (STRIDE) | Categoría OWASP | Componente | Control | Test |
|---|---|---|---|---|---|
| TM1 | Information disclosure: con una sesión ajena, usar el mensaje del perfil para comprobar si se adivinó la contraseña actual | A07:2025 Autenticación | `PerfilController::updatePassword` | La comparación solo se hace después de acreditar la actual; con la actual incorrecta o el límite de 5 fallos por minuto agotado, solo sale el error de la actual (D2) | `…_perfil_sin_la_actual_correcta_no_revela_si_es_igual`, `…_perfil_con_el_limite_agotado_no_revela_si_es_igual` |
| TM2 | Information disclosure: con un enlace ajeno, probar candidatas para averiguar la contraseña anterior | A07:2025 Autenticación | `POST /contrasena/nueva` | Cada intento cuenta para `throttle:fijar-contrasena` (5 por minuto por origen, 041 CA9). El residuo (hasta 300 intentos en los 60 minutos del enlace) está aceptado en la spec, porque quien tiene el enlace ya controla el buzón | `…_los_rechazos_cuentan_para_el_limite_del_enlace` |
| TM3 | Tampering: cambiar `cuenta` en la petición para que se compare con la contraseña de otra cuenta y descubrirla | A01:2025 Control de acceso | `RecuperarContrasenaController::restablecer` | La comparación solo se hace después de validar el enlace y con el `$usuario` que devuelve `cuentaDelEnlace()`. Un token de otra cuenta se rechaza antes como enlace inválido (041 CA10) | `…_con_un_enlace_de_otra_cuenta_nunca_se_compara` |
| TM4 | Information disclosure: la contraseña rechazada queda en la sesión como `old()` o en los logs | A09:2025 Registro y alertas | los dos flujos | Las respuestas nuevas no conservan la entrada (sin `withInput()`) y no hay ningún `Log::` nuevo | `…_la_contrasena_rechazada_no_queda_en_la_sesion` (los dos flujos) |
| TM5 | Elevation of privilege: alguien del personal usa el cambio de contraseña del perfil del paciente | A01:2025 Control de acceso | `PUT /cliente/perfil/password` | `role:cliente` en la ruta (D8) | `…_el_personal_no_puede_cambiar_la_contrasena_por_el_perfil` |

## Trazabilidad
| Criterio de aceptación | Cambio(s) | Test(s) |
|---|---|---|
| CA1: recuperación con la actual → error junto al campo con foco; nada cambia; el enlace sigue | `RecuperarContrasenaController` | `ContrasenaDistintaTest::test_restablecer_con_la_actual_se_rechaza_junto_al_campo`, `…_restablecer_con_la_actual_no_cierra_sesiones_ni_avisa_ni_audita`, `…_tras_el_rechazo_el_enlace_sigue_sirviendo` |
| CA2: perfil con la actual → error junto al campo; no cambia | `PerfilController` | `…_perfil_con_la_actual_se_rechaza_junto_al_campo` |
| CA3: diferencia en los primeros 72 caracteres (una mayúscula o un espacio) → se acepta | los dos controladores | `…_una_diferencia_minima_se_acepta_en_la_recuperacion`, `…_una_diferencia_minima_se_acepta_en_el_perfil` |
| CA4: cuenta sin contraseña → nunca el error | los dos controladores | `…_una_cuenta_sin_contrasena_fija_la_suya_por_el_enlace`, `…_una_cuenta_sin_contrasena_crea_la_suya_en_el_perfil` |
| CA5: los errores de siempre van antes | los dos controladores | `…_los_errores_de_siempre_van_antes_en_la_recuperacion`, `…_los_errores_de_siempre_van_antes_en_el_perfil` |
| CA6 (abuso): en el perfil, solo después de la actual correcta | `PerfilController` | `…_perfil_sin_la_actual_correcta_no_revela_si_es_igual`, `…_perfil_con_el_limite_agotado_no_revela_si_es_igual` |
| CA7 (abuso): los intentos por el enlace cuentan para el límite | `RecuperarContrasenaController` (sin cambios en `routes/web.php`) | `…_los_rechazos_cuentan_para_el_limite_del_enlace` |
| CA8 (abuso): el personal no usa el cambio de contraseña del perfil; el paciente sí | `routes/web.php` | `…_el_personal_no_puede_cambiar_la_contrasena_por_el_perfil`; cliente permitido en los de CA2 y CA3 |
| TM1 | `PerfilController` | los de CA6 |
| TM2 | ruta `contrasena.restablecer` | el de CA7 |
| TM3 | `RecuperarContrasenaController` | `…_con_un_enlace_de_otra_cuenta_nunca_se_compara` |
| TM4 | los dos flujos | `…_la_contrasena_rechazada_no_queda_en_la_sesion` |
| TM5 | `routes/web.php` | `…_el_personal_no_puede_cambiar_la_contrasena_por_el_perfil` |
| RNF: error asociado al campo y foco en el primero con error | `cliente/perfil.blade.php` (la recuperación ya lo cumple, 041 R3) | `…_perfil_el_error_queda_asociado_al_campo_y_con_el_foco` (etiqueta con `for`, `aria-describedby` y un solo `autofocus`); la recuperación, en `…_restablecer_con_la_actual_se_rechaza_junto_al_campo` |

## Observabilidad
- Logs nuevos: ninguno. Un rechazo es un error de validación normal y no deja rastro, como los de longitud.
- Eventos de auditoría: ninguno nuevo. `contrasena_restablecida` solo se registra si la contraseña cambia; CA1 comprueba que un rechazo no lo registra.
- Métricas y alertas: sin cambios.
- Cómo se verifica tras el deploy, sin escribir una contraseña real en ningún sitio:
  - El usuario pide un enlace para su cuenta y escribe la contraseña actual: ve el mensaje.
  - Después fija otra y entra con ella.

## Descubribilidad
No aplica: no crea ni cambia rutas públicas. `/contrasena/nueva/{token}` sigue con `noindex` (041) y el perfil es privado.

## Rollout
- **Feature flag:** no. Es una regla de validación pequeña, sin datos ni migraciones, como en la 028 y
  la 033 (D5). Sigue vigente el interruptor `RECUPERAR_CONTRASENA` de la 041, encendido.
- **Orden de despliegue:** solo código (`desplegar.sh v0.14.1`).
- **Compatibilidad:** total. Un formulario abierto antes del despliegue envía los mismos campos.
- **Rollback:** `desplegar.sh v0.14.0`. No hay nada que revertir en la base de datos.
- **Métricas a vigilar:** respuestas 5xx en `/contrasena/nueva` y `/cliente/perfil/password` en el
  log de nginx.

## Decisiones (→ ADR si son arquitectónicas)
- **D1 · Comparación en la recuperación.**
  - **Opciones:**
    - (a) un `after()` del validador de `restablecer`, con el `$usuario` de `cuentaDelEnlace()`;
    - (b) dentro del callback de `Password::reset`, lanzando una excepción para abortar la
      transacción;
    - (c) una regla de validación propia en `app/Rules/`.
  - **Elegida:** (a).
    - Usa la cuenta ya validada por el token.
    - Solo se ejecuta sin otros errores (CA5).
    - Devuelve el error por la misma redirección que los demás.
    - No toca el broker, que borraría el token si el callback terminara.
  - **Descartadas:**
    - (b) mezcla validación con la transacción del cambio.
    - (c) en el perfil se ejecutaría antes que la comprobación de la actual (CA6). Para dos usos no
      compensa.
- **D2 · Comparación en el perfil.** Va en `updatePassword`, después de `errorDeContrasenaActual()`
  y antes del `update`. No va en `validate()`, porque ahí se ejecutaría antes que la comprobación de
  la actual y revelaría si se adivinó (CA6, TM1). El error de «distinta» no suma un fallo al límite
  de `perfil-contrasena:{id}`: la actual era correcta y el límite ya se limpió.
- **D3 · Texto.** «Elige una contraseña distinta de la actual» (decisión del usuario, 2026-11-03),
  escrito en los dos controladores. Los tests comprueban el texto exacto en los dos flujos.
- **D4 · «Igual» es la que deja entrar.** `Hash::check($nueva, $usuario->password_hash)`, sin
  normalizar, como el inicio de sesión. bcrypt solo distingue los primeros 72 bytes: dos contraseñas
  que solo difieren después cuentan como iguales, igual que al entrar (spec, Decisiones; `/analyze` A7).
  - El middleware `TrimStrings` de Laravel excluye `password` y `password_confirmation`, así que un
    espacio final se conserva y cuenta como diferencia (CA3).
  - Si la cuenta no tiene `password_hash`, no se compara (CA4); además, `Hash::check` con `null`
    devuelve `false`.
  - `TrimStrings` sí recorta `password_actual`: una actual con espacios en los extremos no se puede
    acreditar en el perfil. Es anterior a esta spec (→ roadmap, `/analyze` A8); los tests no usan
    actuales con espacios en los extremos.
- **D5 · Sin feature flag.** El rollback es volver a la v0.14.0, con el precedente de los planes de
  la 028 y la 033.
- **D6 · Accesibilidad del perfil.** Los campos de «Cambiar contraseña» (`password_actual`,
  `password` y `password_confirmation`) reciben `id` y su etiqueta, `for`; con error, `aria-invalid`,
  `aria-describedby` y `autofocus` en el primero. Es el mismo patrón que `contrasena_correo` (041;
  `/analyze` A5). Solo atributos: la deuda de estilos en línea de la
  vista queda como estaba.
- **D8 · Rol de paciente en el cambio de contraseña del perfil** (decisión del usuario, 2026-11-04,
  `/analyze` A1). Se añade `role:cliente` a `PUT /cliente/perfil/password`, ruta a ruta como manda
  `AGENTS.md` y como hicieron la 026 y la 028. El personal no tiene enlace a esa pantalla y cambia la
  suya con la recuperación. `GET` y `PUT /cliente/perfil` no se tocan (RS2.a → spec 015).
- **D7 · Versión v0.14.1** (decisión del usuario, 2026-11-03). Es la versión de parche de la 041, que
  amplía sus criterios de contraseña.

## Impacto en arquitectura
- Ninguno: sin módulos, dependencias ni flujos nuevos. T090 lo deja anotado sin cambiar
  `docs/architecture.md`.

## Riesgos y mitigaciones
- **Una comprobación de hash más por envío válido.** En la recuperación está limitada a 5 por minuto
  por origen; en el perfil solo se llega con la actual correcta. Impacto despreciable.
- **TOCTOU en la recuperación.** Entre la comparación y el `reset` la contraseña podría cambiar desde
  otra sesión. El caso es irrelevante: el peor resultado es aceptar una contraseña que justo acaba
  de dejar de ser la actual.
- **Residuo de TM2 aceptado en la spec.** Con el enlace se pueden probar hasta 5 candidatas por
  minuto durante 60 minutos.
