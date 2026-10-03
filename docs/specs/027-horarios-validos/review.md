---
spec: 027-horarios-validos
verdict: changes_requested
round: 1
date: 2026-10-03
base: ebe3bc1
head: 5d17d1c
human_signoff: no requerido
---

# Review · 027 Horario válido del dentista y aviso de citas afectadas

## Resumen
**changes_requested.** No hay bloqueantes: los 9 criterios tienen tests que pasan y los controles del modelo de
amenazas existen y se comprobaron rompiéndolos. El usuario decide corregir antes de liberar los cuatro importantes:
- R1, un límite de CA7 sin probar;
- R2, el borde con foco del campo de hora, que se sale del sistema;
- R3, un test de contraste con pares fijos;
- R4, una vulnerabilidad media sin registrar.

También se corrigen cinco menores baratos. El resto de los menores va al roadmap. Revisión con cuatro subagentes
independientes (spec, constitución y calidad, seguridad y accesibilidad) sobre `code.diff` (unos 95 KB) de
`ebe3bc1..5d17d1c`.

Conteo: bloqueante 0 · importante 4 · menor 16

## Verificación automática
| Comando | Resultado |
|---|---|
| `docker compose exec app php artisan test` | ✅ 150 passed (1142 assertions); línea base de T002: 127 |
| Tests nuevos de la 027, dos veces | ✅ 23 passed en las dos ejecuciones |
| `vendor/bin/pint --test` sobre los 9 archivos PHP tocados | ✅ PASS |
| `python .ai/bin/aidd.py validate docs/specs/027-horarios-validos` | ✅ 0 errores |

## Criterios de aceptación
| CA | Test | Estado | Nota |
|---|---|---|---|
| CA1 | `HorarioDentistaTest::test_un_horario_valido_se_guarda_y_la_ficha_lo_muestra` | ⚠ | Pasa, pero la aserción `value="09:00"` también la cumplen los días sin marcar (R5) |
| CA2 | `test_una_franja_invertida_o_que_cruza_la_medianoche_se_rechaza`, `HorarioDentistaVistasTest::test_los_errores_de_un_dia_se_asocian_a_sus_campos` | ✅ | |
| CA3 | `test_un_horario_manipulado_se_rechaza_sin_error_500` | ✅ | 10 variantes, con la ficha en 200 al seguir la redirección |
| CA4 | `test_un_dia_marcado_sin_horas_se_rechaza`, `test_los_errores_de_un_dia_se_asocian_a_sus_campos` | ⚠ | No se comprueba que las horas salgan vacías tras el rechazo (R6) |
| CA5 | `test_un_horario_sin_dias_se_guarda_con_aviso` | ✅ | |
| CA6 | `test_no_se_guarda_horario_para_quien_no_es_dentista`, `test_la_ficha_solo_ofrece_horario_a_los_dentistas` | ✅ | |
| CA7 | `CitasFueraDelHorarioTest::test_la_ficha_lista_las_citas_futuras_fuera_del_horario`, `test_al_guardar_el_mensaje_dice_cuantas_quedaron_fuera` | ⚠ | Sin el límite "futura" en la hora de la clínica (R1) |
| CA8 | `test_sin_citas_fuera_no_hay_lista`, `test_las_citas_pasadas_o_cerradas_no_aparecen` | ✅ | |
| CA9 | `test_solo_la_administracion_guarda_horarios` | ✅ | Caracterización; la rama de rol del Gate, sin test directo (R7) |

## Conformidad con el plan
- Cambios fuera del plan: ninguno (`scope.md`: 0 archivos sin tarea). T001 y T041 solo tocan documentación.
- Partes del plan sin implementar: ninguna.

