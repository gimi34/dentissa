---
spec: 046-navegacion-interna
verdict: changes_requested
round: 1
date: 2026-11-15
base: e6c2bbd
head: 53cc84f
human_signoff: pending
---

# Review · 046 Navegación interna

## Resumen
El código cumple los doce criterios y no hay hallazgos bloqueantes: la suite, Pint, el validador y
los escáneres pasan, y los cuatro revisores independientes (spec, constitución y calidad, seguridad,
accesibilidad) no encontraron ningún incumplimiento. Se piden cambios por dos hallazgos importantes
que el usuario decidió corregir antes de liberar (2026-11-15): el test de CA6 solo recorre los
formularios como administración (R1) y la rama quedó por detrás de `main`, que ya tiene la spec 048
en producción (v0.19.0), con un choque en `docs/design/system.md` porque las dos specs tomaron la
versión 1.17.0 del sistema de diseño (R2). El plugin `design` de Anthropic no está habilitado: la
revisión de accesibilidad aplicó la lista propia de `/review`.
Conteo: bloqueante 0 · importante 2 · menor 7

## Verificación automática
| Comando | Resultado |
|---|---|
| `docker compose exec app php artisan test` | ✅ 1242 pasan (17187 aserciones); línea base de `/implement`: 1242 |
| `php artisan test tests/Feature/NavegacionInternaTest.php` (segunda pasada) | ✅ 241 pasan las dos veces; sin intermitentes |
| `vendor/bin/pint --test` sobre los tres tests tocados | ✅ |
| `python .ai/bin/aidd.py validate docs/specs/046-navegacion-interna` | ✅ 0 errores (un aviso heredado de una spec cerrada) |
| `npm run build` | ➖ no se ejecuta: solo afecta a `welcome.blade.php`, sin uso (`.ai/project.yaml`) |
| `node docs/design/capturar.mjs --medir` (línea base v0.18.0, tomada en la misma sesión) | ✅ «todo en orden» en la segunda pasada. La primera dio 10 fallos «escritorio» en las cinco pantallas del paciente (0,03–0,14 % de píxeles, máxima 64/255): al tomar la línea base otra vez desaparecieron, y las dos líneas base, del mismo código, difieren entre sí. Es el defecto conocido de la captura guardada (roadmap, plan D13), no un cambio de la 046. Falta repetirlo contra v0.19.0 tras la fusión (R2) |

## Criterios de aceptación
| CA | Test | Estado | Nota |
|---|---|---|---|
| CA1 | `NavegacionInternaTest::test_cada_pantalla_de_segundo_nivel_lleva_sus_migas`, `::test_las_pantallas_del_menu_no_llevan_migas`; verificación manual OK 2026-11-14 (usuario) | ✅ | La posición dentro del contenido la sostiene la verificación manual; la aserción del test es débil (R3) |
| CA2 | `::test_los_enlaces_de_las_migas_llevan_a_destinos_fijos` | ✅ | |
| CA3 | `::test_las_migas_son_una_navegacion_accesible` | ✅ | Sin otra `nav` con el mismo nombre; foco con el anillo del sistema |
| CA4 | `::test_las_reglas_de_las_migas_distinguen_el_enlace`, `::test_el_contraste_de_las_migas_es_aa` | ✅ | Enlace `--p600` sobre `--bg`: 6,53:1; actual y separador: 19,6:1 |
| CA5 | `::test_reprogramar_lleva_migas_se_pueda_o_no` | ✅ | |
| CA6 | `::test_cancelar_lleva_al_mismo_sitio_que_las_migas` | ⚠ | El comportamiento es correcto en todos los roles; el test solo lo prueba como administración (R1) |
| CA7 | `::test_el_expediente_ya_no_tiene_volver_al_paciente` | ✅ | |
| CA8 | `::test_ninguna_pantalla_usa_el_titulo_por_defecto` | ✅ | |
| CA9 | `::test_el_menu_marca_la_seccion_de_la_pantalla`; `capturar.mjs --medir` | ✅ | |
| CA10 | `::test_las_migas_pueden_pasar_de_linea`; `capturar.mjs --medir` | ✅ | |
| CA11 | TM1, TM2 y TM3 (ver Seguridad) | ✅ | |
| CA12 | `capturar.mjs --medir`, comprobación del escritorio con zonas excluidas | ⚠ | Pasa contra v0.18.0; producción es ahora v0.19.0 y hay que repetirlo tras la fusión (R2) |

