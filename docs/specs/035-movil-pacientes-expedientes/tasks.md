---
spec: 035-movil-pacientes-expedientes
plan: plan.md
impl_base: 883af40
status: approved
---

# Tareas · 035 Móvil para pacientes y expedientes del personal

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
- «Pantallas incluidas»: las de la spec (Criterios) y las de D10 del plan.

## Constitution Check
Sobre el desglose (el análisis completo está en `plan.md`, contra la constitución 1.3.0):

| Principio | Resultado | Evidencia en las tareas |
|---|---|---|
| P1 Spec aprobada antes de código | ✅ | Spec, `design.md` y plan aprobados el 2026-10-16; estas tareas se aprueban antes de `/implement` |
| P2 Test que falla antes y pasa después | ✅ | T010–T014 se escriben antes que la implementación (T020–T026) y fallan hoy; cada implementación `depende:` de su test. Las comprobaciones nuevas de `--medir` (T014) se comprueban rompiendo en las tareas que las hacen pasar |
| P3 Autorización en servidor | ➖ | Ninguna tarea toca rutas, controladores ni consultas |
| P4 Entrada validada y SQL con bindings | ➖ | Ninguna tarea añade entradas ni consultas; los `id`, `for` y `aria-*` no son datos |
| P5 Nada sensible en el repo | ✅ | T002 y T014 usan la base anonimizada `dentissa_capturas` y guardan la línea base fuera del repositorio; T040 pasa gitleaks y borra la base, la línea base y la copia temporal de v0.8.0 |
| P6 Sin vulnerabilidades críticas/altas | ✅ | Ninguna tarea añade dependencias; T002 registra la línea base y T040 pasa `composer audit`, `npm audit` y `trivy fs` |
| P7 Auditoría | ➖ | No toca autenticación, permisos ni datos nuevos |
| P8 Correlación y logs limpios | ➖ | Sin logs nuevos |
| P9 La interfaz usa solo el sistema de diseño | ✅ | T001 incorpora al sistema los cuatro tokens y los componentes antes que el CSS (T020, T021). T012 comprueba solo tokens (con control positivo) en el bloque 035 de `app.css` y en el de `movil.css`, los tokens iguales a `system.md` y los botones del menú sin recuadro; T010 y T011, que los `style=""` imprescindibles pasan a clases y que los campos tienen `for`/`id`. Verificación manual contra `design/opcion-a.html` y `opcion-c.html` y con NVDA en las tareas que construyen cada pieza |
| P10 Las rutas indexables son descubribles | ➖ | Ninguna tarea toca `/registro` ni otra ruta indexable (`docs/seo.md`) |

## Preparación
- [x] T001 Incorporar al sistema de diseño los «Cambios a incorporar al sistema» del plan (1.5.0 → 1.6.0): los nueve tokens (`--ficha-lateral-w`, `--expediente-lateral-w`, `--perfil-avatar-w`, `--perfil-avatar-movil`, `--perfil-iniciales-text`, `--perfil-iniciales-movil`, `--perfil-nombre-text`, `--perfil-nombre-movil` y `--weight-bold`) en sus tablas; en «Menú del móvil», «Menú» y «Cerrar menú» solo con el icono, sin recuadro ni fondo (sustituye la línea del aspecto de `.btn-outline` con borde `--border-control`); los componentes «Ficha en una columna», «Perfil compacto del paciente» (exclusivo de la ficha, `design.md`), «Acciones de la barra en el móvil», «Acciones de formulario» y el foco del sistema en los campos de los formularios con estilo propio (D13); y la fila de «Decisiones» del 2026-10-16 (spec 035) — docs/design/system.md, docs/design/system.html — hecho cuando: `system.md` está en 1.6.0 con todo lo anterior, `system.html` muestra los nueve tokens y los componentes nuevos, y `aidd.py validate` no da errores — cubre: CA1, CA4, CA7, CA11
  - nota: `system.md` 1.6.0 con los nueve tokens (lista «Spec 035» y `--weight-bold` en la tabla de tipografía), el foco del sistema en «Campo de formulario», «Menú» y «Cerrar menú» sin recuadro, los componentes «Ficha en una columna», «Perfil compacto del paciente», «Acciones de la barra en el móvil» y «Acciones de formulario», y la fila de Decisiones del 2026-10-16. `system.html`: tokens de la 035, muestra del botón del menú sin borde y sección «Perfil compacto, acciones y campo con foco (spec 035)»; los «no cumple» que muestra la página son los pares previos de la deuda DS1, sin pares nuevos. `aidd.py validate` sin errores; `MovilPersonalTest` y `MovilPacienteTest` en verde.
  - nota: A24 — `design.md` gana en «Cambios a incorporar» la fila del foco del sistema en los formularios (D13).
