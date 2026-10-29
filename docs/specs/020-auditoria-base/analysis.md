---
result: fail
round: 6
mode: full
constitution_version: 1.3.0
date: 2026-10-28
spec_sha: f1ac9373d74b
plan_sha: 6b8581f01a74
tasks_sha: cd972ca92240
---

# Análisis · 020 Registro de auditoría base y correlación por petición

## Resumen
Resultado **fail**: queda 1 hallazgo crítico (A66) y 3 altos (A67–A69). Ninguno exige rehacer el plan:
bastan `/specify --edit 020` (A72), `/plan 020 --fix` y `/tasks 020 --fix`.
- **Motivo del análisis completo:** el plan y las tareas se rehicieron enteros (`--redo`, 916
  líneas cambiadas) y la spec ganó CA14.
- **Ronda 5:** el `--redo` resuelve casi todo (A44, A47, A50–A65 y las notas A40–A43). A45, A46, A48
  y A49 quedan resueltos con efecto nuevo (A66–A70).
- **A66 (crítico):** CA4 se cubre con un test que el código no deja pasar. Los intentos de un paciente
  sobre una cita ajena se responden con `abort(404)` antes de llegar al Gate, así que no dejan
  evento.
- **Método:** subagente independiente (perfil `critical`, `opus`), con los hallazgos principales
  comprobados contra el código: `ClienteCitasController::citaDelPaciente`,
  `ConfiguracionProduccionTest`, `deploy/volver.sh`, `deploy/restaurar.sh` y `capturar.mjs`.
- **Validador:** 0 errores. Avisos: spec grande (decisión del usuario D0) y análisis desactualizado
  (antes de esta ronda).

Ronda anterior: [history/analysis.r5.md](history/analysis.r5.md).

Conteo: CRÍTICA 1 · ALTA 3 · MEDIA 5 · BAJA 5

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 14 / 14 sobre el papel; 13 / 14 en la práctica (CA4, A66). CA5 en producción depende de A67 y A68 |
| Amenazas con control y test | 16 / 16; faltan dos amenazas en el modelo (A78) |
| Principios de la constitución evaluados | 10 / 10. P3 con una exención sin registrar (A73); P2 con tests sin marcar (A75); P8 ❌ con la excepción aceptada (A44), que el `scheduler` sin `local.ini` ampliaría (A67) |

