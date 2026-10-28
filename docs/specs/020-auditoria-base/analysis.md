---
result: fail
round: 5
mode: full
constitution_version: 1.3.0
date: 2026-10-28
spec_sha: d0b4451c216c
plan_sha: d196df033b14
tasks_sha: 81cb936ea640
---

# Análisis · 020 Registro de auditoría base y correlación por petición

## Resumen
Resultado **fail**, con recomendación de rehacer el plan (`--redo`).
- **Motivo del análisis completo:** el plan y las tareas se evaluaron con la constitución 1.0.0
  (2026-09-28), y hoy rige la 1.3.0. Además, `main` ha cambiado mucho desde entonces: producción
  (030–032), vigilante, Gates de la 026–028, menú del móvil (033–035) y la 041, que depende de la
  020.
- **Hallazgos:** 1 crítico (A44: el residuo de OB4.a se aceptó con una condición que ya no se
  cumple) y 6 altos. Varios no caben en una corrección localizada y exigen editar la spec, pasar por
  `/design-spec 020` y rehacer el plan:
  - el `scheduler` y el vigilante en producción (A45);
  - el diseño de la pantalla de consulta (A48);
  - el actor explícito frente a CA1 y CA10 (A50);
  - el Constitution Check contra la 1.3.0 (A47).
- **Actor explícito:** la D15 (añadida hoy para la 041) cumple la D2 de la 041 y se puede usar. Solo
  pide declarar la obligación de quien llama (A51) y alinear CA10 en la spec (A50).
- **Método:** subagente independiente (perfil `critical`, `opus`), con los hallazgos principales
  comprobados contra el código.
- **Validador:** 0 errores. Avisos: el plan cita OB4 y RS16 y las tareas citan RS16, que la spec no
  cita.

Ronda anterior: [history/analysis.r4.md](history/analysis.r4.md).

Conteo: CRÍTICA 1 · ALTA 6 · MEDIA 7 · BAJA 8

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 13 / 13 sobre el papel; 11 / 13 en la práctica (CA4 sin las denegaciones 404 de los Gates de `main`, A46; CA5 sin purga en producción, A45) |
| Amenazas con control y test | 13 / 13 sobre el papel; el control de TM10 promete más de lo que la 020 puede probar (A51); el `scheduler` de producción será una superficie nueva sin amenaza (A45) |
| Principios de la constitución evaluados | 10 / 10 en esta revisión (el plan evalúa 8: P9 «pendiente» y sin P10). P8 ❌ (A44), P9 ❌ (A48); el resto ✅ con las observaciones de A47 y A55 |