## Conformidad con el plan
- Cambios fuera del plan: ninguno (`scope.md`: 22 archivos de código, 0 sin tarea que los cite).
- Partes del plan sin implementar: ninguna. T001, T002 y T032 no aparecen en el diff de código
  porque son la fusión de `main` y cambios en `docs/` (`system.md`, `system.html`, `capturar.mjs`).

## Constitution Check (sobre el código)
| Principio | Resultado | Evidencia |
|---|---|---|
| P1 Spec aprobada antes de código | ✅ | Todos los commits del rango citan su tarea de la 046 |
| P2 Test que falla antes y pasa después | ✅ | `NavegacionInternaTest` (241 casos); caracterizaciones comprobadas rompiendo el código (notas de T031). La de CA6 queda corta de roles (R1) |
| P3 Autorización en el servidor | ✅ | `routes/web.php`, middleware y Gates sin cambios; `::test_una_pantalla_de_otro_rol_responde_como_hoy_sin_migas` |
| P4 Entrada validada y SQL con bindings | ✅ | `partials/migas.blade.php:11,13` solo con `{{ }}`; sin entradas ni consultas nuevas; semgrep 0 hallazgos |
| P5 Nada sensible en el repositorio | ✅ | gitleaks sin hallazgos en los 18 commits; datos sintéticos en los tests |
| P6 Sin vulnerabilidades críticas o altas | ✅ | Sin cambios en lockfiles; `composer audit` y `npm audit` sin críticas ni altas |
| P7 Auditoría | ➖ | Sin accesos, permisos ni acciones nuevas |
| P8 Correlación y logs | ➖ | No añade logs |
| P9 Solo el sistema de diseño | ✅ | Reglas `.migas` solo con tokens; ningún `style=""` (se retiran los de las migas antiguas); acta aplicada en `system.md` y `system.html`. La versión del sistema choca con la de la 048 (R2) |
| P10 Rutas indexables | ➖ | Todas las pantallas exigen sesión |

## Seguridad
| Herramienta | Resultado |
|---|---|
| gitleaks (`e6c2bbd..HEAD`, 18 commits) | ✅ sin hallazgos |
| semgrep `p/php` (21 archivos del rango) | ✅ 0 hallazgos |
| `composer audit --locked` + `npm audit --audit-level=high` | ✅ crít: 0 · alta: 0. Un aviso de gravedad baja en `laravel/framework` (GHSA-63hm-g96r-2m2q, corregido en 12.71.0), ya anotado en el roadmap para un `/chore` |
| trivy `fs --severity HIGH,CRITICAL` | ✅ `composer.lock`: 0. Descartado como falso positivo: 10 avisos en `vendor/mockery/mockery/docs/requirements.txt`, que son los requisitos de Python para construir la documentación de una dependencia de desarrollo; `vendor/` no se versiona, ese archivo no se instala ni se ejecuta y no llega a la imagen de producción |

