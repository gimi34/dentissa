---
spec: 054-paciente-sin-correo
verdict: changes_requested
round: 1
date: 2026-11-21
base: 347b3f7
head: 1985598
human_signoff: no requerido
---

# Review · 054 Alta de pacientes sin correo

## Resumen
**Cambios pedidos.** La implementación cumple los once criterios y no tiene bloqueantes. Pasan la
suite completa, Pint y los cuatro escáneres de seguridad. El usuario decidió corregir antes de
liberar cuatro de los cinco importantes:
- dos huecos de tests (R1, R2);
- el correo añadido desde «Usuarios» sin evento (R3);
- una escalada anterior a la 054 en la edición de pacientes (R4).

También se corrigen dos menores (R7, R15). R5 y los otros trece menores quedan aceptados como
deuda.

La revisión la hicieron cuatro subagentes independientes: spec (`critical`), constitución y calidad
(`standard`), seguridad (`critical`) y accesibilidad (`standard`). Partieron del paquete de
`aidd.py review-pack` (rango `347b3f7..1985598`: 11 archivos de código, 79 KB). Cada hallazgo
importante se comprobó contra el código.

Conteo: bloqueante 0 · importante 5 · menor 15

## Verificación automática
| Comando | Resultado |
|---|---|
| `php artisan test` (suite completa, contenedor propio con el código del worktree y base `dentissa_054_test` con la migración aplicada) | ✅ 1579 tests, 0 fallos (línea base de T003: 1522, 0 fallos; +57 de la 054) |
| `vendor/bin/pint --test` sobre los 7 PHP tocados | ✅ |
| `python .ai/bin/aidd.py validate docs/specs/054-paciente-sin-correo` | ✅ 0 errores |
| Tests nuevos de vista dos veces | ➖ son de servidor (HTML renderizado), sin JavaScript ni esperas asíncronas |
| Verificación manual de T022–T024 | ✅ confirmada por el usuario el 2026-11-21 (Chrome y Firefox; Safari no cubierto) |

## Criterios de aceptación
| CA | Test | Estado | Nota |
|---|---|---|---|
| CA1 | `PacienteSinCorreoTest::test_el_admin_y_el_asistente_dan_de_alta_sin_correo`, `…solo_con_espacios…`, `…se_le_agenda_y_se_le_crea_expediente`, `…con_correo_sigue_como_hoy`, `…contrasena_corta_sin_correo…` | ⚠ | Cumple; el alta con correo y contraseña escrita no tiene test (R2) |
| CA2 | `…sin_correo_ni_telefono_se_rechaza_y_conserva_lo_escrito` | ✅ | |
| CA3 | `PosiblesDuplicadosTest`, `…el_aviso_aparece_y_no_crea_nada`, `…registrar_de_todos_modos…`, `…mas_de_tres…`, `…enter_en_un_campo…` | ⚠ | Cumple; la guarda del teléfono vacío no tiene test (R1) |
| CA4 | `…el_directorio_y_la_ficha_muestran_sin_correo`, `…la_busqueda_encuentra…` | ✅ | |
| CA5 | `…editar_sin_correo_no_lo_pide_y_exige_telefono`, `…con_correo_dejarlo_vacio_se_rechaza` | ✅ | |
| CA6 | `…anadir_un_correo_lo_guarda_sin_enviar_nada`, `…tras_anadir_el_correo…entra`, `…ya_registrado_se_rechaza` | ⚠ | Cumple en «Pacientes»; la cuenta de personal con ficha admite cambio de correo (R4, anterior) |
| CA7 | `…no_hay_forma_de_entrar…`, `…google_sin_correo_ni_vincula_ni_crea_cuenta` (3 casos) | ✅ | La parte de login y recuperación es estructural (R6); el registro tras Google no se defiende de una sesión antigua (R15) |
| CA8 | `…el_dentista_el_paciente_y_el_visitante_no_dan_de_alta` | ✅ | Comprobado rompiendo los roles (T025) |
| CA9 | `…el_aviso_no_muestra_datos_del_existente`, `…enlaces…ruta_del_rol`, `…nombres_escapados` | ✅ | |
| CA10 | `…el_alta_registra_un_evento_sin_datos_personales` (4 casos), `…confirmacion_falsa…`, `…alta_denegada…`, `…si_la_auditoria_falla_no_se_da_el_alta` | ✅ | |
| CA11 | `…anadir_el_correo_registra_un_cambio_de_cuenta`, `…cambiar_un_correo_existente…`, `…si_la_auditoria_falla_no_se_guarda_el_correo` | ⚠ | Cumple en «Pacientes»; desde «Usuarios» no hay evento (R3) |

