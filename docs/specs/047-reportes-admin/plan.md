---
spec: 047-reportes-admin
status: approved   # draft | approved | blocked; aprobado de nuevo por el usuario el 2026-11-11 tras /analyze r1
constitution_version: 1.3.0
created: 2026-11-10
---

# Plan · 047 Reportes de la clínica para la administración

## Enfoque técnico
`/admin/reportes` deja de devolver la vista `placeholder` y pasa a un `ReportesController` de solo
lectura. El cálculo vive en un módulo nuevo, `app/Reportes/ReporteCitas.php`, que valida el periodo
(`reglasDePeriodo()`, como `reglasDeEntrada()` en `app/Agenda/`) y obtiene las cifras con **una sola
consulta agrupada** sobre `citas` (dentista, servicio y estado, con su cuenta) usando el índice
`idx_citas_fecha_estado`; los tres reportes y el resumen se derivan de ese resultado en PHP, así que
siempre cuadran entre sí (CA4). La consulta nunca lee pacientes ni notas (CA10). El acceso lleva
`role:admin` del grupo y un Gate nuevo, `ver-reportes` (P3, ADR 0003). La vista
`reportes/index.blade.php` usa los componentes del `design.md` aprobado, con una hoja propia
`public/css/reportes.css` que incluye las primeras reglas de impresión del sistema. Sin migraciones,
sin dependencias nuevas y sin JavaScript obligatorio (solo el botón «Imprimir»).

## Constitution Check
| Principio | Resultado | Justificación / ajuste | Cómo se verifica |
|---|---|---|---|
| P1 Spec aprobada antes de código | ✅ | Spec 047 y `design.md` aprobados (2026-11-10); este plan y `tasks.md` aprobados antes de `/implement` | manual: `python .ai/bin/aidd.py status` |
| P2 Test que falla antes y pasa después | ✅ | Cada CA con su test (Trazabilidad). De caracterización, marcados como tales, y cada uno comprobado rompiendo la pieza que lo protege: el 403 por rol y su evento de auditoría (CA9; `role:admin` de la ruta), el no-caché (`PreventBackHistory` del grupo `web`), el `X-Request-Id` (`IdentificadorPeticion`) y el menú solo del admin (la condición de rol de `layouts/app.blade.php`). El test del Gate incluye el caso permitido y el de CA10 un control positivo, para que los dos fallen antes del cambio | test: Trazabilidad; `/implement` anota el fallo previo o la pieza rota en cada caracterización |
| P3 Autorización en el servidor | ✅ | `role:admin` en el grupo de rutas y Gate `ver-reportes` (`app/Autorizacion/VerReportes.php`) comprobado en el controlador antes de validar. El recurso es de la clínica, no de un usuario: el alcance es el rol, como `gestionar-galeria` | test: `ReportesAccesoTest` (admin 200; dentista, asistente y cliente 403; sin sesión → `/login`; el Gate deniega por sí solo) |
| P4 Entrada validada y SQL con bindings | ✅ | `desde` y `hasta` con `$request->validate()` y reglas de `ReporteCitas::reglasDePeriodo()` (`date_format:Y-m-d`, orden y máximo de 366 días); consulta con Query Builder y `whereBetween` con bindings; la única expresión cruda es la constante `COUNT(*)`, sin entrada del usuario (P4 prohíbe concatenar entrada del usuario en ellas; /analyze A17). En Blade solo salida escapada (dobles llaves) | lint: `semgrep` sobre lo tocado, con la salida revisada y sin supresiones sin motivo; test: `ReportesPeriodoTest` (un caso por regla) |
| P5 Nada sensible en el repositorio | ✅ | Sin secretos ni datos; los tests crean sus citas dentro de la transacción | lint: `gitleaks detect --no-banner` |
| P6 Sin vulnerabilidades críticas o altas | ✅ | Sin dependencias nuevas. Línea base del 2026-11-10: `composer audit --locked` y `npm audit --audit-level=high` sin hallazgos | manual: SCA, `semgrep` y `trivy` en `/review` y `/release` |
| P7 Auditoría | ✅ | El 403 de otro rol queda como `acceso_denegado` por el registro central (spec 020), sin código nuevo. La consulta del admin no se audita: no es una lectura de ficha de paciente ni de expediente (spec 047, Auditoría) | test: `ReportesAccesoTest::test_el_acceso_denegado_queda_en_el_registro_de_auditoria` (caracterización) |
| P8 Correlación y logs | ✅ | La respuesta lleva `X-Request-Id` por el middleware global; sin llamadas nuevas a `Log::`; el periodo rechazado no se escribe en ningún log de la aplicación | test: `ReportesAccesoTest::test_la_respuesta_lleva_el_identificador_de_peticion` |
| P9 Sistema de diseño | ✅ | Solo tokens de `system.md` 1.13.0; los componentes nuevos del `design.md` entran en `system.md`/`system.html` 1.14.0 en la primera tarea. Sin `style=""` ni valores literales en la vista ni en `reportes.css`. WCAG 2.1 AA | test: `ReportesCssTest` (solo tokens, con control positivo; `--touch-min` en los controles; sin `style=""` en la vista; contraste de los pares de color de `reportes.css` con `contraste()` de `ReglasCss`: ≥ 4,5:1 en texto y ≥ 3:1 en bordes y foco, también el texto impreso sobre `--white`); manual: `node docs/design/capturar.mjs --medir` |
| P10 Rutas indexables descubribles | ➖ | «Reportes» exige sesión; la spec no crea ni cambia rutas públicas | — |