| Tema OWASP | Resultado | Evidencia |
|---|---|---|
| A01:2025 Control de acceso (TM3) | ✅ | Rutas y permisos sin cambios; 403 o 404 antes de pintar la vista; `NavegacionInternaTest.php:405-423` |
| A01:2025 Redirección abierta (TM1) | ✅ | `href` solo con `route()`; ninguna vista lee la petición ni el `Referer` para un destino; `NavegacionInternaTest.php:362-380` |
| A02:2025 Configuración | ➖ | Sin cambios (ver R9) |
| A03:2025 Cadena de suministro | ✅ | `deps.stat`: sin cambios en lockfiles |
| A04:2025 Datos sensibles | ➖ | Las direcciones solo llevan UUID, como antes; los nombres ya se mostraban en esas pantallas |
| A05:2025 Inyección / XSS (TM2) | ✅ | Salida escapada en texto y destino; `::test_el_nombre_con_codigo_se_pinta_como_texto` |
| A06:2025 Diseño inseguro | ✅ | TM1–TM3 con control y test; CA11 cubierto |
| A07:2025 Autenticación y sesiones | ➖ | No se toca |
| A08:2025 Integridad / CSRF | ➖ | Solo enlaces GET; los formularios conservan `@csrf` |
| A09:2025 Registro y auditoría | ➖ | No aplica (plan, Observabilidad) |
| A10:2025 Errores y condiciones excepcionales | ✅ | La respuesta de acceso denegado sale antes de pintar la vista |
| Proceso | ✅ | Sin dependencias nuevas; de las rutas protegidas solo cambia `AGENTS.md` (T090, aprobado por el usuario); sin instrucciones dirigidas a agentes en el diff |

## Observabilidad
- Eventos de auditoría de la spec emitidos y probados: no aplica (la spec no toca datos sensibles,
  autenticación ni permisos).
- Datos sensibles en logs del código tocado: ninguno (el diff no añade logs).
- `request_id` presente en los logs nuevos: no aplica.

## SEO
No aplica: todas las pantallas de la spec exigen sesión y el diff no toca rutas públicas, sus
layouts, metadatos, `robots.txt` ni el sitemap (plan, «Descubribilidad»). `layouts/app.blade.php` es
el layout del panel, no indexable.

## Hallazgos
| ID | Severidad | Archivo:línea | Hallazgo | Sugerencia |
|---|---|---|---|---|
| R1 | importante | `tests/Feature/NavegacionInternaTest.php:133`, `:305` | El test de CA6 recorre los diez formularios solo como administración (`caso('admin', $pantalla)`). En nueva cita, nuevo y editar paciente y nuevo expediente el destino de «Cancelar» se calcula por rol (`citas/create.blade.php:172`, `pacientes/create.blade.php:203-205`, `expedientes/create.blade.php:127`): si el de la dentista o la asistente apuntara a otra área, el test seguiría en verde. Hoy el código es correcto | Que `formularios()` dé `[rol, pantalla]` desde `PANTALLAS` filtrado por `FORMULARIOS` |
| R2 | importante | `docs/design/system.md:4`, `:437`, `:454`, `:1152` | La spec 048 se liberó el 2026-11-15 (v0.19.0) y la rama no la contiene: `git merge-tree HEAD origin/main` da conflicto en `docs/design/system.md`, y las dos specs tomaron la versión 1.17.0 del sistema de diseño. Sin fusionar no se puede abrir el PR, y la comparación del escritorio (CA12) está hecha contra v0.18.0, que ya no es producción | Fusionar `origin/main`, pasar la 046 a 1.18.0 (decisión del usuario, 2026-11-15) y repetir la suite y `--medir` contra v0.19.0 |
| R3 | menor | `tests/Feature/NavegacionInternaTest.php:188` | «Las migas van en el contenido de la pantalla» es una unión de XPath cuya primera rama (`.//main//*[@id='migas']`) se cumple en cualquier lugar de `<main>`: con las migas al final de la página seguiría pasando. La posición la sostiene hoy la verificación manual de CA1 | Quitar la rama `main` y comprobar que nada con contenido de la vista va antes de `#migas` dentro de `.content` |
| R4 | menor | `public/css/movil.css:268-272` | `.migas { flex-wrap: wrap; }` ya no hace nada (`.migas` es una `nav` en bloque; quien pasa de línea es `.migas ol`, `app.css:629`) y las reglas de salto de las líneas 268 y 271 repiten las de `app.css`. El plan lo declara redundante (D6) porque las fija `MovilPacientesExpedientesTest` | Quitarlas y ajustar ese test cuando se toque `movil.css` |
| R5 | menor | `public/css/app.css:626` | Queda el comentario antiguo «Migas de pan del detalle de la cita y de agendar (T025)»; «Agendar» ya no lleva migas | Borrar la línea |
| R6 | menor | `tests/Feature/NavegacionInternaTest.php:420` | La caracterización de TM3 busca que no aparezcan los nombres de `paciente1` y `dentista1`, pero no el de `paciente2`, que es el dato protegido en «ficha del usuario». Lo que protege de verdad es el código de estado y la ausencia de `#migas` | Añadir `paciente2` a la lista de nombres |
| R7 | menor | `tests/Feature/NavegacionInternaTest.php:245` | `range(1, $elementos->length - 1)` con un camino de un solo elemento daría `[1, 0]` y un error en lugar de un fallo legible. Hoy todos los caminos tienen dos o más | Usar un `for` |
| R8 | menor | `resources/views/citas/create.blade.php:16`, `citas/show.blade.php`, `citas/reprogramar.blade.php`, `partials/migas.blade.php:11-13`, `pacientes/create.blade.php`, `tests/Feature/MovilPacienteTest.php:463` | Duplicación: el texto «Mis Citas»/«Citas» por rol está copiado en tres vistas y el prefijo del rol se recalcula en `reprogramar`; el separador se repite en las dos ramas del parcial; el nombre del paciente se compone de dos formas; queda un `foreach` sobre una lista de un solo elemento | Un helper para el texto y el prefijo; sacar el separador antes del `@if`; `nombre_completo` en las dos vistas; quitar el bucle |
| R9 | menor | `resources/views/partials/migas.blade.php:13`, `bootstrap/app.php:29` | Anterior a la 046: `route()` genera direcciones absolutas con el `Host` de la petición y la aplicación no activa `trustHosts`. Sin vector práctico (un navegador no deja falsear el `Host` de otra persona; las pantallas exigen sesión y no se guardan en caché) | Activar `trustHosts()` en una spec o ficha de seguridad |