## Conformidad con el plan
- Cambios fuera del plan: ninguno (`scope.md`: 0 archivos sin tarea).
- Partes del plan sin implementar: ninguna. T001 y T003 no tienen diff de código porque son de
  documentación (sistema de diseño) y de línea base. `POST /dentista/pacientes` responde 404, no
  405 como decía el plan (nota de T015).

## Constitution Check (sobre el código)
| Principio | Resultado | Evidencia |
|---|---|---|
| P1 | ✅ | Spec, diseño, plan y tareas aprobados; `/analyze` r2 `pass` |
| P2 | ⚠ | Tests en rojo antes de cada implementación (notas de T010–T017); dos huecos: R1 y R2 |
| P3 | ❌ aceptado | Excepción del plan (`create`, `store`, `edit`, `update`), sin empeorar; R4 es anterior y se corrige aquí |
| P4 | ✅ | `LIKE ?` con binding y escapado (`PosiblesDuplicados.php:38`), `exclude_without`, `email` con `max:255`, `{{ }}` en el aviso; semgrep sin hallazgos |
| P5 | ✅ | gitleaks sin hallazgos sobre todos los commits de la rama |
| P6 | ✅ | Sin dependencias nuevas; composer audit, npm y Trivy sin altas ni críticas |
| P7 | ❌ aceptado | OB1.e sigue fuera; los dos eventos de la 054 se emiten y se prueban; R3 cierra el camino de «Usuarios» |
| P8 | ✅ | Ningún `Log::` nuevo; `detalle` solo con booleanos; la contraseña no entra en la sesión |
| P9 | ✅ | Bloque `/* Spec 054 */` solo con tokens (test con control positivo); sin `style=""` nuevos; contrastes medidos por test (R9: faltan pares secundarios) |
| P10 | ➖ | Sin rutas públicas |

## Seguridad
| Herramienta | Resultado |
|---|---|
| gitleaks v8.21.2 (secretos) | ✅ 0 hallazgos sobre el parche de todos los commits de la rama. Los 5 `generic-api-key` de contraseñas ficticias de test se cambiaron por valores de baja entropía en T092 |
| semgrep 1.99.0 `p/php` (SAST) | ✅ 0 hallazgos en los 7 PHP tocados |
| `composer audit --locked` y `auditar-npm.mjs` (SCA) | ✅ crít: 0 · alta: 0 (un aviso bajo de `laravel/framework`, ignorado por la política) |
| Trivy 0.58.1 `fs --severity HIGH,CRITICAL` (contenedores y dependencias) | ✅ código 0, sin hallazgos |

| Tema OWASP (2025) | Resultado | Evidencia |
|---|---|---|
| A01 Control de acceso | ⚠ | Alta solo en `role:admin`/`role:asistente`; R4 (anterior): cambio de correo de cuentas del personal desde «Pacientes» |
| A04 Fallos criptográficos | ✅ | `password_hash` nulo sin correo; `except('password')` en el aviso (TM10) |
| A05 Inyección | ✅ | Binding, escapado de `LIKE`, salida escapada |
| A06 Diseño inseguro | ⚠ | TM1–TM10 con control y test; R3 y R15 son caminos fuera del modelo |
| A07 Fallos de autenticación | ✅ | Login, recuperación y Google (guarda antes de toda búsqueda) |
| A08 Integridad | ✅ | CSRF; `posible_duplicado` calculado por el servidor |
| A09 Registro | ⚠ | Eventos críticos dentro de la transacción; R3 |
| A10 Errores | ✅ | 503 genérico si falla la auditoría; `down()` se niega con cuentas sin correo |
| A02, A03 (configuración, cadena de suministro) | ➖ | Sin cambios de configuración ni dependencias |

## Observabilidad
- Eventos de auditoría emitidos y probados: sí. `paciente_alta` y `paciente_correo_anadido`, críticos
  y dentro de la transacción, con tests de contenido y de fallo del almacén. Falta el camino de
  «Usuarios» (R3).
- Datos sensibles en logs del código tocado: ninguno. No hay logs nuevos y el `detalle` solo lleva
  booleanos.
- `request_id` en los logs nuevos: no hay logs nuevos; los eventos lo llevan por `Auditoria`.

