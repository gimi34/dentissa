---
spec: 046-navegacion-interna
verdict: approved
round: 2
date: 2026-11-16
base: 53cc84f
head: 4e4adbb
human_signoff: pending
---

# Review · 046 Navegación interna

## Resumen
Aprobada, a falta de la confirmación del usuario (`human_signoff`). Los dos hallazgos importantes de
la ronda 1 están corregidos: la rama contiene la v0.19.0 con la 046 en la versión 1.18.0 del sistema
de diseño (R2, T033 y T035) y el test de «Cancelar» recorre cada formulario con todos sus roles (R1,
T034). Durante la verificación apareció un defecto ajeno a la 046, `capturar.mjs --medir` no medía
bien el componente de la 048, y se corrigió en el guion por decisión del usuario (R10, T036). El
diff propio de esta ronda son 7 líneas de test y 16 del guion de capturas, revisadas sin subagentes
(`--rerun`); el resto del rango es la spec 048, ya revisada y liberada. Los siete menores de la
ronda 1 siguen aceptados al roadmap. El plugin `design` de Anthropic no está habilitado.
Conteo: bloqueante 0 · importante 0 abiertos (2 corregidos) · menor 7 aceptados (1 corregido)

## Verificación automática
| Comando | Resultado |
|---|---|
| `docker compose exec app php artisan test` | ✅ 1257 pasan (17407 aserciones) sobre `af62873`, ya con la 048 fusionada; los commits posteriores solo tocan `docs/` |
| `php artisan test tests/Feature/NavegacionInternaTest.php` (segunda pasada) | ✅ 246 pasan las dos veces |
| `vendor/bin/pint --test tests/Feature/NavegacionInternaTest.php` | ✅ |
| `python .ai/bin/aidd.py validate docs/specs/046-navegacion-interna` | ✅ 0 errores |
| `npm run build` | ➖ no se ejecuta: solo afecta a `welcome.blade.php`, sin uso (`.ai/project.yaml`) |
| `node docs/design/capturar.mjs --medir` (línea base v0.19.0, tomada en la misma sesión) | ✅ «todo en orden» en una pasada completa con el guion de T036. Antes, dos pasadas con otras líneas base dieron solo fallos «escritorio» de 0,03–0,14 % en pantallas de un rol distinto cada vez: el defecto conocido de la captura guardada (roadmap, plan D13). Control positivo de T036: con la regla `::after` de la 048 quitada vuelven los 27 fallos |

## Criterios de aceptación
| CA | Test | Estado | Nota |
|---|---|---|---|
| CA1 | `NavegacionInternaTest::test_cada_pantalla_de_segundo_nivel_lleva_sus_migas`, `::test_las_pantallas_del_menu_no_llevan_migas`; verificación manual OK 2026-11-14 (usuario) | ✅ | La posición dentro del contenido la sostiene la verificación manual; la aserción del test es débil (R3) |
| CA2 | `::test_los_enlaces_de_las_migas_llevan_a_destinos_fijos` | ✅ | |
| CA3 | `::test_las_migas_son_una_navegacion_accesible` | ✅ | Sin otra `nav` con el mismo nombre; foco con el anillo del sistema |
| CA4 | `::test_las_reglas_de_las_migas_distinguen_el_enlace`, `::test_el_contraste_de_las_migas_es_aa` | ✅ | Enlace `--p600` sobre `--bg`: 6,53:1; actual y separador: 19,6:1 |
| CA5 | `::test_reprogramar_lleva_migas_se_pueda_o_no` | ✅ | |
| CA6 | `::test_cancelar_lleva_al_mismo_sitio_que_las_migas` | ✅ | Desde T034, 15 casos: cada formulario con todos los roles que lo abren (R1 corregido) |
| CA7 | `::test_el_expediente_ya_no_tiene_volver_al_paciente` | ✅ | |
| CA8 | `::test_ninguna_pantalla_usa_el_titulo_por_defecto` | ✅ | |
| CA9 | `::test_el_menu_marca_la_seccion_de_la_pantalla`; `capturar.mjs --medir` | ✅ | |
| CA10 | `::test_las_migas_pueden_pasar_de_linea`; `capturar.mjs --medir` | ✅ | |
| CA11 | TM1, TM2 y TM3 (ver Seguridad) | ✅ | |
| CA12 | `capturar.mjs --medir`, comprobación del escritorio con zonas excluidas | ✅ | Pasa contra la línea base de v0.19.0, la versión en producción (T035; R2 corregido) |