## Preparación para release
- Rollback factible: sí. Una sola imagen, sin migraciones ni datos que revertir; volver a la imagen
  anterior con `deploy/volver.sh` (plan, Rollout).
- Migraciones compatibles con la versión anterior: no hay migraciones.
- Feature flags: el plan no pide ninguno.
- Docs actualizadas (architecture, deployment): `docs/architecture.md` y `AGENTS.md` actualizados
  (T090); `deployment.md` y `observability.md` sin cambios, como dice el plan (T091).
- Vulnerabilidades críticas o altas de SCA sin excepción: ninguna.
- Cobertura de riesgos: la spec no declara correcciones de riesgos `RS`, `OB`, `RD` ni `SE`; ningún
  estado cambia en `security.md`, `observability.md` ni `deployment.md`.
- Pendiente antes de abrir el PR: la fusión de `main` con la 048 (R2, T033) y la verificación
  posterior (T035).

## Tareas añadidas
- T033 ← R2 (fusionar `origin/main` con la 048; la 046 pasa al sistema de diseño 1.18.0)
- T034 ← R1 (el test de «Cancelar» con todos los roles de cada formulario)
- T035 ← R2 (verificación tras las correcciones, con `--medir` contra v0.19.0)

## Aceptados sin tarea
- **R3** → roadmap: la aserción de posición de las migas de `NavegacionInternaTest` se cumple en cualquier lugar de `<main>`; comprobar que van antes del contenido de la vista
- **R4** → roadmap: reglas de `.migas` sin efecto o repetidas en `movil.css` (las fija el test de la 035); quitarlas al tocar ese archivo
- **R5** → roadmap: comentario antiguo de las migas en `app.css:626`
- **R6** → roadmap: la caracterización de TM3 no busca el nombre de `paciente2`
- **R7** → roadmap: `range()` frágil con caminos de un solo elemento en el test de accesibilidad de las migas
- **R8** → roadmap: duplicación en las vistas de citas y pacientes y en el parcial de migas (texto por rol, prefijo, separador, nombre compuesto) y un bucle de un solo elemento en `MovilPacienteTest`
- **R9** → roadmap: `trustHosts` sin activar (anterior a la 046), para una spec o ficha de seguridad
