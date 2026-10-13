---
spec: 034-movil-personal
plan: plan.md
status: draft
---

# Tareas · 034 Móvil para el personal y listas en tarjetas

Formato:
`- [ ] T### [P] <verbo + qué> — <archivos> — hecho cuando: <criterio> — cubre: CA# — depende: T###`
- T001–T089: trabajo. T090–T099: reservadas.
- `[P]` = paralelizable con las demás `[P]` de su grupo una vez cumplidas sus dependencias (no
  comparte archivos con otra tarea abierta).
- Lo generado por un comando cuenta como un archivo: `android/ (generado por npx cap add android)`.
- `[-]` = obsoleta (`— obsoleta: <motivo> (AAAA-MM-DD)`): no cuenta como abierta ni como hecha.
- Bajo una tarea: `  - nota: …`, `  - bloqueo: AAAA-MM-DD — <qué falta> — <skill a ejecutar>`.
- La comprobación a mano va en la tarea que construye la pieza:
  `hecho cuando: T010 pasa · verificación manual: <qué, dónde, contra qué>`.
- «Pantallas incluidas» y «tablas incluidas»: las de la spec (Criterios) y la tabla D2 del plan.
  «Bloque tocado» es un elemento cuyo marcado cambia la tarea: pierde su `style=""` y pasa a una
  clase del bloque `/* Spec 034 */` de `app.css`, con los mismos valores (D10b del plan).

## Constitution Check
Sobre el desglose (el análisis completo está en `plan.md`):

| Principio | Resultado | Evidencia en las tareas |
|---|---|---|
| P1 Spec aprobada antes de código | ✅ | Spec y plan aprobados el 2026-10-13; las tareas nacen en `draft` |
| P2 Test que falla antes y pasa después | ✅ | T010–T015 se escriben antes que la implementación (T020–T032) y fallan hoy; cada implementación `depende:` de su test. Las comprobaciones nuevas de `--medir` (T015) se comprueban rompiendo en las tareas que las hacen pasar |
| P3 Autorización en servidor | ➖ | Ninguna tarea toca rutas, Gates, controladores ni consultas |
| P4 Entrada validada y SQL con bindings | ➖ | Ninguna tarea añade entradas ni consultas; el contador del filtro se calcula en la vista con los parámetros que ya llegan |
| P5 Nada sensible en el repo | ✅ | T015 usa la base anonimizada `dentissa_capturas` y guarda la línea base del escritorio fuera del repositorio; T040 pasa gitleaks y borra la base de prueba |
| P6 Sin vulnerabilidades críticas/altas | ✅ | Ninguna tarea añade dependencias; T002 registra la línea base y T040 pasa `composer audit`, `npm audit` y `trivy fs` |
| P7 Auditoría | ➖ | No toca datos sensibles nuevos, autenticación ni permisos |
| P8 Correlación y logs limpios | ➖ | Sin logs nuevos |
| P9 La interfaz usa solo el sistema de diseño | ✅ | T001 incorpora al sistema los tres tokens y los componentes antes que el CSS (T020–T031). T012 comprueba solo tokens (con control positivo) en el bloque 034 de `movil.css` y `app.css`, los mismos valores que los `style=""` sustituidos y el contraste de los pares nuevos; T010 y T011, que los bloques tocados no tienen `style=""`. Verificación manual contra `design/opcion-a.html` y NVDA en las tareas que construyen cada pieza |

## Preparación
- [ ] T001 Incorporar al sistema de diseño los «Cambios a incorporar al sistema» del plan: `system.md` 1.4.0 → 1.5.0 con su fila en Decisiones (opción A, 2026-10-13); los tokens `--tarjeta-etiqueta-w: 88px`, `--logo-movil-h: 40px` y `--space-detalle: 18px`; `--text-marca` anotado como sin uso desde la 034; los componentes «Tarjeta de lista (móvil)» y «Filtro plegable» con sus pares de contraste (etiqueta `--p600`/`--white`, contador `--white`/`--p500`) y estados; «Botón Menú» solo con icono y «Marca del menú del móvil» como logo con máscara; DS6 y DS8 «parcial (033, 034)»; y la sección equivalente en `system.html` — docs/design/system.md, docs/design/system.html — hecho cuando: `system.md` está en 1.5.0 con los tres tokens y los cuatro componentes, `system.html` se abre sin errores en consola y muestra la tarjeta, el filtro, el botón y el logo con solo tokens, y sus pares nuevos cumplen AA (anotado) — cubre: CA1, CA2, CA3, CA4, CA5, CA9, CA10
- [ ] T002 Registrar la línea base antes de cualquier test nuevo: `php artisan test` completo, `composer audit --locked` y `npm audit --audit-level=high` — (sin archivos) — hecho cuando: la nota de la tarea anota el número de tests en verde, los fallos (si hay) y el resultado de las dos auditorías — depende: T001

