---
spec: 046-navegacion-interna
status: approved   # draft | approved | blocked
constitution_version: 1.3.0
created: 2026-11-12
---

# Plan · 046 Navegación interna

## Enfoque técnico
Es un cambio de vistas y de estilos: no toca rutas, controladores, permisos ni datos. Las migas pasan
a pintarse en un solo sitio, un parcial `partials/migas.blade.php` que recibe el camino como lista de
elementos (texto y, salvo el último, dirección) y escribe la estructura accesible del diseño. Cada
una de las 18 pantallas de la spec declara su camino con `route()` y lo incluye como primer elemento
del contenido de la vista (debajo de los avisos que pinta el layout, que no cambia); desaparecen las migas escritas a mano con `style=""`, las de «Agendar cita» y el
botón «Volver al paciente» del expediente. El aspecto nuevo (enlace en `--p600` subrayado, camino que
pasa de línea) va en las reglas `.migas` de `public/css/app.css`. El menú de `layouts/app.blade.php`
amplía los patrones que marcan «Pacientes» para las pantallas de expedientes, y las dos pantallas de
servicios declaran su título. Un test nuevo, `NavegacionInternaTest`, recorre las 18 pantallas con
los roles que las abren, con un caso por pantalla y rol. La comparación del escritorio de
`docs/design/capturar.mjs --medir` sigue siendo contra producción y deja fuera las zonas que la 046
cambia a propósito (D12).

## Constitution Check
| Principio | Resultado | Justificación / ajuste | Cómo se verifica |
|---|---|---|---|
| P1 Spec aprobada antes de código | ✅ | Spec 046 y su acta de diseño aprobadas; nada se implementa sin `tasks.md` aprobado | `python .ai/bin/aidd.py status`; hook del plugin |
| P2 Test que falla antes y pasa después | ✅ | Cada CA con test en `NavegacionInternaTest` que falla hoy (pantallas sin migas, sin navegación con nombre, reglas de color, «Cancelar» de «Editar usuario»); el test de CA6 compara con el destino fijo de la tabla, así que antes de implementar falla solo en «Editar usuario»; en los otros nueve formularios y en la parte de permisos de CA11 es de caracterización | test: Trazabilidad; las caracterizaciones se comprueban rompiendo el destino de «Cancelar» y el middleware de rol |
| P3 Autorización en el servidor | ✅ | Ninguna ruta ni Gate cambia; las migas no deciden acceso y solo se pintan en pantallas ya autorizadas | test: `test_una_pantalla_de_otro_rol_responde_como_hoy_sin_migas` (caracterización) |
| P4 Entrada validada y SQL con bindings | ✅ | Sin entradas ni consultas nuevas; el parcial pinta con la salida escapada de Blade (dobles llaves), nunca la salida sin escapar | test: `test_el_nombre_con_codigo_se_pinta_como_texto`; semgrep sobre lo tocado |
| P5 Nada sensible en el repositorio | ✅ | Los tests crean sus datos con nombres sintéticos | `gitleaks detect --no-banner` en `/implement` y CI |
| P6 Sin vulnerabilidades críticas o altas | ✅ | Sin dependencias nuevas; línea base del 2026-11-12: `composer audit` y `npm audit --audit-level=high` sin hallazgos | las herramientas en `/review` y `/release` |
| P7 Auditoría | ➖ | Sin accesos, permisos ni acciones nuevas; lo que hoy se audita no cambia (spec, «Auditoría») | — |
| P8 Correlación y logs | ➖ | No añade logs ni peticiones | — |
| P9 Solo el sistema de diseño | ✅ | Reglas `.migas` con tokens de 1.15.0, sin `style=""`; se eliminan cinco bloques con estilos en línea; «Migas de pan» se actualiza primero en `system.md`; WCAG AA | test: reglas y contraste (`test_las_reglas_de_las_migas…`, `test_el_contraste…`), ningún `style` dentro de `#migas`; las reglas nuevas van en el bloque `/* Spec 033 */` de `app.css`, donde `MovilPacienteTest::test_las_reglas_nuevas_de_app_css_solo_usan_tokens` exige solo tokens en todas sus reglas; manual: `/review` contra `system.html` y `capturar.mjs --medir` |
| P10 Rutas indexables | ➖ | Todas las pantallas exigen sesión; ninguna ruta pública cambia | — |

