---
spec: 002-registro-pacientes
verdict: changes_requested
round: 1
date: 2026-11-21
base: b1d338c
head: 93f7ad0
human_signoff: no requerido
---

# Review · 002 Autorregistro de pacientes

## Resumen
`changes_requested`: no hay bloqueantes, pero hay cinco importantes que el usuario decide corregir antes de liberar.
- R1: se puede saber si un correo tiene cuenta sin crear ninguna.
- R2–R4: tres tests que no fallan siempre que se rompe lo que protegen.
- R5: el aviso general de errores no se anuncia al lector de pantalla. Es anterior a esta spec, pero la pantalla se modificó.

También se corrigen los menores que son tests baratos (R6–R9). El resto va al roadmap como deuda.

Revisores independientes: A (`opus`), C+D (`sonnet`), S (`opus`) y accesibilidad (`sonnet`). SEO no tuvo revisor propio: todo el marcado nuevo está dentro de `@error('email')`, así que el `GET /registro` no cambia. Lo comprobé sobre el diff, y R4 añade el test que lo fija.

Conteo: bloqueante 0 · importante 5 · menor 10

## Verificación automática
| Comando | Resultado |
|---|---|
| `php artisan test` (suite completa, contenedor efímero) | ✅ 1536 en verde, 20 007 aserciones (línea base: 1521, sin fallos) |
| `vendor/bin/pint --test` sobre los 3 PHP tocados | ✅ |
| `npm run build` | ➖ sin cambios en los assets de Vite (`auth.css` se sirve tal cual) |
| `aidd.py validate docs/specs/002-registro-pacientes` | ✅ 0 errores |

## Criterios de aceptación
| CA | Test | Estado | Nota |
|---|---|---|---|
| CA1 | `RegistroPacientesTest::test_el_alta_crea_la_cuenta_y_la_ficha_y_entra`, `…_si_falla_la_ficha_no_queda_la_cuenta` | ✅ | rotura de la transacción anotada |
| CA2 | `…_se_asigna_el_dentista_activo_con_menos_pacientes`, `…_a_igualdad_el_dentista_mas_antiguo`, `…_sin_dentistas_activos_el_alta_queda_sin_dentista` | ⚠ | el desempate solo se detecta con probabilidad (R2) |
| CA3 | `…_un_correo_registrado_se_rechaza_sin_decir_que_existe` | ✅ | |
| CA4 | `…_el_registro_con_google_crea_la_cuenta_sin_contrasena` | ✅ | no comprueba el teléfono ni la ciudad guardados (R8) |
| CA5 | `…_el_rol_es_siempre_cliente` | ✅ | |
| CA6 | `…_una_contrasena_corta_se_rechaza` | ✅ | con el umbral exacto de 8 |
| CA7 | `LimiteIntentosTest::test_el_alta_admite_cinco_intentos_por_minuto_y_direccion` | ✅ | existente |
| CA8 | `…_sin_decir_que_existe`, `…_el_error_del_correo_queda_asociado_al_campo`, `…_otros_errores_del_correo_no_llevan_los_enlaces`, `…_con_el_limite_agotado_no_salen_los_enlaces`, `…_solo_usan_tokens`, `…_tienen_foco_y_contraste` | ⚠ | el mensaje se puede provocar sin crear la cuenta (R1); la vuelta de la contraseña y del correo no está bien probada (R7) |

## Conformidad con el plan
- Cambios fuera del plan: ninguno (`scope.md`: 5 archivos de código, todos con tarea; `ReglasCss` por `/analyze` N1).
- Partes del plan sin implementar: ninguna.

## Constitution Check (sobre el código)
| Principio | Resultado | Evidencia |
|---|---|---|
| P1 Spec aprobada | ✅ | spec, plan y tareas aprobados; `/analyze` r2 pass |
| P2 Test antes | ✅ | CA8 fallaba antes de T020 y T021; caracterizaciones comprobadas rompiendo (notas de T010–T013) |
| P3 Autorización | ✅ | rol y `activo` fijados en el servidor (`RegisterController:76, 78`), con test |
| P4 Entrada y SQL | ✅ | sin SQL nuevo; semgrep 0 |
| P5 Nada sensible | ✅ | gitleaks sin fugas |
| P6 Vulnerabilidades | ✅ | sin cambios de dependencias; SCA y trivy abajo |
| P7 Auditoría | ❌ aceptado | excepción del usuario (alta sin evento → 021); el código no va más allá |
| P8 Logs | ✅ | sin `Log::` nuevos |
| P9 Sistema de diseño | ⚠ | reglas solo con tokens, foco y contraste correctos (unos 7:1), pero el test no comprueba el color real del mensaje ni el subrayado (R3); el aviso general sin `role="alert"` (R5) |
| P10 Rutas indexables | ⚠ | el `GET /registro` no cambia, pero ningún test lo fija (R4) |

