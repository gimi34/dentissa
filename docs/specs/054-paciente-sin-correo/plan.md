---
spec: 054-paciente-sin-correo
status: approved   # re-aprobado por el usuario el 2026-11-21 tras /analyze r2
constitution_version: 1.3.0
created: 2026-11-20
---

# Plan · 054 Alta de pacientes sin correo

## Enfoque técnico
Un paciente sin correo sigue siendo una fila de `usuarios` (rol `cliente`) más su fila de
`pacientes`, pero con `email` y `password_hash` nulos. Así las citas, los expedientes, el
directorio y la ficha funcionan sin tocarlos, y la cuenta no tiene forma de entrar: el inicio de
sesión exige un correo y la recuperación de contraseña busca por correo. La vinculación con Google
necesita una guarda nueva: si Google no devuelve correo, la búsqueda por correo se convertiría en
`email IS NULL` y encontraría una cuenta sin correo (/analyze A1). Por eso el callback rechaza todo
inicio con Google sin correo antes de buscar o registrar. Es la misma convención que ya usan las cuentas creadas
con Google, que no tienen contraseña (`password_hash` nulo). Cuando se le añade un correo, la
recuperación de la 041 le deja crear su contraseña sin cambiar nada más (CA6).

El único cambio de esquema es que `usuarios.email` admita nulos. El índice único `uq_usuarios_email`
se queda: MariaDB admite varios nulos en un índice único.

El aviso de posible duplicado vive en una clase de dominio nueva,
`App\Pacientes\PosiblesDuplicados`. Primero filtra en SQL por el apellido, con binding y la
intercalación de la columna, que no distingue mayúsculas ni tildes. Después compara en PHP el
nombre normalizado y el teléfono (solo dígitos) o la fecha de nacimiento. `store` la consulta
después de validar. Si encuentra coincidencias y no viene la confirmación, vuelve al formulario con
lo escrito y los ids encontrados; con la confirmación, da el alta. El alta y el correo añadido dejan
su evento de auditoría en modo crítico, dentro de la transacción.

## Constitution Check
| Principio | Resultado | Justificación / ajuste | Cómo se verifica |
|---|---|---|---|
| P1 Spec aprobada antes de código | ✅ | Spec 054 y `design.md` aprobados el 2026-11-20; la spec vuelve a aprobarse tras `/analyze` r1 (Decisiones de A2, A4 y A15) antes de `/implement`, igual que este plan y `tasks.md` | manual: `python .ai/bin/aidd.py status` |
| P2 Test que falla antes y pasa después | ✅ | Todos los CA cambian comportamiento. Hoy el alta y la edición exigen correo (CA1, CA5 y CA6 fallan), no hay aviso de duplicado (CA3, CA9), el directorio y la ficha no muestran «Sin correo» (CA4) y el alta no deja evento (CA10, CA11). CA7 y CA8 son en parte de caracterización (el acceso y los roles ya se niegan); se comprueban rompiendo la condición correspondiente (D10) | test: `PacienteSinCorreoTest` y `PosiblesDuplicadosTest`, en rojo antes |
| P3 Autorización en el servidor | ❌ aceptado: `create`, `store`, `edit` y `update` de `PacientesController` conservan su autorización actual: rol por middleware y `abort(403)` del asistente en la edición, sin alcance por recurso (RS1.a → spec 014). La 054 no empeora ni corrige esa deuda — aprobado por el usuario el 2026-11-20; ampliada a `create`, donde el aviso carga los nombres de las coincidencias, el 2026-11-21 (/analyze A4) | Lo que añade la 054: el enlace del aviso solo apunta a la ruta de ficha del rol de quien registra, y el aviso muestra solo nombres (D7). La 014, al llegar, aplica su Gate `ver-paciente` al enlace y mantiene los nombres como excepción (decisión del usuario, 2026-11-21) | test: `test_el_dentista_el_paciente_y_el_visitante_no_dan_de_alta` (CA8), `test_el_asistente_sigue_sin_poder_editar_un_paciente` (caracterización) |
| P4 Entrada validada en servidor y SQL con bindings | ✅ | Reglas nuevas en `validate()`: `email` `nullable` en el alta y en la edición de quien no tiene correo, y `telefono` `required_without:email`. `PosiblesDuplicados` usa `where(... 'LIKE', ?)` con binding y sin `whereRaw` ni `DB::raw` nuevos. En Blade, solo la salida escapada de Blade (dobles llaves) para los nombres del aviso | test: `test_un_correo_no_valido_o_un_array_se_rechaza`, `test_el_aviso_muestra_los_nombres_escapados` · lint: semgrep `p/php` sobre lo tocado |
| P5 Nada sensible en el repositorio | ✅ | Sin secretos ni datos reales; los tests crean sus propios pacientes | test: gitleaks en `/implement` y en la CI |
| P6 Sin vulnerabilidades críticas o altas | ✅ | Sin dependencias nuevas. Línea base del 2026-11-20 sobre `main` 16a110c: `composer audit --locked --ignore-severity=low --ignore-severity=medium` sale con código 0 (un aviso bajo de `laravel/framework`, ignorado); `auditar-npm.mjs` sin avisos altos ni críticos sin excepción | test: job SCA de la CI · manual: salida de las herramientas en `/review` |
| P7 Auditoría de cambios a datos sensibles | ❌ aceptado: el resto de cambios al editar un paciente (nombre, teléfono, datos clínicos) sigue sin evento (OB1.e → spec 022). La 054 no lo añade — aprobado por el usuario el 2026-11-20 | Lo que sí añade: `paciente_alta` (CA10) y `paciente_correo_anadido` (CA11), críticos dentro de la transacción. No cierra OB1.f: el resto de altas, roles y activaciones sigue en la 021 | test: `test_el_alta_registra_un_evento_sin_datos_personales`, `test_anadir_el_correo_registra_un_cambio_de_cuenta`, `test_si_la_auditoria_falla_no_se_da_el_alta` |
| P8 Correlación y logs sin datos sensibles | ✅ | Ningún `Log::` nuevo. Los eventos llevan el `request_id` que pone `Auditoria`, y en `detalle` solo booleanos; nunca nombre, teléfono ni correo | test: los tests de CA10 y CA11 comprueban que `detalle` solo tiene las claves previstas, booleanas, y que `campos` no lleva valores |
| P9 Solo el sistema de diseño | ✅ | Ayudas con `.field-hint.field-hint-aviso` (spec 051). Un bloque `/* Spec 054 */` en `app.css` con `.aviso-duplicado` y `.marca-sin-correo` (A9), solo con tokens; el icono del aviso es un SVG de línea con `stroke="currentColor"` y su tamaño en los atributos `width`/`height`, como los demás iconos de las vistas (A17); sus botones en una columna por debajo de 900 px dentro del mismo bloque, sin tocar `movil.css`. El componente entra en `system.md` y `system.html` en la primera tarea. Sin `style=""` nuevos | test: `test_el_bloque_054_solo_usa_tokens` (con `ReglasCss`, control positivo), `test_el_aviso_de_duplicado_llega_a_aa` (`--text`/`--amber-bg` 18.31:1, `--amber`/`--white` 6.73:1, `--white`/`--p500` 5.42:1) · manual: las pantallas a 320, 390 y 1440 px contra `design.md` |
| P10 Rutas indexables descubribles | ➖ | No crea ni cambia rutas públicas; el registro público no cambia (spec 002) | test: `PaginasPublicasTest` en verde |