Resultado: 9 ✅, 1 ➖ y ninguna violación.

## Cambios por módulo
| Módulo | Cambio | Riesgo |
|---|---|---|
| `app/Reportes/ReporteCitas.php` (nuevo) | `reglasDePeriodo()` y `mensajesDePeriodo()`; `periodoPorDefecto()` y `atajos()` en la zona `clinica.zona_horaria`; `generar(desde, hasta)` con la consulta agrupada y el armado de resumen, «por estado», «por dentista» y «por servicio» | medio: es donde están las cifras; cubierto por `ReportesCitasTest` |
| `app/Autorizacion/VerReportes.php` (nuevo) | `autorizar(Usuario)`: permite solo `admin` | bajo |
| `app/Providers/AppServiceProvider.php` | `Gate::define('ver-reportes', [VerReportes::class, 'autorizar'])` | bajo |
| `app/Http/Controllers/ReportesController.php` (nuevo) | `index()`: Gate, validación, periodo por defecto, `ReporteCitas::generar()` y vista | bajo |
| `routes/web.php` | la ruta `admin.reportes` apunta al controlador (misma URL y mismo nombre) | bajo: el menú y el panel no cambian |
| `resources/views/reportes/index.blade.php` (nuevo) | la pantalla del `design.md`: filtro (`GET` a `/admin/reportes` con `desde` y `hasta`), frase del periodo, resumen, tres tablas `tabla-tarjetas`, vacío, error y cabecera impresa en texto | bajo |
| `config/clinica.php` | clave nueva `nombre` («Melissa López N. · Odontología integral»), la fuente del nombre en la cabecera impresa; las demás vistas que hoy lo escriben a mano no se tocan (un `/refactor` aparte) | bajo |
| `public/css/reportes.css` (nuevo, con `@push('styles')`) | estilos de la pantalla y `@media print` (oculta barra lateral, barra superior, filtro y botones; `.solo-impresion`) | bajo: solo se carga en esta página, así que la impresión del resto no cambia |
| `public/js/reportes.js` (nuevo, `defer`) | muestra «Imprimir» y llama a `window.print()`; sin él, el botón no aparece | bajo |
| `resources/views/placeholder.blade.php` | **no se toca ni se borra**: `GestionGaleriaAccesoTest` lo usa para leer el menú | — |
| `docs/design/system.md`, `docs/design/system.html` | versión 1.14.0 con los «Cambios a incorporar al sistema» del `design.md`; la fila de «Reportes» del inventario deja de ser placeholder | bajo |
| `docs/design/capturar.mjs` | `admin-reportes` sigue; `estado-vacio` pasa a `/admin/reportes?desde=2000-01-01&hasta=2000-01-31` (antes era el placeholder); «Reportes» entra en las pantallas incluidas de `--medir` (anchos, recortes, toque, solapes y tarjetas: las tres tablas son `tabla-tarjetas`) y gana una comprobación propia: a 320×568 y 390×844, la frase del periodo y «Citas en el periodo» dentro de la primera pantalla | bajo |
| `tests/Feature/Reportes/` (nuevo) | `ReportesAccesoTest`, `ReportesCitasTest`, `ReportesPeriodoTest`, `ReportesVistasTest`, `ReportesCssTest`, `ReportesRendimientoTest` | — |
| `tests/Feature/Concerns/EscenarioEstadosCita.php` | se reutiliza sin cambios (admin, dos dentistas, asistente, dos pacientes y `cita()`); `cita()` no acepta servicio ni fecha de creación, así que `ReportesCitasTest` inserta esas citas con un helper propio del test | — |

