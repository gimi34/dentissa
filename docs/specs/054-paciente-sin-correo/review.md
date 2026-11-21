---
spec: 054-paciente-sin-correo
verdict: approved
round: 2
date: 2026-11-21
base: 1985598
head: 6c7b262
human_signoff: no requerido
---

# Review · 054 Alta de pacientes sin correo

## Resumen
**Aprobada.** Los seis hallazgos que el usuario decidió corregir en la ronda 1
([r1](history/review.r1.md)) están resueltos y probados:
- R1 y R2: tests que faltaban;
- R3: correo añadido desde «Usuarios» con su evento;
- R4: «Pacientes» ya no cambia el correo de una cuenta del personal;
- R7: `aria-describedby` hacia ids que existen;
- R15: el registro tras Google rechaza una sesión sin correo.

La suite completa pasa. El único hallazgo nuevo es un menor (R21), aceptado al roadmap. R5 y los
trece menores aceptados en la ronda 1 siguen aceptados, y el código nuevo no los empeora.

Ronda `--rerun` sin subagentes: el diff desde la ronda 1 son 18 KB en 6 archivos, releídos desde
cero.

Conteo: bloqueante 0 · importante 0 · menor 1

## Verificación automática
| Comando | Resultado |
|---|---|
| `php artisan test` (suite completa, contenedor con el código del worktree y base `dentissa_054_test` con la migración aplicada) | ✅ 1591 tests, 0 fallos (ronda 1: 1579; +12 de T030–T037); el código no cambió después, solo documentos (T038, T092) |
| `vendor/bin/pint --test` sobre los 9 PHP tocados por la spec | ✅ |
| `python .ai/bin/aidd.py validate docs/specs/054-paciente-sin-correo` | ✅ 0 errores |
| Tests de vista dos veces | ➖ de servidor, sin JavaScript ni esperas asíncronas |

## Criterios de aceptación
| CA | Test | Estado | Nota |
|---|---|---|---|
| CA1 | `PacienteSinCorreoTest` (alta sin correo, espacios, cita y expediente, con correo como hoy, `…con_correo_y_contrasena_usa_la_escrita`, `…con_un_correo_ya_registrado_se_rechaza`, contraseña corta) | ✅ | R2 resuelto (T030, comprobado rompiendo `exclude_without` y el mensaje) |
| CA2 | `…sin_correo_ni_telefono_se_rechaza_y_conserva_lo_escrito` | ✅ | |
| CA3 | `PosiblesDuplicadosTest` (con `…sin_telefono_y_con_otra_fecha_no_coincide…`), aviso, confirmación, «y N más», Enter | ✅ | R1 resuelto (T030, comprobado rompiendo la guarda) |
| CA4 | directorio y ficha | ✅ | |
| CA5 | edición sin correo y con correo; `MovilPacientesExpedientesTest` con ids existentes | ✅ | R7 resuelto (T035) |
| CA6 | añadir correo, acceso con la 041, correo ya usado, `…no_se_cambia_el_correo_de_una_cuenta_del_personal` (admin y dentista), `…el_correo_de_un_paciente_se_sigue_cambiando` | ✅ | R4 resuelto (T033–T034) |
| CA7 | acceso, Google sin correo (3 casos), `…el_registro_tras_google_sin_correo_no_crea_cuenta` (3 casos) | ✅ | R15 resuelto (T036–T037) |
| CA8 | roles | ✅ | |
| CA9 | el aviso no revela datos; enlaces por rol; escapado | ✅ | |
| CA10 | `paciente_alta` (4 casos), confirmación falsa, alta denegada, almacén que falla | ✅ | |
| CA11 | `paciente_correo_anadido` desde «Pacientes» y desde «Usuarios» (`…desde_usuarios_registra_el_evento`, `…cambiar_un_correo_existente_desde_usuarios_no_lo_registra`, `…si_la_auditoria_falla_desde_usuarios…`) | ✅ | R3 resuelto (T031–T032, comprobado rompiendo la condición) |

## Conformidad con el plan
- Cambios fuera del plan: `UsuarioController.php` y `RegisterController.php`. Los añadió `/review`
  r1 (R3, R15) con sus tareas y filas en «Cambio del plan (módulo)» de `tasks.md`. `scope.md`: 0
  archivos sin tarea.
- Partes del plan sin implementar: ninguna. T038 solo toca documentos (`docs.stat`).