## Cambios por módulo
| Módulo | Cambio | Riesgo |
|---|---|---|
| `database/migrations/2026_11_20_000000_correo_opcional_en_usuarios.php` (nuevo) | `up()`: `ALTER TABLE usuarios MODIFY email VARCHAR(255) NULL`, con `DB::statement`, como la migración de `google_id` con `password_hash` (conserva la intercalación de la tabla y los índices). `down()`: si no hay ninguna fila con `email` nulo, vuelve a `NOT NULL`; si las hay, lanza una excepción que explica que antes hay que dar correo a esas cuentas o restaurar la copia (Rollout) | medio: cambia el esquema de la tabla de cuentas |
| `app/Pacientes/PosiblesDuplicados.php` (nuevo) | `buscar(string $nombre, string $apellido, ?string $telefono, ?string $fechaNacimiento): array{ids: list<string>, total: int}`. Primero filtra en SQL: `pacientes` ⨝ `usuarios`, sin filtrar por rol ni por `activo` (una ficha de paciente de una cuenta del personal también cuenta, A5) y con `usuarios.apellido LIKE ?` (primera palabra del apellido normalizado, seguida de `%`). Después compara en PHP: `clave(nombre)` y `clave(apellido)` iguales (`Str::lower(Str::ascii(Str::squish()))`) y, además, los dígitos del teléfono iguales (si los dos tienen) o la fecha de nacimiento igual (si las dos tienen). Devuelve hasta 3 ids, ordenados por nombre, y el total. Sin teléfono ni fecha en la entrada → ninguna coincidencia | bajo |
| `app/Http/Controllers/PacientesController.php` | **`store`:** `email` pasa a `['nullable', 'email', 'max:255', 'unique:usuarios,email']`; `telefono` a `['required_without:email', 'nullable', 'string', 'max:20']`, con el mensaje de CA2; `confirmar_duplicado` `nullable\|boolean`; `password` con `exclude_without:email` delante de sus reglas de hoy, para que sin correo ni se valide ni se guarde (A6). Tras validar, `PosiblesDuplicados::buscar()`. Si hay coincidencias y no viene la confirmación, `back()->withInput($request->except('password'))->with('posibles_duplicados', $resultado)`: la contraseña escrita nunca llega a la sesión (A2). Si no, la transacción de hoy: con correo, igual que hoy; sin correo, `email` y `password_hash` nulos, se ignora `password` (D4) y no hay `password_generada`. Dentro de la misma transacción, `Auditoria::registrar('paciente_alta', ...)` crítico. Sin correo, el flash `success` es el texto de CA1. **`create`:** si hay `posibles_duplicados` en la sesión, carga los nombres de esos ids y la ruta de su ficha según el rol (D7) y los pasa a la vista. **`update`:** `email` es `required` si el paciente tiene correo y `nullable` si no lo tiene; `telefono` `required_without:email`; si pasa de nulo a un correo, `paciente_correo_anadido` crítico dentro de la transacción. **`edit`:** sin cambios (la vista ya recibe `email`) | medio: el alta y la edición los usan tres roles |
| `resources/views/pacientes/create.blade.php` (alta y edición, que la incluye) | Etiqueta del correo según `design.md` («(opcional)» en el alta y en la edición sin correo; `*` y `required` si el paciente ya tiene correo); ayudas del correo, del teléfono y de la contraseña con `.field-hint.field-hint-aviso` e `id` propios; `aria-describedby` con el error primero y la ayuda después. Aviso `.aviso-duplicado` con `role="alert"` antes de «Datos personales», solo en el alta con `$posiblesDuplicados`: icono SVG con `aria-hidden` (A17), `h2` con `tabindex="-1"` y `autofocus` para que el foco y el lector de pantalla empiecen en el aviso (A14), texto genérico, lista de nombres («y N más»), un enlace «Ver ficha de <nombre>» por coincidencia y el botón `type="submit" name="confirmar_duplicado" value="1"` «Registrar de todos modos». Con el aviso, el **primer** botón de envío del formulario en el DOM es un envío normal sin `confirmar_duplicado`, fuera de la vista y del orden de tabulación (`hidden` o `.envio-por-defecto` del bloque 054, `tabindex="-1"`, `aria-hidden="true"`), para que Enter en un campo nunca confirme el duplicado (D13, A10) | medio: el botón por defecto depende del navegador |
| `resources/views/pacientes/index.blade.php` | En la línea bajo el nombre: con correo, el correo como hoy (solo en el escritorio); sin correo, «Sin correo · sin acceso a la aplicación» con la clase `.marca-sin-correo` (sin `style=""`), visible en todos los anchos y partible | bajo |
| `resources/views/pacientes/show.blade.php` | La fila «Correo» aparece siempre: con correo, como hoy; sin correo, el mismo icono y «Sin correo · sin acceso a la aplicación» | bajo |
| `public/css/app.css` | Bloque `/* Spec 054 */`: `.aviso-duplicado` (fondo `--amber-bg`, borde `var(--border-width) solid var(--amber)`, radio `--radius`, relleno `--space-5`, texto `--text`), su título (`--text-body`, `--weight-strong`), su lista, `.aviso-duplicado .btn-primary` sobre `--p500` (hover `--p600`) con el foco `--focus-ring`, `@media (max-width: 899px)`: acciones en columna a ancho completo, ≥ `--touch-min`; `.marca-sin-correo` (`--text-small`, `--text-muted`); y, si hace falta para D13, `.envio-por-defecto` (fuera de la vista sin literales: `position: absolute`, `inline-size: 0`, `block-size: 0`, `overflow: hidden`) | bajo |
| `app/Http/Controllers/GoogleAuthController.php` | En el callback, si `$googleUser->getEmail()` es nulo o vacío, `volverAlLoginPorFallo()` antes de buscar por correo y antes de guardar `google_registro`: ni vincula ninguna cuenta ni crea una. Así `RegisterController::contactoStore` nunca recibe un correo nulo (A1) | medio: toca el acceso con Google |
| `tests/Feature/MovilPacientesExpedientesTest.php` | Adaptar las aserciones de `aria-describedby` (líneas 372 y 385) al patrón nuevo: sin errores, el atributo solo lleva ids de ayudas (`ayuda-*`), nunca de errores; con error, es una lista que empieza por `error-<campo>`. Sigue fallando si se quita el enlace del error (A3) | bajo |
| `docs/design/system.md`, `docs/design/system.html` | «Aviso de posible duplicado» en «Aviso, diálogo y mensajes», con sus contrastes y una fila en «Decisiones»; versión 1.21.0 (o la siguiente libre) | bajo |
| `tests/Unit/PosiblesDuplicadosTest.php` (nuevo) | ver Estrategia de pruebas | — |
| `tests/Feature/PacienteSinCorreoTest.php` (nuevo) | ver Estrategia de pruebas | — |