## Constitution Check (sobre el código)
| Principio | Resultado | Evidencia |
|---|---|---|
| P1 | ✅ | Spec, plan y tareas aprobados; `/analyze` en `pass` (ronda 3) |
| P2 | ✅ | Notas de fallo previo en T010–T012 y comprobaciones rompiendo el código en T020, T022 y T023 |
| P3 | ✅ | `role:admin` más el Gate `definir-horario` (`GestionPersonal.php`, `PersonalController.php:357`); la rama de rol del Gate no tiene test directo (R7) |
| P4 | ✅ | Reglas con lista de permitidos y `HoraFinPosterior`; solo Query Builder en lo nuevo. Los `DB::raw`/`whereRaw` previos del controlador son deuda (R16) |
| P5 | ✅ | gitleaks: sin secretos |
| P6 | ✅ | Sin dependencias nuevas; 0 altas o críticas |
| P7 | ➖ | No aplica (spec, Auditoría) |
| P8 | ✅ | Sin llamadas a `Log::` nuevas |
| P9 | ⚠ | Solo tokens en las reglas nuevas, pero el `.field:focus` previo de la vista cambia el borde con foco (R2), y el test de contraste usa pares fijos (R3) |

## Seguridad
| Herramienta | Resultado |
|---|---|
| gitleaks (`ebe3bc1..HEAD`) | ✅ 0 hallazgos |
| semgrep `p/php` (6 archivos de `app/`) | ✅ 0 hallazgos |
| composer audit `--locked` | crít: 0 · alta: 0 · media: 1 (phpseclib CVE-2026-84308, R4) · baja: 2 (EX1) |
| npm audit | ✅ 0 |
| trivy fs HIGH,CRITICAL | 9 altas, todas en `vendor/mockery/mockery/docs/requirements.txt` (setuptools, urllib3, wheel). **Falso positivo:** son dependencias Python para generar la documentación de mockery, que nunca se instalan ni se ejecutan; `vendor/` no se versiona y el job de trivy del CI corre sin él (R20) |

| Tema OWASP | Resultado | Evidencia |
|---|---|---|
| A01:2025 Control de acceso (TM2, TM3, TM6, TM8) | ✅ | `role:admin`, el Gate con `denyAsNotFound`, el filtro `citas.dentista_id` y los tests por rol (403 y 404) |
| A05:2025 Inyección y validación (TM1, TM4) | ✅ | Lista de permitidos `array:`, `date_format`, `HoraFinPosterior`; 10 variantes manipuladas sin 500 |
| A05:2025 XSS (TM7) | ✅ | Solo `{{ }}` en las vistas tocadas; el test comprueba `&lt;b&gt;` |
| A06:2025 Concurrencia (TM5) | ✅ | Bloqueo de `personal` antes de borrar; 1205, 1213 y `DeadlockException` → mensaje; tests de orden, del error simulado y de HTTP |
| CSRF | ✅ | `@csrf` en el formulario |
| Datos sensibles | ✅ | La lista (nombre, fecha, hora y estado) solo la ve admin |
| Proceso | ✅ | Sin cambios en rutas protegidas ni lockfiles. En el árbol hay cambios sin commitear en `aidlc/` que no son de esta spec: no se incluyen |

## Observabilidad
- Eventos de auditoría de la spec emitidos y probados: no aplica (spec, Auditoría; P7 ➖).
- Datos sensibles en logs del código tocado: ninguno (sin logs nuevos).
- `request_id` presente en los logs nuevos: no hay logs nuevos.

