---
spec: 047-reportes-admin
plan: plan.md
impl_base: 8b683d8
status: approved
---

# Tareas · 047 Reportes de la clínica para la administración

Formato:
`- [ ] T### [P] <verbo + qué> — <archivos> — hecho cuando: <criterio> — cubre: CA# — depende: T###`
- T001–T089: trabajo. T090–T099: reservadas.
- `[P]` = paralelizable con las demás `[P]` de su grupo una vez cumplidas sus dependencias (no
  comparte archivos con otra tarea abierta).
- `[-]` = obsoleta (`— obsoleta: <motivo> (AAAA-MM-DD)`): no cuenta como abierta ni como hecha.
- Bajo una tarea: `  - nota: …`, `  - bloqueo: AAAA-MM-DD — <qué falta> — <skill a ejecutar>`.
- **Proceso ligero de diseño (spec y `design.md`, Decisiones):** sin ciclo de `/design-spec`; el usuario valida la pantalla a 320, 390 y 1440 px y la vista previa de impresión en la tarea que la construye (T025).
- **Corrección tras `/analyze` r1 (2026-11-11):** `/tasks --fix` editó en su lugar T010–T014, T021, T023–T025, T027, T028, T091 y T098 (A1–A11, A13–A17, A19, A20, A22). No se añadió ni se renumeró ninguna.
- **Periodos de prueba:** los tests fijan la fecha en 2031 (`Carbon::setTestNow`) y comprueban que su periodo está vacío antes de sembrar: `dentissa_test` trae citas de 2026 del volcado (A4).
- **Tests en este clon:** comprobar antes qué carpeta montan los contenedores en marcha; si montan otro clon (`dentissa-041`), usar `MSYS_NO_PATHCONV=1 docker compose run --rm --no-deps -T app php artisan test …` sin tocar los otros contenedores.
- **Otra sesión puede trabajar en el clon vecino:** comprobar rama (`feature/047-reportes-admin`) y `git status` antes de cada commit; los cambios sin confirmar de `aidlc/` no son de esta spec y no se incluyen.

## Constitution Check
Contra `plan.md` (constitución 1.3.0). Lo que el desglose podría romper:

| Principio | Resultado | Justificación / ajuste | Cómo se verifica |
|---|---|---|---|
| P1 Spec aprobada antes de código | ✅ | Spec, `design.md` y plan aprobados; ninguna tarea de código empieza antes de que `tasks.md` esté `approved` y `/analyze` en `pass` | manual: `aidd.py status` |
| P2 Test que falla antes y pasa después | ✅ | Los tests (T010–T015) van antes y cada tarea de implementación depende del suyo. Cada caracterización de T010 se comprueba rompiendo la pieza que la protege (la ruta, `PreventBackHistory`, `IdentificadorPeticion` o la condición de rol del layout) y la nota la nombra | test: T010–T015 |
| P3 Autorización en el servidor | ✅ | T020 crea el Gate antes de que T023 cambie la ruta; la ruta nunca queda sin `role:admin` | test: T010 |
| P4 Entrada validada y SQL con bindings | ✅ | Las reglas (T021) llegan antes que el controlador (T023): no hay un momento con consulta sin validar | test: T012; lint: `semgrep` en T028 |
| P5 Nada sensible en el repositorio | ✅ | Ninguna tarea añade datos ni secretos; los tests crean sus citas en la transacción | lint: `gitleaks` en T028 |
| P6 Sin vulnerabilidades críticas o altas | ✅ | Ninguna tarea añade dependencias | manual: SCA en `/review` y `/release` |
| P7 Auditoría | ✅ | Ninguna tarea toca el registro central de denegaciones | test: T010 |
| P8 Correlación y logs | ✅ | Ninguna tarea añade `Log::` | test: T010 |
| P9 Sistema de diseño | ✅ | T001 (sistema 1.14.0) es la primera tarea y las de interfaz (T023–T026) dependen de ella | test: T014; manual: T025 y T027 |
| P10 Rutas indexables descubribles | ➖ | Sin rutas públicas | — |