Sin cambios, comprobado: `AuthController::login` (exige `email`), `RecuperarContrasenaController` (busca por
correo; ya admite `password_hash` nulo, 043), `PerfilController` (el paciente sin correo no entra),
`UsuarioController` y sus vistas (decisión del usuario: se quedan como están; D9), `CitasController`
y `citas/show` (`@if($cita->email_paciente)`).

## Contratos y datos
- **Esquema:** `usuarios.email` pasa de `varchar(255) NOT NULL` a `varchar(255) NULL`. Índice único
  `uq_usuarios_email` sin cambios. El volcado `database/dentissa.sql` (ruta protegida) no se toca:
  la migración se aplica encima, también en la BD de tests.
- **Cuenta sin correo:** `email` nulo, `password_hash` nulo y rol `cliente`. Al añadirle un correo,
  solo cambia `email`; la contraseña la crea el paciente con la 041.
- **`POST {admin,asistente}/pacientes`:** campos de hoy, más `confirmar_duplicado` (opcional,
  booleano).
  - `email` vacío o solo con espacios: `TrimStrings` y `ConvertEmptyStringsToNull` lo dejan en
    `null`, que equivale a no tener correo.
  - `email` que no es un correo, o un array: error de `email` como hoy.
  - Sin correo y sin teléfono: error en `telefono`, «Sin correo, el teléfono es obligatorio para
    poder contactarle.».
  - Con coincidencias y sin confirmación: `302` atrás, con `old()` de todo salvo `password` y el
    flash `posibles_duplicados` = `{ids: list<uuid> (≤ 3), total: int}`; no se crea nada. El campo
    de contraseña vuelve vacío: al confirmar sin escribirla de nuevo y con correo, se genera y se
    muestra una vez como hoy (decisión del usuario, 2026-11-21, /analyze A2).
  - Sin correo, una contraseña escrita (aunque tenga menos de 6 caracteres) no se valida ni se
    guarda (A6).
  - **Google sin correo:** `GET /auth/google/callback` con un usuario de Google sin correo vuelve al
    inicio de sesión con el mensaje de fallo de siempre, sin sesión, sin `google_registro` y sin
    tocar ninguna cuenta (A1).
  - Éxito con correo: igual que hoy, con `password_generada` si no se escribió contraseña.
  - Éxito sin correo: `302` al listado con `success` = «Paciente registrado sin correo: no tiene
    acceso a la aplicación.».
- **`PUT {admin,dentista}/pacientes/{id}`:** `email` obligatorio si el paciente lo tiene (vacío →
  error con mensaje propio, «El correo es obligatorio: es el acceso del paciente a la aplicación.»,
  porque el proyecto no tiene traducción de los mensajes de Laravel) y
  opcional si no lo tiene; único entre todas las cuentas («Este correo ya está registrado en el
  sistema.»); `telefono` obligatorio sin correo.
- **Auditoría:**
  - `paciente_alta`: resultado `exito`, recurso `pacientes` con el id del paciente nuevo,
    `detalle: {con_acceso: bool, posible_duplicado: bool}`, crítico. `posible_duplicado` es `true`
    si había coincidencias y se confirmó; lo calcula el servidor, no viene de la petición.
  - `paciente_correo_anadido`: resultado `exito`, recurso `pacientes` con su id,
    `campos: ['email']`, `detalle: {acceso_posible: true}`, crítico.
  - El rechazo por rol es el `acceso_denegado` que ya deja el registro central (spec 020).