## Cambios por módulo
| Módulo | Cambio | Riesgo |
|---|---|---|
| `resources/views/partials/migas.blade.php` (nuevo) | Recibe `$migas`: lista de `['texto' => …, 'url' => …]`, el último sin `url`. Pinta `<nav id="migas" class="migas" aria-label="Migas de pan"><ol>`; los hijos del `ol` son solo `<li>`; enlaces `<a href>`; cada `<li>` menos el primero empieza por `<span class="migas-sep" aria-hidden="true">›</span>` (n−1 separadores); el último, `<li class="migas-actual" aria-current="page">`. Todo con la salida escapada de Blade | bajo |
| `resources/views/citas/create.blade.php`, `personal/create.blade.php`, `personal/show.blade.php`, `usuarios/create.blade.php`, `usuarios/show.blade.php` | Sustituir las migas con `style=""` por el parcial. Edición (`isset($miembro)`, `isset($usuario)`): camino de tres elementos «sección › nombre › Editar». En `usuarios/create`, «Cancelar» en edición pasa de `admin.usuarios.index` a `admin.usuarios.show` (CA6; en alta sigue en el listado) | bajo |
| `resources/views/citas/show.blade.php`, `citas/cliente-show.blade.php`, `pacientes/show.blade.php`, `pacientes/create.blade.php`, `expedientes/create.blade.php`, `expedientes/show.blade.php` | Sustituir el `div.migas` por el parcial, con el mismo camino; `pacientes/create` en edición pasa a «Pacientes › *paciente* › Editar»; `expedientes/show` pierde el enlace «← Volver al paciente» | bajo: tests de la 033–035 fijan `div#migas` (se actualizan) |
| `resources/views/citas/reprogramar.blade.php`, `citas/cliente-reprogramar.blade.php` | Incluir el parcial antes de `citas.partials.reprogramar`: personal «Citas › *paciente* › Reprogramar» (la vista calcula `$rp` con el mismo `match(auth()->user()->rol)` de `citas/show`, porque el controlador no se lo pasa; lista `route("{$rp}.citas")`, nombre `$cita->paciente`, detalle `$rutaDetalle`); paciente «Mis Citas › Detalle › Reprogramar». El parcial de reprogramar y los controladores no cambian | bajo |
| `resources/views/servicios/create.blade.php`, `servicios/edit.blade.php` | `@section('title', 'Nuevo servicio')` y `'Editar servicio'`; migas «Servicios › Nuevo servicio» y «Servicios › Editar servicio» | bajo |
| `resources/views/citas/cliente-agendar.blade.php` | Quitar el bloque de migas | bajo: `MovilPacienteTest` lo asierta (se actualiza) |
| `resources/views/layouts/app.blade.php` | «Pacientes» se marca también en `admin/expedientes*`, `asistente/expedientes*` y `dentista/expedientes/*`; «Expedientes» de la dentista solo en su listado (`dentista/expedientes`). Los avisos del layout no se tocan | medio: es el menú de todos los roles; obliga a `capturar.mjs --medir` |
| `public/css/app.css` | Dentro del bloque `/* Spec 033 */`, donde ya están (así las vigila el test de solo tokens de la 033). Declaraciones exactas: `.migas` = `margin-bottom: var(--space-7)`, `font-size: var(--text-meta)`, `color: var(--text)`; `.migas ol` (nueva) = `display: flex`, `flex-wrap: wrap`, `align-items: center`, `gap: var(--space-3)`, `margin: 0`, `padding: 0`, `list-style: none`; `.migas li` (nueva) = `display: flex`, `align-items: center`, `gap: var(--space-3)`, `min-width: 0`, `overflow-wrap: anywhere`; `.migas a` = `color: var(--p600)`, `text-decoration: underline`; `.migas a:hover` (nueva) = `color: var(--p800)`; `.migas-actual` no cambia (`color: var(--text)`); la regla de foco no cambia. En una línea, el bloque conserva en el escritorio el alto y el margen de hoy (mismo tamaño de letra y mismo `margin-bottom`), para no desplazar el contenido | bajo |
| `public/css/movil.css` | Sin cambios: conserva `--touch-min` de `.migas a` y las reglas de salto de línea que fija `MovilPacientesExpedientesTest` (035), ahora redundantes con las de `app.css` | — |
| `tests/Feature/NavegacionInternaTest.php` (nuevo) | Tests de CA1–CA11 | — |
| `tests/Feature/MovilPacienteTest.php`, `MovilPacientesExpedientesTest.php` | Actualizar lo que fija el marcado anterior. En el primero: en `test_las_reglas_nuevas_de_app_css_solo_usan_tokens`, las entradas `.migas` y `.migas a` de `$esperadas` pasan a las declaraciones de la fila de `app.css` (`.migas-actual` se queda igual; las reglas nuevas `.migas ol`, `.migas li` y `.migas a:hover` las recorre ya su bucle de solo tokens y sus declaraciones las fija `NavegacionInternaTest`); y `/cliente/agendar` sale del bucle de pantallas con `#migas`. En el segundo, solo `div#migas` → cualquier elemento con `id="migas"` (sus aserciones de `movil.css` siguen valiendo). `MovilPersonalTest` no cambia | medio: son tests de specs liberadas; se cambian solo las aserciones que la 046 reemplaza |
| `docs/design/capturar.mjs` | La comparación del escritorio sigue contra la línea base del tag en producción (cabecera, paso 7b) y excluye lo que la 046 cambia a propósito. `EN_PAGINA.excluidos` añade, con el margen de 4 px de los demás: (a) el rectángulo de `#migas`, si existe; (b) en la ficha del expediente, la franja derecha de la cabecera donde estaba «← Volver al paciente» (desde el borde derecho de la cabecera, el ancho del botón medido en la línea base, que se anota en la tarea); (c) en las direcciones `…/expedientes/…` y `…/expedientes/crear`, las entradas «Pacientes» y, para la dentista, «Expedientes» de la barra lateral. `cliente-agendar` y `citas-reprogramar-cliente` salen de `COMPARADAS` con una lista propia y su comentario (las migas desaparecen o aparecen y desplazan todo lo de debajo); siguen en las demás comprobaciones de `--medir`. Se actualizan el texto de la comprobación «034 escritorio» de `COMPROBACIONES` y la cabecera (paso 7b: qué excluye la 046 y cuándo vuelven esas dos pantallas a la comparación) | medio: una zona mal calculada taparía una diferencia real; se comprueba con un control positivo (un cambio fuera de las zonas hace fallar la comparación) |
| `docs/design/system.md`, `docs/design/system.html` | «Migas de pan» y las dos reglas del acta («Cambios a incorporar al sistema»); versión siguiente a la vigente al fusionar `main`; DS15 (parte de títulos de servicios) y 033/R6 cerradas | bajo |
| `docs/roadmap.md`, `docs/architecture.md`, `AGENTS.md` | 033/R6 hecha; el parcial de migas en «Frontend» y en las reglas de vistas | bajo |