## Contratos y datos
**Ruta** (la única; ya existe, cambia lo que responde):

| Método y ruta | Entrada | Respuesta |
|---|---|---|
| `GET /admin/reportes` (`admin.reportes`) | `desde`, `hasta` (opcionales, `AAAA-MM-DD`) | 200 con la pantalla. Sin ninguna de las dos fechas: el mes en curso de la clínica. Periodo no válido: 302 a `/admin/reportes` con los errores y lo escrito; esa página muestra el aviso y el error bajo su campo, sin cifras. Otro rol: 403. Sin sesión: 302 a `/login` |

**Reglas del periodo** (`ReporteCitas::reglasDePeriodo()`):
- `desde`: `nullable|required_with:hasta|date_format:Y-m-d`.
- `hasta`: `nullable|required_with:desde|date_format:Y-m-d|after_or_equal:desde` y una regla propia:
  como mucho 366 días contando los dos extremos. La regla propia solo se evalúa cuando las dos fechas
  ya pasaron `date_format`; con una fecha no válida no interpreta nada ni lanza nada (/analyze A6).
- Un valor que no sea texto (una lista) se rechaza con el mismo mensaje de formato.
- Mensajes: «Escribe la fecha como AAAA-MM-DD.», «Falta la fecha «Desde».» / «Falta la fecha «Hasta».»,
  «“Hasta” no puede ser anterior a “Desde”.» y «El periodo puede ser de 366 días como mucho.».

**Atajos** (enlaces con `desde` y `hasta` ya calculados en la hora de la clínica): «Este mes» (del día
1 al último del mes), «Mes pasado» y «Este año» (1 de enero a 31 de diciembre). El que coincide con
el periodo en pantalla lleva `aria-current="true"`.

**Consulta** (sin migraciones; `citas.fecha_hora` guarda la hora local de la clínica):
```
citas  WHERE fecha_hora BETWEEN :desde 00:00:00 AND :hasta 23:59:59
       GROUP BY dentista_id, servicio_id, estado
       → dentista_id, servicio_id, estado, COUNT(*)
```
Más dos lecturas de nombres solo para los identificadores que aparecen: `personal` con `usuarios`
(nombre y apellido del dentista) y `servicios` (nombre, activos o no). Tres consultas por petición,
ninguna a `pacientes`.

**Lo que devuelve `generar()`** (forma, no firma definitiva):
- `total`, `cancelacion` y `inasistencia` (cuenta y porcentaje sobre el total).
- `porEstado`: los seis estados en el orden de `EstadosCita`, con cuenta y porcentaje (ceros incluidos).
- `porDentista`: filas con `total`, `completadas`, `canceladas`, `no_asistio`, ordenadas por total
  descendente y nombre; más `totales`.
- `porServicio`: filas con cuenta y porcentaje sobre las citas no canceladas, ordenadas por cuenta
  descendente y nombre; las citas sin servicio, en «Sin servicio».
- Porcentajes: `generar()` los devuelve como número redondeado a un decimal y la vista los escribe con
  coma («12,5 %»); con total 0 no se calculan: la vista muestra el estado vacío.
- «Sin servicio» se ordena por su cuenta como una fila más; el desempate de dentistas es por nombre y
  apellido.