- **Pantallas y estados** (según `design.md`):

  | Pantalla | Estados |
  |---|---|
  | Alta | reposo (correo opcional y ayudas), error de teléfono (CA2), error de correo, posible duplicado con 1, 3 y más de 3 coincidencias (CA3) |
  | Edición | paciente sin correo (opcional, con su ayuda), paciente con correo (obligatorio, como hoy), error de correo ya registrado (CA6), error de teléfono |
  | Directorio | paciente con correo, paciente sin correo (CA4) |
  | Ficha | fila «Correo» con correo y sin correo (CA4) |
  | Listado tras el alta | `success` de CA1 sin el recuadro de la contraseña generada |

  Accesibilidad medible: ayudas y errores en `aria-describedby` (con el error primero);
  `aria-invalid` en el campo con error; aviso con `role="alert"` y un `h2`; contraste del bloque 054
  ≥ 4.5:1 en texto y ≥ 3:1 en el borde; toque ≥ `--touch-min` en los botones del aviso por debajo
  de 900 px.
- **Qué ve cada rol:** sin cambios de permisos. Dan de alta admin y asistente; editan y añaden el
  correo admin y dentista; los enlaces del aviso usan la ruta de ficha del rol de quien registra.

## Estrategia de pruebas
PHPUnit con `DatabaseTransactions` contra `dentissa_test`, dentro del contenedor, con la
migración aplicada (P2).
- **Unit** `tests/Unit/PosiblesDuplicadosTest.php`, sobre pacientes creados en el test:
  - coincide con nombre y apellido sin distinguir mayúsculas, tildes ni espacios sobrantes, más el
    teléfono con otro formato («55 1234 5678» y «5512345678») o la fecha;
  - no coincide si solo coincide el nombre, si coinciden nombre y teléfono pero el apellido es
    otro, o si la entrada no trae ni teléfono ni fecha;
  - incluye a pacientes desactivados y fichas de paciente de cuentas del personal (A5);
  - devuelve como mucho 3 ids, ordenados, con el total real.
- **Feature** `tests/Feature/PacienteSinCorreoTest.php`, con las cuentas de los roles:
  - **alta (CA1, CA2):**
    - sin correo y con teléfono, como admin y como asistente: cuenta con `email` y `password_hash`
      nulos, ficha creada, sin `password_generada`, mensaje de CA1, y se le puede agendar una cita
      y crear un expediente;
    - correo solo con espacios, igual que sin correo;
    - con correo, como hoy: contraseña generada mostrada;
    - sin correo ni teléfono: error y `old()`, sin filas nuevas;
    - una contraseña escrita en un alta sin correo no se guarda, tampoco si tiene menos de 6
      caracteres (D4, A6);
    - las altas como asistente llevan un dentista asignado, para que sigan valiendo con la regla
      de la 014 (A11).
  - **duplicado (CA3, CA9):**
    - el aviso aparece y no se crea nada; la confirmación crea el paciente con lo escrito;
    - tras el aviso, la sesión no contiene `password` y el campo vuelve vacío; al confirmar con
      correo y sin contraseña, `password_generada` (A2);
    - el primer botón de envío del formulario no lleva `confirmar_duplicado` (A10);
    - el título del aviso lleva `tabindex="-1"` y `autofocus` (A14);
    - sin coincidencias, no hay aviso;
    - con más de 3, «y N más»;
    - el aviso no contiene el teléfono, la fecha, el correo ni datos clínicos del existente;
    - los enlaces usan la ruta del rol;
    - los nombres se muestran escapados.
  - **directorio y ficha (CA4):** el texto «Sin correo · sin acceso a la aplicación» en las dos
    vistas, también en el marcado del móvil, y la búsqueda por nombre o por teléfono lo encuentra.
  - **edición (CA5, CA6):**
    - sin correo, guarda sin pedirlo y exige el teléfono;
    - con correo, dejarlo vacío se rechaza;
    - añadir un correo libre lo guarda y no envía ningún correo (`Mail::fake`);
    - después, el enlace de restablecimiento de la 041 llega y permite entrar;
    - un correo ya usado se rechaza sin cambios.
  - **abuso (CA7, CA8):**
    - con el nombre, el teléfono o una contraseña cualquiera no hay inicio de sesión;
    - la recuperación con un correo inventado no envía nada;
    - Google con un correo nuevo no se vincula con la cuenta sin correo;
    - Google **sin correo** (usuario de Socialite con `getEmail()` nulo) frente a una cuenta sin
      correo: sin sesión, sin `google_id` guardado, sin `google_registro` y sin filas nuevas (A1);
    - dentista contra `/admin/pacientes` y `/asistente/pacientes` → 403 y `acceso_denegado`;
    - dentista contra `POST /dentista/pacientes` → 405 (existe el `GET`) y sin filas nuevas (A13);
    - paciente → 403;
    - visitante → al inicio de sesión.
  - **auditoría (CA10, CA11, TM6, TM7):**
    - `paciente_alta` con `con_acceso` y `posible_duplicado` correctos en los cuatro casos;
    - `paciente_correo_anadido` solo cuando pasa de nulo a un correo, no al cambiar uno que ya
      existía ni al guardar sin correo;
    - con un almacén de auditoría que falla, no se crea el paciente ni se guarda el correo;
    - `detalle` sin texto libre.
  - **CSS y marcado (P9, RNF):**
    - `test_el_bloque_054_solo_usa_tokens`;
    - `test_el_aviso_de_duplicado_llega_a_aa`;
    - etiquetas, `aria-describedby` y `role="alert"`.