## Hallazgos
| ID | Severidad | Archivo:línea | Hallazgo | Sugerencia |
|---|---|---|---|---|
| R1 | importante | `tests/Unit/PosiblesDuplicadosTest.php:58` (protege `app/Pacientes/PosiblesDuplicados.php:48`) | `test_un_telefono_guardado_vacio_no_coincide_con_otro_vacio` sale por el retorno temprano (sin dígitos ni fecha) y nunca llega al filtro: quitar la guarda `$digitos !== ''` no lo hace fallar. Sin ella, un homónimo sin teléfono sería duplicado aunque la fecha difiera | Caso con fecha distinta en la entrada, sin teléfono, frente a un existente con teléfono nulo: `total` 0; comprobado rompiendo la guarda |
| R2 | importante | `tests/Feature/PacienteSinCorreoTest.php:122` (protege `PacientesController.php` reglas de `email`/`password`) | Sin test: el alta con correo y contraseña escrita guarda esa contraseña sin `password_generada`, y un correo ya registrado se rechaza con su mensaje | Dos tests: `Hash::check` de la escrita y sin `password_generada`; correo repetido con «Este correo ya está registrado en el sistema.» y sin filas nuevas |
| R3 | importante | `app/Http/Controllers/UsuarioController.php:135,146-158` | Un admin puede añadir el correo a un paciente sin correo desde «Usuarios», donde es obligatorio (D9), sin el evento `paciente_correo_anadido` (CA11, TM6) | El mismo evento crítico, dentro de una transacción, cuando la cuenta pasa de nulo a un correo; con test |
| R4 | importante (anterior a la 054) | `app/Http/Controllers/PacientesController.php` (`update`, regla de `email`) | Desde «Pacientes», admin o dentista pueden cambiar el correo de cualquier ficha, también de una ficha de cuenta del personal; con ese correo, la recuperación de la 041 les daría esa cuenta. La 054 reescribió esa regla | Rechazar el cambio de correo desde «Pacientes» si la cuenta no es `cliente` (mensaje propio, sin cambios), con test |
| R5 | importante | `resources/views/pacientes/create.blade.php` (aviso) | `role="alert"` presente al cargar más `autofocus` en el título: el anuncio real no se ha probado con un lector de pantalla | Probar con NVDA/VoiceOver; si hay doble lectura, quitar `role="alert"` |
| R6 | menor | `tests/Feature/PacienteSinCorreoTest.php:445-459` | La parte de login y recuperación de `test_no_hay_forma_de_entrar…` pasaría con cualquier implementación (la regla `email` corta antes) | Anotar que la protección es estructural o comparar la respuesta de la recuperación con la de un correo inexistente |
| R7 | menor | `tests/Feature/MovilPacientesExpedientesTest.php:376,394` | Acepta cualquier id `ayuda-`/`aviso-` en `aria-describedby` sin comprobar que exista | Exigir que cada id apunte a un elemento con texto |
| R8 | menor | `tests/Feature/PacienteSinCorreoTest.php:666` | `test_el_bloque_054_solo_usa_tokens` usa `literales()`, que no detecta duraciones ni `rem`/`em` | `literalesPublicos()` con control positivo de duración |
| R9 | menor | `tests/Feature/PacienteSinCorreoTest.php:688` | No mide `--amber` sobre `--amber-bg` (icono, 5.87:1 según el sistema), el botón secundario ni el foco sobre el ámbar | Añadir esos pares al test |
| R10 | menor | `app/Http/Controllers/PacientesController.php` (`store`, `back()`) | Sin `Referer`, el aviso vuelve a `/` y se pierde (sin crear nada) | Redirigir a la ruta de alta del rol |
| R11 | menor | `tests/Feature/PacienteSinCorreoTest.php:748` | `cuenta()` ordena por `creado_en` (resolución de un segundo): frágil si se añade otro paciente de prueba | Localizar por id |
| R12 | menor | `app/Http/Controllers/GoogleAuthController.php:45` | `trim()` redundante: `blank()` ya trata los espacios | `blank($googleUser->getEmail())` |
| R13 | menor | `tests/Unit/PosiblesDuplicadosTest.php:76` | «estables» no afirma el orden declarado (`nombre, apellido, id`) | Afirmar el orden o renombrar |
| R14 | menor | `tests/Feature/PacienteSinCorreoTest.php:709` | El test de una sola consulta fija el SQL y el binding exactos y deja `disableQueryLog()` fuera de un `finally` | Afirmar solo el número de consultas y que no viajan nombre ni teléfono |
| R15 | menor | `app/Http/Controllers/RegisterController.php:147` | `contactoStore` confía en que `google_registro.email` no sea nulo; una sesión creada por la versión anterior antes del despliegue crearía una cuenta sin correo con sesión | Si el correo de la sesión está vacío, olvidarla y volver al inicio de sesión con el fallo de Google; con test |
| R16 | menor | `app/Pacientes/PosiblesDuplicados.php:36-42` | Sin tope de filas candidatas: una primera palabra común («de», una inicial) trae muchas filas a PHP | Filtrar también por la primera palabra del nombre o poner un tope |
| R17 | menor | `app/Http/Controllers/PacientesController.php` (aviso) | El aviso muestra nombres de pacientes desactivados y fichas del personal que el directorio oculta (decisión aceptada, sin escribir como riesgo) | Anotarlo junto a P3/014 en `docs/security.md` |
| R18 | menor | `resources/views/pacientes/create.blade.php` (aviso) | «y N más» va fuera de la lista: el lector lo lee como párrafo suelto | Texto más explícito o un elemento de la lista |
| R19 | menor | `resources/views/pacientes/create.blade.php:123` | El placeholder de la contraseña repite lo que ya dice la ayuda | Reducirlo a «Mín. 6 caracteres» |
| R20 | menor (anterior) | `resources/views/pacientes/create.blade.php` | Los `.error-msg` no se anuncian al cargar ni llevan el foco al primer campo con error (patrón de todo el formulario) | Objetivo 6: foco al primer campo inválido o resumen con `role="alert"` |