## Seguridad
| Herramienta | Resultado |
|---|---|
| gitleaks v8.21.2 (`dir` sobre los 5 archivos y la carpeta de la spec) | ✅ 0 hallazgos |
| semgrep 1.99.0 `p/php` | ✅ 0 hallazgos |
| `composer audit --locked` + `auditar-npm.mjs` | crít: 0 · alta: 0 · media: 0 (1 baja preexistente de `laravel/framework`) |
| trivy 0.58.1 `fs --severity HIGH,CRITICAL` | ✅ 0 hallazgos |

| Tema OWASP | Resultado | Evidencia |
|---|---|---|
| A07:2025 Enumeración de cuentas (TM1) | ❌ (R1) | el mensaje ya no dice que exista, pero solo sale con `unique` y se evalúa junto al resto: un alta inválida a propósito responde sin crear cuenta |
| A07:2025 Límite de intentos (CA7) | ✅ | `throttle:registro` en los dos `POST`; mismo mensaje sin enlaces |
| A01:2025 Asignación masiva (TM3) | ✅ | `test_el_rol_es_siempre_cliente` |
| A05:2025 Salida codificada | ✅ | `{{ }}` en `old('email')` y en el mensaje; test con `a<b>` |
| Enlaces del rechazo (TM4) | ✅ | `route()` sin parámetros; `href` exactos en el test |
| Registro con Google | ➖ | exige poseer la cuenta de Google; no sirve para enumerar |
| A09:2025 Registro y alertas | ➖ | excepción P7; el sondeo no deja rastro (R13 → 021) |
| Proceso | ✅ | sin rutas protegidas tocadas sin tarea; sin instrucciones sospechosas |

## Observabilidad
- Eventos de auditoría de la spec emitidos y probados: ninguno (excepción P7, decisión del usuario → spec 021).
- Datos sensibles en logs del código tocado: ninguno.
- `request_id` presente en los logs nuevos: no hay logs nuevos.

## SEO
| Tema | Resultado | Evidencia |
|---|---|---|
| 3. Indexación y canónica | ✅ sin cambios en el `GET`; sin test (R4) | `git diff b1d338c..HEAD -- resources/views/auth/register.blade.php`: todo lo nuevo, dentro de `@error('email')` |
- Sección «Descubribilidad» del plan aplicada: sí (`/registro` sin cambios en el `GET`).

