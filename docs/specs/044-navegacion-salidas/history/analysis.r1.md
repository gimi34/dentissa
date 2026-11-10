---
result: fail
round: 1
mode: full
constitution_version: 1.3.0
date: 2026-11-08
spec_sha: 9cfd3dba4499
plan_sha: 295b798f8b48
tasks_sha: 8acb06b9de49
---

# Análisis · 044 Salidas de las pantallas

## Resumen
`fail`: no hay hallazgos críticos, pero hay seis altos que obligarían a rehacer trabajo en `/implement`.
Son estos: la ruta de reserva cambiaría a 405 lo que hoy responde 404 (A1); el 429 de Laravel no lo
produce hoy ningún límite (A2); dos contratos de specs extendidas se rompen sin declararlo (A3, A4); el
logout se modifica sin la excepción P7 registrada (A5); y los umbrales de P9 solo se verifican a mano (A6).
Análisis hecho por un subagente independiente; cada hallazgo alto se comprobó después en el archivo citado.
Conteo: CRÍTICA 0 · ALTA 6 · MEDIA 3 · BAJA 6

## Cobertura
| Métrica | Valor |
|---|---|
| Criterios con tarea de test e implementación | 12 / 12 (CA10 sin test viable por A2; CA11 incompleto por A1) |
| Amenazas con control y test | 7 / 7 (TM3 y TM6 incompletos por A1, A7 y A8) |
| Principios de la constitución evaluados | 10 / 10 (con hallazgo: P2 por A2, P7 por A5, P9 por A6) |

## Seguimiento de rondas anteriores
Primera ronda.