## Seguimiento de rondas anteriores
| ID | Severidad | Estado | Evidencia |
|---|---|---|---|
| A40 | BAJA (aceptado) | abierto como nota, sin trasladar (→ A56) | `tasks.md` T028 y T011 no recogen la nota |
| A41 | BAJA (aceptado) | abierto como nota, sin trasladar (→ A56) | T011 sin la nota |
| A42 | BAJA (aceptado) | abierto como nota, sin trasladar (→ A56) | T023 sin la nota ni la dependencia de T012 |
| A43 | BAJA (aceptado) | pendiente en la spec (→ A50) | `spec.md` → Supuestos sin la frase única |

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A44 | Cobertura de riesgos / Constitución (P8) | CRÍTICA | `plan.md` → Constitution Check P8 y Riesgos (residuo de OB4.a); `docs/observability.md` → OB4 | El plan acepta que la consulta o la purga fallidas dejen el SQL con sus bindings en el log porque «la 023 debe liberarse antes de producción». Esa condición ya no se cumple: hay producción desde v0.5.0 sin la 023, y la 030 aceptó OB4.a con otra condición («la 023 antes de tener datos reales»). | Decisión del usuario: aceptar de nuevo el residuo con la condición vigente (antes de datos reales, OB4.a), o que la consulta y la purga fallidas no dejen bindings en el log (test con captura del log en esos dos fallos). Va en el `--redo` |
| A45 | Inconsistencia (deriva) / Cobertura CA5, OB1.g | ALTA | `plan.md` → Cambios por módulo (`docker-compose.yml`, servicio `scheduler`), D2, Rollout; T040; `docs/roadmap.md` (notas de la 032) | El `scheduler` solo se planifica en el Compose local: la purga mensual nunca correría en producción y CA5 y OB1.g no se cumplirían allí. El roadmap pide además: el `scheduler` en `docker-compose.prod.yml`, mover `dentissa:vigilar` a él (cada minuto, sin solaparse) y retirar el temporizador `dentissa-vigilar`. El plan no considera lo que eso toca: `deploy/preparar-vigilancia.sh`, `verificar.sh`, `volver.sh`, los tests de la 030–032 y `docs/deployment.md`. Falta también la vuelta a una versión anterior a la 020, que dejaría producción sin vigilante. | Debe cumplirse en producción: un test del Compose de producción comprueba que hay un `scheduler` que lee solo `app.env` y monta los logs del vigilante; `schedule:list` da `auditoria:purgar` mensual y `dentissa:vigilar` cada minuto sin solaparse; `verificar.sh` comprueba el `scheduler`. Los tests de la 030–032 se actualizan sin debilitarse, y el Rollout dice cómo volver atrás sin perder los avisos. **Redo** (o una spec propia para el traslado del vigilante, decisión del usuario) |
| A46 | Inconsistencia (deriva) / Cobertura CA4, P7 | ALTA | `plan.md` → registro central de denegaciones, D4; `app/Autorizacion/CambiarEstadoCita.php:46`, `GestionPersonal.php:25` (`denyAsNotFound`) | Los Gates de `main` (026–028) deniegan con 404. El registro central solo registra los 403, y `denegado_404` solo sale de `denegar()`, así que el intento horizontal más grave (un paciente sobre la cita de otro) queda sin evento. CA4 no se cumple, aunque el roadmap asigna a la 020 las excepciones P7 de la 025, la 026 y la 028. | CA4 con los Gates existentes, también cuando deniegan con 404, distinguiéndolos de un 404 real. Tests sobre rutas reales: cita ajena → `denegado_404`; dentista fuera de alcance → `denegado`; ruta inexistente → sin evento. T090 y T091 cierran esas excepciones en el roadmap |
| A47 | Constitución (formato y versión) | ALTA | `plan.md` frontmatter (`constitution_version: 1.0.0`) y Constitution Check; `tasks.md` → Constitution Check | El Check está evaluado contra la 1.0.0. No tiene la columna «Cómo se verifica» que exige el contrato, deja P9 «➖ pendiente», no tiene fila P10 y no aplica P2 1.2.x. P4, P5 y P6 no citan semgrep, gitleaks ni los audit del CI. | Volver a evaluar los diez principios contra la 1.3.0, con `test:`, `lint:` o `manual:` por fila, en el plan y en las tareas. **Redo** |
| A48 | Constitución (P9) / contrato de diseño | ALTA | carpeta de la spec sin `design.md`; `.ai/project.yaml` (`design.status: approved`); `plan.md` (delega en la skill `design`, que no está instalada) | La spec tiene interfaz: `/admin/auditoria`, la página 503 y la entrada del menú. No hay acta de diseño, ni CSS en «Cambios por módulo», ni tokens, WCAG o el móvil (320, 390 y 1440 px), ni un test sin literales ni `style=""`. Tampoco se recoge RS8.d (sin estilos ni scripts en línea nuevos). | `/specify --edit 020` con «Diseño» y «Criterios de diseño», y `/design-spec 020` antes de planificar. P9 ✅ verificado con test (solo tokens, sin `style=""`, contraste) y `capturar.mjs --medir` a 320 px. **Redo** |
| A49 | Inconsistencia (deriva del menú, 033–035) | ALTA | `plan.md` → layout (entrada «Auditoría»); T030; `tests/Feature/MovilPacienteTest.php:37-38` (`SECCIONES['admin']`, lista exacta); `AGENTS.md` (regla de `capturar.mjs`) | Añadir «Auditoría» rompe `test_el_menu_del_movil_tiene_las_mismas_secciones`. Ninguna tarea sigue el patrón `.active` + `aria-current="page"` ni ejecuta `capturar.mjs --medir`, obligatorio al tocar `layouts/app.blade.php`. | Test: en `/admin/auditoria`, la entrada está activa y lleva `aria-current`; `SECCIONES` se actualiza; la verificación final ejecuta `capturar.mjs --medir` |
| A50 | Inconsistencia plan vs spec (D15) | ALTA | `plan.md` → actor explícito y D15; `spec.md` CA1 y CA10 | D15 cambia el comportamiento sin editar la spec. Con sesión y actor explícito, el evento lleva otro usuario mientras los logs conservan el de la sesión, en contra de CA10. Sin sesión, guarda un actor y descarta el identificador intentado que CA1 prevé. | `/specify --edit 020`: cuándo se registra un actor comprobado por otra credencial y cómo aplica CA10 en ese caso, e incluir A43. Después se ajusta `test_el_actor_explicito_prevalece_sobre_la_sesion` |
| A51 | Seguridad (D15 frente a la D2 de la 041) | MEDIA | `plan.md` → actor explícito y TM10; plan de la 041, D2 | La regla «nunca un identificador de la petición sin validar» no la puede garantizar la 020: `Usuario::find($request->input('cuenta'))` es un `Usuario` guardado y pasaría. El control de TM10 («comprobado con una credencial») no tiene test en la 020. | Declararlo como obligación de quien llama y residuo de TM10. Cada spec que use `actor:` (041, y después 021 y 022) lleva un test con el identificador de otra cuenta en la petición, y el evento lleva el usuario verificado. `/review` revisa cada llamada |
| A52 | Ambigüedad | MEDIA | `plan.md` → página 503 del modo crítico; plan de la 041 (D5, `POST /contrasena/nueva`) | La 041 mostrará el 503 de la 020 en una ruta pública sin sesión, y el plan no dice si la página depende del layout autenticado. | La página 503 se muestra sin sesión, con el `request_id` y sin recursos externos. Test: fallo en modo crítico en una ruta anónima → 503 genérico, nunca 500 |
| A53 | Inconsistencia (deriva de la vuelta atrás) | MEDIA | `plan.md` → Rollout; T091; `deploy/desplegar.sh`, `deploy/restaurar.sh`; `docs/deployment.md` | Con una migración pendiente, `desplegar.sh` propondrá volver con la copia, y `restaurar.sh` borra la base: se perderían los eventos escritos desde el despliegue. | Rollout y `deployment.md`: la vuelta atrás de la 020 es `volver.sh` sin copia (migración solo de expansión), y se declara la pérdida de eventos si la copia es inevitable |
| A54 | Deriva / Seguridad | MEDIA | `docs/roadmap.md` (triggers con `dentissa_app`); `phpunit.xml`; CI; `plan.md` → Riesgos («al elegir plataforma, objetivo 5») | Los tests y la CI crean los triggers con root, pero el roadmap pide probarlos con `dentissa_app`. La plataforma ya se eligió en la 030. | Comprobar que la migración crea los triggers con `dentissa_app` y que ese usuario recibe el rechazo de `UPDATE` y `DELETE`, en un entorno con el Compose de producción; anotarlo en la tarea de verificación |
| A55 | Constitución (P2 1.2.x) | MEDIA | `plan.md` → Trazabilidad; T012–T014 | Tres tests pasan desde el principio y no están marcados como de caracterización: `test_no_existen_rutas_de_modificacion`, `test_un_visitante_sin_sesion_no_genera_evento` y `test_un_identificador_de_admin_reutilizado_no_da_acceso`. | Marcarlos y exigir «comprobado rompiendo <qué>» |
| A56 | Cobertura (seguimiento) | MEDIA | `tasks.md` T011, T023, T028; ronda 4 (A40–A42) | Las notas aceptadas en la ronda 4 no están en las tareas, y A40 es un fallo funcional de la purga. | Pasarlas al «hecho cuando»: corte con `setTestNow('2028-02-29 00:00:00.500')` → `2023-02-28 00:00:00`; `time_zone '-06:00'` en el test del corte; T023 depende de T012 |
| A57 | Cobertura (Rollout) | MEDIA | `docs/roadmap.md` (comprobaciones previas a `/release 020`); T095–T098; ramas `feature/020-auditoria-base` (vieja) y `feature/020-auditoria` | Las comprobaciones que pide el roadmap antes de `/release 020` no están en las tareas: el merge de la 024 como ancestro, y `composer audit` y `npm audit` con 0 críticas y 0 altas. La rama vieja con copias de la 024 sigue existiendo. | Una tarea de liberación con esos comandos y su resultado. Declarar que la rama vieja no se usa (la 020 se reconstruye en `feature/020-auditoria` desde `main`, decisión del usuario, 2026-10-28) |
| A58 | Duplicación / conflicto con la 014 | BAJA | plan de la 014 (A06, A09, A26 abiertos) | La 014 sigue con `Auditoria::denegacion`, `denegado_403`, `Gate::after` y la marca en el `Context`, y no conoce los Gates de la 026–028 ni el actor explícito. No bloquea la 020. | → roadmap: `/plan 014 --fix` antes de `/tasks 014`, con el contrato final de la 020 |
| A59 | Deriva (arnés de tests) | BAJA | T002; D8; `docs/deployment.md`; CI | T002 hace que `bootstrap-test-db.sh` migre, pero hoy la migración es un paso aparte documentado y la CI ya migra. | Quitar T002 o actualizar la documentación de forma coherente |
| A60 | Observabilidad | BAJA | `plan.md` → Observabilidad («`auditoria.fallo` es la señal a vigilar»); `docs/observability.md` (OB6.b mitigada) | En producción nadie lee `laravel.log`, y el vigilante ya avisa por correo: los fallos no críticos pasarían inadvertidos. | Decidir si el vigilante avisa de `auditoria.fallo` o aceptarlo de forma explícita |
| A61 | Inconsistencia (terminología) | BAJA | `plan.md` → TM11; Constitution Check P6 | TM11 sigue diciendo «el actor sale de `Auth::user()`», sin D15. P6 dice que RS16 «se atiende antes de cualquier release», pero RS16 está mitigado desde v0.1.0. | Alinear el texto (audit de la CI y EX2) |
| A62 | Inconsistencia (convención) | BAJA | `plan.md` → `AppServiceProvider` (listeners de `Login` y `Logout`); T023; `app/Listeners/RegistrarFalloAcceso.php`; plan de la 041 (D4) | Los listeners van en `AppServiceProvider`, mientras que `main` usa clases en `app/Listeners` con descubrimiento automático; la 041 añade otro listener de `Login`. Mezclar los dos mecanismos puede registrar uno dos veces. | Un solo mecanismo, con un test de que cada listener se ejecuta una vez |
| A63 | Inconsistencia | BAJA | `plan.md` → migración `2026_09_28_000000` | Su fecha es anterior a migraciones ya aplicadas en producción (2026-10-02 y 2026-10-07). | Darle la fecha de la implementación |
| A64 | Ambigüedad | BAJA | `plan.md` → actor explícito | Si se pasan a la vez `actor:` e `identificadorIntentado`, el identificador se descarta en silencio. | Definir si es un error de programación (`InvalidArgumentException`), con test |
| A65 | Cobertura (documentación) | BAJA | T090, T091; `docs/roadmap.md`; `docs/observability.md` → Resumen; `AGENTS.md` | Las tareas de documentación no cierran en el roadmap las excepciones P7 de la 025, la 026 y la 028, no actualizan el resumen de `observability.md` ni el mapa de `AGENTS.md` (ruta protegida, con aprobación). | Añadirlo a las tareas de documentación |