## Contratos y datos
Sin endpoints, esquemas ni migraciones. Contrato del parcial:

```blade
@include('partials.migas', ['migas' => [
    ['texto' => 'Pacientes', 'url' => route("{$base}.pacientes")],
    ['texto' => $paciente->nombre_completo, 'url' => route("{$base}.pacientes.show", $paciente->id)],
    ['texto' => 'Expediente'],
]])
```

Pantallas y estados: los 18 caminos de la tabla «Pantallas y caminos» de la spec, a 320, 390 y
1440 px. Accesibilidad medible: una sola `nav[aria-label="Migas de pan"]` por pantalla, un solo
`[aria-current="page"]` dentro de ella, separadores con `aria-hidden="true"`, contraste ≥ 4,5:1 de
`--p600` y de `--text` sobre `--bg`, enlaces con `--touch-min` en el móvil.

Qué ve cada rol: no cambia. Cada camino usa el prefijo de rutas del rol que abre la pantalla
(`admin`, `dentista`, `asistente`, `cliente`), como hoy.

## Estrategia de pruebas
- **Feature (PHPUnit, `DatabaseTransactions`, `dentissa_test`):** `NavegacionInternaTest` monta un
  escenario como el de `MovilPersonalTest` (cuentas de los cuatro roles, un paciente, una cita, un
  expediente, un servicio) y recorre una tabla de `[rol, dirección, camino esperado]` con las 18
  pantallas en los roles que las abren. Sobre el HTML (DOM y XPath) comprueba estructura, textos,
  destinos, títulos y entrada marcada del menú. Los tests que recorren la tabla usan un proveedor de
  datos de PHPUnit con un caso con nombre por pantalla y rol (p. ej. `admin · nueva cita`): cada
  caso pasa o falla por separado y cada tarea se comprueba con el filtro de sus casos
  (`--filter`), sin esperar a las demás pantallas. El proveedor solo da la clave del caso; la
  dirección se resuelve con los datos del escenario.