- [x] T002 Registrar la línea base antes de cualquier test nuevo: `php artisan test` completo, `composer audit --locked`, `npm audit --audit-level=high` y `trivy fs --severity HIGH,CRITICAL` — (sin archivos) — hecho cuando: la nota de la tarea anota el número de tests en verde, los fallos (si hay) y el resultado de las tres auditorías — depende: T001
  - nota: línea base del 2026-10-16 (rama en 2b6500c): `php artisan test` 310 tests en verde (5099 aserciones), sin fallos; `composer audit --locked` sin avisos; `npm audit --audit-level=high` 0 vulnerabilidades; `trivy fs --severity HIGH,CRITICAL` solo el falso positivo conocido de `vendor/mockery/mockery/docs/requirements.txt` (9, fuera de git y de la imagen).

## Tests (antes de implementar)
- [x] T010 Escribir `tests/Feature/MovilPacientesExpedientesTest.php` (nuevo, con `DatabaseTransactions`, `EscenarioEstadosCita` y `ReglasCss`, y un expediente, citas y más de 20 expedientes de la dentista creados en el test) con los tests de marcado del plan (Estrategia): el historial de citas de la ficha y la lista de expedientes con `tabla-tarjetas`, `data-label` igual a su `<th>` y las clases de D3; «Ver detalle» con `aria-label`, `.solo-movil` e icono con `aria-hidden`, y el aviso sin citas; `.ficha-paciente` y `.ficha-expediente` sin `grid-template-columns` en línea (con control positivo) y sus hijos en el orden de CA1 y CA4; `.expediente-cabecera` con el título en `texto-partible`; `.acciones-ficha` en la barra (dos enlaces para admin y dentista, uno para la asistente) y `.acciones-formulario` en los tres formularios; `texto-partible` en los textos de D6 y `.dato-recortado` en el correo, sin `white-space` en línea; `.paginacion` sin `style=""`; migas de las cuatro vistas con `nav.migas` y `.migas-actual`, sin `style=""` (D14); perfil compacto de D12: `.perfil-identidad`, `.perfil-cab` con exactamente dos hijos (el avatar, con `aria-hidden="true"`, y un `.perfil-texto` con nombre, edad y tipo de sangre), `.perfil-avatar`, `.perfil-nombre` y una `.perfil-dato` por dato de contacto, con su `.perfil-etiqueta` en `.solo-movil` e icono con `aria-hidden`, sin `text-align`, `display` ni tamaño del avatar en línea; tipos `tel`, `email` y `date` donde corresponde (CA5) — tests/Feature/MovilPacientesExpedientesTest.php — hecho cuando: los tests fallan hoy por la razón esperada (no hay `tabla-tarjetas`, `ficha-paciente`, `perfil-cab` ni `nav.migas`; anotado); los de tipos de campo, que ya se cumplen, se comprueban rompiendo; la suite previa sigue como en T002 — cubre: CA1, CA2, CA3, CA4, CA5, CA7, CA8, CA9 — depende: T002
  - nota: fallan hoy por la razón esperada 7 de 8 tests: no hay `tabla-tarjetas` en el historial ni en la lista de expedientes, ni `.ficha-paciente`, `.acciones-ficha`, `texto-partible` en los textos de D6, `div#migas.migas` ni `.perfil-identidad`. El de tipos de campo ya se cumple: comprobado rompiendo (`telefono_emergencia` a `type="text"` lo hace fallar; restaurado). Suite: 311 en verde más los 7 nuevos en rojo; pint pasa.
  - nota: A25 — las migas se comprueban como `div#migas.migas` (el componente de la 033), no `<nav>`.