## Tests (antes de implementar)
- [ ] T010 Escribir en `tests/Feature/MovilPersonalTest.php` (nuevo, con el trait `ReglasCss`) los tests de marcado de las tablas incluidas, con los datos creados en el test y las cuentas de los cuatro roles: cada tabla incluida tiene `tabla-tarjetas`; cada `<td>` del cuerpo tiene `data-label` igual al texto de su `<th>` (salvo «Teléfono» en pacientes) y las mismas clases de columna; `celda-cab`, `celda-estado` y `celda-acciones` según D2; `solo-escritorio` en correo, doctor, ciudad, tipo de sangre y registrado de pacientes y en descripción y categoría de servicios, y no en las esenciales; la fila de una cita de hoy lleva `fila-hoy` y el texto «Hoy»; «Ver detalle» tiene `aria-label="Ver detalle"` y su texto en `.solo-movil`; y las filas y celdas tocadas no tienen `style=""` (con control positivo) — tests/Feature/MovilPersonalTest.php — hecho cuando: los tests fallan hoy por la razón esperada (no hay `tabla-tarjetas` ni `data-label`; anotado) y la suite previa sigue como en T002 — cubre: CA1, CA2, CA3, CA4, CA8 — depende: T002
- [ ] T011 Añadir a `MovilPersonalTest` los tests del filtro y del detalle: en la lista de citas de los tres roles del personal, el orden del formulario (buscador, fila de atajos con «Hoy», «Esta semana» y `button.filtro-btn` con `type="button"`, `aria-expanded="false"` y `aria-controls="filtro-panel"`, y después `#filtro-panel` con desde, hasta, dentista salvo para la dentista, estado, «Filtrar» y «Limpiar»); sin filtros aplicados, sin contador ni `aria-label`; con hasta y estado aplicados, `.filtro-contador` con 2 y `aria-label="Filtros, 2 aplicados"`; la vista empuja `filtros.js` con `?v=`; y en el detalle de cita, el orden `detalle-datos`, `detalle-lateral`, `detalle-control` (solo admin) y `detalle-historial` dentro de `.detalle-cita`, con la rejilla de 3 columnas como `detalle-datos-rejilla`, sin `style=""` en esos bloques — tests/Feature/MovilPersonalTest.php — hecho cuando: los tests fallan hoy por la razón esperada (anotado) — cubre: CA5, CA6, CA8 — depende: T010
- [ ] T012 Añadir a `MovilPersonalTest` los tests de estilos: en `movil.css`, dentro de la media query, las reglas de D1 (`thead` oculto, fila en tarjeta con sus tokens, celda en rejilla `var(--tarjeta-etiqueta-w) minmax(0, 1fr)`, `::before` con `attr(data-label)`, `solo-escritorio` oculto, acciones con `flex-wrap`), D6 (botón solo con `filtro-js`, panel oculto con `plegado`), D7 (una columna) y D9/D10 (botón de `--touch-min` sin relleno; logo de `--logo-max-w` × `--logo-movil-h` con fondo `--p600`, `mask` y `-webkit-mask` con el logo actual); fuera de la media query, solo las reglas previstas por el plan; el bloque `/* Spec 034 */` de `app.css` solo con tokens (con control positivo) y la rejilla de escritorio de `.detalle-cita` (D7); los tres tokens en `app.css` iguales a `system.md`; y el contraste de etiqueta, contador y logo calculado desde los tokens — tests/Feature/MovilPersonalTest.php — hecho cuando: los tests fallan hoy por la razón esperada (anotado) — cubre: CA1, CA5, CA6, CA9, CA10, CA11 — depende: T011
- [ ] T013 Añadir a `MovilPersonalTest` el test del contrato de `filtros.js` (texto, con docblock que remite a `--medir`): existe; busca `.filtro-citas` y sale si no está; añade `filtro-js` y `plegado`; el clic en `.filtro-btn` alterna `plegado` y `aria-expanded`; sin `innerHTML`, `outerHTML`, `insertAdjacentHTML`, `eval(`, `new Function` ni `max-width` — tests/Feature/MovilPersonalTest.php — hecho cuando: el test falla hoy porque `public/js/filtros.js` no existe — cubre: CA5 — depende: T012
- [ ] T014 [P] Cambiar en `MovilPacienteTest` los tests de la 033 que cambian por la 034: el botón «Menú» sin texto y con `aria-label="Menú"`; la marca del menú vacía con `role="img"` y `aria-label="Melissa López N."`; las declaraciones de `.sidebar-marca` según D10 (en lugar de `color`, `font-size` y `font-weight` de texto); `test_los_textos_largos_parten_de_linea` según D8 (`.content` con `break-word`, sin regla `.content table`, `anywhere` en `.texto-partible` y `.user-name`); el contrato de `menu.js` con `role` `dialog` y `aria-modal` al abrir y su retirada al cerrar (033/R9) y un `focusout` que olvida `ultimoFoco` si el elemento sigue visible (033/R21); y la lista de reglas fuera del móvil ampliada con las del plan — tests/Feature/MovilPacienteTest.php — hecho cuando: los tests cambiados fallan hoy por la razón esperada y los demás de `MovilPacienteTest` siguen en verde (anotado) — cubre: CA7, CA9, CA10, CA11 — depende: T002
- [ ] T015 [P] Ampliar `capturar.mjs` según la Estrategia de pruebas del plan: vistas nuevas de las pantallas incluidas del personal (paneles, citas, detalle, pacientes y servicios de los roles que las tienen) y de las listas del paciente en `--medir`, a 320, 390, 844 × 390 y 1440 y sin JavaScript a 390; comprobaciones de solapes (ningún control visible cruza a otro ni sale de su tarjeta), etiqueta visible en cada celda de una tarjeta, filtro (plegado al cargar, «Filtros» lo despliega y cambia `aria-expanded`, desplegado sin JavaScript), detalle en una columna en el orden de D7, logo visible con fondo `--p600` y ancho `--logo-max-w`, botón «Menú» sin texto visible, de 44 × 44 y con nombre, y `<aside>` como diálogo al abrir; y la comparación a 1024 y 1440 con una línea base de antes guardada fuera del repositorio. Montar la base de prueba según la cabecera y mantenerla hasta T040 — docs/design/capturar.mjs — hecho cuando: `node capturar.mjs --seco --medir` lista las comprobaciones nuevas, `--medir` contra la base de prueba falla hoy (anotado: p. ej. tabla de citas de 583 px a 390 sin tarjetas, botón «Menú» con texto) y la línea base de 1024 y 1440 de las pantallas incluidas del personal existe — cubre: CA1, CA2, CA3, CA4, CA5, CA6, CA7, CA8, CA9, CA10, CA11 — depende: T002

