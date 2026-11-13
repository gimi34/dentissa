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
del contenido; desaparecen las migas escritas a mano con `style=""`, las de «Agendar cita» y el
botón «Volver al paciente» del expediente. El aspecto nuevo (enlace en `--p600` subrayado, camino que
pasa de línea) va en las reglas `.migas` de `public/css/app.css`. El menú de `layouts/app.blade.php`
amplía los patrones que marcan «Pacientes» para las pantallas de expedientes, y las dos pantallas de
servicios declaran su título. Un test nuevo, `NavegacionInternaTest`, recorre las 18 pantallas con
los roles que las abren.

## Constitution Check
| Principio | Resultado | Justificación / ajuste | Cómo se verifica |
|---|---|---|---|
| P1 Spec aprobada antes de código | ✅ | Spec 046 y su acta de diseño aprobadas; nada se implementa sin `tasks.md` aprobado | `python .ai/bin/aidd.py status`; hook del plugin |
| P2 Test que falla antes y pasa después | ✅ | Cada CA con test en `NavegacionInternaTest` que falla hoy (pantallas sin migas, sin navegación con nombre, reglas de color); CA6 y la parte de permisos de CA11 son de caracterización | test: Trazabilidad; las caracterizaciones se comprueban rompiendo el destino de «Cancelar» y el middleware de rol |
| P3 Autorización en el servidor | ✅ | Ninguna ruta ni Gate cambia; las migas no deciden acceso y solo se pintan en pantallas ya autorizadas | test: `test_una_pantalla_de_otro_rol_responde_como_hoy_sin_migas` (caracterización) |
| P4 Entrada validada y SQL con bindings | ✅ | Sin entradas ni consultas nuevas; el parcial pinta con la salida escapada de Blade (dobles llaves), nunca la salida sin escapar | test: `test_el_nombre_con_codigo_se_pinta_como_texto`; semgrep sobre lo tocado |
| P5 Nada sensible en el repositorio | ✅ | Los tests crean sus datos con nombres sintéticos | `gitleaks detect --no-banner` en `/implement` y CI |
| P6 Sin vulnerabilidades críticas o altas | ✅ | Sin dependencias nuevas; línea base del 2026-11-12: `composer audit` y `npm audit --audit-level=high` sin hallazgos | las herramientas en `/review` y `/release` |
| P7 Auditoría | ➖ | Sin accesos, permisos ni acciones nuevas; lo que hoy se audita no cambia (spec, «Auditoría») | — |
| P8 Correlación y logs | ➖ | No añade logs ni peticiones | — |
| P9 Solo el sistema de diseño | ✅ | Reglas `.migas` con tokens de 1.15.0, sin `style=""`; se eliminan cinco bloques con estilos en línea; «Migas de pan» se actualiza primero en `system.md`; WCAG AA | test: reglas y contraste (`test_las_reglas_de_las_migas…`, `test_el_contraste…`), ningún `style` dentro de `#migas`; manual: `/review` contra `system.html` y `capturar.mjs --medir` |
| P10 Rutas indexables | ➖ | Todas las pantallas exigen sesión; ninguna ruta pública cambia | — |

