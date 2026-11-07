---
spec: 043-contrasena-distinta
verdict: changes_requested
round: 1
date: 2026-11-05
base: 9dba9ac
head: cb4325c
human_signoff: no requerido
---

# Review · 043 La contraseña nueva tiene que ser distinta de la actual

## Resumen
`changes_requested`. El código cumple CA1–CA8 y el requisito no funcional, sin bloqueantes. Hay dos
hallazgos importantes, los dos en tests que no prueban del todo lo que dicen (R1, R2). El usuario
decide corregirlos antes de liberar, con T025 y T026, que solo tocan el test.

Revisaron tres subagentes de contexto limpio:
- A (spec, perfil `critical`);
- C+D (constitución y calidad, perfil `standard`), que también hizo la revisión de accesibilidad,
  porque la única vista del diff es la del perfil;
- S (seguridad, perfil `critical`).

No hay `design.md` (la spec no tiene pantallas nuevas), así que no se ejecuta `evaluate.mjs`. El
rango incluye la fusión de `main` con el `/chore` de `symfony/mime` (PR #63): `composer.json` y
`composer.lock` son de ese PR.
Conteo: bloqueante 0 · importante 2 · menor 7

## Verificación automática
| Comando | Resultado |
|---|---|
| `php artisan test` (suite completa, tras fusionar `main`) | ✅ 719 en verde, 12 140 aserciones (línea base 702, sin fallos) |
| `ContrasenaDistintaTest` | ✅ 17 en verde, ejecutado varias veces en `/implement` |
| `pint --test` sobre los 4 PHP del diff | ✅ |
| `python .ai/bin/aidd.py validate docs/specs/043-contrasena-distinta` | ✅ 0 errores |
| build (`npm run build`) | ➖ no aplica: el diff no toca assets |

## Criterios de aceptación
| CA | Test | Estado | Nota |
|---|---|---|---|
| CA1 | `ContrasenaDistintaTest::test_restablecer_con_la_actual_se_rechaza_junto_al_campo`, `…_no_cierra_sesiones_ni_avisa_ni_audita`, `…_tras_el_rechazo_el_enlace_sigue_sirviendo` | ✅ | |
| CA2 | `…_perfil_con_la_actual_se_rechaza_junto_al_campo` | ⚠ | Cumple; «no cambia» se comprueba con la misma contraseña enviada (R1) |
| CA3 | `…_una_diferencia_minima_se_acepta_en_la_recuperacion`, `…_en_el_perfil` | ✅ | Datos solo ASCII; límite de 72 bytes en R8 |
| CA4 | `…_una_cuenta_sin_contrasena_fija_la_suya_por_el_enlace`, `…_crea_la_suya_en_el_perfil` | ✅ | |
| CA5 | `…_los_errores_de_siempre_van_antes_en_la_recuperacion`, `…_en_el_perfil` | ✅ | El primer subcaso de la recuperación es débil (R3) |
| CA6 | `…_perfil_sin_la_actual_correcta_no_revela_si_es_igual`, `…_perfil_con_el_limite_agotado_no_revela_si_es_igual` | ✅ | |
| CA7 | `…_los_rechazos_cuentan_para_el_limite_del_enlace` | ✅ | El residuo es por origen y no por enlace (R9) |
| CA8 | `…_el_personal_no_puede_cambiar_la_contrasena_por_el_perfil` | ✅ | Paciente permitido en los de CA2 y CA3 |
| RNF | `…_perfil_el_error_queda_asociado_al_campo_y_con_el_foco`; recuperación en el de CA1 | ⚠ | Sin caso de error en la actual y en la nueva a la vez (R2) |

## Conformidad con el plan
- Cambios fuera del plan: ninguno de esta spec.
  - `composer.json` viene de `main` (PR #63, ficha `2026-11-05-chore-symfony-mime-7.4.22`).
  - `docs/architecture.md` cambió en T090 aunque el plan no lo preveía, con la nota que lo justifica.
- Partes del plan sin implementar: ninguna. `scope.md`: 0 tareas sin diff.

## Constitution Check (sobre el código)
| Principio | Resultado | Evidencia |
|---|---|---|
| P1 Spec aprobada antes de código | ✅ | Spec, plan y tareas aprobados el 2026-11-04 y `/analyze` r2 `pass` antes de T010 |
| P2 Test que falla antes y pasa después | ⚠ | Cada control comprobado rompiéndolo (notas de T020, T021 y T024). R1 y R2: dos aserciones que no fallarían con lo protegido roto |
| P3 Autorización en servidor | ✅ | `routes/web.php:192` lleva `role:cliente`, y `RoleMiddleware` responde `abort(403)`. Test del denegado (admin, dentista y asistente) y del permitido. En la recuperación, la comparación usa la cuenta que validó el token |
| P4 Entrada validada | ✅ | La comparación va después de las reglas de siempre; `after()` solo actúa sin errores; sin SQL nuevo |
| P5 Nada sensible en el repo | ✅ | gitleaks sin fugas; contraseñas de prueba ficticias |
| P6 Sin vulnerabilidades críticas/altas | ✅ | Sin dependencias nuevas; `composer audit` con código 0 (6 avisos sin gravedad bajo EX4, vigente hasta el 2026-11-11) |
| P7 Auditoría | ✅ | Un rechazo no registra `contrasena_restablecida` (test de CA1) |
| P8 Logs limpios | ✅ | Sin `Log::` nuevos; la contraseña no queda en la sesión (TM4, con su rotura `withInput()`) |
| P9 Sistema de diseño | ✅ | Solo atributos (`for`, `id`, `aria-*`, `autofocus`, `autocomplete`) y clases de error que ya existían |
| P10 Rutas indexables | ➖ | Sin rutas públicas nuevas ni cambiadas |

## Seguridad
| Herramienta | Resultado |
|---|---|
| gitleaks v8.21.2 (commits `9dba9ac..HEAD`) | ✅ 0 hallazgos |
| semgrep 1.99.0 `p/php` (PHP del diff) | ✅ 0 hallazgos |
| `composer audit --locked` (comando de la CI) | ✅ código 0. Crít: 0 · alta: 0 · media: 0 abiertas (la de `symfony/mime` se corrigió en PR #63). Sin gravedad: 6, ignoradas por EX4 |
| `npm audit --audit-level=high` | ✅ 0 |
| trivy 0.57.1 `fs` HIGH, CRITICAL | ❌ no se ejecutó: falló la descarga de su base de datos (`mirror.gcr.io`, `unexpected EOF`). Se reintenta en `--rerun`. Esta spec no toca imágenes ni Dockerfile |

| Tema OWASP | Resultado | Evidencia |
|---|---|---|
| A01:2025 Control de acceso | ✅ | `role:cliente` en `PUT /cliente/perfil/password`, con test de los tres roles del personal; el resto del grupo sigue en RS2.a (→ spec 015) |
| A07:2025 Autenticación | ✅ | `Hash::check` contra el hash guardado: en el perfil, después de acreditar la actual (`PerfilController.php:114-121`); en la recuperación, después de validar el token |
| Divulgación de información y oráculos | ✅ | No hay oráculo nuevo sin acreditar; el residuo de la recuperación está aceptado en la spec (tamaño real en R9) |
| Limitación de intentos | ✅ | Los rechazos cuentan para `fijar-contrasena` y para los 5 fallos del perfil, con tests |
| A09:2025 Registro | ✅ | Sin `Log::` nuevos; sin `_old_input` con la contraseña |
| A03:2025 Cadena de suministro | ✅ | Sin dependencias nuevas; EX4 coincide con `config.audit.ignore` |
| A02, A04, A10 | ➖ | La spec no toca la configuración, el hashing ni el manejo de excepciones |

## Observabilidad
- Eventos de auditoría de la spec emitidos y probados: no hay eventos nuevos. Que un rechazo no
  registre `contrasena_restablecida` lo prueba
  `…_restablecer_con_la_actual_no_cierra_sesiones_ni_avisa_ni_audita`.
- Datos sensibles en logs del código tocado: ninguno; no hay `Log::` nuevos.
- `request_id` presente en los logs nuevos: no aplica.

## Hallazgos
| ID | Severidad | Archivo:línea | Hallazgo | Sugerencia |
|---|---|---|---|---|
| R1 | importante | `tests/Feature/ContrasenaDistintaTest.php:263` (y 118, 185, 295, 311) | Después de enviar la actual como nueva, `assertContrasena($usuario, self::ACTUAL)` no demuestra que la contraseña no se guardó: `Hash::check(ACTUAL)` da true aunque el controlador hubiera hecho el `UPDATE` con otra sal. En el perfil nada más lo cubre; en CA1 lo cubre `version_sesion`. | Guardar `password_hash` antes del envío y comprobar con `assertSame` que la cadena sigue siendo la misma, en cada caso que envía la actual. |
| R2 | importante | `tests/Feature/ContrasenaDistintaTest.php:343-359`; `resources/views/cliente/perfil.blade.php:135` | Ningún caso tiene error en la actual y en la nueva a la vez, así que el `@unless($errors->has('password_actual'))` del `autofocus` de `password` no se ejerce. Si se quitara, habría dos `autofocus` y el test seguiría en verde. | Añadir un caso con los dos errores (actual vacía y nueva corta): un solo `autofocus`, en `password_actual`, y `password` con `aria-invalid` y `aria-describedby`. |
| R3 | menor | `tests/Feature/ContrasenaDistintaTest.php:175-176` | En el primer subcaso de CA5 de la recuperación, `assertSessionHasErrors([clave => valor])` solo comprueba que el mensaje está entre los de `password`; pasaría aunque también saliera «distinta». Hoy el segundo subcaso detecta la rotura. | Comprobar en la pantalla que `#clave-error` contiene solo el error de longitud. |
| R4 | menor | `tests/Feature/ContrasenaDistintaTest.php:351` | El test de accesibilidad del perfil prueba el error de `password` con la regla de longitud (`'corta'`), no con «distinta», el error que añade la 043. | Añadir al bucle el caso de la actual repetida, con el mensaje exacto en `#contrasena-nueva-error`. |
| R5 | menor | `tests/Feature/ContrasenaDistintaTest.php:~268` | La comprobación de CA2 en la pantalla usa una regex que depende del orden de los atributos y del marcado que sigue al campo. | Usar `xpath` sobre `#contrasena-nueva-error`, como en la recuperación. |
| R6 | menor | `resources/views/cliente/perfil.blade.php:135` | «Mín. 8 caracteres» es solo un `placeholder`, que desaparece al escribir (WCAG 3.3.2). Ya existía antes de la 043. | Pasarlo a un `field-hint` con id dentro del `aria-describedby`. |
| R7 | menor | `app/Http/Controllers/PerfilController.php:119`, `RecuperarContrasenaController.php:140` | El texto «Elige una contraseña distinta de la actual» está duplicado como literal en dos controladores (decisión D3 del plan). | Una constante compartida si aparece un tercer uso. |
| R8 | menor | `spec.md` CA3 y su fila de Decisiones | CA3 habla de «72 caracteres» y bcrypt distingue 72 bytes (`/analyze` A13; el comentario del código ya dice bytes). | Al liberar, dejar en la spec «72 bytes (72 caracteres sin acentos)». |
| R9 | menor | `app/Providers/AppServiceProvider.php:91-92`; spec, Decisiones (residuo de CA7) | `fijar-contrasena` limita por origen, no por enlace: con varias direcciones se pueden probar más de 300 candidatas en los 60 minutos, y el rechazo no deja rastro. | Corregir la cifra del residuo. Si se quiere cerrar, un segundo límite por cuenta en `fijar-contrasena`. |

## Preparación para release
- Rollback factible: sí. `desplegar.sh v0.14.0`: sin migraciones ni datos, y el código anterior sigue siendo válido.
- Migraciones compatibles con la versión anterior: no hay migraciones.
- Feature flags: el plan no pide ninguno (D5); `RECUPERAR_CONTRASENA` sigue encendido.
- Docs actualizadas:
  - `architecture.md` (T090): `role:cliente` ruta a ruta, también en el cambio de contraseña del perfil.
  - `deployment.md` sin cambios (T091).
  - `security.md`: RS2.a anota la ruta y sigue `pendiente`.
- Cobertura de riesgos: RS2.a «fuera, parcialmente atendida» y RS2.b «fuera»; nada se declara mitigado.
- SCA: ninguna crítica ni alta abierta; EX4 vigente hasta el 2026-11-11.

## Tareas añadidas
- T025 ← R1
- T026 ← R2

## Aceptados sin tarea
- **R3** → roadmap: subcaso de CA5 de la recuperación que solo comprueba que el mensaje está entre los de `password`.
- **R4** → roadmap: el test de accesibilidad del perfil no prueba el error «distinta».
- **R5** → roadmap: regex del test de CA2 en el perfil, en lugar de `xpath`.
- **R6** → roadmap: «Mín. 8 caracteres» solo como `placeholder` en el perfil (anterior a la 043).
- **R7** → roadmap: mensaje duplicado en dos controladores.
- **R8** → roadmap: «72 caracteres» de CA3 frente a 72 bytes; `/release` corrige el texto de la spec.
- **R9** → roadmap: el residuo de TM2 es por origen, no por enlace; posible límite por cuenta en `fijar-contrasena`.