- **CSS:** con el trait `Concerns/ReglasCss` (reglas de `app.css` y `movil.css`, y `contraste()`).
- **Navegador:** `node docs/design/capturar.mjs --medir` en la verificación final de `/implement` y
  en `/review` (obligatorio por tocar `layouts/app.blade.php`), con el procedimiento de su cabecera:
  la línea base se toma en la misma sesión del tag en producción. Mide las pantallas de la 033–035
  (entre ellas el expediente con un nombre largo). La comparación de escritorio falla si una
  pantalla comparada difiere de producción en más de 0,1 % de píxeles fuera de las zonas excluidas
  (migas, hueco del botón, entradas del menú de las pantallas de expedientes); «Agendar cita» y
  «Reprogramar» del paciente no se comparan en el escritorio hasta que la 046 esté en producción
  (D12) y las valida el usuario con sus capturas de antes y de después. Control positivo, una vez:
  un cambio provisional fuera de las zonas hace fallar la comparación.
- **Manual:** el usuario valida a 320, 390 y 1440 px una muestra de seis pantallas: una de dos
  elementos («Nueva cita»), la ficha del expediente, «Editar personal» y «Editar usuario» con un
  nombre largo, «Reprogramar» del personal y el detalle de una cita del personal justo después de
  reprogramarla con éxito, que muestra arriba el aviso del sistema «Cita reprogramada
  correctamente» (un rechazo por hueco no disponible no sirve: se muestra junto al campo). La
  muestra basta porque las 18 pantallas pintan el mismo parcial con las mismas reglas; cubre los
  caminos de tres elementos que `--medir` no mide (personal, usuarios y reprogramar). Además, en
  el escritorio, «Agendar cita» y «Reprogramar» del paciente (D12).
- Los tests nuevos de interfaz se ejecutan dos veces.

## Modelo de amenazas
La spec no toca datos, autenticación ni permisos, pero pinta nombres de personas y enlaces de vuelta.

| ID | Amenaza (STRIDE) | Categoría OWASP | Componente | Control | Test |
|---|---|---|---|---|---|
| TM1 | Tampering/Spoofing: un parámetro de vuelta en la dirección convierte las migas en una redirección abierta | A01:2025 | `partials/migas`, las 18 vistas | Destinos solo con `route()`; nada lee la petición ni el `Referer` | `NavegacionInternaTest::test_las_migas_ignoran_un_destino_de_vuelta_en_la_direccion` |
| TM2 | Tampering: un nombre con código se ejecuta en la pantalla de quien abre la ficha | A05:2025 | `partials/migas` | Salida escapada de Blade en todos los textos; nunca la salida sin escapar | `NavegacionInternaTest::test_el_nombre_con_codigo_se_pinta_como_texto` |
| TM3 | Information disclosure: las migas enseñan el nombre de un paciente a un rol que no puede abrir la pantalla | A01:2025 | middleware de rol, Gates y, en el detalle de cita del paciente, el filtro de `ClienteCitasController` (responde «no encontrada») | Sin cambios: la respuesta de acceso denegado o no encontrada sale antes de pintar la vista; el test incluye a un miembro del personal abriendo el detalle de cita del paciente | `NavegacionInternaTest::test_una_pantalla_de_otro_rol_responde_como_hoy_sin_migas` (caracterización) |