## Preparación
- [x] T001 Incorporar al sistema de diseño los «Cambios a incorporar al sistema» del `design.md` (frase del periodo, fila de totales, tasa en `.stat-card`, cabecera impresa y reglas de impresión), subir la versión a 1.14.0 con su entrada en «Decisiones» y cambiar la fila de «Reportes» del inventario — `docs/design/system.md`, `docs/design/system.html` — hecho cuando: los dos documentos describen los cinco cambios con la versión 1.14.0 y `python .ai/bin/aidd.py validate` pasa
- [x] T002 Registrar la línea base: suite completa en verde antes de tocar código y `impl_base` con el commit de partida — `docs/specs/047-reportes-admin/tasks.md` — hecho cuando: `php artisan test` pasa completo y el frontmatter de este archivo lleva `impl_base`
  - nota: 2026-11-11 — línea base con `impl_base` 8b683d8: suite completa 911 en verde (14 014 aserciones), sin fallos previos; ejecutada con `docker compose run --rm --no-deps -T app` porque los contenedores en marcha montan `dentissa-041`.

## Tests (antes de implementar)
- [x] T010 [P] Escribir `ReportesAccesoTest`: `test_solo_la_administracion_ve_los_reportes` (también con `?desde=…&hasta=…` en la dirección), `test_el_gate_deniega_a_quien_no_es_admin` (con el caso permitido: el admin pasa `ver-reportes`), `test_sin_sesion_lleva_al_inicio_de_sesion`, `test_el_acceso_denegado_queda_en_el_registro_de_auditoria`, `test_reportes_solo_esta_en_el_menu_de_la_administracion`, `test_la_respuesta_no_se_guarda_en_cache` y `test_la_respuesta_lleva_el_identificador_de_peticion` — `tests/Feature/Reportes/ReportesAccesoTest.php` — hecho cuando: el test del Gate falla en su caso permitido porque `ver-reportes` no existe; los de caracterización pasan y cada uno se comprueba rompiendo un momento la pieza que lo protege, anotada en la tarea: `role:admin` de la ruta (403 y su auditoría), `PreventBackHistory` (no-caché), `IdentificadorPeticion` (identificador) y la condición de rol de `layouts/app.blade.php` (menú) — cubre: CA9 — depende: T002
  - nota: fallo previo (2026-11-11): `test_el_gate_deniega_a_quien_no_es_admin` falla porque `ver-reportes` no existe; `test_la_administracion_ve_los_reportes` y los demás de caracterización pasan sobre el placeholder
  - nota: comprobado rompiendo `role:admin` de la ruta (`->withoutMiddleware('role:admin')`): fallan los seis casos de rol y el de auditoría; restaurado
  - nota: comprobado rompiendo `PreventBackHistory` del grupo `web` (falla el de no-caché), `IdentificadorPeticion` (falla el del identificador) y la condición `rol === 'admin'` de `layouts/app.blade.php` (falla el del menú); todo restaurado con `git checkout`
- [x] T011 [P] Escribir `ReportesCitasTest` sobre `EscenarioEstadosCita` con fecha fija en 2031 y la comprobación de periodo vacío antes de sembrar; las citas con servicio, sin servicio o con fecha de creación distinta se insertan con un helper propio del test (`cita()` del trait no las admite): `test_una_cita_cuenta_por_su_fecha_agendada_y_no_por_la_de_creacion`, `test_citas_por_estado_muestra_los_seis_estados_con_su_porcentaje`, `test_las_tasas_de_cancelacion_e_inasistencia`, `test_citas_por_dentista_ordena_y_cuadra_con_el_total` (desempate por nombre y apellido), `test_un_dentista_inactivo_con_citas_aparece_y_uno_sin_citas_no`, `test_servicios_mas_solicitados_cuenta_las_no_canceladas`, `test_las_citas_sin_servicio_y_el_servicio_inactivo` («Sin servicio» ordenada por su cuenta) y `test_dos_consultas_del_mismo_periodo_dan_las_mismas_cifras` — `tests/Feature/Reportes/ReportesCitasTest.php` — hecho cuando: fallan porque `App\Reportes\ReporteCitas` no existe — cubre: CA1, CA3, CA4, CA5 — depende: T002
  - nota: fallo previo: los once casos fallan porque `App\Reportes\ReporteCitas` no existe