## Hallazgos nuevos
| ID | Categoría | Severidad | Ubicación | Hallazgo | Corrección |
|---|---|---|---|---|---|
| A1 | Inconsistencia | ALTA | plan D3, «Cambios por módulo» (`routes/web.php`); T029; CA11 | `Route::fallback` solo registra GET (`Router::fallback`), y la búsqueda de verbos alternativos sí tiene en cuenta la ruta de reserva. Con ella, un POST, PUT, PATCH o DELETE a una dirección inexistente pasaría a 405 con la página de Laravel. Eso rompe CA11 y `RecuperarContrasenaTest::test_con_el_interruptor_apagado_no_hay_rutas_ni_enlace` (`post(...)->assertNotFound()`, líneas 440 y 443) | `/plan 044 --fix A1`: cualquier verbo a una dirección inexistente sigue respondiendo 404 con la página propia (test nuevo en `PaginasErrorTest` con POST y DELETE; `RecuperarContrasenaTest` sin cambios) |
| A2 | Inconsistencia / Cobertura | ALTA | spec CA10 y Decisiones (supuesto de las páginas de error); T013 (`test_demasiados_intentos_explica_que_esperar`); T031 | Ningún límite de la app produce hoy el 429 de Laravel: `registro` responde `back()->withErrors` (`AppServiceProvider.php:52-56`), el login usa `back()` y `abrir-enlace` ya tiene su propia vista 429. El test descrito recibiría un 302 | Decisión del usuario → `/specify --edit` (CA10 y el supuesto) y `/tasks 044 --fix A2`: el 429 propio se prueba con una ruta de prueba que lance la excepción de límite, y un test confirma que el 429 de `abrir-enlace` no cambia |
| A3 | Inconsistencia (extends 041) | ALTA | T020 (`alt=""` del logo); plan → Descubribilidad («tests de la 041 sin cambios») | `RecuperarContrasenaVistasTest::test_el_acceso_usa_el_estilo_anterior_del_login` (línea 256) exige `//aside[contains(@class,"panel-left")]//img[@alt!=""]` = 1 en cada pantalla de acceso; con el logo-enlace y `alt=""` falla, y ninguna tarea lo cambia | `/plan 044 --fix A3` y `/tasks 044 --fix A3`: una tarea cambia ese test para que exija el nombre accesible del enlace («Ir a la página de inicio»), se declara como cambio del contrato de la 041 y T098 lo anota en su Historial |
| A4 | Inconsistencia (extends 001) | ALTA | spec CA5 y Decisiones; T098 | `001-autenticacion/spec.md:28`, CA5: «usuario autenticado abre `/` → se le redirige al panel», sigue vigente y 044 CA5 lo contradice; la spec solo declara «reemplaza 038 CA2» | `/specify --edit`: CA5 también reemplaza 001 CA5 (fila en Decisiones) · `/tasks 044 --fix A4`: T098 marca 001 CA5 `→ reemplazado por 044 CA5` |
| A5 | Constitución P7 | ALTA | plan → Constitution Check P7; spec → Auditoría | P7 exige auditar los logouts y T026 modifica `logout()`; el plan pone ✅ con «la spec lo deja fuera» sin una excepción registrada con motivo, usuario y destino (precedente: «Excepción P7 de la spec 030», `roadmap.md`) | Decisión del usuario: excepción P7 con destino OB1.b, o el evento de logout dentro de la 044 con su test → `/plan 044 --fix A5` (+ `/tasks` si entra el evento) |
| A6 | Constitución P9 | ALTA | T020–T031, T040; CA1/K1, K5; RNF de accesibilidad | Los umbrales comprobables (toque ≥ 44 px, sin desborde a 320 px, «Volver al inicio» sin desplazarse, sin `style=""` nuevos) solo se verifican a mano. `capturar.mjs --medir` no cubre las siete pantallas de acceso ni las cinco de error | `/plan 044 --fix A6` y `/tasks 044 --fix A6`: comprobación automática de que cada enlace nuevo declara el alto `--touch-min` (patrón de `MovilPacienteTest::assertDeclara`) y de que las vistas tocadas no ganan `style=""` (test sobre las vistas) |
| A7 | Ambigüedad | MEDIA | `design.md` (`.codigo` solo en la 500); plan P8; T033 («el identificador en las cinco páginas») | No queda claro si el identificador va en el cuerpo de todas las páginas de error. En la 404 rompería `DenegacionesAuditadasTest::test_la_cita_ajena_y_la_inexistente_responden_igual` (compara el contenido, líneas 161-169) y debilitaría TM3 | `/plan 044 --fix A7` · `/tasks 044 --fix A7`: cabecera `X-Request-Id` en las cinco páginas; identificador en el cuerpo solo en la 500; cuerpo de la 404 igual en cada petición (test existente y caso de `PaginasErrorTest`) |
| A8 | Cobertura RNF / TM6 | MEDIA | RNF «sin sesión ni base»; T013 (`test_pintar_la_500_no_consulta_la_base`) | El test mide solo la plantilla. En el camino completo, `RegistroCentralDenegaciones::registrar` llama a `Auth::user()` antes de mirar el código; con una sesión con usuario y la base caída podría lanzar dentro de `respond` (así desde la 020) | Decisión del usuario: probar el camino completo, o acotar el RNF a la plantilla y llevar el riesgo al roadmap |
| A9 | Ambigüedad | MEDIA | spec CA6, `design.md` K4; T012, T027 | «Se anuncia a los lectores de pantalla» solo se comprueba con `role="status"`, y una región viva presente en la carga no se anuncia de forma fiable | `/specify --edit`: CA6 dice lo que se mide (mensaje de estado legible por lectores de pantalla) · verificación manual con NVDA en T027 |
| A10 | Inconsistencia | BAJA | plan → Contratos, fila `GET /registro/contacto` con sesión | Hoy `contactoForm()` no comprueba la sesión: con sesión y `google_registro` muestra el formulario. T025 añade un control nuevo | `/plan 044 --fix A10` · `/tasks 044 --fix A10`: el test cubre `/registro/contacto` con y sin `google_registro` |
| A11 | Terminología | BAJA | spec CA9 («Volver al inicio») frente a `design.md`, fila 419, y T031 («Ir a la página de inicio») | El texto literal del enlace difiere según el documento | `/specify --edit` · `/tasks 044 --fix A11`: un solo texto literal en spec, diseño y tests |
| A12 | Cobertura documental | BAJA | T091 | Quedarían desfasados `seo.md` (`/` «con sesión, 302 al panel»; «404 genérica… SE4») y `roadmap.md` (SE4.a en Pendientes) | `/tasks 044 --fix A12`: incluirlos en T091 |
| A13 | Seguridad | BAJA | plan → Modelo de amenazas; D3 | Con la ruta de reserva, cada GET a una dirección inexistente abre sesión (archivo de sesión y `Set-Cookie`); un rastreo de bots crea archivos de sesión | `/plan 044 --fix A13`: fila TM (consumo de recursos) con su control o su aceptación |
| A14 | Inconsistencia | BAJA | T021; CA2 | T021 no exige `alt=""` en la imagen del logo del registro, a diferencia de T020; podría quedar un nombre accesible duplicado | `/tasks 044 --fix A14`: el test de CA2 comprueba en el registro y en completar el registro que el nombre accesible es exactamente «Ir a la página de inicio» |
| A15 | Inconsistencia | BAJA | plan → Constitution Check P1 | Dice «`design.md` y este plan, pendientes de aprobación», pero ya están `approved` (1dc0313) | `/plan 044 --fix A15` |

## Decisiones pendientes del usuario
- **A5 (P7, logout):** (a) registrar una excepción P7 de la 044, con destino OB1.b del objetivo 3 (los
  eventos de login y logout juntos), como la de la 030; (b) añadir a la 044 el evento de auditoría del
  logout con su test.
- **A2 (429):** (a) mantener la página 429 como preventiva, para cualquier límite futuro que use la
  respuesta de Laravel, y reescribir CA10 y el supuesto; (b) quitar la 429 de la spec.
- **A8 (fallo de la base):** (a) acotar el RNF a la plantilla (que no consulta la base) y llevar al
  roadmap el riesgo de la 020 en `RegistroCentralDenegaciones`; (b) probar y arreglar el camino completo
  dentro de la 044.

## Aceptados
Ninguno todavía.