## Trazabilidad
| Criterio de aceptación | Cambio(s) | Test(s) |
|---|---|---|
| CA1: migas en las 18 pantallas, un bloque, mismo aspecto; las del menú sin migas | parcial, 15 archivos de vista (18 pantallas), `cliente-agendar` | `NavegacionInternaTest::test_cada_pantalla_de_segundo_nivel_lleva_sus_migas` (una `nav#migas`, sin `style`, camino esperado) y `::test_las_pantallas_del_menu_no_llevan_migas`; verificación manual: visibles sin desplazarse a 320, 390 y 1440 px, con `capturar.mjs --medir` y la muestra de seis pantallas del usuario, una de ellas con el aviso del sistema arriba |
| CA2: cada elemento lleva a su pantalla fija; el último no es enlace | parcial, vistas | `::test_los_enlaces_de_las_migas_llevan_a_destinos_fijos` (cada `href` es el de la tabla; el último elemento no tiene `a`) |
| CA3: navegación con nombre, página actual, separadores ocultos, foco | parcial, `app.css` | `::test_las_migas_son_una_navegacion_accesible` (`aria-label`, un `aria-current="page"`, todos los hijos del `ol` son `li`, n−1 separadores con `aria-hidden`) y la regla de foco de `.migas a:focus-visible` |
| CA4: enlace distinto del actual sin color; contraste AA | `app.css` | `::test_las_reglas_de_las_migas_distinguen_el_enlace` (subrayado, `--p600`, actual en `--text` sin subrayar) y `::test_el_contraste_de_las_migas_es_aa` |
| CA5: reprogramar con migas en los dos estados | `citas/reprogramar`, `citas/cliente-reprogramar` | `::test_reprogramar_lleva_migas_se_pueda_o_no` (personal y paciente, cita reprogramable y no) |
| CA6: «Cancelar» lleva al penúltimo elemento | `usuarios/create` en edición; los otros nueve formularios no cambian | `::test_cancelar_lleva_al_mismo_sitio_que_las_migas`, un caso por formulario (diez, sin contar «Cancelar la cita definitivamente»): compara el destino de «Cancelar» con el destino fijo del penúltimo elemento según la tabla de la spec, no con las migas pintadas. Antes de implementar falla solo en «Editar usuario» y pasa en los otros nueve (caracterización, comprobada rompiendo el `href` de un «Cancelar»); que las migas pintan ese mismo destino lo prueba el test de CA2 |
| CA7: expediente sin «Volver al paciente» | `expedientes/show` | `::test_el_expediente_ya_no_tiene_volver_al_paciente` |
| CA8: títulos de servicios; ninguno por defecto | `servicios/create`, `servicios/edit` | `::test_ninguna_pantalla_usa_el_titulo_por_defecto` (`.topbar-title` y `<title>` de las 18) |
| CA9: el menú marca la sección | `layouts/app` | `::test_el_menu_marca_la_seccion_de_la_pantalla` (un solo `.nav-item[aria-current="page"]`, con el mismo texto y el mismo destino que el primer elemento de las migas, para cada rol; el destino se compara por la ruta, sin esquema ni dominio, porque el menú usa direcciones relativas y las migas, absolutas); `capturar.mjs --medir` |
| CA10: camino largo a 320 px | `app.css`, `movil.css` | `::test_las_migas_pueden_pasar_de_linea` (reglas `flex-wrap`, `overflow-wrap`, `--touch-min`); verificación manual: `capturar.mjs --medir` a 320 px con un nombre largo, sin desplazamiento horizontal |
| CA11 (abuso): destinos fijos, nombre como texto, sin migas para otro rol | parcial; sin cambios en permisos | TM1, TM2, TM3 |
| CA12: el escritorio de las pantallas de la 033–035 solo cambia en las migas, el botón y la entrada marcada | `docs/design/capturar.mjs` (zonas excluidas y las dos pantallas fuera de la comparación) | `capturar.mjs --medir`, comprobación «034 escritorio», con la línea base del tag en producción: pasa con las zonas excluidas y falla con un cambio fuera de ellas (control positivo); `--seco --medir` lista el texto nuevo de la comprobación; verificación manual: el usuario compara en el escritorio las capturas de antes y de después de «Agendar cita» y «Reprogramar» del paciente |
| TM1 | parcial, vistas | `::test_las_migas_ignoran_un_destino_de_vuelta_en_la_direccion` |
| TM2 | parcial | `::test_el_nombre_con_codigo_se_pinta_como_texto` |
| TM3 | — | `::test_una_pantalla_de_otro_rol_responde_como_hoy_sin_migas` |
| RNF: WCAG 2.1 AA | parcial, `app.css`, `movil.css` | tests de CA3, CA4 y CA10; verificación manual: `/review` contra `system.html` |
| RNF: solo el sistema de diseño | vistas, `app.css` | `::test_cada_pantalla_de_segundo_nivel_lleva_sus_migas` (ningún `style` en `#migas`); revisión de `/review` |
| RNF: descubribilidad | — | no aplica (todo exige sesión) |