- [x] T012 [P] Escribir `ReportesPeriodoTest` con fecha fija en 2031: `test_sin_fechas_se_muestra_el_mes_en_curso_de_la_clinica`, `test_el_periodo_elegido_recalcula_los_reportes`, `test_los_dos_dias_extremos_cuentan`, `test_los_atajos_enlazan_este_mes_mes_pasado_y_este_ano`, `test_un_periodo_mal_formado_se_rechaza_sin_error_del_servidor` (proveedor: fecha inexistente, texto, texto con SQL y lista, cada caso en `desde` con un `hasta` válido y en `hasta` con un `desde` válido; la respuesta es 302 y nunca 500), `test_hasta_anterior_a_desde`, `test_falta_una_de_las_dos_fechas` y `test_un_periodo_de_mas_de_366_dias_se_rechaza` (366 días contando los extremos pasa, 367 no) — `tests/Feature/Reportes/ReportesPeriodoTest.php` — hecho cuando: fallan porque la ruta todavía devuelve el placeholder — cubre: CA1, CA2, CA7 — depende: T002
  - nota: fallo previo: fallan porque la ruta devuelve el placeholder (sin `#reporte-periodo` ni redirección con errores); el proveedor añade «otro formato» y «fecha con hora» a los casos de la tarea
- [x] T013 [P] Escribir `ReportesVistasTest` con `DOMXPath` y fecha fija en 2031: `test_la_pagina_muestra_los_tres_reportes_y_el_periodo`, `test_el_formulario_envia_el_periodo_por_la_direccion` (`GET` a `/admin/reportes` con `desde` y `hasta`), `test_el_resumen_muestra_el_total_y_las_dos_tasas_con_su_texto`, `test_los_porcentajes_van_con_coma_y_un_decimal`, `test_la_tabla_por_dentista_tiene_fila_de_totales`, `test_la_tabla_de_servicios_muestra_sin_servicio`, `test_un_periodo_sin_citas_muestra_el_estado_vacio_y_el_filtro` (el mensaje exactamente una vez, sin resumen ni tablas), `test_el_error_va_junto_a_su_campo_y_no_hay_cifras`, `test_la_cabecera_impresa_lleva_clinica_periodo_y_fecha` (el texto de `config('clinica.nombre')`, sin imagen), `test_no_aparece_ningun_dato_de_pacientes_ni_de_una_cita_concreta` (con control positivo: las cifras y los nombres de dentista y servicio sí aparecen; el nombre, el contacto y las notas del paciente sembrado no), `test_el_valor_rechazado_vuelve_escapado` y `test_las_tablas_tienen_titulo_y_encabezados_de_fila_y_columna` — `tests/Feature/Reportes/ReportesVistasTest.php` — hecho cuando: todos fallan porque la página es el placeholder — cubre: CA1, CA2, CA3, CA4, CA5, CA6, CA7, CA8, CA10 — depende: T002
  - nota: fallo previo: todos fallan porque la página es el placeholder; añade `test_un_valor_rechazado_que_es_una_lista_no_rompe_la_pagina` y `test_el_boton_imprimir_nace_oculto_y_sin_codigo_en_linea` (contrato de T024 y T026)
- [x] T014 [P] Escribir `ReportesCssTest` con el trait `ReglasCss`: `reportes.css` solo usa tokens (con control positivo), los controles declaran `--touch-min` a todos los anchos, la vista no tiene `style=""`, el contraste de cada par de color que declara `reportes.css` (con `contraste()`: ≥ 4,5:1 en texto y ≥ 3:1 en bordes y foco, incluido el texto impreso sobre `--white`), `test_al_imprimir_se_ocultan_el_menu_el_filtro_y_los_botones` y `test_las_filas_no_se_parten_entre_paginas` — `tests/Feature/Reportes/ReportesCssTest.php` — hecho cuando: falla porque `public/css/reportes.css` no existe — cubre: CA8 — depende: T002
  - nota: fallo previo: falla porque `public/css/reportes.css` y la vista no existen; el control positivo del detector de literales pasa
- [x] T015 [P] Escribir `ReportesRendimientoTest` (`#[Group('rnf')]`): `test_el_reporte_de_un_ano_con_diez_mil_citas_responde_en_menos_de_dos_segundos`, con 10 000 citas sintéticas en 366 días dentro de la transacción — `tests/Feature/Reportes/ReportesRendimientoTest.php` — hecho cuando: con `--group=rnf` falla porque `ReporteCitas` no existe y la suite por defecto no lo ejecuta — depende: T002
  - nota: fallo previo con `--group=rnf`: falla porque la página es el placeholder (no aparece «Citas por estado»); la suite por defecto no lo ejecuta