- **Rendimiento (RNF < 1 s):** `test_la_busqueda_de_duplicados_hace_una_sola_consulta`. Con
  `DB::enableQueryLog()`, `buscar()` hace una única consulta con binding sobre el apellido y solo
  trae a PHP las filas que comparten la primera palabra del apellido. `usuarios.apellido` no tiene
  índice, así que la consulta recorre la tabla: con el volumen actual (cientos de cuentas) es
  inmediata; la medida real es manual (A12).
- **Existentes que deben seguir en verde:** `MovilPacientesExpedientesTest`, con sus aserciones de
  `aria-describedby` adaptadas (ver «Cambios por módulo», A3; el `type="email"` y el error de
  `no-es-correo` siguen), `CiudadListaCerradaTest`, `HoraClinicaFormulariosTest`,
  `PacientesBotonAltaTest`, `NavegacionInternaTest`, `RecuperarContrasenaTest` y los de Google, y
  la suite completa. Ninguno de ellos da de alta con nombre, apellido y teléfono o fecha de un
  paciente existente; si alguno lo hiciera, recibiría el aviso y se ajusta su dato en la misma
  tarea. Son tests de servidor, sin navegador.

## Modelo de amenazas
| ID | Amenaza (STRIDE) | Categoría OWASP | Componente | Control | Test |
|---|---|---|---|---|---|
| TM1 | Spoofing: entrar a la cuenta de un paciente sin correo con su nombre, su teléfono, una contraseña o un inicio con Google sin correo (quitando el permiso de correo en la autorización) | A07:2025 Authentication Failures | `AuthController::login`, `GoogleAuthController`, recuperación | `email` y `password_hash` nulos: el login exige un correo y `Hash::check` contra nulo falla; la recuperación busca por un correo validado; el callback de Google rechaza todo inicio sin correo antes de buscar o registrar (A1). RS5.a–c siguen pendientes (→ objetivo 2): esta guarda no las cubre | `test_no_hay_forma_de_entrar_a_una_cuenta_sin_correo`, `test_google_sin_correo_ni_vincula_ni_crea_cuenta` (CA7) |
| TM2 | Elevation of privilege: un dentista, un paciente o un visitante da de alta enviando el formulario directamente | A01:2025 Broken Access Control | rutas `{admin,asistente}/pacientes` | `role:admin` / `role:asistente` del grupo; el dentista no tiene ruta de alta | `test_el_dentista_el_paciente_y_el_visitante_no_dan_de_alta` (CA8) |
| TM3 | Information disclosure: usar el aviso de duplicado para sacar datos de otro paciente | A01:2025 Broken Access Control | `PosiblesDuplicados`, aviso en `pacientes/create` | el aviso solo pinta nombres y la ruta de ficha del rol; el texto no dice qué dato coincide | `test_el_aviso_no_muestra_datos_del_existente` (CA9) |
| TM4 | Tampering: añadir a un paciente sin correo el correo de otra cuenta para quedarse con ella | A01:2025 Broken Access Control | `update` | `unique:usuarios,email` ignorando solo la propia cuenta | `test_anadir_un_correo_ya_registrado_se_rechaza` (CA6) |
| TM5 | Tampering: enviar `confirmar_duplicado=1` para saltarse el aviso o falsear la auditoría | A06:2025 Insecure Design | `store` | la confirmación solo evita volver al aviso; `posible_duplicado` del evento lo calcula el servidor con `PosiblesDuplicados` | `test_la_confirmacion_falsa_no_altera_el_evento` |
| TM6 | Repudiation: dar de alta o añadir un correo sin rastro | A09:2025 Security Logging and Alerting Failures | `store`, `update` | eventos críticos dentro de la transacción; si no se escriben, no se guarda nada | `test_si_la_auditoria_falla_no_se_da_el_alta`, `test_si_la_auditoria_falla_no_se_guarda_el_correo` |
| TM7 | Information disclosure: datos personales en el evento o en los logs | A09:2025 Security Logging and Alerting Failures | eventos de CA10 y CA11 | `detalle` solo booleanos y `campos` solo nombres; la validación de `Auditoria` rechaza texto libre | `test_el_alta_registra_un_evento_sin_datos_personales` |
| TM8 | Stored XSS: un nombre con marcado se pinta en el aviso | A05:2025 Injection | aviso en `pacientes/create` | salida escapada de Blade | `test_el_aviso_muestra_los_nombres_escapados` |
| TM9 | Tampering: `email[]` o un correo con espacios o malformado para crear cuentas raras | A05:2025 Injection | `validate()` de `store` y `update` | regla `email` (rechaza arrays y formatos no válidos); espacios → `null` por los middlewares globales | `test_un_correo_no_valido_o_un_array_se_rechaza`, `test_un_correo_solo_con_espacios_cuenta_como_vacio` |
| TM10 | Information disclosure: la contraseña temporal escrita queda en claro en la sesión (archivo sin cifrar) al volver con el aviso y se repinta en el formulario | A04:2025 Cryptographic Failures | `store`, `pacientes/create` | `withInput($request->except('password'))`; el campo vuelve vacío. RS10.a (contraseña generada en el flash) sigue pendiente: la 054 no añade otra contraseña a la sesión | `test_la_contrasena_no_queda_en_la_sesion_con_el_aviso` |