## Decisiones del usuario
Decididas el 2026-10-28:
- **Camino** → rehacer la 020: `/specify --edit 020` (A50, A43, «Diseño» y «Criterios de
  diseño»), `/design-spec 020` (A48), `/plan 020 --redo` con la constitución 1.3.0 (resto de
  hallazgos), `/tasks 020 --redo` y `/analyze 020`. El traslado de `dentissa:vigilar` al `scheduler`
  de producción y la retirada del temporizador van en una **spec propia**. La 020 solo lleva el
  `scheduler` a producción con la purga mensual (A45), y el Rollout dice cómo conviven el
  `scheduler` y el temporizador hasta esa spec.
- **A44** → se acepta de nuevo el residuo de OB4.a con la condición vigente (la de la 030: datos
  ficticios, logs solo en el servidor y la 023 antes de tener datos reales). El `--redo` lo registra
  en el plan con esta fecha.
- **A60** → el vigilante de la 032 avisa por correo de `auditoria.fallo`, con su mismo límite de un
  correo por tipo cada 30 minutos. Va en el `--redo` (cambia `App\Avisos\Vigilante`).

## Aceptados
De la ronda 4 (aceptados por el usuario el 2026-09-28), aún sin trasladar a las tareas (A56):
- **A40** → nota de T028, T011: calcular el corte sin desbordamiento de años y truncado al segundo (o leerlo de la BD con `UTC_TIMESTAMP() - INTERVAL 5 YEAR`), de modo que nunca sea posterior al umbral del trigger. Test en `InmutabilidadAuditoriaTest`: con `Carbon::setTestNow('2028-02-29 00:00:00.500')`, el corte es `2023-02-28 00:00:00`.
- **A41** → nota de T011: `test_la_purga_respeta_el_corte_al_segundo` fija en su sesión una zona distinta de UTC (p. ej. `SET time_zone = '-06:00'`) antes de purgar, y así falla contra un trigger que use `NOW()`.
- **A42** → nota de T023: tratar T012 como dependencia (su test cubre los listeners) y comprobar al cerrar T023 que hay listeners registrados para `Login` y `Logout`.
- **A43** → spec: en la próxima edición de la spec 020, enumerar las tres exclusiones de CA2 en una sola frase de Supuestos (vaciar el almacén, desactivar su protección y cambiar su estructura). CA2 ya es correcto y manda.