## Implementación
- [x] T020 Crear el Gate `ver-reportes`: `VerReportes::autorizar()` permite solo al admin, registrado en el proveedor — `app/Autorizacion/VerReportes.php`, `app/Providers/AppServiceProvider.php` — hecho cuando: `test_el_gate_deniega_a_quien_no_es_admin` pasa — cubre: CA9 — depende: T010
  - nota: `test_el_gate_deniega_a_quien_no_es_admin` pasa; el controlador comprueba el Gate antes de validar (T023)
- [x] T021 Crear `ReporteCitas` con el periodo: `reglasDePeriodo()` (formato, orden, las dos fechas juntas, máximo de 366 días contando los extremos, rechazo de listas; la regla de 366 días no se evalúa ni lanza nada si alguna fecha no pasó el formato), `mensajesDePeriodo()`, `periodoPorDefecto()` y `atajos()` en la zona `clinica.zona_horaria` — `app/Reportes/ReporteCitas.php` — hecho cuando: existen los cuatro métodos con los mensajes del plan (Contratos y datos); sus tests pasan al terminar T023 — cubre: CA2, CA7 — depende: T012
  - nota: las reglas de orden y de 366 días son una regla propia con acceso a las dos fechas (no `after_or_equal`): con una fecha que no pasó el formato, o una lista, no compara ni lanza nada (A6); `ReportesPeriodoTest` pasa completo con T023
- [x] T022 Añadir `ReporteCitas::generar(desde, hasta)`: la consulta agrupada con bindings (sin `pacientes`, notas ni horas), los nombres de dentistas y servicios, y el armado de resumen, «por estado», «por dentista» (con totales) y «por servicio» («Sin servicio», porcentajes con un decimal) — `app/Reportes/ReporteCitas.php` — hecho cuando: T011 pasa completo — cubre: CA1, CA3, CA4, CA5, CA10 — depende: T011, T021
  - nota: `ReportesCitasTest` pasa completo (11 casos); los seis estados van en la constante `ReporteCitas::ESTADOS` (`EstadosCita` no tiene la lista ordenada)
- [x] T023 Crear `ReportesController::index()` (Gate, validación con vuelta fija a `/admin/reportes`, detección de los errores en la sesión para no calcular ni pintar cifras al volver, periodo por defecto y `generar()`), apuntar la ruta `admin.reportes` al controlador dentro del grupo del admin y crear la vista con el filtro (`GET`), los atajos, la frase del periodo, el resumen con las dos tasas, y las tres tablas `tabla-tarjetas` (con `caption`, encabezados de fila y columna, porcentajes con coma, fila de totales y «Sin servicio») — `app/Http/Controllers/ReportesController.php`, `routes/web.php`, `resources/views/reportes/index.blade.php` — hecho cuando: T010 y T012 pasan completos y de T013 pasan todos menos los del estado vacío, el error junto a su campo, el valor rechazado y la cabecera impresa — cubre: CA1, CA2, CA3, CA4, CA5, CA7, CA9, CA10 — depende: T001, T013, T020, T022
  - nota: `ReportesAccesoTest` y `ReportesPeriodoTest` pasan completos; el Gate se comprueba antes de validar. La vista se escribió junta con T024–T026 (carga `reportes.css` y `reportes.js`), así que el commit las incluye; T025 y T026 siguen abiertas hasta su verificación manual
- [x] T024 Completar la vista con los estados: vacío («No hay citas en este periodo», una sola vez en toda la pantalla, en lugar del resumen y las tablas, con el filtro), periodo no válido (aviso `role="alert"`, error bajo su campo con `aria-invalid` y `aria-describedby`, valor repuesto escapado, sin cifras) y la cabecera impresa `.solo-impresion` solo con texto (`config('clinica.nombre')`, clave nueva con «Melissa López N. · Odontología integral»; «Reportes», periodo y «Generado el …») — `resources/views/reportes/index.blade.php`, `config/clinica.php` — hecho cuando: T013 pasa completo — cubre: CA6, CA7, CA8 — depende: T023
  - nota: `ReportesVistasTest` pasa completo (15 casos). Caso que la spec no fija: con citas en el periodo pero todas canceladas, «Servicios más solicitados» dice «Todas las citas del periodo están canceladas.» en lugar de una tabla vacía (a confirmar con el usuario)