**Pantalla y estados** (según `design.md`): con datos; vacío («No hay citas en este periodo»,
`.empty-state` con `role="status"`, una sola vez en toda la pantalla, en lugar del resumen y las
tablas, con el filtro disponible); periodo no válido (aviso
`role="alert"`, error bajo su campo con `aria-invalid` y `aria-describedby`, sin cifras); impresa
(cabecera `.solo-impresion` solo con texto: `config('clinica.nombre')`, «Reportes», periodo y
«Generado el …» en la hora de la clínica). Por debajo de 900 px, la frase del periodo y «Citas en el
periodo» se muestran antes del filtro sin cambiar el orden del HTML ni del foco (`design.md`). No cambian permisos: «Reportes» ya solo aparece en el menú y el panel del
admin, así que no hace falta tabla «Qué ve cada rol».

**Accesibilidad medible:** un `h1` y un `h2` por reporte; cada tabla con `caption`, `th scope="col"`
y `th scope="row"`; campos con `label for`; controles con `--touch-min` a todos los anchos; sin
desplazamiento horizontal a 320 px.

## Estrategia de pruebas
PHPUnit Feature con `DatabaseTransactions` contra `dentissa_test`, sobre el escenario de
`EscenarioEstadosCita`. Las fechas de los tests son fijas (`Carbon::setTestNow`) para que «este mes»
sea determinista, y caen en 2031: `dentissa_test` se carga con el volcado, que trae citas de 2026, y
los reportes cuentan todas las citas de la clínica. Cada test que siembra citas comprueba antes que su
periodo está vacío (/analyze A4).
- `ReportesAccesoTest`: roles, sesión, Gate, auditoría del 403 y `X-Request-Id`.
- `ReportesCitasTest`: cifras de cada reporte con un conjunto conocido de citas (estados, dentistas,
  servicios, una sin servicio, un servicio inactivo, un dentista inactivo, citas justo en los bordes
  del periodo y fuera de él).
- `ReportesPeriodoTest`: periodo por defecto, atajos y una prueba por regla de validación.
- `ReportesVistasTest`: estructura del HTML con `DOMXPath` (orden, encabezados, tablas, vacío,
  error, cabecera impresa, ausencia de datos de pacientes con control positivo, resumen con sus
  tasas, porcentajes con coma, fila de totales, «Sin servicio» y formulario `GET`).
- `ReportesCssTest`: `reportes.css` solo con tokens (trait `ReglasCss`), contraste de sus pares de
  color, reglas de impresión presentes, sin `style=""` en la vista.
- `ReportesRendimientoTest` (`#[Group('rnf')]`, fuera de la suite por defecto como el de la 020):
  10 000 citas en 366 días, menos de 2 s.
- En el navegador: `node docs/design/capturar.mjs --medir` (sin desbordes ni objetivos de toque
  pequeños a 320, 390 y 1440 px) y la verificación manual del usuario en pantalla y en la vista
  previa de impresión.