## Observabilidad
No aplica: la spec no toca datos sensibles, autenticación ni permisos y no añade logs, eventos de
auditoría ni métricas. Tras el deploy se comprueba a mano que el detalle de una cita y «Reprogramar»
muestran sus migas en producción.

## Descubribilidad
No aplica: ninguna ruta pública se crea ni cambia; las 18 pantallas exigen sesión.

## Rollout
- Feature flag: no aplica; son vistas y estilos sin riesgo sobre datos.
- Orden de despliegue: una sola imagen de la aplicación; sin migraciones.
- Compatibilidad: `app.css` y `movil.css` llevan `?v=filemtime`, así que cada página pide los
  estilos de su versión.
- Rollback: volver a la imagen anterior (`docs/deployment.md`); no hay datos que revertir.
- Métricas a vigilar tras el deploy: errores 500 en las rutas de las 18 pantallas (canal de alertas
  de la spec 032).

## Decisiones (→ ADR si son arquitectónicas)
- **D1 · Un parcial para las migas.** Opciones: (a) repetir el marcado en cada vista, (b) un parcial
  con el camino como lista, (c) calcular el camino en los controladores. Se elige (b): un solo sitio
  para la estructura accesible, sin tocar controladores; el proyecto ya usa parciales
  (`partials/badge-estado`, `partials/iconos`).
- **D2 · El camino se declara en la vista.** Las vistas conocen el prefijo de rutas del rol (`$rp`,
  `$base`) y sus datos, o lo calculan con el rol como ya hace `citas/show` (es el caso de
  `citas/reprogramar`); así no cambia ningún controlador ni su test.
- **D3 · Se conservan `id="migas"`, `.migas` y `.migas-actual`.** Cambia el elemento (`div` → `nav`
  con `ol`), no los nombres: los tests de la 033–035 solo necesitan ajustar el elemento y las reglas.
- **D4 · Separador como elemento con `aria-hidden`.** Un separador pintado con `::before` puede
  leerse en algunos lectores de pantalla; como `<span aria-hidden="true">` se comprueba en el HTML.
- **D5 · Expedientes marcan «Pacientes» en el menú** (CA9), también para la dentista, que tiene
  entrada «Expedientes»: esa entrada queda marcada solo en su listado. Coherente con el padre fijo
  del expediente (spec, Decisiones).
- **D6 · Las reglas de salto de línea se añaden a `app.css`** para valer a todos los anchos (acta de
  diseño, «camino largo»); las de `movil.css` se quedan como están, aunque sean redundantes, porque
  las fija un test de la 035 y quitarlas no aporta nada (/analyze A3).
- **D7 · Orden respecto de la 045:** plan, tareas y análisis ahora; `/implement` empieza cuando la
  045 esté fusionada en `main`, y la versión de `system.md` es la siguiente a la que deje
  (decisión del usuario, 2026-11-12).