- [ ] T011 Añadir a `MovilPacientesExpedientesTest` los tests de etiquetas y errores (D7): en el alta y la edición del paciente, el alta de expediente, la ficha del expediente con formulario y el buscador de expedientes, cada `label.field-label` con `for` que apunta a un `input`, `select` o `textarea` del mismo formulario; los cuatro títulos de la ficha del expediente son `<label for class="etiqueta-campo">` para admin y dentista, y la asistente no tiene formulario; tras un envío con un error en **cada campo que el controlador valida** (alta del paciente, alta de expediente y edición del expediente), cada campo muestra su mensaje junto a él (`.error-msg` con `id`), con `aria-invalid="true"` y `aria-describedby` que apunta a ese mensaje, y el `old()` se conserva; sin error no hay mensaje ni `aria-describedby` — tests/Feature/MovilPacientesExpedientesTest.php — hecho cuando: fallan hoy porque las etiquetas no tienen `for` y la mayoría de campos validados no muestran su mensaje (anotado), y los demás de T010 siguen como estaban — cubre: CA5, CA6 — depende: T010
- [ ] T012 Añadir a `MovilPacientesExpedientesTest` los tests de estilos: bloque `/* Spec 035 */ … /* Fin spec 035 */` de `app.css` solo con tokens y sin media queries (con control positivo), con las clases de D1, D2, D4, D5, D6, D3 (paginación) y D12 (incluidas `.perfil-nombre` y las iniciales con `--perfil-iniciales-text` y `--weight-bold`) y los valores de hoy, `.etiqueta-campo { display: block }` (D7) y el foco del sistema de D13 en `.form-paciente` y `.form-expediente`; en la media query de `movil.css`, las reglas de D1–D6 (con `.topbar-title` y `.migas-actual` con `overflow-wrap: anywhere`) y D12 (iniciales de `--perfil-iniciales-movil` y nombre de `--perfil-nombre-movil`); `.menu-btn, .menu-cerrar` con `border: 0` y `background: transparent` en la media query (CA11); los nueve tokens nuevos iguales a las tablas de `system.md` — tests/Feature/MovilPacientesExpedientesTest.php — hecho cuando: fallan hoy porque no existen el bloque 035, sus reglas ni los tokens (anotado) — cubre: CA1, CA4, CA7, CA8, CA10, CA11 — depende: T011
- [ ] T013 [P] Cambiar en `MovilPacienteTest` las aserciones del borde y el fondo de `.menu-btn` y `.menu-cerrar` (de `border: var(--border-width) solid var(--border-control)` y `background: var(--white)` a `border: 0` y `background: transparent`), conservando el contraste del icono (`--p600` sobre `--white`) y el toque, y añadiendo que `.topbar` sigue con `background: var(--white)` (el fondo sobre el que se mide ese contraste; /analyze A15) — tests/Feature/MovilPacienteTest.php — hecho cuando: el test cambiado falla hoy por el borde y el fondo (anotado) y los demás de `MovilPacienteTest` siguen en verde — cubre: CA11 — depende: T002
- [ ] T014 [P] Ampliar `capturar.mjs --medir` según D10: las pantallas incluidas de la 035 por rol, con reflujo, toque, solapes, etiquetas de las tarjetas, palabras de 30 letras, orden de las dos fichas, perfil compacto (iniciales de 48 px con el nombre, la edad y el tipo de sangre a su derecha, y etiquetas visibles a 390; ninguna a 1440), fila de acciones bajo el título, texto e icono centrados, migas con enlace de 44 × 44, foco visible en los campos de los formularios, palabras de 30 letras también en el título de la barra y la página actual de las migas, y errores del alta del paciente y del alta de expediente dentro del ancho y junto a su campo, con JS y sin él; los botones del menú sin borde y con fondo transparente calculados, 44 × 44 y foco visible; la comparación de 1024 y 1440 con la línea base, que pasa a ser la versión en producción, v0.8.0 (cabecera, paso 7b), con alergias y medicamentos de cliente1 y más de 20 expedientes en los datos de prueba (paso 3) — docs/design/capturar.mjs — hecho cuando: `node --check` sin errores, `--seco --medir` lista las comprobaciones nuevas y `--medir` falla hoy en las pantallas de la 035 por las razones de la medida previa del plan (fichas de 875 y 559 px, tabla de 506 px, «Ver detalle» de 38 × 44, botón del menú con borde; anotado) — cubre: CA1, CA2, CA3, CA4, CA7, CA8, CA9, CA10, CA11 — depende: T002