## Modelo de amenazas
| ID | Amenaza (STRIDE) | Categoría OWASP | Componente | Control | Test |
|---|---|---|---|---|---|
| TM1 | Elevation: un dentista, asistente o paciente abre `/admin/reportes` para ver cifras de la clínica | A01:2025 Broken Access Control | ruta y controlador | `role:admin` + Gate `ver-reportes` antes de validar o consultar | `ReportesAccesoTest::test_solo_la_administracion_ve_los_reportes` y `::test_el_gate_deniega_a_quien_no_es_admin` |
| TM2 | Tampering: fechas mal formadas, listas o texto con SQL en `desde`/`hasta` para inyectar o provocar un 500 | A05:2025 Injection | validación y consulta | `date_format:Y-m-d` estricto, rechazo de listas, bindings; nunca se consulta con un periodo no validado | `ReportesPeriodoTest::test_un_periodo_mal_formado_se_rechaza_sin_error_del_servidor` (proveedor de casos) |
| TM3 | DoS: periodos de muchos años o peticiones repetidas | A06:2025 Insecure Design | consulta | máximo de 366 días; una consulta agrupada sobre índice; solo el admin llega a ella. Las peticiones repetidas no tienen límite propio: residuo aceptado (solo cuentas de administración, volumen académico y Cloudflare delante, como TM6 de la 038) | `ReportesPeriodoTest::test_un_periodo_de_mas_de_366_dias_se_rechaza` y `ReportesRendimientoTest` |
| TM4 | Information disclosure: datos de pacientes en la pantalla o en el papel | A01:2025 Broken Access Control | consulta y vista | la consulta no une `pacientes` ni lee notas ni horas; la vista solo pinta cuentas y nombres de dentistas y servicios | `ReportesVistasTest::test_no_aparece_ningun_dato_de_pacientes_ni_de_una_cita_concreta` |
| TM5 | Tampering (XSS reflejado): el valor rechazado de una fecha vuelve al campo | A05:2025 Injection | vista | salida escapada (dobles llaves) en todo; solo se repone texto en el campo | `ReportesVistasTest::test_el_valor_rechazado_vuelve_escapado` |
| TM6 | Repudiation: alguien niega haber intentado entrar | A09:2025 Security Logging and Alerting Failures | registro central de denegaciones (spec 020) | el 403 deja `acceso_denegado` con actor, ruta y fecha | `ReportesAccesoTest::test_el_acceso_denegado_queda_en_el_registro_de_auditoria` |
| TM7 | Information disclosure: el reporte queda en la caché de un equipo compartido tras cerrar sesión | A02:2025 Security Misconfiguration | middleware `PreventBackHistory` del grupo `web` (`bootstrap/app.php`) | la respuesta sale con las cabeceras de no-caché de todas las rutas web, sin excepción para esta | `ReportesAccesoTest::test_la_respuesta_no_se_guarda_en_cache` |