## Trazabilidad
| Criterio de aceptación | Cambio(s) | Test(s) |
|---|---|---|
| CA1: alta sin correo, sin contraseña y mensaje; con correo como hoy | migración, `store`, `pacientes/create` | Feature: `test_el_admin_y_el_asistente_dan_de_alta_sin_correo`, `test_una_contrasena_corta_sin_correo_no_impide_el_alta` (A6), `test_un_correo_solo_con_espacios_cuenta_como_vacio`, `test_al_paciente_sin_correo_se_le_agenda_y_se_le_crea_expediente`, `test_el_alta_con_correo_sigue_como_hoy`, `test_una_contrasena_sin_correo_no_se_guarda` · verificación manual: alta a 320, 390 y 1440 px contra `design.md` |
| CA2: sin correo ni teléfono se rechaza | `store` (`required_without`), `pacientes/create` | Feature: `test_sin_correo_ni_telefono_se_rechaza_y_conserva_lo_escrito` |
| CA3: aviso de posible duplicado y confirmación | `PosiblesDuplicados`, `store`, `create`, `pacientes/create`, `app.css` | Unit: `PosiblesDuplicadosTest` · Feature: `test_el_aviso_aparece_y_no_crea_nada`, `test_registrar_de_todos_modos_crea_el_paciente`, `test_sin_coincidencias_no_hay_aviso`, `test_con_mas_de_tres_coincidencias_dice_cuantas_mas`, `test_la_contrasena_no_queda_en_la_sesion_con_el_aviso`, `test_enter_en_un_campo_no_confirma_el_duplicado` (primer envío sin `confirmar_duplicado`) · verificación manual: Enter en un campo con el aviso visible no crea el paciente, en Chrome y Firefox · verificación manual: aviso a 320, 390 y 1440 px, con lector de pantalla al cargar |
| CA4: «Sin correo» en directorio y ficha; búsqueda | `pacientes/index`, `pacientes/show` | Feature: `test_el_directorio_y_la_ficha_muestran_sin_correo`, `test_la_busqueda_encuentra_al_paciente_sin_correo` · verificación manual: las dos vistas a 320 px |
| CA5: edición sin correo; con correo sigue obligatorio | `update`, `pacientes/create` | Feature: `test_editar_sin_correo_no_lo_pide_y_exige_telefono`, `test_con_correo_dejarlo_vacio_se_rechaza` |
| CA6: añadir correo, acceso con la 041 y correo ya usado | `update` | Feature: `test_anadir_un_correo_lo_guarda_sin_enviar_nada`, `test_tras_anadir_el_correo_el_paciente_crea_su_contrasena_y_entra`, `test_anadir_un_correo_ya_registrado_se_rechaza` |
| CA7 (abuso): sin forma de acceder | `GoogleAuthController` (guarda de correo vacío, A1); login y recuperación sin cambios (D2) | Feature: `test_no_hay_forma_de_entrar_a_una_cuenta_sin_correo` (login, Google con correo, recuperación), `test_google_sin_correo_ni_vincula_ni_crea_cuenta`, comprobados poniendo un `password_hash` conocido y quitando la guarda (D10) |
| CA8 (abuso): dentista, paciente o visitante no dan de alta | rutas existentes | Feature: `test_el_dentista_el_paciente_y_el_visitante_no_dan_de_alta` |
| CA9 (abuso): el aviso no revela datos | `create`, `pacientes/create` | Feature: `test_el_aviso_no_muestra_datos_del_existente`, `test_los_enlaces_del_aviso_usan_la_ruta_del_rol` |
| CA10: evento del alta | `store` | Feature: `test_el_alta_registra_un_evento_sin_datos_personales`, `test_el_alta_denegada_deja_acceso_denegado`, `test_si_la_auditoria_falla_no_se_da_el_alta` |
| CA11: evento del correo añadido | `update` | Feature: `test_anadir_el_correo_registra_un_cambio_de_cuenta`, `test_cambiar_un_correo_existente_no_registra_correo_anadido`, `test_si_la_auditoria_falla_no_se_guarda_el_correo` |
| TM1 | acceso existente y guarda de `GoogleAuthController` | Feature: `test_no_hay_forma_de_entrar_a_una_cuenta_sin_correo`, `test_google_sin_correo_ni_vincula_ni_crea_cuenta` |
| TM2 | rutas existentes | Feature: `test_el_dentista_el_paciente_y_el_visitante_no_dan_de_alta` |
| TM3 | aviso | Feature: `test_el_aviso_no_muestra_datos_del_existente` |
| TM4 | `update` | Feature: `test_anadir_un_correo_ya_registrado_se_rechaza` |
| TM5 | `store` | Feature: `test_la_confirmacion_falsa_no_altera_el_evento` |
| TM6 | `store`, `update` | Feature: `test_si_la_auditoria_falla_no_se_da_el_alta`, `test_si_la_auditoria_falla_no_se_guarda_el_correo` |
| TM7 | eventos | Feature: `test_el_alta_registra_un_evento_sin_datos_personales` |
| TM8 | aviso | Feature: `test_el_aviso_muestra_los_nombres_escapados` |
| TM9 | `validate()` | Feature: `test_un_correo_no_valido_o_un_array_se_rechaza`, `test_un_correo_solo_con_espacios_cuenta_como_vacio` |
| TM10 | `store` (`except('password')`), `pacientes/create` | Feature: `test_la_contrasena_no_queda_en_la_sesion_con_el_aviso` |
| RNF: WCAG 2.1 AA en las pantallas tocadas | vistas, `app.css` | Feature: `test_las_ayudas_y_errores_van_en_aria_describedby`, `test_el_aviso_tiene_role_alert_y_titulo` (con `tabindex="-1"` y `autofocus`, A14), `MovilPacientesExpedientesTest` adaptado (A3), `test_el_aviso_de_duplicado_llega_a_aa`, `test_el_bloque_054_solo_usa_tokens` · verificación manual: foco visible y lector de pantalla en el alta con aviso |
| RNF: aviso en < 1 s al 95 % | `PosiblesDuplicados` (una consulta con binding) | Feature: `test_la_busqueda_de_duplicados_hace_una_sola_consulta` · verificación manual: el alta con aviso en local con el volumen del volcado |

## Observabilidad
- Logs nuevos: ninguno. Si el almacén de auditoría falla, la línea `auditoria.fallo` del canal
  `seguridad`, que ya existe y no lleva valores, y el usuario ve el error genérico de la spec 020.