## Cambios por módulo
| Módulo | Cambio | Riesgo |
|---|---|---|
| `resources/views/partials/migas.blade.php` (nuevo) | Recibe `$migas`: lista de `['texto' => …, 'url' => …]`, el último sin `url`. Pinta `<nav id="migas" class="migas" aria-label="Migas de pan"><ol>`; cada elemento en un `<li>`; enlaces `<a href>`; entre elementos, `<span class="migas-sep" aria-hidden="true">›</span>`; el último, `<li class="migas-actual" aria-current="page">`. Todo con la salida escapada de Blade | bajo |
| `resources/views/citas/create.blade.php`, `personal/create.blade.php`, `personal/show.blade.php`, `usuarios/create.blade.php`, `usuarios/show.blade.php` | Sustituir las migas con `style=""` por el parcial. Edición (`isset($miembro)`, `isset($usuario)`): camino de tres elementos «sección › nombre › Editar» | bajo |
| `resources/views/citas/show.blade.php`, `citas/cliente-show.blade.php`, `pacientes/show.blade.php`, `pacientes/create.blade.php`, `expedientes/create.blade.php`, `expedientes/show.blade.php` | Sustituir el `div.migas` por el parcial, con el mismo camino; `pacientes/create` en edición pasa a «Pacientes › *paciente* › Editar»; `expedientes/show` pierde el enlace «← Volver al paciente» | bajo: tests de la 033–035 fijan `div#migas` (se actualizan) |
| `resources/views/citas/reprogramar.blade.php`, `citas/cliente-reprogramar.blade.php` | Incluir el parcial antes de `citas.partials.reprogramar`: personal «Citas › *paciente* › Reprogramar» (lista `route("{$rp}.citas")`, detalle `$rutaDetalle`); paciente «Mis citas › Detalle › Reprogramar». El parcial de reprogramar no cambia | bajo |
| `resources/views/servicios/create.blade.php`, `servicios/edit.blade.php` | `@section('title', 'Nuevo servicio')` y `'Editar servicio'`; migas «Servicios › Nuevo servicio» y «Servicios › Editar servicio» | bajo |
| `resources/views/citas/cliente-agendar.blade.php` | Quitar el bloque de migas | bajo: `MovilPacienteTest` lo asierta (se actualiza) |
| `resources/views/layouts/app.blade.php` | «Pacientes» se marca también en `admin/expedientes*`, `asistente/expedientes*` y `dentista/expedientes/*`; «Expedientes» de la dentista solo en su listado (`dentista/expedientes`) | medio: es el menú de todos los roles; obliga a `capturar.mjs --medir` |
| `public/css/app.css` | Reglas `.migas`: la fila flexible pasa al `ol` (sin viñetas ni sangría, `flex-wrap: wrap`, `gap: var(--space-3)`); `.migas li` en fila con su separador; `.migas a` en `--p600` con subrayado y `--p800` al pasar el puntero; `.migas-actual` en `--text`; `overflow-wrap: anywhere` en los elementos; el foco no cambia | bajo |
| `public/css/movil.css` | `.migas a` conserva `--touch-min`; se retiran `flex-wrap` y `overflow-wrap` de `.migas`, que pasan a `app.css` para todos los anchos | bajo |
| `tests/Feature/NavegacionInternaTest.php` (nuevo) | Tests de CA1–CA11 | — |
| `tests/Feature/MovilPacienteTest.php`, `MovilPacientesExpedientesTest.php`, `MovilPersonalTest.php` | Actualizar lo que fija el marcado anterior: `div#migas` → `nav#migas`, reglas de `.migas` y `.migas a`, y «Agendar cita» fuera de las pantallas con migas | medio: son tests de specs liberadas; se cambian solo las aserciones que la 046 reemplaza |
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
  destinos, títulos y entrada marcada del menú.
- **CSS:** con el trait `Concerns/ReglasCss` (reglas de `app.css` y `movil.css`, y `contraste()`).
- **Navegador:** `node docs/design/capturar.mjs --medir` en la verificación final de `/implement` y
  en `/review` (obligatorio por tocar `layouts/app.blade.php`), con un paciente de nombre largo para
  K4.
- **Manual:** el usuario valida en pantalla las migas a 320, 390 y 1440 px.
- Los tests nuevos de interfaz se ejecutan dos veces.

## Modelo de amenazas
La spec no toca datos, autenticación ni permisos, pero pinta nombres de personas y enlaces de vuelta.

| ID | Amenaza (STRIDE) | Categoría OWASP | Componente | Control | Test |
|---|---|---|---|---|---|
| TM1 | Tampering/Spoofing: un parámetro de vuelta en la dirección convierte las migas en una redirección abierta | A01:2025 | `partials/migas`, las 18 vistas | Destinos solo con `route()`; nada lee la petición ni el `Referer` | `NavegacionInternaTest::test_las_migas_ignoran_un_destino_de_vuelta_en_la_direccion` |
| TM2 | Tampering: un nombre con código se ejecuta en la pantalla de quien abre la ficha | A05:2025 | `partials/migas` | Salida escapada de Blade en todos los textos; nunca la salida sin escapar | `NavegacionInternaTest::test_el_nombre_con_codigo_se_pinta_como_texto` |
| TM3 | Information disclosure: las migas enseñan el nombre de un paciente a un rol que no puede abrir la pantalla | A01:2025 | middleware de rol y Gates existentes | Sin cambios: la respuesta de acceso denegado o no encontrada sale antes de pintar la vista | `NavegacionInternaTest::test_una_pantalla_de_otro_rol_responde_como_hoy_sin_migas` (caracterización) |