## Implementación
- [ ] T020 Añadir a `app.css` los nueve tokens en `:root` y, tras `/* Fin spec 034 */`, el bloque `/* Spec 035 */ … /* Fin spec 035 */` con `.ficha-paciente`, `.ficha-expediente`, `.expediente-cabecera`, `.acciones-ficha`, `.acciones-formulario`, `.dato-recortado`, `.paginacion`, `.perfil-identidad`, `.perfil-avatar`, `.perfil-nombre` y `.perfil-dato`, con los valores que hoy llevan sus `style=""` (D1–D6, D12), `.etiqueta-campo` (D7) y el foco del sistema en los campos de `.form-paciente` y `.form-expediente` (D13) — public/css/app.css — hecho cuando: pasan dos veces las partes de `app.css` y de tokens de T012, y `MovilPersonalTest` sigue en verde (su `bloque034()` termina en `/* Fin spec 034 */`) — cubre: CA1, CA4, CA7, CA10 — depende: T001, T012
- [ ] T021 Añadir a `movil.css` el bloque «Spec 035» dentro de la media query (D1–D6, D12) y, en la regla de `.menu-btn, .menu-cerrar`, `border: 0` y `background: transparent` (D8) — public/css/movil.css — hecho cuando: pasan dos veces la parte de `movil.css` de T012 y T013, y `MovilPersonalTest` sigue en verde · verificación manual: el menú abierto y cerrado a 390 de los cuatro roles, con «Menú» y «Cerrar menú» solo con el icono, y NVDA sigue anunciando «Menú, botón, contraído» — cubre: CA1, CA4, CA7, CA8, CA11 — depende: T013, T020
- [ ] T022 Adaptar la ficha del paciente: rejilla a `.ficha-paciente` (D1); historial de citas en tarjetas, con «Ver detalle» con nombre, texto e icono oculto (D3); acciones de la barra en `.acciones-ficha` (D4); `texto-partible` y `.dato-recortado` (D6); perfil compacto (D12); migas a `nav.migas` (D14) — resources/views/pacientes/show.blade.php — hecho cuando: pasan dos veces las partes de la ficha del paciente de T010; `--medir` no da fallos en la ficha de los tres roles a 320, 390 y 844 × 390, con JS y sin él, y a 1024 y 1440 es igual a la línea base; en Playwright WebKit a 390, la fila de acciones queda bajo el título sin desplazamiento horizontal, con control positivo (R1, /analyze A5; anotado) · verificación manual: la ficha a 320 y 390 de los tres roles contra `design/opcion-a.html#ficha-m` (bloques) y `opcion-c.html#ficha-m` (perfil), y a 1440 igual que antes — cubre: CA1, CA2, CA7, CA8, CA9, CA10 — depende: T010, T014, T021
- [ ] T023 [P] Adaptar la lista de expedientes: tarjetas con `celda-cab` (fecha) y `celda-acciones` («Ver») (D3), paginación a `.paginacion` y etiqueta del buscador con `for`/`id` (D7) — resources/views/expedientes/index.blade.php — hecho cuando: pasan dos veces las partes de la lista de T010 y del buscador de T011; `--medir` no da fallos en la lista a 320, 390 y 844 × 390 y a 1024 y 1440 es igual a la línea base · verificación manual: la lista a 390 contra las tarjetas de `034/design/opcion-a.html` — cubre: CA3, CA5, CA9, CA10 — depende: T011, T014, T021
- [ ] T024 [P] Adaptar la ficha del expediente: rejilla a `.ficha-expediente` y cabecera a `.expediente-cabecera`, con el título en `texto-partible` (D2, D6); los cuatro títulos a `<label for class="etiqueta-campo">`, la fecha sugerida con `for`/`id`, el mensaje de error de cada campo de `update` con su `aria-describedby` y `aria-invalid` (D7) y la clase `form-expediente` en sus dos `<form>` (D13); «Guardar cambios» en `.acciones-formulario` (D5); migas a `nav.migas` (D14) — resources/views/expedientes/show.blade.php — hecho cuando: pasan dos veces las partes de la ficha del expediente de T010 y T011; `--medir` no da fallos en la ficha de los tres roles a 320, 390 y 844 × 390 y a 1024 y 1440 es igual a la línea base · verificación manual: la ficha a 390 de admin y de asistente contra `design/opcion-a.html#expediente-m`, y a 1440 igual que antes — cubre: CA4, CA5, CA6, CA7, CA8, CA9, CA10 — depende: T011, T014, T021
- [ ] T025 [P] Adaptar el formulario del paciente (alta y edición): `for`/`id` en cada campo, el mensaje de error de cada campo que validan `store` y `update` con su `aria-describedby` y `aria-invalid` (D7), la clase `form-paciente` en el `<form>` (D13), fila de acciones a `.acciones-formulario` (D5) y migas a `nav.migas` (D14) — resources/views/pacientes/create.blade.php — hecho cuando: pasan dos veces las partes del formulario del paciente de T010 y T011; `--medir` no da fallos en el alta (admin y asistente) y la edición (admin y dentista) a 320, 390 y 844 × 390, ni con errores, y a 1024 y 1440 es igual a la línea base · verificación manual: con NVDA, un campo del alta se anuncia con su etiqueta y, tras un envío vacío, con su mensaje de error — cubre: CA5, CA6, CA7, CA9, CA10 — depende: T011, T014, T021
- [ ] T026 [P] Adaptar el alta de expediente: `for`/`id`, el mensaje de error de cada campo validado con su `aria-describedby` y `aria-invalid` (D7), la clase `form-expediente` en el `<form>` (D13), fila de acciones a `.acciones-formulario` (D5) y migas a `nav.migas` (D14) — resources/views/expedientes/create.blade.php — hecho cuando: pasan dos veces las partes del alta de expediente de T010 y T011; `--medir` no da fallos en el alta (admin y dentista) a 320, 390 y 844 × 390 y a 1024 y 1440 es igual a la línea base — cubre: CA5, CA6, CA7, CA9, CA10 — depende: T011, T014, T021
- [ ] T027 Comprobar con `--medir` que no queda ningún bloque señalado en las pantallas incluidas tras T022–T026; si aparece alguno, detenerse y proponer una tarea nueva con su test (Desvíos de `/implement`), sin corregirlo aquí — (sin archivos) — hecho cuando: `--medir` no da fallos en ninguna pantalla incluida ni en las de la 033 y la 034, a ningún ancho, con y sin JavaScript, y la nota dice «sin bloques extra» o enlaza las tareas nuevas — cubre: CA8, CA9 — depende: T022, T023, T024, T025, T026
- [ ] T028 Rehacer con `capturar.mjs` las capturas de las pantallas incluidas a 320, 390 y 1440 y las del menú abierto de cada rol, y poner sus filas en las tablas de pantallas e inventario de `system.md` (DS8 «parcial (033, 034, 035)») — docs/design/capturas/ (generado por node capturar.mjs), docs/design/system.md — hecho cuando: las capturas existen, a 1024 y 1440 las pantallas incluidas son iguales a la línea base de la misma sesión (anotado) y `system.md` las lista · verificación manual: el usuario revisa las capturas a 320 y 390 contra `design/opcion-a.html` y `opcion-c.html` — cubre: CA1, CA2, CA3, CA4, CA10, CA11 — depende: T027