- Eventos de auditoría:
  - `paciente_alta` → `PacientesController::store`, dentro de la transacción, crítico →
    `test_el_alta_registra_un_evento_sin_datos_personales`;
  - `paciente_correo_anadido` → `PacientesController::update`, dentro de la transacción, crítico →
    `test_anadir_el_correo_registra_un_cambio_de_cuenta`;
  - el alta denegada ya queda como `acceso_denegado` → `test_el_alta_denegada_deja_acceso_denegado`.
- Métricas y alertas: sin cambios; un fallo del almacén ya avisa por correo (spec 020 CA14).
- Cómo se verifica tras el deploy:
  - dar de alta a un paciente de prueba sin correo;
  - en «Auditoría», filtrar por `paciente_alta` y ver su evento con `con_acceso: false` y el
    `request_id` de la petición;
  - comprobar en el directorio que aparece «Sin correo · sin acceso a la aplicación»;
  - desactivar después ese paciente de prueba.

## Descubribilidad
No aplica: no se crean ni cambian rutas públicas. El registro público sigue igual
(`PaginasPublicasTest` en verde).

## Rollout
- **Feature flag:** no aplica (D8). El cambio relaja una validación y añade una columna nulable;
  apagarlo con un flag no deshace las cuentas sin correo ya creadas, y el rollback de abajo basta.
- **Orden de despliegue:** `deploy/desplegar.sh vX.Y.Z`, con la copia de la base previa de siempre,
  hace `migrate --force` (columna nulable, *expand*) con la imagen nueva antes de `up -d app web`.
- **Compatibilidad:** la versión anterior convive con la columna nulable. Mientras no haya cuentas
  sin correo no cambia nada. Con cuentas sin correo, la versión anterior:
  - las muestra en el directorio con la línea vacía y en la ficha sin la fila «Correo» (ya
    comprueba con `@if`);
  - al editarlas en «Pacientes» o en «Usuarios», pide un correo para guardar;
  - el login, Google y la recuperación no las encuentran.
- **Guarda de Google en la misma versión:** la guarda de A1 sale en la misma imagen que el alta sin
  correo, así que no puede existir una cuenta sin correo sin la guarda.
- **Rollback:** `deploy/volver.sh <tag anterior>`, sin deshacer la migración. Si ya hay cuentas sin
  correo, la versión anterior no tiene la guarda de Google (A1). Por eso, antes de `volver.sh`, se
  desactivan esas cuentas (`UPDATE usuarios SET activo = 0 WHERE email IS NULL`, anotando sus ids):
  Google rechaza una cuenta desactivada. Al volver a la 054 se reactivan por id. La versión anterior
  funciona con la columna nulable (Compatibilidad), y `down()` se niega a ejecutarse si ya hay
  cuentas sin correo. Restaurar la copia solo si hiciera falta volver al esquema anterior, y
  perdería lo guardado desde esa copia.
- **Métricas a vigilar:**
  - errores 500 en el alta y la edición de pacientes (logs de la app);
  - eventos `paciente_alta` en la auditoría, con su proporción de `con_acceso: false`;
  - `auditoria.fallo` en el canal `seguridad`.