## Conformidad con el plan
- Cambios fuera del plan: ninguno propio. `scope.md` lista seis archivos sin tarea
  (`dashboard/*.blade.php`, `CitasPanelDetalleTest`, `MovilPersonalTest`): son de la spec 048 y
  entran por la fusión de `main` (T033).
- Partes del plan sin implementar: ninguna. T033 no aparece en el diff de código porque solo toca
  `docs/`.
- `docs/design/capturar.mjs` recibe en T036 un cambio que el plan no preveía (medir el componente de
  la 048), añadido durante `/implement` por decisión del usuario (2026-11-16).

## Constitution Check (sobre el código)
| Principio | Resultado | Evidencia |
|---|---|---|
| P1 Spec aprobada antes de código | ✅ | Todos los commits del rango citan su tarea de la 046 |
| P2 Test que falla antes y pasa después | ✅ | `NavegacionInternaTest` (246 casos); caracterizaciones comprobadas rompiendo el código (notas de T031 y T034) |
| P3 Autorización en el servidor | ✅ | `routes/web.php`, middleware y Gates sin cambios; `::test_una_pantalla_de_otro_rol_responde_como_hoy_sin_migas` |
| P4 Entrada validada y SQL con bindings | ✅ | `partials/migas.blade.php:11,13` solo con `{{ }}`; sin entradas ni consultas nuevas; semgrep 0 hallazgos |
| P5 Nada sensible en el repositorio | ✅ | gitleaks sin hallazgos en el rango; datos sintéticos en los tests |
| P6 Sin vulnerabilidades críticas o altas | ✅ | Sin cambios en lockfiles; `composer audit` y `npm audit` sin críticas ni altas |
| P7 Auditoría | ➖ | Sin accesos, permisos ni acciones nuevas |
| P8 Correlación y logs | ➖ | No añade logs |
| P9 Solo el sistema de diseño | ✅ | Reglas `.migas` solo con tokens; ningún `style=""` (se retiran los de las migas antiguas); acta aplicada en `system.md` y `system.html`. La 046 queda en la versión 1.18.0 del sistema (T033) |
| P10 Rutas indexables | ➖ | Todas las pantallas exigen sesión |