## Constitution Check (sobre el código)
| Principio | Resultado | Evidencia |
|---|---|---|
| P1 | ✅ | Tareas de `/review` sin `/analyze` nuevo (contrato) |
| P2 | ✅ | T031, T033 y T036 en rojo antes de T032, T034 y T037; T030 y T035 comprobados rompiendo lo que protegen (notas en `tasks.md`) |
| P3 | ❌ aceptado | Sin cambios en la excepción; R4 la mejora: «Pacientes» deja de cambiar el correo de cuentas del personal |
| P4 | ✅ | `ValidationException::withMessages` con el mensaje propio; `blank()` sobre la sesión; sin SQL nuevo salvo una consulta por `usuario_id` con Query Builder |
| P5 | ✅ | gitleaks sin hallazgos sobre los commits de la rama; la contraseña de prueba nueva es de baja entropía |
| P6 | ✅ | Sin dependencias nuevas; lockfiles sin cambios |
| P7 | ❌ aceptado | OB1.e sigue fuera; el nuevo camino de «Usuarios» queda auditado (R3) |
| P8 | ✅ | Sin `Log::` nuevos; el evento de «Usuarios» solo lleva `campos` y un booleano |
| P9 | ✅ | Sin cambios de vista ni CSS en esta ronda |
| P10 | ➖ | Sin rutas públicas tocadas: `/registro/contacto` solo gana un rechazo previo, sin cambio de HTML |

## Seguridad
| Herramienta | Resultado |
|---|---|
| gitleaks v8.21.2 | ✅ 0 hallazgos sobre el parche de todos los commits de la rama (T092) |
| semgrep 1.99.0 `p/php` | ✅ 0 hallazgos en los 9 PHP tocados (T092) |
| SCA (`composer audit`, `auditar-npm.mjs`) | ✅ lockfiles sin cambios desde la ronda 1 (crít: 0 · alta: 0) |
| Trivy 0.58.1 | ✅ sin cambios de dependencias ni de imágenes desde la ronda 1 (0 altas ni críticas) |

| Tema OWASP (2025) | Resultado | Evidencia |
|---|---|---|
| A01 Control de acceso | ✅ | R4: `PacientesController.php` rechaza el cambio de correo si `rol !== 'cliente'` antes de guardar; test con admin y dentista |
| A06 Diseño inseguro | ✅ | R3 y R15 ya no son caminos fuera del modelo |
| A07 Autenticación | ✅ | `contactoStore` olvida la sesión sin correo y vuelve al login con el fallo de Google |
| A09 Registro | ✅ | `paciente_correo_anadido` desde «Usuarios», crítico, en la transacción del guardado; 503 sin guardar si falla |

## Observabilidad
- Eventos de auditoría emitidos y probados: sí. `paciente_alta` y `paciente_correo_anadido`, este
  también desde «Usuarios» (T031–T032), con su fila en `docs/observability.md` (T038).
- Datos sensibles en logs: ninguno; no hay logs nuevos.
- `request_id`: los eventos lo llevan por `Auditoria`.

## Hallazgos
| ID | Severidad | Archivo:línea | Hallazgo | Sugerencia |
|---|---|---|---|---|
| R21 | menor | `resources/views/pacientes/create.blade.php` (campo de correo en la edición) | En la edición de la ficha de una cuenta del personal, el correo sigue mostrándose editable aunque `update` rechaza cualquier cambio (R4) | Mostrar el correo de solo lectura en ese caso, con una ayuda que diga dónde se cambia |

Hallazgos de la ronda 1:
- resueltos: R1, R2, R3, R4, R7, R15;
- aceptados, sin cambios: R5, R6, R8–R14, R16–R20.

## Preparación para release
- Rollback factible: sí, sin cambios respecto a la ronda 1 (`docs/deployment.md`, A19, A20). Los
  controles nuevos solo rechazan acciones: la versión anterior no depende de ellos.
- Migraciones compatibles con la versión anterior: sí, sin migraciones nuevas en esta ronda.
- Docs actualizadas: `security.md` y `observability.md` (T038).
- SCA: sin críticas ni altas.

## Tareas añadidas
Ninguna en esta ronda. Las de la ronda 1 (T030–T038) están cerradas.

## Aceptados sin tarea
Aceptados por el usuario el 2026-11-21 (ronda 1, se mantienen):
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

Aceptado por el usuario el 2026-11-21 (ronda 2):
- **R21** → roadmap: en la edición de la ficha de una cuenta del personal, mostrar el correo de solo lectura con una ayuda que diga que se cambia desde «Usuarios».