## Hallazgos
| ID | Severidad | Archivo:línea | Hallazgo | Sugerencia |
|---|---|---|---|---|
| R1 | importante | `app/Http/Controllers/RegisterController.php:39, 55` | El mensaje neutro solo sale con la regla `unique`, que se evalúa a la vez que los demás campos. Si se envía el formulario con otro campo inválido a propósito (sin `terminos`, confirmación distinta), su presencia dice si el correo tiene cuenta: se enumera sin crear cuentas ni dejar rastro. Es más que el residuo aceptado en RS13.c. | El correo repetido solo se comprueba cuando el resto del formulario es válido; con otro error, la respuesta es igual tenga o no cuenta el correo (test que falle hoy). T023, T024. |
| R2 | importante | `tests/Feature/RegistroPacientesTest.php:81-90` | Sin el orden por antigüedad, el empate no tiene orden definido: el test con 6 empatados detecta la rotura con probabilidad (3 de 4 en `/implement`), no siempre. | Seis altas seguidas con los seis empatados deben asignarse exactamente por antigüedad (1 entre 720 de acertar por azar). T025. |
| R3 | importante | `tests/Feature/RegistroPacientesTest.php:246-254`; `auth.css:293-295` | El contraste se calcula con `--red` y `--white` sin comprobar que el mensaje use `--red`, ni que los enlaces lleven `color: inherit` y subrayado. | Comprobar esas declaraciones en `auth.css`. T025. |
| R4 | importante | `plan.md` P10; `PaginasPublicasTest` | `/registro` no está en la tabla de metadatos de `PaginasPublicasTest`: un `noindex` o un título distinto no harían fallar nada. | Test de `GET /registro`: 200, el título de siempre y sin `noindex` (meta ni cabecera). T025. |
| R5 | importante | `resources/views/auth/register.blade.php:63-73` | El aviso general de errores no tiene `role="alert"` (4.1.3) y su icono no está oculto al lector de pantalla. Era así antes de la spec, pero la pantalla se modificó. | Aviso con `role="alert"` e icono con `aria-hidden="true"` (test que falle hoy). T027, T028. |
| R6 | menor | `register.blade.php:154`; `RegistroPacientesTest` | Con la recuperación desactivada solo debe salir «Iniciar sesión»; esa rama no tiene test. | Test con `acceso.recuperar_contrasena` en `false`. T026. |
| R7 | menor | `RegistroPacientesTest.php:170-173` | «Salvo la contraseña» se comprueba por el `value` vacío (que `dontFlash` ya garantiza) y no se mira la confirmación; el correo que vuelve no se comprueba en el rechazo. | Comprobar el `old input` de la sesión y el `value` del correo. T026. |
| R8 | menor | `RegistroPacientesTest.php:103-117` | CA4 no comprueba que el teléfono y la ciudad enviados queden guardados. | Comprobarlos. T026. |
| R9 | menor | `tests/Feature/Concerns/ReglasCss.php:49` | El comentario de `declaraciones()` dice «en `app.css`» aunque recibe el archivo. | Corregirlo. T026. |
| R10 | menor | `auth.css:293`; `register.blade.php:151-156` | El texto del mensaje es de 12 px y los enlaces miden 71×14 px; cumplen WCAG 2.1 AA, pero son pequeños. | Aceptado → roadmap. |
| R11 | menor | `register.blade.php:155` | El separador «·» entre los enlaces se lee en voz alta. | Aceptado → roadmap. |
| R12 | menor | `register.blade.php:152` | La vista decide los enlaces comparando el texto del mensaje con la constante. | Aceptado → roadmap. |
| R13 | menor | `AppServiceProvider.php:60-64` | El limitador `registro` no deja rastro (no emite `Lockout`): una campaña de sondeo no se ve. | Aceptado → roadmap (spec 021). |
| R14 | menor | `RegisterController.php:66-101` | Un alta nueva calcula bcrypt dos veces y un correo existente no: diferencia de tiempos, dentro del residuo de RS13.c. | Aceptado → roadmap (spec de confirmación del correo). |
| R15 | menor | `RegisterController.php:20` | Con la recuperación desactivada, el mensaje ofrece «recupera tu contraseña» sin enlace (en producción está activa). | Aceptado → roadmap. |

## Preparación para release
- Rollback factible: sí, `bash deploy/volver.sh <tag anterior>`, sin la copia (no hay migraciones).
- Migraciones compatibles con la versión anterior: no hay migraciones.
- Feature flags: el plan no pide ninguno.
- Docs actualizadas: `docs/architecture.md` (T090); `docs/deployment.md` y `docs/observability.md` sin cambios (T091). RS13.c ya está en `docs/security.md` y en el roadmap; RS13.b se marca en `/release`.
- Cobertura de riesgos: RS13.b, dentro, pero con R1 abierta todavía no se puede marcar como mitigada.

## Tareas añadidas
- T023, T024 ← R1
- T025 ← R2, R3, R4
- T026 ← R6, R7, R8, R9
- T027, T028 ← R5

## Aceptados sin tarea
Aceptados por el usuario el 2026-11-21:
- **R10** → roadmap: el mensaje del correo de 12 px y sus enlaces de 71×14 px (cumplen WCAG 2.1 AA, pero son pequeños)
- **R11** → roadmap: el separador «·» entre los enlaces del rechazo se lee en voz alta
- **R12** → roadmap: la vista del registro decide los enlaces comparando el texto del mensaje con la constante
- **R13** → roadmap: el limitador `registro` no deja rastro de un sondeo (→ spec 021)
- **R14** → roadmap: diferencia de tiempos entre el alta nueva y el correo existente (dentro de RS13.c)
- **R15** → roadmap: con la recuperación desactivada, el mensaje ofrece recuperar la contraseña sin enlace