- [x] T025 Crear `reportes.css` (pantalla y `@media print`) solo con tokens y cargarlo en la vista con `@push('styles')`: por debajo de 900 px, la frase del periodo y «Citas en el periodo» antes del filtro (sin cambiar el orden del HTML) y el `tfoot` como última tarjeta «Total» — `public/css/reportes.css`, `resources/views/reportes/index.blade.php` — hecho cuando: T014 pasa · verificación manual: el usuario valida la pantalla con datos, vacía y con error a 320, 390 y 1440 px contra `design.md`, y la vista previa de impresión del navegador a tamaño carta (sin menú ni controles, legible en blanco y negro, ninguna fila cortada) — cubre: CA1, CA8 — depende: T014, T024
  - nota: verificación manual OK 2026-11-11 (usuario): pantalla con datos, vacía y con error vista en `http://127.0.0.1:8001` contra la base de prueba; «se ve bien, aprobado todo». Antes, capturas a 320×568, 390×844 y 1440 y la hoja impresa en carta, sin desbordes ni controles de menos de 44 px
  - nota: decisión del usuario (2026-11-11): con citas en el periodo pero todas canceladas, «Servicios más solicitados» dice «Todas las citas del periodo están canceladas.»
- [x] T026 Añadir el botón «Imprimir» (nace `hidden`) y `reportes.js` con `defer`, que lo muestra y llama a `window.print()` — `public/js/reportes.js`, `resources/views/reportes/index.blade.php` — hecho cuando: el HTML lleva el botón oculto y el script enlazado, sin `onclick` ni script en línea nuevos, y T013 y T014 siguen pasando · verificación manual: el usuario pulsa «Imprimir» y se abre el diálogo de impresión — cubre: CA8 — depende: T025
  - nota: verificación manual OK 2026-11-11 (usuario), con un cambio pedido en ella: el botón se rotula «Descargar» en lugar de «Imprimir» y hace lo mismo (abre el diálogo de impresión del navegador, donde se guarda como PDF); decisión `refinado` en `design.md` y `system.md`. El test del botón aserta el texto nuevo (falló antes del cambio); el `id` `reporte-imprimir` no cambia. En el navegador (Playwright, antes del cambio de rótulo): el clic llama a `window.print()` una vez y sin JavaScript el botón no se muestra
- [x] T027 Actualizar el guion de capturas (`estado-vacio` pasa a `/admin/reportes?desde=2000-01-01&hasta=2000-01-31`; «Reportes» entra en las pantallas incluidas de `--medir`, con todas sus comprobaciones, y gana una propia: a 320×568 y 390×844, la frase del periodo y «Citas en el periodo» dentro de la primera pantalla) y ejecutarlo — `docs/design/capturar.mjs`, `docs/design/capturas/ (generado por node docs/design/capturar.mjs)` — hecho cuando: `node docs/design/capturar.mjs --medir` sale con código 0, con «Reportes» sin desbordes, recortes, solapes ni objetivos de toque pequeños, sus tres tablas como tarjetas y la comprobación de la primera pantalla en verde, y las capturas `admin-reportes` y `estado-vacio` están rehechas — cubre: CA1 — depende: T026
  - nota: 2026-11-11: `node capturar.mjs --medir` sale con código 0 («medir: todo en orden») contra este clon en `:8001`, con la base de prueba `dentissa_capturas` rehecha (pasos 1–4 de la cabecera) y la línea base de v0.16.0 (72 capturas). «Reportes» va en `PANTALLAS_047`, fuera de la línea base; la comprobación de la primera pantalla fija la ventana a 320×568 y 390×844. `admin-reportes` (390 y 1440) y el recorte `estado-vacio` rehechos; el resto de la galería no se toca
- [x] T028 Verificación final: suite completa, `--group=rnf` de la 047, Pint, `semgrep` y `gitleaks` sobre lo tocado — sin archivos — hecho cuando: `php artisan test` pasa completo, `php artisan test --group=rnf --filter=ReportesRendimientoTest` pasa (se anota el tiempo), `vendor/bin/pint --test` limpio sobre los archivos tocados y los dos escáneres sin hallazgos (la salida de `semgrep` sobre `ReporteCitas` se revisa: la expresión `COUNT(*)` es constante; ninguna supresión sin motivo) — depende: T015, T027
  - nota: 2026-11-11: `php artisan test` 980 pasados (911 de la línea base + 69 de la 047), 0 fallos; `--group=rnf --filter=ReportesRendimientoTest` pasa (el test completo, con la siembra de 10 000 citas, 3,04 s; la página, por debajo de los 2 s que aserta); Pint limpio en los 12 archivos PHP tocados; `gitleaks` sin hallazgos en los commits de la spec
  - nota: A17 — `semgrep scan --config p/php --metrics=off --error` sobre lo tocado, `ReporteCitas` incluido: 23 reglas, 0 hallazgos; la expresión `COUNT(*) AS citas` es constante y sin entrada del usuario; ninguna supresión añadida