## Implementación
- [ ] T020 Añadir a `:root` de `app.css` los tres tokens de T001 y abrir al final el bloque `/* Spec 034 */` (vacío salvo su comentario) — public/css/app.css — hecho cuando: pasa dos veces la parte de tokens de T012 y siguen en verde los tests de la 033 — cubre: CA1, CA10 — depende: T001, T012
- [ ] T021 Añadir a `movil.css` el bloque «Spec 034» de D1, D8, D9 y D10 (tarjetas genéricas, `.content` con `break-word` y `anywhere` en `.texto-partible`, sin `.content table`; botón «Menú» solo icono; logo con máscara) y las reglas fuera de la media query del plan (`.filtro-btn, .solo-movil` ocultos; `.filtro-panel` con `display: contents`) — public/css/movil.css — hecho cuando: pasan dos veces las partes de D1, D8, D9, D10 y «fuera del móvil» de T012 y de T014 — cubre: CA1, CA7, CA9, CA10, CA11 — depende: T012, T014, T020
- [ ] T022 Cambiar el layout según D9 y D10: el botón «Menú» sin el texto y con `aria-label="Menú"`; `.sidebar-marca` vacío con `role="img"` y `aria-label="Melissa López N."` — resources/views/layouts/app.blade.php — hecho cuando: pasan dos veces las partes del botón y la marca de T014 · verificación manual: el menú de cada rol a 320 y 390 contra `design/opcion-a.html` (marco 5): botón solo con icono y logo en el color de la marca; a 1440, la barra lateral igual que antes — cubre: CA9, CA10 — depende: T014, T021
- [ ] T023 Cambiar `menu.js` según 033/R9 y R21: al abrir, el `<aside>` pasa a `role="dialog"` y `aria-modal="true"`, y al cerrar se quitan; un `focusout` olvida `ultimoFoco` cuando el foco se pierde sin que el elemento deje de verse — public/js/menu.js — hecho cuando: el contrato de `menu.js` de T014 pasa dos veces, `node --check` no da errores y `--medir` ya no da fallos de menú en ningún rol (casos (a)–(d) de 033 T064 incluidos) · verificación manual: con NVDA, al abrir el menú se anuncia «Menú, diálogo», el botón «Menú, botón, contraído/expandido» y el logo «Melissa López N., gráfico» — cubre: CA9, CA10 — depende: T014, T015, T022
- [ ] T024 Pasar a tarjetas las tablas del paciente según D1 y D2: «Mis citas» (próximas y pasadas) y las citas pasadas de su panel, con `tabla-tarjetas`, `data-label`, clases de celda y los `style=""` de las filas y celdas tocadas a clases del bloque 034 de `app.css` — resources/views/citas/cliente-index.blade.php, resources/views/dashboard/cliente.blade.php, public/css/app.css — hecho cuando: pasa dos veces la parte del paciente de T010, `--medir` no da fallos en esas vistas y a 1440 son iguales a la línea base · verificación manual: «Mis citas» y el panel del paciente a 320, 390 y 1440 contra `design/opcion-a.html` (marco 1); con NVDA, cada dato de la tarjeta se lee con su etiqueta — cubre: CA1, CA8, CA11 — depende: T010, T015, T021
- [ ] T025 Aplicar D8 en el paciente: la rejilla de datos del detalle de la cita y la tarjeta de la próxima cita del panel llevan `texto-partible` y sus `style=""` pasan a clases del bloque 034 — resources/views/citas/cliente-show.blade.php, resources/views/dashboard/cliente.blade.php, public/css/app.css — hecho cuando: pasa dos veces la parte de textos de T014 · verificación en navegador: con Playwright contra el servidor de capturas a 320 px, el detalle de una cita y el panel del paciente con un dentista y un servicio temporales de una palabra de 30 letras dan `scrollWidth` 320 (como 033 T061), con los datos restaurados — cubre: CA7 — depende: T014, T024
- [ ] T026 Pasar a tarjetas la tabla de la lista de citas del personal según D1, D2 y D5: `tabla-tarjetas`, `data-label`, clases de celda, `fila-hoy` en lugar del `style` de la fila de hoy y «Ver detalle» con `aria-label` y texto en `.solo-movil`, con los `style=""` tocados a clases del bloque 034 — resources/views/citas/index.blade.php, public/css/app.css — hecho cuando: pasa dos veces la parte de la lista de citas de T010 y `--medir` no da fallos en la tabla de citas de los tres roles · verificación manual: la lista de citas a 320, 390 y 1440 contra `design/opcion-a.html` (marco 2) — cubre: CA2, CA8, CA11 — depende: T010, T015, T021
- [ ] T027 Reordenar el filtro de citas según D6 (`.filtro-citas`, buscador, fila de atajos con «Filtros» y contador, `#filtro-panel`), empujar `filtros.js` con `@push('scripts')` y `?v=`, crear `public/js/filtros.js` según «Script `filtros.js`» del plan y añadir a `movil.css` las reglas de D6 — resources/views/citas/index.blade.php, public/js/filtros.js, public/css/movil.css — hecho cuando: pasan dos veces T011 (parte del filtro), T013 y la parte de D6 de T012, `node --check public/js/filtros.js` no da errores y `--medir` no da fallos de filtro (con y sin JavaScript) · verificación manual: el filtro plegado y desplegado a 390 contra `design/opcion-a.html` (marcos 2 y 3), y a 1440 igual que la línea base; con NVDA, «Filtros, botón, contraído» y, con filtros aplicados, «Filtros, 2 aplicados» — cubre: CA5, CA8, CA11 — depende: T011, T013, T015, T026
- [ ] T028 Pasar a tarjetas las tablas de citas de hoy de los paneles de administración y asistente según D1 y D2, con los `style=""` tocados a clases del bloque 034 — resources/views/dashboard/admin.blade.php, resources/views/dashboard/asistente.blade.php, public/css/app.css — hecho cuando: pasa dos veces la parte de los paneles de T010 y `--medir` no da fallos en esos paneles · verificación manual: los dos paneles a 390 y 1440 contra `design/opcion-a.html` (marco 2) y la línea base — cubre: CA2, CA8, CA11 — depende: T010, T015, T021
- [ ] T029 Pasar a tarjetas la tabla de pacientes según D1 y D2 (`solo-escritorio` en correo, doctor, ciudad, tipo de sangre y registrado), con los `style=""` tocados a clases del bloque 034 — resources/views/pacientes/index.blade.php, public/css/app.css — hecho cuando: pasa dos veces la parte de pacientes de T010 y `--medir` no da fallos en pacientes de los tres roles · verificación manual: pacientes a 320, 390 y 1440 contra `design/opcion-a.html` (marco 4) — cubre: CA3, CA8, CA11 — depende: T010, T015, T021
- [ ] T030 Pasar a tarjetas la tabla de servicios según D1 y D2 (`solo-escritorio` en descripción y categoría), con los `style=""` tocados a clases del bloque 034 — resources/views/servicios/index.blade.php, public/css/app.css — hecho cuando: pasa dos veces la parte de servicios de T010 y `--medir` no da fallos en servicios · verificación manual: servicios a 390 y 1440 contra `design/opcion-a.html` (tarjeta del marco 4 con duración, estado y acciones) — cubre: CA4, CA8, CA11 — depende: T010, T015, T021
- [ ] T031 Reordenar el detalle de cita del personal según D7 (`.detalle-cita` con `detalle-datos`, `detalle-lateral`, `detalle-control` y `detalle-historial`; `detalle-datos-rejilla`), con la rejilla de escritorio y las clases de los bloques tocados en el bloque 034 de `app.css` y la columna única del móvil en `movil.css` — resources/views/citas/show.blade.php, public/css/app.css, public/css/movil.css — hecho cuando: pasan dos veces la parte del detalle de T011 y la de D7 de T012, `--medir` no da fallos en el detalle a 320, 390 y 844 × 390, y a 1024 y 1440 el detalle del admin y del asistente es igual a la línea base · verificación manual: el detalle a 390 en el orden de la spec contra `design/opcion-a.html` y a 1440 igual que antes — cubre: CA6, CA8, CA11 — depende: T011, T012, T015, T021
- [ ] T032 Corregir los bloques que `--medir` aún señale en las pantallas incluidas tras T024–T031 (p. ej. el panel de la dentista, que no tiene tabla), con un test en `MovilPersonalTest` antes de cada corrección y sus `style=""` a clases del bloque 034, o anotar que no hay ninguno — (según lo que señale `--medir`; como máximo 3 archivos por corrección) — hecho cuando: `--medir` no da fallos en ninguna pantalla incluida a ningún ancho, con y sin JavaScript, y la nota lista lo corregido o «sin bloques extra» — cubre: CA8 — depende: T024, T025, T026, T027, T028, T029, T030, T031
- [ ] T033 Rehacer con `capturar.mjs` las capturas de las pantallas incluidas a 320, 390 y 1440 y las del menú abierto de cada rol, y poner sus filas en la tabla de pantallas de `system.md` — docs/design/capturas/ (generado por node capturar.mjs), docs/design/system.md — hecho cuando: las capturas existen, a 1440 son iguales a la línea base salvo ruido de renderizado (anotado) y `system.md` las lista · verificación manual: el usuario revisa las capturas a 320 y 390 contra `design/opcion-a.html` — cubre: CA1, CA2, CA3, CA4, CA5, CA6, CA9, CA10, CA11 — depende: T032