## Hallazgos
| ID | Severidad | Archivo:línea | Hallazgo | Sugerencia |
|---|---|---|---|---|
| R1 | importante | tests/Feature/CitasFueraDelHorarioTest.php:113-118 (protege `app/Agenda/HorarioDentista.php:66`) | El límite de cita "futura" en la hora de la clínica no se prueba: si `citasFuera` comparara en UTC, una cita de hoy a las 14:00 locales fuera del horario desaparecería de la lista y del recuento, y los tests seguirían en verde | Añadir esa cita con el reloj a las 17:00 UTC y exigir que aparezca |
| R2 | importante | resources/views/personal/show.blade.php:349 frente a public/css/app.css:443-446 | La regla previa `.field:focus { border-color: var(--p300) }` del `<style>` de la vista tiene la misma especificidad que `.horario-dia .field` y va después: con el foco, el borde del campo de hora pasa a `--p300` (2.23:1) y se queda en el grosor literal de 1.5px. El anillo de foco sigue visible, así que WCAG se cumple, pero la pieza modificada se sale de los tokens y ningún test lo ve | `.horario-dia .field:focus` con `--border-control` y `border-width: var(--border-width)` en `app.css`, con su test |
| R3 | importante | tests/Feature/HorarioDentistaVistasTest.php:188-212 | `test_los_pares_de_contraste_cumplen_aa` comprueba una lista fija de pares, no los colores que usan las reglas nuevas: seguiría pasando si una regla nueva usara `--text-light` (3.51:1) | Sacar los pares de las propias reglas (`color`, bordes de control y `outline`) |
| R4 | importante | composer.lock (phpseclib/phpseclib 3.0.55) | CVE-2026-84308, media, sin registrar en `docs/security.md`; el CI ignora las medias | Registrarla con su destino (decisión del usuario: actualizar en la spec de dependencias, antes de que venza EX1) |
| R5 | menor | tests/Feature/HorarioDentistaTest.php:88 | `value="09:00"` en toda la sección también lo cumplen los días sin marcar | Comprobar dentro de `fila-1`, `fila-3` y `fila-5` |
| R6 | menor | tests/Feature/HorarioDentistaVistasTest.php:111 | CA4: no se comprueba que las horas del día marcado sin horas salgan vacías tras el rechazo | Exigir `value=""` en sus dos campos |
| R7 | menor | app/Autorizacion/GestionPersonal.php:19-21 | La rama "otro rol → 403" del Gate no se ejecuta en ningún test (la corta antes `role:admin`) | Test con `Gate::forUser()->inspect()` |
| R8 | menor | app/Http/Controllers/PersonalController.php:350-357 · app/Agenda/HorarioDentista.php:96 | El tipo del personal se comprueba fuera de la transacción, y el bloqueo no vuelve a comprobar `tipo` ni que la fila exista | Bloquear con `where('tipo','dentista')` y abortar si no hay fila |
| R9 | menor | resources/views/personal/show.blade.php:234 | Una fila con `disponible = 0` (dato heredado) sale marcada en la ficha, pero `citasFuera` la trata como día sin horario; al guardar pasa a `disponible = 1` | Mostrarla sin marcar, o anotar que se normaliza al guardar |
| R10 | menor | resources/views/personal/show.blade.php:278-283, 355-364 | Al desmarcar un día con error, su mensaje sigue visible | Que `toggleDia` alterne `hidden` en los mensajes de la fila |
| R11 | menor | resources/views/personal/show.blade.php:195 | "Horarios de atención" es un `span`, el título de la lista es un `h2` y el formulario no tiene nombre accesible (1.3.1, DS7) | `h2` y `aria-labelledby` en la sección |
| R12 | menor | resources/views/personal/show.blade.php:213 | El resumen `role="alert"` ya está en el DOM al cargar y el foco no va a él: el anuncio varía según el lector de pantalla | Llevar el foco al resumen o al primer campo con error |
| R13 | menor | resources/views/personal/partials/citas-fuera.blade.php:15 | Todos los enlaces dicen "Ver cita": cumple 2.4.4 (AA) por el contexto del `li`, no 2.4.9 (AAA) | Texto oculto visualmente con el paciente y la fecha |
| R14 | menor | public/css/app.css:438, 443-446 · `.btn-sm` | Objetivos de toque estimados por debajo de 44px (2.5.5, AAA; DS6) | Al resolver DS6, `min-height: var(--touch-min)` |
| R15 | menor | docs/design/system.md:31, 78-80, 102-104, 182-183 | "Campo de formulario" sigue diciendo que `.field-hint` está en `citas.css`, las referencias de línea de `app.css` están obsoletas, DS4 dice "sección de horario sin estilos en línea" cuando quedan los envoltorios previos, y DS1 cita `citas.css:34` | Corregir el texto y las referencias |
| R16 | menor | app/Http/Controllers/PersonalController.php:35, 41, 163, 201, 220, 231, 443, 469 | `DB::raw`/`whereRaw` previos (SQL constante) en un archivo que la spec modifica, sin registrar como deuda (P4, "Código previo") | Registrarlos como deuda |
| R17 | menor | app/Http/Controllers/PersonalController.php:359, 382-400 · show.blade.php:198-207, 225-240 | La validación está en métodos del controlador y no en un Form Request (AGENTS.md lo prefiere), y la vista concilia `old()` con lo guardado en bloques `@php` | Extraer a `GuardarHorarioRequest` y a un view-model |
| R18 | menor | public/css/app.css:437-438 | `--field-time-width` se usa como `min-width` de la etiqueta del día y `--space-6` como tamaño de la casilla | Tokens de tamaño propios, o documentar ese uso en `system.md` |
| R19 | menor | tests/Feature/HorarioDentistaTest.php:322-355 · HorarioDentistaVistasTest.php:216-250 · CitasFueraDelHorarioTest.php:194-222 | Ayudas de test duplicadas en los tres archivos (`ficha`, `crearHorario`, `crearAdministrador`, análisis del DOM) | Moverlas al trait común (ver 025/R11) |
| R20 | menor | `.ai/project.yaml` (`containers`) | En local, trivy recorre `vendor/` y da 9 altas que son falsos positivos (documentación de mockery) | `--skip-dirs vendor` en el comando local, o un `.trivyignore` justificado |