- [x] T029 Subir `phpseclib/phpseclib` (dependencia transitiva) de 3.0.57 a la primera versión corregida, ≥ 3.0.58, sin tocar ninguna otra dependencia — `composer.lock` — hecho cuando: `composer audit --locked --ignore-severity=low --ignore-severity=medium` sale con 0 (hoy sale con 1 por PKSA-h7cz-q7zq-mdkt), el diff del lockfile solo cambia ese paquete, la versión elegida tiene al menos 7 días y viene del mismo repositorio (`shared/agent-security.md` §2, anotado en la tarea) y la suite completa pasa — depende: T028
  - nota: `composer update phpseclib/phpseclib --no-install`: 3.0.57 → 3.0.58; el diff del lockfile solo cambia ese paquete (versión, referencia, URL y fecha; `content-hash` igual). Verificación (`agent-security.md` §2): existe en Packagist, publicada el 2026-11-02 (10 días), mismo repositorio `github.com/phpseclib/phpseclib`, licencia MIT, sin scripts de instalación. `composer audit --locked --ignore-severity=low --ignore-severity=medium` sale con 0 (antes 1); suite completa 981 en verde (2026-11-12)
  - nota: añadida por /review: R1 (decisión del usuario, 2026-11-12: corregirlo dentro de la 047)
- [x] T030 Endurecer dos tests de la 047: `test_el_acceso_denegado_queda_en_el_registro_de_auditoria` busca el evento por el actor del test, y un test nuevo, `test_el_aviso_de_error_cumple_el_contraste`, comprueba `--red` sobre `--red-bg` ≥ 4,5:1 y que `.reporte-aviso` usa ese par — `tests/Feature/Reportes/ReportesAccesoTest.php`, `tests/Feature/Reportes/ReportesCssTest.php` — hecho cuando: los dos pasan y el del aviso se comprueba rompiendo un momento el color de `.reporte-aviso` en `reportes.css` (anotado) — cubre: CA7, CA9 — depende: T028
  - nota: R4: el evento se busca por `actor_id` del dentista del test. R5: test nuevo del aviso; comprobado rompiendo el color de `.reporte-aviso` (`--p400` en lugar de `--red`: falla) y restaurado. 24 tests de los dos archivos pasan, Pint limpio (2026-11-12)
  - nota: añadida por /review: R4, R5

## Integración y documentación
- [x] T090 Actualizar `docs/architecture.md` si cambió la estructura: la frase de «Otras rutas» sobre `/admin/reportes`, el módulo `app/Reportes/` y el Gate `ver-reportes` — depende: T028
  - nota: `docs/architecture.md`: fila «Reportes» en los módulos (`app/Reportes/`, `ReportesController`, Gate `ver-reportes`) y la frase de «Otras rutas» sobre `/admin/reportes`
- [x] T091 Actualizar `docs/deployment.md` y `docs/observability.md` si cambiaron variables, entornos, pasos de deploy, logs, eventos de auditoría o métricas: sin cambios en los dos (plan, Observabilidad y Rollout). Sí cambian: `docs/security.md` → «Dónde se aplica» (Gate `ver-reportes`) y una fila en «Superficie de ataque» para `GET /admin/reportes` (fechas `AAAA-MM-DD`, máximo de 366 días, solo lectura); `docs/specs/README.md` (quitar «Reportes» de «Funcionalidades no documentadas como spec»); `docs/roadmap.md` (la 037 prevista ya no adapta «reportes» al móvil; con confirmación del usuario); y `AGENTS.md` (mapa del repositorio: `app/Reportes/`, el número de controladores contado en ese momento, `reportes.css` y `reportes.js`; ruta protegida: pedir aprobación antes de editarla) — depende: T028
  - nota: A14 — controladores contados en el momento: 19 más el `Controller` base (`ls app/Http/Controllers`), no la cifra del plan. `AGENTS.md` editado con la aprobación del usuario (2026-11-11) y `docs/roadmap.md` con su confirmación; `docs/security.md` («Dónde se aplica» y «Superficie de ataque») y `docs/specs/README.md` actualizados; `deployment.md` y `observability.md`, sin cambios