## Trazabilidad
| Criterio de aceptación | Cambio(s) | Test(s) |
|---|---|---|
| CA1: migas en las 18 pantallas, un bloque, mismo aspecto; las del menú sin migas | parcial, 17 vistas con migas, `cliente-agendar` | `NavegacionInternaTest::test_cada_pantalla_de_segundo_nivel_lleva_sus_migas` (una `nav#migas`, sin `style`, camino esperado) y `::test_las_pantallas_del_menu_no_llevan_migas`; verificación manual: visibles sin desplazarse a 320, 390 y 1440 px, con `capturar.mjs --medir` y en pantalla |
| CA2: cada elemento lleva a su pantalla fija; el último no es enlace | parcial, vistas | `::test_los_enlaces_de_las_migas_llevan_a_destinos_fijos` (cada `href` es el de la tabla; el último elemento no tiene `a`) |
| CA3: navegación con nombre, página actual, separadores ocultos, foco | parcial, `app.css` | `::test_las_migas_son_una_navegacion_accesible` (`aria-label`, un `aria-current="page"`, `aria-hidden` en separadores) y la regla de foco de `.migas a:focus-visible` |
| CA4: enlace distinto del actual sin color; contraste AA | `app.css` | `::test_las_reglas_de_las_migas_distinguen_el_enlace` (subrayado, `--p600`, actual en `--text` sin subrayar) y `::test_el_contraste_de_las_migas_es_aa` |
| CA5: reprogramar con migas en los dos estados | `citas/reprogramar`, `citas/cliente-reprogramar` | `::test_reprogramar_lleva_migas_se_pueda_o_no` (personal y paciente, cita reprogramable y no) |
| CA6: «Cancelar» lleva al penúltimo elemento | vistas de formulario (sin cambio de destino) | `::test_cancelar_lleva_al_mismo_sitio_que_las_migas` (caracterización: comprobado rompiendo el `href` de un «Cancelar») |
| CA7: expediente sin «Volver al paciente» | `expedientes/show` | `::test_el_expediente_ya_no_tiene_volver_al_paciente` |
| CA8: títulos de servicios; ninguno por defecto | `servicios/create`, `servicios/edit` | `::test_ninguna_pantalla_usa_el_titulo_por_defecto` (`.topbar-title` y `<title>` de las 18) |
| CA9: el menú marca la sección | `layouts/app` | `::test_el_menu_marca_la_seccion_de_la_pantalla` (un solo `.nav-item[aria-current="page"]`, con el texto del primer elemento de las migas); `capturar.mjs --medir` |
| CA10: camino largo a 320 px | `app.css`, `movil.css` | `::test_las_migas_pueden_pasar_de_linea` (reglas `flex-wrap`, `overflow-wrap`, `--touch-min`); verificación manual: `capturar.mjs --medir` a 320 px con un nombre largo, sin desplazamiento horizontal |
| CA11 (abuso): destinos fijos, nombre como texto, sin migas para otro rol | parcial; sin cambios en permisos | TM1, TM2, TM3 |
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
- **D2 · El camino se declara en la vista.** Las vistas ya conocen el prefijo de rutas del rol
  (`$rp`, `$base`) y sus datos; así no cambia ningún controlador ni su test.
- **D3 · Se conservan `id="migas"`, `.migas` y `.migas-actual`.** Cambia el elemento (`div` → `nav`
  con `ol`), no los nombres: los tests de la 033–035 solo necesitan ajustar el elemento y las reglas.
- **D4 · Separador como elemento con `aria-hidden`.** Un separador pintado con `::before` puede
  leerse en algunos lectores de pantalla; como `<span aria-hidden="true">` se comprueba en el HTML.
- **D5 · Expedientes marcan «Pacientes» en el menú** (CA9), también para la dentista, que tiene
  entrada «Expedientes»: esa entrada queda marcada solo en su listado. Coherente con el padre fijo
  del expediente (spec, Decisiones).
- **D6 · Las reglas de salto de línea pasan de `movil.css` a `app.css`** para valer a todos los
  anchos (acta de diseño, «camino largo»).
- **D7 · Orden respecto de la 045:** plan, tareas y análisis ahora; `/implement` empieza cuando la
  045 esté fusionada en `main`, y la versión de `system.md` es la siguiente a la que deje
  (decisión del usuario, 2026-11-12).
- **D8 · Tamaño:** 11 criterios y 7 specs extendidas; se mantiene junta (decisión del usuario,
  2026-11-12, al aprobar la spec).
- **D9 · «Agendar cita» sin migas:** es una entrada del menú (decisión del usuario, 2026-11-12;
  spec corregida).
- Sin ADR: no hay dependencias nuevas ni cambio de arquitectura.

## Impacto en arquitectura
- `docs/architecture.md` → «Frontend»: el parcial `partials/migas` y la regla de qué pantallas lo
  llevan.
- `AGENTS.md` → «Reglas y estilo» (vistas): una línea sobre las migas en pantallas de segundo nivel.
- `docs/design/system.md` → «Migas de pan», tabla de deuda (DS15, parte de títulos) y Decisiones.

## Riesgos y mitigaciones
- **Tests de specs liberadas que fijan el marcado anterior** (`MovilPacienteTest`,
  `MovilPacientesExpedientesTest`, `MovilPersonalTest`): se cambian solo las aserciones que la 046
  reemplaza (elemento, reglas de color, «Agendar cita»), en la misma tarea que cambia el marcado.
- **El menú es de todos los roles:** un patrón mal escrito marcaría dos entradas; el test de CA9
  exige una sola entrada marcada en cada una de las 18 pantallas y `capturar.mjs --medir` lo mide en
  el navegador.
- **Choque con la 045** en `system.md`, `architecture.md` y `CHANGELOG`: se evita con D7.
- **Nombre largo en el escritorio:** el camino puede ocupar dos líneas en fichas estrechas; aceptado
  en el acta de diseño y validado por el usuario en pantalla.
- **Contraste de `--p600` sobre `--bg`** calculado a mano (≈ 6,5:1): lo fija el test de contraste; si
  no llegara a 4,5:1, se vuelve a `/design-spec 046 --fix`.