Descartados al verificar:
- La duplicación de la regla de franja entre `ReservaCitas` y `HorarioDentista`: ya está en el roadmap (T041 → spec 013).

## Preparación para release
- Rollback factible: sí. Basta con volver a desplegar la versión anterior: no hay migraciones, y los horarios guardados (`disponible = 1`, `H:i:s`) siguen siendo válidos para la versión anterior. El campo oculto `formulario` lo ignora la versión anterior.
- Migraciones compatibles con la versión anterior: no hay migraciones.
- Feature flags: ninguno (D9, decisión del usuario).
- Docs actualizadas: `architecture.md` (T090), `security.md` (T091, por corrección: RS12.b "en curso"); `deployment.md` sin cambios, porque no hay variables, entornos ni pasos nuevos.
- SCA: 0 críticas o altas sin excepción.
- Cobertura de riesgos: RS12.b está implementada y probada (CA2–CA4). RS12.c sigue "pendiente" con la nota de la 027. Ningún riesgo se declara mitigado antes de la release.

## Tareas añadidas
- T013 ← R1
- T014 ← R5
- T015 ← R2, R3, R6, R7, R10 (tests)
- T024 ← R2
- T025 ← R10
- T042 ← R15
- T043 ← R4

## Aceptados sin tarea
Aceptados por el usuario el 2026-10-03:
- **R8** → roadmap: comprobar `tipo = dentista` y la existencia de la fila dentro de la transacción del horario.
- **R9** → roadmap: una fila heredada con `disponible = 0` sale marcada en la ficha y se normaliza a 1 al guardar.
- **R11** → roadmap: "Horarios de atención" como `h2`, con nombre accesible para el formulario (DS7).
- **R12** → roadmap: llevar el foco al resumen de errores del horario tras el rechazo.
- **R13** → roadmap: texto oculto en los "Ver cita" de la lista de citas fuera (2.4.9, AAA).
- **R14** → roadmap: objetivos de toque de la fila, el campo de hora y "Ver cita" (DS6).
- **R16** → roadmap: `DB::raw`/`whereRaw` previos de `PersonalController` (P4, código previo).
- **R17** → roadmap: validación del horario en un Form Request y estado de la vista en un view-model.
- **R18** → roadmap: tokens de tamaño propios para la casilla y la etiqueta del día.
- **R19** → roadmap: ayudas de test de la 027 al trait común (junto con 025/R11).
- **R20** → roadmap: excluir `vendor/` de trivy en local (falsos positivos de la documentación de mockery).