- [x] T092 Marcar spec como `implemented` — depende: T090, T091, T029, T030
  - nota: de nuevo tras /review r1 (T029 y T030 hechas): spec `implemented` e índice al día (2026-11-12)
  - nota: 2026-11-11: los diez CA marcados con sus tests en verde (69 de `tests/Feature/Reportes`, suite completa 980); spec en `implemented` e índice de specs actualizado

## Despliegue (lo ejecuta `/release`)
- [ ] T095 Desplegar a staging y verificar criterios de aceptación. No hay staging (objetivo 5): la comprobación se hace en producción, dentro de T097: como admin, `/admin/reportes` muestra el mes en curso con cifras o el estado vacío, un atajo cambia el periodo y la vista previa de impresión sale sin menú; con una cuenta de dentista, 403 y su fila en `/admin/auditoria`
- [ ] T096 Aprobación humana para producción
- [ ] T097 Desplegar a producción y vigilar métricas del plan (Rollout): `deploy/desplegar.sh vX.Y.Z` (sin migraciones), `verificar.sh` y la comprobación de T095; vigilar la alerta de 5xx. Rollback: `deploy/volver.sh <tag anterior>` sin copia
- [ ] T098 Marcar spec como `released`; en la spec 012, una línea en su Historial: el placeholder de «Reportes» ya no existe (spec 047)

## Cobertura
| Criterio | Tarea(s) de test | Tarea(s) de implementación |
|---|---|---|
| CA1 | T011, T012, T013 | T022, T023, T025, T027 |
| CA2 | T012, T013 | T021, T023 |
| CA3 | T011, T013 | T022, T023 |
| CA4 | T011, T013 | T022, T023 |
| CA5 | T011, T013 | T022, T023 |
| CA6 | T013 | T024 |
| CA7 | T012, T013, T030 | T021, T023, T024 |
| CA8 | T013, T014 | T024, T025, T026 |
| CA9 | T010, T030 | T020, T023 |
| CA10 | T013 | T022, T023 |

| Amenaza (TM#) | Tarea(s) de control | Tarea(s) de test |
|---|---|---|
| TM1 | T020, T023 | T010 |
| TM2 | T021, T022 | T012 |
| TM3 | T021 (peticiones repetidas: residuo aceptado en el plan) | T012, T015 |
| TM4 | T022, T023 | T013 |
| TM5 | T024 | T013 |
| TM6 | T023 (la ruta sigue en el grupo que audita los 403; sin código nuevo) | T010 |
| TM7 | T023 (la ruta sigue en el grupo `web`, con su no-caché; sin código nuevo) | T010 |

| Cambio del plan (módulo) | Tarea(s) |
|---|---|
| `app/Reportes/ReporteCitas.php` | T021, T022 |
| `app/Autorizacion/VerReportes.php` | T020 |
| `app/Providers/AppServiceProvider.php` | T020 |
| `app/Http/Controllers/ReportesController.php` | T023 |
| `routes/web.php` | T023 |
| `resources/views/reportes/index.blade.php` | T023, T024, T025, T026 |
| `public/css/reportes.css` | T025 |
| `public/js/reportes.js` | T026 |
| `config/clinica.php` | T024 |
| `resources/views/placeholder.blade.php` | ninguna: se conserva sin cambios (plan D8) |
| `docs/design/system.md`, `docs/design/system.html` | T001 |
| `docs/design/capturar.mjs` | T027 |
| `tests/Feature/Reportes/` | T010–T015, T030 |
| `composer.lock` (fuera del plan: /review R1) | T029 |
| `tests/Feature/Concerns/EscenarioEstadosCita.php` | ninguna: se reutiliza sin cambios (T011) |

| Requisito no funcional | Tarea(s) de test o verificación manual (T095) |
|---|---|
| Accesibilidad (AA, toque, sin desbordes, encabezados de tabla) | T013, T014 (contraste y `--touch-min`), T025 (verificación manual), T027 (`capturar.mjs --medir`) |
| Rendimiento (10 000 citas, 366 días, < 2 s en local) | T015, T028 |
| Exactitud (dos consultas iguales dan lo mismo) | T011 |
| Descubribilidad (no se indexa) | T010 (`test_sin_sesion_lleva_al_inicio_de_sesion`) |