## Integración y documentación
- [ ] T040 Hacer la verificación final: `php artisan test` completo con la línea base de T002 más los tests nuevos, todos en verde; `pint --test` en los PHP tocados; `node docs/design/capturar.mjs --medir` con código 0 (todas las pantallas incluidas y las 7 del paciente de la 033, todos los anchos, 4 roles, sin JS); gitleaks sobre el rango de la spec sin fugas; `composer audit`, `npm audit` y `trivy fs --severity HIGH,CRITICAL` sin avisos nuevos respecto a T002; y la base `dentissa_capturas`, su servidor, el volumen de Playwright y la línea base del escritorio borrados — (sin archivos) — hecho cuando: todo lo anterior pasa y queda anotado — cubre: CA1, CA2, CA3, CA4, CA5, CA6, CA7, CA8, CA9, CA10, CA11 — depende: T033
- [ ] T090 Actualizar `docs/architecture.md`: en Frontend, `public/js/filtros.js` (filtro plegable de la lista de citas, segundo script propio) y, en `movil.css`, las tablas en tarjetas (`tabla-tarjetas`) y el logo con máscara
- [ ] T091 Actualizar `docs/deployment.md` y `docs/observability.md` si cambiaron variables, entornos, pasos de deploy, logs, eventos de auditoría o métricas
  - nota: el plan no prevé cambios (sin variables, migraciones, logs ni métricas); se anota «no aplica» al cerrar.