## Decisiones (→ ADR si son arquitectónicas)
| ID | Decisión | Opciones | Elegida y motivo |
|---|---|---|---|
| D1 | Cómo se representa un paciente sin correo | fila `pacientes` sin `usuarios` · `usuarios` con `email` nulo · correo ficticio | **`usuarios` con `email` nulo**. Sin `usuarios`, `pacientes.usuario_id` (NOT NULL, con FK) y todas las consultas que unen `pacientes` con `usuarios` (directorio, ficha, citas, expedientes, paneles) tendrían que cambiar, y CA6 necesitaría crear la cuenta después. Un correo ficticio rompería la unicidad y los envíos. No es arquitectónica: sigue el modelo de cuentas de hoy (sin ADR) |
| D2 | Contraseña de la cuenta sin correo | aleatoria con hash · nula | **Nula**, como las cuentas de Google sin contraseña (`password_hash` ya es nulable). La 041 y el perfil ya tratan ese caso; una contraseña aleatoria no aporta nada sin correo con el que entrar |
| D3 | Cambio de esquema | `->nullable()->change()` · `DB::statement('ALTER TABLE … MODIFY …')` | **`DB::statement`**, como la migración de `google_id`: el esquema del dominio vive en el volcado y `MODIFY` conserva la intercalación de la tabla sin depender de cómo `change()` lee la columna |
| D4 | Contraseña escrita en un alta sin correo | ignorarla · rechazar el alta | **Ignorarla**, también sin validarla (`exclude_without:email`, /analyze A6). CA1 dice que sin correo no se crea contraseña, y la ayuda del campo lo explica (`design.md`). Rechazarla añadiría un error que la spec no tiene |
| D5 | Cómo se buscan los duplicados | SQL con `REGEXP_REPLACE`/`whereRaw` · todos los pacientes en PHP · filtro por apellido en SQL y comparación en PHP | **Filtro por la primera palabra del apellido con `LIKE ?` (binding; la intercalación `utf8mb4_unicode_ci` ya ignora mayúsculas y tildes) y comparación exacta normalizada en PHP**. Sin `whereRaw` (P4), robusto a espacios internos y a teléfonos con otro formato, y con una sola consulta pequeña (RNF) |
| D6 | Cómo vuelve el formulario con el aviso | renderizar la vista desde el `POST` · `back()->withInput()` con los ids en la sesión | **`back()->withInput($request->except('password'))`** con `posibles_duplicados = {ids, total}`: evita que recargar la página reenvíe el alta y reutiliza `old()` como un error de validación. En la sesión solo van ids y los datos del formulario, nunca la contraseña: el campo vuelve vacío y, al confirmar con correo, se genera una (decisión del usuario, 2026-11-21, /analyze A2) |
| D7 | Enlace «Ver ficha» y nombres del aviso | siempre · según el rol · Gate `ver-paciente` | **La ruta de ficha del rol de quien registra** (`admin.pacientes.show` o `asistente.pacientes.show`): hoy los dos roles ven todas las fichas. La 014 aplicará su Gate `ver-paciente` al enlace y lo quitará si no hay permiso (CA9, «si puedo ver su ficha»). **Los nombres se muestran siempre**, también los de pacientes fuera del alcance del asistente cuando llegue la 014 (solo el nombre, nunca otros datos), y la 014 lo acepta como excepción (decisión del usuario, 2026-11-21, /analyze A4) |
| D8 | Feature flag | sí · no | **No**: ver Rollout |
| D9 | «Usuarios» de la administración (spec 010) | permitir correo vacío · dejarlo como está | **Dejarlo como está** (decisión del usuario, 2026-11-20). Editar ahí a un paciente sin correo exige añadirle uno; se le edita desde «Pacientes». Sus vistas ya ocultan el correo vacío con `@if` |
| D10 | Probar los CA de caracterización (CA7, CA8) | solo en verde · comprobar que el test detecta el fallo | **Comprobar que lo detecta**: en la tarea, el test de CA7 se pone en rojo dando a la cuenta sin correo un `password_hash` conocido y un correo, y quitando la guarda de Google (A1); el de CA8, quitando temporalmente `role:` del grupo; el 403 del asistente, quitando el `abort(403)` de `edit`/`update`; se anota en la tarea (P2) |
| D11 | Orden con otras specs | 054 primero · 014 primero | **054 primero** (decisión del usuario, 2026-11-20). La 014 (`approved`, con plan) toca `store`, `create` y `pacientes/create`: cuando llegue, su `/plan 014 --fix` debe aplicar el Gate `ver-paciente` al enlace del aviso, mantener los nombres como excepción (D7), y ajustar en `PacienteSinCorreoTest` el test del 403 del asistente al editar, que pasará a 404 con su `denyAsNotFound` (/analyze A11). Su regla del asistente (dentista obligatorio) convive con la de teléfono, y las altas de prueba como asistente de la 054 ya llevan un dentista asignado. La 052 (`approved`, sin plan) debe saltarse a los pacientes sin correo sin contarlo como fallo (spec 054, Decisiones) |
| D12 | Excepciones de la constitución | — | P3 y P7 `❌ aceptado` como en la 051 (decisión del usuario, 2026-11-20); P3 ampliada a `create` (decisión del usuario, 2026-11-21, /analyze A4) |
| D13 | Que Enter no confirme el duplicado | sacar «Registrar de todos modos» del formulario · casilla de confirmación · un botón de envío normal como primer botón del formulario | **Botón de envío normal primero en el DOM**, fuera de la vista y del orden de tabulación (/analyze A10). Conserva las dos acciones del aviso tal como las aprobó `design.md`; Enter equivale a «Guardar» y vuelve al aviso sin crear nada. En T022 se comprueba en Chrome y Firefox. Si `hidden` no sirve como botón por defecto en alguno, se usa `.envio-por-defecto` (fuera de la vista sin literales) |
| D14 | Guarda de Google sin correo | en el callback · en `contactoStore` · en los dos | **En el callback**, antes de buscar por correo y de guardar `google_registro`: es el único sitio por el que entra el correo de Google, así que `contactoStore` nunca lo recibe nulo (/analyze A1). Usa el fallo y el mensaje que ya existen (`volverAlLoginPorFallo()`) |

## Impacto en arquitectura
- `docs/architecture.md`:
  - modelo de datos: `usuarios.email` nulable y qué significa (cuenta de paciente sin acceso);
  - módulo `app/Pacientes/` con `PosiblesDuplicados`;
  - la migración en la lista.
- `docs/security.md` → autenticación: las cuentas sin correo no pueden entrar hasta que el
  personal les añade uno y crean su contraseña con la 041.
- `docs/observability.md`: filas de `paciente_alta` y `paciente_correo_anadido` en la tabla de
  eventos (OB1.f sigue pendiente para el resto de altas, → 021).
- `AGENTS.md` → «Mapa del repositorio»: `PosiblesDuplicados` en la fila de `app/Pacientes/`.
- `docs/design/system.md` y `system.html`: el aviso de posible duplicado (primera tarea, no T090).
- Spec extendida (006): su Historial lo anota `/release`.

## Riesgos y mitigaciones
- **Correo añadido equivocado:** el personal teclea mal el correo y otra persona crea la contraseña
  con la 041 y ve la ficha del paciente. Es el mismo riesgo que tiene hoy el alta con correo (no hay
  verificación de correo, `docs/security.md`); la spec deja la verificación fuera de alcance.
  Mitigación: la ayuda del campo explica el efecto, y el evento `paciente_correo_anadido` deja quién
  lo hizo.
- **La 052 y otras funciones futuras que envían correo** pueden asumir que todo paciente tiene
  correo. Mitigación: D11 y la fila de Decisiones de la spec; `/plan 052` lo debe recoger.
- **Tests existentes que dan de alta con datos de un paciente del volcado** recibirían el aviso.
  Mitigación: suite completa en `/implement` y ajuste del dato en la misma tarea.
- **Choques con la 014** en `PacientesController` y `pacientes/create`. Mitigación: D11, y ponerse
  al día con `main` antes de `/implement`.
- **Rollback con cuentas sin correo** reabre A1 en la versión anterior. Mitigación: desactivarlas
  antes de `volver.sh` (Rollout).
- **Botón por defecto (D13)** con comportamiento distinto entre navegadores. Mitigación:
  comprobación en Chrome y Firefox en T022 y test de marcado del primer envío.
- **`down()` que no se puede ejecutar** con cuentas sin correo: es intencionado y queda explicado en
  el mensaje de la excepción y en Rollout.