## Seguimiento de rondas anteriores
| ID | Severidad | Estado | Evidencia |
|---|---|---|---|
| A40 | BAJA (aceptado) | resuelto | T011 (`test_el_corte_no_desborda_los_anios_bisiestos`), D13 |
| A41 | BAJA (aceptado) | resuelto | T011 (`SET time_zone = '-06:00'`) |
| A42 | BAJA (aceptado) | resuelto | T034 (listener por descubrimiento, depende de T012, `event:list`) |
| A43 | BAJA (aceptado) | resuelto | `spec.md` → Supuestos, limitación de CA2 en una frase |
| A44 | CRÍTICA | resuelto | `plan.md` P8 «❌ aceptado» con la condición de la 030 (usuario, 2026-10-28) |
| A45 | ALTA | resuelto con efecto nuevo (→ A67, A68) | `scheduler` de producción, T043–T045 |
| A46 | ALTA | resuelto con efecto nuevo (→ A66) | D4 detecta los 404 de los Gates (comprobado en `Handler::prepareException`) |
| A47 | ALTA | resuelto | Constitution Check contra la 1.3.0, con «Cómo se verifica», en el plan y en las tareas |
| A48 | ALTA | resuelto con efecto nuevo (→ A70) | `design.md` aprobado, T017, medición |
| A49 | ALTA | resuelto con efecto nuevo (→ A69) | T033, `aria-current` (T014, T030), T046 |
| A50 | ALTA | resuelto | `spec.md` CA1 y CA10 |
| A51 | MEDIA | resuelto | plan → Contratos, obligación de quien llama; TM10 con residuo |
| A52 | MEDIA | resuelto | D18; T016 (`test_un_fallo_critico_sin_sesion_muestra_el_503`) |
| A53 | MEDIA | resuelto (salvo el camino con copia, → A68) | Rollout → Vuelta atrás; T091 |
| A54 | MEDIA | resuelto | T047 |
| A55 | MEDIA | resuelto (quedan casos sin marcar, → A75) | T012, T013, T014 |
| A56 | MEDIA | resuelto | T011, T034 |
| A57 | MEDIA | resuelto | T095; Rollout → Antes de `/release 020` |
| A58 | BAJA | resuelto (destino) | T048 apunta `/plan 014 --fix` |
| A59 | BAJA | resuelto | T002 retirada |
| A60 | BAJA | resuelto | D16, T018, T038, T039 |
| A61 | BAJA | resuelto | TM11 cita D15; P6 sin RS16 |
| A62 | BAJA | resuelto | D20, T034 |
| A63 | BAJA | resuelto | T001 |
| A64 | BAJA | resuelto | Contratos; T010 |
| A65 | BAJA | resuelto | T048, T049, T091 |

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A66 | Cobertura CA4 / contradicción con el código | CRÍTICA | `plan.md` → D4, Estrategia de pruebas, Trazabilidad CA4; T013, T027; `app/Http/Controllers/ClienteCitasController.php` (`citaDelPaciente`, `show`) | `test_una_cita_ajena_denegada_como_no_encontrada_queda_registrada` usa `reprogramar-cita` con un paciente, pero ese camino no llega al Gate: `citaDelPaciente` filtra por `paciente_id` y hace `abort_unless($cita, 404)` antes (`ReprogramarCitaRolesTest` da 404 sin `AuthorizationException`). El detalle y reprogramar (GET y PATCH) ocultan una cita ajena que existe con un `abort(404)`, que D4 trata como un 404 real: no dejan evento, en contra de CA4. Solo `PATCH …/cancelar` pasa por `denyAsNotFound` | Debe cumplirse: un paciente sobre una cita ajena que existe (detalle, reprogramar GET y PATCH, cancelar) recibe 404 y queda un `denegado_404`; el mismo id inexistente da 404 sin evento. Test: `DenegacionesAuditadasTest` con esas cuatro rutas reales; `ReprogramarCitaRolesTest` y `EstadosCitaRolesTest` en verde. `/plan 020 --fix` (D4, `ClienteCitasController` en Cambios por módulo) y `/tasks 020 --fix` (T013 y una tarea de implementación) |
| A67 | Cobertura CA5 / inconsistencia con la 030 y la 031 | ALTA | `plan.md` → `docker-compose.prod.yml`, `docker-compose.yml`, D2; T019, T040, T043; `ConfiguracionProduccionTest` (`assertVersionExacta`, `assertMontaLocalIni`) | Al `scheduler` le falta `restart: unless-stopped` (030 CA9: tras reiniciar el servidor no vuelve y la purga deja de correr sin aviso). No monta `local.ini` (pierde `zend.exception_ignore_args`, `expose_php`, la zona y los límites de PHP: 030 CA10, 031). Y el `image:` explícito rompe `test_las_imagenes_de_produccion_tienen_version_exacta_e_iguales_a_local` y, con un segundo montaje, `test_el_compose_de_produccion_monta_un_local_ini_que_existe`. Ninguna tarea ajusta estos tests | Debe cumplirse, con test del Compose: el `scheduler` (local y producción) se reinicia solo y aplica la misma configuración de PHP que `app`; los tests de imágenes y de `local.ini` siguen protegiendo lo mismo, ajustados por servicio y sin debilitarse. `/plan 020 --fix` y `/tasks 020 --fix` (T019, T040, T043) |
| A68 | Inconsistencia (vuelta atrás) | ALTA | `plan.md` → `deploy/*.sh`, Rollout; T044; `deploy/volver.sh` (`--sin-levantar`), `deploy/restaurar.sh`; tests de la 032 que fijan `stop app web` y `up -d app web < /dev/null` | Al volver con copia a una versión anterior a la 020, el Compose del checkout no tiene `scheduler`: nombrarlo falla («no such service») y, si no se nombra, el `scheduler` de la 020 sigue en marcha durante el borrado y la carga de la base. `--remove-orphans` solo está en el modo que levanta. El «hecho cuando» de T044 (los tests de la 032 en verde) contradice sus propios cambios, y T019 no actualiza esos tests | Debe cumplirse: con un destino con o sin `scheduler`, `volver.sh --sin-levantar` y `restaurar.sh` paran todo lo que escribe en la base antes de cargar, y al terminar no queda ningún `scheduler` de otra versión. Test: los de la 032, actualizados con ese contrato, con un caso de destino sin `scheduler`. `/plan 020 --fix` y `/tasks 020 --fix` (T019, T044) |
| A69 | Inconsistencia (033–035, `capturar.mjs`) | ALTA | T046; `docs/design/capturar.mjs` (`COMPARADAS` con las pantallas de admin) | `--medir` compara el escritorio a 1024 y 1440 px con la línea base del tag de producción, y con «Auditoría» en la barra lateral todas las pantallas de admin difieren: T046 («sale con código 0») no se puede cumplir | Decidido: excluir de la comparación solo la entrada nueva, como ya se excluyen los campos del filtro. Debe cumplirse: `--medir` sale con 0 y el resto de cada pantalla se sigue comparando. `/plan 020 --fix` (`capturar.mjs` en Cambios por módulo) y `/tasks 020 --fix` (T046 y una tarea que cambia el script) |
| A70 | Ambigüedad (D19) | MEDIA | D19; T037; `layouts/app.blade.php` (`request()->is('admin/…*')`); `medir.mjs` (sin excepción DS1 sobre una URL); `criterios.json` (`menu`, `menu-escritorio`) | En `/_disenio/020/…` ninguna entrada del menú queda `.active`, así que K7 y K7b fallan; la pantalla `menu` necesita el menú abierto, que solo abre `menu.js`; y sobre una URL `medir.mjs` sale con 1 por la barra lateral, así que T037 pide a la vez «pasa» y «los únicos fallos son de la barra» | Debe cumplirse: K7 y K7b se miden sobre el menú real con «Auditoría» marcada y el menú del teléfono abierto, y T037 dice qué salida de `medir.mjs` se acepta. `/plan 020 --fix` (D19) y `/tasks 020 --fix` (T037) |
| A71 | Tareas (dependencias) | MEDIA | T006, T028, T029, T036 | T029 exige tests de T014 que renderizan `auditoria.index`, que no existe hasta T030; T036 exige `test_la_consulta_falla_cerrada…` (T016), que necesita el controlador de T029, y no depende de T029; T006 se verifica con `ReglasCss` de T017, que va después; T028 exige un test de purga que necesita el trigger de T031 | Cada «hecho cuando» se cumple con sus dependencias declaradas. `/tasks 020 --fix` |
| A72 | Inconsistencia (`extends`) | MEDIA | `spec.md` frontmatter | La 020 cambia el despliegue y la vuelta atrás de la 030 (servicios que se levantan y paran) y `verificar.sh` de la 031, que no están en `extends` | Decidido: `extends: [030, 031, 032]`; T098 añade la fila al Historial de cada una. `/specify --edit 020` y `/tasks 020 --fix` (T098) |
| A73 | Constitución (P3) | MEDIA | `plan.md` P3, D19; TM15 | `/_disenio/020/*` es un endpoint sin `auth` ni `role` que pinta el layout de admin; solo existe con `APP_ENV=local`, pero la exención no tiene al usuario como fuente | Decidido: exención de P3 registrada (solo local, datos inventados, sin leer la base). Test: `test_la_vista_previa_no_existe_fuera_de_local` y uno que compruebe que no consulta la base. `/plan 020 --fix` y `/tasks 020 --fix` (T017, T037) |
| A74 | Seguridad / operación | MEDIA | `deploy/aplicar-entorno.sh` (recrea un solo servicio); `deploy/README.md` | Con dos servicios que leen `app.env`, `aplicar-entorno.sh app` deja el `scheduler` con el entorno anterior (p. ej. la contraseña de la base sin rotar). `verificar.sh` lo detecta, pero ni el plan ni la guía lo dicen | Debe cumplirse: tras cambiar `app.env`, «Entorno aplicado» da OK también en el `scheduler` (que el script recree los dos, o que la guía lo diga). `/plan 020 --fix` y `/tasks 020 --fix` (T045) |
| A75 | Constitución (P2) | BAJA | T013 (`test_un_recurso_que_no_existe_no_genera_evento`, `test_una_ruta_inexistente_no_genera_evento`); T018 (`test_un_fallo_de_auditoria_no_cuenta_como_fallo_de_acceso`) | Pasan desde el principio y no están marcados como de caracterización | Marcarlos con «comprobado rompiendo <qué>». `/tasks 020 --fix` |
| A76 | Inconsistencia (Rollout frente a tareas) | BAJA | `plan.md` → Rollout («`desplegar.sh` de la versión instalada»); T097 (checkout antes de `desplegar.sh`) | Con el checkout primero corre el script nuevo, que ya levanta el `scheduler`, y el script informa como «anterior» el propio tag (lo advierte `deploy/README.md` desde la 032) | Alinear el Rollout y T097, y anotar en T097 la versión real a la que volver. `/plan 020 --fix` y `/tasks 020 --fix` |
| A77 | Ambigüedad / conflicto con la 041 | BAJA | `plan.md` → vista 503 («el teléfono de la consulta»); D16 de la 041 (`config/clinica.php → contacto`) | No dice de dónde sale el teléfono; puede chocar con la clave `contacto` de la 041 | Fijar el origen (la misma clave que la 041). Test: el 503 muestra el teléfono configurado. `/plan 020 --fix` y `/tasks 020 --fix` (T016, T036) |
| A78 | Seguridad (modelo de amenazas) | BAJA | `plan.md` → Modelo de amenazas | Faltan dos amenazas: un usuario autenticado que provoca 403 y 404 en masa hace crecer la tabla sin límite; con las credenciales de la app se pueden insertar eventos falsos (CA2 solo cubre modificar y borrar) | Decidido: dos residuos aceptados con su motivo en el modelo de amenazas. `/plan 020 --fix` |
| A79 | Formato de tareas | BAJA | T090, T091 | No tienen archivos ni «hecho cuando» | Añadirlos. `/tasks 020 --fix` |

## Decisiones del usuario
Decididas el 2026-10-28:
- **A66** → corregirlo en la 020: `ClienteCitasController` registra como `denegado_404` la cita ajena
  que existe, con la misma respuesta 404; un id inexistente sigue sin evento.
- **A69** → `capturar.mjs --medir` excluye de la comparación solo el enlace «Auditoría» del menú.
- **A72** → `extends: [030, 031, 032]`.
- **A73 y A78** → se aceptan: la exención de P3 para la vista previa local (solo local, datos
  inventados, sin leer la base) y, como residuos del modelo de amenazas, las denegaciones en masa
  (volumen académico) y la inserción de eventos falsos con las credenciales de la app (mismo límite
  que la limitación aceptada de CA2).

## Correcciones
1. `/specify --edit 020`: A72.
2. `/plan 020 --fix A66,A67,A68,A69,A70,A73,A74,A76,A77,A78`.
3. `/tasks 020 --fix A66,A67,A68,A69,A70,A71,A72,A73,A74,A75,A76,A77,A79`.
4. `/analyze 020` (delta).