## Integración y documentación
- [ ] T040 Hacer la verificación final: `php artisan test` completo, con la línea base de T002 más los tests nuevos, todo en verde; `pint --test` en los PHP tocados; `node docs/design/capturar.mjs --medir` con código 0; gitleaks sobre el rango de la spec, sin fugas; `composer audit`, `npm audit` y `trivy fs --severity HIGH,CRITICAL` sin avisos nuevos respecto a T002; borrados la base `dentissa_capturas`, sus servidores, el volumen de Playwright, la línea base, la copia `git worktree` de v0.8.0 y su `.env`, y la carpeta temporal — (sin archivos) — hecho cuando: todo lo anterior pasa y queda anotado — cubre: CA1, CA2, CA3, CA4, CA5, CA6, CA7, CA8, CA9, CA10, CA11 — depende: T028
- [ ] T041 Anotar en `docs/roadmap.md`, junto a la 014, que sus cambios de la ficha del expediente (formularios solo con `@can('editar-expediente')`), del alta del paciente (dentista obligatorio para la asistente) y del alta de expediente (autor fijo para la dentista) se aplican sobre el marcado de la 035 y conservan `.ficha-expediente`, `.expediente-cabecera`, `.acciones-formulario`, los `for`/`id`, los mensajes de error con su `aria-describedby` y las migas (`MovilPacientesExpedientesTest` falla si no; plan D11, /analyze A7); y junto a la 020, que se reconstruye después de la 035 (/analyze A8) — docs/roadmap.md — hecho cuando: el roadmap lo dice junto a la 014 y la 020 — cubre: CA4, CA5 — depende: T040
- [ ] T090 Actualizar `docs/architecture.md`: en Frontend, las fichas en una columna, el perfil compacto y `.acciones-ficha` con `:has()` en `movil.css`
- [ ] T091 Actualizar `docs/deployment.md` y `docs/observability.md` si cambiaron variables, entornos, pasos de deploy, logs, eventos de auditoría o métricas
  - nota: el plan no prevé cambios (sin variables, migraciones, logs ni métricas); se anota «no aplica» al cerrar.