## Preparación para release
- Rollback factible: sí. `deploy/volver.sh <tag anterior>` sin deshacer la migración. Si ya hay
  cuentas sin correo, antes se desactivan las activas y, al volver, se reactivan sin `google_id`.
  Está en `docs/deployment.md` (T091, A19, A20).
- Migraciones compatibles con la versión anterior: sí. Es solo *expand* (`email` nulable) y la
  versión anterior convive con ella; `down()` se niega con cuentas sin correo.
- Feature flags: no aplica (D8).
- Docs actualizadas: `architecture.md`, `AGENTS.md` y `security.md` (T090); `observability.md` y
  `deployment.md` (T091).
- SCA: sin críticas ni altas.
- Cobertura de riesgos: la spec no cita riesgos. RS5 y RS10 siguen pendientes sin declararse
  mitigados; OB1.f sigue pendiente, con la parte de la 054 descrita.

## Tareas añadidas
- T030 ← R1, R2
- T031, T032 ← R3
- T033, T034 ← R4
- T035 ← R7
- T036, T037 ← R15
- T038 ← R3, R4, R15 (documentación)

## Aceptados sin tarea
Aceptados por el usuario el 2026-11-21:
- **R5** → roadmap: probar el aviso de duplicado con un lector de pantalla real (NVDA o VoiceOver) y, si se anuncia dos veces, quitar `role="alert"`.
- **R6** → roadmap: la parte de login y recuperación del test de CA7 es estructural; dejarlo dicho en el test o comparar la respuesta de la recuperación.
- **R8** → roadmap: el test del bloque 054 con `literalesPublicos()`.
- **R9** → roadmap: medir también `--amber` sobre `--amber-bg`, el botón secundario y el foco sobre el ámbar.
- **R10** → roadmap: el aviso de duplicado vuelve con `back()`, que sin `Referer` va a `/`.
- **R11** → roadmap: `cuenta()` de `PacienteSinCorreoTest` por id, no por `creado_en`.
- **R12** → roadmap: `trim()` redundante en la guarda de Google.
- **R13** → roadmap: el test «estables» de `PosiblesDuplicadosTest` no afirma el orden.
- **R14** → roadmap: el test de una sola consulta, menos acoplado al SQL y con `finally`.
- **R16** → roadmap: tope o segundo filtro para las filas candidatas de `PosiblesDuplicados`.
- **R17** → roadmap: anotar en `security.md`, junto a P3/014, que el aviso enseña nombres de desactivados y de fichas del personal.
- **R18** → roadmap: «y N más» del aviso, más explícito para lectores de pantalla.
- **R19** → roadmap: el placeholder de la contraseña del alta repite la ayuda.
- **R20** → roadmap: anunciar los errores del formulario de pacientes al cargar (objetivo 6).