## Trazabilidad
| Criterio de aceptación | Cambio(s) | Test(s) |
|---|---|---|
| CA1: tres reportes del mes en curso, periodo escrito, cita por su fecha agendada | controlador, `ReporteCitas`, vista | `ReportesPeriodoTest::test_sin_fechas_se_muestra_el_mes_en_curso_de_la_clinica`; `ReportesVistasTest::test_la_pagina_muestra_los_tres_reportes_y_el_periodo`; `ReportesCitasTest::test_una_cita_cuenta_por_su_fecha_agendada_y_no_por_la_de_creacion` |
| CA2: «Desde»/«Hasta» y atajos; extremos incluidos; el periodo está en la dirección | `ReporteCitas` (reglas, atajos), vista | `ReportesPeriodoTest::test_el_periodo_elegido_recalcula_los_reportes`, `::test_los_dos_dias_extremos_cuentan`, `::test_los_atajos_enlazan_este_mes_mes_pasado_y_este_ano`; `ReportesVistasTest::test_el_formulario_envia_el_periodo_por_la_direccion` |
| CA3: total, seis estados con cuenta y porcentaje, tasas destacadas | `ReporteCitas`, vista | `ReportesCitasTest::test_citas_por_estado_muestra_los_seis_estados_con_su_porcentaje`, `::test_las_tasas_de_cancelacion_e_inasistencia`; `ReportesVistasTest::test_el_resumen_muestra_el_total_y_las_dos_tasas_con_su_texto`, `::test_los_porcentajes_van_con_coma_y_un_decimal` |
| CA4: fila por dentista con citas, orden, inactivos y fila de totales que cuadra | `ReporteCitas`, vista | `ReportesCitasTest::test_citas_por_dentista_ordena_y_cuadra_con_el_total`, `::test_un_dentista_inactivo_con_citas_aparece_y_uno_sin_citas_no`; `ReportesVistasTest::test_la_tabla_por_dentista_tiene_fila_de_totales` |
| CA5: servicios por citas no canceladas, «Sin servicio», servicio inactivo | `ReporteCitas`, vista | `ReportesCitasTest::test_servicios_mas_solicitados_cuenta_las_no_canceladas`, `::test_las_citas_sin_servicio_y_el_servicio_inactivo`; `ReportesVistasTest::test_la_tabla_de_servicios_muestra_sin_servicio` |
| CA6: periodo sin citas | vista | `ReportesVistasTest::test_un_periodo_sin_citas_muestra_el_estado_vacio_y_el_filtro` |
| CA7: periodo no válido, mensaje junto al selector, sin cifras ni página de error | `ReporteCitas` (reglas), controlador, vista | `ReportesPeriodoTest::test_un_periodo_mal_formado_se_rechaza_sin_error_del_servidor`, `::test_hasta_anterior_a_desde`, `::test_falta_una_de_las_dos_fechas`, `::test_un_periodo_de_mas_de_366_dias_se_rechaza`; `ReportesVistasTest::test_el_error_va_junto_a_su_campo_y_no_hay_cifras` |
| CA8: hoja impresa | vista (`.solo-impresion`), `reportes.css` (`@media print`) | `ReportesVistasTest::test_la_cabecera_impresa_lleva_clinica_periodo_y_fecha`; `ReportesCssTest::test_al_imprimir_se_ocultan_el_menu_el_filtro_y_los_botones`, `::test_las_filas_no_se_parten_entre_paginas`. verificación manual: vista previa de impresión del navegador a tamaño carta, contra `design.md` (sin menú ni controles, legible en blanco y negro, ninguna fila cortada) |
| CA9 (abuso): otros roles y sin sesión, con o sin periodo; 403 auditado; la entrada solo en el menú del admin | ruta, Gate | `ReportesAccesoTest::test_solo_la_administracion_ve_los_reportes` (caracterización; también con `?desde=…&hasta=…`), `::test_sin_sesion_lleva_al_inicio_de_sesion`, `::test_el_acceso_denegado_queda_en_el_registro_de_auditoria` (caracterización), `::test_reportes_solo_esta_en_el_menu_de_la_administracion` (caracterización) |
| CA10 (abuso): solo totales y nombres de dentistas y servicios | `ReporteCitas`, vista | `ReportesVistasTest::test_no_aparece_ningun_dato_de_pacientes_ni_de_una_cita_concreta` |
| TM1 | Gate y ruta | `ReportesAccesoTest::test_solo_la_administracion_ve_los_reportes`, `::test_el_gate_deniega_a_quien_no_es_admin` |
| TM2 | reglas y consulta | `ReportesPeriodoTest::test_un_periodo_mal_formado_se_rechaza_sin_error_del_servidor` |
| TM3 | regla de 366 días | `ReportesPeriodoTest::test_un_periodo_de_mas_de_366_dias_se_rechaza`; `ReportesRendimientoTest` |
| TM4 | consulta y vista | `ReportesVistasTest::test_no_aparece_ningun_dato_de_pacientes_ni_de_una_cita_concreta` |
| TM5 | vista | `ReportesVistasTest::test_el_valor_rechazado_vuelve_escapado` |
| TM6 | registro central (sin cambios) | `ReportesAccesoTest::test_el_acceso_denegado_queda_en_el_registro_de_auditoria` |
| TM7 | middleware del grupo (sin cambios) | `ReportesAccesoTest::test_la_respuesta_no_se_guarda_en_cache` |
| RNF: accesibilidad (AA, toque, sin desbordes, encabezados de tabla) | vista, `reportes.css` | `ReportesVistasTest::test_las_tablas_tienen_titulo_y_encabezados_de_fila_y_columna`; `ReportesCssTest` (`--touch-min` y contraste de los pares de color); verificación manual: `node docs/design/capturar.mjs --medir` a 320, 390 y 1440 px |
| RNF: rendimiento (10 000 citas, 366 días, < 2 s en local) | consulta agrupada | `ReportesRendimientoTest::test_el_reporte_de_un_ano_con_diez_mil_citas_responde_en_menos_de_dos_segundos` (`--group=rnf`) |
| RNF: exactitud (dos consultas iguales dan lo mismo) | `ReporteCitas` | `ReportesCitasTest::test_dos_consultas_del_mismo_periodo_dan_las_mismas_cifras` |
| RNF: no se indexa | layout de la aplicación (sin cambios) | `ReportesAccesoTest::test_sin_sesion_lleva_al_inicio_de_sesion` (sin sesión no hay contenido que indexar) |
| K1–K7 (`design.md`) | vista, `reportes.css`, `capturar.mjs` | `ReportesVistasTest` y `ReportesCssTest`; K1 y K4 con `capturar.mjs --medir` (primera pantalla a 320×568 y 390×844; tarjetas); verificación manual: el usuario valida la pantalla a 320, 390 y 1440 px durante `/implement` |