- **D8 · Tamaño:** 12 criterios y 7 specs extendidas; se mantiene junta (decisión del usuario,
  2026-11-12, al aprobar la spec).
- **D9 · «Agendar cita» sin migas:** es una entrada del menú (decisión del usuario, 2026-11-12;
  spec corregida).
- **D10 · El texto del primer elemento es el de la entrada del menú de cada rol** («Mis Citas» para
  la dentista y el paciente): cada vista lo elige con el rol, igual que el prefijo de rutas
  (decisión del usuario, 2026-11-12; /analyze A4).
- **D11 · Las migas van dentro del contenido de cada vista**, debajo de los avisos del layout, que
  no cambia (decisión del usuario, 2026-11-12; /analyze A8).
- **D12 · La comparación del escritorio excluye lo que la 046 cambia.** Opciones: (a) una línea
  base nueva tomada de la 046: no existe en el procedimiento, la línea base se toma en cada sesión
  del tag en producción y se borra, y compararía la rama consigo misma (/analyze A17); (b) excluir
  zonas en `capturar.mjs`, como ya hacen las specs 020, 034 y 039; (c) aceptar el fallo y validar a
  mano. Se elige (b) (decisión del usuario, 2026-11-12): migas, hueco del botón «Volver al
  paciente» y entradas del menú de las pantallas de expedientes. «Agendar cita» y «Reprogramar» del
  paciente salen de la comparación de escritorio hasta que la 046 esté en producción, porque el
  contenido se desplaza entero; el usuario las valida en pantalla y el roadmap anota devolverlas a
  la comparación entonces.
- Sin ADR: no hay dependencias nuevas ni cambio de arquitectura.

## Impacto en arquitectura
- `docs/architecture.md` → «Frontend»: el parcial `partials/migas` y la regla de qué pantallas lo
  llevan.
- `AGENTS.md` → «Reglas y estilo» (vistas): una línea sobre las migas en pantallas de segundo nivel.
- `docs/design/system.md` → «Migas de pan», tabla de deuda (DS15, parte de títulos) y Decisiones.
- `docs/roadmap.md` → 033/R6 hecha; en 033/R11, lo que la 046 ya prueba de la entrada marcada; la
  nota de la 014; y devolver «Agendar cita» y «Reprogramar» del paciente a la comparación de
  `capturar.mjs` cuando la 046 esté en producción.

## Riesgos y mitigaciones
- **Tests de specs liberadas que fijan el marcado anterior** (`MovilPacienteTest`,
  `MovilPacientesExpedientesTest`, `MovilPersonalTest`): se cambian solo las aserciones que la 046
  reemplaza (elemento, reglas de color, «Agendar cita»), en la misma tarea que cambia el marcado.
- **El menú es de todos los roles:** un patrón mal escrito marcaría dos entradas; el test de CA9
  exige una sola entrada marcada en cada una de las 18 pantallas y `capturar.mjs --medir` lo mide en
  el navegador.
- **Choque con la 045** en `system.md`, `architecture.md`, `CHANGELOG`, `public/css/app.css` y
  `AGENTS.md`: se evita con D7; tras fusionar, la suite completa debe pasar antes de empezar.
- **Nombre largo en el escritorio:** el camino puede ocupar dos líneas en fichas estrechas; aceptado
  en el acta de diseño y validado por el usuario en pantalla.
- **Contraste de `--p600` sobre `--bg`** calculado a mano (≈ 6,5:1): lo fija el test de contraste; si
  no llegara a 4,5:1, se vuelve a `/design-spec 046 --fix`.
- **Choque con la 014** (aprobada, sin implementar): su plan toca `pacientes/create`,
  `expedientes/show` y `expedientes/create`. Va después de la 046; al retomarla con
  `/plan 014 --fix` debe partir del parcial de migas y conservarlo (nota del roadmap).
- **Zonas excluidas de `capturar.mjs`:** tapan lo que hay debajo de ellas y dos pantallas del
  paciente quedan sin comparar en el escritorio hasta la versión siguiente; lo cubren el control
  positivo, la validación del usuario y la nota del roadmap para retirarlo.