- [ ] T092 Marcar spec como `implemented`

## Despliegue (lo ejecuta `/release`)
- [ ] T095 Desplegar a staging y verificar criterios de aceptación
  - No hay staging (030): las comprobaciones en un móvil real se hacen en producción, en T097.
- [ ] T096 Aprobación humana para producción
- [ ] T097 Desplegar a producción y vigilar métricas del plan (Rollout)
  - verificación manual: desde el móvil del usuario, en la dirección pública, «Mis citas» y el panel del paciente con sus botones a la vista; como personal, el panel, la lista de citas con el filtro plegado y desplegado, el detalle de una cita en una columna, pacientes y servicios, sin desplazamiento lateral ni elementos encimados; el botón «Menú» solo con icono y el logo en el menú.
  - Sin errores 500 en `laravel.log` ni respuestas 5xx en el log de nginx durante 15 minutos.
- [ ] T098 Marcar spec como `released`

## Cobertura
| Criterio | Tarea(s) de test | Tarea(s) de implementación |
|---|---|---|
| CA1 | T010, T012, T015 | T020, T021, T024, T033 |
| CA2 | T010, T015 | T026, T028, T033 |
| CA3 | T010, T015 | T029, T033 |
| CA4 | T010, T015 | T030, T033 |
| CA5 | T011, T012, T013, T015 | T027, T033 |
| CA6 | T011, T012, T015 | T031, T033 |
| CA7 | T014, T015 | T021, T025 |
| CA8 | T010, T011, T015, T040 | T024, T026, T027, T028, T029, T030, T031, T032 |
| CA9 | T012, T014, T015 | T021, T022, T023, T033 |
| CA10 | T012, T014, T015 | T020, T021, T022, T023, T033 |
| CA11 | T012, T014, T015 | T021, T024, T026, T027, T028, T029, T030, T031, T033 |