## Observabilidad
- Logs nuevos: ninguno.
- Eventos de auditoría: `acceso_denegado` del registro central (spec 020) cuando otro rol pide
  `/admin/reportes` → sin código nuevo → `ReportesAccesoTest::test_el_acceso_denegado_queda_en_el_registro_de_auditoria`.
  La consulta del admin no genera evento (spec 047, Auditoría).
- Métricas y alertas: sin cambios; la alerta de 5xx de la spec 032 cubre un fallo de la página.
- Cómo se verifica tras el deploy: entrar como admin en `https://dentissa.app/admin/reportes`, ver el
  mes en curso con cifras o el estado vacío, probar un atajo y la vista previa de impresión; con una
  cuenta de dentista, comprobar el 403 y su fila en `/admin/auditoria`.

## Descubribilidad
No aplica: «Reportes» exige sesión y la spec no crea ni cambia rutas públicas.

## Rollout
- Feature flag: no aplica: la ruta ya existe y hoy muestra «en construcción»; el cambio es de solo
  lectura, sin datos ni migraciones, y la vuelta atrás es inmediata.
- Orden de despliegue: un solo paso, `deploy/desplegar.sh vX.Y.Z` (no hay migraciones). Versión
  **minor** (comportamiento nuevo): v0.17.0 si es la siguiente en salir.
- Compatibilidad: sin estado compartido entre versiones; la URL y el nombre de la ruta no cambian, así
  que el `route:cache` y los enlaces del menú siguen valiendo.
- Rollback: `bash deploy/volver.sh <tag anterior>` sin copia (no hay migraciones).
- Métricas a vigilar tras el deploy: la alerta de 5xx (spec 032), `verificar.sh` sin fallos y la
  comprobación manual de arriba.

## Decisiones (→ ADR si son arquitectónicas)
- **D1 · Módulo nuevo `app/Reportes/`.** Opciones: (a) la lógica en el controlador, como
  `DashboardController`; (b) una clase de dominio. Se elige (b): es la regla de código nuevo de
  `AGENTS.md` (reglas en la clase de dominio) y permite probar las cifras sin pasar por HTTP. No
  cambia la arquitectura (un módulo más junto a `app/Agenda/` y `app/Galeria/`): sin ADR.
- **D2 · Una consulta agrupada y el resto en PHP.** Opciones: (a) tres consultas, una por reporte;
  (b) una agrupada por dentista, servicio y estado. Se elige (b): los tres reportes salen de la misma
  lectura y no pueden descuadrarse por un cambio entre consultas (CA4); el resultado tiene como mucho
  dentistas × servicios × 6 filas.
- **D3 · Periodo no válido: `$request->validate()` con vuelta fija a `/admin/reportes`.** Por defecto
  una validación fallida en un `GET` vuelve a la página anterior, que puede ser cualquier otra si la
  dirección se abrió a mano. Se captura la `ValidationException` y se le fija `redirectTo` a
  `/admin/reportes`. Al volver, el controlador detecta los errores en la sesión y pinta la página sin
  cifras, como hace el filtro de auditoría (spec 020) al volver de un filtro rechazado; sin esa
  detección se verían las cifras del mes en curso junto al error. Alternativa descartada: pintar la página con un 422, que cambia el
  contrato habitual de los filtros del panel.
- **D4 · «Hoy» y los atajos en la hora de la clínica** (`clinica.zona_horaria`), porque
  `citas.fecha_hora` guarda la hora local de la clínica y la aplicación trabaja en UTC
  (`config/clinica.php`).
- **D5 · Porcentajes con coma y un decimal** («12,5 %»), sobre el total del periodo en «por estado» y
  sobre las citas no canceladas en «por servicio»; no se fuerza que sumen 100,0.
- **D6 · Reglas de impresión en `reportes.css`**, no en `app.css`: el `design.md` las llama «del
  layout», pero cargarlas solo en esta página evita cambiar cómo se imprimen las demás pantallas sin
  haberlas revisado. `system.md` las documenta como la hoja de impresión del sistema, a reutilizar
  cuando otra pantalla la necesite.