- [ ] T092 Marcar spec como `implemented`

## Despliegue (lo ejecuta `/release`)
- [ ] T095 Desplegar a staging y verificar criterios de aceptación
  - No hay staging (030): las comprobaciones en un móvil real se hacen en producción, en T097.
- [ ] T096 Aprobación humana para producción
- [ ] T097 Desplegar a producción y vigilar métricas del plan (Rollout)
  - verificación manual: desde el móvil del usuario en https://dentissa.app, la ficha del paciente de un rol del personal, la lista y la ficha del expediente de la dentista, un formulario y el menú abierto y cerrado.
  - Sin errores 500 en `laravel.log` ni respuestas 5xx en nginx durante 15 minutos.
- [ ] T098 Marcar spec como `released`

## Cobertura
| Criterio | Tarea(s) de test | Tarea(s) de implementación |
|---|---|---|
| CA1 | T010, T012, T014 | T001, T020, T021, T022, T028 |
| CA2 | T010, T014 | T022, T028 |
| CA3 | T010, T014 | T023, T028 |
| CA4 | T010, T012, T014 | T001, T020, T021, T024, T028 |
| CA5 | T010, T011 | T023, T024, T025, T026 |
| CA6 | T011 | T024, T025, T026 |
| CA7 | T010, T012, T014 | T001, T020, T021, T022, T024, T025, T026 |
| CA8 | T010, T012, T014, T027 | T021, T022, T024 |
| CA9 | T010, T014, T027 | T022, T023, T024, T025, T026 |
| CA10 | T012, T014 | T020, T022, T023, T024, T025, T026, T028 |
| CA11 | T012, T013, T014 | T001, T021, T028 |

| Amenaza (TM#) | Tarea(s) de control | Tarea(s) de test |
|---|---|---|
| — (el plan no tiene amenazas: no toca datos, permisos ni entradas) | — | — |

| Cambio del plan (módulo) | Tarea(s) |
|---|---|
| Sistema de diseño (`system.md`, `system.html`) | T001, T028 |
| Estilos existentes (`public/css/app.css`) | T020 |
| Estilos del móvil (`public/css/movil.css`) | T021 |
| Ficha del paciente (`pacientes/show`) | T022 |
| Formulario del paciente (`pacientes/create`) | T025 |
| Lista de expedientes (`expedientes/index`) | T023 |
| Ficha del expediente (`expedientes/show`) | T024 |
| Alta de expediente (`expedientes/create`) | T026 |
| Tests (`MovilPacientesExpedientesTest`, `MovilPacienteTest`, `MovilPersonalTest`) | T010, T011, T012, T013, T020 |
| Capturas y medidas (`capturar.mjs`) | T014, T028 |

| Requisito no funcional | Tarea(s) de test o verificación manual (T095) |
|---|---|
| WCAG 2.1 AA (1.4.10, 1.3.1, 2.4.7, 3.3.1, 1.4.11) | T010, T011, T012, T013, T014, T040 · verificación manual con NVDA en T021 y T025 |
| Toque ≥ 44 × 44 px | T014, T040 |
| Medido a 320, 390, 844 × 390 y 1440, también sin JS | T014, T040 |
| Escritorio sin cambios salvo el foco, contra capturas de antes | T014, T022, T023, T024, T025, T026, T028 |