| Amenaza (TM#) | Tarea(s) de control | Tarea(s) de test |
|---|---|---|
| — (el plan no tiene amenazas: no toca datos, permisos ni entradas) | — | — |

| Cambio del plan (módulo) | Tarea(s) |
|---|---|
| Sistema de diseño (`system.md`, `system.html`) | T001, T033 |
| Estilos del móvil (`public/css/movil.css`) | T021, T027, T031 |
| Estilos existentes (`public/css/app.css`) | T020, T024, T025, T026, T028, T029, T030, T031 |
| Script del filtro (`public/js/filtros.js`) | T027 |
| Script del menú (`public/js/menu.js`) | T023 |
| Layout (`layouts/app.blade.php`) | T022 |
| Lista de citas del personal (`citas/index`) | T026, T027 |
| Detalle de cita del personal (`citas/show`) | T031 |
| Pacientes (`pacientes/index`) | T029 |
| Servicios (`servicios/index`) | T030 |
| Paneles (`dashboard/admin`, `asistente`, `cliente`, `dentista`) | T024, T025, T028, T032 |
| Mis citas y detalle del paciente (`citas/cliente-index`, `cliente-show`) | T024, T025 |
| Tests (`MovilPersonalTest`, `MovilPacienteTest`) | T010, T011, T012, T013, T014 |
| Capturas y medidas (`capturar.mjs`) | T015, T033 |

| Requisito no funcional | Tarea(s) de test o verificación manual (T095) |
|---|---|
| WCAG 2.1 AA (1.4.10, 1.3.1, 1.4.11, 2.4.7, 4.1.2, 1.4.1) | T010, T011, T012, T014, T015, T040 · verificación manual con NVDA en T023, T024 y T027 |
| Toque ≥ 44 × 44 px | T015, T040 |
| Medido a 320, 390, 844 × 390 y 1440, también sin JS | T015, T040 |
| Escritorio sin cambios contra capturas de antes | T015, T024, T026, T027, T028, T031, T033 |