- **D7 · «Imprimir» con un archivo propio `public/js/reportes.js`**, no con un `<script>` en línea ni
  un `onclick`: la CSP admite hoy `'unsafe-inline'`, pero la CSP estricta sin `'unsafe-inline'` está pendiente en el roadmap (`docs/security.md`) y
  el código nuevo no debe depender de ella. El botón nace oculto (`hidden`) y el script lo muestra.
- **D8 · `placeholder.blade.php` se conserva.** Ya no lo usa ninguna ruta, pero un test de la galería
  lo usa para leer el menú; retirarlo es un `/refactor` aparte.
- **D9 · Test de rendimiento en el grupo `rnf`**, fuera de la suite por defecto, como
  `AuditoriaRendimientoTest` (plan 020, D14).
- **D11 · Nombre de la clínica en `config('clinica.nombre')`** (decisión del usuario, 2026-11-11: la
  cabecera impresa va solo con texto, sin imagen). Es la única fuente del nombre para «Reportes».
- **D12 · Periodos de prueba en 2031**, sin citas del volcado, con la comprobación de periodo vacío
  antes de sembrar (/analyze A4).
- **D13 · Las tres tablas son `tabla-tarjetas` por debajo de 900 px** y los controles llevan
  `--touch-min` a todos los anchos (`design.md`, /analyze A8 y A20).
- **D10 · Sin dependencias nuevas** (ni librería de gráficas ni de PDF): fuera de alcance en la spec.

## Impacto en arquitectura
- `docs/architecture.md`: la frase de «Otras rutas» que dice que `/admin/reportes` sirve un
  placeholder; añadir el módulo `app/Reportes/` y el Gate `ver-reportes`.
- `AGENTS.md` (ruta protegida: pedir aprobación al editarla): mapa del repositorio (`app/Reportes/`,
  el número de controladores contado al editarlo, `reportes.css` y `reportes.js`).
- `docs/security.md`: «Dónde se aplica» (Gate `ver-reportes`) y una fila en «Superficie de ataque»
  para `GET /admin/reportes` (fechas `AAAA-MM-DD`, máximo de 366 días, solo lectura).
- `docs/specs/012-paneles-por-rol/spec.md`: una línea en su Historial al liberar (el placeholder de
  «Reportes» ya no existe); no entra en `extends`.
- `docs/specs/README.md`: quitar «Reportes» de «Funcionalidades no documentadas como spec».
- `docs/design/system.md` y `system.html`: versión 1.14.0 (primera tarea).
- `docs/roadmap.md`: la 037 prevista ya no necesita adaptar «reportes» al móvil.

## Riesgos y mitigaciones
- **Cifras mal contadas en los bordes del periodo** (citas a las 00:00 o a las 23:59 del último día):
  test específico de extremos (CA2).
- **Tabla de cinco columnas a 320 px:** `tabla-tarjetas` (034) y medición con `capturar.mjs --medir`;
  si una cifra desborda, se corrige en `reportes.css`, sin tocar `movil.css`.
- **`tabla-tarjetas` no contempla `tfoot`:** el `design.md` lo fija como una última tarjeta «Total»;
  sus reglas van en `reportes.css`, sin tocar `movil.css`, y se documentan en `system.md` 1.14.0.
- **Otras specs tocarán los mismos archivos** (la 014 y la 040: `AppServiceProvider`,
  `docs/security.md`, `docs/architecture.md` y `system.md`): al fusionar `main`, la suite completa
  debe seguir en verde (T028), y la versión de `system.md` de la que salga después parte de la
  anterior.
- **Impresión distinta entre navegadores:** la verificación manual se hace en el navegador del
  usuario (Chrome); las reglas usan solo propiedades de impresión estándar (`break-inside: avoid`).
- **Otra sesión trabajando en el mismo clon** (`fix/tests-intermitentes-041` en `dentissa-041`): este
  plan no toca archivos de ese carril; comprobar rama y `git status` antes de cada commit.
- **Las capturas del sistema dependían del placeholder** (`estado-vacio`): `capturar.mjs` pasa a usar
  un periodo sin citas de «Reportes»; se rehace esa captura en la tarea de cierre.