## Seguridad
| Herramienta | Resultado |
|---|---|
| gitleaks (`53cc84f..HEAD`, los commits de esta ronda y los de la 048 fusionada) | ✅ sin hallazgos |
| semgrep `p/php` (27 archivos de `e6c2bbd..HEAD`) | ✅ 0 hallazgos |
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
| R1 | importante (corregido, T034) | `tests/Feature/NavegacionInternaTest.php:133`, `:305` | El test de CA6 recorre los diez formularios solo como administración (`caso('admin', $pantalla)`). En nueva cita, nuevo y editar paciente y nuevo expediente el destino de «Cancelar» se calcula por rol (`citas/create.blade.php:172`, `pacientes/create.blade.php:203-205`, `expedientes/create.blade.php:127`): si el de la dentista o la asistente apuntara a otra área, el test seguiría en verde. Hoy el código es correcto | Que `formularios()` dé `[rol, pantalla]` desde `PANTALLAS` filtrado por `FORMULARIOS` |
| R2 | importante (corregido, T033 y T035) | `docs/design/system.md:4`, `:437`, `:454`, `:1152` | La spec 048 se liberó el 2026-11-15 (v0.19.0) y la rama no la contiene: `git merge-tree HEAD origin/main` da conflicto en `docs/design/system.md`, y las dos specs tomaron la versión 1.17.0 del sistema de diseño. Sin fusionar no se puede abrir el PR, y la comparación del escritorio (CA12) está hecha contra v0.18.0, que ya no es producción | Fusionar `origin/main`, pasar la 046 a 1.18.0 (decisión del usuario, 2026-11-15) y repetir la suite y `--medir` contra v0.19.0 |
| R3 | menor | `tests/Feature/NavegacionInternaTest.php:188` | «Las migas van en el contenido de la pantalla» es una unión de XPath cuya primera rama (`.//main//*[@id='migas']`) se cumple en cualquier lugar de `<main>`: con las migas al final de la página seguiría pasando. La posición la sostiene hoy la verificación manual de CA1 | Quitar la rama `main` y comprobar que nada con contenido de la vista va antes de `#migas` dentro de `.content` |
| R4 | menor | `public/css/movil.css:268-272` | `.migas { flex-wrap: wrap; }` ya no hace nada (`.migas` es una `nav` en bloque; quien pasa de línea es `.migas ol`, `app.css:629`) y las reglas de salto de las líneas 268 y 271 repiten las de `app.css`. El plan lo declara redundante (D6) porque las fija `MovilPacientesExpedientesTest` | Quitarlas y ajustar ese test cuando se toque `movil.css` |
| R5 | menor | `public/css/app.css:626` | Queda el comentario antiguo «Migas de pan del detalle de la cita y de agendar (T025)»; «Agendar» ya no lleva migas | Borrar la línea |
| R6 | menor | `tests/Feature/NavegacionInternaTest.php:420` | La caracterización de TM3 busca que no aparezcan los nombres de `paciente1` y `dentista1`, pero no el de `paciente2`, que es el dato protegido en «ficha del usuario». Lo que protege de verdad es el código de estado y la ausencia de `#migas` | Añadir `paciente2` a la lista de nombres |
| R7 | menor | `tests/Feature/NavegacionInternaTest.php:245` | `range(1, $elementos->length - 1)` con un camino de un solo elemento daría `[1, 0]` y un error en lugar de un fallo legible. Hoy todos los caminos tienen dos o más | Usar un `for` |
| R8 | menor | `resources/views/citas/create.blade.php:16`, `citas/show.blade.php`, `citas/reprogramar.blade.php`, `partials/migas.blade.php:11-13`, `pacientes/create.blade.php`, `tests/Feature/MovilPacienteTest.php:463` | Duplicación: el texto «Mis Citas»/«Citas» por rol está copiado en tres vistas y el prefijo del rol se recalcula en `reprogramar`; el separador se repite en las dos ramas del parcial; el nombre del paciente se compone de dos formas; queda un `foreach` sobre una lista de un solo elemento | Un helper para el texto y el prefijo; sacar el separador antes del `@if`; `nombre_completo` en las dos vistas; quitar el bucle |
| R9 | menor | `resources/views/partials/migas.blade.php:13`, `bootstrap/app.php:29` | Anterior a la 046: `route()` genera direcciones absolutas con el `Host` de la petición y la aplicación no activa `trustHosts`. Sin vector práctico (un navegador no deja falsear el `Host` de otra persona; las pantallas exigen sesión y no se guardan en caché) | Activar `trustHosts()` en una spec o ficha de seguridad |
| R10 | menor (corregido, T036) | `docs/design/capturar.mjs:414`, `:1287` | Visto en T035: `capturar.mjs --medir` no conocía el componente «Cita que abre su detalle» de la 048 y daba 27 fallos de toque y de foco en los paneles de inicio, también en la propia v0.19.0 (la 048 no ejecutó este guion, su plan D4). No lo causa la 046 | Hecho: el toque se mide por la cita entera que cubre el `::after` y el foco por el contorno de ese `::after`, con control positivo |

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
- La rama contiene `main` (v0.19.0): se puede abrir el PR sin conflictos.
- Tras liberar: vaciar `SIN_COMPARAR_046` en `capturar.mjs` (ya anotado en el roadmap).

## Tareas añadidas
- T033 ← R2 (hecha: fusión de `origin/main`; la 046 pasa al sistema de diseño 1.18.0)
- T034 ← R1 (hecha: el test de «Cancelar» con todos los roles de cada formulario)
- T035 ← R2 (hecha: verificación tras las correcciones, con `--medir` contra v0.19.0)
- T036 ← R10 (hecha; añadida durante `/implement`: `capturar.mjs` mide el componente de la 048)

## Aceptados sin tarea
- **R3** → roadmap: la aserción de posición de las migas de `NavegacionInternaTest` se cumple en cualquier lugar de `<main>`; comprobar que van antes del contenido de la vista
- **R4** → roadmap: reglas de `.migas` sin efecto o repetidas en `movil.css` (las fija el test de la 035); quitarlas al tocar ese archivo
- **R5** → roadmap: comentario antiguo de las migas en `app.css:626`
- **R6** → roadmap: la caracterización de TM3 no busca el nombre de `paciente2`
- **R7** → roadmap: `range()` frágil con caminos de un solo elemento en el test de accesibilidad de las migas
- **R8** → roadmap: duplicación en las vistas de citas y pacientes y en el parcial de migas (texto por rol, prefijo, separador, nombre compuesto) y un bucle de un solo elemento en `MovilPacienteTest`
- **R9** → roadmap: `trustHosts` sin activar (anterior a la 046), para una spec o ficha de seguridad
